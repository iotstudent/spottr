<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\BrandController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductListingController;
use App\Http\Controllers\Api\v1\ProductRequestController;
use App\Http\Controllers\Api\v1\IndustryController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\SubCategoryController;
use App\Http\Controllers\Api\v1\RepresentativeController;
use App\Http\Controllers\Api\v1\FeedBackController;
use App\Http\Controllers\Api\v1\SubscriptionController;
use App\Http\Controllers\Api\v1\SubscriptionPlanController;
use App\Http\Controllers\Api\v1\BankAccountController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\TransactionController;
use App\Http\Controllers\Api\v1\UserAddressController;
use App\Http\Controllers\Api\v1\MembershipController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function () {

    Route::group(['prefix' => 'threshold'], function(){

        Route::post('/', [PaymentController::class, 'verifyCryptoTopUp']);
    });

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

    Route::group(['prefix' => 'subscription-plans'], function(){

        Route::get('/', [SubscriptionPlanController::class, 'index']);


    });

     Route::group(['prefix' => 'payments'], function(){
            Route::get('/verify/fiat', [PaymentController::class, 'verifyFiatPayment'])->name('payment.callback');
    });


    Route::get('/get-banks', [BankAccountController::class, 'getBanks']);
    Route::post('/verify-bank-account', [BankAccountController::class, 'verifyBankAccount']);




    Route::group(['middleware' => 'auth:sanctum'], function(){

        Route::apiResource('brands', BrandController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('industries', IndustryController::class)->except(['index']);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('sub_categories', SubCategoryController::class);
        Route::apiResource('representatives', RepresentativeController::class);
        Route::apiResource('feedbacks', FeedbackController::class);
        Route::apiResource('subscription-plans', SubscriptionPlanController::class)->except(['index']);

        Route::group(['prefix' => 'users'], function(){


            Route::get('/all', [UserController::class, 'index']);

            Route::get('/{id}', [UserController::class, 'show']);

            Route::get('/', [UserController::class, 'getProfile']);
            Route::post('/change-password', [UserController::class, 'changePassword']);

            Route::post('/corporate/update/', [UserController::class, 'updateCorporate']);

            Route::post('/buyer/update/', [UserController::class, 'updateBuyer']);
            Route::post('/buyer/update/profile-image', [UserController::class, 'updateBuyerProfileImage']);

            Route::post('/store/update/', [UserController::class, 'updateStore']);
            Route::post('/store/update/background-image', [UserController::class, 'updateStoreBgImage']);
            Route::post('/store/update/profile-image', [UserController::class, 'updateStoreProfileImage']);

            Route::delete('/deactivate', [UserController::class, 'deactivateAccount']);
            Route::post('/authorization', [UserController::class, 'authorizeUser']);

            Route::post('/check-transaction-pin', [UserController::class, 'validateTransactionPin']);
            Route::get('/send/transaction-otp-pin', [UserController::class, 'generateTransactionPinOtp']);
            Route::post('/change-transaction-pin', [UserController::class, 'confirmTransactionPinChange']);


            Route::post('/create/admin', [UserController::class, 'createAdmin']);
            Route::post('/create/corporate', [UserController::class, 'createCorporateAccountByAdmin']);
            Route::post('/update/corporate/{id}', [UserController::class, 'updateCorporateAccountByAdmin']);
            Route::patch('/block-unblock/{id}', [UserController::class, 'UserActivation']);
             Route::patch('/swith-type', [UserController::class, 'switchToBuyerOrSeller']);



        });

        Route::group(['prefix' => 'bank-accounts'], function(){

            Route::post('/create', [BankAccountController::class, 'store']);
            Route::get('/', [BankAccountController::class, 'index']);
            Route::get('/user/{userId}', [BankAccountController::class, 'getByUser']);
            Route::patch('/default/{id}', [BankAccountController::class, 'setDefault']);
            Route::delete('/{id}', [BankAccountController::class, 'destroy']);
        });

        Route::group(['prefix' => 'representatives'], function(){


           Route::get('/corporate/{corporateProfileId}', [RepresentativeController::class, 'fetchByCorporateRepresentativeProfile']);


        });

        Route::group(['prefix' => 'products'], function(){

            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::get('/vendors/{productId}', [ProductController::class, 'getListingsByProduct']);
            Route::post('/', [ProductController::class, 'store']);
            Route::post('/update/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);

        });

        Route::group(['prefix' => 'product-listings'], function(){

            Route::get('/', [ProductListingController::class, 'index']);
            Route::get('/{productLisitingId}', [ProductListingController::class, 'show']);
            Route::post('/update/{id}', [ProductListingController::class, 'update']);
            Route::post('/', [ProductListingController::class, 'store']);
            Route::patch('/activate-inactivate/{id}', [ProductListingController::class, 'toggleStatus']);
            Route::post('/update-image/{id}', [ProductListingController::class, 'updateImage']);
            Route::delete('/{id}', [ProductListingController::class, 'destroy']);

        });

        Route::group(['prefix' => 'product-requests'], function(){

            Route::get('/', [ProductRequestController::class, 'index']);
            Route::post('/', [ProductRequestController::class, 'store']);
            Route::post('/approve/{id}', [ProductRequestController::class, 'approve']);
            Route::post('/reject/{id}', [ProductRequestController::class, 'reject']);

        });

        Route::group(['prefix' => 'subscription-plans'], function(){

            Route::post('/{subscription_plan}/upload-image', [SubscriptionPlanController::class, 'uploadImage']);
            Route::patch('/{subscription_plan}/toggle', [SubscriptionPlanController::class, 'toggleActivation']);
            Route::post('/{subscription_plan}/add-feature', [SubscriptionPlanController::class, 'addFeature']);
            Route::delete('/{subscription_plan}/remove-feature/{feature}', [SubscriptionPlanController::class, 'removeFeature']);

        });

        Route::group(['prefix' => 'payments'], function(){
            Route::post('/initiate/wallet-top-up/fiat', [PaymentController::class, 'initiateWalletTopUp']);
            Route::post('/initiate/wallet-top-up/fiat/mobile', [PaymentController::class, 'initiateWalletTopUpMobile']);
            // Route::get('/verify/fiat', [PaymentController::class, 'verifyFiatPayment'])->name('payment.callback');
        });

        Route::group(['prefix' => 'subscriptions'], function(){

            Route::get('/', [SubscriptionController::class, 'index']);
            Route::post('/', [SubscriptionController::class, 'store']);


        });

        Route::group(['prefix' => 'into-wallet-transactions'], function(){

            Route::get('/', [TransactionController::class, 'index']);

        });

        Route::group(['prefix' => 'transactions'], function(){

            Route::get('/', [TransactionController::class, 'indexTransaction']);

        });

        Route::group(['prefix' => 'crypto-addresses'], function(){

            Route::get('/', [UserAddressController::class, 'index']);

        });

        Route::group(['prefix' => 'memberships'], function(){

            Route::get('/', [MembershipController::class, 'index']);

            Route::post('/invite', [MembershipController::class, 'invite']);
            Route::put('/revoke/{id}', [MembershipController::class, 'revoke']);
            Route::delete('/remove/{id}', [MembershipController::class, 'removeMembership']);


            Route::post('/apply', [MembershipController::class, 'apply']);
            Route::put('/respond/{id}', [MembershipController::class, 'respond']);



        });



    });

});

