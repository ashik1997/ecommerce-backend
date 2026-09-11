@extends('backend.master')
@section('page_title','Commission Manual - Bangla')
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
            <p class="mb-0">সহজ ভাষায় এই module কীভাবে ব্যবহার করবেন—Salesman commission, Affiliate commission, Settlement, Ledger এবং Report।</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('sr.commission-manual.en') }}" class="btn btn-light btn-sm">English Manual</a>
            <a href="{{ route('sr.commission-reports.index') }}" class="btn btn-outline-light btn-sm">Commission Report</a>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">১. এই module দিয়ে কী হবে?</div>
        <div class="card-body">
            <p>এই module দিয়ে দোকান/ব্যবসা owner দেখতে পারবেন কোন salesman বা affiliate কত sales করেছে, তার কত commission হয়েছে, কত paid হয়েছে, আর কত due আছে।</p>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li>Salesman বা SR-wise commission rule setup করা যাবে।</li>
                        <li>Affiliate partner তৈরি করে referral code দেওয়া যাবে।</li>
                        <li>Order confirm/delivered হলে commission generate হবে।</li>
                        <li>Commission approve, settle এবং paid করা যাবে।</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>Order cancel/return হলে commission reverse হবে।</li>
                        <li>Paid commission ফেরত না মুছে adjustment হবে।</li>
                        <li>Ledger থেকে salesman/affiliate due দেখা যাবে।</li>
                        <li>Report থেকে total earned, paid, due দেখা যাবে।</li>
                    </ul>
                </div>
            </div>
            <div class="soft-note mt-2">সহজ কথা: <strong>Sales হলো → commission তৈরি হলো → approve হলো → settlement হলো → payment হলো → ledger/report update হলো।</strong></div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">২. Basic Flow</div>
        <div class="card-body">
            <ol class="mb-0">
                <li>প্রথমে <strong>Affiliate Partners</strong> বা Salesman/User ready রাখুন।</li>
                <li><strong>Commission Rules</strong> থেকে rule তৈরি করুন।</li>
                <li>POS/E-commerce order করার সময় salesman বা affiliate code যুক্ত করুন।</li>
                <li>Order status <code>invoiced</code> / <code>delivered</code> হলে commission entry তৈরি হবে।</li>
                <li><strong>Commission Entries</strong> থেকে entry check করে approve করুন।</li>
                <li><strong>Commission Settlement</strong> থেকে date range দিয়ে settlement তৈরি করুন।</li>
                <li>Settlement approve করে paid করলে ledger/report update হবে।</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৩. Affiliate Partner তৈরি করার নিয়ম</div>
        <div class="card-body">
            <p>যদি আপনার দোকানে Facebook promoter, influencer, reseller বা external reference থাকে, তাকে Affiliate Partner হিসেবে তৈরি করুন।</p>
            <p><strong>Menu:</strong> SR Management → Affiliate Partners → Create</p>
            <ul>
                <li><strong>Name:</strong> Affiliate ব্যক্তির নাম।</li>
                <li><strong>Phone/Email:</strong> যোগাযোগের তথ্য।</li>
                <li><strong>Code:</strong> যেমন <code>RAHIM10</code>, <code>FBAD01</code>। Order-এ এই code দিলে affiliate detect হবে।</li>
                <li><strong>Default Commission:</strong> আলাদা rule না থাকলে এই default value ব্যবহার করা যাবে।</li>
                <li><strong>Status:</strong> Active না হলে code ব্যবহার করা যাবে না।</li>
            </ul>
            <div class="ok-note">Tip: Affiliate code uppercase এবং short রাখুন। যেমন: <code>SUMON5</code>, <code>FB2026</code>।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৪. Commission Rule তৈরি করার নিয়ম</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Rules → Create</p>
            <p>Commission rule হলো system-কে বলা: “কাকে, কোন sales-এর উপর, কত commission দিতে হবে।”</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Commission For</td><td>Salesman নাকি Affiliate</td><td>Salesman</td></tr>
                        <tr><td>Base</td><td>Sale amount নাকি gross profit-এর উপর হিসাব হবে</td><td>Sale Amount</td></tr>
                        <tr><td>Type</td><td>Fixed amount নাকি percentage</td><td>Percentage</td></tr>
                        <tr><td>Value</td><td>Commission value</td><td>3%</td></tr>
                        <tr><td>User/Affiliate</td><td>নির্দিষ্ট person হলে select করুন</td><td>Rahim SR</td></tr>
                        <tr><td>Product/Category/Website</td><td>নির্দিষ্ট product/category/site হলে select করুন</td><td>Website A</td></tr>
                        <tr><td>Date Range</td><td>Rule কতদিন active থাকবে</td><td>01-06-2026 to 30-06-2026</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="warn-note">একই person/product/date-এর জন্য একাধিক active rule থাকলে conflict হতে পারে। Priority দেখে rule তৈরি করুন।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৫. Order করার সময় কী করবেন?</div>
        <div class="card-body">
            <h6><span class="step-badge">A</span> POS Order</h6>
            <p>POS order screen-এ Salesman/SR select করুন। Affiliate থাকলে affiliate code দিন। Order status <code>invoiced</code> বা <code>delivered</code> হলে commission generate হবে।</p>
            <h6 class="mt-3"><span class="step-badge">B</span> E-commerce Order</h6>
            <p>E-commerce order edit page-এ Salesman বা Affiliate Code set করা যাবে। Checkout/referral tracking থাকলে affiliate code automatically আসতে পারে।</p>
            <div class="soft-note">Order শুধু pending/quotation থাকলে commission তৈরি হবে না। Confirm বা delivered status-এ গেলে commission হবে।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৬. Commission Entries কীভাবে ব্যবহার করবেন?</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Entries</p>
            <p>এখানে order-wise commission list দেখা যাবে।</p>
            <ul>
                <li><strong>Pending:</strong> Commission তৈরি হয়েছে, কিন্তু এখনো approve হয়নি।</li>
                <li><strong>Approved:</strong> Settlement-এর জন্য ready।</li>
                <li><strong>Settled/Paid:</strong> Settlement/payment process হয়েছে।</li>
                <li><strong>Reversed:</strong> Order cancel/return/edit এর কারণে reverse হয়েছে।</li>
            </ul>
            <p>ভুল commission হলে entry reverse করা যাবে। Order edit হলে system recalculation করতে পারে।</p>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৭. Commission Settlement / Payment</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Settlement</p>
            <p>এখান থেকে salesman/affiliate কে কবে কত commission দেওয়া হলো তা track হবে।</p>
            <ol>
                <li>Settlement type select করুন: Salesman অথবা Affiliate।</li>
                <li>Person select করুন।</li>
                <li>Date range দিন।</li>
                <li>System approved unpaid commission load করবে।</li>
                <li>Draft settlement create করুন।</li>
                <li>Check করে Approve করুন।</li>
                <li>Payment method দিয়ে Paid/Partially Paid করুন।</li>
            </ol>
            <div class="warn-note">Paid settlement delete করা উচিত নয়। Order পরে cancel/return হলে next settlement থেকে adjustment কাটবে।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৮. Commission Ledger</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Ledger</p>
            <p>Ledger থেকে একজন salesman/affiliate-এর complete history দেখা যাবে।</p>
            <ul>
                <li>Opening Due</li>
                <li>Earned Commission</li>
                <li>Approved Commission</li>
                <li>Paid Settlement</li>
                <li>Adjustment / Deduction</li>
                <li>Closing Due</li>
            </ul>
            <div class="ok-note">Owner-এর জন্য সবচেয়ে দরকারি প্রশ্ন: “তার কাছে আর কত পাওনা আছে?” — এর উত্তর Ledger দেবে।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">৯. Commission Report</div>
        <div class="card-body">
            <p><strong>Menu:</strong> SR Management → Commission Report</p>
            <p>এখানে overall summary দেখা যাবে:</p>
            <ul>
                <li>Total Earned</li>
                <li>Pending / Approved / Settled / Paid / Due</li>
                <li>Salesman-wise summary</li>
                <li>Affiliate-wise summary</li>
                <li>Commission cost profit-এর উপর কত impact করছে</li>
                <li>CSV export</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">১০. Profit Impact</div>
        <div class="card-body">
            <p>Commission system profit calculation-এর সাথে connected।</p>
            <div class="soft-note">
                Gross Profit = Sales - FIFO Purchase Cost<br>
                Net/Contribution Profit = Gross Profit - Salesman Commission - Affiliate Commission - Other Direct Costs
            </div>
            <p class="mt-3">তাই commission approve/settlement করলে owner বুঝতে পারবেন actual profit কত থাকছে।</p>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">১১. Accounting Integration</div>
        <div class="card-body">
            <p>যদি accounting integration enable থাকে, settlement approve/payment-এর সময় accounting entry তৈরি হবে।</p>
            <ul>
                <li>Settlement approve: Commission Expense Dr, Commission Payable Cr</li>
                <li>Payment done: Commission Payable Dr, Cash/Bank Cr</li>
            </ul>
            <div class="warn-note">Accounting head seed করা না থাকলে আগে <code>CommissionAccountingSeeder</code> run করতে হবে।</div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">১২. Common Problems & Solution</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Commission তৈরি হচ্ছে না</td><td>Order pending অথবা rule inactive</td><td>Order status confirm/delivered করুন, rule active করুন</td></tr>
                        <tr><td>Affiliate detect হচ্ছে না</td><td>Code wrong/inactive</td><td>Affiliate code validate করুন</td></tr>
                        <tr><td>Settlement list empty</td><td>Commission approved হয়নি</td><td>Commission Entries থেকে approve করুন</td></tr>
                        <tr><td>Paid commission reverse দরকার</td><td>Order return/cancel হয়েছে</td><td>Next settlement adjustment হবে</td></tr>
                        <tr><td>Report amount মিলছে না</td><td>Date/status filter ভিন্ন</td><td>Filter reset করে আবার check করুন</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card manual-card">
        <div class="card-header">১৩. Daily Use Checklist</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>প্রতিদিন confirmed/delivered orders check করুন।</li>
                <li>Commission Entries থেকে pending entries approve করুন।</li>
                <li>সপ্তাহ/মাস শেষে settlement generate করুন।</li>
                <li>Payment হলে settlement mark paid করুন।</li>
                <li>Commission Ledger থেকে due verify করুন।</li>
                <li>Commission Report থেকে owner summary দেখুন।</li>
            </ul>
        </div>
    </div>
</div>
@endsection
