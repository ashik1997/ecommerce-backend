<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCrmCustomerTagRequest;
use App\Http\Requests\Crm\SyncCustomerTagsRequest;
use App\Http\Requests\Crm\UpdateCrmCustomerTagRequest;
use App\Http\Requests\Crm\UpdateCrmCustomerTagStatusRequest;
use App\Services\Crm\CrmCustomerTagService;
use App\Services\Crm\CustomerTagAssignmentService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCustomerTagController extends Controller
{
    protected CrmCustomerTagService $tagService;
    protected CustomerTagAssignmentService $assignmentService;

    public function __construct(
        CrmCustomerTagService $tagService,
        CustomerTagAssignmentService $assignmentService
    ) {
        $this->tagService = $tagService;
        $this->assignmentService = $assignmentService;
    }

    public function index()
    {
        return view('backend.crm.settings.customer-tags.index');
    }

    public function data(Request $request)
    {
        return DataTables::of($this->tagService->listQuery())
            ->addIndexColumn()
            ->addColumn('color_preview', function ($tag) {
                $storedColor = (string) ($tag->color ?? '');
                $previewColor = preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $storedColor)
                    ? $storedColor
                    : '#adb5bd';
                $label = $storedColor !== '' ? e($storedColor) : 'Not set';

                return '<span class="d-inline-block rounded-circle mr-2" style="width:18px;height:18px;vertical-align:middle;background:' . e($previewColor) . ';border:1px solid #ced4da;"></span>'
                    . '<span>' . $label . '</span>';
            })
            ->addColumn('status_badge', fn($tag) => $tag->status === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>')
            ->editColumn('created_at', fn($tag) => $tag->created_at ? $tag->created_at->format('Y-m-d h:i:s a') : '')
            ->addColumn('actions', function ($tag) {
                $payload = e(json_encode([
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                    'status' => $tag->status,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $nextStatus = $tag->status === 'active' ? 'inactive' : 'active';
                $statusLabel = $nextStatus === 'active' ? 'Activate' : 'Deactivate';
                $statusClass = $nextStatus === 'active' ? 'btn-outline-success' : 'btn-outline-warning';

                return '<button type="button" class="btn btn-sm btn-outline-primary js-edit-tag mr-1" data-tag="' . $payload . '">'
                    . '<i class="feather-edit-2"></i> Edit</button>'
                    . '<button type="button" class="btn btn-sm ' . $statusClass . ' js-toggle-tag" data-id="' . (int) $tag->id . '" data-name="' . e($tag->name) . '" data-status="' . $nextStatus . '">'
                    . $statusLabel . '</button>';
            })
            ->rawColumns(['color_preview', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(StoreCrmCustomerTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->create($request->validated(), auth()->id());

        return response()->json([
            'message' => 'CRM customer tag created successfully.',
            'tag' => $tag,
        ], 201);
    }

    public function update(UpdateCrmCustomerTagRequest $request, int $tag): JsonResponse
    {
        $updatedTag = $this->tagService->update($tag, $request->validated(), auth()->id());

        return response()->json([
            'message' => 'CRM customer tag updated successfully.',
            'tag' => $updatedTag,
        ]);
    }

    public function changeStatus(UpdateCrmCustomerTagStatusRequest $request, int $tag): JsonResponse
    {
        $updatedTag = $this->tagService->changeStatus($tag, $request->validated()['status'], auth()->id());

        return response()->json([
            'message' => $updatedTag->status === 'active'
                ? 'CRM customer tag activated successfully.'
                : 'CRM customer tag deactivated successfully.',
            'tag' => $updatedTag,
        ]);
    }

    public function activeOptions(Request $request): JsonResponse
    {
        $search = substr((string) $request->query('q', ''), 0, 120);
        $options = $this->assignmentService->activeOptions($search);

        return response()->json(['results' => $options]);
    }

    public function assignedTags(int $customer): JsonResponse
    {
        return response()->json([
            'customer_id' => $customer,
            'tags' => $this->assignmentService->assignedTags($customer),
        ]);
    }

    public function syncCustomerTags(SyncCustomerTagsRequest $request, int $customer): JsonResponse
    {
        $result = $this->assignmentService->sync($customer, $request->validated()['tag_ids'], auth()->id());

        return response()->json([
            'message' => 'Customer CRM tags synchronized successfully.',
            'data' => $result,
        ]);
    }
}
