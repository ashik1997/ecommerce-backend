<?php

namespace App\Services\Crm;

class CrmUserManualContentService
{
    public function forLocale(string $locale): array
    {
        $locale = $locale === 'en' ? 'en' : 'bn';
        $isBn = $locale === 'bn';

        return [
            'locale' => $locale,
            'page_title' => $isBn ? 'CRM ব্যবহার নির্দেশিকা' : 'CRM User Manual',
            'page_heading' => $isBn ? 'CRM ব্যবহার নির্দেশিকা' : 'CRM User Manual',
            'hero_title' => $isBn ? 'CRM ব্যবহার নির্দেশিকা' : 'CRM User Manual',
            'hero_subtitle' => $isBn
                ? 'সহজ ভাষায় ধাপে ধাপে CRM ব্যবহার শিখুন। প্রতিটি topic-এ menu path, বাস্তব উদাহরণ, expected result এবং সতর্কতা দেওয়া আছে।'
                : 'Learn the CRM step by step. Every topic includes the menu path, a practical example, the expected result, and safety notes.',
            'hero_note' => $isBn
                ? 'এই manual read-only। এখানে কিছু submit, send বা database update হয় না।'
                : 'This manual is read-only. It does not submit, send, or update database records.',
            'switch_url' => route($isBn ? 'crm.user-manual.en' : 'crm.user-manual.bn'),
            'switch_text' => $isBn ? 'English Version' : 'বাংলা সংস্করণ',
            'labels' => $this->labels($locale),
            'quick_start' => $this->quickStart($locale),
            'daily_checklist' => $this->dailyChecklist($locale),
            'sections' => $isBn ? $this->sectionsBn() : $this->sectionsEn(),
        ];
    }

    protected function labels(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'index_title' => 'বিষয়ভিত্তিক সূচি',
                'index_help' => 'Topic লিখে খুঁজুন অথবা index থেকে section খুলুন।',
                'search_placeholder' => 'যেমন: campaign, customer, task',
                'quick_start_title' => 'দ্রুত শুরু করুন',
                'daily_checklist_title' => 'Daily operator checklist',
                'menu_path' => 'Menu path',
                'why' => 'এখানে কী হবে?',
                'steps' => 'ধাপে ধাপে ব্যবহার',
                'example' => 'সহজ উদাহরণ',
                'expected_result' => 'Expected result',
                'tips' => 'ভালোভাবে কাজ করার টিপস',
                'warning' => 'সতর্কতা',
                'table_problem' => 'সমস্যা',
                'table_reason' => 'সম্ভাব্য কারণ',
                'table_solution' => 'করণীয়',
                'back_to_top' => 'উপরে যান',
                'no_match' => 'কোনো matching topic পাওয়া যায়নি। অন্য keyword দিয়ে search করুন।',
                'section_count' => 'Tutorial sections',
                'language' => 'Language',
            ];
        }

        return [
            'index_title' => 'Topic index',
            'index_help' => 'Search by topic or open a section from the index.',
            'search_placeholder' => 'Example: campaign, customer, task',
            'quick_start_title' => 'Start quickly',
            'daily_checklist_title' => 'Daily operator checklist',
            'menu_path' => 'Menu path',
            'why' => 'What is this for?',
            'steps' => 'Step-by-step tutorial',
            'example' => 'Simple example',
            'expected_result' => 'Expected result',
            'tips' => 'Tips for safe operation',
            'warning' => 'Warning',
            'table_problem' => 'Problem',
            'table_reason' => 'Possible reason',
            'table_solution' => 'What to do',
            'back_to_top' => 'Back to top',
            'no_match' => 'No matching topic was found. Try another keyword.',
            'section_count' => 'Tutorial sections',
            'language' => 'Language',
        ];
    }

    protected function quickStart(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                ['icon' => 'feather-user', 'title' => 'Customer দেখুন', 'text' => 'Customer 360 থেকে customer-এর complete CRM context review করুন।', 'anchor' => 'customers-360'],
                ['icon' => 'feather-target', 'title' => 'Lead follow-up দিন', 'text' => 'Lead তৈরি করে owner, priority এবং next follow-up date দিন।', 'anchor' => 'leads-pipeline'],
                ['icon' => 'feather-check-square', 'title' => 'Task assign করুন', 'text' => 'User-কে due date সহ CRM task দিন এবং calendar-এ দেখুন।', 'anchor' => 'tasks-calendar'],
                ['icon' => 'feather-send', 'title' => 'Campaign বুঝুন', 'text' => 'Draft থেকে approval এবং controlled dispatch flow অনুসরণ করুন।', 'anchor' => 'campaign-governance'],
            ];
        }

        return [
            ['icon' => 'feather-user', 'title' => 'Review a customer', 'text' => 'Use Customer 360 to review the complete CRM context.', 'anchor' => 'customers-360'],
            ['icon' => 'feather-target', 'title' => 'Follow up a lead', 'text' => 'Create a lead with an owner, priority, and next follow-up date.', 'anchor' => 'leads-pipeline'],
            ['icon' => 'feather-check-square', 'title' => 'Assign a task', 'text' => 'Create a dated CRM task for a user and review it in the calendar.', 'anchor' => 'tasks-calendar'],
            ['icon' => 'feather-send', 'title' => 'Understand campaigns', 'text' => 'Follow the governed flow from draft through controlled dispatch.', 'anchor' => 'campaign-governance'],
        ];
    }

    protected function dailyChecklist(string $locale): array
    {
        if ($locale === 'bn') {
            return [
                'Overdue task এবং আজকের follow-up check করুন।',
                'New lead এবং urgent lead filter করে owner assign আছে কিনা দেখুন।',
                'Communication করার পরে history log করুন।',
                'Campaign পাঠানোর আগে audience, content এবং approval status review করুন।',
                'Permission change করলে affected user-কে refresh অথবা পুনরায় login করতে বলুন।',
            ];
        }

        return [
            'Review overdue tasks and today’s follow-ups.',
            'Filter new and urgent leads and confirm that an owner is assigned.',
            'Log communication after the real conversation takes place.',
            'Review audience, content, and approval status before campaign dispatch.',
            'After permission changes, ask the affected user to refresh or sign in again.',
        ];
    }

    protected function sectionsBn(): array
    {
        return [
            $this->section(
                'overview', 'CRM কী এবং কোথা থেকে শুরু করবেন?', 'feather-compass', 'CRM & CUSTOMERS',
                'CRM হলো customer relationship management layer। এটি existing order, payment, due, accounting, SMS, newsletter এবং support flow replace করে না; বরং customer-এর সাথে কাজগুলো organized করে।',
                [
                    'বাম sidebar থেকে CRM & CUSTOMERS module খুলুন।',
                    'প্রথমবার হলে Customers, CRM Leads, CRM Tasks এবং CRM Communications menuগুলো দেখে নিন।',
                    'Customer-related কাজ শুরু করার আগে সঠিক customer search করুন।',
                    'Campaign-related কাজ করলে draft status এবং permission দেখে action নিন।',
                ],
                'ধরুন, একজন পুরোনো customer আবার quotation চেয়েছে। Customer 360 খুলে আগের order, due, note এবং communication context দেখে তারপর নতুন follow-up task তৈরি করুন।',
                'User বুঝতে পারবেন কোন কাজ কোন CRM menu থেকে করতে হবে এবং কোন financial কাজ original ERP module-এ থাকবে।',
                ['CRM হলো visibility এবং follow-up layer। Accounting source-of-truth accounting module-এই থাকবে।'],
                'Customer due, payment বা ledger manual CRM record দিয়ে change করবেন না।'
            ),
            $this->section(
                'customer-setup', 'Customer setup: category, source এবং tag', 'feather-tag', 'CRM & CUSTOMERS → Customers',
                'Customer list clean এবং searchable রাখতে category, source type এবং tag ব্যবহার করুন। এগুলো reporting, filtering এবং segment তৈরিতে সাহায্য করে।',
                [
                    'Customer Categories থেকে প্রয়োজনীয় category তৈরি বা review করুন।',
                    'Customer Source Types থেকে customer কোথা থেকে এসেছে সেটি define করুন।',
                    'Customer Tags থেকে reusable tag তৈরি করুন।',
                    'Customer edit অথবা Customer 360 flow থেকে সঠিক tag assign করুন।',
                    'একই meaning-এর duplicate tag তৈরি না করে existing tag reuse করুন।',
                ],
                'Retail customer-এর জন্য category “Retail”, Facebook ad থেকে আসা customer-এর source “Facebook Ads”, এবং high-value buyer-এর tag “VIP” দিন।',
                'Customer filter এবং Portfolio Segment ব্যবহার করলে relevant customer group সহজে পাওয়া যাবে।',
                ['Short এবং clear tag name ব্যবহার করুন: VIP, Wholesale, Repeat Buyer, Service Due.'],
                'Tag মানেই financial status নয়। যেমন “Due Customer” tag দিলেও ledger due automatically change হবে না।'
            ),
            $this->section(
                'customers-360', 'Customers এবং Customer 360 ব্যবহার', 'feather-users', 'CRM & CUSTOMERS → Customers → All Customers',
                'Customer 360 একটি consolidated profile। এখানে notes, tags, CRM tasks, communications, leads এবং linked business context এক জায়গায় review করা যায়।',
                [
                    'All Customers খুলে নাম, phone অথবা available filter দিয়ে customer খুঁজুন।',
                    'সঠিক customer row থেকে profile বা Customer 360 খুলুন।',
                    'প্রয়োজন অনুযায়ী overview, notes, tags, task, communication এবং lead tab review করুন।',
                    'CRM note যোগ করলে short কিন্তু useful context লিখুন।',
                    'Order, payment, due এবং accounting action original module থেকে করুন।',
                ],
                'Customer “Rahim Traders” phone করে বলল আগামী সপ্তাহে ২০টি product লাগবে। Customer 360 খুলে note দিন: “20 units requested, call again on Sunday”, এরপর একটি task তৈরি করুন।',
                'পরবর্তী user Customer 360 খুলেই বুঝতে পারবে customer-এর সাথে শেষ কী কথা হয়েছে এবং পরবর্তী কাজ কী।',
                ['Customer note-এ sensitive credential লিখবেন না।', 'একই নামে একাধিক customer থাকলে phone verify করে profile খুলুন।'],
                'Customer 360 consolidated view হলেও এটি accounting ledger edit screen নয়।'
            ),
            $this->section(
                'leads-pipeline', 'Lead Worklist এবং Pipeline', 'feather-target', 'CRM & CUSTOMERS → CRM Leads → Lead Worklist / Lead Pipeline',
                'Confirmed customer বা order হওয়ার আগের sales opportunity track করতে Lead ব্যবহার করুন। Pipeline stage দেখে sales team বুঝতে পারে কোন opportunity কোথায় আছে।',
                [
                    'Lead Worklist খুলে Add Lead action ব্যবহার করুন।',
                    'Lead title, contact context, source, owner, priority এবং next follow-up date দিন।',
                    'Customer আগে থেকে থাকলে verified customer link করুন।',
                    'Pipeline থেকে stage-wise lead review করুন।',
                    'কাজ এগোলে status update করুন; অপ্রয়োজনীয় lead delete না করে archive করুন।',
                ],
                'Facebook inbox থেকে “Office Chair - 30 pcs” enquiry এসেছে। Lead title দিন “Office Chair 30 pcs - Karim Office”, priority High, owner Nila, next follow-up আগামীকাল।',
                'Pipeline-এ lead visible হবে এবং responsible user বুঝতে পারবে কবে follow-up করতে হবে।',
                ['Lead title এমন লিখুন যেন list দেখেই opportunity বোঝা যায়।', 'Owner ছাড়া lead রেখে দেবেন না।'],
                'Lead convert বা update করলেই automatically order, invoice, payment বা accounting entry তৈরি হয় না।'
            ),
            $this->section(
                'tasks-calendar', 'CRM Task এবং Follow-up Calendar', 'feather-calendar', 'CRM & CUSTOMERS → CRM Tasks → Task Worklist / Task Calendar',
                'Customer follow-up, reminder এবং user assignment track করতে CRM Task ব্যবহার করুন। Calendar date-wise workload বুঝতে সাহায্য করে।',
                [
                    'Task Worklist খুলে Add CRM Task করুন।',
                    'Customer, assigned user, title, priority এবং due date নির্বাচন করুন।',
                    'Task description-এ কী করতে হবে তা পরিষ্কার লিখুন।',
                    'Task Calendar খুলে date-wise pending task review করুন।',
                    'বাস্তব কাজ শেষ হলে task complete করুন এবং দরকার হলে completion note দিন।',
                ],
                'Rahim Traders-কে Sunday সকাল ১১টায় price confirm করার কথা। Task title দিন “Call Rahim Traders for final price”, due date Sunday 11:00 AM, priority High, assigned user Nila।',
                'Assigned user Task Worklist এবং Calendar উভয় জায়গায় task দেখতে পাবে। Complete করলে history audit trail থাকবে।',
                ['Overdue filter প্রতিদিন review করুন।', 'Title-এর সঙ্গে action verb ব্যবহার করুন: Call, Send quotation, Visit, Confirm payment.'],
                'কাজ না করে task complete mark করবেন না।'
            ),
            $this->section(
                'communications-activities', 'Communication History এবং Activity Audit', 'feather-message-circle', 'CRM & CUSTOMERS → CRM Communications → Communication History; CRM Activities → Activity History',
                'Phone, SMS, email, WhatsApp, meeting অথবা other communication-এর history log এবং CRM activity audit করার জন্য।',
                [
                    'Customer-এর সাথে বাস্তব যোগাযোগ শেষ হওয়ার পর Communication History খুলুন।',
                    'Add communication action থেকে customer, channel, direction, subject এবং summary দিন।',
                    'Filter দিয়ে customer, user, channel অথবা date অনুযায়ী history review করুন।',
                    'Activity History থেকে CRM event audit করুন।',
                    'Activity History review-only; edit করার চেষ্টা করবেন না।',
                ],
                'Nila customer-কে phone করে quotation পাঠিয়েছে। Communication log দিন: channel Phone, direction Outbound, subject “Quotation follow-up”, summary “Customer requested revised delivery date.”',
                'অন্য user পরে history দেখে conversation context বুঝতে পারবে এবং duplicate call কমবে।',
                ['Communication সত্যি হওয়ার পরে log করুন।', 'Summary ছোট হলেও decision-relevant রাখুন।'],
                'Manual communication log কোনো SMS বা email send করে না। এটি শুধু history record করে।'
            ),
            $this->section(
                'health-duplicates', 'Customer Health এবং Duplicate Review', 'feather-heart', 'CRM & CUSTOMERS → Customers → Customer Health / Duplicate Review',
                'Risk signal review এবং সম্ভাব্য duplicate customer record identify করতে এই read-only worklist ব্যবহার করুন।',
                [
                    'Customer Health খুলে risk signal এবং available filter review করুন।',
                    'High-risk বা attention-needed customer আলাদা করে দেখুন।',
                    'Duplicate Review খুলে একই phone, email বা similar identity-এর possible match review করুন।',
                    'Profile খুলে বাস্তব তথ্য verify করুন।',
                    'Merge অথবা destructive action না থাকলে original record safe রাখুন এবং operational note দিন।',
                ],
                '“Rahim Traders” এবং “Rahim Trade” দুই record-এ একই phone দেখা যাচ্ছে। Duplicate Review থেকে profile খুলে verify করুন কোনটি active operational record।',
                'Operator risky customer এবং possible duplicate সম্পর্কে informed decision নিতে পারবে।',
                ['Duplicate সন্দেহ হলে phone এবং order context মিলিয়ে দেখুন।'],
                'Read-only review worklist থেকে database record delete বা merge হয় না।'
            ),
            $this->section(
                'segments', 'Portfolio Segments এবং Saved Segments', 'feather-filter', 'CRM & CUSTOMERS → Customers → Portfolio Segments / Saved Segments',
                'Customer behavior অনুযায়ী group filter এবং reusable audience definition save করতে segment ব্যবহার করুন।',
                [
                    'Portfolio Segments খুলে প্রয়োজনীয় business filter নির্বাচন করুন।',
                    'Result count review করুন এবং কয়েকটি customer spot-check করুন।',
                    'Useful filter combination হলে Saved Segments-এ save করুন।',
                    'Saved segment-এর clear নাম দিন যাতে campaign creator purpose বুঝতে পারে।',
                    'Campaign audience হিসেবে ব্যবহারের আগে আবার count review করুন।',
                ],
                'যেসব repeat customer গত ৯০ দিনে order করেছে তাদের জন্য segment নাম দিন “Repeat buyers - last 90 days”। পরে SMS campaign draft-এ এই segment select করুন।',
                'Team একই audience logic বারবার recreate না করে saved definition reuse করতে পারবে।',
                ['Segment name-এ audience এবং time window লিখুন।', 'Campaign-এর আগে segment count এবং sample customer review করুন।'],
                'Segment customer financial record change করে না; এটি selection definition।'
            ),
            $this->section(
                'contact-management', 'Legacy Contact Management', 'feather-phone', 'CRM & CUSTOMERS → Contact Management',
                'Existing Contact History, Scheduled Contacts এবং Contact Requests flow preserve করা হয়েছে। পুরোনো operational process যেখানে প্রয়োজন সেখানে এটি ব্যবহার করুন।',
                [
                    'Contact History থেকে legacy contact records দেখুন।',
                    'Scheduled Contacts থেকে existing scheduled-contact flow review করুন।',
                    'Contact Requests থেকে website বা external request follow-up করুন।',
                    'নতুন structured CRM follow-up দরকার হলে CRM Tasks এবং Communication History ব্যবহার করুন।',
                ],
                'Website contact request এসেছে। Contact Requests থেকে request review করুন, customer-কে call করুন, তারপর CRM Communication History-তে conversation log এবং CRM Task-এ next follow-up দিন।',
                'Legacy flow নষ্ট না করে নতুন CRM history এবং follow-up layer ব্যবহার করা যাবে।',
                ['পুরোনো workflow চললে হঠাৎ replace না করে team process অনুযায়ী gradual adoption করুন।'],
                'Legacy scheduled-contact record এবং CRM Task আলাদা record; প্রয়োজন অনুযায়ী সঠিক flow ব্যবহার করুন।'
            ),
            $this->section(
                'campaign-governance', 'Campaign Draft: create, review এবং approval', 'feather-file-text', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'Saved segment ব্যবহার করে controlled SMS অথবা Email campaign draft তৈরি করুন। Campaign action approval এবং immutable ledger boundary অনুসরণ করে।',
                [
                    'Campaign Drafts খুলে Add Draft করুন।',
                    'Channel হিসেবে BulkSMSBD SMS অথবা Email নির্বাচন করুন।',
                    'Saved segment select করে audience count preview করুন।',
                    'Message content review করে draft save করুন।',
                    'Content এবং audience ready হলে Submit for Review করুন।',
                    'Draft creator ছাড়া অন্য authorized user draft approve অথবা reject করবে।',
                    'Approved draft থেকে snapshot preparation, run release, batch claim এবং provider-attempt ledger step permission অনুযায়ী করুন।',
                ],
                'Segment “Repeat buyers - last 90 days” select করে SMS draft লিখুন: “প্রিয় গ্রাহক, এই সপ্তাহে selected items-এ বিশেষ অফার চলছে।” Audience preview ৪ জন হলে content review করে submit করুন।',
                'Approved campaign একটি controlled, auditable flow অনুসরণ করবে; audience এবং ledger step trace করা যাবে।',
                ['Campaign creator এবং approver আলাদা user রাখুন।', 'Audience preview এবং message spelling submit-এর আগে check করুন।'],
                'Draft তৈরি করলেই message send হয় না। প্রতিটি controlled step বুঝে action নিন।'
            ),
            $this->section(
                'sms-campaign', 'BulkSMSBD SMS: bounded real-send flow', 'feather-send', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'CRM campaign-এর একমাত্র bounded real-send transport হলো BulkSMSBD SMS। Server-side safety boundary আছে এবং automatic retry নেই।',
                [
                    'Approved BulkSMSBD SMS draft-এর immutable snapshot prepare করুন।',
                    'Provider-neutral dispatch run release করুন।',
                    'Manual execution batch claim করুন।',
                    'Bounded provider-attempt ledger prepare করুন।',
                    'Real SMS execution button ব্যবহার করার আগে recipient count এবং message আবার review করুন।',
                    'SweetAlert2 prompt-এ SEND SMS ঠিকভাবে লিখে confirm করুন।',
                    'Result এবং attempt event review করুন; browser থেকে blind retry করবেন না।',
                ],
                'Audience ৪ জন। Ledger prepare করার পরে execute action চাপলে prompt আসবে। SEND SMS লিখে confirm করলে system recipient প্রতি HTTPS POST করে bounded send attempt record করবে।',
                'সর্বোচ্চ ৫ recipient-এর bounded SMS execution হবে এবং append-only attempt history থাকবে।',
                ['প্রথম campaign ছোট audience দিয়ে শুরু করুন।', 'Failure হলে attempt history এবং log review করে তারপর সিদ্ধান্ত নিন।'],
                'এক attempt-এ maximum ৫ recipient। Auto retry disabled। একই button বারবার চাপবেন না।'
            ),
            $this->section(
                'email-campaign', 'Email Campaign: ledger-only boundary', 'feather-mail', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'Email campaign draft এবং controlled ledger preparation available, কিন্তু real SMTP execution intentionally disabled।',
                [
                    'Email channel দিয়ে campaign draft create করুন।',
                    'Subject, message এবং saved segment audience review করুন।',
                    'Submit for Review এবং approval flow complete করুন।',
                    'Permission থাকলে snapshot, run, batch এবং provider-attempt ledger prepare করুন।',
                    'Real send button expect করবেন না; Email বর্তমানে non-sending boundary-তে থাকবে।',
                ],
                '“June service reminder” নামে Email draft তৈরি করে subject দিন “Service reminder for your device”। Approval-এর পরে ledger steps prepare করা যাবে, কিন্তু SMTP send হবে না।',
                'Email campaign future execution-এর জন্য auditable readiness ledger পর্যন্ত advance করবে।',
                ['Email draft-এ subject অবশ্যই clear রাখুন।', 'Real send দরকার হলে approved operational process ব্যবহার করুন; CRM Email send enabled ধরে নেবেন না।'],
                'CRM Email campaign থেকে real SMTP email পাঠানো currently disabled।'
            ),
            $this->section(
                'legacy-sms-newsletter', 'Legacy SMS এবং Newsletter', 'feather-inbox', 'CRM & CUSTOMERS → SMS / Newsletter Subscribers',
                'Existing SMS এবং newsletter flow preserve করা হয়েছে। CRM campaign governance-এর সঙ্গে legacy flow mix না করে সঠিক menu ব্যবহার করুন।',
                [
                    'Existing BulkSMSBD management action দরকার হলে SMS menu খুলুন।',
                    'Subscriber management দরকার হলে Newsletter Subscribers খুলুন।',
                    'Approval, frozen audience এবং campaign ledger দরকার হলে Campaign Drafts ব্যবহার করুন।',
                    'Legacy newsletter send এবং CRM Email ledger-only boundary আলাদা হিসেবে বুঝুন।',
                ],
                'শুধু subscriber list update করতে হলে Newsletter Subscribers ব্যবহার করুন। Repeat buyer SMS campaign approvalসহ পাঠাতে হলে CRM Campaign Drafts ব্যবহার করুন।',
                'Operator প্রয়োজন অনুযায়ী legacy utility flow এবং governed CRM campaign flow আলাদা করে ব্যবহার করতে পারবে।',
                ['Menu নির্বাচন করার আগে কাজটি legacy utility নাকি governed campaign সেটি নির্ধারণ করুন।'],
                'Legacy flow আছে বলে CRM campaign approval bypass করবেন না।'
            ),
            $this->section(
                'troubleshooting', 'Common problem এবং solution', 'feather-life-buoy', 'CRM User Manual → Troubleshooting',
                'Page দেখা যাচ্ছে না, form submit হচ্ছে না অথবা campaign step আটকে গেলে প্রথমে basic checks করুন।',
                [
                    'User logged in এবং active কিনা check করুন।',
                    'Role Sidebar Permissions থেকে required CRM permission আছে কিনা দেখুন।',
                    'Permission change-এর পরে page refresh অথবা পুনরায় login করুন।',
                    'Form submit fail হলে inline validation message পড়ুন।',
                    'Campaign fail হলে draft status, approval status, channel এবং typed confirmation check করুন।',
                    'Request এখনও fail হলে Laravel log review করুন।',
                ],
                'User Task Calendar দেখতে পাচ্ছে না। Role Sidebar Permissions খুলে crm.tasks.calendar read permission আছে কিনা দেখুন, save করে user-কে পুনরায় login করতে বলুন।',
                'Common configuration problem দ্রুত identify করা যাবে এবং unsafe database edit এড়ানো যাবে।',
                ['UI error text copy করে log review করলে root cause দ্রুত পাওয়া যায়।'],
                'Campaign retry করার জন্য database row manually edit করবেন না।',
                [
                    ['Page menu দেখা যাচ্ছে না', 'Role permission নেই অথবা session refresh হয়নি', 'Role Sidebar Permissions check করে save করুন; user refresh বা পুনরায় login করুক'],
                    ['Form submit হচ্ছে না', 'Required field missing অথবা validation error', 'Inline error পড়ে field ঠিক করে একবার submit করুন'],
                    ['SMS execute option নেই', 'Draft SMS নয়, approval/ledger step incomplete, অথবা permission নেই', 'Channel, status এবং crm.campaign-drafts.execute-dispatch permission review করুন'],
                    ['Email send option নেই', 'Email real-send intentionally disabled', 'Ledger-only boundary হিসেবে ব্যবহার করুন'],
                    ['Segment audience unexpected', 'Filter definition অথবা current customer data বদলেছে', 'Saved segment filter, count এবং sample customer review করুন'],
                ]
            ),
            $this->section(
                'user-management', 'শেষ ধাপ: User Module এবং permission setup', 'feather-shield', 'USER MANAGEMENT → System Users / Roles & Permissions → User Roles / Role Sidebar Permissions',
                'কে কোন CRM menu দেখবে এবং কোন action করতে পারবে তা User Module থেকে control করুন। Least-privilege permission ব্যবহার করুন।',
                [
                    'System Users থেকে user active আছে কিনা review করুন।',
                    'User Roles থেকে প্রয়োজনীয় role তৈরি বা assign করুন।',
                    'Role Sidebar Permissions খুলে CRM & CUSTOMERS section expand করুন।',
                    'Normal operator-কে শুধু প্রয়োজনীয় read/create permission দিন।',
                    'Campaign creator এবং approver permission আলাদা user বা role-এ রাখুন।',
                    'SMS execute permission শুধু trusted operator-কে দিন।',
                    'Save করার পরে affected user-কে refresh অথবা পুনরায় login করতে বলুন।',
                ],
                'Nila শুধু customer follow-up করবে। তাকে CRM Customers read, CRM Leads read/create, CRM Tasks read/create/complete, Communication History read/create এবং User Manual read দিন। SMS execute permission দেবেন না।',
                'প্রতিটি user তার দায়িত্ব অনুযায়ী menu এবং action দেখবে; risky campaign execution সীমিত থাকবে।',
                ['Role name দায়িত্বভিত্তিক রাখুন: CRM Operator, CRM Supervisor, Campaign Approver.', 'নতুন permission দেওয়ার আগে user-এর বাস্তব কাজ verify করুন।'],
                'সব permission সবাইকে দেবেন না। বিশেষ করে approval এবং SMS execution permission সীমিত রাখুন।'
            ),
        ];
    }

    protected function sectionsEn(): array
    {
        return [
            $this->section(
                'overview', 'What CRM is and where to start', 'feather-compass', 'CRM & CUSTOMERS',
                'CRM is the customer-relationship layer of the ERP. It organizes customer work without replacing existing order, payment, due, accounting, SMS, newsletter, or support flows.',
                [
                    'Open CRM & CUSTOMERS from the left sidebar.',
                    'On your first visit, review Customers, CRM Leads, CRM Tasks, and CRM Communications.',
                    'Before a customer action, search for the correct customer record.',
                    'Before a campaign action, confirm the draft status and your permission.',
                ],
                'A previous customer asks for another quotation. Open Customer 360, review earlier orders, dues, notes, and communication context, then create a follow-up task.',
                'The operator understands which CRM menu to use and which financial actions must remain in the original ERP modules.',
                ['CRM is a visibility and follow-up layer. Accounting remains the financial source of truth.'],
                'Do not change dues, payments, or ledger values through manual CRM records.'
            ),
            $this->section(
                'customer-setup', 'Customer setup: category, source, and tag', 'feather-tag', 'CRM & CUSTOMERS → Customers',
                'Use categories, source types, and tags to keep customer records searchable and useful for filtering and segmentation.',
                [
                    'Review or create the required values under Customer Categories.',
                    'Use Customer Source Types to define where a customer came from.',
                    'Create reusable labels under Customer Tags.',
                    'Assign the correct tags through the customer edit or Customer 360 flow.',
                    'Reuse an existing tag instead of creating duplicates with the same meaning.',
                ],
                'For a retail customer from a Facebook ad, use category “Retail”, source “Facebook Ads”, and tag “VIP” when the buyer is high value.',
                'Customer filtering and Portfolio Segments can locate relevant groups more easily.',
                ['Use short, clear tag names: VIP, Wholesale, Repeat Buyer, Service Due.'],
                'A tag is not a financial status. Adding a “Due Customer” tag does not update the accounting ledger.'
            ),
            $this->section(
                'customers-360', 'Customers and Customer 360', 'feather-users', 'CRM & CUSTOMERS → Customers → All Customers',
                'Customer 360 is the consolidated profile for reviewing notes, tags, CRM tasks, communications, leads, and linked business context.',
                [
                    'Open All Customers and search by name, phone, or an available filter.',
                    'Open the correct row’s profile or Customer 360 page.',
                    'Review overview, notes, tags, task, communication, and lead tabs as required.',
                    'When adding a CRM note, keep it short but useful.',
                    'Perform order, payment, due, and accounting actions in their original modules.',
                ],
                'Rahim Traders asks for 20 units next week. Open Customer 360, add the note “20 units requested, call again on Sunday”, and create a dated follow-up task.',
                'The next operator can understand the latest context and the next required action from one place.',
                ['Do not put credentials or secrets in customer notes.', 'When names are similar, verify the phone number before opening a profile.'],
                'Customer 360 is a consolidated view, not an accounting-ledger edit screen.'
            ),
            $this->section(
                'leads-pipeline', 'Lead Worklist and Pipeline', 'feather-target', 'CRM & CUSTOMERS → CRM Leads → Lead Worklist / Lead Pipeline',
                'Use leads to track sales opportunities before they become confirmed customers or orders. The Pipeline shows where opportunities are in the sales process.',
                [
                    'Open Lead Worklist and use Add Lead.',
                    'Enter a clear title, contact context, source, owner, priority, and next follow-up date.',
                    'Link a verified existing customer when applicable.',
                    'Review stage-based opportunities in Lead Pipeline.',
                    'Update status as work progresses; archive irrelevant leads instead of deleting history.',
                ],
                'A Facebook inbox enquiry asks for 30 office chairs. Create “Office Chair 30 pcs - Karim Office”, set priority High, owner Nila, and next follow-up tomorrow.',
                'The lead is visible in Pipeline and the responsible user knows when to follow up.',
                ['Write a title that explains the opportunity in the list.', 'Do not leave a lead without an owner.'],
                'Updating or converting a lead does not automatically create an order, invoice, payment, or accounting entry.'
            ),
            $this->section(
                'tasks-calendar', 'CRM Tasks and Follow-up Calendar', 'feather-calendar', 'CRM & CUSTOMERS → CRM Tasks → Task Worklist / Task Calendar',
                'Use CRM tasks for customer follow-up, reminders, and user assignments. The calendar provides a date-based workload view.',
                [
                    'Open Task Worklist and choose Add CRM Task.',
                    'Select the customer, assigned user, title, priority, and due date.',
                    'Write a clear description of the action required.',
                    'Review pending work by date in Task Calendar.',
                    'Complete the task only after the real action is done and add a completion note when useful.',
                ],
                'Rahim Traders needs a final price call on Sunday at 11:00 AM. Create “Call Rahim Traders for final price”, assign Nila, and set priority High.',
                'The assigned user can see the task in both the worklist and calendar. Completion remains auditable.',
                ['Review the Overdue filter every day.', 'Start titles with an action: Call, Send quotation, Visit, Confirm payment.'],
                'Do not mark a task complete before the real work is completed.'
            ),
            $this->section(
                'communications-activities', 'Communication History and Activity Audit', 'feather-message-circle', 'CRM & CUSTOMERS → CRM Communications → Communication History; CRM Activities → Activity History',
                'Log phone, SMS, email, WhatsApp, meeting, or other communication history and audit CRM events.',
                [
                    'After the real conversation, open Communication History.',
                    'Use Add communication and enter customer, channel, direction, subject, and summary.',
                    'Filter history by customer, user, channel, or date as needed.',
                    'Open Activity History to audit CRM events.',
                    'Treat Activity History as read-only; it is not an edit screen.',
                ],
                'Nila calls a customer after sending a quotation. Log channel Phone, direction Outbound, subject “Quotation follow-up”, and summary “Customer requested revised delivery date.”',
                'Another operator can understand the conversation and avoid duplicate follow-up calls.',
                ['Log communication after it actually happens.', 'Keep summaries short but decision-relevant.'],
                'A manual communication log records history only. It does not send SMS or email.'
            ),
            $this->section(
                'health-duplicates', 'Customer Health and Duplicate Review', 'feather-heart', 'CRM & CUSTOMERS → Customers → Customer Health / Duplicate Review',
                'Use these read-only worklists to review customer risk signals and possible duplicate records.',
                [
                    'Open Customer Health and review risk signals and available filters.',
                    'Separate customers needing attention from normal records.',
                    'Open Duplicate Review and inspect possible matches by phone, email, or similar identity.',
                    'Open profiles and verify real-world information.',
                    'If there is no approved merge action, preserve original records and leave an operational note.',
                ],
                '“Rahim Traders” and “Rahim Trade” share the same phone number. Open both profiles from Duplicate Review and verify the active operational record.',
                'The operator can make an informed decision about risky customers and possible duplicates.',
                ['When a duplicate is suspected, compare phone and order context.'],
                'These worklists do not delete or merge customer records.'
            ),
            $this->section(
                'segments', 'Portfolio Segments and Saved Segments', 'feather-filter', 'CRM & CUSTOMERS → Customers → Portfolio Segments / Saved Segments',
                'Use segments to filter customer groups by business behavior and save reusable audience definitions.',
                [
                    'Open Portfolio Segments and select the required business filters.',
                    'Review the result count and spot-check a few customers.',
                    'Save useful filter combinations under Saved Segments.',
                    'Use a clear saved-segment name so campaign creators understand the purpose.',
                    'Review the count again before using a segment as a campaign audience.',
                ],
                'Save customers who ordered in the last 90 days as “Repeat buyers - last 90 days”, then select that segment in an SMS campaign draft.',
                'The team can reuse the same audience logic instead of rebuilding filters each time.',
                ['Include the audience type and time window in the segment name.', 'Review segment count and sample customers before a campaign.'],
                'A segment is a selection definition. It does not change customer financial records.'
            ),
            $this->section(
                'contact-management', 'Legacy Contact Management', 'feather-phone', 'CRM & CUSTOMERS → Contact Management',
                'Existing Contact History, Scheduled Contacts, and Contact Requests flows are preserved for operational continuity.',
                [
                    'Use Contact History to review legacy contact records.',
                    'Use Scheduled Contacts for the existing scheduled-contact flow.',
                    'Use Contact Requests for website or external follow-up requests.',
                    'For structured new CRM work, use CRM Tasks and Communication History.',
                ],
                'A website contact request arrives. Review it under Contact Requests, call the customer, log the conversation in Communication History, and create a CRM Task for the next step.',
                'Legacy workflows remain available while the newer CRM history and follow-up layer is adopted gradually.',
                ['Adopt the newer CRM flow gradually when an older process is still active.'],
                'A legacy scheduled-contact record and a CRM Task are separate records. Use the correct flow for the work.'
            ),
            $this->section(
                'campaign-governance', 'Campaign Draft: create, review, and approve', 'feather-file-text', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'Create governed SMS or Email campaign drafts from saved segments. Campaign actions follow approval and immutable-ledger boundaries.',
                [
                    'Open Campaign Drafts and choose Add Draft.',
                    'Select BulkSMSBD SMS or Email as the channel.',
                    'Choose a saved segment and preview the audience count.',
                    'Review message content and save the draft.',
                    'When content and audience are ready, submit the draft for review.',
                    'A different authorized user must approve or reject the draft.',
                    'For an approved draft, perform snapshot preparation, run release, batch claim, and provider-attempt ledger steps according to permission.',
                ],
                'Select “Repeat buyers - last 90 days” and write an SMS draft: “Dear customer, selected items have a special offer this week.” Preview 4 recipients, review the text, and submit it.',
                'The campaign follows a controlled, auditable path with traceable audience and ledger steps.',
                ['Keep campaign creator and approver permissions on different users.', 'Check audience preview and spelling before submission.'],
                'Creating a draft does not send a message. Understand each controlled step before continuing.'
            ),
            $this->section(
                'sms-campaign', 'BulkSMSBD SMS: bounded real-send flow', 'feather-send', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'BulkSMSBD SMS is the only bounded real-send transport in CRM campaigns. It has a server-side safety cap and no automatic retry.',
                [
                    'Prepare the immutable snapshot for an approved BulkSMSBD SMS draft.',
                    'Release the provider-neutral dispatch run.',
                    'Claim the manual execution batch.',
                    'Prepare the bounded provider-attempt ledger.',
                    'Before real execution, recheck recipient count and message content.',
                    'Type SEND SMS exactly in the SweetAlert2 prompt and confirm.',
                    'Review attempt events and results; do not issue blind browser retries.',
                ],
                'The audience contains 4 recipients. After the ledger is prepared, execute the attempt and type SEND SMS. The system performs a bounded recipient-by-recipient HTTPS POST attempt and records events.',
                'A maximum of 5 recipients can be attempted and append-only history remains available.',
                ['Start the first campaign with a small audience.', 'After failure, review attempt history and logs before deciding what to do next.'],
                'Maximum 5 recipients per attempt. Auto retry is disabled. Do not press execute repeatedly.'
            ),
            $this->section(
                'email-campaign', 'Email Campaign: ledger-only boundary', 'feather-mail', 'CRM & CUSTOMERS → CRM Communications → Campaign Drafts',
                'Email draft governance and ledger preparation are available, but real SMTP execution is intentionally disabled.',
                [
                    'Create a campaign draft with the Email channel.',
                    'Review subject, message, and saved-segment audience.',
                    'Complete submit-for-review and approval steps.',
                    'When permitted, prepare snapshot, run, batch, and provider-attempt ledger steps.',
                    'Do not expect a real-send button; Email remains within the non-sending boundary.',
                ],
                'Create “June service reminder” with subject “Service reminder for your device”. After approval, ledger steps can be prepared, but SMTP email is not sent.',
                'Email campaigns can advance to an auditable readiness ledger for future execution work.',
                ['Use a clear subject.', 'Do not assume CRM Email sending is enabled when a real operational send is needed.'],
                'Real SMTP email sending from CRM campaigns is currently disabled.'
            ),
            $this->section(
                'legacy-sms-newsletter', 'Legacy SMS and Newsletter', 'feather-inbox', 'CRM & CUSTOMERS → SMS / Newsletter Subscribers',
                'Existing SMS and newsletter flows remain available. Use the correct menu without mixing legacy utilities with governed campaign expectations.',
                [
                    'Use SMS for existing BulkSMSBD management actions.',
                    'Use Newsletter Subscribers for subscriber management.',
                    'Use Campaign Drafts when approval, frozen audience, and campaign ledger governance are required.',
                    'Treat legacy newsletter sending and CRM Email ledger-only behavior as separate flows.',
                ],
                'Use Newsletter Subscribers when only subscriber data needs maintenance. Use CRM Campaign Drafts when a repeat-buyer SMS campaign needs approval and an audit ledger.',
                'Operators can choose the correct legacy or governed flow for each job.',
                ['Before opening a menu, decide whether the job is a legacy utility action or a governed campaign.'],
                'Do not bypass campaign approval merely because a legacy utility flow exists.'
            ),
            $this->section(
                'troubleshooting', 'Common problems and solutions', 'feather-life-buoy', 'CRM User Manual → Troubleshooting',
                'Start with basic checks when a page is hidden, a form cannot submit, or a campaign action is blocked.',
                [
                    'Confirm that the user is active and signed in.',
                    'Check the required CRM permission under Role Sidebar Permissions.',
                    'After permission changes, refresh the page or sign in again.',
                    'Read inline validation messages when form submission fails.',
                    'For campaign problems, check draft status, approval status, channel, and typed confirmation.',
                    'Review Laravel logs when the request still fails.',
                ],
                'A user cannot see Task Calendar. Check crm.tasks.calendar read permission under Role Sidebar Permissions, save, and ask the user to sign in again.',
                'Common configuration issues can be diagnosed without unsafe database edits.',
                ['Copy the UI error text when reviewing logs to locate the root cause quickly.'],
                'Do not manually edit database rows to retry campaign execution.',
                [
                    ['A page menu is hidden', 'Role permission is missing or the session was not refreshed', 'Check Role Sidebar Permissions, save, and refresh or sign in again'],
                    ['A form cannot submit', 'A required field is missing or validation failed', 'Read the inline error, fix the field, and submit once'],
                    ['SMS execute action is missing', 'The draft is not SMS, ledger steps are incomplete, or permission is missing', 'Review channel, status, and crm.campaign-drafts.execute-dispatch permission'],
                    ['Email send action is missing', 'Real Email sending is intentionally disabled', 'Use Email as a ledger-only boundary'],
                    ['Segment audience is unexpected', 'Filter definition or current customer data has changed', 'Review saved filters, count, and sample customers'],
                ]
            ),
            $this->section(
                'user-management', 'Final step: User Module and permissions', 'feather-shield', 'USER MANAGEMENT → System Users / Roles & Permissions → User Roles / Role Sidebar Permissions',
                'Control which CRM menus and actions each user can access. Use least-privilege permissions.',
                [
                    'Review whether the user is active under System Users.',
                    'Create or assign the appropriate role under User Roles.',
                    'Open Role Sidebar Permissions and expand CRM & CUSTOMERS.',
                    'Grant normal operators only the read and create permissions they genuinely need.',
                    'Keep campaign creator and approver permissions on different users or roles.',
                    'Grant SMS execute permission only to trusted operators.',
                    'After saving, ask the affected user to refresh or sign in again.',
                ],
                'Nila only handles customer follow-up. Grant customer read, lead read/create, task read/create/complete, communication read/create, and User Manual read. Do not grant SMS execute permission.',
                'Each user sees menus and actions appropriate to their responsibility while risky campaign execution remains restricted.',
                ['Use responsibility-based role names: CRM Operator, CRM Supervisor, Campaign Approver.', 'Verify real job duties before granting a new permission.'],
                'Do not give every permission to every user. Keep approval and SMS execution tightly restricted.'
            ),
        ];
    }

    protected function section(
        string $id,
        string $title,
        string $icon,
        string $menu,
        string $summary,
        array $steps,
        string $example,
        string $expectedResult,
        array $tips = [],
        ?string $warning = null,
        array $troubleshooting = []
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'icon' => $icon,
            'menu' => $menu,
            'summary' => $summary,
            'steps' => $steps,
            'example' => $example,
            'expected_result' => $expectedResult,
            'tips' => $tips,
            'warning' => $warning,
            'troubleshooting' => $troubleshooting,
        ];
    }
}
