<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Courier\AreaBaseCourierController;
use App\Http\Controllers\Courier\AreaBaseCourierManagementController;

Route::group(['middleware' => ['auth', 'CheckUserType', 'DemoMode']], function () {
    // Courier name management
    Route::resource('area-base-courier-names', AreaBaseCourierManagementController::class);

    // Courier area 
    Route::resource('area-base-courier-charges', AreaBaseCourierController::class);
    
});
