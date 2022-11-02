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

Route::post('/signup', 'API\AuthController@signupForDriver');
Route::post('/resignup', 'API\AuthController@resignupForDriver');
Route::post('/signin', 'API\AuthController@signinForDriver');
Route::post('/social_signin', 'API\AuthController@socialSigninForDriver');
Route::post('/verify', 'API\AuthController@verifyForDriver');
Route::post('/forgot_password', 'API\AuthController@forgotpasswordForDriver');
Route::post('/reset_password', 'API\AuthController@resetpasswordForDriver');
Route::post('/change_password', 'API\AuthController@changepasswordForDriver');

Route::post('/get_doc_type', 'API\BasedataController@getDocType');
Route::post('/get_my_doc', 'API\BasedataController@getMyDoc');
Route::post('/upload_doc', 'API\BasedataController@uploadDoc');
Route::post('/get_service_type', 'API\BasedataController@getServiceType');

Route::post('/get_profile', 'API\ProfileController@getDriverProfile');
Route::post('/update_profile', 'API\ProfileController@updateDriverProfile');
Route::post('/update_quickblox', 'API\ProfileController@updateDriverQuickblox');

Route::post('/all_rides', 'API\RideController@getAllRides');
Route::post('/finished_rides', 'API\RideController@getDriverFinishedRides');
Route::post('/finished_rides2', 'API\RideController@getDriverFinishedRides2');
Route::post('/current_ride', 'API\RideController@getDriverCurrentRide');
Route::post('/booking_rides', 'API\RideController@getDriverBookingRides');
Route::post('/requested_rides', 'API\RideController@getDriverRequestedRides');
Route::post('/cancel_ride', 'API\RideController@cancelDriverRide');
Route::post('/total_rides', 'API\RideController@getDriverTotalRides');

Route::post('/add_card', 'API\PaymentController@addCard');
Route::post('/get_card', 'API\PaymentController@getCard');
Route::post('/update_card', 'API\PaymentController@updateCard');
Route::post('/delete_card', 'API\PaymentController@deleteCard');
Route::post('/charge', 'API\PaymentController@chargeForDriver');
Route::post('/get_braintree_token', 'API\PaymentController@getBraintreeTokenForDriver');
//Route::post('/payout', 'API\PaymentController@payoutForDriver');
Route::post('/payout', 'API\PaymentController@rawpayoutForDriver');
Route::post('/get_balance', 'API\PaymentController@getBalanceForDriver');
Route::post('/estimated_fair', 'API\BasedataController@getDriverEstimatedFair');

Route::post('/add_carimage', 'API\ProfileController@addCarImage');
Route::post('/update_carimage', 'API\ProfileController@updateCarImage');
Route::post('/delete_carimage', 'API\ProfileController@deleteCarImage');

Route::post('/sos_email', 'API\AuthController@sendSOSEmailForDriver');

