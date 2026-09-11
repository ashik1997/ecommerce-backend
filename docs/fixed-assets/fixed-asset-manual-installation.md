# Fixed Asset User Manual Pages Installation

Extract this ZIP at the Laravel project root.

It will add:

- `resources/views/backend/fixed_asset/manual_bn.blade.php`
- `resources/views/backend/fixed_asset/manual_en.blade.php`

Add these routes to your fixed asset route file, for example `routes/fixedAssetRoutes.php`:

```php
Route::get('/fixed-assets/manual/bn', function () {
    return view('backend.fixed_asset.manual_bn');
})->name('fixed-assets.manual.bn');

Route::get('/fixed-assets/manual/en', function () {
    return view('backend.fixed_asset.manual_en');
})->name('fixed-assets.manual.en');
```

Optional sidebar child:

```text
Fixed Asset Management → User Manual
```

Recommended default URL:

```text
/fixed-assets/manual/bn
```
