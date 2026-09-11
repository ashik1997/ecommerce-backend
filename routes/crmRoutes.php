<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactRequestontroller;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\SubscribedUsersController;
use App\Http\Controllers\Outlet\CustomerSourceController;
use App\Http\Controllers\Customer\CustomerCategoryController;
use App\Http\Controllers\Customer\CustomerEcommerceController;
use App\Http\Controllers\Customer\CustomerContactHistoryController;
use App\Http\Controllers\Customer\CustomerNextContactDateController;
use App\Http\Controllers\Customer\BulkSmsBdManagementController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Crm\CrmCustomerTagController;
use App\Http\Controllers\Crm\CrmCustomerProfileController;
use App\Http\Controllers\Crm\CrmCustomerNoteController;
use App\Http\Controllers\Crm\CrmTaskController;
use App\Http\Controllers\Crm\CrmCommunicationController;
use App\Http\Controllers\Crm\CrmActivityController;
use App\Http\Controllers\Crm\CrmCustomerHealthController;
use App\Http\Controllers\Crm\CrmDuplicateCustomerController;
use App\Http\Controllers\Crm\CrmCustomerSegmentController;
use App\Http\Controllers\Crm\CrmSavedCustomerSegmentController;
use App\Http\Controllers\Crm\CrmCampaignDraftController;
use App\Http\Controllers\Crm\CrmCampaignDispatchPreparationController;
use App\Http\Controllers\Crm\CrmCampaignDispatchRunController;
use App\Http\Controllers\Crm\CrmCampaignDispatchExecutionBatchController;
use App\Http\Controllers\Crm\CrmCampaignDispatchAttemptController;
use App\Http\Controllers\Crm\CrmCampaignDispatchExecutionController;
use App\Http\Controllers\Crm\CrmLeadController;
use App\Http\Controllers\Crm\CrmUserManualController;

Route::group(['middleware' => ['auth', 'CheckUserType', 'DemoMode']], function () {

    // Customer Source Type 
    Route::get('/add/new/customer-source', [CustomerSourceController::class, 'addNewCustomerSource'])->name('AddNewCustomerSource');    
    Route::post('/save/new/customer-source', [CustomerSourceController::class, 'saveNewCustomerSource'])->name('SaveNewCustomerSource');
    Route::get('/view/all/customer-source', [CustomerSourceController::class, 'viewAllCustomerSource'])->name('ViewAllCustomerSource');
    Route::get('/delete/customer-source/{slug}', [CustomerSourceController::class, 'deleteCustomerSource'])->name('DeleteCustomerSource');
    Route::get('/edit/customer-source/{slug}', [CustomerSourceController::class, 'editCustomerSource'])->name('EditCustomerSource');      
    Route::post('/update/customer-source', [CustomerSourceController::class, 'updateCustomerSource'])->name('UpdateCustomerSource');
    
    // import customer 
    Route::post('/import/customers', [CustomerController::class, 'importCustomerData'])->name('ImportCustomers');

    // Customer Category 
    Route::get('/add/new/customer-category', [CustomerCategoryController::class, 'addNewCustomerCategory'])->name('AddNewCustomerCategory');    
    Route::post('/save/new/customer-category', [CustomerCategoryController::class, 'saveNewCustomerCategory'])->name('SaveNewCustomerCategory');
    Route::get('/view/all/customer-category', [CustomerCategoryController::class, 'viewAllCustomerCategory'])->name('ViewAllCustomerCategory');
    Route::get('/delete/customer-category/{slug}', [CustomerCategoryController::class, 'deleteCustomerCategory'])->name('DeleteCustomerCategory');
    Route::get('/edit/customer-category/{slug}', [CustomerCategoryController::class, 'editCustomerCategory'])->name('EditCustomerCategory');      
    Route::post('/update/customer-category', [CustomerCategoryController::class, 'updateCustomerCategory'])->name('UpdateCustomerCategory');

    // Customer Ecommerce
    Route::get('/add/new/customer-ecommerce', [CustomerEcommerceController::class, 'addNewCustomerEcommerce'])->name('AddNewCustomerEcommerce');    
    Route::post('/save/new/customer-ecommerce', [CustomerEcommerceController::class, 'saveNewCustomerEcommerce'])->name('SaveNewCustomerEcommerce');
    Route::get('/view/all/customer-ecommerce', [CustomerEcommerceController::class, 'viewAllCustomerEcommerce'])->name('ViewAllCustomerEcommerce');
    Route::get('/delete/customer-ecommerce/{slug}', [CustomerEcommerceController::class, 'deleteCustomerEcommerce'])->name('DeleteCustomerEcommerce');
    Route::get('/edit/customer-ecommerce/{slug}', [CustomerEcommerceController::class, 'editCustomerEcommerce'])->name('EditCustomerEcommerce');      
    Route::post('/update/customer-ecommerce', [CustomerEcommerceController::class, 'updateCustomerEcommerce'])->name('UpdateCustomerEcommerce');

    
    // Customer Contact History
    Route::get('/add/new/customer-contact-history', [CustomerContactHistoryController::class, 'addNewCustomerContactHistory'])->name('AddNewCustomerContactHistories');    
    Route::post('/save/new/customer-contact-history', [CustomerContactHistoryController::class, 'saveNewCustomerContactHistory'])->name('SaveNewCustomerContactHistories');
    Route::get('/view/all/customer-contact-history', [CustomerContactHistoryController::class, 'viewAllCustomerContactHistory'])->name('ViewAllCustomerContactHistories');
    Route::get('/delete/customer-contact-history/{slug}', [CustomerContactHistoryController::class, 'deleteCustomerContactHistory'])->name('DeleteCustomerContactHistories');
    Route::get('/edit/customer-contact-history/{slug}', [CustomerContactHistoryController::class, 'editCustomerContactHistory'])->name('EditCustomerContactHistories');      
    Route::post('/update/customer-contact-history', [CustomerContactHistoryController::class, 'updateCustomerContactHistory'])->name('UpdateCustomerContactHistories');


    // Customer Next Contact Date
    Route::get('/add/new/customer-next-contact-date', [CustomerNextContactDateController::class, 'addNewCustomerNextContactDate'])->name('AddNewCustomerNextContactDate');    
    Route::post('/save/new/customer-next-contact-date', [CustomerNextContactDateController::class, 'saveNewCustomerNextContactDate'])->name('SaveNewCustomerNextContactDate');
    Route::get('/view/all/customer-next-contact-date', [CustomerNextContactDateController::class, 'viewAllCustomerNextContactDate'])->name('ViewAllCustomerNextContactDate');
    Route::get('/delete/customer-next-contact-date/{slug}', [CustomerNextContactDateController::class, 'deleteCustomerNextContactDate'])->name('DeleteCustomerNextContactDate');
    Route::get('/edit/customer-next-contact-date/{slug}', [CustomerNextContactDateController::class, 'editCustomerNextContactDate'])->name('EditCustomerNextContactDate');      
    Route::post('/update/customer-next-contact-date', [CustomerNextContactDateController::class, 'updateCustomerNextContactDate'])->name('UpdateCustomerNextContactDate');


    // support ticket routes
    Route::get('/pending/support/tickets', [SupportTicketController::class, 'pendingSupportTickets'])->name('PendingSupportTickets');
    Route::get('/solved/support/tickets', [SupportTicketController::class, 'solvedSupportTickets'])->name('SolvedSupportTickets');
    Route::get('/on/hold/support/tickets', [SupportTicketController::class, 'onHoldSupportTickets'])->name('OnHoldSupportTickets');
    Route::get('/rejected/support/tickets', [SupportTicketController::class, 'rejectedSupportTickets'])->name('RejectedSupportTickets');
    Route::get('/delete/support/ticket/{slug}', [SupportTicketController::class, 'deleteSupportTicket'])->name('DeleteSupportTicket');
    Route::get('/support/status/change/{slug}', [SupportTicketController::class, 'changeStatusSupport'])->name('ChangeStatusSupport');
    Route::get('/support/status/on/hold/{slug}', [SupportTicketController::class, 'changeStatusSupportOnHold'])->name('ChangeStatusSupportOnHold');
    Route::get('/support/status/in/progress/{slug}', [SupportTicketController::class, 'changeStatusSupportInProgress'])->name('ChangeStatusSupportInProgress');
    Route::get('/support/status/rejected/{slug}', [SupportTicketController::class, 'changeStatusSupportRejected'])->name('ChangeStatusSupportRejected');
    Route::get('/view/support/messages/{slug}', [SupportTicketController::class, 'viewSupportMessage'])->name('ViewSupportMessage');
    Route::post('/send/support/message', [SupportTicketController::class, 'sendSupportMessage'])->name('SendSupportMessage');

     // subscribed users routes
    Route::get('/view/all/subscribed/users', [SubscribedUsersController::class, 'viewAllSubscribedUsers'])->name('ViewAllSubscribedUsers');
    Route::get('/delete/subcribed/users/{id}', [SubscribedUsersController::class, 'deleteSubscribedUsers'])->name('DeleteSubscribedUsers');
    Route::get('/download/subscribed/users/excel', [SubscribedUsersController::class, 'downloadSubscribedUsersExcel'])->name('DownloadSubscribedUsersExcel');
    Route::get('/subscribed/users/send-email', [SubscribedUsersController::class, 'sendEmailPage'])->name('SendEmailSubscribedUsers');
    Route::post('/subscribed/users/send-email', [SubscribedUsersController::class, 'sendBulkEmail'])->name('SendBulkEmailSubscribedUsers');


   // contact request routes
    Route::get('/view/all/contact/requests', [ContactRequestontroller::class, 'viewAllContactRequests'])->name('ViewAllContactRequests');
    Route::get('/delete/contact/request/{id}', [ContactRequestontroller::class, 'deleteContactRequests'])->name('DeleteContactRequests');
    Route::get('/change/request/status/{id}', [ContactRequestontroller::class, 'changeRequestStatus'])->name('ChangeRequestStatus');

    // Bulk SMS BD Management routes
    Route::prefix('bulk-sms-bd')->name('bulk-sms-bd.')->group(function () {
        Route::get('/', [BulkSmsBdManagementController::class, 'index'])->name('index');
        Route::get('/customers', [BulkSmsBdManagementController::class, 'getCustomers'])->name('customers');
        Route::post('/send-single', [BulkSmsBdManagementController::class, 'sendSingle'])->name('send-single');
        Route::post('/send-one-to-many', [BulkSmsBdManagementController::class, 'sendOneToMany'])->name('send-one-to-many');
        Route::post('/send-many-to-many', [BulkSmsBdManagementController::class, 'sendManyToMany'])->name('send-many-to-many');
        Route::get('/get-balance', [BulkSmsBdManagementController::class, 'getBalance'])->name('get-balance');
    });
    
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 2: Customer Tag Settings and Assignment Foundation
|--------------------------------------------------------------------------
|
| These routes use canonical sidebar permissions as a server-side gate. They
| intentionally stay outside the legacy CheckUserType URI gate so a staff role
| with crm.settings.tags permissions can use the working Stage 2 module.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/settings/customer-tags', [CrmCustomerTagController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.settings.tags,read')
        ->name('settings.customer-tags.index');
    Route::get('/settings/customer-tags/data', [CrmCustomerTagController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.settings.tags,read')
        ->name('settings.customer-tags.data');
    Route::get('/settings/customer-tags/active-options', [CrmCustomerTagController::class, 'activeOptions'])
        ->middleware('crm.sidebar.permission:crm.settings.tags,read')
        ->name('settings.customer-tags.active-options');
    Route::post('/settings/customer-tags', [CrmCustomerTagController::class, 'store'])
        ->middleware('crm.sidebar.permission:crm.settings.tags,create')
        ->name('settings.customer-tags.store');
    Route::post('/settings/customer-tags/{tag}', [CrmCustomerTagController::class, 'update'])
        ->where('tag', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.settings.tags,update')
        ->name('settings.customer-tags.update');
    Route::post('/settings/customer-tags/{tag}/status', [CrmCustomerTagController::class, 'changeStatus'])
        ->where('tag', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.settings.tags,update')
        ->name('settings.customer-tags.status');

    Route::get('/customers/{customer}/tags', [CrmCustomerTagController::class, 'assignedTags'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.settings.tags,read')
        ->name('customers.tags.assigned');
    Route::post('/customers/{customer}/tags/sync', [CrmCustomerTagController::class, 'syncCustomerTags'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.settings.tags,update')
        ->name('customers.tags.sync');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 3: Customer 360 Profile
|--------------------------------------------------------------------------
|
| Customer-context routes expose a read-focused CRM profile. Contextual role
| permissions are intentionally not rendered as sidebar URLs.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/customers/{customer}/profile', [CrmCustomerProfileController::class, 'show'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile');

    Route::get('/customers/{customer}/profile/activities', [CrmCustomerProfileController::class, 'activities'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.activities');
    Route::get('/customers/{customer}/profile/communications', [CrmCustomerProfileController::class, 'communications'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.communications');
    Route::get('/customers/{customer}/profile/orders', [CrmCustomerProfileController::class, 'orders'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.orders');
    Route::get('/customers/{customer}/profile/quotations', [CrmCustomerProfileController::class, 'quotations'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.quotations');
    Route::get('/customers/{customer}/profile/contact-histories', [CrmCustomerProfileController::class, 'contactHistories'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.contact-histories');
    Route::get('/customers/{customer}/profile/scheduled-contacts', [CrmCustomerProfileController::class, 'scheduledContacts'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.scheduled-contacts');
    Route::get('/customers/{customer}/profile/returns-refunds', [CrmCustomerProfileController::class, 'returnsAndRefunds'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.returns-refunds');

    Route::get('/customers/{customer}/profile/notes', [CrmCustomerNoteController::class, 'index'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.notes.index');
    Route::post('/customers/{customer}/profile/notes', [CrmCustomerNoteController::class, 'store'])
        ->where('customer', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.customers.profile,create')
        ->name('customers.profile.notes.store');
    Route::post('/customers/{customer}/profile/notes/{note}', [CrmCustomerNoteController::class, 'update'])
        ->where(['customer' => '[0-9]+', 'note' => '[0-9]+'])
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.notes.update');
    Route::post('/customers/{customer}/profile/notes/{note}/archive', [CrmCustomerNoteController::class, 'archive'])
        ->where(['customer' => '[0-9]+', 'note' => '[0-9]+'])
        ->middleware('crm.sidebar.permission:crm.customers.profile,read')
        ->name('customers.profile.notes.archive');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 4: CRM Tasks and Follow-up Worklist
|--------------------------------------------------------------------------
|
| CRM tasks extend the existing crm_tasks foundation without replacing the
| legacy contact-history or scheduled-contact flows.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/tasks', [CrmTaskController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.tasks.list,read')
        ->name('tasks.index');
    Route::get('/tasks/data', [CrmTaskController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.tasks.list,read')
        ->name('tasks.data');
    Route::get('/tasks/options/customers', [CrmTaskController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.tasks.list,read')
        ->name('tasks.options.customers');
    Route::get('/tasks/options/users', [CrmTaskController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.tasks.list,read')
        ->name('tasks.options.users');
    Route::post('/tasks', [CrmTaskController::class, 'store'])
        ->middleware('crm.sidebar.permission:crm.tasks.create,create')
        ->name('tasks.store');
    Route::post('/tasks/{task}', [CrmTaskController::class, 'update'])
        ->where('task', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.tasks.create,update')
        ->name('tasks.update');
    Route::post('/tasks/{task}/complete', [CrmTaskController::class, 'complete'])
        ->where('task', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.tasks.complete,update')
        ->name('tasks.complete');
    Route::post('/tasks/{task}/archive', [CrmTaskController::class, 'archive'])
        ->where('task', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.tasks.list,delete')
        ->name('tasks.archive');

    Route::get('/customers/{customer}/profile/tasks', [CrmCustomerProfileController::class, 'tasks'])
        ->where('customer', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.customers.profile,read',
            'crm.sidebar.permission:crm.tasks.list,read',
        ])
        ->name('customers.profile.tasks');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 10: Read-only CRM Task Calendar
|--------------------------------------------------------------------------
|
| Calendar routes expose a read-only due-date projection of existing CRM
| tasks. Existing task writes remain in the Stage 4 worklist endpoints.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/tasks/calendar', [CrmTaskController::class, 'calendar'])
        ->middleware('crm.sidebar.permission:crm.tasks.calendar,read')
        ->name('tasks.calendar');
    Route::get('/tasks/calendar/data', [CrmTaskController::class, 'calendarData'])
        ->middleware('crm.sidebar.permission:crm.tasks.calendar,read')
        ->name('tasks.calendar.data');
    Route::get('/tasks/calendar/options/customers', [CrmTaskController::class, 'calendarCustomerOptions'])
        ->middleware('crm.sidebar.permission:crm.tasks.calendar,read')
        ->name('tasks.calendar.options.customers');
    Route::get('/tasks/calendar/options/users', [CrmTaskController::class, 'calendarUserOptions'])
        ->middleware('crm.sidebar.permission:crm.tasks.calendar,read')
        ->name('tasks.calendar.options.users');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 5: CRM Communications Worklist and Manual Logging
|--------------------------------------------------------------------------
|
| Manual communication logging stores customer history only. It does not
| send messages or connect existing SMS, newsletter, or provider adapters.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/communications', [CrmCommunicationController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.communications.list,read')
        ->name('communications.index');
    Route::get('/communications/data', [CrmCommunicationController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.communications.list,read')
        ->name('communications.data');
    Route::get('/communications/options/customers', [CrmCommunicationController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.communications.list,read')
        ->name('communications.options.customers');
    Route::post('/communications', [CrmCommunicationController::class, 'store'])
        ->middleware('crm.sidebar.permission:crm.communications.list,create')
        ->name('communications.store');
    Route::get('/communications/{communication}', [CrmCommunicationController::class, 'show'])
        ->where('communication', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.communications.list,read')
        ->name('communications.show');
});


/*
|--------------------------------------------------------------------------
| ERP CRM Stage 12: Read-only CRM Customer Health & Risk Worklist
|--------------------------------------------------------------------------
|
| Health routes expose customer CRM summary fields for operational review.
| No customer, due, payment, accounting, or summary recalculation write
| endpoint is introduced in this stage.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/customer-health', [CrmCustomerHealthController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.customer-health.list,read')
        ->name('customer-health.index');
    Route::get('/customer-health/data', [CrmCustomerHealthController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.customer-health.list,read')
        ->name('customer-health.data');
    Route::get('/customer-health/options/customers', [CrmCustomerHealthController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.customer-health.list,read')
        ->name('customer-health.options.customers');
    Route::get('/customer-health/options/users', [CrmCustomerHealthController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.customer-health.list,read')
        ->name('customer-health.options.users');
});


/*
|--------------------------------------------------------------------------
| ERP CRM Stage 13: Read-only CRM Duplicate Customer Review Worklist
|--------------------------------------------------------------------------
|
| Duplicate-customer routes expose a review-only projection of customer
| records and duplicate signals. No merge, edit, delete, archive, or
| recalculation endpoint is introduced in this stage.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/duplicate-customers', [CrmDuplicateCustomerController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.duplicate-customers.list,read')
        ->name('duplicate-customers.index');
    Route::get('/duplicate-customers/data', [CrmDuplicateCustomerController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.duplicate-customers.list,read')
        ->name('duplicate-customers.data');
    Route::get('/duplicate-customers/options/customers', [CrmDuplicateCustomerController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.duplicate-customers.list,read')
        ->name('duplicate-customers.options.customers');
    Route::get('/duplicate-customers/options/users', [CrmDuplicateCustomerController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.duplicate-customers.list,read')
        ->name('duplicate-customers.options.users');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 15: Read-only CRM Customer Portfolio Segmentation Worklist
|--------------------------------------------------------------------------
|
| Portfolio-segment routes expose a review-only customer projection using
| existing CRM tags and summary fields. No customer, messaging, accounting,
| or automation write endpoint is introduced in this stage.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/customer-segments', [CrmCustomerSegmentController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.customer-segments.list,read')
        ->name('customer-segments.index');
    Route::get('/customer-segments/data', [CrmCustomerSegmentController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.customer-segments.list,read')
        ->name('customer-segments.data');
    Route::get('/customer-segments/options/customers', [CrmCustomerSegmentController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.customer-segments.list,read')
        ->name('customer-segments.options.customers');
    Route::get('/customer-segments/options/users', [CrmCustomerSegmentController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.customer-segments.list,read')
        ->name('customer-segments.options.users');
    Route::get('/customer-segments/options/tags', [CrmCustomerSegmentController::class, 'tagOptions'])
        ->middleware('crm.sidebar.permission:crm.customer-segments.list,read')
        ->name('customer-segments.options.tags');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 16: CRM Saved Customer Segments Foundation
|--------------------------------------------------------------------------
|
| Saved segment routes persist validated Stage 15 filter definitions only.
| Applying a saved segment remains read-only for customers. Archive is the
| only removal action; no hard delete, messaging, or automation endpoint exists.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/saved-customer-segments', [CrmSavedCustomerSegmentController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,read')
        ->name('saved-customer-segments.index');
    Route::get('/saved-customer-segments/data', [CrmSavedCustomerSegmentController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,read')
        ->name('saved-customer-segments.data');
    Route::get('/saved-customer-segments/options', [CrmSavedCustomerSegmentController::class, 'options'])
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,read')
        ->name('saved-customer-segments.options');
    Route::post('/saved-customer-segments', [CrmSavedCustomerSegmentController::class, 'store'])
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,create')
        ->name('saved-customer-segments.store');
    Route::get('/saved-customer-segments/{segment}/apply', [CrmSavedCustomerSegmentController::class, 'apply'])
        ->where('segment', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,read')
        ->name('saved-customer-segments.apply');
    Route::get('/saved-customer-segments/{segment}', [CrmSavedCustomerSegmentController::class, 'show'])
        ->where('segment', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,read')
        ->name('saved-customer-segments.show');
    Route::post('/saved-customer-segments/{segment}', [CrmSavedCustomerSegmentController::class, 'update'])
        ->where('segment', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,update')
        ->name('saved-customer-segments.update');
    Route::post('/saved-customer-segments/{segment}/archive', [CrmSavedCustomerSegmentController::class, 'archive'])
        ->where('segment', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.saved-customer-segments.list,delete')
        ->name('saved-customer-segments.archive');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stages 23-32: Controlled Dispatch Ledger and Transport Boundary
|--------------------------------------------------------------------------
|
| BulkSMSBD SMS and Email may advance through immutable dispatch ledgers.
| Only the hardened BulkSMSBD SMS execute route may perform provider calls;
| Email remains non-sending. No schedule, queue, export, or job route exists.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/campaign-drafts', [CrmCampaignDraftController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.index');
    Route::get('/campaign-drafts/data', [CrmCampaignDraftController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.data');
    Route::get('/campaign-drafts/options/saved-segments', [CrmCampaignDraftController::class, 'savedSegmentOptions'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.saved-customer-segments.list,read',
        ])
        ->name('campaign-drafts.options.saved-segments');
    Route::get('/campaign-drafts/audience-preview/{segment}', [CrmCampaignDraftController::class, 'previewSavedSegment'])
        ->where('segment', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.saved-customer-segments.list,read',
        ])
        ->name('campaign-drafts.audience-preview.saved-segment');
    Route::post('/campaign-drafts', [CrmCampaignDraftController::class, 'store'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,create',
            'crm.sidebar.permission:crm.saved-customer-segments.list,read',
        ])
        ->name('campaign-drafts.store');
    Route::post('/campaign-drafts/{draft}/refresh-audience-snapshot', [CrmCampaignDraftController::class, 'refreshAudienceSnapshot'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,update',
            'crm.sidebar.permission:crm.saved-customer-segments.list,read',
        ])
        ->name('campaign-drafts.refresh-audience-snapshot');
    Route::get('/campaign-drafts/{draft}/dispatch-preparations/preview', [CrmCampaignDispatchPreparationController::class, 'preview'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch,read',
        ])
        ->name('campaign-drafts.dispatch-preparations.preview');
    Route::get('/campaign-drafts/{draft}/dispatch-preparations', [CrmCampaignDispatchPreparationController::class, 'history'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch,read',
        ])
        ->name('campaign-drafts.dispatch-preparations.history');
    Route::post('/campaign-drafts/{draft}/dispatch-preparations', [CrmCampaignDispatchPreparationController::class, 'store'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch,create',
        ])
        ->name('campaign-drafts.dispatch-preparations.store');
    Route::post('/campaign-drafts/{draft}/dispatch-preparations/{preparation}/cancel', [CrmCampaignDispatchPreparationController::class, 'cancel'])
        ->where(['draft' => '[0-9]+', 'preparation' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch,update',
        ])
        ->name('campaign-drafts.dispatch-preparations.cancel');
    Route::post('/campaign-drafts/{draft}/dispatch-preparations/{preparation}/invalidate', [CrmCampaignDispatchPreparationController::class, 'invalidate'])
        ->where(['draft' => '[0-9]+', 'preparation' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch,update',
        ])
        ->name('campaign-drafts.dispatch-preparations.invalidate');
    Route::get('/campaign-drafts/{draft}/dispatch-runs/preview', [CrmCampaignDispatchRunController::class, 'preview'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.release-dispatch,read',
        ])
        ->name('campaign-drafts.dispatch-runs.preview');
    Route::get('/campaign-drafts/{draft}/dispatch-runs', [CrmCampaignDispatchRunController::class, 'history'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.release-dispatch,read',
        ])
        ->name('campaign-drafts.dispatch-runs.history');
    Route::post('/campaign-drafts/{draft}/dispatch-preparations/{preparation}/dispatch-runs', [CrmCampaignDispatchRunController::class, 'store'])
        ->where(['draft' => '[0-9]+', 'preparation' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.release-dispatch,create',
        ])
        ->name('campaign-drafts.dispatch-runs.store');
    Route::post('/campaign-drafts/{draft}/dispatch-runs/{run}/cancel', [CrmCampaignDispatchRunController::class, 'cancel'])
        ->where(['draft' => '[0-9]+', 'run' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.release-dispatch,update',
        ])
        ->name('campaign-drafts.dispatch-runs.cancel');
    Route::get('/campaign-drafts/{draft}/dispatch-execution-batches/preview', [CrmCampaignDispatchExecutionBatchController::class, 'preview'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.claim-dispatch-execution,read',
        ])
        ->name('campaign-drafts.dispatch-execution-batches.preview');
    Route::get('/campaign-drafts/{draft}/dispatch-execution-batches', [CrmCampaignDispatchExecutionBatchController::class, 'history'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.claim-dispatch-execution,read',
        ])
        ->name('campaign-drafts.dispatch-execution-batches.history');
    Route::post('/campaign-drafts/{draft}/dispatch-runs/{run}/execution-batches', [CrmCampaignDispatchExecutionBatchController::class, 'store'])
        ->where(['draft' => '[0-9]+', 'run' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.claim-dispatch-execution,create',
        ])
        ->name('campaign-drafts.dispatch-execution-batches.store');
    Route::post('/campaign-drafts/{draft}/dispatch-execution-batches/{batch}/cancel', [CrmCampaignDispatchExecutionBatchController::class, 'cancel'])
        ->where(['draft' => '[0-9]+', 'batch' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.claim-dispatch-execution,update',
        ])
        ->name('campaign-drafts.dispatch-execution-batches.cancel');
    Route::get('/campaign-drafts/{draft}/dispatch-attempts/preview', [CrmCampaignDispatchAttemptController::class, 'preview'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch-attempt,read',
        ])
        ->name('campaign-drafts.dispatch-attempts.preview');
    Route::get('/campaign-drafts/{draft}/dispatch-attempts', [CrmCampaignDispatchAttemptController::class, 'history'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch-attempt,read',
        ])
        ->name('campaign-drafts.dispatch-attempts.history');
    Route::post('/campaign-drafts/{draft}/dispatch-execution-batches/{batch}/dispatch-attempts', [CrmCampaignDispatchAttemptController::class, 'store'])
        ->where(['draft' => '[0-9]+', 'batch' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch-attempt,create',
        ])
        ->name('campaign-drafts.dispatch-attempts.store');
    Route::post('/campaign-drafts/{draft}/dispatch-attempts/{attempt}/execute', [CrmCampaignDispatchExecutionController::class, 'execute'])
        ->where(['draft' => '[0-9]+', 'attempt' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.execute-dispatch,create',
        ])
        ->name('campaign-drafts.dispatch-attempts.execute');
    Route::post('/campaign-drafts/{draft}/dispatch-attempts/{attempt}/cancel', [CrmCampaignDispatchAttemptController::class, 'cancel'])
        ->where(['draft' => '[0-9]+', 'attempt' => '[0-9]+'])
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.prepare-dispatch-attempt,update',
        ])
        ->name('campaign-drafts.dispatch-attempts.cancel');
    Route::get('/campaign-drafts/{draft}/preflight', [CrmCampaignDraftController::class, 'preflight'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.preflight');
    Route::get('/campaign-drafts/{draft}/approval-history', [CrmCampaignDraftController::class, 'approvalHistory'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.approval-history');
    Route::post('/campaign-drafts/{draft}/submit-for-review', [CrmCampaignDraftController::class, 'submitForReview'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,update')
        ->name('campaign-drafts.submit-for-review');
    Route::post('/campaign-drafts/{draft}/approve', [CrmCampaignDraftController::class, 'approve'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.approve,update',
        ])
        ->name('campaign-drafts.approve');
    Route::post('/campaign-drafts/{draft}/reject', [CrmCampaignDraftController::class, 'reject'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,read',
            'crm.sidebar.permission:crm.campaign-drafts.approve,update',
        ])
        ->name('campaign-drafts.reject');
    Route::post('/campaign-drafts/{draft}/return-to-draft', [CrmCampaignDraftController::class, 'returnToDraft'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,update')
        ->name('campaign-drafts.return-to-draft');
    Route::post('/campaign-drafts/{draft}/archive', [CrmCampaignDraftController::class, 'archive'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,delete')
        ->name('campaign-drafts.archive');
    Route::get('/campaign-drafts/{draft}/audience-preview', [CrmCampaignDraftController::class, 'preview'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.preview');
    Route::get('/campaign-drafts/{draft}', [CrmCampaignDraftController::class, 'show'])
        ->where('draft', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.campaign-drafts.list,read')
        ->name('campaign-drafts.show');
    Route::post('/campaign-drafts/{draft}', [CrmCampaignDraftController::class, 'update'])
        ->where('draft', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.campaign-drafts.list,update',
            'crm.sidebar.permission:crm.saved-customer-segments.list,read',
        ])
        ->name('campaign-drafts.update');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 11: Read-only CRM Activity Audit Worklist
|--------------------------------------------------------------------------
|
| Activity routes expose immutable audit history recorded by existing CRM
| flows. No activity create, edit, archive, delete, or backfill endpoint exists.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/activities', [CrmActivityController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.activities.list,read')
        ->name('activities.index');
    Route::get('/activities/data', [CrmActivityController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.activities.list,read')
        ->name('activities.data');
    Route::get('/activities/options/customers', [CrmActivityController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.activities.list,read')
        ->name('activities.options.customers');
    Route::get('/activities/options/users', [CrmActivityController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.activities.list,read')
        ->name('activities.options.users');
    Route::get('/activities/{activity}', [CrmActivityController::class, 'show'])
        ->where('activity', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.activities.list,read')
        ->name('activities.show');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 7: Lead Management Foundation
|--------------------------------------------------------------------------
|
| Leads capture pre-customer sales opportunities. Conversion links a lead to
| an existing customer only and does not create orders, payments, SMS, dues,
| or accounting entries.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/leads/pipeline', [CrmLeadController::class, 'pipeline'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.pipeline');
    Route::get('/leads/pipeline/data', [CrmLeadController::class, 'pipelineData'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.pipeline.data');
    Route::get('/leads', [CrmLeadController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.index');
    Route::get('/leads/data', [CrmLeadController::class, 'data'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.data');
    Route::get('/leads/options/customers', [CrmLeadController::class, 'customerOptions'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.options.customers');
    Route::get('/leads/options/users', [CrmLeadController::class, 'userOptions'])
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.options.users');
    Route::post('/leads', [CrmLeadController::class, 'store'])
        ->middleware('crm.sidebar.permission:crm.leads.list,create')
        ->name('leads.store');
    Route::get('/leads/{lead}', [CrmLeadController::class, 'show'])
        ->where('lead', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.leads.list,read')
        ->name('leads.show');
    Route::post('/leads/{lead}', [CrmLeadController::class, 'update'])
        ->where('lead', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.leads.list,update')
        ->name('leads.update');
    Route::post('/leads/{lead}/status', [CrmLeadController::class, 'status'])
        ->where('lead', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.leads.list,update')
        ->name('leads.status');
    Route::post('/leads/{lead}/archive', [CrmLeadController::class, 'archive'])
        ->where('lead', '[0-9]+')
        ->middleware('crm.sidebar.permission:crm.leads.list,delete')
        ->name('leads.archive');

    Route::get('/customers/{customer}/profile/leads', [CrmCustomerProfileController::class, 'leads'])
        ->where('customer', '[0-9]+')
        ->middleware([
            'crm.sidebar.permission:crm.customers.profile,read',
            'crm.sidebar.permission:crm.leads.list,read',
        ])
        ->name('customers.profile.leads');
});

/*
|--------------------------------------------------------------------------
| ERP CRM Stage 33: Bilingual CRM User Manual
|--------------------------------------------------------------------------
|
| Read-only CRM tutorial documentation for operators. This route does not
| create, update, execute, or mutate any customer, campaign, order, payment,
| accounting, SMS, email, support-ticket, or sidebar data.
|
*/
Route::prefix('crm')->name('crm.')->middleware(['auth', 'DemoMode'])->group(function () {
    Route::get('/user-manual', [CrmUserManualController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.user-manual,read')
        ->name('user-manual.index');
    Route::get('/user-manual/bn', [CrmUserManualController::class, 'bn'])
        ->middleware('crm.sidebar.permission:crm.user-manual,read')
        ->name('user-manual.bn');
    Route::get('/user-manual/en', [CrmUserManualController::class, 'en'])
        ->middleware('crm.sidebar.permission:crm.user-manual,read')
        ->name('user-manual.en');
});
