<select name="{{ $name ?? 'employee_id' }}" class="form-control select2" {{ !empty($required) ? 'required' : '' }}>
    <option value="">-- Select Employee --</option>
    @foreach ($employees as $employee)
        <option value="{{ $employee->id }}" {{ (string) old($name ?? 'employee_id', $selected ?? '') === (string) $employee->id ? 'selected' : '' }}>
            {{ $employee->name }} {{ $employee->email ? '- ' . $employee->email : '' }}
        </option>
    @endforeach
</select>
