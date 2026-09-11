<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerBillingController;
use App\Http\Controllers\Outlet\SupplierSourceController;
use App\Http\Controllers\Inventory\ProductSupplierController;
use App\Http\Controllers\Inventory\ProductWarehouseController;
use App\Http\Controllers\Inventory\Models\ProductWarehouseRoom;
use App\Http\Controllers\Inventory\ProductPurchaseOrderController;
use App\Http\Controllers\Inventory\ProductWarehouseRoomController;
use App\Http\Controllers\Inventory\ProductPurchaseChargeController;
use App\Http\Controllers\Inventory\ProductPurchaseQuotationController;
use App\Http\Controllers\Inventory\ProductPurchasReturnController;
use App\Http\Controllers\Inventory\ProductWarehouseRoomCartoonController;
use App\Http\Controllers\Inventory\ProductOrderController;
use App\Http\Controllers\Inventory\ProductOrderReturnController;
use App\Http\Controllers\Inventory\ProductOrderReturnReportController;
use App\Http\Controllers\Customer\CustomerPaymentController;
use App\Http\Controllers\Product\ManualProductReturnController;
use App\Http\Controllers\Courier\PathaoController;
use App\Http\Controllers\Inventory\ProductOrderQuotationController;
use App\Http\Controllers\Inventory\ProductPurchaseOrderV2Controller;

Route::group(['middleware' => ['auth', 'CheckUserType', 'DemoMode']], function () {

    // product warehouse routes
    Route::get('/add/new/product-warehouse', [ProductWarehouseController::class, 'addNewProductWarehouse'])->name('AddNewProductWarehouse');
    //  Route::post('/subcategory/wise/childcategory', [ProductController::class, 'childcategorySubcategoryWise'])->name('ChildcategorySubcategoryWise');
    Route::post('/save/new/product-warehouse', [ProductWarehouseController::class, 'saveNewProductWarehouse'])->name('SaveNewProductWarehouse');
    Route::get('/view/all/product-warehouse', [ProductWarehouseController::class, 'viewAllProductWarehouse'])->name('ViewAllProductWarehouse');
    Route::get('/delete/product-warehouse/{slug}', [ProductWarehouseController::class, 'deleteProductWarehouse'])->name('DeleteProductWarehouse');
    Route::get('/edit/product-warehouse/{slug}', [ProductWarehouseController::class, 'editProductWarehouse'])->name('EditProductWarehouse');
    Route::post('/update/product-warehouse', [ProductWarehouseController::class, 'updateProductWarehouse'])->name('UpdateProductWarehouse');
    Route::get('/api/product-warehouses', [ProductWarehouseController::class, 'apiListProductWarehouses'])->name('ApiListProductWarehouses');
    Route::post('/api/product-warehouses', [ProductWarehouseController::class, 'apiStoreProductWarehouse'])->name('ApiStoreProductWarehouse');
    Route::post('/api/product-warehouses/{id}', [ProductWarehouseController::class, 'apiUpdateProductWarehouse'])->name('ApiUpdateProductWarehouse');
    Route::delete('/api/product-warehouses/{id}', [ProductWarehouseController::class, 'apiDeleteProductWarehouse'])->name('ApiDeleteProductWarehouse');
    //  Route::post('/add/another/variant', [ProductController::class, 'addAnotherVariant'])->name('AddAnotherVariant');
    //  Route::get('/delete/product/variant/{id}', [ProductController::class, 'deleteProductVariant'])->name('DeleteProductVariant');
    //  Route::get('/products/from/excel', [ProductController::class, 'productsFromExcel'])->name('ProductsFromExcel');
    //  Route::post('/upload/product/from/excel', [ProductController::class, 'uploadProductsFromExcel'])->name('UploadProductsFromExcel');


    // product warehouse rooms routes
    Route::get('/add/new/product-warehouse-room', [ProductWarehouseRoomController::class, 'addNewProductWarehouseRoom'])->name('AddNewProductWarehouseRoom');
    //  Route::post('/subcategory/wise/childcategory', [ProductController::class, 'childcategorySubcategoryWise'])->name('ChildcategorySubcategoryWise');
    Route::post('/save/new/product-warehouse-room', [ProductWarehouseRoomController::class, 'saveNewProductWarehouseRoom'])->name('SaveNewProductWarehouseRoom');
    Route::get('/view/all/product-warehouse-room', [ProductWarehouseRoomController::class, 'viewAllProductWarehouseRoom'])->name('ViewAllProductWarehouseRoom');
    Route::get('/delete/product-warehouse-room/{slug}', [ProductWarehouseRoomController::class, 'deleteProductWarehouseRoom'])->name('DeleteProductWarehouseRoom');
    Route::get('/edit/product-warehouse-room/{slug}', [ProductWarehouseRoomController::class, 'editProductWarehouseRoom'])->name('EditProductWarehouseRoom');
    Route::post('/update/product-warehouse-room', [ProductWarehouseRoomController::class, 'updateProductWarehouseRoom'])->name('UpdateProductWarehouseRoom');
    Route::post('/get-product-warehouse-rooms', [ProductWarehouseRoomController::class, 'getProductWarehouseRooms'])->name('get.product.warehouse.rooms');
    Route::get('/get-warehouse-rooms/{warehouseId}', function ($warehouseId) {
        $rooms = ProductWarehouseRoom::where('product_warehouse_id', $warehouseId)->get();
        return response()->json(['rooms' => $rooms]);
    });


    // product warehouse room cartoon routes
    Route::get('/add/new/product-warehouse-room-cartoon', [ProductWarehouseRoomCartoonController::class, 'addNewProductWarehouseRoomCartoon'])->name('AddNewProductWarehouseRoomCartoon');
    //  Route::post('/subcategory/wise/childcategory', [ProductController::class, 'childcategorySubcategoryWise'])->name('ChildcategorySubcategoryWise');
    Route::post('/save/new/product-warehouse-room-cartoon', [ProductWarehouseRoomCartoonController::class, 'saveNewProductWarehouseRoomCartoon'])->name('SaveNewProductWarehouseRoomCartoon');
    Route::get('/view/all/product-warehouse-room-cartoon', [ProductWarehouseRoomCartoonController::class, 'viewAllProductWarehouseRoomCartoon'])->name('ViewAllProductWarehouseRoomCartoon');
    Route::get('/delete/product-warehouse-room-cartoon/{slug}', [ProductWarehouseRoomCartoonController::class, 'deleteProductWarehouseRoomCartoon'])->name('DeleteProductWarehouseRoomCartoon');
    Route::get('/edit/product-warehouse-room-cartoon/{slug}', [ProductWarehouseRoomCartoonController::class, 'editProductWarehouseRoomCartoon'])->name('EditProductWarehouseRoomCartoon');
    Route::post('/update/product-warehouse-room-cartoon', [ProductWarehouseRoomCartoonController::class, 'updateProductWarehouseRoomCartoon'])->name('UpdateProductWarehouseRoomCartoon');
    Route::post('/get-product-warehouse-room-cartoons', [ProductWarehouseRoomCartoonController::class, 'getProductWarehouseRoomCartoon'])->name('get.product.warehouse.room.cartoon');

    // product supplier routes
    Route::get('/add/new/product-supplier', [ProductSupplierController::class, 'addNewProductSupplier'])->name('AddNewProductSupplier');
    Route::post('/save/new/product-supplier', [ProductSupplierController::class, 'saveNewProductSupplier'])->name('SaveNewProductSupplier');
    Route::get('/view/all/product-supplier', [ProductSupplierController::class, 'viewAllProductSupplier'])->name('ViewAllProductSupplier');
    Route::get('/delete/product-supplier/{slug}', [ProductSupplierController::class, 'deleteProductSupplier'])->name('DeleteProductSupplier');
    Route::get('/edit/product-supplier/{slug}', [ProductSupplierController::class, 'editProductSupplier'])->name('EditProductSupplier');
    Route::post('/update/product-supplier', [ProductSupplierController::class, 'updateProductSupplier'])->name('UpdateProductSupplier');

    // Supplier Source Type 
    Route::get('/add/new/supplier-source', [SupplierSourceController::class, 'addNewSupplierSource'])->name('AddNewSupplierSource');
    Route::post('/save/new/supplier-source', [SupplierSourceController::class, 'saveNewSupplierSource'])->name('SaveNewSupplierSource');
    Route::get('/view/all/supplier-source', [SupplierSourceController::class, 'viewAllSupplierSource'])->name('ViewAllSupplierSource');
    Route::get('/delete/supplier-source/{slug}', [SupplierSourceController::class, 'deleteSupplierSource'])->name('DeleteSupplierSource');
    Route::get('/edit/supplier-source/{slug}', [SupplierSourceController::class, 'editSupplierSource'])->name('EditSupplierSource');
    Route::post('/update/supplier-source', [SupplierSourceController::class, 'updateSupplierSource'])->name('UpdateSupplierSource');

    Route::get('/get-warehouse-rooms', [ProductWarehouseController::class, 'getWarehouseRooms']);
    Route::get('/get-warehouse-room-cartoons', [ProductWarehouseController::class, 'getWarehouseRoomCartoons']);

    // Route::get('get-rooms/{warehouseId}', [WarehouseController::class, 'getRooms'])->name('get.rooms');
    // Route::get('get-cartoons/{roomId}', [WarehouseController::class, 'getCartoons'])->name('get.cartoons');

    Route::get('/api/get-rooms/{warehouseId}', [ProductWarehouseController::class, 'apiGetetWarehouseRooms']);
    Route::get('/api/get-cartoons/{warehouseId}/{roomId}', [ProductWarehouseController::class, 'apiGetetWarehouseRoomCartoons']);

    // purchase product quotation routes
    Route::get('/add/new/purchase-product/quotation', [ProductPurchaseQuotationController::class, 'addNewPurchaseProductQuotation'])->name('AddNewPurchaseProductQuotation');
    Route::post('/save/new/purchase-product/quotation', [ProductPurchaseQuotationController::class, 'saveNewPurchaseProductQuotation'])->name('SaveNewPurchaseProductQuotation');
    Route::get('/view/all/purchase-product/quotation', [ProductPurchaseQuotationController::class, 'viewAllPurchaseProductQuotation'])->name('ViewAllPurchaseProductQuotation');
    Route::get('/delete/purchase-product/quotation/{slug}', [ProductPurchaseQuotationController::class, 'deletePurchaseProductQuotation'])->name('DeletePurchaseProductQuotation');
    Route::get('/edit/purchase-product/quotation/{slug}', [ProductPurchaseQuotationController::class, 'editPurchaseProductQuotation'])->name('EditPurchaseProductQuotation');
    Route::get('/edit/purchase-product/sales/quotation/{slug}', [ProductPurchaseQuotationController::class, 'editPurchaseProductSalesQuotation'])->name('EditPurchaseProductSalesQuotation');
    Route::get('api/edit/purchase-product/quotation/{slug}', [ProductPurchaseQuotationController::class, 'apiEditPurchaseProduct'])->name('ApiEditPurchaseProductQuotation');
    Route::post('/update/purchase-product/quotation', [ProductPurchaseQuotationController::class, 'updatePurchaseProductQuotation'])->name('UpdatePurchaseProductQuotation');
    Route::post('/update/purchase-product/sales/quotation', [ProductPurchaseQuotationController::class, 'updatePurchaseProductSalesQuotation'])->name('UpdatePurchaseProductSalesQuotation');

    Route::get('/api/products/search', [ProductPurchaseQuotationController::class, 'searchProduct'])->name('SearchProduct');

    // purchase product order routes
    Route::get('/add/new/purchase-product/order', [ProductPurchaseOrderV2Controller::class, 'addNewPurchaseProductOrder'])->name('AddNewPurchaseProductOrder');
    Route::post('/save/new/purchase-product/order', [ProductPurchaseOrderV2Controller::class, 'saveNewPurchaseProductOrder'])->name('SaveNewPurchaseProductOrder');
    Route::get('/view/all/purchase-product/order', [ProductPurchaseOrderV2Controller::class, 'viewAllPurchaseProductOrder'])->name('ViewAllPurchaseProductOrder');
    Route::get('/delete/purchase-product/order/{slug}', [ProductPurchaseOrderV2Controller::class, 'deletePurchaseProductOrder'])->name('DeletePurchaseProductOrder');
    Route::get('/edit/purchase-product/order/{slug}', [ProductPurchaseOrderV2Controller::class, 'editPurchaseProductOrder'])->name('EditPurchaseProductOrder');
    Route::get('/edit/purchase-product/order/confirm/{slug}', [ProductPurchaseOrderV2Controller::class, 'editPurchaseProductOrderConfirm'])->name('EditPurchaseProductOrderConfirm');
    Route::get('api/edit/purchase-product/order/{slug}', [ProductPurchaseOrderV2Controller::class, 'apiEditPurchaseProduct'])->name('ApiEditPurchaseProductOrder');
    Route::post('/update/purchase-product/order', [ProductPurchaseOrderV2Controller::class, 'updatePurchaseProductOrder'])->name('UpdatePurchaseProductOrder');
    Route::get('/print-purchase-barcode/{purchase_id}', [ProductPurchaseOrderV2Controller::class, 'printPurchaseBarcode'])->name('PrintPurchaseBarcode');
    Route::get('/api/purchase-barcode-units/{purchase_id}', [ProductPurchaseOrderV2Controller::class, 'apiGetPurchaseBarcodeUnits'])->name('ApiGetPurchaseBarcodeUnits');
    Route::post('/api/purchase-barcode-unit/update-code', [ProductPurchaseOrderV2Controller::class, 'apiUpdateBarcodeUnitCode'])->name('ApiUpdateBarcodeUnitCode');
    Route::get('/api/purchase-product/order/{slug}/invoice-modal', [ProductPurchaseOrderV2Controller::class, 'purchaseInvoiceModal'])->name('PurchaseInvoiceModal');
    Route::get('/purchase-invoice/{slug}', [ProductPurchaseOrderV2Controller::class, 'purchaseInvoice'])->name('PurchaseInvoice');
    Route::post('/purchase-invoice/{slug}/send-email', [ProductPurchaseOrderV2Controller::class, 'sendPurchaseInvoiceEmail'])->name('SendPurchaseInvoiceEmail');
    Route::get('/edit/purchase-product/order/v2/{slug}', [ProductPurchaseOrderV2Controller::class, 'editPurchaseProductOrderV2'])->name('EditPurchaseProductOrderV2');
    Route::post('/update/purchase-product/order/v2', [ProductPurchaseOrderV2Controller::class, 'updatePurchaseProductOrderV2'])->name('UpdatePurchaseProductOrderV2');


    // purchase product order routes
    Route::get('/add/new/purchase-return/order', [ProductPurchasReturnController::class, 'addNewPurchaseReturnOrder'])->name('AddNewPurchaseReturnOrder');
    Route::post('/save/new/purchase-return/order', [ProductPurchasReturnController::class, 'saveNewPurchaseReturnOrder'])->name('SaveNewPurchaseReturnOrder');
    Route::get('/view/all/purchase-return/order', [ProductPurchasReturnController::class, 'viewAllPurchaseReturnOrder'])->name('ViewAllPurchaseReturnOrder');
    Route::get('/delete/purchase-return/order/{slug}', [ProductPurchasReturnController::class, 'deletePurchaseReturnOrder'])->name('DeletePurchaseReturnOrder');
    Route::get('/edit/purchase-return/order/{slug}', [ProductPurchasReturnController::class, 'editPurchaseReturnOrder'])->name('EditPurchaseReturnOrder');
    Route::get('/edit/purchase-return/order/confirm/{slug}', [ProductPurchasReturnController::class, 'editPurchaseReturnOrderConfirm'])->name('EditPurchaseReturnOrderConfirm');
    Route::get('api/edit/purchase-return/order/{slug}', [ProductPurchasReturnController::class, 'apiEditPurchaseReturn'])->name('ApiEditPurchaseReturnOrder');
    Route::post('/update/purchase-return/order', [ProductPurchasReturnController::class, 'updatePurchaseReturnOrder'])->name('UpdatePurchaseReturnOrder');

    // purchase product other charge
    Route::get('/add/new/purchase-product/charge', [ProductPurchaseChargeController::class, 'addNewPurchaseProductCharge'])->name('AddNewPurchaseProductCharge');
    Route::post('/save/new/purchase-product/charge', [ProductPurchaseChargeController::class, 'saveNewPurchaseProductCharge'])->name('SaveNewPurchaseProductCharge');
    Route::get('/view/all/purchase-product/charge', [ProductPurchaseChargeController::class, 'viewAllPurchaseProductCharge'])->name('ViewAllPurchaseProductCharge');
    Route::get('/delete/purchase-product/charge/{slug}', [ProductPurchaseChargeController::class, 'deletePurchaseProductCharge'])->name('DeletePurchaseProductCharge');
    Route::get('/edit/purchase-product/charge/{slug}', [ProductPurchaseChargeController::class, 'editPurchaseProductCharge'])->name('EditPurchaseProductCharge');
    Route::post('/update/purchase-product/charge', [ProductPurchaseChargeController::class, 'updatePurchaseProductCharge'])->name('UpdatePurchaseProductCharge');

    // Customer 
    Route::get('/add/new/customers', [CustomerController::class, 'addNewCustomer'])->name('AddNewCustomers');
    Route::post('/save/new/customers', [CustomerController::class, 'saveNewCustomer'])->name('SaveNewCustomers');
    Route::get('/view/all/customer', [CustomerController::class, 'viewAllCustomer'])->name('ViewAllCustomer');
    Route::get('/customer-billing', [CustomerBillingController::class, 'index'])->name('CustomerBillingIndex');
    Route::get('/delete/customers/{slug}', [CustomerController::class, 'deleteCustomer'])->name('DeleteCustomers');
    Route::get('/edit/customers/{slug}', [CustomerController::class, 'editCustomer'])->name('EditCustomers');
    Route::post('/update/customers', [CustomerController::class, 'updateCustomer'])->name('UpdateCustomers');
    Route::post('/customers/store', [CustomerController::class, 'customer_store']);
    Route::get('/customers/{user_id?}', [CustomerController::class, 'customers']);

    // generate report
    Route::get('/product/purchase/report', [ReportController::class, 'productPurchaseReport'])->name('productPurchaseReport');
    Route::post('/generate/product/purchase/report', [ReportController::class, 'generateProductPurchaseReport'])->name('generateProductPurchaseReport');

    // product order management

    Route::get('/add/new/product-order/manage', [ProductOrderController::class, 'addNewProductOrder'])->name('AddNewProductOrder');
    Route::post('/save/new/product-order/manage', [ProductOrderController::class, 'saveNewProductOrder'])->name('SaveNewProductOrder');
    Route::get('/view/all/product-order/manage', [ProductOrderController::class, 'viewAllProductOrder'])->name('ViewAllProductOrder');
    Route::get('/delete/product-order/manage/{slug}', [ProductOrderController::class, 'deleteProductOrder'])->name('DeleteProductOrder');
    Route::get('/edit/product-order/manage/{slug}', [ProductOrderController::class, 'editProductOrder'])->name('EditProductOrder');
    Route::get('/edit/product-order/manage/confirm/{slug}', [ProductOrderController::class, 'editProductOrderConfirm'])->name('EditProductOrderConfirm');
    Route::get('api/edit/product-order/manage/{slug}', [ProductOrderController::class, 'apiEditProduct'])->name('ApiEditProductOrder');
    Route::post('/update/product-order/manage', [ProductOrderController::class, 'updateProductOrder'])->name('UpdateProductOrder');
    Route::get('/show/product-order/manage/{slug}', [ProductOrderController::class, 'showProductOrder'])->name('ShowProductOrder');
    Route::get('/pay-due/product-order/manage/{slug}', [ProductOrderController::class, 'payDueProductOrder'])->name('PayDueProductOrder');
    Route::post('/process-payment/product-order/manage/{slug}', [ProductOrderController::class, 'processPaymentProductOrder'])->name('ProcessPaymentProductOrder');
    Route::get('/print/product-order/manage/{slug}', [ProductOrderController::class, 'printProductOrder'])->name('PrintProductOrder');
    Route::get('/return/product-order/manage/{slug}', [ProductOrderController::class, 'returnProductOrder'])->name('ReturnProductOrder');
    Route::post('/process-return/product-order/manage', [ProductOrderController::class, 'processReturnProductOrder'])->name('ProcessReturnProductOrder');
    
    // Courier Management Routes
    Route::get('/product-order/{id}/courier', [\App\Http\Controllers\Courier\CourierController::class, 'showCourier'])->name('ShowCourierOrder');
    
    /** get customer due */
    Route::get('/api/customer-payment-info/{customer_id}', [ProductOrderController::class, 'getCustomerPaymentUpdate'])->name('GetCustomerPaymentInfo');
    
    // Delivery Information Routes
    Route::get('/api/districts', [ProductOrderController::class, 'getDistricts'])->name('GetDistricts');
    Route::get('/api/upazilas/{district_id}', [ProductOrderController::class, 'getUpazilas'])->name('GetUpazilas');
    Route::get('/api/customer-delivery-info/{customer_id}', [ProductOrderController::class, 'getCustomerDeliveryInfo'])->name('GetCustomerDeliveryInfo');
    Route::post('/api/customer-delivery-info/{customer_id}', [ProductOrderController::class, 'saveCustomerDeliveryInfo'])->name('SaveCustomerDeliveryInfo');

    // Product Order Return Management
    Route::get('/return-refund/dashboard', [ProductOrderReturnReportController::class, 'dashboard'])->name('ReturnRefundDashboard');
    Route::get('/return-refund/refunds', [ProductOrderReturnReportController::class, 'refunds'])->name('ViewAllProductOrderRefunds');
    Route::get('/view/all/product-order-returns', [ProductOrderReturnController::class, 'index'])->name('ViewAllProductOrderReturns');
    Route::get('/create/product-order-return/{slug}', [ProductOrderReturnController::class, 'create'])->name('CreateProductOrderReturn');
    Route::post('/store/product-order-return', [ProductOrderReturnController::class, 'store'])->name('StoreProductOrderReturn');
    Route::get('/show/product-order-return/{slug}', [ProductOrderReturnController::class, 'show'])->name('ShowProductOrderReturn');
    Route::post('/refund/product-order-return/{slug}', [ProductOrderReturnController::class, 'storeRefund'])->name('RefundProductOrderReturn');
    Route::post('/reverse/product-order-return/{slug}', [ProductOrderReturnController::class, 'reverseReturn'])->name('ReverseProductOrderReturn');
    Route::get('/show/product-order-refund/{slug}', [ProductOrderReturnController::class, 'showRefund'])->name('ShowProductOrderRefund');
    Route::get('/print/product-order-refund/{slug}', [ProductOrderReturnController::class, 'printRefund'])->name('PrintProductOrderRefund');
    Route::post('/reverse/product-order-refund/{slug}', [ProductOrderReturnController::class, 'reverseRefund'])->name('ReverseProductOrderRefund');
    Route::get('/edit/product-order-return/{slug}', [ProductOrderReturnController::class, 'edit'])->name('EditProductOrderReturn');
    Route::post('/update/product-order-return/{slug}', [ProductOrderReturnController::class, 'update'])->name('UpdateProductOrderReturn');
    Route::get('/delete/product-order-return/{slug}', [ProductOrderReturnController::class, 'destroy'])->name('DeleteProductOrderReturn');
    Route::get('/print/product-order-return/{slug}', [ProductOrderReturnController::class, 'printReturn'])->name('PrintProductOrderReturn');
    
    // API endpoints for return history and original invoice
    Route::get('/api/return-history/{order_id}', [ProductOrderReturnController::class, 'getReturnHistory'])->name('GetReturnHistory');
    Route::get('/api/original-invoice/{order_id}', [ProductOrderReturnController::class, 'getOriginalInvoice'])->name('GetOriginalInvoice');

    // Customer Payment Management
    Route::get('/customer-payment-dashboard', [CustomerPaymentController::class, 'dashboard'])->name('CustomerPaymentDashboard');
    Route::get('/customer-transaction-report', [CustomerPaymentController::class, 'report'])->name('CustomerTransactionReport');
    Route::get('/customer-payment-create/{order_id}', [CustomerPaymentController::class, 'createWithOrder'])->name('CreateCustomerPaymentWithOrder');
    Route::get('/customer-payment-create', [CustomerPaymentController::class, 'create'])->name('CreateCustomerPayment');
    Route::get('/customer-due-payment-create', [CustomerPaymentController::class, 'createDue'])->name('CreateCustomerDuePayment');
    Route::post('/customer-payment-store', [CustomerPaymentController::class, 'store'])->name('StoreCustomerPayment');
    Route::get('/customer-opening-balance', [CustomerPaymentController::class, 'createOpeningBalance'])->name('CreateCustomerOpeningBalance');
    Route::post('/customer-opening-balance-store', [CustomerPaymentController::class, 'storeOpeningBalance'])->name('StoreCustomerOpeningBalance');
    Route::get('/customer-payment-return', [CustomerPaymentController::class, 'createReturn'])->name('CreateCustomerPaymentReturn');
    Route::post('/customer-payment-return-process', [CustomerPaymentController::class, 'processReturn'])->name('ProcessCustomerPaymentReturn');
    Route::get('/customer-payments', [CustomerPaymentController::class, 'index'])->name('ViewAllCustomerPayments');
    Route::get('/customer-payment-history/{customer_id}', [CustomerPaymentController::class, 'history'])->name('ViewCustomerPaymentHistory');

    // Supplier Payment Management
    Route::get('/supplier-payments', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'index'])->name('ViewAllSupplierPayments');
    Route::get('/supplier-payment-create-due/{supplier_id?}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'createDue'])->name('CreateSupplierPaymentDue');
    Route::get('/supplier-payment-create-advance/{supplier_id?}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'createAdvance'])->name('CreateSupplierPaymentAdvance');
    Route::get('/supplier-advance-refund/{supplier_id?}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'createRefund'])->name('CreateSupplierAdvanceRefund');
    Route::post('/supplier-advance-refund-process', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'processRefund'])->name('ProcessSupplierAdvanceRefund');
    Route::get('/supplier-advance-refund-invoice/{ids}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'refundInvoice'])->name('PrintSupplierAdvanceRefundInvoice');
    Route::get('/supplier-opening-balance', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'createOpeningBalance'])->name('CreateSupplierOpeningBalance');
    Route::post('/supplier-opening-balance-store', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'storeOpeningBalance'])->name('StoreSupplierOpeningBalance');
    Route::get('/api/supplier-due-purchases/{supplier_id}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'getSupplierDuePurchases'])->name('GetSupplierDuePurchases');
    Route::get('/api/supplier-advance-balance/{supplier_id}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'getSupplierAdvanceBalance'])->name('GetSupplierAdvanceBalance');
    Route::get('/api/account-balance/{payment_type_id}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'getAccountBalance'])->name('GetSupplierPaymentAccountBalance');
    Route::post('/supplier-payment-store', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'store'])->name('StoreSupplierPayment');
    Route::get('/supplier-payments-list/{supplier_id?}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'viewPayments'])->name('ViewSupplierPayments');
    Route::post('/supplier-payment-void/{id}', [\App\Http\Controllers\Account\SupplierPaymentController::class, 'voidPayment'])->name('VoidSupplierPayment');

    Route::prefix('supplier-cheque-payments')->name('supplier-cheque-payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'destroy'])->name('destroy');
        Route::get('/supplier/{supplierId}/due-purchases', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'getDuePurchases'])->name('due-purchases');
        Route::get('/purchase/{purchaseId}/summary', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'getPurchaseSummary'])->name('purchase-summary');
        Route::patch('/{id}/mark-cleared', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'markCleared'])->name('mark-cleared');
        Route::patch('/{id}/mark-cancelled', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'markCancelled'])->name('mark-cancelled');
        Route::patch('/{id}/mark-bounced', [\App\Http\Controllers\Account\SupplierChequePaymentController::class, 'markBounced'])->name('mark-bounced');
    });
    
    // API endpoint for customer due orders
    Route::get('/api/customer-due-orders/{customer_id}', [CustomerPaymentController::class, 'getCustomerDueOrders'])->name('GetCustomerDueOrders');

    // Manual Product Return Management
    Route::get('/product-return-manual/customer-search', [ManualProductReturnController::class, 'customerSearch'])->name('ManualProductReturnCustomerSearch');
    Route::get('/product-return-manual/product-search', [ManualProductReturnController::class, 'productSearch'])->name('ManualProductReturnProductSearch');
    Route::get('/product-return-manual', [ManualProductReturnController::class, 'create'])->name('CreateManualProductReturn');
    Route::post('/product-return-manual-store', [ManualProductReturnController::class, 'store'])->name('StoreManualProductReturn');
    Route::get('/product-return-manual-list', [ManualProductReturnController::class, 'index'])->name('ViewAllManualProductReturns');
    Route::get('/product-return-manual-show/{slug}', [ManualProductReturnController::class, 'show'])->name('ShowManualProductReturn');
    Route::get('/product-return-manual-edit/{slug}', [ManualProductReturnController::class, 'edit'])->name('EditManualProductReturn');
    Route::post('/product-return-manual-update/{slug}', [ManualProductReturnController::class, 'update'])->name('UpdateManualProductReturn');
    Route::get('/product-return-manual-delete/{slug}', [ManualProductReturnController::class, 'destroy'])->name('DeleteManualProductReturn');

    // Order List Management (Vue + pagination, default order_source=pos)
    Route::get('/product-order/list', [ProductOrderController::class, 'orderListPage'])->name('OrderListPage');

    // Courier wise orders (Vue + pagination)
    Route::get('/orders/courier-wise-orders/{courier}', [ProductOrderController::class, 'courierWiseOrders'])->name('courier-wise-orders');

    // ── Quotation Management ────────────────────────────────────────────────
    Route::get('/quotations',                         [ProductOrderQuotationController::class, 'index'])->name('ViewAllProductOrderQuotations');
    Route::get('/quotation/create',                   [ProductOrderQuotationController::class, 'create'])->name('CreateProductOrderQuotation');
    Route::post('/quotation/store',                   [ProductOrderQuotationController::class, 'store'])->name('StoreProductOrderQuotation');
    Route::get('/quotation/edit/{slug}',              [ProductOrderQuotationController::class, 'edit'])->name('EditProductOrderQuotation');
    Route::post('/quotation/update/{slug}',           [ProductOrderQuotationController::class, 'update'])->name('UpdateProductOrderQuotation');
    Route::get('/quotation/delete/{slug}',            [ProductOrderQuotationController::class, 'destroy'])->name('DeleteProductOrderQuotation');
    Route::get('/quotation/json',                     [ProductOrderQuotationController::class, 'list'])->name('QuotationListJson');
    Route::post('/quotation/quick-status',            [ProductOrderQuotationController::class, 'quickChangeStatus'])->name('QuotationQuickChangeStatus');
    Route::post('/quotation/convert/{slug}',          [ProductOrderQuotationController::class, 'convertToOrder'])->name('ConvertQuotationToOrder');
    Route::get('/quotation/pos-data/{id}',            [ProductOrderQuotationController::class, 'posData'])->name('QuotationPosData');
    Route::get('/quotation/latest-code',              [ProductOrderQuotationController::class, 'latestCode'])->name('QuotationLatestCode');
});

// ── Public purchase invoice (no auth required) ──────────────────────────────
Route::get('/public/purchase-invoice/{slug}', [ProductPurchaseOrderV2Controller::class, 'publicPurchaseInvoice'])->name('PublicPurchaseInvoice');
