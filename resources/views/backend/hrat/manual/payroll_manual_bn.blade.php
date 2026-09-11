@extends('backend.master')
@section('page_title', 'Payroll Manual - Bangla')
@section('page_heading', 'Payroll Manual')
@section('content')
<style>
    .payroll-manual-page .manual-hero{background:linear-gradient(135deg,#155e75,#0f766e);color:#fff;border-radius:12px;padding:24px;margin-bottom:18px;}
    .payroll-manual-page .manual-card{border:1px solid #e5e7eb;border-radius:10px;margin-bottom:16px;box-shadow:0 2px 10px rgba(15,23,42,.04);}
    .payroll-manual-page .manual-card .card-header{background:#f8fafc;font-weight:700;border-bottom:1px solid #e5e7eb;}
    .payroll-manual-page .soft-note{background:#ecfeff;border-left:4px solid #06b6d4;padding:12px;border-radius:8px;}
    .payroll-manual-page .warn-note{background:#fff7ed;border-left:4px solid #f97316;padding:12px;border-radius:8px;}
    .payroll-manual-page .ok-note{background:#f0fdf4;border-left:4px solid #22c55e;padding:12px;border-radius:8px;}
    .payroll-manual-page .index-list a{display:block;padding:7px 0;color:#0f766e;font-weight:600;}
    .payroll-manual-page code{background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#0f172a;}
</style>

<div class="payroll-manual-page">
    <div class="manual-hero d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="mb-2 text-white">HR Payroll Manual</h3>
            <p class="mb-0">Salary grade, component, assignment, payroll generate, accounting finalize এবং payment process-এর detailed Bangla guide।</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('hrat.payroll-manual.en') }}" class="btn btn-light btn-sm">English Manual</a>
            <a href="{{ route('hrat.payrolls.index') }}" class="btn btn-outline-light btn-sm">Payrolls</a>
        </div>
    </div>

    <div class="card manual-card" id="index">
        <div class="card-header">Index</div>
        <div class="card-body">
            <div class="row index-list">
                <div class="col-md-4">
                    <a href="#overview">১. Payroll module overview</a>
                    <a href="#before-start">২. Payroll চালুর আগে যা লাগবে</a>
                    <a href="#salary-grades">৩. Salary Grades</a>
                    <a href="#salary-components">৪. Salary Components</a>
                </div>
                <div class="col-md-4">
                    <a href="#assignments">৫. Salary Assignments</a>
                    <a href="#calculation">৬. Payroll calculation formula</a>
                    <a href="#generate">৭. Draft payroll generate</a>
                    <a href="#review">৮. Draft review checklist</a>
                </div>
                <div class="col-md-4">
                    <a href="#approve-finalize">৯. Approve, finalize, paid</a>
                    <a href="#reverse">১০. ভুল হলে correction</a>
                    <a href="#examples">১১. Practical examples</a>
                    <a href="#problems">১২. Common problems</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="overview">
        <div class="card-header">১. Payroll module দিয়ে কী হবে?</div>
        <div class="card-body">
            <p>Payroll module দিয়ে employee salary package setup, monthly salary calculation, payslip, accounting accrual এবং salary payment posting manage করা যায়।</p>
            <div class="soft-note">
                মূল flow: <strong>Salary Grade → Salary Component → Salary Assignment → Attendance verify → Generate Draft → Review → Approve → Finalize + Post → Mark Paid</strong>
            </div>
            <ul class="mt-3">
                <li><strong>Salary Grades:</strong> grade-wise salary structure reference।</li>
                <li><strong>Salary Components:</strong> allowance/deduction item।</li>
                <li><strong>Salary Assignments:</strong> employee-এর actual salary package।</li>
                <li><strong>Payrolls:</strong> monthly salary generate, approve, finalize, paid।</li>
                <li><strong>Payroll Report:</strong> summary, filter এবং export।</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card" id="before-start">
        <div class="card-header">২. Payroll চালুর আগে যা লাগবে</div>
        <div class="card-body">
            <ol>
                <li>Employee Profile active থাকতে হবে।</li>
                <li>Employee-এর attendance month complete এবং verified হতে হবে।</li>
                <li>Pending attendance adjustment clear করতে হবে।</li>
                <li>Holiday, leave, absent, late এবং overtime report check করতে হবে।</li>
                <li>Salary grade এবং salary component setup থাকতে হবে।</li>
                <li>Employee salary assignment active এবং payroll month-এর সাথে effective date overlap থাকতে হবে।</li>
                <li>Finalize করার জন্য accounting head লাগবে: Salary Expense account এবং Salary Payable account।</li>
                <li>Mark paid করার জন্য payment type/account-এ পর্যাপ্ত balance থাকতে হবে।</li>
            </ol>
            <div class="warn-note">Payroll generate করার আগে attendance data verify না করলে net payable ভুল হবে। Payroll draft approve করার পর regenerate করা যাবে না।</div>
        </div>
    </div>

    <div class="card manual-card" id="salary-grades">
        <div class="card-header">৩. Salary Grades</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Grades</p>
            <p>Salary grade হলো salary structure-এর category। এটি employee package organize করতে সাহায্য করে।</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Grade Name</td><td>Salary category name</td><td>Grade A, Grade B, Sales Grade</td></tr>
                        <tr><td>Minimum Salary</td><td>এই grade-এর minimum range</td><td>15000</td></tr>
                        <tr><td>Maximum Salary</td><td>এই grade-এর maximum range</td><td>30000</td></tr>
                        <tr><td>Status</td><td>Active হলে assignment-এ use করা যাবে</td><td>Active</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Tip: grade naming simple রাখুন। যেমন <code>Office Staff</code>, <code>Sales Team</code>, <code>Management</code>।</div>
        </div>
    </div>

    <div class="card manual-card" id="salary-components">
        <div class="card-header">৪. Salary Components</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Components</p>
            <p>Salary component হলো basic salary-এর বাইরে extra allowance বা deduction।</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Name</td><td>Component name</td><td>House Rent, Medical, Transport, Tax</td></tr>
                        <tr><td>Code</td><td>Short unique code</td><td>HR, MED, TAX</td></tr>
                        <tr><td>Type</td><td>Allowance নাকি Deduction</td><td>Allowance</td></tr>
                        <tr><td>Calculation Type</td><td>Fixed নাকি Percentage</td><td>Fixed</td></tr>
                        <tr><td>Default Amount</td><td>Default value</td><td>3000</td></tr>
                        <tr><td>Taxable</td><td>Tax calculation-এ ধরবে কি না</td><td>No</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">
                Allowance salary বাড়ায়। Deduction salary কমায়। Example: Basic 20000 + Allowance 3000 - Deduction 500 = 22500 before attendance adjustments।
            </div>
        </div>
    </div>

    <div class="card manual-card" id="assignments">
        <div class="card-header">৫. Salary Assignments</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Assignments</p>
            <p>Salary assignment দিয়ে কোন employee কোন grade, কত basic salary এবং কোন allowance/deduction পাবে তা set করা হয়।</p>
            <ol>
                <li>Employee select করুন।</li>
                <li>Salary Grade select করুন।</li>
                <li>Basic Salary লিখুন।</li>
                <li>Effective From date দিন।</li>
                <li>Salary change temporary হলে Effective To date দিন, না হলে blank রাখুন।</li>
                <li>Save করার পর package components add করুন।</li>
            </ol>
            <div class="warn-note">একই employee-এর একই date range-এ দুইটা active salary assignment overlap করা যাবে না। Salary increment হলে পুরনো assignment-এর end date দিয়ে নতুন assignment শুরু করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="calculation">
        <div class="card-header">৬. Payroll calculation formula</div>
        <div class="card-body">
            <p>System payroll generate করার সময় active salary assignment এবং attendance summary থেকে calculation করে।</p>
            <div class="soft-note">
                Daily Rate = Basic Salary / Payroll Cycle Days<br>
                Hourly Rate = Daily Rate / 8<br>
                Absent Deduction = Daily Rate × Absent Days<br>
                Late Deduction = Hourly Rate × Late Minutes / 60<br>
                Unpaid Leave Deduction = Daily Rate × Unpaid Leave Days<br>
                Overtime Amount = Hourly Rate × Overtime Minutes / 60<br>
                Net Payable = Basic Salary + Allowances + Overtime - Component Deductions - Late Deduction - Unpaid Leave Deduction - Absent Deduction
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead><tr><th>Item</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Basic Salary</td><td>30000</td></tr>
                        <tr><td>Allowances</td><td>5000</td></tr>
                        <tr><td>Overtime</td><td>1000</td></tr>
                        <tr><td>Component Deduction</td><td>500</td></tr>
                        <tr><td>Absent/Late/Unpaid Leave Deduction</td><td>2000</td></tr>
                        <tr><td>Net Payable</td><td>30000 + 5000 + 1000 - 500 - 2000 = 33500</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="generate">
        <div class="card-header">৭. Draft payroll generate</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Payrolls</p>
            <ol>
                <li>Year দিন। Example: <code>2026</code></li>
                <li>Month দিন। Example: <code>6</code></li>
                <li><strong>Generate Draft</strong> button click করুন।</li>
                <li>System active salary assignments খুঁজে employee-wise payroll lines তৈরি করবে।</li>
                <li>যদি same month-এর draft আগে থাকে, draft status হলে regenerate/update হবে। Approved/finalized/paid হলে regenerate হবে না।</li>
            </ol>
            <div class="ok-note">Draft হলো review stage। এই stage-এ attendance বা salary assignment ঠিক করে আবার generate করা যায়।</div>
        </div>
    </div>

    <div class="card manual-card" id="review">
        <div class="card-header">৮. Draft review checklist</div>
        <div class="card-body">
            <ul>
                <li>সব employee payroll list-এ এসেছে কি না।</li>
                <li>Basic salary ঠিক আছে কি না।</li>
                <li>Allowance এবং component deduction ঠিক আছে কি না।</li>
                <li>Present, absent, late days ঠিক আছে কি না।</li>
                <li>Overtime minutes/amount ঠিক আছে কি না।</li>
                <li>Absent, late, unpaid leave deduction policy অনুযায়ী হয়েছে কি না।</li>
                <li>Net payable payroll report বা internal sheet-এর সাথে মিলে কি না।</li>
                <li>Payslip view করে employee-wise details check করুন।</li>
            </ul>
            <div class="warn-note">Approve করার আগে ভুল ধরুন। Approve করার পর draft regenerate করা যাবে না; correction করতে status অনুযায়ী reverse/void flow লাগতে পারে।</div>
        </div>
    </div>

    <div class="card manual-card" id="approve-finalize">
        <div class="card-header">৯. Approve, finalize এবং paid</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Status</th><th>Action</th><th>Meaning</th></tr></thead>
                    <tbody>
                        <tr><td>Draft</td><td>Approve</td><td>Payroll checked and approved for accounting</td></tr>
                        <tr><td>Approved</td><td>Finalize + Post</td><td>Salary expense/payable accounting entry হবে</td></tr>
                        <tr><td>Finalized</td><td>Mark Paid</td><td>Payment type দিয়ে salary payment accounting entry হবে</td></tr>
                        <tr><td>Paid</td><td>Done</td><td>Payroll complete</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">
                Accounting entry:<br>
                Finalize: <strong>Salary Expense Dr, Salary Payable Cr</strong><br>
                Mark Paid: <strong>Salary Payable Dr, Cash/Bank/Payment Account Cr</strong>
            </div>
            <div class="warn-note mt-3">Mark Paid করার সময় selected payment type/account-এ enough balance না থাকলে payment post হবে না।</div>
        </div>
    </div>

    <div class="card manual-card" id="reverse">
        <div class="card-header">১০. ভুল হলে correction</div>
        <div class="card-body">
            <ul>
                <li><strong>Draft status:</strong> attendance/salary setup ঠিক করে আবার Generate Draft করুন।</li>
                <li><strong>Approved status:</strong> accounting post হয়নি; দরকার হলে payroll process hold করে data verify করুন।</li>
                <li><strong>Finalized status:</strong> accounting accrual posted হয়েছে। ভুল হলে <strong>Void Finalization</strong> করুন; status আবার Approved হবে।</li>
                <li><strong>Paid status:</strong> payment posted হয়েছে। আগে <strong>Reverse Payment</strong> করুন; status Finalized হবে। তারপর দরকার হলে Void Finalization করুন।</li>
            </ul>
            <div class="warn-note">Paid payroll সরাসরি edit/delete করবেন না। Accounting consistency রাখার জন্য reverse flow ব্যবহার করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="examples">
        <div class="card-header">১১. Practical examples</div>
        <div class="card-body">
            <h6>Example 1: নতুন employee salary setup</h6>
            <ol>
                <li>Salary Grade তৈরি করুন: <code>Sales Grade</code>।</li>
                <li>Components তৈরি করুন: House Rent allowance, Mobile Bill allowance, Loan deduction।</li>
                <li>Salary Assignments-এ employee select করে basic salary এবং effective date দিন।</li>
                <li>Assignment save করার পর employee package-এ components add করুন।</li>
                <li>Payroll generate করে line amount verify করুন।</li>
            </ol>

            <h6 class="mt-3">Example 2: Salary increment</h6>
            <ol>
                <li>পুরনো assignment-এর Effective To দিন: <code>2026-06-30</code>।</li>
                <li>নতুন assignment তৈরি করুন Effective From: <code>2026-07-01</code>।</li>
                <li>নতুন basic salary এবং components set করুন।</li>
                <li>July payroll generate করলে নতুন salary ধরবে।</li>
            </ol>

            <h6 class="mt-3">Example 3: Month-end payroll</h6>
            <ol>
                <li>Monthly, absent, late, overtime, leave report verify করুন।</li>
                <li>Pending adjustment clear করুন।</li>
                <li>Payrolls থেকে draft generate করুন।</li>
                <li>Employee-wise payslip/net payable check করুন।</li>
                <li>Approve → Finalize + Post → Mark Paid করুন।</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card" id="problems">
        <div class="card-header">১২. Common problems & solution</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Employee payroll-এ আসছে না</td><td>Salary assignment নেই বা inactive</td><td>Active salary assignment add করুন</td></tr>
                        <tr><td>পুরনো salary ধরছে</td><td>Effective date overlap বা নতুন assignment active নয়</td><td>Assignment date range ঠিক করুন</td></tr>
                        <tr><td>Absent deduction বেশি</td><td>Attendance/leave/holiday update হয়নি</td><td>Reports verify করে payroll regenerate করুন</td></tr>
                        <tr><td>Overtime আসছে না</td><td>Attendance summary বা shift overtime rule missing</td><td>Shift/report check করুন</td></tr>
                        <tr><td>Draft regenerate হচ্ছে না</td><td>Payroll approved/finalized/paid</td><td>Only draft payroll regenerate করা যায়</td></tr>
                        <tr><td>Finalize হচ্ছে না</td><td>Wrong accounting head বা zero total</td><td>Expense/payable account এবং total check করুন</td></tr>
                        <tr><td>Mark paid হচ্ছে না</td><td>Insufficient payment balance</td><td>Payment type/account balance check করুন</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Best practice: payroll approve করার আগে HR, Accounts এবং owner/manager summary একবার মিলিয়ে নিন।</div>
        </div>
    </div>
</div>
@endsection
