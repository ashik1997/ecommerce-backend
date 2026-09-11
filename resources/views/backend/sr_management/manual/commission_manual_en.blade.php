@extends('backend.master')
@section('page_title','Commission Manual - English')
@section('page_heading','Commission Manual')
@section('content')
<style>
    .commission-manual-page .manual-hero{background:linear-gradient(135deg,#0f766e,#0ea5e9);color:#fff;border-radius:12px;padding:24px;margin-bottom:18px;}
    .commission-manual-page .manual-card{border:1px solid #e5e7eb;border-radius:10px;margin-bottom:16px;box-shadow:0 2px 10px rgba(15,23,42,.04);}
    .commission-manual-page .manual-card .card-header{background:#f8fafc;font-weight:700;border-bottom:1px solid #e5e7eb;}
    .commission-manual-page .step-badge{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#0f766e;color:#fff;font-weight:700;margin-right:8px;}
    .commission-manual-page .soft-note{background:#ecfeff;border-left:4px solid #06b6d4;padding:12px;border-radius:8px;}
    .commission-manual-page .warn-note{background:#fff7ed;border-left:4px solid #f97316;padding:12px;border-radius:8px;}
    .commission-manual-page .ok-note{background:#f0fdf4;border-left:4px solid #22c55e;padding:12px;border-radius:8px;}
    .commission-manual-page code{background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#0f172a;}
</style>
<div class="commission-manual-page">
    <div class="manual-hero d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="mb-2 text-white">Sales Commission & Affiliate Manual</h3>
            <p class="mb-0">A simple guide for using salesman commission, affiliate commission, settlement, ledger, and reports.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('sr.commission-manual.bn') }}" class="btn btn-light btn-sm">বাংলা Manual</a>
            <a href="{{ route('sr.commission-reports.index') }}" class="btn btn-outline-light btn-sm">Commission Report</a>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">1. What is this module for?</div>
        <div class="card-body">
            <p>This module helps the business owner track how much sales commission or affiliate commission is earned, approved, settled, paid, and still due.</p>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li>Create salesman/SR commission rules.</li>
                        <li>Create affiliate partners with referral codes.</li>
                        <li>Generate commission when an order is confirmed or delivered.</li>
                        <li>Approve, settle, and pay commission.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>Reverse commission when an order is cancelled or returned.</li>
                        <li>Handle paid commission through adjustment, not deletion.</li>
                        <li>Check due amounts from the ledger.</li>
                        <li>View earned, paid, and due amounts from reports.</li>
                    </ul>
                </div>
            </div>
            <div class="soft-note mt-2"><strong>Simple flow:</strong> Sale happens → commission is generated → commission is approved → settlement is created → payment is made → ledger/report is updated.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">2. Basic Flow</div>
        <div class="card-body">
            <ol class="mb-0">
                <li>Prepare salesmen/users and affiliate partners.</li>
                <li>Create commission rules from <strong>Commission Rules</strong>.</li>
                <li>Select a salesman or enter an affiliate code while creating/editing an order.</li>
                <li>When order status becomes <code>invoiced</code> or <code>delivered</code>, commission is generated.</li>
                <li>Review and approve entries from <strong>Commission Entries</strong>.</li>
                <li>Create settlement for a date range from <strong>Commission Settlement</strong>.</li>
                <li>Approve and pay the settlement to update ledger and reports.</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">3. Affiliate Partner Setup</div>
        <div class="card-body">
            <p>Use Affiliate Partners for influencers, resellers, external promoters, Facebook campaign references, or referral partners.</p>
            <p><strong>Menu:</strong> SR Management → Affiliate Partners → Create</p>
            <ul>
                <li><strong>Name:</strong> Partner name.</li>
                <li><strong>Phone/Email:</strong> Contact details.</li>
                <li><strong>Code:</strong> Example: <code>RAHIM10</code>, <code>FBAD01</code>. This code links the order with the affiliate.</li>
                <li><strong>Default Commission:</strong> Used when no specific rule is available.</li>
                <li><strong>Status:</strong> Only active affiliates can be used.</li>
            </ul>
            <div class="ok-note">Tip: Keep affiliate codes short and uppercase, such as <code>SUMON5</code> or <code>FB2026</code>.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">4. Commission Rule Setup</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Rules → Create</p>
            <p>A commission rule tells the system who should receive commission, on which base, and how much.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Commission For</td><td>Salesman or Affiliate</td><td>Salesman</td></tr>
                        <tr><td>Base</td><td>Calculate on sale amount or gross profit</td><td>Sale Amount</td></tr>
                        <tr><td>Type</td><td>Fixed amount or percentage</td><td>Percentage</td></tr>
                        <tr><td>Value</td><td>Commission value</td><td>3%</td></tr>
                        <tr><td>User/Affiliate</td><td>Select a specific person if needed</td><td>Rahim SR</td></tr>
                        <tr><td>Product/Category/Website</td><td>Limit rule to a specific product/category/site</td><td>Website A</td></tr>
                        <tr><td>Date Range</td><td>Rule validity period</td><td>2026-06-01 to 2026-06-30</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="warn-note">Avoid overlapping active rules for the same person, product/category, and date range unless you intentionally set rule priority.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">5. What to do during order creation/edit?</div>
        <div class="card-body">
            <h6><span class="step-badge">A</span> POS Order</h6>
            <p>Select Salesman/SR in the POS screen. If there is an affiliate reference, enter the affiliate code. Commission is generated when the order becomes <code>invoiced</code> or <code>delivered</code>.</p>
            <h6 class="mt-3"><span class="step-badge">B</span> E-commerce Order</h6>
            <p>From the e-commerce order edit page, you can set Salesman or Affiliate Code. If referral tracking is available, affiliate code may already be attached to the order.</p>
            <div class="soft-note">No commission is generated for pending or quotation orders. Commission starts when the order becomes confirmed/delivered.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">6. Commission Entries</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Entries</p>
            <p>This page shows order-wise commission records.</p>
            <ul>
                <li><strong>Pending:</strong> Commission was generated but not approved yet.</li>
                <li><strong>Approved:</strong> Ready for settlement.</li>
                <li><strong>Settled/Paid:</strong> Settlement/payment was processed.</li>
                <li><strong>Reversed:</strong> Reversed because of order cancellation, return, or edit.</li>
            </ul>
            <p>If an entry is wrong, it can be reversed. If an order is edited, the system can recalculate commission.</p>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">7. Commission Settlement / Payment</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Settlement</p>
            <p>This page tracks when and how much commission was settled for a salesman or affiliate.</p>
            <ol>
                <li>Select settlement type: Salesman or Affiliate.</li>
                <li>Select the person.</li>
                <li>Select the date range.</li>
                <li>The system loads approved unpaid commission.</li>
                <li>Create a draft settlement.</li>
                <li>Review and approve the settlement.</li>
                <li>Select payment method and mark as Paid/Partially Paid.</li>
            </ol>
            <div class="warn-note">Do not delete paid settlements. If an order is later returned/cancelled, the amount should be adjusted in the next settlement.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">8. Commission Ledger</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Ledger</p>
            <p>The ledger shows the full earning and payment history of a salesman or affiliate.</p>
            <ul>
                <li>Opening Due</li>
                <li>Earned Commission</li>
                <li>Approved Commission</li>
                <li>Paid Settlement</li>
                <li>Adjustment / Deduction</li>
                <li>Closing Due</li>
            </ul>
            <div class="ok-note">The key business question is: “How much do we still owe this person?” The ledger answers that.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">9. Commission Report</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Report</p>
            <p>This page gives a management-level overview:</p>
            <ul>
                <li>Total Earned</li>
                <li>Pending / Approved / Settled / Paid / Due</li>
                <li>Salesman-wise summary</li>
                <li>Affiliate-wise summary</li>
                <li>Commission cost impact on profit</li>
                <li>CSV export</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">10. Profit Impact</div>
        <div class="card-body">
            <p>The commission system is connected with order profit calculation.</p>
            <div class="soft-note">
                Gross Profit = Sales - FIFO Purchase Cost<br>
                Net/Contribution Profit = Gross Profit - Salesman Commission - Affiliate Commission - Other Direct Costs
            </div>
            <p class="mt-3">This helps the owner understand actual profit after commission cost.</p>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">11. Accounting Integration</div>
        <div class="card-body">
            <p>If accounting integration is enabled, accounting entries are created during settlement approval/payment.</p>
            <ul>
                <li>Settlement approval: Dr Commission Expense, Cr Commission Payable</li>
                <li>Payment: Dr Commission Payable, Cr Cash/Bank</li>
            </ul>
            <div class="warn-note">If accounting heads are missing, run <code>CommissionAccountingSeeder</code> first.</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">12. Common Problems & Solutions</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Commission is not generated</td><td>Order is pending or rule is inactive</td><td>Confirm/deliver the order and activate the rule</td></tr>
                        <tr><td>Affiliate is not detected</td><td>Wrong or inactive code</td><td>Validate the affiliate code</td></tr>
                        <tr><td>Settlement list is empty</td><td>Commission is not approved</td><td>Approve commission entries first</td></tr>
                        <tr><td>Paid commission needs reversal</td><td>Order was returned/cancelled</td><td>Use adjustment in the next settlement</td></tr>
                        <tr><td>Report amount does not match</td><td>Different date/status filter</td><td>Reset filters and check again</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">13. Daily Use Checklist</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Check confirmed/delivered orders every day.</li>
                <li>Approve pending commission entries.</li>
                <li>Create settlement weekly or monthly.</li>
                <li>Mark settlement as paid after payment.</li>
                <li>Verify due amount from Commission Ledger.</li>
                <li>Use Commission Report for owner-level summary.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
