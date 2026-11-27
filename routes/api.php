<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OccasionController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ResultsController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('packages', [ShopController::class, 'packages'])->name('packages');

    Route::middleware('throttle:auth-code')->post('auth/request-code', [AuthController::class, 'requestCode'])->name('auth.request-code');
    Route::middleware('throttle:auth-verify')->post('auth/verify', [AuthController::class, 'verify'])->name('auth.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        // Setup: the shop exists but may not be finished.
        Route::middleware('shop')->group(function () {
            Route::get('shop', [ShopController::class, 'show'])->name('shop.show');
            Route::patch('shop', [ShopController::class, 'update'])->name('shop.update');
            Route::post('shop/setup/complete', [ShopController::class, 'completeSetup'])->name('shop.setup.complete');
            Route::post('shop/setup/{step}', [ShopController::class, 'setupStep'])->whereNumber('step')->name('shop.setup.step');
        });

        Route::middleware('shop:ready')->group(function () {
            Route::get('dashboard', DashboardController::class)->name('dashboard');
            Route::get('studio', [AccountController::class, 'studio'])->name('studio');

            Route::get('content', [ContentController::class, 'index'])->name('content.index');
            Route::post('content', [ContentController::class, 'store'])->name('content.store');
            Route::post('content/approve-batch', [ContentController::class, 'approveBatch'])->name('content.approve-batch');
            Route::get('content/{id}', [ContentController::class, 'show'])->whereNumber('id')->name('content.show');
            Route::patch('content/{id}', [ContentController::class, 'update'])->whereNumber('id')->name('content.update');
            foreach (['approve', 'schedule', 'publish', 'unpublish', 'skip'] as $action) {
                Route::post("content/{id}/{$action}", [ContentController::class, $action])->whereNumber('id')->name("content.{$action}");
            }

            Route::get('plans/{month}', [PlanController::class, 'show'])->where('month', '\d{4}-\d{2}')->name('plans.month');
            Route::get('plans/id/{id}', [PlanController::class, 'find'])->whereNumber('id')->name('plans.find');
            Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
            Route::post('plans/{id}/approve', [PlanController::class, 'approve'])->whereNumber('id')->name('plans.approve');

            Route::get('occasions', [OccasionController::class, 'index'])->name('occasions');

            Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
            Route::get('campaigns/{id}', [CampaignController::class, 'show'])->whereNumber('id')->name('campaigns.show');
            Route::patch('campaigns/{id}', [CampaignController::class, 'update'])->whereNumber('id')->name('campaigns.update');

            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::post('customers/win-back', [CustomerController::class, 'winBack'])->name('customers.win-back');
            Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('customers.show');
            Route::patch('customers/{id}', [CustomerController::class, 'update'])->whereNumber('id')->name('customers.update');

            Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
            Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
            Route::patch('messages/{id}', [MessageController::class, 'update'])->whereNumber('id')->name('messages.update');
            Route::post('messages/{id}/schedule', [MessageController::class, 'schedule'])->whereNumber('id')->name('messages.schedule');

            Route::get('workflows', [WorkflowController::class, 'index'])->name('workflows.index');
            Route::patch('workflows/{id}', [WorkflowController::class, 'update'])->whereNumber('id')->name('workflows.update');

            Route::get('results', ResultsController::class)->name('results');

            Route::get('billing', [BillingController::class, 'show'])->name('billing');
            Route::post('billing/package', [BillingController::class, 'changePackage'])->name('billing.package');
            Route::post('credits/topup', [BillingController::class, 'topUpCredits'])->name('credits.topup');
            Route::post('wallet/topup', [BillingController::class, 'topUpWallet'])->name('wallet.topup');

            Route::get('team', [AccountController::class, 'team'])->name('team.index');
            Route::post('team', [AccountController::class, 'invite'])->name('team.invite');
            Route::get('connections', [AccountController::class, 'connections'])->name('connections.index');
            Route::patch('connections/{provider}', [AccountController::class, 'updateConnection'])->name('connections.update');
            Route::get('notifications', [AccountController::class, 'notifications'])->name('notifications.index');
            Route::post('notifications/read', [AccountController::class, 'readNotifications'])->name('notifications.read');
        });
    });
});
