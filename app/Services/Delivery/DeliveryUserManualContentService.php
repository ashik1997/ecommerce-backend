<?php

namespace App\Services\Delivery;

class DeliveryUserManualContentService
{
    public function forLocale(string $locale): array
    {
        $locale = $locale === 'en' ? 'en' : 'bn';
        $isBn = $locale === 'bn';

        return [
            'locale' => $locale,
            'switch_locale' => $isBn ? 'en' : 'bn',
            'page_title' => $isBn ? 'Delivery Management ব্যবহার নির্দেশিকা' : 'Delivery Management User Manual',
            'hero_title' => $isBn ? 'Delivery Management ব্যবহার নির্দেশিকা' : 'Delivery Management User Manual',
            'hero_subtitle' => $isBn
                ? 'Provider, local delivery, courier API, shipment, COD, settlement এবং reports কীভাবে ব্যবহার করবেন তার ধাপে ধাপে guide।'
                : 'A practical guide for providers, local delivery, courier APIs, shipments, COD, settlements, and reports.',
            'hero_note' => $isBn
                ? 'এই manual read-only। এখানে কোনো shipment, order, stock বা accounting update হয় না।'
                : 'This manual is read-only. It does not update shipments, orders, stock, or accounting.',
            'labels' => $this->labels($locale),
            'quick_start' => $this->quickStart($locale),
            'daily_checklist' => $this->dailyChecklist($locale),
            'quick_links' => $this->quickLinks($locale),
            'sections' => $isBn ? $this->sectionsBn() : $this->sectionsEn(),
        ];
    }

    private function labels(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'switch_text' => 'English Version',
                'section_count' => 'Tutorial sections',
                'language' => 'Language',
                'index_title' => 'বিষয়ভিত্তিক সূচি',
                'index_help' => 'Search করুন অথবা index থেকে section খুলুন।',
                'search_placeholder' => 'যেমন: provider, shipment, COD, settlement',
                'daily_checklist_title' => 'Daily checklist',
                'menu_path' => 'Menu path',
                'why' => 'এখানে কী হবে?',
                'steps' => 'ধাপে ধাপে ব্যবহার',
                'example' => 'সহজ উদাহরণ',
                'expected_result' => 'Expected result',
                'tips' => 'টিপস',
                'warning' => 'সতর্কতা',
                'troubleshooting' => 'সমস্যা হলে',
                'problem' => 'সমস্যা',
                'reason' => 'সম্ভাব্য কারণ',
                'solution' => 'করণীয়',
                'no_match' => 'কোনো matching topic পাওয়া যায়নি। অন্য keyword দিয়ে search করুন।',
            ];
        }

        return [
            'switch_text' => 'বাংলা সংস্করণ',
            'section_count' => 'Tutorial sections',
            'language' => 'Language',
            'index_title' => 'Topic index',
            'index_help' => 'Search by topic or open a section from the index.',
            'search_placeholder' => 'Example: provider, shipment, COD, settlement',
            'daily_checklist_title' => 'Daily checklist',
            'menu_path' => 'Menu path',
            'why' => 'What is this for?',
            'steps' => 'Step-by-step tutorial',
            'example' => 'Simple example',
            'expected_result' => 'Expected result',
            'tips' => 'Tips',
            'warning' => 'Warning',
            'troubleshooting' => 'Troubleshooting',
            'problem' => 'Problem',
            'reason' => 'Possible reason',
            'solution' => 'What to do',
            'no_match' => 'No matching topic was found. Try another keyword.',
        ];
    }

    private function quickStart(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                ['icon' => 'feather-navigation', 'title' => 'Provider setup', 'text' => 'Pathao/Steadfast, local provider, own fleet configure করুন।', 'anchor' => 'providers'],
                ['icon' => 'feather-map', 'title' => 'Zone & rate', 'text' => 'District/upazila/area map করে delivery charge setup করুন।', 'anchor' => 'zones'],
                ['icon' => 'feather-package', 'title' => 'Shipment monitor', 'text' => 'Order থেকে shipment create হলে status/assignment দেখুন।', 'anchor' => 'shipments'],
                ['icon' => 'feather-dollar-sign', 'title' => 'COD collect', 'text' => 'Rider/customer courier থেকে COD collect/verify করুন।', 'anchor' => 'cod'],
                ['icon' => 'feather-check-square', 'title' => 'Settlement', 'text' => 'Verified COD settlement করে delivery cycle close করুন।', 'anchor' => 'settlements'],
                ['icon' => 'feather-bar-chart-2', 'title' => 'Reports', 'text' => 'Aging, provider, employee, finance report/export দেখুন।', 'anchor' => 'reports'],
            ];
        }

        return [
            ['icon' => 'feather-navigation', 'title' => 'Provider setup', 'text' => 'Configure Pathao/Steadfast, local providers, and own fleet.', 'anchor' => 'providers'],
            ['icon' => 'feather-map', 'title' => 'Zone & rate', 'text' => 'Map district/upazila/area and set delivery charges.', 'anchor' => 'zones'],
            ['icon' => 'feather-package', 'title' => 'Shipment monitor', 'text' => 'Track shipments created from orders.', 'anchor' => 'shipments'],
            ['icon' => 'feather-dollar-sign', 'title' => 'COD collect', 'text' => 'Collect and verify COD from riders or couriers.', 'anchor' => 'cod'],
            ['icon' => 'feather-check-square', 'title' => 'Settlement', 'text' => 'Close the delivery cycle with verified settlements.', 'anchor' => 'settlements'],
            ['icon' => 'feather-bar-chart-2', 'title' => 'Reports', 'text' => 'Review aging, provider, employee, finance reports and exports.', 'anchor' => 'reports'],
        ];
    }

    private function dailyChecklist(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'Dashboard থেকে pending shipment, COD pending এবং delayed shipment দেখুন।',
                'Provider/API courier order status sync হয়েছে কিনা check করুন।',
                'Assigned rider/employee delivery status update করেছে কিনা দেখুন।',
                'Delivered COD collection submitted/verified হয়েছে কিনা দেখুন।',
                'Settlement pending থাকলে finance/accounting team-এর সাথে close করুন।',
            ];
        }

        return [
            'Review pending shipments, COD pending, and delayed shipments on the dashboard.',
            'Check whether provider/API courier orders are synced.',
            'Confirm assigned riders or employees updated delivery statuses.',
            'Verify submitted COD collections for delivered shipments.',
            'Close pending settlements with finance/accounting.',
        ];
    }

    private function quickLinks(string $locale): array
    {
        return [
            ['route' => 'delivery-management.dashboard', 'label' => $locale === 'bn' ? 'Dashboard' : 'Dashboard'],
            ['route' => 'delivery-management.providers.index', 'label' => $locale === 'bn' ? 'Providers' : 'Providers'],
            ['route' => 'delivery-management.zones.index', 'label' => $locale === 'bn' ? 'Zones' : 'Zones'],
            ['route' => 'delivery-management.shipments.index', 'label' => $locale === 'bn' ? 'Shipments' : 'Shipments'],
            ['route' => 'delivery-management.cod-collections.index', 'label' => $locale === 'bn' ? 'COD' : 'COD'],
            ['route' => 'delivery-management.reports.index', 'label' => $locale === 'bn' ? 'Reports' : 'Reports'],
        ];
    }

    private function sectionsBn(): array
    {
        return [
            $this->section('overview', 'Delivery Management কী?', 'feather-compass', 'DELIVERY MANAGEMENT → Dashboard',
                'এটি order থেকে shipment, local delivery, API courier, COD collection, settlement এবং reporting manage করার central module।',
                ['Sidebar থেকে Delivery Management খুলুন।', 'প্রথমে Dashboard দেখুন।', 'Provider, Employee, Zone/Rate Card setup করুন।', 'Order/POS থেকে shipment তৈরি হলে Shipments screen থেকে monitor করুন।'],
                'POS order-এ local delivery provider select করলে shipment Delivery Management-এ provider সহ তৈরি হবে।',
                'Delivery team এক জায়গা থেকে shipment, COD, settlement এবং reports চালাতে পারবে।',
                ['POS stable flow আলাদা রাখা হয়েছে।', 'Pathao/Steadfast API courier old dispatch flow থেকে continue করে।'],
                'Order/stock/accounting flow manual থেকে update হয় না।',
                [['Shipment দেখা যাচ্ছে না', 'Order delivery intent নেই বা migration/table missing', 'Order delivery info, shipment sync log এবং migration check করুন।']]
            ),
            $this->section('providers', 'Delivery Providers এবং Courier Config', 'feather-navigation', 'Delivery Management → Delivery Providers / Courier Config',
                'Provider মানে delivery partner: local provider, own fleet, store pickup বা API courier। Pathao/Steadfast credential Courier Config থেকে manage হয়।',
                ['Delivery Providers খুলুন।', 'Local/own fleet provider create করুন।', 'API courier হলে Courier Config থেকে credential/status manage করুন।', 'Provider active রাখুন।', 'POS Local Delivery Method dropdown-এ local provider পাওয়া যাবে।'],
                'Own Rider Team নামে internal_fleet provider create করলে POS local delivery method হিসেবে দেখা যাবে।',
                'Shipment provider correctly map হবে।',
                ['Pathao/Steadfast duplicate config দুই জায়গায় maintain করবেন না।', 'Courier Config save করলে matching provider mirror update হয়।'],
                'Wrong API credential দিলে courier booking/status fail করবে।',
                [['Provider dropdown empty', 'No active local/manual/internal provider', 'Delivery Providers থেকে active provider create করুন।']]
            ),
            $this->section('employees', 'Delivery Employees / Rider setup', 'feather-users', 'Delivery Management → Delivery Employees',
                'Own delivery employee/rider record, vehicle, status এবং cash collection limit manage করার জায়গা।',
                ['Employees খুলুন।', 'Create Employee করুন।', 'Name, phone, vehicle, salary/commission info দিন।', 'Status active রাখুন।', 'Shipment detail থেকে assign/reassign করুন।'],
                'Rider Rahim active করলে shipment detail page থেকে তাকে assign করা যাবে।',
                'Assignment history এবং rider-wise COD report তৈরি হবে।',
                ['Cash collection limit ব্যবহার করলে COD risk control সহজ হয়।', 'Inactive rider assign করবেন না।'],
                'Employee record HR payroll-এর replacement নয়; delivery operation tracking।',
                [['Rider assign হচ্ছে না', 'Employee inactive বা missing', 'Employee status active করুন।']]
            ),
            $this->section('zones', 'Zones, Areas এবং Rate Cards', 'feather-map', 'Delivery Management → Zones & Rate Cards',
                'Delivery area map এবং provider charge setup করার জায়গা। District/upazila select2 দিয়ে area map করা যায়।',
                ['Zones খুলুন।', 'Create/Edit Zone করুন।', 'Zone Areas থেকে District, Upazila, Area Name add করুন।', 'Rate Cards থেকে provider/zone charge setup করুন।', 'POS/order charge automation পরে rate card থেকে extend করা যাবে।'],
                'Dhaka City zone-এ Dhaka district এবং Dhanmondi upazila map করে Same Day rate card set করা হলো।',
                'Provider cost এবং area coverage reporting clear হবে।',
                ['Area duplicate না করা ভালো।', 'District select করলে upazila filtered হবে।'],
                'Rate card setup করলেই existing POS charge auto-overwrite হয় না।',
                [['Upazila list ভুল', 'District selection mismatch', 'District reselect করুন এবং select2 refresh করুন।']]
            ),
            $this->section('shipments', 'Shipments monitor এবং assignment', 'feather-package', 'Delivery Management → Shipments',
                'Order/POS/eCommerce flow থেকে shipment তৈরি হলে এখান থেকে status, provider, employee, assignment এবং COD monitor করা হয়।',
                ['Shipments খুলুন।', 'Search/filter করে shipment open করুন।', 'Provider/employee assignment দেখুন।', 'Need হলে assign/reassign করুন।', 'Status update করুন।'],
                'Ready for dispatch shipment own rider-কে assign করে status out for delivery করা হলো।',
                'Delivery lifecycle visible থাকবে।',
                ['Terminal status delivered/returned/cancelled হলে সতর্ক হয়ে update করুন।', 'API courier status external sync থেকেও আসতে পারে।'],
                'Wrong status দিলে reports/COD settlement impact হতে পারে।',
                [['Shipment duplicate', 'Same order multiple non-terminal shipment created', 'Order sync logic/status terminal state check করুন।']]
            ),
            $this->section('pos-integration', 'POS Local Delivery Method', 'feather-shopping-cart', 'POS → Delivery Info / Delivery Management → Providers',
                'POS order save করার সময় Local Delivery Method select করলে Delivery Management provider shipment-এর সাথে map হয়। Pathao/Steadfast আলাদা Courier Method section-এ থাকে।',
                ['POS খুলুন।', 'Delivery Info enable করুন।', 'Delivery Method select করুন।', 'Local Delivery Method থেকে local provider select করুন।', 'Courier Method select করলে local provider clear হবে।', 'Order submit করুন।'],
                'Home Delivery + Own Rider Team select করলে shipment provider Own Rider Team হবে।',
                'Local delivery order API courier ছাড়া internal delivery pipeline-এ যাবে।',
                ['Courier Method এবং Local Delivery Method একসাথে ব্যবহার করবেন না।', 'Pathao/Steadfast থাকলে Courier Method ব্যবহার করুন।'],
                'POS stock/accounting flow অপরিবর্তিত থাকে।',
                [['Local method দেখা যাচ্ছে না', 'No active local provider or frontend cache', 'Provider active করুন এবং POS refresh করুন।']]
            ),
            $this->section('cod', 'COD Collections', 'feather-dollar-sign', 'Delivery Management → COD Collections / Shipment Details',
                'Delivered shipment-এর COD rider/courier থেকে collect, submit এবং verify করার workflow।',
                ['Shipment detail খুলুন।', 'COD collection entry করুন।', 'Collected/submitted amount দিন।', 'COD Collections page থেকে pending review করুন।', 'Finance verify করুন।'],
                'Rider 2,000 টাকা collect করে 1,950 submit করলে pending 50 দেখা যাবে।',
                'Cash accountability clear হবে।',
                ['Expected, collected, submitted amount আলাদা বুঝে লিখুন।', 'Verify করার আগে cash/bank match করুন।'],
                'Verified COD ভুল হলে settlement wrong হতে পারে।',
                [['COD pending বেশি', 'Submitted amount কম বা verify হয়নি', 'Employee COD outstanding report দেখুন।']]
            ),
            $this->section('settlements', 'Settlements', 'feather-check-square', 'Delivery Management → Settlements / API Courier Settlement',
                'Verified COD এবং courier settlement close করার finance workflow। Internal settlement এবং legacy API courier settlement আলাদা screen-এ আছে।',
                ['Settlements খুলুন।', 'Verified unsettled COD থেকে draft settlement করুন।', 'Amount review করুন।', 'Approve করুন।', 'API courier হলে API Courier Settlement screen ব্যবহার করুন।'],
                'Steadfast settlement upload/status sync করে delivered COD receive এবং courier cost post করা হলো।',
                'Receivable, delivery expense এবং settlement status clean হবে।',
                ['Approve করার আগে amount, provider, employee, date check করুন।', 'Return settlement stock log impact করতে পারে।'],
                'Wrong settlement accounting/stock report affect করতে পারে।',
                [['Settlement item নেই', 'COD not verified or already settled', 'COD collection status check করুন।']]
            ),
            $this->section('reports', 'Reports এবং CSV export', 'feather-bar-chart-2', 'Delivery Management → Reports',
                'Status, provider, employee COD, aging, delayed shipment এবং finance summary report দেখা/export করা যায়।',
                ['Reports খুলুন।', 'Date/provider/employee/status filter দিন।', 'Summary cards দেখুন।', 'Aging/watchlist review করুন।', 'CSV report type select করে export করুন।'],
                'Last 7 days filter দিয়ে delayed shipment watchlist export করা হলো।',
                'Management daily delivery performance বুঝবে।',
                ['Report দেখার আগে latest status/COD update করুন।', 'Aging report non-terminal shipment দেখে।'],
                'CSV export current filters follow করে; wrong filter দিলে wrong download হবে।',
                [['Report blank', 'No shipment in filter range', 'Date/status/provider filter reset করুন।']]
            ),
        ];
    }

    private function sectionsEn(): array
    {
        return [
            $this->section('overview', 'What is Delivery Management?', 'feather-compass', 'DELIVERY MANAGEMENT → Dashboard',
                'A central module for order-to-shipment tracking, local delivery, API couriers, COD collection, settlements, and reports.',
                ['Open Delivery Management from the sidebar.', 'Review the dashboard first.', 'Set up providers, employees, zones, and rate cards.', 'Monitor shipments created from POS or order flows.'],
                'A POS order with a selected local provider creates a Delivery Management shipment with that provider.',
                'The delivery team can manage shipments, COD, settlements, and reports from one module.',
                ['The stable POS flow remains separate.', 'Pathao/Steadfast continue through the courier dispatch flow.'],
                'The manual does not update orders, stock, or accounting.',
                [['Shipment missing', 'No delivery intent or missing tables', 'Check order delivery info, sync logs, and migrations.']]
            ),
            $this->section('providers', 'Providers and courier configuration', 'feather-navigation', 'Delivery Management → Delivery Providers / Courier Config',
                'Providers represent local partners, own fleet, store pickup, or API couriers. Pathao/Steadfast credentials are managed from Courier Config.',
                ['Open Delivery Providers.', 'Create local or own-fleet providers.', 'Use Courier Config for API courier credentials/status.', 'Keep providers active.', 'Active local providers appear in POS Local Delivery Method.'],
                'Create an Own Rider Team provider with internal_fleet type and it becomes selectable in POS.',
                'Shipments map to the correct provider.',
                ['Do not maintain duplicate Pathao/Steadfast config.', 'Saving Courier Config mirrors the matching provider.'],
                'Wrong API credentials can break courier booking/status sync.',
                [['Provider dropdown empty', 'No active local/manual/internal provider', 'Create and activate a provider.']]
            ),
            $this->section('employees', 'Delivery employees and riders', 'feather-users', 'Delivery Management → Delivery Employees',
                'Manage own delivery employees/riders, vehicle information, status, and cash collection limits.',
                ['Open Employees.', 'Create an employee.', 'Fill name, phone, vehicle, salary/commission information.', 'Keep status active.', 'Assign from shipment details.'],
                'Active rider Rahim can be assigned from a shipment detail page.',
                'Assignment history and rider-wise COD reporting become available.',
                ['Use cash collection limits to control COD risk.', 'Do not assign inactive riders.'],
                'This is operation tracking, not a full HR payroll replacement.',
                [['Cannot assign rider', 'Employee inactive or missing', 'Activate or create the employee.']]
            ),
            $this->section('zones', 'Zones, areas, and rate cards', 'feather-map', 'Delivery Management → Zones & Rate Cards',
                'Map service areas and configure provider delivery charges. District/upazila select2 fields help map areas.',
                ['Open Zones.', 'Create or edit a zone.', 'Add District, Upazila, and Area Name in Zone Areas.', 'Configure provider/zone charges from Rate Cards.', 'Later automation can use these rate cards for charges.'],
                'Map Dhaka district and Dhanmondi upazila to a Dhaka City zone, then set Same Day provider rates.',
                'Provider cost and area coverage are easier to report.',
                ['Avoid duplicate areas.', 'Upazilas are filtered after district selection.'],
                'Creating a rate card does not overwrite existing POS delivery charges automatically.',
                [['Wrong upazila list', 'District mismatch', 'Reselect district and refresh select2.']]
            ),
            $this->section('shipments', 'Shipment monitoring and assignment', 'feather-package', 'Delivery Management → Shipments',
                'Monitor status, provider, employee assignment, and COD for shipments created from POS/eCommerce/order flows.',
                ['Open Shipments.', 'Search/filter shipments.', 'Open a shipment.', 'Assign or reassign provider/employee when needed.', 'Update delivery status.'],
                'A ready-for-dispatch shipment is assigned to a rider and moved to out for delivery.',
                'The delivery lifecycle becomes visible.',
                ['Be careful with terminal statuses.', 'API courier status may also arrive from external sync.'],
                'Wrong statuses affect reports and COD settlement.',
                [['Duplicate shipment', 'Multiple non-terminal shipments for one order', 'Check order sync logic and terminal status.']]
            ),
            $this->section('pos-integration', 'POS Local Delivery Method', 'feather-shopping-cart', 'POS → Delivery Info / Delivery Management → Providers',
                'When a POS order selects Local Delivery Method, the Delivery Management provider is mapped to the shipment. Pathao/Steadfast remain under Courier Method.',
                ['Open POS.', 'Enable Delivery Info.', 'Select Delivery Method.', 'Select a Local Delivery Method.', 'Selecting a Courier Method clears local provider.', 'Submit the order.'],
                'Home Delivery plus Own Rider Team creates a shipment with Own Rider Team as provider.',
                'Local delivery orders enter the internal delivery pipeline without API courier booking.',
                ['Do not use Courier Method and Local Delivery Method together.', 'Use Courier Method for Pathao/Steadfast.'],
                'POS stock/accounting flow remains unchanged.',
                [['Local method missing', 'No active local provider or frontend cache', 'Activate provider and refresh POS.']]
            ),
            $this->section('cod', 'COD collections', 'feather-dollar-sign', 'Delivery Management → COD Collections / Shipment Details',
                'Collect, submit, and verify COD from riders or couriers for delivered shipments.',
                ['Open shipment details.', 'Add COD collection.', 'Enter collected/submitted amounts.', 'Review pending COD from COD Collections.', 'Finance verifies the collection.'],
                'A rider collects 2,000 and submits 1,950; pending 50 remains visible.',
                'Cash accountability is clear.',
                ['Understand expected, collected, and submitted amounts separately.', 'Verify only after cash/bank reconciliation.'],
                'Wrong verification can create wrong settlements.',
                [['High pending COD', 'Submitted amount is short or unverified', 'Review employee COD outstanding report.']]
            ),
            $this->section('settlements', 'Settlements', 'feather-check-square', 'Delivery Management → Settlements / API Courier Settlement',
                'Close the finance cycle for verified COD and API courier settlements.',
                ['Open Settlements.', 'Create a draft from verified unsettled COD.', 'Review amounts.', 'Approve settlement.', 'Use API Courier Settlement for Pathao/Steadfast settlement workflows.'],
                'A Steadfast settlement posts delivered COD received and courier cost.',
                'Receivables, delivery expense, and settlement status stay clear.',
                ['Review amount, provider, employee, and date before approval.', 'Return settlements may affect stock logs.'],
                'Wrong settlements can affect accounting/stock reports.',
                [['No settlement items', 'COD not verified or already settled', 'Check COD collection status.']]
            ),
            $this->section('reports', 'Reports and CSV export', 'feather-bar-chart-2', 'Delivery Management → Reports',
                'Review status, provider, employee COD, aging, delayed shipment, and finance summaries.',
                ['Open Reports.', 'Apply date/provider/employee/status filters.', 'Review summary cards.', 'Check aging/watchlist.', 'Choose a CSV report type and export.'],
                'Export the delayed shipment watchlist for the last 7 days.',
                'Management can read daily delivery performance.',
                ['Update statuses and COD before reporting.', 'Aging reports use non-terminal shipments.'],
                'CSV export follows current filters.',
                [['Blank report', 'No shipment in the selected filter range', 'Reset date/status/provider filters.']]
            ),
        ];
    }

    private function section(string $key, string $title, string $icon, string $menuPath, string $why, array $steps, string $example, string $result, array $tips, string $warning, array $troubleshooting): array
    {
        return compact('key', 'title', 'icon', 'menuPath', 'why', 'steps', 'example', 'result', 'tips', 'warning', 'troubleshooting');
    }
}
