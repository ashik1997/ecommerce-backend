<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmUserManualSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FbmUserManualContentService
{
    public function forLocale(string $locale): array
    {
        $locale = $locale === 'en' ? 'en' : 'bn';
        $isBn = $locale === 'bn';
        $schemaReady = Schema::hasTable('fbm_user_manual_sections');

        return [
            'locale' => $locale,
            'switch_locale' => $isBn ? 'en' : 'bn',
            'schema_ready' => $schemaReady,
            'page_title' => $isBn ? 'FB Marketing ব্যবহার নির্দেশিকা' : 'FB Marketing User Manual',
            'hero_title' => $isBn ? 'FB Marketing ব্যবহার নির্দেশিকা' : 'FB Marketing User Manual',
            'hero_subtitle' => $isBn
                ? 'সহজ ভাষায় setup, sync, reporting, campaign planning, Lead Ads, alerts এবং controlled publish flow ধাপে ধাপে শিখুন।'
                : 'A practical step-by-step guide for setup, sync, reporting, campaign planning, Lead Ads, alerts, and controlled publish flows.',
            'hero_note' => $isBn
                ? 'এই manual read-only। এখানে কোনো Meta request, campaign publish বা database update হয় না।'
                : 'This manual is read-only. It does not send Meta requests, publish campaigns, or update database records.',
            'labels' => $this->labels($locale),
            'quick_start' => $this->quickStart($locale),
            'daily_checklist' => $this->dailyChecklist($locale),
            'sections' => $isBn ? $this->sectionsBn() : $this->sectionsEn(),
            'custom_sections' => $schemaReady ? $this->databaseSections($locale)->values()->all() : [],
            'quick_links' => $this->quickLinks($locale),
        ];
    }

    private function databaseSections(string $locale): Collection
    {
        return FbmUserManualSection::query()
            ->where('locale', $locale)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn(FbmUserManualSection $section): array => $section->toSafeSummary());
    }

    private function labels(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'switch_text' => 'English Version',
                'quick_start_title' => 'দ্রুত শুরু করুন',
                'daily_checklist_title' => 'Daily checklist',
                'index_title' => 'বিষয়ভিত্তিক সূচি',
                'index_help' => 'Search করুন অথবা index থেকে section খুলুন।',
                'search_placeholder' => 'যেমন: setup, report, campaign, lead',
                'section_count' => 'Tutorial sections',
                'language' => 'Language',
                'menu_path' => 'Menu path',
                'why' => 'এখানে কী হবে?',
                'steps' => 'ধাপে ধাপে ব্যবহার',
                'example' => 'সহজ উদাহরণ',
                'expected_result' => 'Expected result',
                'tips' => 'ভালোভাবে কাজ করার টিপস',
                'warning' => 'সতর্কতা',
                'troubleshooting' => 'সমস্যা হলে',
                'problem' => 'সমস্যা',
                'reason' => 'সম্ভাব্য কারণ',
                'solution' => 'করণীয়',
                'custom_notes' => 'Application custom notes',
                'back_to_top' => 'উপরে যান',
                'no_match' => 'কোনো matching topic পাওয়া যায়নি। অন্য keyword দিয়ে search করুন।',
            ];
        }

        return [
            'switch_text' => 'বাংলা সংস্করণ',
            'quick_start_title' => 'Start quickly',
            'daily_checklist_title' => 'Daily checklist',
            'index_title' => 'Topic index',
            'index_help' => 'Search by topic or open a section from the index.',
            'search_placeholder' => 'Example: setup, report, campaign, lead',
            'section_count' => 'Tutorial sections',
            'language' => 'Language',
            'menu_path' => 'Menu path',
            'why' => 'What is this for?',
            'steps' => 'Step-by-step tutorial',
            'example' => 'Simple example',
            'expected_result' => 'Expected result',
            'tips' => 'Tips for safe operation',
            'warning' => 'Warning',
            'troubleshooting' => 'Troubleshooting',
            'problem' => 'Problem',
            'reason' => 'Possible reason',
            'solution' => 'What to do',
            'custom_notes' => 'Application custom notes',
            'back_to_top' => 'Back to top',
            'no_match' => 'No matching topic was found. Try another keyword.',
        ];
    }

    private function quickStart(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                ['icon' => 'feather-sliders', 'title' => 'Setup Wizard খুলুন', 'text' => 'Migration, connection, asset, queue এবং writer gate একসাথে দেখুন।', 'anchor' => 'setup-wizard'],
                ['icon' => 'feather-key', 'title' => 'Connection test করুন', 'text' => 'Encrypted Meta token save করে read-only health check চালান।', 'anchor' => 'connection-health'],
                ['icon' => 'feather-refresh-cw', 'title' => 'Data sync করুন', 'text' => 'Asset discovery, selected Ad Account sync এবং snapshot refresh করুন।', 'anchor' => 'sync-refresh'],
                ['icon' => 'feather-bar-chart-2', 'title' => 'Report দেখুন', 'text' => 'Dashboard, Performance, Attribution, Profitability থেকে outcome দেখুন।', 'anchor' => 'reporting'],
                ['icon' => 'feather-edit-3', 'title' => 'Campaign plan করুন', 'text' => 'Audience, creative, draft, approval এবং publish gate follow করুন।', 'anchor' => 'campaign-drafts'],
                ['icon' => 'feather-shield', 'title' => 'Safety gate বুঝুন', 'text' => 'Live Meta write করার আগে FBM-35 launch gate clean কিনা দেখুন।', 'anchor' => 'provider-writer'],
            ];
        }

        return [
            ['icon' => 'feather-sliders', 'title' => 'Open Setup Wizard', 'text' => 'Review migrations, connection, assets, queue, and writer gates in one place.', 'anchor' => 'setup-wizard'],
            ['icon' => 'feather-key', 'title' => 'Test connection', 'text' => 'Save the encrypted Meta token and run a read-only health check.', 'anchor' => 'connection-health'],
            ['icon' => 'feather-refresh-cw', 'title' => 'Sync data', 'text' => 'Run asset discovery, selected Ad Account sync, and snapshot refresh.', 'anchor' => 'sync-refresh'],
            ['icon' => 'feather-bar-chart-2', 'title' => 'Read reports', 'text' => 'Use Dashboard, Performance, Attribution, and Profitability to see outcomes.', 'anchor' => 'reporting'],
            ['icon' => 'feather-edit-3', 'title' => 'Plan campaigns', 'text' => 'Follow audience, creative, draft, approval, and publish gates.', 'anchor' => 'campaign-drafts'],
            ['icon' => 'feather-shield', 'title' => 'Understand safety', 'text' => 'Check the FBM-35 launch gate before any live Meta write.', 'anchor' => 'provider-writer'],
        ];
    }

    private function dailyChecklist(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'Dashboard freshness, latest sync এবং open alerts review করুন।',
                'Selected Ad Account, Page, Pixel/Dataset এবং Catalog ঠিক আছে কিনা দেখুন।',
                'Performance/Profitability report দেখে low performance, overspend বা stock risk note করুন।',
                'Lead Ads inbox/CRM lead mapping এবং webhook status check করুন।',
                'Campaign draft approve/publish করার আগে creative, audience, budget cap এবং launch gate verify করুন।',
            ];
        }

        return [
            'Review dashboard freshness, latest sync, and open alerts.',
            'Confirm selected Ad Account, Page, Pixel/Dataset, and Catalog.',
            'Use Performance/Profitability reports to spot low performance, overspend, or stock risk.',
            'Check Lead Ads ingestion, CRM mapping, and webhook status.',
            'Before approving or publishing a draft, verify creative, audience, budget caps, and launch gates.',
        ];
    }

    private function sectionsBn(): array
    {
        return [
            $this->section('overview', 'FB Marketing module কী?', 'feather-compass', 'FB MARKETING',
                'এটি ERP-এর ভিতরে Meta/Facebook marketing control center। এখানে credential setup, asset sync, campaign planning, stored report, Lead Ads, alerts এবং controlled provider write readiness manage করা যায়।',
                ['Sidebar থেকে FB Marketing খুলুন।', 'প্রথমে Dashboard, Configuration, Setup Wizard এবং User Manual দেখে নিন।', 'Reporting data stored snapshots থেকে আসে; page load করলে সরাসরি Meta call হয় না।', 'Live campaign publish/budget update default বন্ধ থাকে, আলাদা signed enablement ছাড়া চালু করবেন না।'],
                'নতুন operator প্রথমে Setup Wizard খুলে কোন migration/connection/asset/queue pending আছে তা দেখে। তারপর Configuration থেকে connection test করে Dashboard report দেখে।',
                'User বুঝবে কোন screen কোন কাজের জন্য এবং কোন action live Meta write করতে পারে।',
                ['Dashboard/reporting safe read-only data দেখায়।', 'Provider write actions always permission, approval, queue readiness এবং safety gate মানে।'],
                'Meta credential, access token, app secret বা raw provider ID manual note বা chat-এ paste করবেন না.',
                [['Data দেখাচ্ছে না', 'Setup বা sync incomplete', 'Setup Wizard, Configuration health, selected assets এবং latest sync দেখুন।']]
            ),
            $this->section('roles-permissions', 'Role এবং permission setup', 'feather-lock', 'Admin → Role / FB MARKETING sidebar',
                'সব user একই action করতে পারবে না। Admin, Marketing Operator, Analyst, Finance এবং CRM user-এর permission আলাদা রাখা নিরাপদ।',
                ['Admin role permission খুলুন।', 'যারা শুধু report দেখবে তাদের dashboard/report/performance read দিন।', 'Credential manage permission শুধু trusted admin-কে দিন।', 'Campaign draft create/approve/publish permission আলাদা user বা approval flow-এ দিন।', 'Lead Ads/CRM mapping permission CRM owner-কে দিন।'],
                'Finance user Profitability/Boosting Jobs দেখবে, কিন্তু credential edit বা campaign publish করবে না। Marketing manager draft approve করবে, analyst শুধু report দেখবে।',
                'User নিজের কাজের menu দেখবে; risky action hidden বা forbidden থাকবে।',
                ['Permission change করলে user-কে refresh বা পুনরায় login করতে বলুন।', 'Publish/operational write permission কম user-কে দিন।'],
                'সব user-কে full access দিলে accidental publish, budget edit বা credential change হতে পারে।',
                [['Button দেখা যাচ্ছে না', 'Permission নেই', 'RoleSidebarPermissionService/role permission থেকে contextual key দিন।']]
            ),
            $this->section('setup-wizard', 'Setup Wizard দিয়ে readiness check', 'feather-list', 'FB Marketing → Setup Wizard',
                'Setup Wizard module-এর checklist। কোন migration, connection, asset, queue, tracking, writer gate ready/pending তা এক জায়গায় দেখায়।',
                ['Setup Wizard খুলুন।', 'Basic readiness card দেখুন: vault, health, asset discovery, queue, insights।', 'Advanced checklist-এ FBM-15 থেকে FBM-35 পর্যন্ত Ready/Blocked দেখুন।', 'Blocked row-এর missing item note করুন।', 'প্রতিটি missing item সংশ্লিষ্ট screen থেকে complete করুন।'],
                'FBM-35 blocked দেখাচ্ছে: Provider writer readiness, Dedicated queue. এর মানে reporting blocked না; live Meta write enablement blocked।',
                'Operator বুঝবে আগে কোন setup complete করতে হবে।',
                ['প্রথম deployment-এর পর Wizard খুলে screenshot/save note রাখুন।', 'Migration pending থাকলে আগে application migration run করুন।'],
                'Wizard ready দেখালেই live write auto-enabled হয় না। Live write আলাদা signed config release লাগে।',
                [['FBM-19 migration pending দেখায়', 'Wrong/old table অথবা migration missing', 'Actual table `fbm_boosting_job_cost_adjustments` আছে কিনা check করুন।']]
            ),
            $this->section('connection-health', 'Meta connection এবং health test', 'feather-key', 'FB Marketing → Configuration',
                'Meta credential encrypted vault-এ save হয়। Health test read-only; token valid, scope, expiry এবং Graph version safe summary দেখায়।',
                ['Configuration খুলুন।', 'Add encrypted Meta connection form পূরণ করুন।', 'Access token, app secret, webhook verify token password field-এ দিন।', 'Save করুন; secret আর browser-এ দেখাবে না।', 'Run health test চাপুন।', 'Health result healthy/warning এবং missing scope দেখুন।'],
                'Token save করার পর health test বলল missing writer scope `ads_management`; reporting চলতে পারে, কিন্তু provider writer readiness pass করবে না।',
                'Connection usable কিনা এবং কোন permission/scope missing তা জানা যাবে।',
                ['System user token ব্যবহার করলে expiry/permission policy আগে ঠিক করুন।', 'Read-only health pass না হলে asset discovery বা sync-এ যাওয়ার আগে token fix করুন।'],
                'Credential note, ticket বা manual content table-এ token লিখবেন না।',
                [['Health failed', 'Token invalid, expired, wrong Graph version বা app permission missing', 'Meta Business/App settings থেকে token regenerate করে আবার save/test করুন।']]
            ),
            $this->section('assets-selection', 'Asset discovery এবং selection', 'feather-layers', 'FB Marketing → Configuration / Assets',
                'Ad Account, Page, Pixel/Dataset, Catalog, Instagram account, Audience, Product Set local mirror করে selected asset নির্ধারণ করা হয়।',
                ['Health test healthy/warning করুন।', 'Run asset discovery চাপুন।', 'Available asset list review করুন।', 'Business, Ad Account, Page, Pixel/Dataset এবং Catalog select করুন।', 'Selection audit দেখে confirm করুন কে change করেছে।'],
                'একাধিক Ad Account থাকলে শুধু intended production account select করুন। Wrong account select করলে report/publish wrong account target করতে পারে।',
                'Reporting, sync, campaign draft এবং launch gate সঠিক asset ব্যবহার করবে।',
                ['প্রথম discovery শুধু list আনতে পারে; selection করার পর sync আবার চালান।', 'Unavailable asset select করবেন না।'],
                'Raw provider asset ID browser-এ দেখানোর দরকার নেই; name/status দেখে select করুন।',
                [['Asset নেই', 'Token scope/business access missing', 'Meta Business permission ঠিক করে discovery আবার চালান।']]
            ),
            $this->section('sync-refresh', 'Sync এবং snapshot refresh', 'feather-refresh-cw', 'FB Marketing → Configuration',
                'Report data local snapshots থেকে আসে। Sync Meta থেকে read-only data এনে local tables update করে। Queue-ready production path এবং bounded no-queue testing path আছে।',
                ['Configuration খুলুন।', 'Queue readiness ready হলে Queue full read-only Meta sync চালান।', 'Queue না থাকলে testing-এর জন্য Run manual sync now (no queue) ব্যবহার করুন।', 'Drilldown report দরকার হলে Refresh drilldown snapshots now চালান।', 'Latest sync status, row count, freshness watermark check করুন।'],
                'Dashboard blank হলে health test → discovery → select Ad Account → manual sync → drilldown refresh এই order follow করুন।',
                'Dashboard/reporting fresh stored data পাবে।',
                ['Production-এ queue worker ব্যবহার করুন।', 'Manual no-queue small test/fallback; বড় historical backfill নয়।'],
                'একই connection-এ overlapping sync চালাবেন না; lock থাকলে wait করুন।',
                [['Queue blocked', 'Dedicated queue connection/jobs table/worker missing', '`php artisan fb-marketing:queue-readiness` এবং worker config check করুন।']]
            ),
            $this->section('dashboard', 'Dashboard overview পড়া', 'feather-home', 'FB Marketing → Dashboard',
                'Executive overview: spend, impressions, clicks, CTR, CPC, purchase attribution, ROAS, freshness এবং alerts summary।',
                ['Date range select করুন।', 'Selected Ad Account filter apply করুন।', 'Freshness panel দেখুন।', 'KPI card এবং trend compare করুন।', 'Alert থাকলে Health & Alerts খুলুন।'],
                'Last 7 days range দিলে spend, clicks, CTR এবং attributed sales দেখা যাবে। Freshness যদি 3 দিন old হয়, আগে sync refresh করুন।',
                'Management দ্রুত বুঝতে পারবে ad performance ভালো না খারাপ।',
                ['Mixed currency থাকলে totals আলাদা বুঝুন; silent conversion হয় না।', 'Summed daily reach unique reach নয়।'],
                'Freshness old হলে dashboard decision নেবেন না।',
                [['KPI zero', 'Snapshot নেই বা wrong date range', 'Sync run এবং selected Ad Account check করুন।']]
            ),
            $this->section('reporting', 'Performance, Attribution, Profitability report', 'feather-bar-chart-2', 'FB Marketing → Performance / Attribution / Profitability / Reports',
                'Stored Meta spend + ERP sales/cost data মিলিয়ে campaign outcome দেখা যায়। Finance/report export এখান থেকে হয়।',
                ['Performance খুলে campaign/ad set/ad drilldown দেখুন।', 'Attribution Reports খুলে ERP order attribution summary দেখুন।', 'Profitability খুলে spend, local cost, sales, margin review করুন।', 'Reports থেকে export request করুন।', 'Export/download করার আগে filter/date confirm করুন।'],
                'Campaign spend বেশি কিন্তু ERP attributed sales কম হলে Profitability report দেখিয়ে budget pause/update recommendation তৈরি করা যায়।',
                'Marketing, finance এবং management একই source data থেকে decision নিতে পারবে।',
                ['Finance cost adjustment approved status না হলে totals-এ নাও আসতে পারে।', 'Attribution uses consent-bounded local evidence; সব sale Meta-attributed হবে না।'],
                'Report export মানে live Meta call নয়; old snapshot হলে আগে refresh করুন।',
                [['ROAS mismatch', 'Meta attribution window বনাম ERP attribution আলাদা', 'Reporting definitions দেখে source/metric boundary বুঝুন।']]
            ),
            $this->section('catalog', 'Products & Catalog diagnostics', 'feather-shopping-bag', 'FB Marketing → Products & Catalog',
                'ERP product feed readiness, catalog mapping, product set, stock risk এবং feed diagnostic দেখা যায়।',
                ['Products & Catalog খুলুন।', 'Feed-ready, excluded, missing image/title/price diagnostic দেখুন।', 'Refresh catalog mappings now চালান।', 'Automatic mapping review করুন।', 'Trusted operator হলে manual override save করুন।'],
                'Meta catalog retailer_id যদি ERP product ID-এর সাথে match করে, automatic mapping হবে। Duplicate হলে ambiguous দেখাবে।',
                'Product-level sales/profit/stock report reliable হবে।',
                ['Product image/title/price clean রাখুন।', 'Manual override reason লিখুন।'],
                'Manual mapping local database only; Meta catalog update/delete করে না।',
                [['Product unmatched', 'Retailer ID mismatch বা product feed issue', 'Feed diagnostics fix করে mapping refresh করুন।']]
            ),
            $this->section('tracking-capi', 'Tracking, Pixel এবং Conversions API', 'feather-activity', 'FB Marketing → Tracking & Attribution',
                'Landing attribution, browser Pixel contract, server CAPI event ledger এবং order attribution এখানে monitor করা হয়।',
                ['Configuration থেকে Pixel/CAPI mode review করুন।', 'Tracking & Attribution খুলুন।', 'Recent attribution sessions, events, pending delivery দেখুন।', 'Dry-run/test diagnostic চালান।', 'Queue unavailable হলে bounded manual retry ব্যবহার করুন।'],
                'Order confirmed হওয়ার পর attributed purchase event pending delivery দেখালে queue readiness fix করে retry করুন।',
                'ERP order source-of-truth থাকে; Meta delivery fail হলেও order save blocked হয় না।',
                ['Consent policy ছাড়া browser tracking enable করবেন না।', 'Test mode দিয়ে first verify করুন।'],
                'Pixel/CAPI token বা event payload browser/manual-এ expose করবেন না।',
                [['CAPI pending', 'Queue disabled বা token missing', 'Connection CAPI token, mode, queue readiness check করুন।']]
            ),
            $this->section('lead-ads', 'Lead Ads থেকে CRM lead', 'feather-user-plus', 'FB Marketing → Lead Ads',
                'Meta Lead Ads webhook callback local ledger-এ আসে এবং mapped field data দিয়ে CRM lead তৈরি করতে পারে।',
                ['Meta app/webhook settings-এ Lead Ads callback URL configure করুন।', 'Verify token Configuration vault-এ save করুন।', 'Lead Ads page খুলে webhook logs দেখুন।', 'Field mapping/status review করুন।', 'CRM lead তৈরি হয়েছে কিনা CRM Leads-এ verify করুন।'],
                'Lead form থেকে name/phone/email field এলে CRM lead তৈরি হবে; শুধু leadgen ID এলে pending retrieval হিসেবে থাকবে।',
                'Sales team CRM pipeline-এ lead follow-up করতে পারবে।',
                ['Lead form field names standard রাখুন।', 'Webhook test lead দিয়ে mapping verify করুন।'],
                'Lead Ads callback raw payload browser-এ দেখাবেন না।',
                [['Lead আসছে না', 'Webhook verify/token/subscription issue', 'Meta webhook verify, app permission এবং callback route throttle/log check করুন।']]
            ),
            $this->section('campaign-drafts', 'Campaign draft, creative, audience এবং approval', 'feather-edit-3', 'FB Marketing → Campaign Drafts / Creative Library / Audiences',
                'Campaign live করার আগে local planning: audience/product set, creative preflight, draft, approval snapshot এবং publish attempt তৈরি হয়।',
                ['Audience/Product Set ready করুন।', 'Creative Library-তে creative asset upload/configure করুন।', 'Preflight pass করান।', 'Campaign Drafts থেকে draft তৈরি করুন।', 'Budget, objective, destination URL, schedule দিন।', 'Approval request/approve করুন।'],
                'New product launch campaign: audience select, product set select, ready creative attach, daily budget cap-এর নিচে budget, manager approval।',
                'Approved snapshot immutable থাকে; duplicate click idempotent publish attempt reuse করে।',
                ['Creative text/link/image আগে QA করুন।', 'Budget cap exceed করলে publish blocked হবে।'],
                'Approval ছাড়া publish attempt করবেন না।',
                [['Draft publish disabled', 'Provider writes disabled/readiness blocked', 'FBM-35 launch gate এবং provider writer readiness check করুন।']]
            ),
            $this->section('provider-writer', 'Live publish, pause/resume, budget/schedule safety', 'feather-shield', 'FB Marketing → Campaign Drafts / Configuration',
                'FBM-31-35 provider writer gate live Meta writes-এর safety layer। Default off. Clean launch gate + signed config release ছাড়া live publish/operational action চালানো যাবে না।',
                ['Configuration → Provider writer readiness দেখুন।', 'Final provider-writer launch gate clean কিনা দেখুন।', 'Queue readiness ready করুন।', 'Approved draft থেকে publish attempt করুন।', 'Published object PAUSED status-এ তৈরি হয়েছে কিনা verify করুন।', 'Operational action দরকার হলে pause/resume/budget/schedule ledger দিয়ে করুন।'],
                'Gate blocked: Provider writer readiness, Dedicated queue. Meaning: reporting OK, but live Meta write blocked until connection scope + queue ready।',
                'Live write controlled, auditable এবং rollback/reconciliation summary সহ হবে।',
                ['First UAT always small budget and PAUSED status।', 'Budget/schedule only ad set target।', 'safe_edit intentionally blocked।'],
                'Config file edit করে bypass করবেন না। Production enablement separate signed release।',
                [['Action queued but not running', 'Queue worker stopped', '`queue:work fb-marketing --queue=fb-marketing` process check করুন।']]
            ),
            $this->section('alerts', 'Health & Alerts এবং reconciliation', 'feather-alert-triangle', 'FB Marketing → Health & Alerts',
                'Token expiry, sync failure, poor CTR, stock risk, optional overspend এবং webhook recovery alert এখানে দেখা যায়।',
                ['Health & Alerts খুলুন।', 'Open/critical alert review করুন।', 'Run reconciliation চাপুন।', 'Alert source, severity, recommendation দেখুন।', 'Need হলে recommendation approve/dismiss করুন।'],
                'Poor CTR alert দেখলে Performance report খুলে ad creative review করুন, তারপর recommendation থেকে pause/budget action plan করুন।',
                'Missed webhook বা stale data recover করতে bounded reconciliation path থাকবে।',
                ['Overspend threshold default 0, policy ছাড়া enable করবেন না।', 'Alert close করার আগে source issue fix করুন।'],
                'Alert recommendation approval automatic Meta write নয়; controlled action boundary follow করে।',
                [['Alert repeat হচ্ছে', 'Underlying sync/token/campaign issue unresolved', 'Connection health, sync log, campaign status check করুন।']]
            ),
            $this->section('boosting-jobs', 'Boosting Jobs এবং finance ledger', 'feather-briefcase', 'FB Marketing → Boosting Jobs',
                'Own-store বা client boosting job আলাদা করে spend, payment, local cost, service fee, refund এবং balance track করা হয়।',
                ['Boosting Jobs খুলুন।', 'New job তৈরি করুন: own-store/client mode, planned budget, service fee, date।', 'Campaign/ad/ad set link করুন।', 'Payment/cost row add করুন।', 'Ledger summary এবং balance review করুন।'],
                'Client boosting job-এ actual Meta spend + service fee + local cost থেকে receivable হিসাব হবে; payment দিলে balance কমবে।',
                'Agency/client marketing finance cleanভাবে track হবে।',
                ['Cost adjustment approved status check করুন।', 'Campaign link না থাকলে actual spend zero হতে পারে।'],
                'এই ledger Meta campaign create/edit করে না; finance/local tracking only।',
                [['Local cost missing', 'Cost adjustment table/migration বা status issue', '`fbm_boosting_job_cost_adjustments` table এবং approved row check করুন।']]
            ),
            $this->section('troubleshooting', 'Common troubleshooting', 'feather-help-circle', 'FB Marketing → Setup Wizard / Configuration / Health & Alerts',
                'Data missing, queue blocked, permission hidden, health failed, publish blocked—এসব দ্রুত diagnose করার checklist।',
                ['Setup Wizard খুলে blocked row দেখুন।', 'Configuration health + selected assets check করুন।', 'Latest sync/API log দেখুন।', 'Health & Alerts reconciliation চালান।', 'Permission issue হলে role key verify করুন।'],
                'Dashboard blank: migration ready → connection healthy → asset selected → sync run success → date range correct—এই order-এ check করুন।',
                'Most issues exact missing item সহ পাওয়া যাবে।',
                ['প্রথমে freshness দেখুন, তারপর metric নিয়ে decision নিন।', 'Route/page forbidden হলে permission key check করুন।'],
                'Database row manually edit করে retry/publish করবেন না।',
                [['403 forbidden', 'Role permission missing', 'Role permission grant করে user refresh/login করুন।'], ['Provider write blocked', 'Final launch gate not clean', 'Scope, selected Ad Account, queue readiness, signed config release complete করুন।']]
            ),
        ];
    }

    private function sectionsEn(): array
    {
        return [
            $this->section('overview', 'What is the FB Marketing module?', 'feather-compass', 'FB MARKETING',
                'The module is an ERP control center for Meta/Facebook marketing: credentials, asset sync, campaign planning, stored reports, Lead Ads, alerts, and controlled provider-write readiness.',
                ['Open FB Marketing from the sidebar.', 'Review Dashboard, Configuration, Setup Wizard, and this User Manual first.', 'Reports render from stored snapshots; page loads do not call Meta directly.', 'Live campaign publish and budget updates stay disabled until a separate signed enablement.'],
                'A new operator opens Setup Wizard, completes connection health, selects assets, runs sync, then reviews Dashboard outcomes.',
                'Users understand which screen does what and which actions can eventually write to Meta.',
                ['Dashboard and reports are safe read-only surfaces.', 'Provider writes require permission, approval, queue readiness, and safety gates.'],
                'Never paste Meta credentials, access tokens, app secrets, or raw provider IDs into notes.',
                [['No data appears', 'Setup or sync is incomplete', 'Check Setup Wizard, connection health, selected assets, and latest sync.']]
            ),
            $this->section('setup-wizard', 'Use Setup Wizard first', 'feather-list', 'FB Marketing → Setup Wizard',
                'Setup Wizard shows the module checklist: migrations, connection, assets, queue, tracking, and writer launch gates.',
                ['Open Setup Wizard.', 'Review basic readiness cards.', 'Review the advanced checklist from FBM-15 through FBM-35.', 'Write down any blocked/missing item.', 'Open the related screen and complete that item.'],
                'FBM-35 blocked by Provider writer readiness and Dedicated queue means reporting is usable, but live Meta writes are not ready.',
                'Operators know the next setup action before they touch campaign workflows.',
                ['Use it after deployment and after permission/config changes.', 'Resolve migration pending items before using the related feature.'],
                'A clean Wizard does not automatically enable live writes.',
                [['Migration pending', 'Application migration not applied or checklist table mismatch', 'Run application migrations and check the exact table name.']]
            ),
            $this->section('connection-health', 'Connection and health test', 'feather-key', 'FB Marketing → Configuration',
                'Credentials are stored in the encrypted vault. Health tests are read-only and show token validity, scopes, expiry, and Graph version.',
                ['Open Configuration.', 'Add an encrypted Meta connection.', 'Save token/app secret/webhook verify token in password fields.', 'Run health test.', 'Review healthy/warning/failed status and missing scopes.'],
                'If health says `ads_management` is missing, reports may still work, but provider writer readiness will remain blocked.',
                'You know whether the connection is usable and what scope is missing.',
                ['Run health after every token rotation.', 'Fix health before asset discovery or sync.'],
                'Do not store secrets in notes or manual sections.',
                [['Health failed', 'Invalid/expired token or permission issue', 'Regenerate token in Meta and test again.']]
            ),
            $this->section('assets-selection', 'Discover and select assets', 'feather-layers', 'FB Marketing → Configuration / Assets',
                'Mirror available Ad Accounts, Pages, Pixels/Datasets, Catalogs, Audiences, and Product Sets, then select the intended assets.',
                ['Run asset discovery after a healthy connection.', 'Review available assets.', 'Select the intended Business, Ad Account, Page, Pixel/Dataset, and Catalog.', 'Review selection audit.', 'Run sync again after selection.'],
                'If multiple Ad Accounts exist, select only the production account intended for reporting or publishing.',
                'Reports and campaign flows use the correct local asset mirrors.',
                ['Do not select unavailable assets.', 'Re-run discovery after Meta permission changes.'],
                'Raw provider IDs remain hidden; select by safe names/statuses.',
                [['Asset missing', 'Business access or token scope missing', 'Fix Meta permission and run discovery again.']]
            ),
            $this->section('sync-refresh', 'Refresh sync and snapshots', 'feather-refresh-cw', 'FB Marketing → Configuration',
                'Reports use local snapshots. Sync reads Meta data and stores safe local summaries.',
                ['Use queued full read-only sync in production.', 'Use manual no-queue sync for small application-level testing.', 'Use drilldown refresh for campaign/ad set/ad tables.', 'Check latest sync status and freshness watermark.'],
                'Blank dashboard: health test, discover assets, select Ad Account, run sync, then refresh drilldowns.',
                'Dashboards and reports receive fresh stored data.',
                ['Use queue workers for production scale.', 'Manual no-queue is bounded and not a historical backfill.'],
                'Avoid overlapping sync runs on the same connection.',
                [['Queue blocked', 'Queue tables/worker/config missing', 'Run queue readiness and start the dedicated worker.']]
            ),
            $this->section('reporting', 'Read outcomes and reports', 'feather-bar-chart-2', 'FB Marketing → Dashboard / Performance / Attribution / Profitability / Reports',
                'Use stored Meta spend plus ERP sales/cost data to understand marketing outcomes.',
                ['Choose a date range.', 'Check freshness.', 'Open Performance for campaign/ad set/ad drilldowns.', 'Open Attribution and Profitability for ERP outcome.', 'Export reports after confirming filters.'],
                'High spend with low ERP attributed sales should be reviewed in Profitability before budget decisions.',
                'Marketing, finance, and management can use the same source rows.',
                ['Mixed currencies remain separated.', 'Summed daily reach is not exact unique reach.'],
                'Refresh stale snapshots before making decisions.',
                [['ROAS mismatch', 'Meta and ERP attribution boundaries differ', 'Review reporting definitions and source rows.']]
            ),
            $this->section('campaign-drafts', 'Plan campaigns safely', 'feather-edit-3', 'FB Marketing → Campaign Drafts / Creative Library / Audiences',
                'Plan campaign audiences, creatives, draft settings, approvals, and publish attempts before any provider write.',
                ['Prepare audience/product set.', 'Create and preflight a creative asset.', 'Create a campaign draft.', 'Set objective, budget, destination URL, and schedule.', 'Request approval and publish only after approval.'],
                'A product launch campaign uses selected audience, ready creative, approved draft, and budget within the safety cap.',
                'Approved snapshots are auditable and duplicate clicks reuse idempotent attempts.',
                ['QA creative links and images first.', 'Keep budgets under approved caps.'],
                'Do not bypass approval.',
                [['Publish disabled', 'Provider writes/readiness blocked', 'Check FBM-35 launch gate and writer readiness.']]
            ),
            $this->section('provider-writer', 'Understand live write safety', 'feather-shield', 'FB Marketing → Configuration / Campaign Drafts',
                'FBM-31 through FBM-35 guard live Meta writes. They are disabled by default and require signed enablement.',
                ['Check provider writer readiness.', 'Check the final launch gate.', 'Confirm dedicated queue readiness.', 'Publish approved drafts only.', 'Verify created objects start PAUSED.', 'Use operational action ledgers for pause/resume/budget/schedule.'],
                'A blocked gate means reporting is OK, but live publish or budget mutation is blocked.',
                'Live writes are controlled, auditable, and carry rollback/reconciliation summaries.',
                ['First UAT should use small budget and PAUSED objects.', 'Budget and schedule apply to ad sets only.', 'safe_edit remains blocked.'],
                'Do not bypass by editing config directly outside the signed release process.',
                [['Queued action not running', 'Worker stopped', 'Check `queue:work fb-marketing --queue=fb-marketing`.']]
            ),
            $this->section('lead-ads', 'Lead Ads to CRM', 'feather-user-plus', 'FB Marketing → Lead Ads',
                'Lead Ads webhooks can create mapped CRM leads from safe field data.',
                ['Configure webhook URL in Meta.', 'Save verify token in Configuration.', 'Open Lead Ads.', 'Review webhook logs and mapping.', 'Confirm CRM lead creation.'],
                'A form with name/phone/email can create a CRM lead; ID-only callbacks stay pending for retrieval.',
                'Sales users can follow up through CRM pipeline.',
                ['Keep form field names consistent.', 'Test with a Meta test lead.'],
                'Do not expose raw webhook payloads.',
                [['Lead missing', 'Webhook verify/subscription issue', 'Check callback route, token, and Meta app subscription.']]
            ),
            $this->section('catalog', 'Products and catalog diagnostics', 'feather-shopping-bag', 'FB Marketing → Products & Catalog',
                'Review product feed readiness, catalog mapping, product sets, and stock risk.',
                ['Open Products & Catalog.', 'Review feed-valid/excluded products.', 'Refresh catalog mappings.', 'Review automatic mapping.', 'Save manual override only if trusted and necessary.'],
                'Automatic mapping works when Meta retailer_id equals ERP product ID.',
                'Product-level performance and stock risk reports become reliable.',
                ['Fix missing image/title/price before feed use.', 'Record a reason for manual overrides.'],
                'Manual overrides are local only and do not edit Meta catalog items.',
                [['Unmatched product', 'Retailer ID or feed issue', 'Fix feed diagnostics and refresh mappings.']]
            ),
            $this->section('alerts', 'Health, alerts, and reconciliation', 'feather-alert-triangle', 'FB Marketing → Health & Alerts',
                'Review token expiry, sync failures, poor CTR, stock risk, optional overspend, and webhook recovery alerts.',
                ['Open Health & Alerts.', 'Review open and critical alerts.', 'Run reconciliation.', 'Open related reports.', 'Approve/dismiss recommendations after review.'],
                'A poor CTR alert should lead to creative/performance review before an operational action.',
                'Missed webhook or stale state can be recovered through local reconciliation and read-only sync.',
                ['Overspend threshold is opt-in.', 'Fix the source issue before closing alerts.'],
                'Recommendation approval is not an automatic Meta write.',
                [['Alert repeats', 'Root issue unresolved', 'Check health, sync log, and campaign state.']]
            ),
            $this->section('troubleshooting', 'Troubleshooting', 'feather-help-circle', 'FB Marketing → Setup Wizard / Configuration / Health & Alerts',
                'Use this order for common issues: setup, health, assets, sync, freshness, permissions, and launch gate.',
                ['Open Setup Wizard.', 'Check Configuration health and selected assets.', 'Check latest sync/API logs.', 'Run Health & Alerts reconciliation.', 'Verify role permissions.'],
                'For a blank dashboard, check migration, connection, selected Ad Account, successful sync, and date range.',
                'Most issues point to an exact missing item.',
                ['Check freshness before metrics.', 'Forbidden pages usually mean missing permission.'],
                'Do not manually edit database rows to retry or publish.',
                [['403 forbidden', 'Missing role permission', 'Grant the contextual permission and ask the user to refresh.'], ['Provider write blocked', 'Final launch gate not clean', 'Complete scope, queue, selected account, and signed enablement.']]
            ),
        ];
    }

    private function section(string $key, string $title, string $icon, string $menuPath, string $why, array $steps, string $example, string $result, array $tips, string $warning, array $troubleshooting = []): array
    {
        return compact('key', 'title', 'icon', 'menuPath', 'why', 'steps', 'example', 'result', 'tips', 'warning', 'troubleshooting');
    }

    private function quickLinks(string $locale): array
    {
        $labels = $locale === 'bn'
            ? ['Setup Wizard', 'Configuration', 'Health & Alerts', 'Campaign Drafts', 'Reports']
            : ['Setup Wizard', 'Configuration', 'Health & Alerts', 'Campaign Drafts', 'Reports'];

        return [
            ['label' => $labels[0], 'route' => 'fbMarketing.configuration.setup-wizard'],
            ['label' => $labels[1], 'route' => 'fbMarketing.configuration.index'],
            ['label' => $labels[2], 'route' => 'fbMarketing.alerts.index'],
            ['label' => $labels[3], 'route' => 'fbMarketing.campaign-drafts.index'],
            ['label' => $labels[4], 'route' => 'fbMarketing.reports.index'],
        ];
    }
}
