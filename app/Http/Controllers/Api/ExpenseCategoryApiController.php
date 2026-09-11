<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Account\Models\DbExpenseCategory;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseCategoryApiController extends Controller
{
    public function index(Request $request)
    {
        $query = DbExpenseCategory::with(['debitAccount:id,account_name', 'creditAccount:id,account_name'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('category_name', 'like', "%{$search}%")
                        ->orWhere('category_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('category_name');

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) $request->input('per_page', 15)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);

        $category = DbExpenseCategory::create(array_merge($data, [
            'store_id' => $request->input('store_id', auth()->user()->store_id ?? 1),
            'creator' => auth()->id(),
            'slug' => Str::slug($data['category_name']) . time(),
            'status' => $request->input('status', 'active'),
            'created_at' => Carbon::now('Asia/Dhaka'),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function show($id)
    {
        $category = DbExpenseCategory::with(['debitAccount:id,account_name', 'creditAccount:id,account_name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = DbExpenseCategory::findOrFail($id);
        $data = $this->validateCategory($request);

        $category->fill(array_merge($data, [
            'store_id' => $request->input('store_id', $category->store_id),
            'status' => $request->input('status', $category->status),
            'creator' => auth()->id() ?: $category->creator,
        ]));

        if ($category->isDirty('category_name')) {
            $category->slug = Str::slug($data['category_name']) . time();
        }

        $category->updated_at = Carbon::now('Asia/Dhaka');
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => $category,
        ]);
    }

    public function destroy($id)
    {
        $category = DbExpenseCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }

    private function validateCategory(Request $request)
    {
        return $request->validate([
            'category_name' => ['required', 'string', 'max:100'],
            'category_code' => ['required', 'string', 'max:100'],
            'debit_id' => ['required', 'exists:ac_accounts,id'],
            'credit_id' => ['required', 'exists:ac_accounts,id'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'store_id' => ['nullable', 'integer'],
        ]);
    }
}
