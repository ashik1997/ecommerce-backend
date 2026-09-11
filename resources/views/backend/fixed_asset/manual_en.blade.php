@extends('backend.master')

@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
<style>
    .fa-manual-wrap{max-width:1120px;margin:0 auto;padding:22px;color:#1f2937}
    .fa-manual-header{display:flex;justify-content:space-between;gap:15px;align-items:center;background:linear-gradient(135deg,#0f766e,#115e59);color:#fff;padding:26px;border-radius:18px;margin-bottom:20px;box-shadow:0 10px 28px rgba(15,118,110,.22)}
    .fa-manual-header h2{margin:0;font-weight:800;font-size:28px}.fa-manual-header p{margin:6px 0 0;opacity:.94}
    .fa-lang-btn{background:#fff;color:#0f766e;padding:10px 16px;border-radius:999px;text-decoration:none;font-weight:800;white-space:nowrap}.fa-lang-btn:hover{color:#115e59;text-decoration:none}
    .fa-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 4px 14px rgba(15,118,110,.07)}
    .fa-card h4{color:#0f766e;margin-bottom:10px;font-weight:800}.fa-card h5{font-weight:800;margin-top:14px;color:#334155}
    .fa-steps{margin:0;padding-left:22px}.fa-steps li{margin-bottom:8px;line-height:1.75}.fa-steps strong{color:#0f766e}
    .fa-example{background:#f0fdfa;border-left:4px solid #0f766e;padding:13px 14px;border-radius:9px;margin-top:10px;line-height:1.7}
    .fa-note{background:#fff7ed;border-left:4px solid #f97316;padding:13px 14px;border-radius:9px;line-height:1.7}
    .fa-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.fa-mini{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px}
    .fa-badge{display:inline-block;background:#ccfbf1;color:#115e59;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:800;margin-bottom:8px}
    .fa-table{width:100%;border-collapse:collapse;margin-top:10px}.fa-table th,.fa-table td{border:1px solid #e5e7eb;padding:10px;text-align:left;vertical-align:top}.fa-table th{background:#f0fdfa;color:#115e59}
    @media(max-width:768px){.fa-manual-header{flex-direction:column;align-items:flex-start}.fa-grid{grid-template-columns:1fr}.fa-manual-wrap{padding:12px}.fa-manual-header h2{font-size:22px}}
</style>

<div class="fa-manual-wrap">
    <div class="fa-manual-header">
        <div>
            <h2>Fixed Asset Management User Manual</h2>
            <p>A simple step-by-step guide to add, assign, transfer, maintain, depreciate, verify and dispose assets.</p>
        </div>
        <a href="{{ url('/fixed-assets/manual/bn') }}" class="fa-lang-btn">বাংলা</a>
    </div>

    <div class="fa-card"><h4>1. What is a Fixed Asset?</h4><p>A fixed asset is a long-term business asset used for operations, not for resale. Examples: Laptop, AC, Furniture, Printer, Vehicle, Machine.</p><div class="fa-example"><strong>Example:</strong> A Dell Laptop purchased for office work is a fixed asset because it will be used internally.</div></div>

    <div class="fa-card"><h4>2. Most Important Rule: Warehouse is Required</h4><p>In this system, <strong>Warehouse = Branch / Asset Base</strong>. Every asset must be linked with a warehouse.</p><div class="fa-note"><strong>Note:</strong> An asset cannot be saved without selecting a warehouse. Reports will also be shown warehouse-wise.</div></div>

    <div class="fa-card"><h4>3. How to Add a New Asset</h4><ol class="fa-steps"><li>Go to <strong>Fixed Asset Management → Asset Register → Add New Asset</strong>.</li><li>Enter <strong>Asset Name</strong>, such as Dell Laptop or Office AC.</li><li>Select <strong>Category</strong>, such as IT Equipment or Furniture.</li><li>Select <strong>Warehouse</strong>. This is mandatory.</li><li>Select Department, Custodian/Employee and Room/Location if needed.</li><li>Enter Purchase Date, Available For Use Date and Cost.</li><li>Add Warranty Start/End Date if applicable.</li><li>Upload invoice, photo or warranty card.</li><li>Review everything and click <strong>Save</strong>.</li></ol><div class="fa-example"><strong>Example:</strong> Asset Name: Dell Laptop, Category: IT Equipment, Warehouse: Dhaka Office, Employee: Rahim, Cost: 85,000.</div></div>

    <div class="fa-grid"><div class="fa-mini"><span class="fa-badge">Assign</span><h5>4. Assign an Asset</h5><p>Use assignment when an employee or department receives an asset.</p><ol class="fa-steps"><li>Open asset details.</li><li>Click <strong>Assign Asset</strong>.</li><li>Select employee or department.</li><li>Enter issue date and condition.</li><li>Save.</li></ol></div><div class="fa-mini"><span class="fa-badge">Return</span><h5>5. Return an Asset</h5><p>Use return when an employee gives back an assigned asset.</p><ol class="fa-steps"><li>Open the assigned asset.</li><li>Click <strong>Return Asset</strong>.</li><li>Enter return condition.</li><li>Add note if damaged.</li><li>Save.</li></ol></div></div>

    <div class="fa-card"><h4>6. Warehouse Transfer</h4><p>Do not directly edit the warehouse. Use the transfer workflow when an asset moves from one warehouse to another.</p><ol class="fa-steps"><li>Go to <strong>Assignment & Movement → Transfer Asset</strong>.</li><li>Select the asset.</li><li>Choose From Warehouse and To Warehouse.</li><li>Add transfer note.</li><li>Complete dispatch and receive.</li></ol><div class="fa-example"><strong>Example:</strong> A laptop is moved from Dhaka Warehouse to Chattogram Warehouse. Both warehouses will remain in movement history.</div></div>

    <div class="fa-card"><h4>7. Maintenance / Repair</h4><ol class="fa-steps"><li>Go to <strong>Maintenance → Service Request</strong>.</li><li>Select the asset.</li><li>Write the problem, such as Laptop display not working.</li><li>Select vendor or technician.</li><li>Add repair cost and documents.</li><li>Click <strong>Complete</strong> when repair is done.</li></ol></div>

    <div class="fa-card"><h4>8. Depreciation</h4><p>Depreciation records the reduction of asset value over time.</p><ol class="fa-steps"><li>Go to <strong>Finance → Depreciation Runs</strong>.</li><li>Select the month.</li><li>Click <strong>Preview</strong>.</li><li>Check the amount.</li><li>If correct, click <strong>Post</strong>.</li></ol><div class="fa-note">Posted depreciation cannot be edited. If there is a mistake, reverse it.</div></div>

    <div class="fa-card"><h4>9. Physical Verification</h4><p>Verification checks whether assets physically exist in the selected warehouse or department.</p><ol class="fa-steps"><li>Go to <strong>Physical Verification → Verification Sessions</strong>.</li><li>Select warehouse.</li><li>Create session.</li><li>Scan QR/Barcode.</li><li>Mark as Found, Missing, Found Elsewhere or Damaged.</li><li>Approve the final report.</li></ol></div>

    <div class="fa-card"><h4>10. Disposal / Write-off</h4><p>Use disposal when an asset is sold, damaged, lost or obsolete.</p><ol class="fa-steps"><li>Go to <strong>Disposal → Disposal Requests</strong>.</li><li>Select the asset.</li><li>Choose reason: Sold, Damaged, Lost, etc.</li><li>Enter sale value if applicable.</li><li>Complete approval.</li><li>Complete disposal.</li></ol><div class="fa-note">Disposed assets cannot be assigned or transferred again.</div></div>

    <div class="fa-card"><h4>11. Reports</h4><table class="fa-table"><thead><tr><th>Report</th><th>Purpose</th></tr></thead><tbody><tr><td>Warehouse-wise Asset Summary</td><td>Shows how many assets each warehouse has</td></tr><tr><td>Book Value Report</td><td>Shows current accounting value</td></tr><tr><td>Maintenance Report</td><td>Shows repair history and cost</td></tr><tr><td>Depreciation Report</td><td>Shows monthly depreciation</td></tr><tr><td>Disposal Report</td><td>Shows sold, damaged or lost asset history</td></tr></tbody></table></div>
</div>
</div>
@endsection
