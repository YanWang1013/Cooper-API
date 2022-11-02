<?php

namespace App\Http\Controllers\API;

use App\Http\Constants;
use App\Http\Models\DriverProfile;
use App\Http\Models\Drivers;
use App\Http\Models\Rides;
use App\Http\Models\ServiceTypes;
use App\Http\Models\Settings;
use App\Http\Models\Users;
use App\Http\Models\Wallet;
use App\Http\Utils;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Validator;

class RideController extends Controller
{

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function __construct(Request $request){
        // Auto cancel ride pasted book_at time
        $get_sql =      "SELECT * ".
                        "FROM t_rides ".
                        "WHERE book_at IS NOT NULL AND book_at < NOW() AND status <> ".Constants::$RIDE_STATUS_CANCELED." AND status <> ".Constants::$RIDE_STATUS_FINISHED;
        $rides = DB::select($get_sql);
        $penalty_fee = Settings::where('key', 'auto_penalty_fee')->first()->value;
        foreach ($rides as $r) {
            $data = array();
            $data['cancel_reason'] = 'Automatically canceled for past booking time.';
            $data['canceled_at'] = date('Y-m-d H:i:s');
            $data['status'] = Constants::$RIDE_STATUS_CANCELED;
            if ($r->driver_id && $r->status >= Constants::$RIDE_STATUS_ACCEPTED) {
                $data['cancel_by'] = 'auto_driver';
                $user = Users::where('id', $r->user_id)->first();
                $driver = Drivers::where('id', $r->driver_id)->first();
                $commission_percentage = Settings::where('key', 'commission_percentage')->first()->value;
                $cooper_fee = round($penalty_fee * $commission_percentage/100, 2);
                $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
                $cooper_tax_fee = round(( $tax_percentage/100 ) * $cooper_fee, 2);
                $data['pay_amount'] = $penalty_fee;
                $data['cooper_fee'] = $cooper_fee;
                $data['cooper_tax_fee'] = $cooper_tax_fee;
                $data['user_start_balance'] = Wallet::getMyBalance($user->email);
                $data['driver_start_balance'] = Wallet::getMyBalance($driver->email);

                $driver_data = array();
                $driver_data['email'] =$driver->email;
                $driver_data['pp_fee'] =0;
                $driver_data['total'] = $penalty_fee;
                $driver_data['amount'] = (-1) * ($penalty_fee);
                $driver_data['cooper_fee'] = 0;
                $driver_data['payment_method'] = 'wallet';
                $driver_data['purpose'] = 'cancel_penalty';
                $driver_data['status'] = 'settled';
                $driver_data['currency'] = 'USD';
                $driver_data['ride_id'] = $r->id;
                Wallet::create($driver_data);

                $user = Users::where('id', $r->user_id)->first();
                $user_data = array();
                $user_data['email'] =$user->email;
                $user_data['pp_fee'] =0;
                $user_data['total'] = $penalty_fee;
                $user_data['amount'] = $penalty_fee - $cooper_fee - $cooper_tax_fee;
                $user_data['cooper_fee'] = $cooper_fee;
                $user_data['payment_method'] = 'wallet';
                $user_data['purpose'] = 'cancel_penalty';
                $user_data['status'] = 'settled';
                $user_data['currency'] = 'USD';
                $user_data['ride_id'] = $r->id;
                Wallet::create($user_data);

                $data['user_end_balance'] = Wallet::getMyBalance($user->email);
                $data['driver_end_balance'] = Wallet::getMyBalance($driver->email);
            } else {
                $data['cancel_by'] = 'auto_user';
            }
            Rides::where('id', $r->id)->update($data);
        }
    }
    public function getAllRides(Request $request)
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
            $rides = Rides::where(array('driver_id'=>$driver->id))->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'rides'=>$rides,
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
    public function getDriverFinishedRides(Request $request)
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

            $sql =      "SELECT b.fixed, c.email, c.gender, c.first_name, c.last_name, c.phone_number, c.login_by, c.avatar, d.user_avg_rating, a.*, ".
                        "   IF( finish_at IS NOT NULL, finish_at, canceled_at) AS fc_at ".
                        "FROM t_rides AS a ".
                        "INNER JOIN t_service_type AS b ON a.service_type = b.id ".
                        "LEFT JOIN t_users AS c ON a.user_id = c.id ".
                        "LEFT JOIN ".
                        "   (SELECT user_id, AVG(user_rating) as user_avg_rating ".
                        "   FROM t_rides ".
                        "   WHERE status = ".Constants::$RIDE_STATUS_FINISHED." AND user_id IS NOT NULL ".
                        "   GROUP BY user_id) AS d ".
                        "   ON a.user_id = d.user_id ".
                        "WHERE a.driver_id = ".$driver->id." ".
                        "   AND ( a.status = ".Constants::$RIDE_STATUS_FINISHED." OR a.status = ".Constants::$RIDE_STATUS_CANCELED.") ".
                        "   AND ( a.accept_at IS NOT NULL OR a.book_accept_at IS NOT NULL) ".
                        "ORDER BY fc_at DESC, id DESC";

            $rides = DB::select($sql);
            $result = array();
            foreach ($rides as $r) {
                $tmp = (array) $r;
                $tmp['rating'] = ($r->user_rating == null)?'':$r->user_rating;
                $tmp['comment'] = ($r->user_rated == null)?'':$r->user_rated;
                if ($r->user_id) {
                    $tmp['avatar'] = empty($r->login_by) ? url('storage/user/avatar') . '/' . $r->avatar : $r->avatar;
                }
                if ($r->cancel_by == 'driver' || $r->cancel_by == 'auto_driver') {
                    $tmp['earning'] = (-1) * $r->pay_amount;
                } else {
                    $tmp['earning'] = $r->pay_amount - $r->cooper_fee - $r->cooper_tax_fee;
                }
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getDriverFinishedRides2(Request $request)
    {
        $rules=[
            'email'=>'required|max:255' // driver email
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
            if (!$driver=Drivers::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            $rides = Rides::where(array('driver_id'=>$driver->id, 'status'=>Constants::$RIDE_STATUS_FINISHED))
                ->orderBy('finish_at', 'desc')
                ->get();
            $result = array();
            foreach ($rides as $r) {
                $user = Users::where('id', $r->user_id)->first();
                $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                    ->where('user_id', $r->user_id)
                    ->avg('user_rating');
                $tmp = $r;
                $service_type = ServiceTypes::findOrFail($r->service_type);
                $tmp['base_fee'] = $service_type->fixed;
                $tmp['email'] = $user->email;
                $tmp['gender'] = $user->gender;
                $tmp['username'] = $user->first_name.' '.$user->last_name;
                $tmp['phone_number'] = $user->phone_number;
                $tmp['avatar'] = empty($user->login_by) ? url('storage/user/avatar') . '/' . $user->avatar : $user->avatar;
                $tmp['rating'] = ($r->user_rating == null)?'':$r->user_rating;
                $tmp['comment'] = ($r->user_rated == null)?'':$r->user_rated;
                $tmp['user_avg_rating'] = $avg;
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getUserFinishedRides(Request $request)
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
            $sql =      "SELECT b.fixed, c.email, c.gender, c.first_name, c.last_name, c.phone_number, c.login_by, c.avatar, d.driver_avg_rating, a.*, ".
                        "   IF( finish_at IS NOT NULL, finish_at, canceled_at) AS fc_at ".
                        "FROM t_rides AS a ".
                        "INNER JOIN t_service_type AS b ON a.service_type = b.id ".
                        "LEFT JOIN ".
                        "   (SELECT a.*, b.avatar FROM t_drivers AS a, t_driver_profile AS b WHERE a.id = b.driver_id) AS c ".
                        "    ON a.driver_id = c.id ".
                        "LEFT JOIN ".
                        "   (SELECT driver_id, AVG(driver_rating) as driver_avg_rating ".
                        "   FROM t_rides ".
                        "   WHERE status = ".Constants::$RIDE_STATUS_FINISHED." AND driver_id IS NOT NULL ".
                        "   GROUP BY driver_id) AS d ".
                        "   ON a.driver_id = d.driver_id ".
                        "WHERE a.user_id = ".$user->id." ".
                        "   AND ( a.status = ".Constants::$RIDE_STATUS_FINISHED." OR a.status = ".Constants::$RIDE_STATUS_CANCELED.") ".
                        "   AND ( a.accept_at IS NOT NULL OR a.book_accept_at IS NOT NULL) ".
                        "ORDER BY fc_at DESC, id DESC";

            $rides = DB::select($sql);
            $result = array();
            foreach ($rides as $r) {
                //print_r($r); exit;
                $tmp = (array) $r;
                $tmp['driver_rating'] = ($r->driver_rating == null) ? '' : $r->driver_rating;
                $tmp['driver_rated'] = ($r->driver_rated == null) ? '' : $r->driver_rated;
                if ($r->driver_id) {
                    $tmp['avatar'] = empty($r->login_by) ? url('storage/driver/avatar') . '/' . $r->avatar : $r->avatar;
                }
                if ($r->cancel_by == 'driver' || $r->cancel_by == 'auto_driver') {
                    $tmp['earning'] = $r->pay_amount - $r->cooper_fee - $r->cooper_tax_fee;
                } else {
                    $tmp['earning'] = (-1) * $r->pay_amount;
                }
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getUserFinishedRides2(Request $request)
    {
        $rules=[
            'email'=>'required|max:255' // user email
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
            if (!$user=Users::where('email', $request->email)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_EMAIL
                ], 0);
            }
            $sql =      "SELECT b.fixed, c.email, c.gender, c.first_name, c.last_name, c.phone_number, c.login_by, c.avatar, d.driver_avg_rating, a.*, ".
                        "   IF( finish_at IS NOT NULL, finish_at, canceled_at) AS fc_at ".
                        "FROM t_rides AS a ".
                        "INNER JOIN t_service_type AS b ON a.service_type = b.id ".
                        "LEFT JOIN ".
                        "   (SELECT a.*, b.avatar FROM t_drivers AS a, t_driver_profile AS b WHERE a.id = b.driver_id) AS c ".
                        "    ON a.driver_id = c.id ".
                        "LEFT JOIN ".
                        "   (SELECT driver_id, AVG(driver_rating) as driver_avg_rating ".
                        "   FROM t_rides ".
                        "   WHERE status = ".Constants::$RIDE_STATUS_FINISHED." AND driver_id IS NOT NULL ".
                        "   GROUP BY driver_id) AS d ".
                        "   ON a.driver_id = d.driver_id ".
                        "WHERE a.user_id = ".$user->id." ".
                        "   AND ( a.status = ".Constants::$RIDE_STATUS_FINISHED." OR a.status = ".Constants::$RIDE_STATUS_CANCELED.") ".
                        "   AND ( a.accept_at IS NOT NULL OR a.book_accept_at IS NOT NULL) ".
                        "ORDER BY fc_at DESC, id DESC";

            $rides = DB::select($sql);
            $result = array();
            foreach ($rides as $r) {
                //print_r($r); exit;
                $tmp = (array) $r;
                $tmp['driver_rating'] = ($r->driver_rating == null) ? '' : $r->driver_rating;
                $tmp['driver_rated'] = ($r->driver_rated == null) ? '' : $r->driver_rated;
                if ($r->driver_id) {
                    $tmp['avatar'] = empty($r->login_by) ? url('storage/driver/avatar') . '/' . $r->avatar : $r->avatar;
                }
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
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getDriverBookingRides(Request $request)
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
            $rides = Rides::where(array('status'=>Constants::$RIDE_STATUS_REQUESTED))
                    ->whereNotNull('book_accept_at')
                    ->where('book_accept_at', '<>', '0')
                    ->orderBy('finish_at', 'desc')
                    ->get();
            $result = array();
            foreach ($rides as $r) {
                $user= Users::where('id', $r->user_id)->first();
                $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                    ->where('user_id', $r->user_id)
                    ->avg('user_rating');
                $tmp = $r;
                $service_type = ServiceTypes::findOrFail($r->service_type);
                $tmp['base_fee'] = $service_type->fixed;
                $tmp['email'] = $user->email;
                $tmp['gender'] = $user->gender;
                $tmp['firstname'] = $user->first_name;
                $tmp['lastname'] = $user->last_name;
                $tmp['phone_number'] = $user->phone_number;
                $tmp['avatar'] = empty($user->login_by) ? url('storage/user/avatar') . '/' . $user->avatar : $user->avatar;
                $tmp['user_rating'] = ($r->user_rating == null)?'':$r->user_rating;
                $tmp['user_rated'] = ($r->user_rated == null)?'':$r->user_rated;
                $tmp['user_rating'] = $avg;
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
    public function getUserBookingRides(Request $request)
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
            $rides = Rides::where(array('status'=>Constants::$RIDE_STATUS_REQUESTED, 'user_id'=>$user->id))
                ->whereNotNull('book_at')
                ->where('book_at', '<>', '0')
                ->orderBy('finish_at', 'desc')
                ->get();
            $result = array();
            foreach ($rides as $r) {
                $driver = Drivers::where('id', $r->driver_id)->first();
                $tmp = $r;
                $service_type = ServiceTypes::findOrFail($r->service_type);
                $tmp['base_fee'] = $service_type->fixed;
                if ($driver) {
                    $d_profile = DriverProfile::where('driver_id', $driver->id)->first();
                    $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                        ->where('driver_id', $r->driver_id)
                        ->avg('driver_rating');

                    $tmp['email'] = $driver->email;
                    $tmp['gender'] = $driver->gender;
                    $tmp['firstname'] = $driver->first_name;
                    $tmp['lastname'] = $driver->last_name;
                    $tmp['phone_number'] = $driver->phone_number;
                    $tmp['avatar'] = empty($driver->login_by) ? url('storage/driver/avatar') . '/' . $d_profile->avatar : $d_profile->avatar;
                    $tmp['driver_rating'] = ($r->driver_rating == null)?'':$r->driver_rating;
                    $tmp['driver_rated'] = ($r->driver_rated == null)?'':$r->driver_rated;
                    $tmp['driver_rating'] = $avg;
                }
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * This function has used for only login or auto login
     */
    public function getDriverCurrentRide(Request $request)
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
            $rides = Rides::where('status', '<>', Constants::$RIDE_STATUS_CANCELED)
                ->where('status', '<>', Constants::$RIDE_STATUS_FINISHED)
                ->where('driver_id', $driver->id)
                ->where(function ($query) {
                    $query->whereNull('book_at')->orWhere(function($query1) {
                        $query1->whereNotNull('book_at')
                            ->where('status', '>=', Constants::$RIDE_STATUS_ACCEPTED);
                    });
                })
                ->first();
            $result = array();
            // current ride
            $profile = DriverProfile::where('driver_id', $driver->id)->first();
            if ($rides) {
                $user = Users::where('id', $rides->user_id)->first();
                $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                    ->where('user_id', $user->id)
                    ->avg('user_rating');

                $result['ride_id'] = $rides->id;
                $result['user_email'] = $user->email;
                $result['user_gender'] = $user->gender;
                $result['user_firstname'] = $user->first_name;
                $result['user_lastname'] = $user->last_name;
                $result['user_phonenumber'] = $user->phone_number;
                $result['quickblox_id'] = $user->quickblox_id;
                $result['quickblox_username'] = $user->quickblox_username;
                $result['quickblox_password'] = $user->quickblox_password;
                $result['ride_caption'] = '';
                $result['user_avatar'] = empty($user->login_by)?url('storage/user/avatar') . '/' . $user->avatar:$user->avatar;
                $result['user_averagerate'] = $avg;
                $result['time_finished'] = $rides->driver_end_at;
                $result['time_requested'] = $rides->request_at;
                $result['comment_touser'] = $rides->driver_lated;
                $result['comment_todriver'] = $rides->user_lated;
                $result['rate_fordriver'] = $rides->user_rating;
                $result['rate_foruser'] = $rides->driver_rating;
                $result['src_latitude'] = $rides->s_lat;
                $result['src_longitude'] = $rides->s_lon;
                $result['dst_latitude'] = $rides->d_lat;
                $result['dst_longitude'] = $rides->d_lon;
                $result['ride_code'] = $rides->ride_code;
                $result['accept_lat'] = $rides->accept_lat;
                $result['accept_lon'] = $rides->accept_lon;
                $result['time_started'] = $rides->start_at;
                $result['payment_method'] = $rides->payment_method;
                $result['status'] = $rides->status;
                $result['booking'] = ($rides->book_at)?1:0;
                $result['pay_amount'] = $rides->pay_amount;
                $result['distance_fee'] = $rides->distance_fee;
                $result['tax_fee'] = $rides->tax_fee;
                $result['cooper_fee'] = $rides->cooper_fee;
                $result['cooper_tax_fee'] = $rides->cooper_tax_fee;
                $result['distance'] = $rides->distance;
                $result['duration'] = $rides->rental_hours;
                $result['wallet_deduction'] = 0;
                $result['discount_fee'] = $rides->discount_fee;
                $service_type = ServiceTypes::findOrFail($rides->service_type);
                $result['base_fee'] = $service_type->fixed;

                $result['last_lat'] = $rides->last_lat;
                $result['last_lon'] = $rides->last_lon;
                $result['real_distance'] = $rides->rental_distance;
                if ($profile->service_type_status == Constants::$SERVICETYPE_STATUS_APPROVE) {
                    $message = Constants::$S_SERVICETYPE_APPROVED_BAD;
                } elseif ($profile->service_type_status == Constants::$SERVICETYPE_STATUS_INIT) {
                    $message = Constants::$S_SERVICETYPE_NOT_APPROVED;
                } else {
                    $message = Constants::$S_SUCCESS_GET;
                }
            } else {
                $result = null;
                if ($profile->service_type_status == Constants::$SERVICETYPE_STATUS_APPROVE) {
                    $data = array();
                    $data['service_type'] = $profile->new_service_type;
                    $data['new_service_type'] = null;
                    $data['service_type_release_at'] = date('Y-m-d H:i:s');
                    $data['service_type_status'] = Constants::$SERVICETYPE_STATUS_RELEASE;
                    $profile->update($data);
                    $message = Constants::$S_SERVICETYPE_RELEASED;
                } elseif ($profile->service_type_status == Constants::$SERVICETYPE_STATUS_INIT) {
                    $message = Constants::$S_SERVICETYPE_NOT_APPROVED;
                } else {
                    $message = Constants::$S_SUCCESS_GET;
                }
            }
            return Utils::makeResponse([
                'message'=>$message,
                'rides'=>$result,
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);

        }
    }

    public function getUserCurrentRide(Request $request)
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
            $rides = Rides::where('status', '<>', Constants::$RIDE_STATUS_CANCELED)
                ->where('status', '<>', Constants::$RIDE_STATUS_FINISHED)
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('book_at')->orWhere(function($query1) {
                        $query1->whereNotNull('book_at')
                            ->where('status', '>=', Constants::$RIDE_STATUS_ACCEPTED);
                    });
                })
                ->first();

            //$rides = Rides::where('id', 1445)->first();
            $result = array();
            if ($rides != null) {
                $driver = Drivers::where('id', $rides->driver_id)->first();
                if ($driver) {
                    $d_profile = DriverProfile::where('driver_id', $driver->id)->first();
                    $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                        ->where('driver_id', $driver->id)
                        ->avg('driver_rating');
                    $result['driver_email'] = $driver->email;
                    $result['driver_gender'] = $driver->gender;
                    $result['driver_firstname'] = $driver->first_name;
                    $result['driver_lastname'] = $driver->last_name;
                    $result['driver_phonenumber'] = $driver->phone_number;
                    $result['quickblox_id'] = $driver->quickblox_id;
                    $result['quickblox_username'] = $driver->quickblox_username;
                    $result['quickblox_password'] = $driver->quickblox_password;
                    $result['driver_carnumber'] = $d_profile->car_number;
                    $result['driver_carmodel'] = $d_profile->car_model;
                    $result['driver_avatar'] = empty($driver->login_by) ? url('storage/driver/avatar') . '/' . $d_profile->avatar : $d_profile->avatar;
                    $result['driver_averagerate'] = $avg;
                    $result['driver_servicetype'] = $d_profile->service_type;
                }

                $result['ride_id'] = $rides->id;
                $result['accepted_latitude'] = $rides->accept_lat;
                $result['accepted_longitude'] = $rides->accept_lon;
                $result['accepted_time'] = $rides->accept_at;
                $result['time_finished'] = $rides->driver_end_at;
                $result['time_requested'] = $rides->request_at;
                $result['comment_touser'] = $rides->driver_rated;
                $result['comment_todriver'] = $rides->user_rated;
                $result['rate_fordriver'] = $rides->user_rating;
                $result['rate_foruser'] = $rides->driver_rating;
                $result['src_latitude'] = $rides->s_lat;
                $result['src_longitude'] = $rides->s_lon;
                $result['dst_latitude'] = $rides->d_lat;
                $result['dst_longitude'] = $rides->d_lon;
                $result['ride_code'] = $rides->ride_code;
                $result['accept_lat'] = $rides->accept_lat;
                $result['accept_lon'] = $rides->accept_lon;
                $result['time_started'] = $rides->start_at;
                $result['payment_method'] = $rides->payment_method;
                $result['status'] = $rides->status;
                $result['booking'] = ($rides->book_at)?1:0;
                $result['pay_amount'] = $rides->pay_amount;
                $result['distance_fee'] = $rides->distance_fee;
                $result['tax_fee'] = $rides->tax_fee;
                $result['cooper_fee'] = $rides->cooper_fee;
                $result['cooper_tax_fee'] = $rides->cooper_tax_fee;
                $result['distance'] = $rides->distance;
                $result['duration'] = $rides->rental_hours;
                $result['wallet_deduction'] = 0;
                $result['discount_fee'] = $rides->discount_fee;
                $service_type = ServiceTypes::findOrFail($rides->service_type);
                $result['base_fee'] = $service_type->fixed;

                $result['last_lat'] = $rides->last_lat;
                $result['last_lon'] = $rides->last_lon;
                $result['real_distance'] = $rides->rental_distance;

                if ($rides->status == Constants::$RIDE_STATUS_DRIVERENDED) {
                    $kilometer = round($rides->distance/1000, 2);
                    $minutes = round($rides->rental_hours/60, 2);
                    $rental = ceil($rides->rental_hours);
                    $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
                    $service_type = ServiceTypes::findOrFail($rides->service_type);
                    $base_fee = $service_type->fixed;
                    $hour = $service_type->hour;
//                    if($service_type->calculator == 'MIN') {
//                        $distance_fee = $service_type->minute * $minutes;
//                    } else if($service_type->calculator == 'HOUR') {
//                        $distance_fee = $service_type->minute * 60;
//                    } else if($service_type->calculator == 'DISTANCE') {
//                        $distance_fee = ($kilometer * $service_type->price);
//                    } else if($service_type->calculator == 'DISTANCEMIN') {
//                        $distance_fee = ($kilometer * $service_type->price) + ($service_type->minute * $minutes);
//                    } else if($service_type->calculator == 'DISTANCEHOUR') {
//                        $distance_fee = ($kilometer * $service_type->price) + ($rental * $hour);
//                    } else {
//                        $distance_fee = ($kilometer * $service_type->price);
//                    }
                    $distance_fee = $kilometer * $service_type->price;
                    $distance_fee = round($distance_fee, 2);
                    $tax_fee = round(( $tax_percentage/100 ) * ($base_fee + $distance_fee), 2);

                    $result['base_fee'] = $base_fee;
                    $result['distance_fee'] = $distance_fee;
                    $result['tax_fee'] = $tax_fee;
                }
            } else {
                $result = null;
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getDriverRequestedRides(Request $request)
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
            $rides = Rides::where(array('status'=>Constants::$RIDE_STATUS_REQUESTED))
                ->whereNull('book_accept_at')
                ->where(function ($query) use ($driver) {
                    $query->whereNull('driver_id')->orWhere('driver_id', $driver->id);
                })
                ->get();
            $result = array();
            foreach ($rides as $r) {
                $user = Users::where('id', $r->user_id)->first();
                $avg = Rides::where('status', Constants::$RIDE_STATUS_FINISHED)
                    ->where('user_id', $r->user_id)
                    ->avg('user_rating');
                $tmp = array();
                $tmp['ride_id'] = $r->id;
                $tmp['is_booking'] = $r->book_at?1:0;
                $tmp['email'] = $user->email;
                $tmp['gender'] = $user->gender;
                $tmp['firstname'] = $user->first_name;
                $tmp['lastname'] = $user->last_name;
                $tmp['phone_number'] = $user->phone_number;
                $tmp['src_latitude'] = $r->s_lat;
                $tmp['src_longitude'] = $r->s_lon;
                $tmp['dst_latitude'] = $r->d_lat;
                $tmp['dst_longitude'] = $r->d_lon;
                $tmp['avatar'] = empty($user->login_by) ? url('storage/driver/avatar') . '/' . $user->avatar : $user->avatar;
                $tmp['user_avg_rating'] = $avg;
                $tmp['book_at'] = $r->book_at;
                $tmp['payment_method'] = $r->payment_method;
                $tmp['distance'] = $r->distance;
                $tmp['user_time'] = $r->request_at;
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

    // Only for booking ride
    public function cancelUserRide(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'ride_id'=>'required|max:255',
            'cancel_at'=>'required',
            'cancel_reason' =>'required'
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
            if (!$ride = Rides::where('id', $request->ride_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RIDE
                ], 0);
            }
            if (!$driver = Rides::where('id', $ride->driver_id)->first()) {
                $data = array();
                $data['status'] = Constants::$RIDE_STATUS_CANCELED;
                $data['canceled_at'] = date('Y-m-d H:i:s', $request->cancel_at);
                $data['cancel_reason'] = $request->cancel_reason;
                $data['cancel_by'] = 'user';
                $ride->update($data);
            } else {
                $data = array();
                $data['status'] = Constants::$RIDE_STATUS_CANCELED;
                $data['canceled_at'] = date('Y-m-d H:i:s', $request->cancel_at);
                $data['cancel_reason'] = $request->cancel_reason;
                $data['cancel_by'] = 'user';
                $penalty_fee = Settings::where('key', 'penalty_fee')->first()->value;
                $data['pay_amount'] = $penalty_fee;
                $commission_percentage = Settings::where('key', 'commission_percentage')->first()->value;
                $cooper_fee = round($penalty_fee * $commission_percentage / 100, 2);
                $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
                $cooper_tax_fee = round(( $tax_percentage/100 ) * $cooper_fee, 2);
                $data['cooper_fee'] = $cooper_fee;
                $data['cooper_tax_fee'] = $cooper_tax_fee;
                $data['user_start_balance'] = Wallet::getMyBalance($user->email);
                $data['driver_start_balance'] = Wallet::getMyBalance($driver->email);

                $user_data = array();
                $user_data['email'] = $user->email;
                $user_data['pp_fee'] = 0;
                $user_data['total'] = $penalty_fee;
                $user_data['amount'] = (-1) * ($penalty_fee);
                $user_data['cooper_fee'] = 0;
                $user_data['payment_method'] = 'wallet';
                $user_data['purpose'] = 'cancel penalty';
                $user_data['status'] = 'settled';
                $user_data['currency'] = 'USD';
                $user_data['ride_id'] = $ride->id;
                Wallet::create($user_data);

                $driver_data = array();
                $driver_data['email'] = $driver->email;
                $driver_data['pp_fee'] = 0;
                $driver_data['total'] = $penalty_fee;
                $driver_data['amount'] = $penalty_fee - $cooper_fee - $cooper_tax_fee;
                $driver_data['cooper_fee'] = $cooper_fee;
                $driver_data['payment_method'] = 'wallet';
                $driver_data['purpose'] = 'cancel penalty';
                $driver_data['status'] = 'settled';
                $driver_data['currency'] = 'USD';
                $driver_data['ride_id'] = $ride->id;
                Wallet::create($driver_data);

                $data['user_end_balance'] = Wallet::getMyBalance($user->email);
                $data['driver_end_balance'] = Wallet::getMyBalance($driver->email);

                $ride->update($data);
            }

            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_CANCEL
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }

    public function cancelDriverRide(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'ride_id'=>'required|max:255',
            'cancel_at'=>'required',
            'cancel_reason' =>'required'
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
            if (!$ride = Rides::where('id', $request->ride_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RIDE
                ], 0);
            }
            if (!$user = Users::where('id', $ride->user_id)->first()) {
                return Utils::makeResponse([
                    'message'=>Constants::$E_INVALID_RIDE
                ], 0);
            }
            $data = array();
            $data['status'] = Constants::$RIDE_STATUS_CANCELED;
            $data['canceled_at'] = date('Y-m-d H:i:s', $request->cancel_at);
            $data['cancel_reason'] = $request->cancel_reason;
            $data['cancel_by'] = 'driver';
            $penalty_fee = Settings::where('key', 'penalty_fee')->first()->value;
            $data['pay_amount'] = $penalty_fee;
            $commission_percentage = Settings::where('key', 'commission_percentage')->first()->value;
            $cooper_fee = round($penalty_fee*$commission_percentage/100, 2);
            $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
            $cooper_tax_fee = round(( $tax_percentage/100 ) * $cooper_fee, 2);
            $data['cooper_fee'] = $cooper_fee;
            $data['cooper_tax_fee'] = $cooper_tax_fee;
            $data['user_start_balance'] = Wallet::getMyBalance($user->email);
            $data['driver_start_balance'] = Wallet::getMyBalance($driver->email);

            $driver_data = array();
            $driver_data['email'] =$driver->email;
            $driver_data['pp_fee'] =0;
            $driver_data['total'] = $penalty_fee;
            $driver_data['amount'] = (-1) * ($penalty_fee);
            $driver_data['cooper_fee'] = 0;
            $driver_data['payment_method'] = 'wallet';
            $driver_data['purpose'] = 'cancel penalty';
            $driver_data['status'] = 'settled';
            $driver_data['currency'] = 'USD';
            $driver_data['ride_id'] = $ride->id;
            Wallet::create($driver_data);

            $user_data = array();
            $user_data['email'] =$user->email;
            $user_data['pp_fee'] =0;
            $user_data['total'] = $penalty_fee;
            $user_data['amount'] = $penalty_fee - $cooper_fee - $cooper_tax_fee;
            $user_data['cooper_fee'] = $cooper_fee;
            $user_data['payment_method'] = 'wallet';
            $user_data['purpose'] = 'cancel penalty';
            $user_data['status'] = 'settled';
            $user_data['currency'] = 'USD';
            $user_data['ride_id'] = $ride->id;
            Wallet::create($user_data);

            $data['user_end_balance'] = Wallet::getMyBalance($user->email);
            $data['driver_end_balance'] = Wallet::getMyBalance($driver->email);
            $ride->update($data);

            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_CANCEL
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();
            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }
    }
    // ------------------------- statistic -----------------------------
    public function getDriverTotalRides(Request $request)
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
            $total = Rides::where('driver_id', $driver->id)
                ->where(function ($query) {
                    $query->where('status', Constants::$RIDE_STATUS_FINISHED)->orWhere('status', Constants::$RIDE_STATUS_CANCELED);
                })
                ->count();
            $revenue = Wallet::where('email', $driver->email)
                ->where(function ($query) {
                    $query->where('purpose', 'pay')->orWhere('purpose', 'cancel_penalty');
                })
                ->sum('amount');
            $schedule = Rides::where('driver_id', $driver->id)
                ->where(function ($query) {
                    $query->where('status', Constants::$RIDE_STATUS_FINISHED)->orWhere('status', Constants::$RIDE_STATUS_CANCELED);
                })
                ->whereNotNull('book_at')
                ->where('book_at', '<>', '0')
                ->count();
            $cancelled = Rides::where('driver_id', $driver->id)
                ->where('status', Constants::$RIDE_STATUS_CANCELED)
                ->count();
            $result = array();
            $result['total'] = $total;
            $result['revenue'] = $revenue;
            $result['schedule'] = $schedule;
            $result['cancelled'] = $cancelled;

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
