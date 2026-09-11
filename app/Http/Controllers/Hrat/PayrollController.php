<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Hrat\EmployeeSalaryAssignment;
use App\Models\Hrat\DailyAttendanceSummary;
use App\Models\Hrat\EmployeeSalaryComponent;
use App\Models\Hrat\LeaveApplication;
use App\Models\Hrat\Payroll;
use App\Models\Hrat\PayrollLine;
use App\Models\User;
use App\Services\Hrat\AttendanceConfigService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::with(['lines.employee', 'accountingTransactions.debitAccount', 'accountingTransactions.creditAccount'])->latest()->paginate(12);
        $expenseAccounts = AcAccount::where('status', 'active')->where('account_type', 'expense')->orderBy('account_name')->get();
        $payableAccounts = AcAccount::where('status', 'active')->where('account_type', 'liability')->orderBy('account_name')->get();
        $paymentTypes = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();

        return view('backend.hrat.payrolls.index', compact('payrolls', 'expenseAccounts', 'payableAccounts', 'paymentTypes'));
    }

    public function report(Request $request)
    {
        $payrolls = $this->payrollReportQuery($request)
            ->paginate(12)
            ->appends($request->query());
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();

        return view('backend.hrat.payrolls.report', compact('payrolls', 'employees'));
    }

    public function exportCsv(Request $request)
    {
        $payrolls = $this->payrollReportQuery($request)->get();

        return response()->streamDownload(function () use ($payrolls) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Payroll',
                'Cycle',
                'Status',
                'Employee',
                'Basic',
                'Allowance',
                'Component Deduction',
                'Late Deduction',
                'Unpaid Leave Deduction',
                'Absent Deduction',
                'Overtime Amount',
                'Net Payable',
            ]);

            foreach ($payrolls as $payroll) {
                foreach ($payroll->lines as $line) {
                    $meta = $line->meta ?: [];
                    fputcsv($out, [
                        $payroll->year . '-' . str_pad($payroll->month, 2, '0', STR_PAD_LEFT),
                        optional($payroll->from_date)->format('Y-m-d') . ' to ' . optional($payroll->to_date)->format('Y-m-d'),
                        $payroll->status,
                        $line->employee->name ?? 'N/A',
                        $line->basic_salary,
                        $line->allowance_total,
                        $meta['component_deductions'] ?? 0,
                        $meta['late_deduction'] ?? 0,
                        $meta['unpaid_leave_deduction'] ?? 0,
                        $line->absent_deduction,
                        $line->overtime_amount,
                        $line->net_payable,
                    ]);
                }
            }

            fclose($out);
        }, 'hrat_payroll_report.csv', ['Content-Type' => 'text/csv']);
    }

    public function payslip(Payroll $payroll, PayrollLine $line)
    {
        if ((int) $line->payroll_id !== (int) $payroll->id) {
            abort(404);
        }

        $payroll->loadMissing('lines');
        $line->loadMissing('employee.employeeProfile');

        return view('backend.hrat.payrolls.payslip', compact('payroll', 'line'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        [$from, $to] = app(AttendanceConfigService::class)->attendanceMonthRange((int) $data['year'], (int) $data['month']);
        $existing = Payroll::where('year', $data['year'])->where('month', $data['month'])->first();
        if ($existing && $existing->status !== 'draft') {
            Toastr::error('Only draft payroll can be regenerated.', 'Error');
            return back();
        }

        DB::transaction(function () use ($data, $from, $to) {
            $payroll = Payroll::updateOrCreate(
                ['year' => $data['year'], 'month' => $data['month']],
                ['from_date' => $from, 'to_date' => $to, 'status' => 'draft', 'created_by' => auth()->id()]
            );

            $assignments = EmployeeSalaryAssignment::with('components.salaryComponent')
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $to)
                ->where(fn($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from))
                ->latest('effective_from')
                ->get()
                ->unique('employee_id');

            $activeEmployeeIds = [];
            foreach ($assignments as $assignment) {
                $activeEmployeeIds[] = $assignment->employee_id;
                $allowances = $this->componentTotal($assignment, 'allowance');
                $componentDeductions = $this->componentTotal($assignment, 'deduction');
                $summaries = DailyAttendanceSummary::where('employee_id', $assignment->employee_id)->whereBetween('attendance_date', [$from, $to]);
                $presentDays = (clone $summaries)->where('is_present', true)->count();
                $absentDays = (clone $summaries)->where('is_absent', true)->count();
                $lateDays = (clone $summaries)->where('is_late', true)->count();
                $lateMinutes = (clone $summaries)->sum('late_minutes');
                $overtimeMinutes = (clone $summaries)->sum('overtime_minutes');
                $cycleDays = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
                $dailyRate = $cycleDays > 0 ? $assignment->basic_salary / $cycleDays : 0;
                $hourlyRate = $dailyRate / 8;
                $absentDeduction = $dailyRate * $absentDays;
                $lateDeduction = $hourlyRate * ($lateMinutes / 60);
                $unpaidLeaveDays = $this->unpaidLeaveDays($assignment->employee_id, $from, $to);
                $unpaidLeaveDeduction = $dailyRate * $unpaidLeaveDays;
                $overtimeAmount = $hourlyRate * ($overtimeMinutes / 60);
                $deductions = $componentDeductions + $lateDeduction + $unpaidLeaveDeduction;

                $payroll->lines()->updateOrCreate(
                    ['employee_id' => $assignment->employee_id],
                    [
                        'basic_salary' => $assignment->basic_salary,
                        'allowance_total' => $allowances,
                        'deduction_total' => $deductions,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'late_days' => $lateDays,
                        'overtime_minutes' => $overtimeMinutes,
                        'absent_deduction' => $absentDeduction,
                        'overtime_amount' => $overtimeAmount,
                        'net_payable' => $assignment->basic_salary + $allowances + $overtimeAmount - $deductions - $absentDeduction,
                        'meta' => [
                            'salary_assignment_id' => $assignment->id,
                            'daily_rate' => $dailyRate,
                            'hourly_rate' => $hourlyRate,
                            'attendance_cycle_days' => $cycleDays,
                            'component_deductions' => $componentDeductions,
                            'late_minutes' => $lateMinutes,
                            'late_deduction' => $lateDeduction,
                            'unpaid_leave_days' => $unpaidLeaveDays,
                            'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                            'components' => $this->componentBreakdown($assignment),
                        ],
                    ]
                );
            }

            $payroll->lines()->whereNotIn('employee_id', $activeEmployeeIds ?: [0])->delete();
        });

        Toastr::success('Payroll draft generated.', 'Success');
        return back();
    }

    public function approve(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            Toastr::warning('Only draft payroll can be approved.', 'Warning');
            return back();
        }

        $payroll->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        Toastr::success('Payroll approved.', 'Success');
        return back();
    }

    public function finalize(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'approved') {
            Toastr::warning('Only approved payroll can be finalized.', 'Warning');
            return back();
        }

        $data = $request->validate([
            'expense_account_id' => ['required', 'exists:ac_accounts,id'],
            'payable_account_id' => ['required', 'exists:ac_accounts,id'],
        ]);

        try {
            DB::transaction(function () use ($payroll, $data) {
                $payroll->loadMissing('lines');
                $expenseAccount = $this->accountOfType((int) $data['expense_account_id'], 'expense', 'Salary expense account must be an expense account.');
                $payableAccount = $this->accountOfType((int) $data['payable_account_id'], 'liability', 'Salary payable account must be a liability account.');
                $amount = $payroll->totalNetPayable();
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll total must be greater than zero.']);
                }
                if ($payroll->accounting_posted_at || $this->hasOpenPayrollEvent($payroll, 'payroll_accrual', 'payroll_accrual_reversal')) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll accounting is already posted.']);
                }

                $reference = $this->payrollReference($payroll, 'ACCRUAL');
                $note = 'Payroll accrual for ' . $this->payrollPeriodLabel($payroll);
                $this->createPayrollTransactions($payroll, $reference, 'payroll_accrual', $expenseAccount->id, $payableAccount->id, $amount, $note);

                $payroll->update([
                    'status' => 'finalized',
                    'expense_account_id' => $expenseAccount->id,
                    'payable_account_id' => $payableAccount->id,
                    'accounting_posted_at' => now(),
                    'accounting_reference' => $reference,
                    'finalized_by' => auth()->id(),
                    'finalized_at' => now(),
                ]);
            });
        } catch (ValidationException $exception) {
            Toastr::error(collect($exception->errors())->flatten()->first(), 'Error');
            return back()->withInput();
        }

        Toastr::success('Payroll finalized and accounting posted.', 'Success');
        return back();
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'finalized') {
            Toastr::warning('Only finalized payroll can be marked paid.', 'Warning');
            return back();
        }

        $data = $request->validate([
            'payment_type_id' => ['required', 'exists:db_paymenttypes,id'],
        ]);

        try {
            DB::transaction(function () use ($payroll, $data) {
                $payroll->loadMissing('lines');
                if (!$payroll->accounting_posted_at || !$payroll->payable_account_id) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll must be finalized with accounting before payment.']);
                }
                if ($payroll->payment_posted_at || $this->hasOpenPayrollEvent($payroll, 'payroll_payment', 'payroll_payment_reversal')) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll payment is already posted.']);
                }

                $paymentType = DbPaymentType::findOrFail($data['payment_type_id']);
                $paymentAccount = $this->paymentAccountForType($paymentType);
                $amount = $payroll->totalNetPayable();
                if ($this->accountBalance($paymentAccount->id) < $amount) {
                    throw ValidationException::withMessages(['payment_type_id' => 'Insufficient balance in selected payment type.']);
                }

                $reference = $this->payrollReference($payroll, 'PAYMENT');
                $note = 'Payroll payment via ' . $paymentType->payment_type . ' for ' . $this->payrollPeriodLabel($payroll);
                $this->createPayrollTransactions($payroll, $reference, 'payroll_payment', $payroll->payable_account_id, $paymentAccount->id, $amount, $note);

                $payroll->update([
                    'status' => 'paid',
                    'payment_type_id' => $paymentType->id,
                    'payment_account_id' => $paymentAccount->id,
                    'payment_posted_at' => now(),
                    'payment_reference' => $reference,
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
            });
        } catch (ValidationException $exception) {
            Toastr::error(collect($exception->errors())->flatten()->first(), 'Error');
            return back()->withInput();
        }

        Toastr::success('Payroll marked as paid and accounting posted.', 'Success');
        return back();
    }

    public function reversePayment(Payroll $payroll)
    {
        if ($payroll->status !== 'paid') {
            Toastr::warning('Only paid payroll can have payment reversed.', 'Warning');
            return back();
        }

        try {
            DB::transaction(function () use ($payroll) {
                $payroll->loadMissing('lines');
                if (!$payroll->payment_posted_at || !$payroll->payable_account_id || !$payroll->payment_account_id) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll payment posting information is missing.']);
                }
                if (!$this->hasOpenPayrollEvent($payroll, 'payroll_payment', 'payroll_payment_reversal')) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll payment is already reversed.']);
                }

                $amount = $payroll->totalNetPayable();
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll total must be greater than zero.']);
                }

                $reference = $this->payrollReference($payroll, 'PAYMENT-REV-' . now('Asia/Dhaka')->format('YmdHis'));
                $note = 'Payroll payment reversal for ' . $this->payrollPeriodLabel($payroll);
                $this->createPayrollTransactions($payroll, $reference, 'payroll_payment_reversal', $payroll->payment_account_id, $payroll->payable_account_id, $amount, $note);

                $payroll->update([
                    'status' => 'finalized',
                    'payment_type_id' => null,
                    'payment_account_id' => null,
                    'payment_posted_at' => null,
                    'payment_reference' => null,
                    'paid_by' => null,
                    'paid_at' => null,
                ]);
            });
        } catch (ValidationException $exception) {
            Toastr::error(collect($exception->errors())->flatten()->first(), 'Error');
            return back();
        }

        Toastr::success('Payroll payment reversed. Payroll is finalized again.', 'Success');
        return back();
    }

    public function voidFinalization(Payroll $payroll)
    {
        if ($payroll->status !== 'finalized') {
            Toastr::warning('Only finalized payroll can be voided.', 'Warning');
            return back();
        }

        try {
            DB::transaction(function () use ($payroll) {
                $payroll->loadMissing('lines');
                if (!$payroll->accounting_posted_at || !$payroll->expense_account_id || !$payroll->payable_account_id) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll accrual posting information is missing.']);
                }
                if ($this->hasOpenPayrollEvent($payroll, 'payroll_payment', 'payroll_payment_reversal')) {
                    throw ValidationException::withMessages(['payroll' => 'Reverse payroll payment before voiding finalization.']);
                }
                if (!$this->hasOpenPayrollEvent($payroll, 'payroll_accrual', 'payroll_accrual_reversal')) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll accrual is already reversed.']);
                }

                $amount = $payroll->totalNetPayable();
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['payroll' => 'Payroll total must be greater than zero.']);
                }

                $reference = $this->payrollReference($payroll, 'ACCRUAL-REV-' . now('Asia/Dhaka')->format('YmdHis'));
                $note = 'Payroll accrual reversal for ' . $this->payrollPeriodLabel($payroll);
                $this->createPayrollTransactions($payroll, $reference, 'payroll_accrual_reversal', $payroll->payable_account_id, $payroll->expense_account_id, $amount, $note);

                $payroll->update([
                    'status' => 'approved',
                    'expense_account_id' => null,
                    'payable_account_id' => null,
                    'accounting_posted_at' => null,
                    'accounting_reference' => null,
                    'finalized_by' => null,
                    'finalized_at' => null,
                ]);
            });
        } catch (ValidationException $exception) {
            Toastr::error(collect($exception->errors())->flatten()->first(), 'Error');
            return back();
        }

        Toastr::success('Payroll finalization voided. Payroll is approved again.', 'Success');
        return back();
    }

    private function componentTotal(EmployeeSalaryAssignment $assignment, string $type): float
    {
        return $assignment->components
            ->filter(fn ($row) => $row->is_active && optional($row->salaryComponent)->type === $type)
            ->sum(fn ($row) => $this->componentAmount($assignment, $row));
    }

    private function componentAmount(EmployeeSalaryAssignment $assignment, EmployeeSalaryComponent $component): float
    {
        if (optional($component->salaryComponent)->calculation_type === 'percentage') {
            return ((float) $assignment->basic_salary * (float) $component->amount) / 100;
        }

        return (float) $component->amount;
    }

    private function componentBreakdown(EmployeeSalaryAssignment $assignment): array
    {
        return $assignment->components
            ->filter(fn ($row) => $row->is_active && $row->salaryComponent)
            ->map(fn ($row) => [
                'name' => $row->salaryComponent->name,
                'type' => $row->salaryComponent->type,
                'calculation_type' => $row->salaryComponent->calculation_type,
                'input_amount' => (float) $row->amount,
                'calculated_amount' => $this->componentAmount($assignment, $row),
            ])
            ->values()
            ->all();
    }

    private function unpaidLeaveDays(int $employeeId, string $from, string $to): int
    {
        $applications = LeaveApplication::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $to)
            ->whereDate('to_date', '>=', $from)
            ->get()
            ->filter(fn ($application) => optional($application->leaveType)->is_paid === false);

        return $applications->sum(function ($application) use ($employeeId, $from, $to) {
            $rangeFrom = Carbon::parse(max($from, $application->from_date->toDateString()))->toDateString();
            $rangeTo = Carbon::parse(min($to, $application->to_date->toDateString()))->toDateString();

            return DailyAttendanceSummary::where('employee_id', $employeeId)
                ->where('status', 'leave')
                ->whereBetween('attendance_date', [$rangeFrom, $rangeTo])
                ->count();
        });
    }

    private function payrollReportQuery(Request $request)
    {
        $employeeId = $request->employee_id;

        return Payroll::with([
            'accountingTransactions.debitAccount',
            'accountingTransactions.creditAccount',
            'lines' => fn ($query) => $query
                ->when($employeeId, fn ($lineQuery) => $lineQuery->where('employee_id', $employeeId))
                ->with('employee.employeeProfile'),
        ])
            ->when($request->year, fn ($query) => $query->where('year', (int) $request->year))
            ->when($request->month, fn ($query) => $query->where('month', (int) $request->month))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($employeeId, function ($query) use ($employeeId) {
                $query->whereHas('lines', fn ($lineQuery) => $lineQuery->where('employee_id', $employeeId));
            })
            ->latest();
    }

    private function accountOfType(int $accountId, string $type, string $message): AcAccount
    {
        $account = AcAccount::where('id', $accountId)->where('status', 'active')->first();
        if (!$account || $account->account_type !== $type) {
            throw ValidationException::withMessages(['account_id' => $message]);
        }

        return $account;
    }

    private function paymentAccountForType(DbPaymentType $paymentType): AcAccount
    {
        $accountId = $paymentType->credit_account_id ?: $paymentType->debit_account_id;
        $account = $accountId
            ? AcAccount::where('id', $accountId)->where('status', 'active')->first()
            : AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first();

        if (!$account) {
            throw ValidationException::withMessages(['payment_type_id' => 'Selected payment type has no account mapping.']);
        }
        if ($account->account_type !== 'asset') {
            throw ValidationException::withMessages(['payment_type_id' => 'Selected payment type is not mapped to an asset account.']);
        }

        return $account;
    }

    private function accountBalance(int $accountId): float
    {
        $account = AcAccount::findOrFail($accountId);
        $debits = (float) AcTransaction::where('debit_account_id', $account->id)->where('status', 'active')->sum('debit_amt');
        $credits = (float) AcTransaction::where('credit_account_id', $account->id)->where('status', 'active')->sum('credit_amt');

        if (in_array($account->account_type, ['liability', 'equity', 'revenue'], true)) {
            return $credits - $debits;
        }

        return $debits - $credits;
    }

    private function createPayrollTransactions(Payroll $payroll, string $reference, string $eventType, int $debitAccountId, int $creditAccountId, float $amount, string $note): void
    {
        $date = now('Asia/Dhaka')->toDateString();
        $base = [
            'store_id' => auth()->user()->store_id ?? 1,
            'payment_code' => $reference,
            'transaction_date' => $date,
            'transaction_type' => 'PAYROLL',
            'event_type' => $eventType,
            'ref_payroll_id' => $payroll->id,
            'note' => $note,
            'creator' => auth()->id(),
            'status' => 'active',
            'created_at' => now('Asia/Dhaka'),
        ];

        AcTransaction::create($base + [
            'debit_account_id' => $debitAccountId,
            'debit_amt' => $amount,
            'credit_account_id' => null,
            'credit_amt' => null,
            'slug' => Str::slug($reference . '-dr') . '-' . time() . rand(1000, 9999),
        ]);

        AcTransaction::create($base + [
            'debit_account_id' => null,
            'debit_amt' => null,
            'credit_account_id' => $creditAccountId,
            'credit_amt' => $amount,
            'slug' => Str::slug($reference . '-cr') . '-' . time() . rand(1000, 9999),
        ]);
    }

    private function hasOpenPayrollEvent(Payroll $payroll, string $eventType, string $reversalEventType): bool
    {
        return $this->payrollEventDebitTotal($payroll, $eventType) > $this->payrollEventDebitTotal($payroll, $reversalEventType);
    }

    private function payrollEventDebitTotal(Payroll $payroll, string $eventType): float
    {
        return (float) AcTransaction::where('ref_payroll_id', $payroll->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->sum('debit_amt');
    }

    private function payrollReference(Payroll $payroll, string $suffix): string
    {
        return 'PAYROLL-' . $payroll->year . '-' . str_pad((string) $payroll->month, 2, '0', STR_PAD_LEFT) . '-' . $suffix;
    }

    private function payrollPeriodLabel(Payroll $payroll): string
    {
        return $payroll->year . '-' . str_pad((string) $payroll->month, 2, '0', STR_PAD_LEFT);
    }
}
