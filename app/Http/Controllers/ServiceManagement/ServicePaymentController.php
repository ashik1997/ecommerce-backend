<?php

namespace App\Http\Controllers\ServiceManagement;

use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use App\Models\GeneralInfo;
use App\Models\ServiceManagement\ServiceInstance;
use App\Models\ServiceManagement\ServicePayment;
use App\Services\ServiceManagement\ServiceTransactionService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class ServicePaymentController extends Controller
{
    public function __construct(private ServiceTransactionService $transactionService)
    {
    }

    public function index()
    {
        $this->transactionService->ensureAccountingSetup();

        $dueInstances = ServiceInstance::with(['service', 'customer'])
            ->where('due_amount', '>', 0)
            ->where('status', 'billed')
            ->latest()
            ->get();

        $paymentTypes = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();
        $payments = ServicePayment::with(['serviceInstance.service', 'customer', 'paymentType', 'account'])
            ->latest()
            ->paginate(20);

        return view('backend.service_management.payments.index', compact('dueInstances', 'paymentTypes', 'payments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_instance_id' => ['required', 'exists:srms_service_instances,id'],
            'payment_type_id' => ['required', 'exists:db_paymenttypes,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $instance = ServiceInstance::findOrFail($data['service_instance_id']);
            $paymentType = DbPaymentType::findOrFail($data['payment_type_id']);

            $this->transactionService->collectPayment(
                $instance,
                $paymentType,
                (float) $data['amount'],
                $data['payment_date'],
                $data['note'] ?? null
            );

            Toastr::success('Service payment received successfully.', 'Success');

            return redirect()->route('service-management.payments.index');
        } catch (\Throwable $exception) {
            Toastr::error($exception->getMessage(), 'Payment Error');

            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }
    }

    public function receipt(ServicePayment $payment)
    {
        $payment->load(['serviceInstance.service', 'customer', 'paymentType', 'account']);

        return view('backend.service_management.invoices.receipt', [
            'payment' => $payment,
            'company' => $this->companyInfo(),
        ]);
    }

    private function companyInfo(): array
    {
        $generalInfo = GeneralInfo::where('id', 1)->first();

        return [
            'name' => $generalInfo->company_name ?? config('app.name', 'Company Name'),
            'address' => $generalInfo->address ?? '',
            'phone' => $generalInfo->contact ?? '',
            'email' => $generalInfo->email ?? '',
            'website' => $generalInfo->website ?? str_replace(['http://', 'https://'], '', url('/')),
            'logo' => $generalInfo && $generalInfo->logo ? get_file_url() . '/' . $generalInfo->logo : null,
        ];
    }
}
