<?php

namespace App\Http\Controllers\API;

use App\Http\Constants;
use App\Http\Models\Coupons;
use App\Http\Models\Drivers;
use App\Http\Models\Rides;
use App\Http\Models\Users;
use App\Http\Utils;
use App\ProviderDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Validator;

class CouponController extends Controller
{

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function addMyCoupon(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'coupon_code'=>'required|max:50'
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
            $coupon = Coupons::where(array('coupon_code'=>$request->coupon_code, 'status'=>'CREATED'))
                        ->where('expiration', '>', date('Y-m-d H:i:s'))->first();
            if ($coupon) {
                $data = array();
                $data['user_id'] = $user->id;
                $data['status'] = Constants::$COUPON_STATUS_CREATED;
                $data['added_at'] = date('Y-m-d H:i:s');
                $coupon->update($data);
                return Utils::makeResponse([
                    'message'=>Constants::$S_SUCCESS_GET_COUPON
                ]);
            } else {
                return Utils::makeResponse([
                    'message'=>Constants::$NO_SUCH_COUPONCODE
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
    public function getMyCoupon(Request $request)
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
            $coupons = Coupons::where('status', Constants::$COUPON_STATUS_CREATED)->where('expiration', '>', date('Y-m-d H:i:s'))->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'coupons'=>$coupons,
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

}
