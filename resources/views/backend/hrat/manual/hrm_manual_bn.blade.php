@extends('backend.master')
@section('page_title', 'HRM Manual - Bangla')
@section('page_heading', 'HRM Manual')
@section('content')
<style>
    .hrm-manual-page .manual-hero{background:linear-gradient(135deg,#0f766e,#0284c7);color:#fff;border-radius:12px;padding:24px;margin-bottom:18px;}
    .hrm-manual-page .manual-card{border:1px solid #e5e7eb;border-radius:10px;margin-bottom:16px;box-shadow:0 2px 10px rgba(15,23,42,.04);}
    .hrm-manual-page .manual-card .card-header{background:#f8fafc;font-weight:700;border-bottom:1px solid #e5e7eb;}
    .hrm-manual-page .soft-note{background:#ecfeff;border-left:4px solid #06b6d4;padding:12px;border-radius:8px;}
    .hrm-manual-page .warn-note{background:#fff7ed;border-left:4px solid #f97316;padding:12px;border-radius:8px;}
    .hrm-manual-page .ok-note{background:#f0fdf4;border-left:4px solid #22c55e;padding:12px;border-radius:8px;}
    .hrm-manual-page .index-list a{display:block;padding:7px 0;color:#0f766e;font-weight:600;}
    .hrm-manual-page code{background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#0f172a;}
</style>

<div class="hrm-manual-page">
    <div class="manual-hero d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="mb-2 text-white">HRM Attendance & Payroll Manual</h3>
            <p class="mb-0">এই manual-এ HRM module কীভাবে setup, attendance, leave, report এবং payroll চালাবেন তা Bangla-তে step-by-step দেখানো হলো।</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('hrat.manual.en') }}" class="btn btn-light btn-sm">English Manual</a>
            <a href="{{ route('hrat.dashboard') }}" class="btn btn-outline-light btn-sm">HR Dashboard</a>
        </div>
    </div>

    <div class="card manual-card" id="index">
        <div class="card-header">Index</div>
        <div class="card-body">
            <div class="row index-list">
                <div class="col-md-4">
                    <a href="#overview">১. Module overview</a>
                    <a href="#setup-flow">২. প্রথম setup flow</a>
                    <a href="#masters">৩. Department, Designation, Branch</a>
                    <a href="#shift">৪. Shift setup</a>
                    <a href="#holiday-leave">৫. Holiday এবং Leave setup</a>
                </div>
                <div class="col-md-4">
                    <a href="#employee">৬. Employee Profile</a>
                    <a href="#schedule">৭. Employee Schedule</a>
                    <a href="#attendance">৮. Attendance entry/import</a>
                    <a href="#adjustment">৯. Adjustment approval</a>
                    <a href="#reports">১০. Reports</a>
                </div>
                <div class="col-md-4">
                    <a href="#payroll-setup">১১. Payroll setup</a>
                    <a href="#payroll-run">১২. Payroll generate to paid</a>
                    <a href="#examples">১৩. Practical examples</a>
                    <a href="#problems">১৪. Common problems</a>
                    <a href="#checklist">১৫. Daily/monthly checklist</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="overview">
        <div class="card-header">১. HRM module দিয়ে কী হবে?</div>
        <div class="card-body">
            <p>HRM module দিয়ে employee master data, attendance, leave, shift, overtime, absent/late report এবং monthly payroll একই জায়গা থেকে manage করা যায়।</p>
            <div class="soft-note">
                সহজ flow: <strong>Master setup → Employee profile → Schedule assign → Attendance entry/import → Report check → Payroll generate → Approve → Finalize accounting → Mark paid</strong>
            </div>
            <ul class="mt-3">
                <li><strong>Attendance:</strong> Daily in/out, late, early exit, overtime, absent হিসাব।</li>
                <li><strong>Leave:</strong> Leave type, application, approve/reject, leave report।</li>
                <li><strong>Payroll:</strong> Salary grade, component, employee assignment, payroll generation এবং payment।</li>
                <li><strong>Reports:</strong> Daily, monthly, absent, late, overtime, leave, employee master এবং department/branch summary।</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card" id="setup-flow">
        <div class="card-header">২. প্রথম setup flow</div>
        <div class="card-body">
            <ol>
                <li><strong>Departments</strong>, <strong>Designations</strong>, <strong>Branches</strong> তৈরি করুন।</li>
                <li><strong>Shifts</strong> তৈরি করুন: start time, end time, late grace, overtime rules ঠিক করুন।</li>
                <li><strong>Holiday Calendar</strong> এবং <strong>Leave Types</strong> setup করুন।</li>
                <li><strong>Employee Profiles</strong> তৈরি করে user, department, branch, joining date, manager set করুন।</li>
                <li><strong>Employee Schedules</strong> থেকে employee অনুযায়ী shift assign করুন।</li>
                <li><strong>Configuration</strong> থেকে attendance policy verify করুন।</li>
                <li>Daily attendance <strong>Manual Entry</strong> বা <strong>CSV Import</strong> দিয়ে দিন।</li>
                <li>Month end-এ reports মিলিয়ে payroll generate করুন।</li>
            </ol>
            <div class="warn-note">Employee profile বা schedule ছাড়া attendance দিলে report/payroll ভুল আসতে পারে। আগে setup complete করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="masters">
        <div class="card-header">৩. Department, Designation, Branch</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Departments / Designations / Branches</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Item</th><th>কাজ</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Department</td><td>Employee কোন team বা unit-এ কাজ করে</td><td>Sales, Accounts, Warehouse</td></tr>
                        <tr><td>Designation</td><td>Employee-এর পদবি</td><td>SR, Manager, Accountant</td></tr>
                        <tr><td>Branch</td><td>Employee কোন branch/location-এ কাজ করে</td><td>Dhaka Office, Chittagong Branch</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Tip: Payroll/report filter সুন্দর রাখতে naming standard রাখুন। যেমন <code>Sales</code> এবং <code>sales team</code> দুইভাবে না লিখে একটাই format ব্যবহার করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="shift">
        <div class="card-header">৪. Shift setup</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Shifts</p>
            <p>Shift হলো employee কখন office শুরু করবে, কখন শেষ করবে এবং late/overtime কীভাবে calculate হবে তার rule।</p>
            <ul>
                <li><strong>Start Time:</strong> অফিস শুরু। Example: <code>09:00 AM</code></li>
                <li><strong>End Time:</strong> অফিস শেষ। Example: <code>06:00 PM</code></li>
                <li><strong>Grace Minute:</strong> কত মিনিট দেরি করলে late ধরবে না। Example: <code>10 minutes</code></li>
                <li><strong>Overtime Rule:</strong> shift end-এর পর কত সময় থেকে overtime ধরবে।</li>
                <li><strong>Working Days:</strong> সপ্তাহের কোন দিনগুলো কাজের দিন।</li>
            </ul>
            <div class="soft-note">Example: 09:00-18:00 shift, 10 min grace হলে 09:10 পর্যন্ত late হবে না। 09:11 হলে late report-এ আসবে।</div>
        </div>
    </div>

    <div class="card manual-card" id="holiday-leave">
        <div class="card-header">৫. Holiday Calendar এবং Leave Types</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Holiday Calendar / Leave Types / Leave Applications</p>
            <ul>
                <li><strong>Holiday Calendar:</strong> সরকারি ছুটি, weekly holiday বা company special holiday add করুন।</li>
                <li><strong>Leave Types:</strong> Casual, Sick, Earned, Unpaid leave type তৈরি করুন।</li>
                <li><strong>Leave Applications:</strong> Employee-এর leave request approve/reject/cancel করুন।</li>
            </ul>
            <div class="warn-note">Holiday বা approved leave থাকলে employee absent report-এ সাধারণ absent হিসেবে ধরা উচিত নয়। তাই month start-এর আগে calendar update করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="employee">
        <div class="card-header">৬. Employee Profile</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Employee Profiles</p>
            <p>Employee profile হলো HRM module-এর মূল record। Attendance, leave, payroll সব employee profile-এর সাথে linked।</p>
            <ul>
                <li><strong>User:</strong> system user থাকলে select করুন।</li>
                <li><strong>Employee Code:</strong> unique code দিন, যেমন <code>EMP-001</code>। CSV import-এ code match দরকার হতে পারে।</li>
                <li><strong>Department/Designation/Branch:</strong> report grouping-এর জন্য mandatory ধরে setup করুন।</li>
                <li><strong>Joining Date:</strong> payroll এবং leave eligibility বুঝতে দরকার।</li>
                <li><strong>Status:</strong> inactive হলে attendance/payroll process থেকে বাদ পড়তে পারে।</li>
            </ul>
            <div class="ok-note">প্রতিটি employee-এর code, mobile এবং department ঠিক আছে কি না save করার আগে check করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="schedule">
        <div class="card-header">৭. Employee Schedule</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Employee Schedules</p>
            <p>Schedule দিয়ে employee-এর জন্য shift assign করা হয়। একই employee-এর shift change হলে date range দিয়ে নতুন schedule দিন।</p>
            <ol>
                <li>Employee select করুন।</li>
                <li>Shift select করুন।</li>
                <li>Effective date বা date range দিন।</li>
                <li>Save করে monthly report-এ attendance calculation verify করুন।</li>
            </ol>
            <div class="soft-note">Example: Rahim আগে Day Shift করত, ১ জুলাই থেকে Evening Shift করবে। তাহলে ১ জুলাই থেকে নতুন schedule add করুন, পুরনো data edit করবেন না।</div>
        </div>
    </div>

    <div class="card manual-card" id="attendance">
        <div class="card-header">৮. Attendance entry/import</div>
        <div class="card-body">
            <p><strong>Manual Entry Menu:</strong> HRM Management → Attendance → Manual Entry</p>
            <p><strong>CSV Import Menu:</strong> HRM Management → Attendance → CSV Import</p>
            <h6>Manual Entry কখন ব্যবহার করবেন?</h6>
            <ul>
                <li>Biometric machine miss করেছে।</li>
                <li>Employee field visit থেকে late entry দিয়েছে।</li>
                <li>Old attendance correction করতে হবে।</li>
            </ul>
            <h6 class="mt-3">CSV Import কখন ব্যবহার করবেন?</h6>
            <ul>
                <li>Biometric/device থেকে daily বা monthly attendance file export করা হয়েছে।</li>
                <li>অনেক employee-এর data একসাথে import করতে হবে।</li>
            </ul>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Employee</td><td>কার attendance দেওয়া হচ্ছে</td><td>Rahim Uddin</td></tr>
                        <tr><td>Date</td><td>Attendance date</td><td>2026-06-07</td></tr>
                        <tr><td>Time</td><td>Punch time</td><td>09:05 AM</td></tr>
                        <tr><td>Punch Type</td><td>In অথবা Out</td><td>In</td></tr>
                        <tr><td>Note/Reason</td><td>Manual correction reason</td><td>Device missed punch</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="warn-note">Manual attendance entry audit-sensitive। Reason না লিখে random correction করলে পরে payroll dispute হতে পারে।</div>
        </div>
    </div>

    <div class="card manual-card" id="adjustment">
        <div class="card-header">৯. Adjustment History এবং Approval</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Adjustment History</p>
            <p>Attendance edit/correction approval-based হলে এখানে request review করতে হবে।</p>
            <ol>
                <li>Employee, date এবং old/new value check করুন।</li>
                <li>Reason এবং supporting note verify করুন।</li>
                <li>ঠিক হলে Approve করুন, ভুল হলে Reject করুন।</li>
                <li>Approve হলে daily/monthly summary regenerate হতে পারে।</li>
            </ol>
            <div class="ok-note">Best practice: Payroll generate করার আগে pending adjustment zero করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="reports">
        <div class="card-header">১০. Reports কীভাবে ব্যবহার করবেন?</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Report</th><th>কী দেখাবে</th><th>ব্যবহার</th></tr></thead>
                    <tbody>
                        <tr><td>Daily Report</td><td>একদিনের present/late/in/out</td><td>Daily HR check</td></tr>
                        <tr><td>Monthly Report</td><td>মাসের present, absent, late, overtime summary</td><td>Payroll before check</td></tr>
                        <tr><td>Absent Report</td><td>কে absent ছিল</td><td>Warning/deduction decision</td></tr>
                        <tr><td>Late Report</td><td>কে late করেছে</td><td>Late policy apply</td></tr>
                        <tr><td>Overtime Report</td><td>Overtime minutes/amount basis</td><td>Payroll allowance</td></tr>
                        <tr><td>Leave Report</td><td>Leave application/status</td><td>Leave balance review</td></tr>
                        <tr><td>Employee Master Report</td><td>Employee profile list</td><td>HR audit</td></tr>
                        <tr><td>Department/Branch Report</td><td>Department/branch summary</td><td>Management overview</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">Payroll generate করার আগে Monthly, Absent, Late, Overtime এবং Leave report মিলিয়ে নিন।</div>
        </div>
    </div>

    <div class="card manual-card" id="payroll-setup">
        <div class="card-header">১১. Payroll setup</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup</p>
            <ol>
                <li><strong>Salary Grades:</strong> Grade-wise basic salary range তৈরি করুন। Example: Grade A, Grade B।</li>
                <li><strong>Salary Components:</strong> Allowance বা deduction component তৈরি করুন। Example: House Rent, Medical, Transport, Tax, Loan Deduction।</li>
                <li><strong>Salary Assignments:</strong> Employee-এর salary grade এবং components assign করুন।</li>
                <li><strong>Payrolls:</strong> Month/year দিয়ে draft payroll generate করুন।</li>
                <li><strong>Payroll Report:</strong> generated payroll summary filter/export করুন।</li>
            </ol>
            <div class="warn-note">Salary assignment ছাড়া employee payroll line তৈরি নাও হতে পারে। Payroll generate করার আগে active employee-দের salary assignment check করুন।</div>
        </div>
    </div>

    <div class="card manual-card" id="payroll-run">
        <div class="card-header">১২. Payroll generate থেকে paid পর্যন্ত</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Payrolls</p>
            <ol>
                <li>Year এবং Month দিয়ে <strong>Generate Draft</strong> করুন।</li>
                <li>Draft line-এ present, absent, late, overtime, allowance, deduction এবং net payable check করুন।</li>
                <li>সব ঠিক থাকলে <strong>Approve</strong> করুন।</li>
                <li>Accounting head select করে <strong>Finalize + Post</strong> করুন। এতে salary expense/payable entry তৈরি হবে।</li>
                <li>Salary payment হলে payment type select করে <strong>Mark Paid</strong> করুন।</li>
                <li>ভুল হলে status অনুযায়ী <strong>Void Finalization</strong> বা <strong>Reverse Payment</strong> ব্যবহার করুন।</li>
            </ol>
            <div class="soft-note">
                Payroll status flow: <strong>Draft → Approved → Finalized → Paid</strong>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="examples">
        <div class="card-header">১৩. Practical examples</div>
        <div class="card-body">
            <h6>Example 1: নতুন employee add করে attendance চালু করা</h6>
            <ol>
                <li>Department: Sales, Designation: SR, Branch: Dhaka তৈরি আছে কি না check করুন।</li>
                <li>Employee Profile-এ <code>EMP-025</code> code দিয়ে Rahim add করুন।</li>
                <li>Employee Schedules-এ Rahim-এর জন্য Day Shift assign করুন।</li>
                <li>Manual Entry বা CSV Import দিয়ে attendance দিন।</li>
                <li>Daily Report থেকে Rahim present দেখাচ্ছে কি না verify করুন।</li>
            </ol>

            <h6 class="mt-3">Example 2: Device miss punch correction</h6>
            <ol>
                <li>Manual Entry খুলুন।</li>
                <li>Employee, date, missing punch time এবং punch type select করুন।</li>
                <li>Reason লিখুন: <code>Biometric device missed out punch</code>।</li>
                <li>Save করুন এবং Daily Report refresh করে verify করুন।</li>
            </ol>

            <h6 class="mt-3">Example 3: মাস শেষে salary process</h6>
            <ol>
                <li>Monthly Report থেকে present/absent/late/overtime মিলান।</li>
                <li>Leave Report দেখে approved leave confirm করুন।</li>
                <li>Pending adjustment approve/reject করুন।</li>
                <li>Payrolls থেকে month/year দিয়ে draft generate করুন।</li>
                <li>Net payable ঠিক হলে approve, finalize এবং mark paid করুন।</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card" id="problems">
        <div class="card-header">১৪. Common problems & solution</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Employee report-এ আসছে না</td><td>Profile inactive বা department/branch missing</td><td>Employee Profile update করুন</td></tr>
                        <tr><td>Attendance calculate হচ্ছে না</td><td>Schedule/shift assign নেই</td><td>Employee Schedule assign করুন</td></tr>
                        <tr><td>Late ভুল দেখাচ্ছে</td><td>Shift time বা grace minute ভুল</td><td>Shift setup check করুন</td></tr>
                        <tr><td>Absent বেশি দেখাচ্ছে</td><td>Holiday/leave approve হয়নি</td><td>Holiday Calendar এবং Leave Applications check করুন</td></tr>
                        <tr><td>CSV import failed</td><td>Employee code/date/time format mismatch</td><td>CSV template format মিলিয়ে import করুন</td></tr>
                        <tr><td>Payroll line missing</td><td>Salary assignment নেই</td><td>Salary Assignments add করুন</td></tr>
                        <tr><td>Payroll amount মিলছে না</td><td>Attendance adjustment pending বা component ভুল</td><td>Adjustment History এবং Salary Components verify করুন</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="checklist">
        <div class="card-header">১৫. Daily/monthly checklist</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Daily checklist</h6>
                    <ul>
                        <li>Daily Report দেখে present/absent/late check করুন।</li>
                        <li>Device miss punch থাকলে Manual Entry দিন।</li>
                        <li>Leave application approve/reject করুন।</li>
                        <li>CSV Import failed rows থাকলে fix করুন।</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Monthly payroll checklist</h6>
                    <ul>
                        <li>Holiday calendar update আছে কি না check করুন।</li>
                        <li>Pending adjustment clear করুন।</li>
                        <li>Monthly, Absent, Late, Overtime, Leave report মিলান।</li>
                        <li>Salary assignment এবং components verify করুন।</li>
                        <li>Payroll draft generate করে approve/finalize/paid করুন।</li>
                    </ul>
                </div>
            </div>
            <div class="ok-note">সবচেয়ে গুরুত্বপূর্ণ: Payroll run করার আগে attendance data lock/verify না করলে salary ভুল হতে পারে।</div>
        </div>
    </div>
</div>
@endsection
