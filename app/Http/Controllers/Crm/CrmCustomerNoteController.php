<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCrmCustomerNoteRequest;
use App\Http\Requests\Crm\UpdateCrmCustomerNoteRequest;
use App\Services\Crm\CrmCustomerNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCustomerNoteController extends Controller
{
    protected CrmCustomerNoteService $noteService;

    public function __construct(CrmCustomerNoteService $noteService)
    {
        $this->noteService = $noteService;
    }

    public function index(Request $request, int $customer): JsonResponse
    {
        return response()->json(
            $this->noteService->paginate($customer, $request->user(), (int) $request->query('per_page', 10))
        );
    }

    public function store(StoreCrmCustomerNoteRequest $request, int $customer): JsonResponse
    {
        $note = $this->noteService->create($customer, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM customer note created successfully.',
            'note' => $this->noteService->payload($note, $request->user()),
        ], 201);
    }

    public function update(UpdateCrmCustomerNoteRequest $request, int $customer, int $note): JsonResponse
    {
        $updatedNote = $this->noteService->update($customer, $note, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM customer note updated successfully.',
            'note' => $this->noteService->payload($updatedNote, $request->user()),
        ]);
    }

    public function archive(Request $request, int $customer, int $note): JsonResponse
    {
        $this->noteService->archive($customer, $note, $request->user());

        return response()->json(['message' => 'CRM customer note archived successfully.']);
    }
}
