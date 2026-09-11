@extends('backend.master')
@section('page_title', 'Payroll Manual - English')
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
            <p class="mb-0">A focused guide for salary grades, components, assignments, payroll generation, accounting, and payment.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('hrat.payroll-manual.bn') }}" class="btn btn-light btn-sm">বাংলা Manual</a>
            <a href="{{ route('hrat.payrolls.index') }}" class="btn btn-outline-light btn-sm">Payrolls</a>
        </div>
    </div>

    <div class="card manual-card" id="index">
        <div class="card-header">Index</div>
        <div class="card-body">
            <div class="row index-list">
                <div class="col-md-4">
                    <a href="#overview">1. Overview</a>
                    <a href="#before-start">2. Before payroll</a>
                    <a href="#salary-grades">3. Salary grades</a>
                    <a href="#salary-components">4. Salary components</a>
                </div>
                <div class="col-md-4">
                    <a href="#assignments">5. Salary assignments</a>
                    <a href="#calculation">6. Calculation formula</a>
                    <a href="#generate">7. Generate draft</a>
                    <a href="#review">8. Review checklist</a>
                </div>
                <div class="col-md-4">
                    <a href="#approve-finalize">9. Approve/finalize/paid</a>
                    <a href="#reverse">10. Corrections</a>
                    <a href="#examples">11. Examples</a>
                    <a href="#problems">12. Common problems</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card manual-card" id="overview">
        <div class="card-header">1. Payroll module overview</div>
        <div class="card-body">
            <p>The payroll module manages employee salary packages, monthly salary calculation, payslips, accounting accrual, and salary payment posting.</p>
            <div class="soft-note"><strong>Flow:</strong> Salary Grade → Salary Component → Salary Assignment → Attendance Review → Generate Draft → Review → Approve → Finalize + Post → Mark Paid.</div>
            <ul class="mt-3">
                <li><strong>Salary Grades:</strong> reference categories for salary structures.</li>
                <li><strong>Salary Components:</strong> allowance or deduction items.</li>
                <li><strong>Salary Assignments:</strong> the actual employee salary package.</li>
                <li><strong>Payrolls:</strong> monthly generation, approval, accounting finalization, and payment.</li>
                <li><strong>Payroll Report:</strong> payroll summary, filtering, and export.</li>
            </ul>
        </div>
    </div>

    <div class="card manual-card" id="before-start">
        <div class="card-header">2. Before running payroll</div>
        <div class="card-body">
            <ol>
                <li>Employee profiles must be active.</li>
                <li>Attendance for the payroll month must be complete and verified.</li>
                <li>Pending attendance adjustments should be cleared.</li>
                <li>Holiday, leave, absent, late, and overtime reports should be reviewed.</li>
                <li>Salary grades and salary components must be configured.</li>
                <li>Each employee must have an active salary assignment with an effective date range covering the payroll month.</li>
                <li>Finalize requires salary expense and salary payable accounts.</li>
                <li>Mark paid requires enough balance in the selected payment type/account.</li>
            </ol>
            <div class="warn-note">Verify attendance before generating payroll. Once a draft is approved, it cannot be regenerated.</div>
        </div>
    </div>

    <div class="card manual-card" id="salary-grades">
        <div class="card-header">3. Salary grades</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Grades</p>
            <p>Use salary grades to organize salary structures, such as Sales Grade, Office Staff, or Management.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Grade Name</td><td>Salary category name</td><td>Grade A, Grade B, Sales Grade</td></tr>
                        <tr><td>Minimum Salary</td><td>Minimum range for this grade</td><td>15000</td></tr>
                        <tr><td>Maximum Salary</td><td>Maximum range for this grade</td><td>30000</td></tr>
                        <tr><td>Status</td><td>Active grades can be used in assignments</td><td>Active</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Tip: keep grade names simple, such as <code>Office Staff</code>, <code>Sales Team</code>, or <code>Management</code>.</div>
        </div>
    </div>

    <div class="card manual-card" id="salary-components">
        <div class="card-header">4. Salary components</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Components</p>
            <p>Components are allowances or deductions outside the basic salary. Examples: House Rent, Medical, Transport, Tax, Loan Deduction.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Field</th><th>Meaning</th><th>Example</th></tr></thead>
                    <tbody>
                        <tr><td>Name</td><td>Component name</td><td>House Rent, Medical, Transport, Tax</td></tr>
                        <tr><td>Code</td><td>Short unique code</td><td>HR, MED, TAX</td></tr>
                        <tr><td>Type</td><td>Allowance or Deduction</td><td>Allowance</td></tr>
                        <tr><td>Calculation Type</td><td>Fixed or Percentage</td><td>Fixed</td></tr>
                        <tr><td>Default Amount</td><td>Default value</td><td>3000</td></tr>
                        <tr><td>Taxable</td><td>Whether it should be considered for tax calculation</td><td>No</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">Allowance increases salary. Deduction decreases salary.</div>
        </div>
    </div>

    <div class="card manual-card" id="assignments">
        <div class="card-header">5. Salary assignments</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Salary Assignments</p>
            <p>Salary assignment defines an employee's grade, basic salary, effective date range, and package components.</p>
            <ol>
                <li>Select the employee.</li>
                <li>Select the salary grade.</li>
                <li>Enter the basic salary.</li>
                <li>Enter the effective from date.</li>
                <li>Use effective to only when the salary package is temporary or ending.</li>
                <li>After saving the assignment, add package components for allowance or deduction.</li>
            </ol>
            <div class="warn-note">Do not overlap active assignments for the same employee. For increments, end the old assignment and start a new one.</div>
        </div>
    </div>

    <div class="card manual-card" id="calculation">
        <div class="card-header">6. Payroll calculation formula</div>
        <div class="card-body">
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
        <div class="card-header">7. Generate draft payroll</div>
        <div class="card-body">
            <p><strong>Menu:</strong> HRM Management → HR Payroll Setup → Payrolls</p>
            <ol>
                <li>Enter year and month.</li>
                <li>Click Generate Draft.</li>
                <li>The system creates employee payroll lines from active salary assignments and attendance summaries.</li>
                <li>A draft payroll can be regenerated. Approved, finalized, or paid payrolls cannot be regenerated.</li>
            </ol>
            <div class="ok-note">Draft is the review stage. If attendance or salary setup is wrong, correct it and generate the draft again before approval.</div>
        </div>
    </div>

    <div class="card manual-card" id="review">
        <div class="card-header">8. Draft review checklist</div>
        <div class="card-body">
            <ul>
                <li>All expected employees are present in the payroll.</li>
                <li>Basic salary, allowances, and deductions are correct.</li>
                <li>Present, absent, late, and overtime values are correct.</li>
                <li>Absent, late, and unpaid leave deductions follow company policy.</li>
                <li>Net payable matches internal expectations.</li>
                <li>Payslips are reviewed employee by employee.</li>
            </ul>
            <div class="warn-note">Find mistakes before approval. After approval, correction depends on the payroll status and may require void/reversal actions.</div>
        </div>
    </div>

    <div class="card manual-card" id="approve-finalize">
        <div class="card-header">9. Approve, finalize, and paid</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Status</th><th>Action</th><th>Meaning</th></tr></thead>
                    <tbody>
                        <tr><td>Draft</td><td>Approve</td><td>Payroll has been checked and approved for accounting</td></tr>
                        <tr><td>Approved</td><td>Finalize + Post</td><td>Salary expense/payable accounting entry is posted</td></tr>
                        <tr><td>Finalized</td><td>Mark Paid</td><td>Salary payment accounting entry is posted using payment type</td></tr>
                        <tr><td>Paid</td><td>Done</td><td>Payroll is complete</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="soft-note">
                Finalize entry: <strong>Salary Expense Dr, Salary Payable Cr</strong><br>
                Payment entry: <strong>Salary Payable Dr, Cash/Bank/Payment Account Cr</strong>
            </div>
            <div class="warn-note mt-3">If the selected payment type/account does not have enough balance, Mark Paid will not post.</div>
        </div>
    </div>

    <div class="card manual-card" id="reverse">
        <div class="card-header">10. Corrections</div>
        <div class="card-body">
            <ul>
                <li><strong>Draft:</strong> fix setup or attendance and regenerate.</li>
                <li><strong>Approved:</strong> accounting is not posted yet; hold the process and recheck the data if needed.</li>
                <li><strong>Finalized:</strong> use Void Finalization to reverse accrual and return to Approved.</li>
                <li><strong>Paid:</strong> use Reverse Payment first; then void finalization if needed.</li>
            </ul>
            <div class="warn-note">Do not directly edit/delete paid payroll. Use the reversal flow for accounting consistency.</div>
        </div>
    </div>

    <div class="card manual-card" id="examples">
        <div class="card-header">11. Practical examples</div>
        <div class="card-body">
            <h6>New employee salary setup</h6>
            <ol>
                <li>Create a salary grade, such as <code>Sales Grade</code>.</li>
                <li>Create components such as House Rent allowance, Mobile Bill allowance, and Loan deduction.</li>
                <li>Create a salary assignment with employee, basic salary, and effective date.</li>
                <li>Add package components to the assignment.</li>
                <li>Generate payroll and verify the employee line.</li>
            </ol>
            <h6 class="mt-3">Salary increment</h6>
            <ol>
                <li>Set the old assignment's Effective To date, for example <code>2026-06-30</code>.</li>
                <li>Create a new assignment with Effective From <code>2026-07-01</code>.</li>
                <li>Set the new basic salary and components.</li>
                <li>Generate July payroll; the new salary should be used.</li>
            </ol>
            <h6 class="mt-3">Month-end payroll</h6>
            <ol>
                <li>Verify Monthly, Absent, Late, Overtime, and Leave reports.</li>
                <li>Clear pending adjustments.</li>
                <li>Generate draft payroll.</li>
                <li>Review employee-wise payslip and net payable.</li>
                <li>Approve, finalize, and mark paid.</li>
            </ol>
        </div>
    </div>

    <div class="card manual-card" id="problems">
        <div class="card-header">12. Common problems</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Problem</th><th>Reason</th><th>Solution</th></tr></thead>
                    <tbody>
                        <tr><td>Employee missing</td><td>No active salary assignment</td><td>Add active assignment</td></tr>
                        <tr><td>Old salary used</td><td>Wrong effective dates</td><td>Fix assignment date range</td></tr>
                        <tr><td>Absence deduction too high</td><td>Leave/holiday/attendance not updated</td><td>Verify reports and regenerate draft</td></tr>
                        <tr><td>Cannot regenerate</td><td>Payroll is not draft</td><td>Only draft payroll can be regenerated</td></tr>
                        <tr><td>Cannot finalize</td><td>Invalid accounts or zero total</td><td>Check salary expense/payable accounts and total</td></tr>
                        <tr><td>Cannot mark paid</td><td>Insufficient payment balance</td><td>Check selected payment account</td></tr>
                        <tr><td>Overtime missing</td><td>Attendance summary or shift overtime rule is missing</td><td>Check shift setup and overtime report</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-note">Best practice: HR, Accounts, and the manager/owner should review the payroll summary before approval.</div>
        </div>
    </div>
</div>
@endsection
