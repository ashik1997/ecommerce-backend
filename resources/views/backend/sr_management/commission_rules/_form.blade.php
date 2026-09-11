@php($isEdit = isset($rule) && $rule)
<div class="row">
    <div class="col-md-6 form-group"><label>Rule Name *</label><input type="text" name="rule_name" class="form-control" value="{{ old('rule_name', $isEdit ? $rule->rule_name : '') }}" required>    <div class="col-md-12">
        <div class="alert alert-info mb-2">
            Conflict rule: If a specific rule and an all-salesman/all-affiliate rule match the same order, the lower priority number will be used.
        </div>
        <label class="d-flex align-items-center" style="gap:8px;">
            <input type="checkbox" name="confirm_conflict" value="1" {{ old('confirm_conflict') ? 'checked' : '' }}>
            <span>I understand possible rule overlap and want to save this rule.</span>
        </label>
        @if($errors->any())<div class="text-danger mt-2">Please check the highlighted fields.</div>@endif
    </div>
</div>
    <div class="col-md-3 form-group"><label>Commission For</label><select name="commission_for" id="commission_for" class="form-control"><option value="salesman" {{ old('commission_for', $isEdit ? $rule->commission_for : 'salesman')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ old('commission_for', $isEdit ? $rule->commission_for : '')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
    <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" {{ old('status', $isEdit ? $rule->status : 'active')=='active'?'selected':'' }}>Active</option><option value="inactive" {{ old('status', $isEdit ? $rule->status : '')=='inactive'?'selected':'' }}>Inactive</option></select></div>
    <div class="col-md-4 form-group"><label>Specific Salesman (optional)</label><select name="user_id" class="form-control"><option value="">All Salesman</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('user_id', $isEdit ? $rule->user_id : '')==$user->id?'selected':'' }}>{{ $user->name }}</option>@endforeach</select></div>
    <div class="col-md-4 form-group"><label>Specific Affiliate (optional)</label><select name="affiliate_id" class="form-control"><option value="">All Affiliate</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" {{ old('affiliate_id', $isEdit ? $rule->affiliate_id : '')==$affiliate->id?'selected':'' }}>{{ $affiliate->name }} ({{ $affiliate->code }})</option>@endforeach</select></div>
    <div class="col-md-4 form-group"><label>Website ID (optional)</label><input type="number" name="website_id" class="form-control" value="{{ old('website_id', $isEdit ? $rule->website_id : '') }}"></div>
    <div class="col-md-3 form-group"><label>Commission Base</label><select name="commission_base" class="form-control"><option value="sale_amount" {{ old('commission_base', $isEdit ? $rule->commission_base : 'sale_amount')=='sale_amount'?'selected':'' }}>Sale Amount</option><option value="gross_profit" {{ old('commission_base', $isEdit ? $rule->commission_base : '')=='gross_profit'?'selected':'' }}>Gross Profit</option></select></div>
    <div class="col-md-3 form-group"><label>Type</label><select name="commission_type" class="form-control"><option value="percentage" {{ old('commission_type', $isEdit ? $rule->commission_type : 'percentage')=='percentage'?'selected':'' }}>Percentage</option><option value="fixed" {{ old('commission_type', $isEdit ? $rule->commission_type : '')=='fixed'?'selected':'' }}>Fixed</option></select></div>
    <div class="col-md-3 form-group"><label>Value</label><input type="number" step="0.01" name="commission_value" class="form-control" value="{{ old('commission_value', $isEdit ? $rule->commission_value : 0) }}"></div>
    <div class="col-md-3 form-group"><label>Min Order Amount</label><input type="number" step="0.01" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $isEdit ? $rule->min_order_amount : 0) }}"></div>
    <div class="col-md-3 form-group"><label>Priority</label><input type="number" name="priority" class="form-control" value="{{ old('priority', $isEdit ? $rule->priority : 100) }}"><small class="text-muted">Lower number applies first.</small></div>
    <div class="col-md-3 form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', $isEdit && $rule->start_date ? $rule->start_date->format('Y-m-d') : '') }}"></div>
    <div class="col-md-3 form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $isEdit && $rule->end_date ? $rule->end_date->format('Y-m-d') : '') }}"></div>
</div>
