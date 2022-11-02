<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyCodeMailable;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/signup', 'API\AuthController@signupForUser');
Route::post('/resignup', 'API\AuthController@resignupForUser');
Route::post('/signin', 'API\AuthController@signinForUser');
Route::post('/social_signin', 'API\AuthController@socialSigninForUser');
Route::post('/verify', 'API\AuthController@verifyForUser');
Route::post('/forgot_password', 'API\AuthController@forgotpasswordForUser');
Route::post('/reset_password', 'API\AuthController@resetpasswordForUser');
Route::post('/change_password', 'API\AuthController@changepasswordForUser');

Route::post('/get_service_type', 'API\BasedataController@getServiceType');

Route::post('/get_profile', 'API\ProfileController@getUserProfile');
Route::post('/update_profile', 'API\ProfileController@updateUserProfile');
Route::post('/update_quickblox', 'API\ProfileController@updateUserQuickblox');

Route::post('/add_card', 'API\PaymentController@addCard');
Route::post('/get_card', 'API\PaymentController@getCard');
Route::post('/update_card', 'API\PaymentController@updateCard');
Route::post('/delete_card', 'API\PaymentController@deleteCard');
Route::post('/charge', 'API\PaymentController@chargeForUser');
Route::post('/get_braintree_token', 'API\PaymentController@getBraintreeTokenForUser');
Route::post('/pay', 'API\PaymentController@payForUser');
Route::post('/get_balance', 'API\PaymentController@getBalanceForUser');

Route::post('/finished_rides', 'API\RideController@getUserFinishedRides');
Route::post('/finished_rides2', 'API\RideController@getUserFinishedRides2');
Route::post('/current_ride', 'API\RideController@getUserCurrentRide');
Route::post('/booking_rides', 'API\RideController@getUserBookingRides');
Route::post('/cancel_ride', 'API\RideController@cancelUserRide');
Route::post('/estimated_fair', 'API\BasedataController@getUserEstimatedFair');
Route::post('/balance_history', 'API\PaymentController@getUserBalanceHistory');
Route::post('/search_place', 'API\MapController@getSearchPlaces');
Route::post('/get_place_type', 'API\MapController@getPlaces');

Route::post('/add_coupon', 'API\CouponController@addMyCoupon');
Route::post('/get_coupon', 'API\CouponController@getMyCoupon');



Route::post('/sos_email', 'API\AuthController@sendSOSEmailForUser');


