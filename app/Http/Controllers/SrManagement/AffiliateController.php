<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        $query = Affiliate::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $affiliates = $query->latest()->paginate(20)->appends($request->query());

        return view('backend.sr_management.affiliates.index', compact('affiliates'));
    }

    public function create()
    {
        return view('backend.sr_management.affiliates.create', ['affiliate' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::id();
        $data['code'] = $this->normalizeCode($data['code']);
        Affiliate::create($data);

        return redirect()->route('sr.affiliates.index')->with('success', 'Affiliate partner created successfully.');
    }

    public function edit($id)
    {
        $affiliate = Affiliate::findOrFail($id);
        return view('backend.sr_management.affiliates.edit', compact('affiliate'));
    }

    public function update(Request $request, $id)
    {
        $affiliate = Affiliate::findOrFail($id);
        $data = $this->validated($request, $affiliate->id);
        $data['code'] = $this->normalizeCode($data['code']);
        $affiliate->update($data);

        return redirect()->route('sr.affiliates.index')->with('success', 'Affiliate partner updated successfully.');
    }

    public function destroy($id)
    {
        $affiliate = Affiliate::findOrFail($id);
        $affiliate->delete();

        return redirect()->route('sr.affiliates.index')->with('success', 'Affiliate partner deleted successfully.');
    }

    public function validateCode(Request $request)
    {
        $code = $this->normalizeCode((string) $request->input('code'));
        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();

        return response()->json([
            'valid' => (bool) $affiliate,
            'code' => $code,
            'affiliate' => $affiliate ? [
                'id' => $affiliate->id,
                'name' => $affiliate->name,
                'commission_base' => $affiliate->default_commission_base,
                'commission_type' => $affiliate->default_commission_type,
                'commission_value' => $affiliate->default_commission_value,
            ] : null,
            'message' => $affiliate ? 'Affiliate code is active.' : 'No active affiliate found for this code.',
        ]);
    }

    protected function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('affiliates', 'code')->ignore($ignoreId)],
            'default_commission_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'default_commission_base' => ['required', Rule::in(['sale_amount', 'gross_profit'])],
            'default_commission_value' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'note' => ['nullable', 'string'],
        ]);
    }

    protected function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}

