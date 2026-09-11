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
            <h2>Fixed Asset Management ব্যবহার নির্দেশিকা</h2>
            <p>সহজভাবে Asset যোগ, Assign, Transfer, Maintenance, Depreciation, Verification ও Disposal করার নিয়ম।</p>
        </div>
        <a href="{{ url('/fixed-assets/manual/en') }}" class="fa-lang-btn">English</a>
    </div>

    <div class="fa-card">
        <h4>১. Fixed Asset কী?</h4>
        <p>Fixed Asset হলো এমন সম্পদ যা ব্যবসায় দীর্ঘদিন ব্যবহার করা হয়, বিক্রির জন্য রাখা হয় না। যেমন: Laptop, AC, Furniture, Printer, Vehicle, Machine।</p>
        <div class="fa-example"><strong>Example:</strong> অফিসে ব্যবহারের জন্য ১টি Dell Laptop কেনা হলো। এটি বিক্রির জন্য নয়, তাই এটি Fixed Asset।</div>
    </div>

    <div class="fa-card">
        <h4>২. সবচেয়ে গুরুত্বপূর্ণ নিয়ম: Warehouse নির্বাচন বাধ্যতামূলক</h4>
        <p>এই system-এ <strong>Warehouse = Branch / Asset Base</strong>। তাই কোনো asset যোগ করার সময় অবশ্যই warehouse select করতে হবে।</p>
        <div class="fa-note"><strong>Note:</strong> Warehouse ছাড়া asset save করা যাবে না। রিপোর্টও warehouse-wise দেখা যাবে।</div>
    </div>

    <div class="fa-card">
        <h4>৩. Asset যোগ করার ধাপ</h4>
        <ol class="fa-steps">
            <li>Sidebar থেকে <strong>Fixed Asset Management → Asset Register → Add New Asset</strong> এ যান।</li>
            <li><strong>Asset Name</strong> লিখুন। যেমন: Dell Laptop, Office AC।</li>
            <li><strong>Category</strong> select করুন। যেমন: IT Equipment, Furniture।</li>
            <li><strong>Warehouse</strong> select করুন। এটি বাধ্যতামূলক।</li>
            <li>Department, Custodian/Employee, Room/Location থাকলে select করুন।</li>
            <li>Purchase Date, Available For Use Date এবং Cost দিন।</li>
            <li>Warranty থাকলে Warranty Start/End Date দিন।</li>
            <li>Invoice, Photo বা Warranty Card upload করুন।</li>
            <li>সব তথ্য check করে <strong>Save</strong> করুন।</li>
        </ol>
        <div class="fa-example"><strong>Example:</strong> Asset Name: Dell Laptop, Category: IT Equipment, Warehouse: Dhaka Office, Employee: Rahim, Cost: 85,000।</div>
    </div>

    <div class="fa-grid">
        <div class="fa-mini"><span class="fa-badge">Assign</span><h5>৪. Asset কাউকে দেওয়া</h5><p>Asset যদি কোনো employee বা department ব্যবহার করে, তাহলে Assign করতে হবে।</p><ol class="fa-steps"><li>Asset details খুলুন।</li><li><strong>Assign Asset</strong> click করুন।</li><li>Employee/Department select করুন।</li><li>Issue date ও condition দিন।</li><li>Save করুন।</li></ol></div>
        <div class="fa-mini"><span class="fa-badge">Return</span><h5>৫. Asset ফেরত নেওয়া</h5><p>Employee asset ফেরত দিলে Return করতে হবে।</p><ol class="fa-steps"><li>Assigned asset খুলুন।</li><li><strong>Return Asset</strong> click করুন।</li><li>Return condition দিন।</li><li>Damage থাকলে note লিখুন।</li><li>Save করুন।</li></ol></div>
    </div>

    <div class="fa-card">
        <h4>৬. Warehouse Transfer</h4>
        <p>Asset এক warehouse থেকে অন্য warehouse-এ গেলে সরাসরি warehouse edit করা যাবে না। Transfer workflow ব্যবহার করতে হবে।</p>
        <ol class="fa-steps"><li><strong>Assignment & Movement → Transfer Asset</strong> এ যান।</li><li>Asset select করুন।</li><li>From Warehouse এবং To Warehouse দিন।</li><li>Transfer note লিখুন।</li><li>Dispatch/Receive complete করুন।</li></ol>
        <div class="fa-example"><strong>Example:</strong> Laptop Dhaka Warehouse থেকে Chattogram Warehouse-এ পাঠানো হলো। Transfer history-তে দুই warehouse-ই থাকবে।</div>
    </div>

    <div class="fa-card">
        <h4>৭. Maintenance / Repair</h4>
        <ol class="fa-steps"><li><strong>Maintenance → Service Request</strong> এ যান।</li><li>Asset select করুন।</li><li>Problem লিখুন। যেমন: Laptop display not working।</li><li>Vendor/Technician select করুন।</li><li>Repair cost এবং document add করুন।</li><li>Repair শেষ হলে <strong>Complete</strong> করুন।</li></ol>
    </div>

    <div class="fa-card">
        <h4>৮. Depreciation</h4>
        <p>Depreciation হলো asset ব্যবহারের কারণে সময়ের সাথে value কমার হিসাব।</p>
        <ol class="fa-steps"><li><strong>Finance → Depreciation Runs</strong> এ যান।</li><li>Month select করুন।</li><li><strong>Preview</strong> করুন।</li><li>Amount check করুন।</li><li>ঠিক থাকলে <strong>Post</strong> করুন।</li></ol>
        <div class="fa-note">Posted depreciation edit করা যাবে না। ভুল হলে Reverse করতে হবে।</div>
    </div>

    <div class="fa-card">
        <h4>৯. Physical Verification</h4>
        <p>নির্দিষ্ট warehouse বা department-এর asset বাস্তবে আছে কিনা check করার জন্য Verification ব্যবহার হবে।</p>
        <ol class="fa-steps"><li><strong>Physical Verification → Verification Sessions</strong> এ যান।</li><li>Warehouse select করুন।</li><li>Session create করুন।</li><li>QR/Barcode scan করুন।</li><li>Found, Missing, Found Elsewhere বা Damaged status দিন।</li><li>শেষে report approve করুন।</li></ol>
    </div>

    <div class="fa-card">
        <h4>১০. Disposal / Write-off</h4>
        <p>Asset বিক্রি, নষ্ট, হারিয়ে যাওয়া বা obsolete হলে Disposal করতে হবে।</p>
        <ol class="fa-steps"><li><strong>Disposal → Disposal Requests</strong> এ যান।</li><li>Asset select করুন।</li><li>Reason দিন। যেমন: Sold, Damaged, Lost।</li><li>Sale value থাকলে দিন।</li><li>Approval complete করুন।</li><li>Disposal complete করুন।</li></ol>
        <div class="fa-note">Disposed asset আর assign বা transfer করা যাবে না।</div>
    </div>

    <div class="fa-card">
        <h4>১১. Reports</h4>
        <table class="fa-table"><thead><tr><th>Report</th><th>কাজ</th></tr></thead><tbody><tr><td>Warehouse-wise Asset Summary</td><td>কোন warehouse-এ কত asset আছে</td></tr><tr><td>Book Value Report</td><td>Asset-এর বর্তমান হিসাবি value</td></tr><tr><td>Maintenance Report</td><td>কোন asset repair হয়েছে এবং খরচ কত</td></tr><tr><td>Depreciation Report</td><td>মাসভিত্তিক depreciation</td></tr><tr><td>Disposal Report</td><td>বিক্রি/নষ্ট/হারানো asset history</td></tr></tbody></table>
    </div>
</div>
</div>
@endsection
