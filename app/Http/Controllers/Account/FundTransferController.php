<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcMoneyTransfer;
use App\Http\Controllers\Account\Models\AcMoneyTransferType;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class FundTransferController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = AcMoneyTransfer::with([
                    'fromPaymentType:id,payment_type',
                    'toPaymentType:id,payment_type',
                    'transferType:id,name',
                    'creator_info:id,name',
                ])
                ->where('status', 'active')
                ->orderBy('id', 'DESC');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('date', fn ($data) => $data->transfer_date ? date('Y-m-d', strtotime($data->transfer_date)) : '')
                ->addColumn('from_account', fn ($data) => '<span class="text-danger font-weight-bold">' . e(optional($data->fromPaymentType)->payment_type ?? 'N/A') . '</span>')
                ->addColumn('to_account', fn ($data) => '<span class="text-success font-weight-bold">' . e(optional($data->toPaymentType)->payment_type ?? 'N/A') . '</span>')
                ->addColumn('transfer_type', fn ($data) => optional($data->transferType)->name ?? '-')
                ->addColumn('amount', fn ($data) => '<span class="text-primary font-weight-bold">৳ ' . number_format($data->amount ?? 0, 2) . '</span>')
                ->addColumn('balances', function ($data) {
                    return 'From: ৳ ' . number_format($data->from_balance_after ?? 0, 2)
                        . '<br>To: ৳ ' . number_format($data->to_balance_after ?? 0, 2);
                })
                ->addColumn('creator_name', fn ($data) => optional($data->creator_info)->name ?? '')
                ->addColumn('action', function ($data) {
                    return '<a href="' . route('PrintFundTransfer', $data->id) . '" class="btn-sm btn-success rounded" title="Print Receipt" target="_blank"><i class="fas fa-print"></i></a>';
                })
                ->rawColumns(['from_account', 'to_account', 'amount', 'balances', 'action'])
                ->make(true);
        }

        return view('backend.fund_transfer.index');
    }

    public function create(Request $request)
    {
        $paymentTypes = DbPaymentType::where('status', 'active')->get();
        $transferTypes = AcMoneyTransferType::where('status', 'active')->orderBy('name')->get();
        $users = User::where('status', 1)->orderBy('name')->get();
        $accounts = AcAccount::where('status', 'active')->orderBy('account_name')->get();
        $selectedFrom = $request->query('from_payment_type_id');
        $transferNo = $this->nextTransferCode();

        return view('backend.fund_transfer.create', compact('paymentTypes', 'transferTypes', 'users', 'accounts', 'selectedFrom', 'transferNo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transfer_date' => ['required', 'date'],
            'from_payment_type_id' => ['required', 'different:to_payment_type_id'],
            'to_payment_type_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_type_id' => ['nullable', 'exists:ac_moneytransfer_types,id'],
            'transfer_method' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'transfer_charge' => ['nullable', 'numeric', 'min:0'],
            'charge_account_id' => ['nullable', 'exists:ac_accounts,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'approval_status' => ['required', 'in:approved,pending'],
            'attachment' => ['nullable', 'file', 'max:4096'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $fromPaymentType = DbPaymentType::findOrFail($request->from_payment_type_id);
            $toPaymentType = DbPaymentType::findOrFail($request->to_payment_type_id);
            $fromAccount = $this->paymentTypeAccount($fromPaymentType);
            $toAccount = $this->paymentTypeAccount($toPaymentType);

            if (!$fromAccount || !$toAccount) {
                DB::rollBack();
                Toastr::error('Selected payment type account is missing.', 'Error');
                return back()->withInput();
            }

            $amount = (float) $request->amount;
            $charge = (float) ($request->transfer_charge ?? 0);
            if ($charge > 0 && !$request->charge_account_id) {
                DB::rollBack();
                Toastr::error('Please select a charge account for transfer charge.', 'Error');
                return back()->withInput();
            }

            $fromBalanceBefore = $this->accountBalanceOnDate($fromAccount->id, $request->transfer_date);
            $toBalanceBefore = $this->accountBalanceOnDate($toAccount->id, $request->transfer_date);

            if ($amount + $charge > $fromBalanceBefore) {
                DB::rollBack();
                Toastr::error('Insufficient source account balance. Available: ৳' . number_format($fromBalanceBefore, 2), 'Error');
                return back()->withInput();
            }

            $attachment = null;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment')->store('uploads/fund-transfers', 'public');
                $attachment = 'storage/' . $attachment;
            }

            $paymentCode = generate_payment_code('FT');
            $transferCode = $request->transfer_code ?: $this->nextTransferCode();
            $transfer = AcMoneyTransfer::create([
                'store_id' => $user->store_id ?? null,
                'transfer_code' => $transferCode,
                'transfer_date' => $request->transfer_date,
                'reference_no' => $request->reference_no,
                'from_payment_type_id' => $fromPaymentType->id,
                'to_payment_type_id' => $toPaymentType->id,
                'transfer_type_id' => $request->transfer_type_id,
                'transfer_method' => $request->transfer_method,
                'debit_account_id' => $toAccount->id,
                'credit_account_id' => $fromAccount->id,
                'amount' => $amount,
                'transfer_charge' => $charge,
                'charge_account_id' => $request->charge_account_id,
                'attachment' => $attachment,
                'from_balance_before' => $fromBalanceBefore,
                'from_balance_after' => $fromBalanceBefore - $amount - $charge,
                'to_balance_before' => $toBalanceBefore,
                'to_balance_after' => $toBalanceBefore + $amount,
                'closing_balance_date' => $request->transfer_date,
                'approved_by' => $request->approved_by,
                'approval_status' => $request->approval_status,
                'note' => $request->note,
                'created_by' => substr($user->name, 0, 50),
                'created_date' => $request->transfer_date,
                'created_time' => date('H:i:s'),
                'creator' => $user->id,
                'slug' => Str::slug($transferCode) . '-' . time(),
                'status' => 'active',
                'created_at' => Carbon::now('Asia/Dhaka'),
                'updated_at' => Carbon::now('Asia/Dhaka'),
            ]);

            $baseNote = $request->note ?: 'Fund transfer from ' . $fromPaymentType->payment_type . ' to ' . $toPaymentType->payment_type;
            AcTransaction::create($this->transactionPayload($user, $paymentCode, $request->transfer_date, $toAccount->id, null, $amount, null, $baseNote, $transfer->id));
            AcTransaction::create($this->transactionPayload($user, $paymentCode, $request->transfer_date, null, $fromAccount->id, null, $amount, $baseNote, $transfer->id));

            if ($charge > 0 && $request->charge_account_id) {
                $chargeNote = 'Transfer charge for ' . $transferCode;
                AcTransaction::create($this->transactionPayload($user, $paymentCode, $request->transfer_date, $request->charge_account_id, null, $charge, null, $chargeNote, $transfer->id));
                AcTransaction::create($this->transactionPayload($user, $paymentCode, $request->transfer_date, null, $fromAccount->id, null, $charge, $chargeNote, $transfer->id));
            }

            DB::commit();

            Toastr::success('Fund transfer saved successfully.', 'Success');
            return $request->has('save_print')
                ? redirect()->route('PrintFundTransfer', $transfer->id)
                : redirect()->route('ViewAllFundTransfer');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    public function print($id)
    {
        $transfer = AcMoneyTransfer::with(['fromPaymentType', 'toPaymentType', 'transferType', 'creator_info', 'approver'])->findOrFail($id);
        $generalInfo = DB::table('general_infos')->where('id', 1)->first();

        return view('backend.fund_transfer.print', compact('transfer', 'generalInfo'));
    }

    public function balance(Request $request)
    {
        $request->validate([
            'payment_type_id' => ['required'],
            'date' => ['nullable', 'date'],
        ]);

        $paymentType = DbPaymentType::findOrFail($request->payment_type_id);
        $account = $this->paymentTypeAccount($paymentType);
        $date = $request->date ?: Carbon::now('Asia/Dhaka')->toDateString();
        $closingBalance = $account ? $this->accountBalanceOnDate($account->id, $date) : 0;

        return response()->json([
            'success' => true,
            'balance' => $paymentType->total_amount ?? 0,
            'closing_balance' => $closingBalance,
            'formatted_balance' => number_format($paymentType->total_amount ?? 0, 2),
            'formatted_closing_balance' => number_format($closingBalance, 2),
        ]);
    }

    public function transferTypes(Request $request)
    {
        if ($request->ajax()) {
            $data = AcMoneyTransferType::where('status', 'active')->orderBy('id', 'DESC');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    return '<button type="button" class="btn-sm btn-warning rounded edit-type" data-id="' . $data->id . '" data-name="' . e($data->name) . '" data-note="' . e($data->note) . '"><i class="fas fa-edit"></i></button>'
                        . ' <a href="' . route('DeleteFundTransferType', $data->id) . '" class="btn-sm btn-danger rounded delete-type"><i class="fas fa-trash-alt"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('backend.fund_transfer.types');
    }

    public function storeTransferType(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        $payload = [
            'name' => $request->name,
            'note' => $request->note,
            'creator' => auth()->id(),
            'slug' => Str::slug($request->name) . '-' . time(),
            'status' => 'active',
        ];

        if ($request->id) {
            AcMoneyTransferType::whereKey($request->id)->update($payload);
        } else {
            AcMoneyTransferType::create($payload);
        }

        Toastr::success('Transfer type saved successfully.', 'Success');
        return back();
    }

    public function deleteTransferType($id)
    {
        AcMoneyTransferType::whereKey($id)->update(['status' => 'inactive']);

        return response()->json(['success' => true]);
    }

    private function paymentTypeAccount(DbPaymentType $paymentType): ?AcAccount
    {
        return AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first()
            ?: AcAccount::whereIn('id', array_filter([$paymentType->debit_account_id, $paymentType->credit_account_id]))->where('status', 'active')->first();
    }

    private function accountBalanceOnDate(int $accountId, string $date): float
    {
        $debits = (float) AcTransaction::where('debit_account_id', $accountId)
            ->where('status', 'active')
            ->whereDate('transaction_date', '<=', $date)
            ->sum('debit_amt');
        $credits = (float) AcTransaction::where('credit_account_id', $accountId)
            ->where('status', 'active')
            ->whereDate('transaction_date', '<=', $date)
            ->sum('credit_amt');

        $account = AcAccount::find($accountId);
        $normalBalance = $account->normal_balance ?: (in_array($account->account_type, ['liability', 'equity', 'revenue'], true) ? 'credit' : 'debit');

        return $normalBalance === 'credit' ? $credits - $debits : $debits - $credits;
    }

    private function nextTransferCode(): string
    {
        $prefix = 'FT-' . date('Ymd') . '-';
        $last = AcMoneyTransfer::where('transfer_code', 'LIKE', $prefix . '%')->orderBy('transfer_code', 'desc')->value('transfer_code');
        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    private function transactionPayload($user, string $paymentCode, string $date, $debitAccountId, $creditAccountId, $debitAmount, $creditAmount, string $note, int $transferId): array
    {
        return [
            'store_id' => $user->store_id ?? null,
            'payment_code' => $paymentCode,
            'transaction_date' => $date,
            'transaction_type' => 'FUND_TRANSFER',
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'debit_amt' => $debitAmount,
            'credit_amt' => $creditAmount,
            'note' => $note,
            'ref_moneytransfer_id' => $transferId,
            'created_by' => substr($user->name, 0, 50),
            'creator' => $user->id,
            'slug' => uniqid() . time(),
            'status' => 'active',
            'created_at' => Carbon::now('Asia/Dhaka'),
            'updated_at' => Carbon::now('Asia/Dhaka'),
        ];
    }
}
