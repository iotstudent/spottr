<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\BrandController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\IndustryController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\SubCategoryController;
use App\Http\Controllers\Api\v1\RepresentativeController;
use App\Http\Controllers\Api\v1\FeedBackController;
use App\Models\Feedback;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function () {

    Route::group(['prefix' => 'auth'], function () {

        Route::post('/corporate/register', [AuthController::class, 'registerCorporateUser']);

        Route::post('/individual/register', [AuthController::class, 'registerIndividualUser']);

        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

        Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

        Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOTP']);

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::post('/signin', [AuthController::class, 'signIn']);

        Route::post('/admin/signin', [AuthController::class, 'adminSignIn']);

    });

    Route::group(['prefix' => 'industries'], function(){

        Route::get('/', [IndustryController::class, 'index']);

    });


    Route::group(['middleware' => 'auth:sanctum'], function(){

        Route::apiResource('brands', BrandController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('industries', IndustryController::class)->except(['index']);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('sub_categories', SubCategoryController::class);
        Route::apiResource('representatives', RepresentativeController::class);
         Route::apiResource('feedbacks', FeedbackController::class);

        Route::group(['prefix' => 'users'], function(){

            Route::get('/{id}', [UserController::class, 'getUser']);
            Route::put('/block-unblock/{id}', [UserController::class, 'toggleBlockUser']);


            Route::get('/', [UserController::class, 'getProfile']);
            Route::post('/change-password', [UserController::class, 'changePassword']);
            Route::post('/corporate/update/', [UserController::class, 'updateCorporate']);
            Route::delete('/deactivate', [UserController::class, 'deactivateAccount']);
            Route::post('/authorization', [UserController::class, 'authorizeUser']);
            Route::post('/check-transaction-pin', [UserController::class, 'validateTransactionPin']);
            Route::post('/get-transaction-otp-pin', [UserController::class, 'generateTransactionPinOtp']);
            Route::post('/change-transaction-pin', [UserController::class, 'confirmTransactionPinChange']);


            Route::post('/create-admin', [UserController::class, 'createAdmin']);
            Route::patch('/toggle-user-activation', [UserController::class, 'UserActivation']);


        });

        Route::group(['prefix' => 'products'], function(){

            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::post('/', [ProductController::class, 'store']);
            Route::post('/update/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);

        });

    });

});

