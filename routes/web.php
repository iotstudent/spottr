<?php

use Illuminate\Support\Facades\Route;


Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});


Route::get('/migrations', function () {
    Artisan::call('migrate');
    return 'Database migrated successfully.';
});
