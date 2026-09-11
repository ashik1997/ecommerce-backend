<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SmsServiceController;
use App\Http\Controllers\GeneralInfoController;
use App\Models\GeneralInfo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::group(['middleware' => ['auth', 'CheckUserType', 'DemoMode']], function () {
    
    // sms service
    Route::get('/view/sms/templates', [SmsServiceController::class, 'viewSmsTemplates'])->name('ViewSmsTemplates');
    Route::get('/create/sms/template', [SmsServiceController::class, 'createSmsTemplate'])->name('CreateSmsTemplate');
    Route::post('/save/sms/template', [SmsServiceController::class, 'saveSmsTemplate'])->name('SaveSmsTemplate');
    Route::get('get/sms/template/info/{id}', [SmsServiceController::class, 'getSmsTemplateInfo'])->name('GetSmsTemplateInfo');
    Route::get('delete/sms/template/{id}', [SmsServiceController::class, 'deleteSmsTemplate'])->name('DeleteSmsTemplate');
    Route::get('/send/sms/page', [SmsServiceController::class, 'sendSmsPage'])->name('SendSmsPage');
    Route::post('/get/template/description', [SmsServiceController::class, 'getTemplateDescription'])->name('GetTemplateDescription');
    Route::post('/update/sms/template', [SmsServiceController::class, 'updateSmsTemplate'])->name('UpdateSmsTemplate');
    Route::post('/send/sms', [SmsServiceController::class, 'sendSms'])->name('SendSms');
    Route::get('/view/sms/history', [SmsServiceController::class, 'viewSmsHistory'])->name('ViewSmsHistory');
    Route::get('/delete/sms/with/range', [SmsServiceController::class, 'deleteSmsHistoryRange'])->name('DeleteSmsHistoryRange');
    Route::get('/delete/sms/{id}', [SmsServiceController::class, 'deleteSmsHistory'])->name('DeleteSmsHistory');

});

// // Public route to serve uploads files (without authentication)
// // This is a fallback for direct file access
// Route::get('/uploads/{path}', function ($path) {
//     $filePath = public_path('uploads/' . $path);
    
//     if (!File::exists($filePath)) {
//         abort(404);
//     }
    
//     $file = File::get($filePath);
//     $type = File::mimeType($filePath);
    
//     return Response::make($file, 200)->header('Content-Type', $type);
// })->where('path', '.*');