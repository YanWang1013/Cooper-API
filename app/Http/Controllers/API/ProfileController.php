<?php

namespace App\Http\Controllers\API;

use App\Http\Models\DriverCar;
use App\Http\Models\DocType;
use App\Http\Constants;
use App\Http\Models\DriverDocument;
use App\Http\Models\DriverProfile;
use App\Http\Models\Drivers;
use App\Http\Models\Rides;
use App\Http\Models\ServiceTypes;
use App\Http\Models\Users;
use App\Http\Utils;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\Storage;
use Validator;

class ProfileController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getUserProfile(Request $request)
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
            unset($user['password']);
            unset($user['device_id']);
            unset($user['reset_otp']);
            unset($user['sms_otp']);
            unset($user['email_otp']);
            $user['avatar'] = url('storage/user/avatar').'/'.$user['avatar'];
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'profile'=>$user,
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
    public function updateUserProfile(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'first_name'=>'required|max:50',
            'last_name' => 'required|max:50',
            'avatar' => 'mimes:jpg,jpeg,png,pdf',
            'gender'=>'required|integer:1',
            'birthday'=>'required|max:10'
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
            $user_data = array();
            if (!empty($request->avatar)) {
                $filename=time() . '_-' . $request->file('avatar')->getClientOriginalName();
                $request->file('avatar')->storeAs('public/user/avatar', $filename);
                $user_data['avatar'] = $filename;
            }
            $user_data['first_name'] = $request->first_name;
            $user_data['last_name'] = $request->last_name;
            $user_data['gender'] = $request->gender;
            if (!empty($request->birthday)) {
                $user_data['birthday'] = $request->birthday;
            }
            $user->update($user_data);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATEPROFILE
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
    public function getDriverProfile(Request $request)
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
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $profile = $driver->profile;
            unset($driver['password']);
            unset($driver['device_id']);
            unset($driver['reset_otp']);
            unset($driver['sms_otp']);
            unset($driver['email_otp']);
            unset($driver['profile']);
            if (empty($driver['login_by'])) {
                $driver['avatar'] = url('storage/driver/avatar') . '/' . $profile['avatar'];
            } else {
                $driver['avatar'] = $profile['avatar'];
            }
            $driver['language'] = $profile['language'];
            $driver['address'] = $profile['address'];
            $driver['address_2'] = $profile['address_2'];
            $driver['city'] = $profile['city'];
            $driver['country'] = $profile['country'];
            $driver['postal_code'] = $profile['postal_code'];
            $driver['car_number'] = $profile['car_number'];
            $driver['car_model'] = $profile['car_model'];
            $driver['service_type'] = $profile['service_type'];
            $driver_car = DriverCar::where('driver_id', $driver->id)->get();
            $driver['car_image'] = $driver_car;
            if ($profile['service_type_status'] === Constants::$SERVICETYPE_STATUS_INIT) {
                $driver['service_type_status'] = Constants::$S_SERVICETYPE_NOT_APPROVED;
            } elseif ($profile['service_type_status'] == Constants::$SERVICETYPE_STATUS_APPROVE) {

                $rides = Rides::where('status', '<>', Constants::$RIDE_STATUS_CANCELED)
                    ->where('status', '<>', Constants::$RIDE_STATUS_FINISHED)
                    ->where('driver_id', $driver->id)
                    ->where(function ($query) {
                        $query->whereNull('book_at')->orWhere(function($query1) {
                            $query1->whereNotNull('book_at')
                                ->where('status', '>=', Constants::$RIDE_STATUS_ARRIVED);
                        });
                    })
                    ->first();
                if ($rides) {
                    $driver['service_type_status'] = Constants::$S_SERVICETYPE_APPROVED_BAD;
                } else {
                    $driver['service_type_status'] = Constants::$S_SERVICETYPE_APPROVED_OK;
                }
            } else {
                $driver['service_type_status'] = Constants::$S_SERVICETYPE_RELEASED;
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'profile'=>$driver,
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
    public function updateDriverProfile(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'first_name'=>'required|max:50',
            'last_name' => 'required|max:50',
            'avatar' => 'mimes:jpg,jpeg,png,pdf',
            'gender'=>'required|integer:1',
            'birthday'=>'max:10',
            'language' => 'max:50',
            'address' => 'required|max:255',
            'address_2' => 'max:255',
            'city' => 'required|max:50',
            'country' => 'required|max:50',
            'postal_code' => 'required|max:50',
            'car_number' => 'max:255',
            'car_model' => 'max:255',
            'service_type' => 'required|integer:2'
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
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            if (!$profile=DriverProfile::where('driver_id', $driver->id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $profile_data = array();
            $old_service_type = $profile->service_type;
            if ($old_service_type != $request->service_type) {
                $profile_data['new_service_type'] = $request->service_type;
                $profile_data['service_type_status'] = Constants::$SERVICETYPE_STATUS_INIT;
                $profile_data['service_type_new_at'] = date('Y-m-d H:i:s');
            } else {
                $profile_data['service_type'] = $request->service_type;
            }
            if (!empty($request->avatar)) {
                $filename=time() . '_-' . $request->file('avatar')->getClientOriginalName();
                $request->file('avatar')->storeAs('public/driver/avatar', $filename);
                $profile_data['avatar'] = $filename;
            }
            if (!empty($request->language)) {
                $profile_data['language'] = $request->language;
            }
            $profile_data['address'] = $request->address;
            if (!empty($request->address_2)) {
                $profile_data['address_2'] = $request->address_2;
            }
            $profile_data['city'] = $request->city;
            $profile_data['country'] = $request->country;
            $profile_data['postal_code'] = $request->postal_code;
            if (!empty($request->car_number)) {
                $profile_data['car_number'] = $request->car_number;
            }
            if (!empty($request->car_model)) {
                $profile_data['car_model'] = $request->car_model;
            }

            $driver->update([
                'first_name' =>$request->first_name,
                'last_name' =>$request->last_name,
                'gender' => $request->gender,
                'birthday' => $request->birthday
            ]);
            $profile->update($profile_data);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATEPROFILE
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
    public function updateUserQuickblox(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'quickblox_id'=>'required|max:255',
            'quickblox_username' => 'required|max:255',
            'quickblox_password' => 'required|max:255'
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
            $user_data = array();
            $user_data['quickblox_id'] = $request->quickblox_id;
            $user_data['quickblox_username'] = $request->quickblox_username;
            $user_data['quickblox_password'] = $request->quickblox_password;
            $user->update($user_data);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATEPROFILE
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
    public function updateDriverQuickblox(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'quickblox_id'=>'required|max:255',
            'quickblox_username' => 'required|max:255',
            'quickblox_password' => 'required|max:255'
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
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $driver_data = array();
            $driver_data['quickblox_id'] = $request->quickblox_id;
            $driver_data['quickblox_username'] = $request->quickblox_username;
            $driver_data['quickblox_password'] = $request->quickblox_password;
            $driver->update($driver_data);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATEPROFILE
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
    public function addCarImage(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'car_image' => 'mimes:jpg,jpeg,png,pdf',
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
            if (!$driver=Drivers::where('api_token', $request->api_token)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $car = array();
            $car['driver_id'] = $driver->id;
            if (!empty($request->car_image)) {
                $filename=time() . '_-' . $request->file('car_image')->getClientOriginalName();
                $request->file('car_image')->storeAs('public/driver/car', $filename);
                $car['car_image'] = $filename;
            }
            DriverCar::create($car);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_ADDCARIMAGE
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
    public function updateCarImage(Request $request)
    {
        $rules=[
            'car_image_id'=>'required|max:255',
            'car_image' => 'mimes:jpg,jpeg,png,pdf',
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
            if (!$driver_car=DriverCar::where('id', $request->car_image_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $car = array();
            if (!empty($request->car_image)) {
                $filename=time() . '_-' . $request->file('car_image')->getClientOriginalName();
                $request->file('car_image')->storeAs('public/driver/car', $filename);
                $car['car_image'] = $filename;
            }
            $driver_car->update($car);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_UPDATECARIMAGE
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
    public function deleteCarImage(Request $request)
    {
        $rules=[
            'car_image_id'=>'required|max:255',
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
            if (!$driver_car=DriverCar::where('id', $request->car_image_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_APITOKEN
                ], 0);
            }
            $driver_car->delete();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_DELETECARIMAGE
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }
}
