@extends('backend.master')
@section('page_title', 'HRM Manual - English')
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
            <p class="mb-0">A step-by-step guide for setting up employees, attendance, leave, reports, and payroll.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('hrat.manual.bn') }}" class="btn btn-light btn-sm">বাংলা Manual</a>
            <a href="{{ route('hrat.dashboard') }}" class="btn btn-outline-light btn-sm">HR Dashboard</a>
        </div>
    </div>

    <div class="card manual-card" id="index">
        <div class="card-header">Index</div>
        <div class="card-body">
            <div class="row index-list">
                <div class="col-md-4">
                    <a href="#overview">1. Module overview</a>
                    <a href="#setup-flow">2. Initial setup flow</a>
                    <a href="#masters">3. Departments, designations, branches</a>
                    <a href="#shift">4. Shift setup</a>
                    <a href="#holiday-leave">5. Holidays and leave</a>
                </div>
                <div class="col-md-4">
                    <a href="#employee">6. Employee profiles</a>
                    <a href="#schedule">7. Employee schedules</a>
                    <a href="#attendance">8. Attendance entry/import</a>
                    <a href="#adjustment">9. Adjustment approval</a>
                    <a href="#reports">10. Reports</a>
                </div>
                <div class="col-md-4">
                    <a href="#payroll-setup">11. Payroll setup</a>
                    <a href="#payroll-run">12. Payroll run</a>
                    <a href="#examples">13. Practical examples</a>
                    <a href="#problems">14. Common problems</a>
                    <a href="#checklist">15. Checklist</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="overview">
        <div class="card-header">1. What does this module do?</div>
        <div class="card-body">
            <p>The HRM module manages employee master data, attendance, leave, shifts, overtime, reports, and monthly payroll from one place.</p>
            <div class="soft-note"><strong>Flow:</strong> Master setup → Employee profile → Schedule assignment → Attendance entry/import → Report review → Payroll generation → Approval → Accounting finalization → Payment.</div>
            <ul class="mt-3">
                <li><strong>Attendance:</strong> daily in/out punches, late, early exit, overtime, and absence tracking.</li>
                <li><strong>Leave:</strong> leave types, applications, approve/reject/cancel actions, and leave reports.</li>
                <li><strong>Payroll:</strong> salary grades, salary components, employee assignments, payroll generation, and payment.</li>
                <li><strong>Reports:</strong> daily, monthly, absent, late, overtime, leave, employee master, and department/branch summaries.</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card" id="setup-flow">
        <div class="card-header">2. Initial setup flow</div>
        <div class="card-body">
            <ol>
                <li>Create departments, designations, and branches.</li>
                <li>Create shifts with start time, end time, grace minutes, and overtime rules.</li>
                <li>Configure holiday calendar and leave types.</li>
                <li>Create employee profiles with employee code, department, branch, joining date, and manager.</li>
                <li>Assign shifts from Employee Schedules.</li>
                <li>Verify attendance configuration.</li>
                <li>Enter attendance manually or import it by CSV.</li>
                <li>At month end, review reports and generate payroll.</li>
            </ol>
            <div class="warn-note">Reports and payroll can be wrong if employee profiles or schedules are missing.</div>
        </div>
    </div>

    <div class="card manual-card" id="masters">
        <div class="card-header">3. Departments, designations, and branches</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Departments / Designations / Branches</p>
            <p>Use these records to group employees and filter attendance/payroll reports correctly.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Item</th><th>Purpose</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Department</td><td>The team or unit where the employee works</td><td>Sales, Accounts, Warehouse</td></tr>
                        <tr><td>Designation</td><td>The employee's job title</td><td>SR, Manager, Accountant</td></tr>
                        <tr><td>Branch</td><td>The employee's work location</td><td>Dhaka Office, Chittagong Branch</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Tip: keep names consistent. Do not create both <code>Sales</code> and <code>sales team</code> if they mean the same department.</div>
        </div>
    </div>

    <div class="card manual-card" id="shift">
        <div class="card-header">4. Shift setup</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Shifts</p>
            <ul>
                <li><strong>Start Time:</strong> office start time, for example <code>09:00 AM</code>.</li>
                <li><strong>End Time:</strong> office end time, for example <code>06:00 PM</code>.</li>
                <li><strong>Grace Minutes:</strong> allowed delay before marking late.</li>
                <li><strong>Overtime Rule:</strong> when overtime starts after shift end.</li>
                <li><strong>Working Days:</strong> selected workdays for the shift.</li>
            </ul>
            <div class="soft-note">Example: if the shift is 09:00-18:00 with 10 grace minutes, 09:10 is on time and 09:11 is late.</div>
        </div>
    </div>

    <div class="card manual-card" id="holiday-leave">
        <div class="card-header">5. Holiday calendar and leave</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Holiday Calendar / Leave Types / Leave Applications</p>
            <ul>
                <li><strong>Holiday Calendar:</strong> add public holidays, weekly holidays, or company-specific holidays.</li>
                <li><strong>Leave Types:</strong> create leave categories such as Casual, Sick, Earned, or Unpaid leave.</li>
                <li><strong>Leave Applications:</strong> approve, reject, or cancel employee leave requests.</li>
            </ul>
            <div class="warn-note">Update holidays and approve leave before payroll so valid leave is not counted as ordinary absence.</div>
        </div>
    </div>

    <div class="card manual-card" id="employee">
        <div class="card-header">6. Employee profiles</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Employee Profiles</p>
            <p>Employee profiles are the base record for attendance, leave, and payroll. Keep employee code, department, branch, joining date, manager, and status accurate.</p>
            <ul>
                <li><strong>User:</strong> select the system user if the employee already has one.</li>
                <li><strong>Employee Code:</strong> use a unique code such as <code>EMP-001</code>.</li>
                <li><strong>Department/Designation/Branch:</strong> required for filtering and grouped reports.</li>
                <li><strong>Joining Date:</strong> useful for payroll and leave eligibility.</li>
                <li><strong>Status:</strong> inactive employees may be excluded from attendance or payroll processing.</li>
            </ul>
            <div class="ok-note">Use a unique employee code such as <code>EMP-001</code>; CSV import may depend on matching this code.</div>
        </div>
    </div>

    <div class="card manual-card" id="schedule">
        <div class="card-header">7. Employee schedules</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Employee Schedules</p>
            <p>Assign each employee to the correct shift. When a shift changes, add a new schedule from the effective date instead of overwriting past history.</p>
            <ol>
                <li>Select the employee.</li>
                <li>Select the shift.</li>
                <li>Enter the effective date or date range.</li>
                <li>Save and verify the calculation in the monthly attendance report.</li>
            </ol>
            <div class="soft-note">Example: if Rahim moves from Day Shift to Evening Shift from July 1, add a new schedule from July 1 instead of editing old attendance history.</div>
        </div>
    </div>

    <div class="card manual-card" id="attendance">
        <div class="card-header">8. Attendance entry/import</div>
        <div class="card-body">
            <p><strong>Manual Entry:</strong> use this for missing device punches, field visit corrections, or old attendance corrections.</p>
            <p><strong>CSV Import:</strong> use this when you export daily or monthly attendance from a biometric/device system.</p>
            <h6>When should you use Manual Entry?</h6>
            <ul>
                <li>The biometric/device punch was missed.</li>
                <li>The employee worked outside the office and HR needs to record the time.</li>
                <li>An old attendance record needs a correction.</li>
            </ul>
            <h6 class="mt-3">When should you use CSV Import?</h6>
            <ul>
                <li>A device or biometric system exports daily/monthly attendance data.</li>
                <li>Many employee attendance records need to be imported together.</li>
            </ul>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Employee</td><td>Employee being recorded</td><td>Rahim Uddin</td></tr>
                        <tr><td>Date</td><td>Attendance date</td><td>2026-06-07</td></tr>
                        <tr><td>Time</td><td>Punch time</td><td>09:05 AM</td></tr>
                        <tr><td>Punch Type</td><td>In or Out</td><td>In</td></tr>
                        <tr><td>Reason</td><td>Why this is manual</td><td>Device missed punch</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="warn-note">Manual attendance is audit-sensitive. Always keep a clear reason.</div>
        </div>
    </div>

    <div class="card manual-card" id="adjustment">
        <div class="card-header">9. Adjustment approval</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → Attendance → Adjustment History</p>
            <p>Review the employee, date, old value, new value, and reason. Approve valid corrections and reject incorrect requests.</p>
            <ol>
                <li>Check the employee, attendance date, and old/new values.</li>
                <li>Review the reason or supporting note.</li>
                <li>Approve if the correction is valid, or reject it if it is not.</li>
                <li>After approval, the daily/monthly summary may be regenerated.</li>
            </ol>
            <div class="ok-note">Clear pending adjustments before generating payroll.</div>
        </div>
    </div>

    <div class="card manual-card" id="reports">
        <div class="card-header">10. Reports</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Report</th><th>What it shows</th><th>Best use</th></tr></thead>
                    <tbody>
                        <tr><td>Daily Report</td><td>Present, late, in/out data for one day</td><td>Daily HR checking</td></tr>
                        <tr><td>Monthly Report</td><td>Present, absent, late, and overtime summary for a month</td><td>Before payroll review</td></tr>
                        <tr><td>Absent Report</td><td>Employees marked absent</td><td>Warning or deduction decisions</td></tr>
                        <tr><td>Late Report</td><td>Employees who were late</td><td>Late policy application</td></tr>
                        <tr><td>Overtime Report</td><td>Overtime minutes and amount basis</td><td>Payroll allowance review</td></tr>
                        <tr><td>Leave Report</td><td>Leave applications and statuses</td><td>Leave balance review</td></tr>
                        <tr><td>Employee Master Report</td><td>Employee profile list</td><td>HR audit</td></tr>
                        <tr><td>Department/Branch Report</td><td>Grouped summary by department or branch</td><td>Management overview</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">Before payroll, compare Monthly, Absent, Late, Overtime, and Leave reports.</div>
        </div>
    </div>

    <div class="card manual-card" id="payroll-setup">
        <div class="card-header">11. Payroll setup</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup</p>
            <ol>
                <li>Create salary grades.</li>
                <li>Create salary components such as house rent, medical, transport, tax, or loan deduction.</li>
                <li>Assign salary grade and components to employees.</li>
                <li>Use Payrolls to generate monthly payroll.</li>
                <li>Use Payroll Report for summary and export.</li>
            </ol>
            <div class="warn-note">An employee may not appear in payroll if there is no active salary assignment for the selected payroll month.</div>
        </div>
    </div>

    <div class="card manual-card" id="payroll-run">
        <div class="card-header">12. Payroll run</div>
        <div class="card-body">
            <ol>
                <li>Generate draft payroll by year and month.</li>
                <li>Review attendance, overtime, deductions, allowances, and net payable.</li>
                <li>Approve the draft.</li>
                <li>Select accounting heads and finalize to post salary expense/payable entries.</li>
                <li>When salary is paid, select payment type and mark paid.</li>
                <li>If needed, use Void Finalization or Reverse Payment according to the status.</li>
            </ol>
            <div class="soft-note">Payroll status flow: <strong>Draft → Approved → Finalized → Paid</strong></div>
        </div>
    </div>

    <div class="card manual-card" id="examples">
        <div class="card-header">13. Practical examples</div>
        <div class="card-body">
            <h6>Example 1: New employee attendance</h6>
            <ol>
                <li>Make sure Department, Designation, and Branch records already exist.</li>
                <li>Create the employee profile with a code such as <code>EMP-025</code>.</li>
                <li>Assign a shift from Employee Schedules.</li>
                <li>Enter attendance manually or import it by CSV.</li>
                <li>Verify the employee in the Daily Report.</li>
            </ol>
            <h6 class="mt-3">Example 2: Missing device punch</h6>
            <ol>
                <li>Open Manual Entry.</li>
                <li>Select employee, date, missing punch time, and punch type.</li>
                <li>Enter a reason such as <code>Biometric device missed out punch</code>.</li>
                <li>Save and verify the Daily Report.</li>
            </ol>
            <h6 class="mt-3">Example 3: Month-end salary</h6>
            <ol>
                <li>Review Monthly, Absent, Late, Overtime, and Leave reports.</li>
                <li>Clear pending attendance adjustments.</li>
                <li>Generate draft payroll for the month.</li>
                <li>Review employee-wise net payable.</li>
                <li>Approve, finalize, and mark paid.</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card" id="problems">
        <div class="card-header">14. Common problems & solutions</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Employee missing from report</td><td>Inactive profile or missing branch/department</td><td>Update Employee Profile</td></tr>
                        <tr><td>Attendance not calculated</td><td>No schedule or shift</td><td>Assign Employee Schedule</td></tr>
                        <tr><td>Late count looks wrong</td><td>Wrong shift time or grace minutes</td><td>Check Shift setup</td></tr>
                        <tr><td>Too many absents</td><td>Holiday or leave not approved</td><td>Check Holiday Calendar and Leave Applications</td></tr>
                        <tr><td>CSV import failed</td><td>Employee code/date/time format mismatch</td><td>Fix CSV format</td></tr>
                        <tr><td>Payroll line missing</td><td>No salary assignment</td><td>Add Salary Assignment</td></tr>
                        <tr><td>Payroll amount mismatch</td><td>Pending adjustment or wrong salary component</td><td>Verify Adjustment History and Salary Components</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="checklist">
        <div class="card-header">15. Daily/monthly checklist</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Daily checklist</h6>
                    <ul>
                        <li>Review the Daily Report for present, absent, and late employees.</li>
                        <li>Add Manual Entry for missing device punches.</li>
                        <li>Approve or reject leave applications.</li>
                        <li>Resolve failed CSV import rows.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Monthly payroll checklist</h6>
                    <ul>
                        <li>Update the holiday calendar.</li>
                        <li>Clear pending attendance adjustments.</li>
                        <li>Review Monthly, Absent, Late, Overtime, and Leave reports.</li>
                        <li>Verify salary assignments and components.</li>
                        <li>Generate, approve, finalize, and mark payroll paid.</li>
                    </ul>
                </div>
            </div>
            <div class="ok-note">Most important: verify attendance data before payroll. Incorrect attendance usually creates incorrect salary.</div>
        </div>
    </div>
</div>
@endsection
