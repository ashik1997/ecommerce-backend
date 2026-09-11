<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\SalesCommissionRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CommissionRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesCommissionRule::with(['user', 'affiliate']);

        if ($request->filled('commission_for')) {
            $query->where('commission_for', $request->commission_for);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rules = $query->orderBy('priority')->latest()->paginate(20)->appends($request->query());
        $activeConflictCount = $this->activeConflictCount();

        return view('backend.sr_management.commission_rules.index', compact('rules', 'activeConflictCount'));
    }

    public function create()
    {
        return view('backend.sr_management.commission_rules.create', $this->formData(null));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        unset($data['confirm_conflict'], $data['ignore_id']);
        $data['created_by'] = Auth::id();

        $conflicts = $this->findConflictingRules($data);
        if ($conflicts->count() > 0 && ! $request->boolean('confirm_conflict')) {
            return back()
                ->withInput()
                ->with('warning', 'This rule overlaps with ' . $conflicts->count() . ' active rule(s). Lower priority number wins. Tick conflict confirmation to save anyway.');
        }

        SalesCommissionRule::create($data);

        return redirect()->route('sr.commission-rules.index')->with('success', 'Commission rule created successfully.');
    }

    public function edit($id)
    {
        $rule = SalesCommissionRule::findOrFail($id);
        return view('backend.sr_management.commission_rules.edit', $this->formData($rule));
    }

    public function update(Request $request, $id)
    {
        $rule = SalesCommissionRule::findOrFail($id);
        $data = $this->validated($request);
        unset($data['confirm_conflict'], $data['ignore_id']);

        $conflicts = $this->findConflictingRules($data, $rule->id);
        if ($conflicts->count() > 0 && ! $request->boolean('confirm_conflict')) {
            return back()
                ->withInput()
                ->with('warning', 'This rule overlaps with ' . $conflicts->count() . ' active rule(s). Lower priority number wins. Tick conflict confirmation to save anyway.');
        }

        $rule->update($data);

        return redirect()->route('sr.commission-rules.index')->with('success', 'Commission rule updated successfully.');
    }

    public function destroy($id)
    {
        SalesCommissionRule::findOrFail($id)->delete();
        return redirect()->route('sr.commission-rules.index')->with('success', 'Commission rule deleted successfully.');
    }

    public function conflictCheck(Request $request)
    {
        $data = $this->validated($request);
        $ignoreId = $request->input('ignore_id');

        $conflicts = $this->findConflictingRules($data, $ignoreId)
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'rule_name' => $rule->rule_name,
                    'priority' => $rule->priority,
                    'commission_for' => $rule->commission_for,
                    'commission_base' => $rule->commission_base,
                    'commission_type' => $rule->commission_type,
                    'commission_value' => $rule->commission_value,
                ];
            })
            ->values();

        return response()->json([
            'has_conflict' => $conflicts->count() > 0,
            'count' => $conflicts->count(),
            'conflicts' => $conflicts,
            'message' => $conflicts->count() > 0
                ? 'Overlapping active rule found. Lower priority number wins.'
                : 'No overlapping active rule found.',
        ]);
    }

    protected function formData($rule): array
    {
        return [
            'rule' => $rule,
            'users' => User::orderBy('name')->limit(500)->get(['id', 'name', 'email']),
            'affiliates' => Affiliate::where('status', 'active')->orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'rule_name' => ['required', 'string', 'max:255'],
            'commission_for' => ['required', Rule::in(['salesman', 'affiliate'])],
            'user_id' => ['nullable', 'integer'],
            'affiliate_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'website_id' => ['nullable', 'integer'],
            'commission_base' => ['required', Rule::in(['sale_amount', 'gross_profit'])],
            'commission_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'confirm_conflict' => ['nullable'],
            'ignore_id' => ['nullable', 'integer'],
        ]);
    }

    protected function findConflictingRules(array $data, $ignoreId = null)
    {
        $start = $data['start_date'] ?? null;
        $end = $data['end_date'] ?? null;

        return SalesCommissionRule::query()
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->where('status', 'active')
            ->where('commission_for', $data['commission_for'])
            ->where(function ($query) use ($data) {
                $this->nullableMatch($query, 'user_id', $data['user_id'] ?? null);
                $this->nullableMatch($query, 'affiliate_id', $data['affiliate_id'] ?? null);
                $this->nullableMatch($query, 'product_id', $data['product_id'] ?? null);
                $this->nullableMatch($query, 'category_id', $data['category_id'] ?? null);
                $this->nullableMatch($query, 'website_id', $data['website_id'] ?? null);
            })
            ->where(function ($query) use ($start, $end) {
                if ($start && $end) {
                    $query->where(function ($q) use ($start, $end) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', $end);
                    })->where(function ($q) use ($start, $end) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $start);
                    });
                } elseif ($start) {
                    $query->where(function ($q) use ($start) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $start);
                    });
                } elseif ($end) {
                    $query->where(function ($q) use ($end) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', $end);
                    });
                }
            })
            ->orderBy('priority')
            ->limit(10)
            ->get();
    }

    protected function nullableMatch($query, string $column, $value): void
    {
        $query->where(function ($q) use ($column, $value) {
            if ($value === null || $value === '') {
                $q->whereNull($column);
            } else {
                $q->whereNull($column)->orWhere($column, $value);
            }
        });
    }

    protected function activeConflictCount(): int
    {
        $rules = SalesCommissionRule::where('status', 'active')->get();
        $count = 0;

        foreach ($rules as $rule) {
            $data = $rule->only([
                'commission_for', 'user_id', 'affiliate_id', 'product_id', 'category_id', 'website_id', 'start_date', 'end_date'
            ]);
            $data['start_date'] = optional($rule->start_date)->format('Y-m-d');
            $data['end_date'] = optional($rule->end_date)->format('Y-m-d');

            if ($this->findConflictingRules($data, $rule->id)->count() > 0) {
                $count++;
            }
        }

        return $count;
    }
}
