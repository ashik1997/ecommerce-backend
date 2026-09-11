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

class CarryBeeController extends Controller
{
    private const BASE_URL = 'https://developers.carrybee.com';

    //   curl --location '{{base_url}}/api/v2/orders/{{consignment_id}}/details' \
    // --header 'Client-ID: {{client_id}}' \
    // --header 'Client-Secret: {{client_secret}}' \
    // --header 'Client-Context: {{client_context}}'

    // {
    //     "error": false,
    //     "message": "Order details",
    //     "data": {
    //         "transfer_status": "In transit",
    //         "store_id": "a1b2c3",
    //         "consignment_id": "FX1212124433",
    //         "merchant_order_id": "order-1234",
    //         "recipient_name": "recipient name",
    //         "recipient_phone": "8801652241276",
    //         "recipient_secondary_phone": "8801652241276",
    //         "recipient_address": "recipient address",
    //         "collectable_amount": "1000",
    //         "collected_amount": "0",
    //         "cod_fee": 0,
    //         "delivery_fee": "105",
    //         "attempt": 0,
    //         "updated_at": "2025-07-30T10:11:12+00:00"
    //     }
    // }

    public function getOrderStatus(string $carryBeeId): JsonResponse
    {
        $courierMethod = ProductOrderCourierMethod::where('title', 'like', '%CarryBee%')
            ->where('status', 'active')
            ->first();

        if (!$courierMethod) {
            return response()->json([
                'success' => false,
                'message' => 'CarryBee courier method not configured.'
            ], 404);
        }

        $config = $courierMethod->config ?? [];

        try {
            $response = Http::withHeaders([
                'Client-ID' => $config['client_id'] ?? '',
                'Client-Secret' => $config['client_secret'] ?? '',
                'Client-Context' => $config['client_context'] ?? '',
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->get(self::BASE_URL . '/api/v2/orders/' . $carryBeeId . '/details');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()['data'] ?? $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json()['message'] ?? 'API Error',
                'error' => $response->json()
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('CarryBee getOrderStatus error', [
                'carrybee_id' => $carryBeeId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //   curl --location '{{base_url}}/api/v2/orders' \
    // --header 'Content-Type: application/json' \
    // --header 'Client-ID: {{client_id}}' \
    // --header 'Client-Secret: {{client_secret}}' \
    // --header 'Client-Context: {{client_context}}'
    // --data '{
    //     "store_id": "{{store_id}}",
    //     "merchant_order_id": "order-1234",
    //     "delivery_type": 1,
    //     "product_type": 1,
    //     "recipient_phone": "01652241276",
    //     "recipient_secendary_phone": "nullable recipient secondary phone number",
    //     "recipient_name": "recipient name",
    //     "recipient_address": "receipient address",
    //     "city_id": {{city_id}},
    //     "zone_id": {{zone_id}},
    //     "area_id": {{area_id}},
    //     "special_instruction": "null_or_string",
    //     "product_description": "null_or_string",
    //     "item_weight": 500,
    //     "item_quantity": 2,
    //     "collectable_amount": 15000,
    //     "is_closed": false
    // }
    // '

    public function createOrder(ProductOrder $order, $courierMethodId)
    {
        $courierMethod = ProductOrderCourierMethod::find($courierMethodId);
        if (!$courierMethod) return null;

        $config = $courierMethod->config ?? [];

        $headers = [
            'Client-ID' => $config['client_id'] ?? '',
            'Client-Secret' => $config['client_secret'] ?? '',
            'Client-Context' => $config['client_context'] ?? '',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $storeInfo = $this->getStoreInfo($courierMethodId);
        if (!$storeInfo) return null;

        $order->load(['customer', 'order_products.product']);
        $customer = $order->customer;
        $deliveryInfo = $order->delivery_info ?? [];

        $recipientName = trim($customer->name ?? 'Customer');

        //  fix phone number to Bangladesh format (remove leading zeros, add country code)
        $recipientPhone = $this->formatPhoneBD($customer->phone ?? '');

        $recipientAddress = trim($customer->address ?? $order->address ?? '');

        $itemQuantity = max(1, $order->order_products->sum('qty'));
        $itemWeight = max(0.5, (float) ($deliveryInfo['item_weight'] ?? 1));

        $orderPayload = [
            "store_id" => $storeInfo['store_id'],

            "merchant_order_id" => $order->order_code,

            "delivery_type" => 1,
            "product_type" => 1,

            "recipient_name" => $recipientName,
            "recipient_phone" => $recipientPhone,
            "recipient_secondary_phone" => null,

            "recipient_address" => substr($recipientAddress, 0, 220),

            "city_id" => $storeInfo['city_id'],
            "zone_id" => $storeInfo['zone_id'],
            "area_id" => $storeInfo['area_id'],

            "special_instruction" => $order->note ?? null,
            "product_description" => "Order Items",

            "item_weight" => $itemWeight,
            "item_quantity" => $itemQuantity,

            "collectable_amount" => (int) round($order->total),

            "is_closed" => false
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post(self::BASE_URL . '/api/v2/orders', $orderPayload);

            if ($response->successful()) {
                $data = $response->json();

                $order->update([
                    'is_couriered' => 1,
                    'shipping_date' => now(),
                    'courier_info' => [
                        'courier' => 'carrybee',
                        'consignment_id' => $data['data']['consignment_id'] ?? null,
                        'merchant_order_id' => $orderPayload['merchant_order_id'],
                    ]
                ]);

                return $data;
            }

            Log::error('CarryBee API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('CarryBee Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    
    //  curl --location '{{base_url}}/api/v2/orders-bulk' \
    // --header 'Content-Type: application/json' \
    // --header 'Client-ID: {{client_id}}' \
    // --header 'Client-Secret: {{client_secret}}' \
    // --header 'Client-Context: {{client_context}}'
    // --data '{
    //     "orders": [
    //         {
    //             "store_id": "{{store_id}}",
    //             "merchant_order_id": "order-1234",
    //             "delivery_type": 1,
    //             "product_type": 1,
    //             "recipient_phone": "01652241276",
    //             "recipient_secendary_phone": "nullable recipient secondary phone number",
    //             "recipient_name": "recipient name",
    //             "recipient_address": "receipient address",
    //             "city_id": {{city_id}},
    //             "zone_id": {{zone_id}},
    //             "area_id": {{area_id}},
    //             "special_instruction": "null_or_string",
    //             "product_description": "null_or_string",
    //             "item_weight": 500,
    //             "item_quantity": 2,
    //             "collectable_amount": 15000,
    //             "is_closed": false
    //         },
    //         {
    //             "store_id": "{{store_id}}",
    //             "merchant_order_id": "order-1234",
    //             "delivery_type": 1,
    //             "product_type": 1,
    //             "recipient_phone": "01652241276",
    //             "recipient_secendary_phone": "nullable recipient secondary phone number",
    //             "recipient_name": "recipient name",
    //             "recipient_address": "receipient address",
    //             "city_id": "{{city_id}}",
    //             "zone_id": "{{zone_id}}",
    //             "area_id": "{{area_id}}",
    //             "special_instruction": "null_or_string",
    //             "product_description": "null_or_string",
    //             "item_weight": 500,
    //             "item_quantity": 2,
    //             "collectable_amount": 15000
    //         }
    //     ]
    // }
    // '
    // {
    //     "error": false,
    //     "message": "Order list accepted to be processed"
    // }
    public function setBulkOrders(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:product_orders,id',
        ]);

        $orders = ProductOrder::with(['customer', 'order_products.product'])
            ->whereIn('id', $request->ids)
            ->get();

        $courierMethod = ProductOrderCourierMethod::where('title', 'like', '%CarryBee%')
            ->where('status', 'active')
            ->first();

        if (!$courierMethod) {
            return response()->json(['success' => false, 'message' => 'CarryBee not configured'], 404);
        }

        $config = $courierMethod->config ?? [];

        $headers = [
            'Client-ID' => $config['client_id'],
            'Client-Secret' => $config['client_secret'],
            'Client-Context' => $config['client_context'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $storeInfo = $this->getStoreInfo($courierMethod->id);
        if (!$storeInfo) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 500);
        }

        $payloads = [];

        foreach ($orders as $order) {
            if ($order->is_couriered == 1) continue;

            $customer = $order->customer;
            $deliveryInfo = $order->delivery_info ?? [];

            $phone = $this->formatPhoneBD($customer->phone ?? '');

            $payloads[] = [
                "store_id" => $storeInfo['store_id'],
                "merchant_order_id" => (string) $order->id,

                "delivery_type" => 1,
                "product_type" => 1,

                "recipient_name" => $customer->name ?? 'Customer',
                "recipient_phone" => $phone,
                "recipient_secondary_phone" => null,

                "recipient_address" => substr($customer->address ?? $order->address, 0, 220),

                "city_id" => $storeInfo['city_id'],
                "zone_id" => $storeInfo['zone_id'],
                "area_id" => $storeInfo['area_id'],

                "special_instruction" => $order->note ?? null,
                "product_description" => "Order Items",

                "item_weight" => 1,
                "item_quantity" => max(1, $order->order_products->sum('qty')),

                "collectable_amount" => (int) round($order->total),

                "is_closed" => false
            ];
        }

        if (empty($payloads)) {
            return response()->json(['success' => false, 'message' => 'No valid orders'], 422);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post(self::BASE_URL . '/api/v2/orders-bulk', [
                    "orders" => $payloads
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bulk order submitted successfully',
                    'response' => $response->json()
                ]);
            }

            Log::error('CarryBee Bulk Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API Error',
                'error' => $response->json()
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('CarryBee Bulk Exception', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // curl --location '{{base_url}}/api/v2/stores' \
    // --header 'Client-ID: {{client_id}}' \
    // --header 'Client-Secret: {{client_secret}}' \
    // --header 'Client-Context: {{client_context}}'

    // {
    //     "error": false,
    //     "message": "Store list",
    //     "data": {
    //         "stores": [
    //             {
    //                 "id": "abcd-1234",
    //                 "name": "Store of Anik at 20250729-085709",
    //                 "contact_person_name": "Anik",
    //                 "contact_person_number": "8801652241276",
    //                 "contact_person_secondary_number": "8801652241274",
    //                 "address": "address of Store of Anik at 20250729-085709",
    //                 "city_id": 14,
    //                 "zone_id": 5,
    //                 "area_id": 282,
    //                 "is_active": false,
    //                 "is_approved": false,
    //                 "is_default_pickup_store": false,
    //                 "is_default_return_store": false
    //             },
    //             {
    //                 "id": "wxyz-1234",
    //                 "name": "Store of Anik at 20250729-105709",
    //                 "contact_person_name": "Anik",
    //                 "contact_person_number": "8801652241271",
    //                 "address": "address of Store of Anik at 20250729-105709",
    //                 "city_id": 14,
    //                 "zone_id": 5,
    //                 "area_id": 111,
    //                 "is_active": true,
    //                 "is_approved": true,
    //                 "is_default_pickup_store": true,
    //                 "is_default_return_store": true
    //             }
    //         ],
    //         "pending_count": 1
    //     }
    // }

    private function getStoreInfo(int $courierMethodId): ?array
    {
        $cacheKey = 'carrybee_store_info_' . $courierMethodId;

        // return full cached data
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $courierMethod = ProductOrderCourierMethod::find($courierMethodId);
        if (!$courierMethod) {
            Log::warning('CarryBee: courier method not found');
            return null;
        }

        $config = $courierMethod->config ?? [];

        try {
            $response = Http::withHeaders([
                'Client-ID' => $config['client_id'] ?? '',
                'Client-Secret' => $config['client_secret'] ?? '',
                'Client-Context' => $config['client_context'] ?? '',
                'Accept' => 'application/json',
            ])
            ->timeout(20)
            ->get(self::BASE_URL . '/api/v2/stores');

            if (!$response->successful()) {
                Log::error('CarryBee store API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();
            $stores = $data['data']['stores'] ?? [];

            if (empty($stores)) {
                Log::warning('CarryBee: no stores found');
                return null;
            }

            // smart selection
            $selectedStore = collect($stores)
                ->firstWhere('is_default_pickup_store', true)
                ?? collect($stores)->firstWhere('is_active', true)
                ?? $stores[0];

            $storeInfo = [
                'store_id' => $selectedStore['id'] ?? null,
                'city_id' => $selectedStore['city_id'] ?? null,
                'zone_id' => $selectedStore['zone_id'] ?? null,
                'area_id' => $selectedStore['area_id'] ?? null,
            ];

            // cache FULL ARRAY (IMPORTANT)
            Cache::put($cacheKey, $storeInfo, now()->addDay());

            Log::info('CarryBee Store Selected', $storeInfo);

            return $storeInfo;

        } catch (\Throwable $e) {
            Log::error('CarryBee store exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }


    // curl --location '{{base_url}}/api/v2/orders/{{consignment_id}}/details' \
    // --header 'Client-ID: {{client_id}}' \
    // --header 'Client-Secret: {{client_secret}}' \
    // --header 'Client-Context: {{client_context}}'
    // {
    //     "error": false,
    //     "message": "Order details",
    //     "data": {
    //         "transfer_status": "In transit",
    //         "store_id": "a1b2c3",
    //         "consignment_id": "FX1212124433",
    //         "merchant_order_id": "order-1234",
    //         "recipient_name": "recipient name",
    //         "recipient_phone": "8801652241276",
    //         "recipient_secondary_phone": "8801652241276",
    //         "recipient_address": "recipient address",
    //         "collectable_amount": "1000",
    //         "collected_amount": "0",
    //         "cod_fee": 0,
    //         "delivery_fee": "105",
    //         "attempt": 0,
    //         "updated_at": "2025-07-30T10:11:12+00:00"
    //     }
    // }
    public function handleCarryBeeCallback(Request $request)
    {
        $payload = $request->all();

        // basic validation
        if (empty($payload['merchant_order_id']) && empty($payload['consignment_id'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $event = $payload['event'] ?? null;
        $eventName = $event ? last(explode('.', $event)) : 'unknown';

        $merchantOrderId = $payload['merchant_order_id'] ?? null;
        $consignmentId   = $payload['consignment_id'] ?? null;
        $updatedAt       = $payload['updated_at'] ?? now();
        $deliveryFee     = (float) ($payload['delivery_fee'] ?? 0);

        // find order
        $order = null;
        if ($merchantOrderId) {
            $order = is_numeric($merchantOrderId)
                ? ProductOrder::find($merchantOrderId)
                : ProductOrder::where('order_code', $merchantOrderId)->first();
        }

        if (!$order) {
            Log::warning('CarryBee webhook: Order not found', $payload);
            return response()->json(['success' => true], 200); // don't fail webhook
        }

        // update courier info
        $existingCourierInfo = is_array($order->courier_info) ? $order->courier_info : [];

        $order->courier_info = array_merge($existingCourierInfo, [
            'courier'        => 'carrybee',
            'status'         => $eventName,
            'consignment_id' => $consignmentId,
            'updated_at'     => $updatedAt,
            'tracking_url'   => $consignmentId
                ? "https://merchant.carrybee.com/tracking?consignment_id={$consignmentId}&phone=" . $this->formatPhoneBD(optional($order->customer)->phone)
                : ($existingCourierInfo['tracking_url'] ?? ''),
        ]);

        // track history
        $tracks = is_array($order->order_tracks) ? $order->order_tracks : [];
        $tracks[] = [
            'status'         => $eventName,
            'consignment_id' => $consignmentId,
            'updated_at'     => $updatedAt,
        ];
        $order->order_tracks = $tracks;

        // delivery fee update (safe)
        if ($eventName === 'created' && $deliveryFee > 0) {
            $otherCharges = $order->other_charges ?? [
                "extra_charge" => 0,
                "delivery_charge" => 0,
            ];

            $otherCharges['delivery_charge'] = $deliveryFee;

            $order->other_charges = $otherCharges;
            $order->delivery_fee = $deliveryFee;

            $total = ($order->subtotal + $deliveryFee + $order->other_charge_amount)
                - $order->calculated_discount_amount
                - $order->round_off_from_total;

            $order->total = round($total);
        }

        $order->is_couriered = 1;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'CarryBee webhook received'
        ], 200);
    }

    //  fix phone number to Bangladesh format (remove leading zeros, add country code)
    private function formatPhoneBD(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone); // remove non-digit characters

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        if (!str_starts_with($phone, '880')) {
            $phone = '880' . $phone;
        }

        return $phone;
    }

}
