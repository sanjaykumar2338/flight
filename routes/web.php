<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AirlineCommissionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Bookings\BookingController as UserBookingController;
use App\Http\Controllers\Bookings\BookingDemoController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\OfferPricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Payments\PaystackCallbackController;
use App\Http\Controllers\Payments\PaystackCheckoutController;
use App\Http\Controllers\Payments\PaystackWebhookController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FlightSearchController::class, 'index'])->name('flights.search');
Route::post('/offers/price', OfferPricingController::class)->name('offers.price');
Route::post('/checkout/paystack', PaystackCheckoutController::class)->name('checkout.paystack');
Route::get('/bookings/{booking}/payment/callback', PaystackCallbackController::class)->name('bookings.paystack.callback');
Route::post('/payments/stripe/checkout/{booking}', [StripeController::class, 'checkout'])->name('payments.stripe.checkout');
Route::get('/payments/stripe/success', [StripeController::class, 'success'])->name('payments.stripe.success');

Route::get('/bookings/{booking}/demo', [BookingDemoController::class, 'show'])->name('bookings.demo');
Route::post('/bookings/{booking}/demo/{status}', [BookingDemoController::class, 'simulate'])->name('bookings.demo.simulate');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [UserBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [UserBookingController::class, 'show'])->name('bookings.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('pricing', [PricingRuleController::class, 'index'])->name('pricing.index');
    Route::post('pricing/rules', [PricingRuleController::class, 'store'])->name('pricing.rules.store');
    Route::put('pricing/rules/{pricingRule}', [PricingRuleController::class, 'update'])->name('pricing.rules.update');
    Route::delete('pricing/rules/{pricingRule}', [PricingRuleController::class, 'destroy'])->name('pricing.rules.destroy');
    Route::post('pricing/import-legacy', [PricingRuleController::class, 'importLegacy'])->name('pricing.import-legacy');
    Route::resource('airline-commissions', AirlineCommissionController::class)->except(['create', 'edit', 'show']);
    Route::resource('bookings', AdminBookingController::class)->only(['index', 'show']);
    Route::resource('payments', PaymentController::class)->only(['index', 'show']);
});

Route::post('/webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

require __DIR__.'/auth.php';
