<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Models\ProductOrderCourierMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PathaoController extends Controller
{
    private const BASE_URL = 'https://api-hermes.pathao.com/aladdin/api/v1';

    /**
     * Get Pathao order short info by consignment_id.
     * API: GET /aladdin/api/v1/orders/{{consignment_id}}/info
     * Public route (no auth).
     */
    public function getOrderStatus(string $pathaoId): JsonResponse
    {
        $courierMethod = ProductOrderCourierMethod::where('title', 'like', '%Pathao%')
            ->where('status', 'active')
            ->first();

        if (!$courierMethod) {
            return response()->json(['success' => false, 'message' => 'Pathao courier method not configured.'], 404);
        }

        $config = $courierMethod->config ?? [];
        Log::info('Pathao config line '. __FILE__ . ' ' . __LINE__ . ' pathao_id: ' . $pathaoId . ' config: '. url('/') . json_encode($config));
        $accessToken = $this->getAccessToken((int) $courierMethod->id, $config);
        if (!$accessToken) {
            return response()->json(['success' => false, 'message' => 'Failed to get Pathao access token.'], 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->get(self::BASE_URL . '/orders/' . $pathaoId . '/info');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(
                $response->json() ?? ['message' => $response->body()],
                $response->status()
            );
        } catch (\Throwable $e) {
            Log::error('Pathao getOrderStatus exception line '. __LINE__ . ' pathao_id: ' . $pathaoId, ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a Pathao order (consignment) for the given ProductOrder.
     * Uses courier method config: client_id, client_secret, grant_type, username, password.
     * Optional delivery_info: pathao_city_id, pathao_zone_id, pathao_area_id (Pathao will auto-fill from address if omitted).
     * API: POST {{base_url}}/aladdin/api/v1/orders
     */
    public function createOrder(ProductOrder $order, $courierMethodId)
    {
        $courierMethod = ProductOrderCourierMethod::find($courierMethodId);
        if (!$courierMethod) {
            Log::warning('Pathao: courier method not found line '. __LINE__ . ' order_id: ' . $order->id, ['id' => $courierMethodId]);
            return null;
        }

        $config = $courierMethod->config ?? [];
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if (empty($clientId) || empty($clientSecret) || empty($username) || empty($password)) {
            Log::warning('Pathao: missing credentials in config line '. __LINE__ . ' order_id: ' . $order->id);
            return null;
        }

        $accessToken = $this->getAccessToken($courierMethodId, $config);
        if (!$accessToken) {
            Log::warning('Pathao: failed to get access token line '. __LINE__ . ' order_id: ' . $order->id);
            return null;
        }

        $storeId = $this->getStoreId($courierMethodId, $accessToken);
        if (!$storeId) {
            Log::warning('Pathao: failed to get store id line '. __LINE__ . ' order_id: ' . $order->id);
            return null;
        }

        $order->load(['customer', 'order_products.product']);
        $customer = $order->customer;
        $deliveryInfo = $order->delivery_info ?? [];

        $recipientName = trim($customer ? ($customer->name ?? '') : '');
        $recipientPhone = $this->normalizePhone($customer ? ($customer->phone ?? '') : '');
        $recipientAddress = ($customer && !empty($customer->address))
            ? trim($customer->address)
            : trim($deliveryInfo['recipient_address'] ?? $deliveryInfo['address'] ?? '');

        if ($recipientAddress === '' && !empty($order->address)) {
            $recipientAddress = trim((string) $order->address);
        }

        $this->ensurePathaoRecipientShape($recipientName, $recipientPhone, $recipientAddress, $order->id);

        $itemNames = $order->order_products->map(function ($op) {
            return $op->product ? $op->product->name : 'Item';
        })->toArray();
        $itemDescription = implode(', ', array_slice($itemNames, 0, 10));
        if (count($itemNames) > 10) {
            $itemDescription .= '...';
        }
        $itemQuantity = (int) max(1, $order->order_products->sum('qty'));
        $itemWeight = (float) ($deliveryInfo['pathao_item_weight'] ?? $deliveryInfo['item_weight'] ?? 1);
        $itemWeight = max(0.5, min(10, $itemWeight)); // Pathao: min 0.5 kg, max 10 kg

        // delivery_type: 48 = Normal Delivery, 12 = On Demand
        $deliveryType = (int) ($deliveryInfo['pathao_delivery_type'] ?? $deliveryInfo['delivery_type'] ?? 48);
        // item_type: 1 = Document, 2 = Parcel
        $itemType = (int) ($deliveryInfo['pathao_item_type'] ?? $deliveryInfo['item_type'] ?? 2);

        $orderPayload = [
            'store_id' => $storeId,
            'merchant_order_id' => $order->order_code ?? ('ORD' . $order->id . '-' . time()),
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'recipient_address' => substr($recipientAddress, 0, 220),
            'delivery_type' => $deliveryType,
            'item_type' => $itemType,
            'special_instruction' => substr((string) ($order->note ?? ''), 0, 500),
            'item_quantity' => $itemQuantity,
            'item_weight' => (string) $itemWeight,
            'item_description' => $itemDescription,
            'amount_to_collect' => (int) round($order->total),
        ];

        // Optional: only include if set (do not send null per Pathao docs)
        $recipientCity = (int) ($deliveryInfo['pathao_city_id'] ?? $deliveryInfo['recipient_city'] ?? 0);
        $recipientZone = (int) ($deliveryInfo['pathao_zone_id'] ?? $deliveryInfo['recipient_zone'] ?? 0);
        $recipientArea = (int) ($deliveryInfo['pathao_area_id'] ?? $deliveryInfo['recipient_area'] ?? 0);
        if ($recipientCity > 0) {
            $orderPayload['recipient_city'] = $recipientCity;
        }
        if ($recipientZone > 0) {
            $orderPayload['recipient_zone'] = $recipientZone;
        }
        if ($recipientArea > 0) {
            $orderPayload['recipient_area'] = $recipientArea;
        }
        $secondaryPhone = $this->normalizePhone($deliveryInfo['recipient_secondary_phone'] ?? $deliveryInfo['alternative_phone'] ?? '');
        if (strlen($secondaryPhone) === 11) {
            $orderPayload['recipient_secondary_phone'] = $secondaryPhone;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::BASE_URL . '/orders', $orderPayload);

            if ($response->successful()) {
                $body = $response->json();
                $order->is_couriered = 1;
                $order->courier_info = array_merge($body['data'] ?? $body, [
                    'courier' => 'pathao',
                    'merchant_order_id' => $orderPayload['merchant_order_id'],
                    'order_status_url' => route('pathao.order-status', $body['data']['consignment_id']),
                    'pathao_status_url' => "https://merchant.pathao.com/tracking?consignment_id=" . $body['data']['consignment_id'] . "&phone=" . $recipientPhone,
                    'date' => now()->format('Y-m-d H:i:s'),
                ]);
                $order->shipping_date = now()->format('Y-m-d H:i:s');
                $order->save();
                return $body;
            }

            Log::warning('Pathao create order failed line '. __LINE__ . ' order_id: ' . $order->id, [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Pathao create order exception line '. __LINE__ . ' order_id: ' . $order->id, [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Issue access token. API: POST {{base_url}}/aladdin/api/v1/issue-token
     * Body: client_id, client_secret, grant_type (password), username, password
     */
    private function getAccessToken(int $courierMethodId, array $config): ?string
    {
        $cacheKey = 'pathao_access_token_' . $this->pathaoApplicationCacheKeySuffix() . '_courier_' . $courierMethodId;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $credData = [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'grant_type' => $config['grant_type'] ?? 'password',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
        ];

        $tokenResponse = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post(self::BASE_URL . '/issue-token', $credData);

        if (!$tokenResponse->successful()) {
            Log::error('Pathao issue-token failed line '. __LINE__ . ' courier_method_id: ' . $courierMethodId, [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body(),
            ]);
            return null;
        }

        $data = $tokenResponse->json();
        $accessToken = $data['access_token'] ?? null;
        if ($accessToken) {
            $expiresIn = (int) ($data['expires_in'] ?? 432000); // default 5 days in seconds
            Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 60));
        }
        return $accessToken;
    }

    private function getStoreId(int $courierMethodId, string $accessToken): ?int
    {
        $cacheKey = 'pathao_store_id_' . $this->pathaoApplicationCacheKeySuffix() . '_courier_' . $courierMethodId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        $storeResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get(self::BASE_URL . '/stores');

        if (!$storeResponse->successful()) {
            return null;
        }

        $data = $storeResponse->json();
        $stores = $data['data']['data'] ?? $data['data'] ?? [];
        $firstStore = is_array($stores) ? ($stores[0] ?? null) : null;
        $storeId = $firstStore['store_id'] ?? null;
        if ($storeId !== null) {
            Cache::put($cacheKey, $storeId, 86400); // 24h
        }
        return $storeId !== null ? (int) $storeId : null;
    }

    /**
     * Unique per application host. Non-alphanumeric characters become underscores.
     */
    private function pathaoApplicationCacheKeySuffix(): string
    {
        $host = request()->getHost();
        if ($host === '') {
            $parsed = parse_url((string) config('app.url'), PHP_URL_HOST);
            $host = is_string($parsed) && $parsed !== '' ? $parsed : 'default';
        }
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $host);
        $slug = trim((string) $slug, '_');

        return $slug !== '' ? $slug : 'default';
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }
        return $digits;
    }

    /**
     * Pathao requires recipient_name (3–100), recipient_phone (11 digits), recipient_address (10–220).
     * Pad or trim with '_' / '-' / leading zeros so the order can proceed when data is slightly short.
     */
    private function ensurePathaoRecipientShape(string &$recipientName, string &$recipientPhone, string &$recipientAddress, int $orderId): void
    {
        $recipientName = trim($recipientName);
        if (strlen($recipientName) < 3) {
            $recipientName .= str_repeat('_', 3 - strlen($recipientName));
            Log::info('Pathao: recipient_name padded to minimum length line '. __LINE__ . ' order_id: ' . $orderId);
        }
        if (strlen($recipientName) > 100) {
            $recipientName = substr($recipientName, 0, 100);
        }

        $recipientPhone = preg_replace('/\D/', '', (string) $recipientPhone);
        if (strlen($recipientPhone) > 11) {
            $recipientPhone = substr($recipientPhone, -11);
        }
        while (strlen($recipientPhone) < 11) {
            $recipientPhone = '0' . $recipientPhone;
        }
        if (strlen($recipientPhone) > 11) {
            $recipientPhone = substr($recipientPhone, 0, 11);
        }

        $recipientAddress = trim($recipientAddress);
        if (strlen($recipientAddress) < 10) {
            $recipientAddress .= str_repeat('-', 10 - strlen($recipientAddress));
            Log::info('Pathao: recipient_address padded to minimum length line '. __LINE__ . ' order_id: ' . $orderId);
        }
        if (strlen($recipientAddress) > 220) {
            $recipientAddress = substr($recipientAddress, 0, 220);
        }
    }

    /**
     * Bulk create Pathao orders. API: POST {{base_url}}/aladdin/api/v1/orders/bulk
     * Request body: { "orders": [ { store_id, merchant_order_id, recipient_name, recipient_phone, ... } ] }
     * Success: 202 Accepted.
     */
    public function setBulkOrders(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:product_orders,id',
        ]);
        $ids = $request->input('ids');
        $orders = ProductOrder::with(['customer', 'order_products.product'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        $courierMethod = ProductOrderCourierMethod::where('title', 'like', '%Pathao%')
            ->where('status', 'active')
            ->first();
        if (!$courierMethod) {
            return response()->json(['success' => false, 'message' => 'Pathao courier method not configured.'], 404);
        }

        $config = $courierMethod->config ?? [];
        $accessToken = $this->getAccessToken((int) $courierMethod->id, $config);
        if (!$accessToken) {
            return response()->json(['success' => false, 'message' => 'Failed to get Pathao access token.'], 500);
        }

        $storeId = $this->getStoreId((int) $courierMethod->id, $accessToken);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'Failed to get Pathao store ID.'], 500);
        }

        $payloads = [];
        foreach ($orders as $order) {
            if ($order->is_couriered != 1) {
                $customer = $order->customer;
                $deliveryInfo = $order->delivery_info ?? [];
                $recipientName = trim($customer ? ($customer->name ?? '') : '');
                $recipientPhone = $this->normalizePhone($customer ? ($customer->phone ?? '') : '');
                $recipientAddress = ($customer && !empty($customer->address))
                    ? trim($customer->address)
                    : trim($deliveryInfo['recipient_address'] ?? $deliveryInfo['address'] ?? '');
                if ($recipientAddress === '' && !empty($order->address)) {
                    $recipientAddress = trim((string) $order->address);
                }

                $this->ensurePathaoRecipientShape($recipientName, $recipientPhone, $recipientAddress, $order->id);

                $itemNames = $order->order_products->map(function ($op) {
                    return $op->product ? $op->product->name : 'Item';
                })->toArray();
                $itemDescription = implode(', ', array_slice($itemNames, 0, 10));
                if (count($itemNames) > 10) {
                    $itemDescription .= '...';
                }
                $itemQuantity = (int) max(1, $order->order_products->sum('qty'));
                $itemWeight = (float) ($deliveryInfo['pathao_item_weight'] ?? $deliveryInfo['item_weight'] ?? 1);
                $itemWeight = max(0.5, min(10, $itemWeight));
                $deliveryType = (int) ($deliveryInfo['pathao_delivery_type'] ?? $deliveryInfo['delivery_type'] ?? 48);
                $itemType = (int) ($deliveryInfo['pathao_item_type'] ?? $deliveryInfo['item_type'] ?? 2);

                $payload = [
                    'store_id' => $storeId,
                    // 'merchant_order_id' => $order->order_code ?? ('ORD' . $order->id . '-' . time()),
                    'merchant_order_id' => $order->id,
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone,
                    'recipient_address' => $recipientAddress,
                    'delivery_type' => $deliveryType,
                    'item_type' => $itemType,
                    'special_instruction' => substr((string) ($order->note ?? ''), 0, 500),
                    'item_quantity' => $itemQuantity,
                    'item_weight' => (string) $itemWeight,
                    'item_description' => $itemDescription,
                    'amount_to_collect' => (int) round($order->total),
                ];

                $recipientCity = (int) ($deliveryInfo['pathao_city_id'] ?? $deliveryInfo['recipient_city'] ?? 0);
                $recipientZone = (int) ($deliveryInfo['pathao_zone_id'] ?? $deliveryInfo['recipient_zone'] ?? 0);
                $recipientArea = (int) ($deliveryInfo['pathao_area_id'] ?? $deliveryInfo['recipient_area'] ?? 0);
                if ($recipientCity > 0) {
                    $payload['recipient_city'] = $recipientCity;
                }
                if ($recipientZone > 0) {
                    $payload['recipient_zone'] = $recipientZone;
                }
                if ($recipientArea > 0) {
                    $payload['recipient_area'] = $recipientArea;
                }
                $secondaryPhone = $this->normalizePhone($deliveryInfo['recipient_secondary_phone'] ?? $deliveryInfo['alternative_phone'] ?? '');
                if (strlen($secondaryPhone) === 11) {
                    $payload['recipient_secondary_phone'] = $secondaryPhone;
                }

                $payloads[] = $payload;
            }
        }

        if (count($payloads) === 0) {
            return response()->json(['success' => false, 'message' => 'No valid orders to send.'], 422);
        }

        foreach ($payloads as $orderPayload) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post(self::BASE_URL . '/orders', $orderPayload);

                if ($response->successful()) {
                    $body = $response->json();
                    $order->is_couriered = 1;
                    $order->courier_info = array_merge($body['data'] ?? $body, [
                        'courier' => 'pathao',
                        'merchant_order_id' => $orderPayload['merchant_order_id'],
                        'order_status_url' => route('pathao.order-status', $body['data']['consignment_id']),
                        'pathao_status_url' => "https://merchant.pathao.com/tracking?consignment_id=" . $body['data']['consignment_id'] . "&phone=" . $recipientPhone,
                        'date' => now()->format('Y-m-d H:i:s'),
                    ]);
                    $order->shipping_date = now()->format('Y-m-d H:i:s');
                    $order->save();
                    return $body;
                }

                Log::warning('Pathao create order failed line '. __LINE__ . ' order_id: ' . $order->id, [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Pathao create order exception line '. __LINE__ . ' order_id: ' . $order->id, [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk order creation accepted for ' . count($payloads) . ' order(s).',
            'count' => count($payloads),
        ]);

        // try {
        //     $response = Http::withHeaders([
        //         'Authorization' => 'Bearer ' . $accessToken,
        //         'Content-Type' => 'application/json; charset=UTF-8',
        //     ])->timeout(60)->post(self::BASE_URL . '/orders/bulk', ['orders' => $payloads]);

        //     if ($response->status() === 202 || $response->successful()) {
        //         $submittedIds = $orders->pluck('id')->toArray();
        //         $courierInfo = array_merge($response->json() ?? [], [
        //             'courier' => 'pathao',
        //             'bulk_submitted_at' => now()->toIso8601String(),
        //             'orders_count' => count($payloads),
        //         ]);
        //         ProductOrder::whereIn('id', $submittedIds)->update([
        //             'is_couriered' => 1,
        //             'courier_info' => json_encode($courierInfo),
        //         ]);
        //         return response()->json([
        //             'success' => true,
        //             'message' => $response->json()['message'] ?? 'Bulk order creation accepted for ' . count($payloads) . ' order(s).',
        //             'count' => count($payloads),
        //         ]);
        //     }

        //     Log::warning('Pathao bulk order failed', [
        //         'status' => $response->status(),
        //         'body' => $response->body(),
        //     ]);
        //     return response()->json([
        //         'success' => false,
        //         'message' => $response->json()['message'] ?? $response->body() ?? 'Pathao bulk order request failed.',
        //     ], $response->status() >= 400 ? $response->status() : 500);
        // } catch (\Throwable $e) {
        //     Log::error('Pathao setBulkOrders exception', ['message' => $e->getMessage()]);
        //     return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        // }
    }

    public function handlePathaoCallback(Request $request)
    {
        /**
            {
                consignment_id: "DL121224VS8TTJ",
                merchant_order_id: "TS-123",
                updated_at: "2024-12-27 23:49:43",
                timestamp: "2024-12-27T17:49:43+00:00",
                store_id: 130820,
                event: "order.created",
                delivery_fee: 83.46
            }
         */
        // Log::info('Pathao callback received', ['payload' => request()->all()]);

        $payload = request()->all();
        $event = $payload['event'] ?? null;
        if ($event) {
            $eventParts = explode('.', (string) $event);
            $event_name = $eventParts[1] ?? $event;
            $merchantOrderId = $payload['merchant_order_id'] ?? null;
            $consignmentId = $payload['consignment_id'] ?? null;
            $updatedAt = $payload['updated_at'] ?? null;
            $deliveryFee = $payload['delivery_fee'] ?? 0;

            $order = null;
            if ($merchantOrderId !== null && $merchantOrderId !== '') {
                $order = is_numeric($merchantOrderId)
                    ? ProductOrder::find($merchantOrderId)
                    : ProductOrder::where('order_code', $merchantOrderId)->first();
            }

            if ($order) {
                $order->is_couriered = 1;
                $existingCourierInfo = is_array($order->courier_info) ? $order->courier_info : [];
                $order->courier_info = array_merge($existingCourierInfo, [
                    'courier'           => 'pathao',
                    'status'            => $event_name,
                    'consignment_id'    => $consignmentId,
                    'updated_at'        => $updatedAt,
                    'pathao_status_url' => $consignmentId
                        ? 'https://merchant.pathao.com/tracking?consignment_id=' . $consignmentId . '&phone=' . $this->normalizePhone(optional($order->customer)->phone ?? '')
                        : ($existingCourierInfo['pathao_status_url'] ?? ''),
                ]);

                $tracks = is_array($order->order_tracks) ? $order->order_tracks : [];
                $tracks[] = [
                    'status'         => $event_name,
                    'consignment_id' => $consignmentId,
                    'updated_at'     => $updatedAt,
                ];
                $order->order_tracks = $tracks;

                if($event_name == 'created' && $payload['delivery_fee'] > 0){
                    $other_charges = $order->other_charges ?? [
                        "extra_charge" => 0,
                        "delivery_charge" => $deliveryFee,
                    ];
                    $order->other_charges = [...$other_charges, "delivery_charge" => $deliveryFee];
                    $order->delivery_fee = $deliveryFee;
                    $total = ($order->subtotal + $deliveryFee + $order->other_charge_amount) - $order->calculated_discount_amount - $order->round_off_from_total;
                    $order->total = round($total);
                    $order->save();
                }

                $order->save();
            }
        }

        $response = response()->json([
            'success' => true,
            'message' => 'Pathao callback received',
        ], 202);

        $integrationSecret = (string) config('services.pathao.webhook_integration_secret', '');
        if ($integrationSecret !== '') {
            $response->headers->set('X-Pathao-Merchant-Webhook-Integration-Secret', $integrationSecret);
        }

        return $response;
    }

    /**
     * Pathao courier status page: view loads first, then Vue fetches same URL for JSON.
     * GET /orders/pathao-courier-status
     * ?courier_status=delivered&page=2 (optional, for AJAX)
     * Returns: view (initial load) or JSON { analytics: [{ status, count }], data: paginator } (AJAX).
     */
    // no need this function because we have add function courierWiseOrders in ProductOrderController
    public function pathaoCourierStatus(Request $request) 
    {
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $query = ProductOrder::where('courier_info->courier', $request->get('courier', 'pathao'))
                ->with(['customer:id,name,phone']);

            $fixedEvents = [
                'updated', 'pickup-requested', 'assigned-for-pickup', 'picked', 'pickup-failed',
                'pickup-cancelled', 'at-the-sorting-hub', 'assigned-for-delivery', 'delivered',
                'partial-delivery', 'returned', 'delivery-failed', 'on-hold', 'paid', 'paid-return', 'exchanged',
            ];

            if ($request->filled('courier_status')) {
                $status = (string) $request->courier_status;
                // $query->whereRaw('order_tracks LIKE ?', ['%"status":"' . $status . '"%']);
                $query->where('courier_info->status', $status);
            }

            $orders = $query->orderBy('updated_at', 'desc')->paginate((int) $request->get('per_page', 15));

            // Existing: analytics from order_tracks (event counts)
            // $ordersForTracks = ProductOrder::where('courier_info->courier', 'pathao')->get(['id', 'order_tracks']);
            // $countsByStatus = collect();
            // foreach ($ordersForTracks as $order) {
            //     $tracks = is_array($order->order_tracks) ? $order->order_tracks : [];
            //     foreach ($tracks as $track) {
            //         $status = $track['status'] ?? 'unknown';
            //         $countsByStatus->put($status, $countsByStatus->get($status, 0) + 1);
            //     }
            // }
            // $analytics = collect($fixedEvents)->map(function ($status) use ($countsByStatus) {
            //     $count = $countsByStatus->get($status, 0);
            //     return ['status' => $status, 'count' => $count];
            // })->filter(function ($item) {
            //     return $item['count'] > 0;
            // })->values()->toArray();

            // Analytics from courier_info->status (count orders per latest status)
            $ordersForAnalytics = ProductOrder::where('courier_info->courier', 'pathao')->get(['id', 'courier_info']);
            $countsByStatus = $ordersForAnalytics->groupBy(function ($o) {
                return $o->courier_info['status'] ?? 'unknown';
            })->map->count();
            $analytics = collect($fixedEvents)->map(function ($eventName) use ($countsByStatus) {
                $count = $countsByStatus->get($eventName, 0);
                return ['status' => $eventName, 'count' => $count];
            })->filter(function ($item) {
                return $item['count'] > 0;
            })->values()->toArray();

            return response()->json([
                'analytics' => $analytics,
                'data' => $orders,
            ]);
        }

        return view('backend.product_order_management.pathao_courier_status');
    }
}
