<?php

namespace App\Http\Controllers\API;

use App\Http\Constants;
use App\Http\Controllers\SendPushNotification;
use App\Http\Models\Coupons;
use App\Http\Models\DriverProfile;
use App\Http\Models\Drivers;
use App\Http\Models\Rides;
use App\Http\Models\ServiceTypes;
use App\Http\Models\Settings;
use App\Http\Models\Wallet;
use App\Http\Utils;
use App\Http\PayPal;
use App\UserRequestPayment;
use App\UserRequests;
use Braintree_ClientToken;
use Braintree_Configuration;
use Braintree_Transaction;
use App\Http\Hyperwallet;
use DateTime;
use Hyperwallet\Model\BankAccount;
use Hyperwallet\Model\BankCard;
use Hyperwallet\Model\Payment;
use Hyperwallet\Model\PayPalAccount;
use Hyperwallet\Model\Transfer;
use Hyperwallet\Model\User;
use Illuminate\Http\Request;
use App\Http\Models\Users;
use App\Http\Models\Cards;
use App\Http\Models\UserWallet;
use Illuminate\Routing\Controller;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\StripeInvalidRequestError;
use Stripe\Token;
use Exception;
use Validator;

class PaymentController extends Controller
{
    private $_api_context;
    private $hyperwallet;
    private $hyperwallet_program_token;

    // constructor
    public function __construct(){
        Braintree_Configuration::environment(Settings::where('key', 'braintree_env')->first()->value);
        Braintree_Configuration::merchantId(Settings::where('key', 'braintree_merchant_id')->first()->value);
        Braintree_Configuration::publicKey(Settings::where('key', 'braintree_public_key')->first()->value);
        Braintree_Configuration::privateKey(Settings::where('key', 'braintree_private_key')->first()->value);

        $hyperwallet_server = Settings::where('key', 'hyperwallet_program_token')->first()->value;
        $hyperwallet_username = Settings::where('key', 'hyperwallet_username')->first()->value;
        $hyperwallet_password = Settings::where('key', 'hyperwallet_password')->first()->value;
        $hyperwallet_program_token = Settings::where('key', 'hyperwallet_program_token')->first()->value;
        $this->hyperwallet_program_token = $hyperwallet_program_token;
        $this->hyperwallet = new Hyperwallet($hyperwallet_username, $hyperwallet_password, $hyperwallet_program_token, $hyperwallet_server);
    }
    /**
     * add card for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function addCard(Request $request) {
        $rules=[
            'api_token'=>'required|max:255',
            'brand'=>'required|max:50',
            'card_no'=>'required|max:255|unique:t_cards',
            'expiry_year' => 'required|integer:4',
            'expiry_month' => 'required|integer:2',
            'cvc' => 'required|integer:10',
            'atype' =>'required|in:user,driver'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check if user is valid
            if ($request->atype == 'user') {
                if (!$owner=Users::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            if ($request->atype == 'driver') {
                if (!$owner=Drivers::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            $card = $request->all();
            $card['email'] = $owner->email;

            Cards::create($card);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_ADDCARD
            ]);

        } catch (Exception $e) {
 //           return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * update card for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateCard(Request $request) {
        $rules=[
            'api_token'=>'required|max:255',
            'brand'=>'required|max:50',
            'card_no'=>'required|max:255',
            'expiry_year' => 'required|integer:4',
            'expiry_month' => 'required|integer:2',
            'cvc' => 'required|integer:10',
            'atype' =>'required|in:user,driver'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check if user is valid
            if ($request->atype == 'user') {
                if (!$owner=Users::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            if ($request->atype == 'driver') {
                if (!$owner=Drivers::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            Cards::where('card_no', $request->card_no)->update(
                                                            ['brand'=>$request->brand,
                                                                'expiry_year'=>$request->expiry_year,
                                                                'expiry_month'=>$request->expiry_month,
                                                                'cvc'=>$request->cvc
                                                            ]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATECARD
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * delete card for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteCard(Request $request) {
        $rules=[
            'api_token'=>'required|max:255',
            'card_no'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check if user is valid
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            Cards::where('card_no', $request->card_no)->delete();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_DELETECARD
            ]);

        } catch (Exception $e) {
//            return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * get card list for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCard(Request $request) {
        $rules=[
            'api_token'=>'required|max:255',
            'atype' =>'required|in:user,driver'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check if user is valid
            if ($request->atype == 'user') {
                if (!$owner=Users::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            if ($request->atype == 'driver') {
                if (!$owner=Drivers::where('api_token', $request->api_token)->first()) {
                    return Utils::makeResponse([
                        'message'=>Constants::$E_INVALID_APITOKEN
                    ], 0);
                }
            }
            $cards = Cards::where('email', $owner->email)->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GETCARD,
                'cards'=>$cards
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }




    /**
     * add wallet money for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function chargeForStripe(Request $request){
        // check valid email
        if (!$user=Users::where('api_token', $request->api_token)->first()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_INVALID_EMAIL
            ], 0);
        }

        $rules=[
            'api_token'=>'required|max:255',
            'amount' => 'required|integer:10',
            'card_no' => 'required|exists:t_cards,card_no,user_id,'.$user->id
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            $card = Cards::where(array('user_id'=>$user->id, 'card_no'=>$request->card_no))->first();
            $charge_amount = $request->amount * 100;
            $strip_sk = Settings::where('key', 'stripe_secret_key')->first()->value;
            Stripe::setApiKey($strip_sk);
            $cardInfo = array();
            $cardInfo['number'] = $request->card_no;
            $cardInfo['exp_month'] = $card->expiry_month;
            $cardInfo['exp_year'] = $card->expiry_year;
            $cardInfo['cvc'] = $card->cvc;
            $token = Token::create(array("card" => $cardInfo));
            $token = $token->id;

            Charge::create(array(
                "amount" => $charge_amount,
                "currency" => "usd",
                "card" => $token,
                "description" => "Adding Money for ".$user->email,
                //"receipt_email" => $user->email
            ));

            UserWallet::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'status' => 'in',
                'via' => 'card',
            ]);

            Cards::where('user_id',$user->id)->update(['is_default' => 0]);
            Cards::where('card_no',$request->card_no)->update(['is_default' => 1]);

            //sending push on adding wallet money
            //(new SendPushNotification)->WalletMoney(Auth::user()->id,currency($request->amount));

            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_CHARGE
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
            'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * pay for ride by user.
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function payForStripe(Request $request) {
        $rules=[
            'api_token'=>'required|max:255',
            'payment_method'=>'required|in:cash,card',
            'amount'=>'required|integer:10',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        // check valid email
        if (!$user=Users::where('api_token', $request->api_token)->first()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_INVALID_EMAIL
            ], 0);
        }

        try {
            $ride = Rides::where(array('user_id'=>$user->id, status=>Constants::$RIDE_STATUS_DRIVERENDED))->first();
            $pay_amount = $request->amount * 100;
            if($request->payment_mode == 'card') {
                $card=Cards::where(array('user_id'=>$user->id, 'card_no'=>$request->card_no))->first();
                $strip_sk = Settings::where('key', 'stripe_secret_key')->first()->value;
                Stripe::setApiKey($strip_sk);
                $cardInfo = array();
                $cardInfo['number'] = $request->card_no;
                $cardInfo['exp_month'] = $card->expiry_month;
                $cardInfo['exp_year'] = $card->expiry_year;
                $cardInfo['cvc'] = $card->cvc;
                $token = Token::create(array("card" => $cardInfo));
                $token = $token->id;

                $Charge = Charge::create(array(
                    "amount"=>$pay_amount,
                    "currency"=>"usd",
                    "customer"=>Auth::user()->stripe_cust_id,
                    "card"=>$card->card_id,
                    "description"=>"Payment Charge for " . $user->email,
                    "receipt_email"=>$user->email
                ));

                //                    $RequestPayment->payment_id = $Charge["id"];
                //                    $RequestPayment->payment_mode = 'CARD';
                //                    $RequestPayment->save();
                //
                //                    $UserRequest->paid = 1;
                //                    $UserRequest->status = 'COMPLETED';
                //                    $UserRequest->save();
                //
                //                    if($request->ajax()) {
                //                        return response()->json(['message' => trans('api.paid')]);
                //                    } else {
                //                        return redirect('dashboard')->with('flash_success','Paid');
                //                    }
            }
            if($request->payment_mode == 'cash') {


            }

        } catch(Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * add wallet money for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBalanceForUser(Request $request){

        $rules=[
            'api_token'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }

            return Utils::makeResponse([
                'message' =>Constants::$S_SUCCESS_GET,
                'balance' =>Wallet::getMyBalance($user->email)
            ]);


        } catch (Exception $e) {
//            return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    public function getBalanceForDriver(Request $request){

        $rules=[
            'api_token'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }

            return Utils::makeResponse([
                'message' =>Constants::$S_SUCCESS_GET,
                'balance' =>Wallet::getMyBalance($driver->email)
            ]);


        } catch (Exception $e) {
//            return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    public function chargeForUser(Request $request){

        $rules=[
            'api_token'=>'required|max:255',
            'amount' => 'required|numeric:10|gt:0',
            'nonce' => 'required|max:255',
            'type' => 'required|max:20',
            //'type_value' => 'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $result = Braintree_Transaction::sale([
                'amount' => $request->amount,
                'paymentMethodNonce' => $request->nonce,
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);
            if ($result->success || !is_null($result->transaction)) {
                $pp_fee = Utils::paypalfee($request->amount);
                $wallet_data['email'] =$user->email;
                $wallet_data['pp_fee'] =$pp_fee;
                $wallet_data['total'] = $request->amount;
                $wallet_data['amount'] = $request->amount-$pp_fee;
                $wallet_data['cooper_fee'] = 0;
                $wallet_data['in_out'] = 'in';
                $wallet_data['purpose'] = 'charge';
                if ($result->transaction->creditCardDetails) {
                    $wallet_data['via'] = $request->type;
                    $wallet_data['via_value'] = $result->transaction->creditCardDetails->maskedNumber;
                } else {
                    $wallet_data['via'] = 'paypal';
                }
                $wallet_data['status'] = 'settled';
                $wallet_data['nonce'] = $request->nonce;
                $wallet_data['currency'] = $result->transaction->currencyIsoCode;
                Wallet::create($wallet_data);
                return Utils::makeResponse([
                    'message' =>Constants::$S_PAY_CHARGE_SUCCESS,
                    'balance' =>Wallet::getMyBalance($user->email)
                ]);
            } else {
                return Utils::makeResponse([
                    'message' =>Constants::$E_PAY_CHARGE_ERROR
                ], 0);
//                return Utils::makeResponse([
//                    'message' => $result
//                ], 0);
            }


        } catch (Exception $e) {
//            return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    public function chargeForDriver(Request $request){

        $rules=[
            'api_token'=>'required|max:255',
            'amount' => 'required|numeric:10|gt:0',
            'nonce' => 'required|max:255',
            'type' => 'required|max:20',
            //'type_value' => 'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $result = Braintree_Transaction::sale([
                'amount' => $request->amount,
                'paymentMethodNonce' => $request->nonce,
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);
            if ($result->success || !is_null($result->transaction)) {
                $pp_fee = Utils::paypalfee($request->amount);
                $wallet_data['email'] =$driver->email;
                $wallet_data['pp_fee'] =$pp_fee;
                $wallet_data['total'] = $request->amount;
                $wallet_data['amount'] = $request->amount-$pp_fee;
                $wallet_data['cooper_fee'] = 0;
                $wallet_data['in_out'] = 'in';
                $wallet_data['purpose'] = 'charge';
                $wallet_data['via'] = $request->type;
                //$wallet_data['via_value'] = $request->type_value;
                $wallet_data['status'] = 'settled';
                $wallet_data['nonce'] = $request->nonce;
                $wallet_data['currency'] = $result->transaction->currencyIsoCode;
                Wallet::create($wallet_data);
                return Utils::makeResponse([
                    'message' =>Constants::$S_PAY_CHARGE_SUCCESS,
                    'balance' =>Wallet::getMyBalance($driver->email)
                ]);
            } else {
                return Utils::makeResponse([
                    'message' =>Constants::$E_PAY_CHARGE_ERROR
                ], 0);
//                return Utils::makeResponse([
//                    'message' => $result
//                ], 0);
            }


        } catch (Exception $e) {
            return $e->getMessage();
//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }
    }

    public function getBraintreeTokenForUser(Request $request){
        $rules=[
            'api_token'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try {
            // check valid email
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            return Utils::makeResponse([
                'token' =>Braintree_ClientToken::generate()
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }
    }

    public function getBraintreeTokenForDriver(Request $request){
        $rules=[
            'api_token'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try {
            // check valid email
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            return Utils::makeResponse([
                'token' =>Braintree_ClientToken::generate()
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }
    }

    public function payForUser(Request $request){

        $rules=[
            'api_token'=>'required|max:255',
            'email'=>'required|max:255',
            'coupon_code'=>'max:50',
            'ride_id'=>'required|max:20'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }

            // GET ride
            if (!$ride = Rides::where('id', $request->ride_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RIDE
                ], 0);
            }

            if ($ride->payment_method != 'wallet' && $ride->payment_method != 'cash') {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_PAYMENT_METHOD
                ], 0);
            }
            $driver_data = array();
            $user_data = array();
            $ride_data = array();
            $discount_fee = 0;

            $kilometer = round($ride->distance/1000, 2);
            $minutes = round($ride->rental_hours/60, 2);
            $rental = ceil($ride->rental_hours);

            $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
            $service_type = ServiceTypes::findOrFail($ride->service_type);

            $base_fee = $service_type->fixed;
            $hour = $service_type->hour;
//            if($service_type->calculator == 'MIN') {
//                $distance_fee = $service_type->minute * $minutes;
//            } else if($service_type->calculator == 'HOUR') {
//                $distance_fee = $service_type->minute * 60;
//            } else if($service_type->calculator == 'DISTANCE') {
//                $distance_fee = ($kilometer * $service_type->price);
//            } else if($service_type->calculator == 'DISTANCEMIN') {
//                $distance_fee = ($kilometer * $service_type->price) + ($service_type->minute * $minutes);
//            } else if($service_type->calculator == 'DISTANCEHOUR') {
//                $distance_fee = ($kilometer * $service_type->price) + ($rental * $hour);
//            } else {
//                $distance_fee = ($kilometer * $service_type->price);
//            }
            $distance_fee = $kilometer * $service_type->price;
            $distance_fee = round($distance_fee, 2);
            if ($request->coupon_code) {
                $coupon = Coupons::where(array('coupon_code'=>$request->coupon_code, 'status'=>Constants::$COUPON_STATUS_CREATED))
                        ->where('expiration', '>', date('Y-m-d H:i:s'))->first();
                if (!$coupon) {
                    return Utils::makeResponse([
                        'message'=>Constants::$NO_SUCH_COUPONCODE
                    ], 0);
                } else {
                    if ($coupon->discount_type == 'amount') {
                        $discount_fee = $coupon->discount;
                    }
                    if ($coupon->discount_type == 'percent') {
                        $discount_fee = round(($base_fee + $distance_fee)*$coupon->discount/100, 2);
                    }
                    $coupon->update(['user_id'=>$user->id, 'status'=>Constants::$COUPON_STATUS_USED, 'used_at'=>date('Y-m-d H:i:s')]);
                    $ride_data['coupon_id'] = $coupon->id;
                    $ride_data['discount_fee'] = $discount_fee;
                }
            }
            $sub_total = $base_fee + $distance_fee - $discount_fee;
            $tax_fee = round(( $tax_percentage/100 ) * $sub_total, 2);
            $total = $sub_total + $tax_fee;
            $commission_percentage = Settings::where('key', 'commission_percentage')->first()->value;
            $cooper_fee = round($total * $commission_percentage/100, 2);
            $cooper_tax_fee = round(( $tax_percentage/100 ) * $cooper_fee, 2);
            $ride_data['base_fee'] = $base_fee;
            $ride_data['distance_fee'] = $distance_fee;
            $ride_data['tax_fee'] = $tax_fee;
            $ride_data['cooper_fee'] = $cooper_fee;
            $ride_data['cooper_tax_fee'] = $cooper_tax_fee;
            $ride_data['pay_amount'] = $total;
            $user_start_balance = Wallet::getMyBalance($user->email);
            $driver_start_balance = Wallet::getMyBalance($driver->email);
            $ride_data['user_start_balance'] = $user_start_balance;
            $ride_data['driver_start_balance'] = $driver_start_balance;
            $ride->update($ride_data);

            if ($ride->payment_method == 'wallet') {
                $driver_data['amount'] = $total - $cooper_fee - $cooper_tax_fee;
                $user_data['amount'] = (-1) * $total;
            } elseif ($ride->payment_method == 'cash') {
                $driver_data['amount'] = (-1) * ($cooper_fee + $cooper_tax_fee);
                $user_data['amount'] = 0;
            }

            $driver_data['email'] =$request->email;
            $driver_data['pp_fee'] =0;
            $driver_data['total'] = $total;
            $driver_data['cooper_fee'] = $cooper_fee;
            if ($discount_fee > 0) {
                $driver_data['discount_fee'] = $discount_fee;
                $driver_data['coupon_id'] = $coupon->id;
            }
            $driver_data['purpose'] = 'pay';
            $driver_data['payment_method'] = $ride->payment_method;
            $driver_data['status'] = 'settled';
            $driver_data['currency'] = 'USD';
            $driver_data['ride_id'] = $ride->id;
            Wallet::create($driver_data);

            $user_data['email'] =$user->email;
            $user_data['pp_fee'] =0;
            $user_data['total'] = $total;
            $user_data['cooper_fee'] = 0;
            if ($discount_fee > 0) {
                $user_data['discount_fee'] = $discount_fee;
                $user_data['coupon_id'] = $coupon->id;
            }
            $user_data['purpose'] = 'pay';
            $user_data['payment_method'] = $ride->payment_method;
            $user_data['status'] = 'settled';
            $user_data['currency'] = 'USD';
            $user_data['ride_id'] = $ride->id;
            //print_r($user_data); exit;
            Wallet::create($user_data);
            $user_end_balance = Wallet::getMyBalance($user->email);
            $driver_end_balance = Wallet::getMyBalance($driver->email);
            $balance_data = array();
            $balance_data['user_end_balance'] = $user_end_balance;
            $balance_data['driver_end_balance'] = $driver_end_balance;
            $ride->update($balance_data);
            return Utils::makeResponse([
                'message' =>Constants::$S_PAY_PAY_SUCCESS,
                'balance' =>$user_end_balance
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    public function payoutForDriver(Request $request){
        $rules=[
            'api_token'=>'required|max:255',
            'card_no' =>'max:255',
            'amount' =>'required|max:8'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try {
            // check valid email
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $driver_profile=DriverProfile::where('driver_id', $driver->id)->first();
            if (empty($driver->hyperwallet_user_token)) {
                $hw_user = new User();
                $hw_user
                    ->setProgramToken($this->hyperwallet_program_token)
                    ->setClientUserId('cooper_d_'.$driver->id)
                    ->setProfileType('INDIVIDUAL')
                    ->setFirstName($driver->first_name)
                    ->setLastName($driver->last_name)
                    ->setEmail($driver->email)
                    ->setAddressLine1($driver_profile->address)
                    ->setCity($driver_profile->city)
                    ->setStateProvince($driver_profile->address)
                    ->setCountry($driver_profile->country)
                    ->setPostalCode($driver_profile->postal_code);
                $hw_user = $this->hyperwallet->createUser($hw_user);
                $hw_user_token = $hw_user->getToken();
                $driver->update(['hyperwallet_user_token'=>$hw_user_token]);
            } else {
                $hw_user_token = $driver->hyperwallet_user_token;
                $hw_user = $this->hyperwallet->getUser($hw_user_token);
            }
//            if (!empty($request->card_no && $card = Cards::where('card_no', $request->card_no)->first())) {
//
//                //print_r(DateTime::createFromFormat('Y-m', strtotime($card->expiry_year.'-'.$card->expiry_month))); exit;
//                $bankcard = new BankCard();
//                $bankcard
//                    ->setCardNumber($card->card_no)
//                    //->setDateOfExpiry(DateTime::createFromFormat('Y-m', strtotime($card->expiry_year.'-'.$card->expiry_month)))
//                    ->setTransferMethodCountry($driver_profile->country)
//                    ->setTransferMethodCurrency('USD')
//                    ->setType('BANK_CARD');
//                $bankcard = $this->hyperwallet->createBankCard($hw_user_token, $bankcard);
//                print_r ($bankcard); exit;
//            }
//            $paypalAccount = new PayPalAccount();
//            $paypalAccount
//                ->setTransferMethodCountry('US')
//                ->setTransferMethodCurrency('USD')
//                ->setType("PAYPAL_ACCOUNT")
//                ->setEmail($hw_user->email);
//            $paypalAccount = $this->hyperwallet->createPayPalAccount($hw_user_token, $paypalAccount);

            $payment = new Payment();
            $payment
                ->setDestinationToken($hw_user_token)
                ->setProgramToken($this->hyperwallet_program_token)
                ->setClientPaymentId('psdk-'.time())
                ->setCurrency('USD')
                ->setAmount($request->amount)
                ->setPurpose('PAYROLL');
            $payment = $this->hyperwallet->createPayment($payment);
            $driver_data['email'] =$driver->email;
            $driver_data['pp_fee'] =0;
            $driver_data['total'] = $request->amount;
            $driver_data['amount'] = (-1)* $request->amount;
            $driver_data['cooper_fee'] = 0;
            $driver_data['purpose'] = 'payout';
            $driver_data['status'] = 'settled';
            $driver_data['currency'] = 'USD';
            Wallet::create($driver_data);
            return Utils::makeResponse([
                'message' =>Constants::$S_PAY_OUT_SUCCESS,
                'balance' =>Wallet::getMyBalance($driver->email)
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }
    }

    public function rawpayoutForDriver(Request $request){
        $rules=[
            'api_token'=>'required|max:255',
            'card_no' =>'max:255',
            'paypal_email'=>'required|max:255',
            'amount' =>'required|max:8'
        ];
        $validator=Validator::make($request->all(), $rules);


        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try {
            // check valid email
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            // check wallet
            $balance = Wallet::getMyBalance($driver->email);
            if ($balance < $request->amount) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_PAY_OUT_NOT_ENOUGH_WALLET
                ], 0);
            }
//            $paypal_client_id = env('PAYPAL_CLIENT_ID');
//            $paypal_secret = env('PAYPAL_SECRET');
//            $paypal_mode = env('PAYPAL_MODE');
            $paypal_client_id = Settings::where('key', 'paypal_client_id')->first()->value;
            $paypal_secret = Settings::where('key', 'paypal_secret')->first()->value;
            $paypal_mode = Settings::where('key', 'paypal_mode')->first()->value;


            $base_url = '';
            if ($paypal_mode == "sandbox") {
                $base_url = "https://api.sandbox.paypal.com";
            }
            else if ($paypal_mode == "live") {
                $base_url = "https://api.paypal.com";
            }
            $client = new \GuzzleHttp\Client();

            $response = $client->request('POST', "$base_url/v1/oauth2/token", [
                    'headers' =>
                        [
                            'Accept' => 'application/json',
                            'Accept-Language' => 'en_US',
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ],
                    'body' => 'grant_type=client_credentials',

                    'auth' => [$paypal_client_id, $paypal_secret, 'basic']
                ]
            );

            $data = json_decode($response->getBody(), true);

            $access_token = $data['access_token'];
            $token_type = $data['token_type'];

            //$receiver_email = "sb-ww47gt475108@personal.example.com";
            $receiver_email = $request->paypal_email;
            $value = request('amount');

            $post_data = [
                "sender_batch_header" => [
                    "sender_batch_id" => time(),
                    "email_subject" => "Test",
                    "email_message" => "Heloo world"
                ],
                "items" => [
                    [
                        "recipient_type" => "EMAIL",
                        "receiver"  =>  $receiver_email,
                        "note"  =>  "The payout for CoOper",
                        "sender_item_id"  =>  "19861211",
                        "amount"  => [
                            "currency" => "USD",
                            "value" => $value
                        ]
                    ]
                ]
            ];

            $json_data  = json_encode($post_data);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "$base_url/v1/payments/payouts",
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $json_data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Authorization: $token_type $access_token"
                )
            ));

            curl_exec($curl);

            curl_close($curl);


            $payout_data['email'] =$driver->email;
            $payout_data['pp_fee'] =0;
            $payout_data['paypal_email'] =$request->paypal_email;
            $payout_data['in_out'] ='out';
            $payout_data['total'] = $request->amount;
            $payout_data['amount'] = (-1)* $request->amount;
            $payout_data['cooper_fee'] = 0;
            $payout_data['purpose'] = 'payout';
            $payout_data['status'] = 'settled';
            $payout_data['currency'] = 'USD';
            Wallet::create($payout_data);
            return Utils::makeResponse([
                'message' =>Constants::$S_PAY_OUT_SUCCESS,
                'balance' =>Wallet::getMyBalance($driver->email)
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    // ------------------------- statistic -----------------------------
    public function getUserBalanceHistory(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try {
            // check if user is valid
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $history = Wallet::where('email', $user->email)
                ->orderBy('created_at', 'desc')
                ->get();
            $result = array();
            $sum = Wallet::getMyBalance($user->email);
            foreach ($history as $r) {
                $tmp['total'] = $r->total;
                $tmp['amount'] = $r->amount;
                $tmp['fee'] = $r->pp_fee;
                if ($r->purpose == 'cancel_penalty') {
                    if ($r->amount < 0) {
                        $tmp['type'] = 'user_cancel_penalty';
                    } else {
                        $tmp['type'] = 'driver_cancel_penalty';
                    }

                } else {
                    $tmp['type'] = $r->purpose;
                }
                $tmp['payment_method'] = $r->payment_method;
                $tmp['date'] = Date($r->created_at);
                $tmp['via'] = $r->via;
                $tmp['via_value'] = $r->via_value;
                $tmp['start_balance'] = round($sum - $r->amount, 2);
                $tmp['end_balance'] = round($sum, 2);
                $sum -= round($r->amount, 2);
                $result[] = $tmp;
            }

            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'rides'=>$result,
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
}
