<?php

namespace App\Http\Controllers\API;

use App\Http\Constants;
use App\Http\Models\DriverProfile;
use App\Http\Models\Drivers;
use App\Http\Models\Rides;
use App\Http\Models\ServiceTypes;
use App\Http\Quickblox;
use App\Http\Utils;
use Illuminate\Http\Request;
use App\Http\Models\Users;
use App\Http\Models\Settings;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Validator;

class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function signupForUser(Request $request)
    {
        // if unverified email exists, send verify code again
        if ($user = Users::where(array('email'=>$request->email))->first()) {
            if ($user->status >= Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        if ($driver = Drivers::where(array('email'=>$request->email))->first()) {
            if ($driver->status >= Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }

        $rules=[
            'device_type'=>'required|in:android,ios',
            'device_token'=>'required',
            'fcm_token' =>'required',
            'first_name'=>'required|max:255',
            'last_name'=>'required|max:255',
            'email'=>'required|email|max:255',
            'phone_number'=>'required',
            'gender'=>'max:1',
            'birthday'=>'max:10',
            'password'=>'required|min:6',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            $account=$request->all();
            $account['password']=bcrypt($request->password);
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            do {
                $api_token=Str::random(20);
            } while (Users::where('api_token', $api_token)->first());

            $account['sms_otp']=$sms_verify_code;
            $account['email_otp']=$email_verify_code;
            $account['api_token']=$api_token;
            if (!Utils::sendSmsForVerify($request->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($request->email, $email_verify_code);
//            $qb = new Quickblox();
//            $session = $qb->createSession();
//            $token = $session->token;
//            $qb_username = 'cooper_user_qb_' . $request->email;
//            $qb_password = 'cooper_qb_' . time();
//            $qb_user = $qb->createUser($token, $qb_username, $qb_password, $request->email);
//            if (isset($qb_user->user)) {
//                $qb_user = $qb_user->user;
//                $account['quickblox_id'] = $qb_user->id;
//                $account['quickblox_username'] = $qb_username;
//                $account['quickblox_password'] = $qb_password;
//            }
            if ($user && $user->status == Constants::$USER_STATUS_INIT) {
                $user->update($account);
            } else {
                Users::create($account);
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SIGNUP,
                'api_token'=>$api_token,
//                'qb_id'=>$qb_user->id,
//                'qb_password'=>$qb_password
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    public function signupForDriver(Request $request)
    {
        // if unverified email exists, send verify code again
        if ($driver = Drivers::where(array('email'=>$request->email))->first()) {
            if ($driver->status >= Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        if ($user = Users::where(array('email'=>$request->email))->first()) {
            if ($user->status >= Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }

        $rules=[
            'device_type'=>'required|in:android,ios',
            'device_token'=>'required',
            'fcm_token' =>'required',
            'first_name'=>'required|max:255',
            'last_name'=>'required|max:255',
            'email'=>'required|email|max:255',
            'phone_number'=>'required',
            'gender'=>'max:1',
            'birthday'=>'max:10',
            'password'=>'required|min:6',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            $account=$request->all();

            $account['password']=bcrypt($request->password);
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            do {
                $api_token=Str::random(20);
            } while (Drivers::where('api_token', $api_token)->first());

            $account['sms_otp']=$sms_verify_code;
            $account['email_otp']=$email_verify_code;
            $account['api_token']=$api_token;
            //print_r($user); exit;
            if (!Utils::sendSmsForVerify($request->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($request->email, $email_verify_code);
//            $qb = new Quickblox();
//            $session = $qb->createSession();
//            $token = $session->token;
//            $qb_username = 'cooper_driver_qb_' . $request->email;
//            $qb_password = 'cooper_qb_' . time();
//            $qb_user = $qb->createUser($token, $qb_username, $qb_password, $request->email);
//            if (isset($qb_user->user)) {
//                $qb_user = $qb_user->user;
//                $account['quickblox_id'] = $qb_user->id;
//                $account['quickblox_username'] = $qb_username;
//                $account['quickblox_password'] = $qb_password;
//            }
            if ($driver && $driver->status == Constants::$DRIVER_STATUS_INIT) {
                $driver->update($account);
            } else {
                Drivers::create($account);
            }
            $driver_id = Drivers::where('api_token', $api_token)->first()->id;
            $profile = array();
            $profile['driver_id'] = $driver_id;
            $profile['service_type'] = 1;
            $driver_profile = DriverProfile::where('driver_id', $driver_id)->first();
            if ($driver_profile) {
                $driver_profile->update($profile);
            } else {
                DriverProfile::create($profile);
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SIGNUP,
                'api_token'=>$api_token,
//                'qb_id'=>$qb_user->id,
//                'qb_password'=>$qb_password
            ]);
        } catch (Exception $e) {
            return $e->getMessage();

//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }

    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function resignupForUser(Request $request)
    {

        $rules=[
            'email'=>'required|email|max:255',
            'api_token'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and api token
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            if (!Utils::sendSmsForVerify($user->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($user->email, $email_verify_code);

            $user->update(['sms_otp'=>$sms_verify_code, 'email_otp'=>$email_verify_code]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_RESIGNUP
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function resignupForDriver(Request $request)
    {

        $rules=[
            'email'=>'required|email|max:255',
            'api_token'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and api token
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            if (!Utils::sendSmsForVerify($driver->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($driver->email, $email_verify_code);
            $driver->update(['sms_otp'=>$sms_verify_code, 'email_otp'=>$email_verify_code]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_RESIGNUP
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function verifyForUser(Request $request)
    {

        $rules=[
            'api_token'=>'required|max:255',
            'email'=>'required|max:255',
            'sms_verify_code'=>'max:255',
            'email_verify_code'=>'max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and api token
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            // check valid timeout
            $timeout=Settings::where('key', 'verify_timeout')->first()->value;
            if (time() - strtotime($user->created_at) > $timeout * 60) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_VERIFY_TIMEOUT
                ], 0);
            }
            // verify sms and email
            $msg=array(); $b_verify = false;
            if ($request->sms_verify_code != $user->sms_otp) {
                $msg['sms']=Constants::$E_INVALID_SMS_VERIFYCODE;
            } else {
                $b_verify = true;
                $user->update(['sms_verify'=>1]);
            }
            //echo $request->email_verify_code.'   '.$user->email_otp; exit;
            if ($request->email_verify_code != $user->email_otp) {
                $msg['email']=Constants::$E_INVALID_EMAIL_VERIFYCODE;
            } else {
                $b_verify = true;
                $user->update(['email_verify'=>1]);
            }
            if ($b_verify) {
                $user->update(['status'=>Constants::$DRIVER_STATUS_VERIFIED]);
                return Utils::makeResponse([
                    'message'=>Constants::$S_SUCCESS_VERIFY
                ]);
            } else {
                return Utils::makeResponse([
                    'message'=>Constants::$E_VERIFICATION_ERROR,
                    'verify_error'=>$msg
                ], 0);
            }
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function verifyForDriver(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'email'=>'required|max:255',
            'sms_verify_code'=>'max:255',
            'email_verify_code'=>'max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and api token
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            // check valid timeout
            $timeout=Settings::where('key', 'verify_timeout')->first()->value;
            if (time() - strtotime($driver->created_at) > $timeout * 60) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_VERIFY_TIMEOUT
                ], 0);
            }
            // verify sms and email
            $msg=array(); $b_verify = false;
            if ($request->sms_verify_code != $driver->sms_otp) {
                $msg['sms']=Constants::$E_INVALID_SMS_VERIFYCODE;
            } else {
                $b_verify = true;
                $driver->update(['sms_verify'=>1]);
            }
            //echo $request->email_verify_code.'   '.$user->email_otp; exit;
            if ($request->email_verify_code != $driver->email_otp) {
                $msg['email']=Constants::$E_INVALID_EMAIL_VERIFYCODE;
            } else {
                $b_verify = true;
                $driver->update(['email_verify'=>1]);
            }
            if ($b_verify) {
                $driver->update(['status'=>Constants::$DRIVER_STATUS_VERIFIED]);
                return Utils::makeResponse([
                    'message'=>Constants::$S_SUCCESS_VERIFY
                ]);
            } else {
                return Utils::makeResponse([
                    'message'=>Constants::$E_VERIFICATION_ERROR,
                    'verify_error'=>$msg
                ], 0);
            }
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function signinForUser(Request $request)
    {

        $rules=[
            'device_token'=>'required|max:255',
            'email'=>'required|max:255',
            'password'=>'required|max:255',
            'fcm_token'=>'max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Users::where('device_token', $request->device_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_DEVICETOKEN
                ], 0);
            }
            // check verify
            if ($user->status < Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // send sms
            if (!Hash::check($request->password, $user->password)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_WRONG_PASSWORD
                ], 0);
            }
//            if ($user->status == Constants::$USER_STATUS_VERIFIED)
//                $user->update(['status'=>Constants::$USER_STATUS_ACTIVED]);

            if ($request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }
            // with service type
            $service_types = ServiceTypes::where(array('status'=>1))->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_LOGIN,
                'api_token'=>$user->api_token,
                'service_type'=>$service_types,
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function signinForDriver(Request $request)
    {

        $rules=[
            'device_token'=>'required|max:255',
            'email'=>'required|max:255',
            'password'=>'required|max:255',
            'fcm_token'=>'max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            if (!Drivers::where('device_token', $request->device_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_DEVICETOKEN
                ], 0);
            }
            // check verify
            if ($driver->status < Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // send sms
            if (!Hash::check($request->password, $driver->password)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_WRONG_PASSWORD
                ], 0);
            }
//            if ($driver->status == Constants::$DRIVER_STATUS_VERIFIED) {
//                $driver->update(['status' => Constants::$DRIVER_STATUS_ACTIVED]);
//            }
            if ($request->fcm_token) {
                $driver->update(['fcm_token' => $request->fcm_token]);
            }

            $message = Constants::$S_SUCCESS_LOGIN;
            // current ride
//            $new_service_type = DriverProfile::where(array('driver_id'=>$driver->id, 'service_type_status'=>Constants::$SERVICETYPE_STATUS_APPROVE))->first();
//            if ($new_service_type) {
//                $rides = Rides::where('status', '<>', Constants::$RIDE_STATUS_CANCELED)
//                    ->where('status', '<>', Constants::$RIDE_STATUS_FINISHED)
//                    ->where('driver_id', $driver->id)
//                    ->where(function ($query) {
//                        $query->whereNull('book_at')->orWhere(function($query1) {
//                            $query1->whereNotNull('book_at')
//                                ->where('status', '>=', Constants::$RIDE_STATUS_ARRIVED);
//                        });
//                    })
//                    ->first();
//                if ($rides) {
//                    $message = Constants::$S_SUCCESS_LOGIN_WITHOUT_UPDATING_SERVICETYPE;
//                } else {
//                    $data = array();
//                    $data['service_type'] = $new_service_type->new_service_type;
//                    $data['new_service_type'] = null;
//                    $data['service_type_release_at'] = date('Y-m-d H:i:s');
//                    $data['service_type_status'] = Constants::$SERVICETYPE_STATUS_RELEASE;
//                    $new_service_type->update($data);
//                    $message = Constants::$S_SUCCESS_LOGIN_WITH_UPDATING_SERVICETYPE;
//                }
//            }
            return Utils::makeResponse([
                'message'=>$message,
                'api_token'=>$driver->api_token
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function forgotpasswordForUser(Request $request)
    {

        $rules=[
            'email'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            // check verify
            if ($user->status < Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // check match password
            $reset_verify_code=Str::random(7);
            $user->update(['reset_otp'=>$reset_verify_code]);
            if (!Utils::sendSmsForVerify($user->phone_number, $reset_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }

            return Utils::makeResponse([
                'message'=>Constants::$S_RESET_PASSWORD
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function forgotpasswordForDriver(Request $request)
    {

        $rules=[
            'email'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            // check verify
            if ($driver->status < Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // check match password
            $reset_verify_code=Str::random(7);
            $driver->update(['reset_otp'=>$reset_verify_code]);
            if (!Utils::sendSmsForVerify($driver->phone_number, $reset_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }

            return Utils::makeResponse([
                'message'=>Constants::$S_RESET_PASSWORD
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function resetpasswordForUser(Request $request)
    {

        $rules=[
            'email'=>'required|max:255',
            'password'=>'required|max:255',
            'reset_code'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            // check verify
            if ($user->status < Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // check reset code
            if ($request->reset_code != $user->reset_otp) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RESETCODE
                ], 0);
            }
            $user->update(['reset_otp'=>'']);
            $user->update(['password'=>bcrypt($request->password)]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_RESETPASSWORD
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function resetpasswordForDriver(Request $request)
    {

        $rules=[
            'email'=>'required|max:255',
            'password'=>'required|max:255',
            'reset_code'=>'required|max:255',
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            // check verify
            if ($driver->status < Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            // check reset code
            if ($request->reset_code != $driver->reset_otp) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RESETCODE
                ], 0);
            }
            $driver->update(['reset_otp'=>'']);
            $driver->update(['password'=>bcrypt($request->password)]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_RESETPASSWORD
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function changepasswordForUser(Request $request)
    {

        $rules=[
            'api_token'=>'required|max:255',
            'password'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            // check verify
            if ($user->status < Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            $user->update(['password'=>bcrypt($request->password)]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_CHANGEPASSWORD
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function changepasswordForDriver(Request $request)
    {

        $rules=[
            'api_token'=>'required|max:255',
            'password'=>'required|max:255'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            // check verify
            if ($driver->status < Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_NOT_VERIFY
                ], 0);
            }
            $driver->update(['password'=>bcrypt($request->password)]);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_CHANGEPASSWORD
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function socialSigninForUser(Request $request)
    {
        // if unverified email exists, send verify code again
        if ($user = Users::where(array('email'=>$request->email))->first()) {
            if ($user->status >= Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        if ($driver = Drivers::where(array('email'=>$request->email))->first()) {
            if ($driver->status >= Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        $rules=[
            'device_type'=>'in:android,ios',
            'device_token'=>'max:255',
            //'fcm_token' =>'required',
            'phone_number'=>'required|max:255',
            'email'=>'required|email|max:255',
            'password'=>'required|min:6',
            'accessToken'=> 'required',
            'login_by' => 'required|in:facebook,google',
            'birthday'=>'max:10'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try{
            if ($request->login_by == "facebook") {
                $social=Socialite::driver('facebook')->stateless();
                $socialDrive=$social->userFromToken($request->accessToken);
            } else if ($request->login_by == "google") {
                $social = Socialite::driver('google')->stateless();
                $socialDrive = $social->userFromToken( $request->accessToken);
            }
            $account=$request->all();
            $name = explode(' ', $socialDrive->name, 2);
            $account['first_name'] = $name[0];
            $account['last_name'] = isset($name[1]) ? $name[1] : '';
            if ($request->login_by == "facebook") {
                $gender = $socialDrive->user['gender'];
                $account['gender'] = ($gender == 'male') ? 1 : 2;
            }
            $account['social_unique_id'] = $socialDrive->id;
            $account['login_by'] = $request->login_by;
            $account['password'] = bcrypt($request->password);
            $account['avatar'] = $socialDrive->avatar;
            do {
                $api_token=Str::random(20);
            } while (Users::where('api_token', $api_token)->first());
            $account['api_token'] = $api_token;
            $account['password']=bcrypt($request->password);
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            $account['sms_otp']=$sms_verify_code;
            $account['email_otp']=$email_verify_code;
            if (!Utils::sendSmsForVerify($request->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($request->email, $email_verify_code);
//                $qb = new Quickblox();
//                $session = $qb->createSession();
//                $token = $session->token;
//                $qb_username = 'cooper_user_qb_' . $request->email;
//                $qb_password = 'cooper_qb_' . time();
//                $qb_user = $qb->createUser($token, $qb_username, $qb_password, $request->email);
//                if (isset($qb_user->user)) {
//                    $qb_user = $qb_user->user;
//                    $user['quickblox_id'] = $qb_user->id;
//                    $user['quickblox_username'] = $qb_username;
//                    $user['quickblox_password'] = $qb_password;
//                }
            if ($user && $user->status == Constants::$USER_STATUS_INIT) {
                $user->update($account);
            } else {
                Users::create($account);
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SIGNUP,
                'api_token'=>$api_token,
//                'qb_id'=>$qb_user->id,
//                'qb_password'=>$qb_password
            ]);

        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function socialSigninForDriver(Request $request)
    {
        // if unverified email exists, send verify code again
        if ($driver = Drivers::where(array('email'=>$request->email))->first()) {
            if ($driver->status >= Constants::$DRIVER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        if ($user = Users::where(array('email'=>$request->email))->first()) {
            if ($user->status >= Constants::$USER_STATUS_VERIFIED) {
                return Utils::makeResponse([
                    'message' => Constants::$E_DUPLICATED_EMAIL
                ], 0);
            }
        }
        $rules=[
            'device_type'=>'in:android,ios',
            'device_token'=>'max:255',
            //'fcm_token' =>'required',
            'email'=>'required|email|max:255',
            'password'=>'required|min:6',
            'phone_number'=>'required|max:255',
            'accessToken'=> 'required',
            'login_by' => 'required|in:facebook,google',
            'birthday'=>'max:10'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }
        try{
            if ($request->login_by == "facebook") {
                $social=Socialite::driver('facebook')->stateless();
                $socialDrive=$social->userFromToken($request->accessToken);
            } else if ($request->login_by == "google") {
                $social = Socialite::driver('google')->stateless();
                $socialDrive = $social->userFromToken( $request->accessToken);
            }
            // check valid email and device token

            $account=$request->all();
            $name = explode(' ', $socialDrive->name, 2);
            $account['first_name'] = $name[0];
            $account['last_name'] = isset($name[1]) ? $name[1] : '';
            if ($request->login_by == "facebook") {
                if (isset($socialDrive->user['gender'])) {
                    $gender = $socialDrive->user['gender'];
                    $account['gender'] = ($gender=='male')?1:2;
                }
            }
            $account['social_unique_id'] = $socialDrive->id;
            $account['login_by'] = $request->login_by;
            $account['password'] = bcrypt($request->password);
            do {
                $api_token=Str::random(20);
            } while (Users::where('api_token', $api_token)->first());
            $account['api_token'] = $api_token;
            $sms_verify_code=Str::random(7);
            $email_verify_code=Str::random(7);
            $account['sms_otp']=$sms_verify_code;
            $account['email_otp']=$email_verify_code;
            if (!Utils::sendSmsForVerify($request->phone_number, $sms_verify_code)) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_TWILIO_NUMBER_VERIFY_ERROR
                ], 0);
            }
            Utils::sendEmailForVerify($request->email, $email_verify_code);
//                $qb = new Quickblox();
//                $session = $qb->createSession();
//                $token = $session->token;
//                $qb_username = 'cooper_user_qb_' . $request->email;
//                $qb_password = 'cooper_qb_' . time();
//                $qb_user = $qb->createUser($token, $qb_username, $qb_password, $request->email);
//                if (isset($qb_user->user)) {
//                    $qb_user = $qb_user->user;
//                    $user['quickblox_id'] = $qb_user->id;
//                    $user['quickblox_username'] = $qb_username;
//                    $user['quickblox_password'] = $qb_password;
//                }
            if ($driver && $driver->status == Constants::$DRIVER_STATUS_INIT) {
                $driver->update($account);
            } else {
                Drivers::create($account);
            }
            $driver_id = Drivers::where('api_token', $api_token)->first()->id;
            $profile = array();
            $profile['driver_id'] = $driver_id;
            $profile['service_type'] = 1;
//            if ($request->login_by == "facebook") {
//                print_r($socialDrive); exit;
//                $response = $socialDrive->get('/me?fields=picture');
//                $fb_user = $response->getGraphUser();
//                $avatar = $fb_user->getPicture();
//                $avatar = $avatar['url'];
//            } else if ($request->login_by == "google") {
//                $avatar = $socialDrive->avatar;
//            }
            $avatar = $socialDrive->avatar;
            $profile['avatar'] = $avatar;
            $driver_profile = DriverProfile::where('driver_id', $driver_id)->first();
            if ($driver_profile) {
                $driver_profile->update($profile);
            } else {
                DriverProfile::create($profile);
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SIGNUP,
                'api_token'=>$api_token,
//                'qb_id'=>$qb_user->id,
//                'qb_password'=>$qb_password
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
    public function sendSOSEmailForUser(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'msg_text' =>'required'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$user=Users::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            Utils::sendEmailForSOS($user->email, $request->msg_text);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SEND_EMAIL
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }


    public function sendSOSEmailForDriver(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'msg_text' =>'required'
        ];
        $validator=Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Utils::makeResponse([
                'message'=>Constants::$E_VALIDATION_ERROR,
                'validation_error'=>$validator->errors()
            ], 0);
        }

        try {
            // check valid email and device token
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            Utils::sendEmailForSOS($driver->email, $request->msg_text);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_SEND_EMAIL
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

}
