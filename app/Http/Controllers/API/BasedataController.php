<?php

namespace App\Http\Controllers\API;

use App\Http\Models\DocType;
use App\Http\Constants;
use App\Http\Models\DriverDocument;
use App\Http\Models\Drivers;
use App\Http\Models\ServiceTypes;
use App\Http\Models\Settings;
use App\Http\Models\Users;
use App\Http\Models\Wallet;
use App\Http\Utils;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\Storage;
use Validator;
use Setting;

class BasedataController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
//    public function store(Request $request)
//    {
//        //
//    }
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getServiceType(Request $request)
    {
        try {
            $service_types = ServiceTypes::where(array('status'=>1))->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
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
    public function getDocType(Request $request)
    {
        try {

            $doc_types = DocType::where(array('type'=>'DRIVER'))->get();
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'service_type'=>$doc_types,
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
    public function getMyDoc(Request $request)
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
            $driver_docs = array();
            foreach($driver->documents as $index => $doc) {
                $tmp = array();
                $tmp['doc_id'] = $doc->document_id;
                $tmp['url'] = $doc->url;
                $tmp['expires_at'] = $doc->expires_at;
                $tmp['status'] = $doc->status;
                $driver_docs[$doc->document_id] = $tmp;
            }
            $doc_types = DocType::all();
            $result = array();
            foreach ($doc_types as $index=>$dt) {
                $tmp = array();
                $tmp['doc_id'] = $dt->id;
                $tmp['name'] = $dt->name;
                $tmp['type'] = $dt->type;
                if (isset($driver_docs[$dt->id])) {
                    $path = $driver_docs[$dt->id]['url'];
                } else {
                    $path = "";
                }
                $tmp['url'] = $path;
                $tmp['expires_at'] = isset($driver_docs[$dt->id])?$driver_docs[$dt->id]['expires_at']:'';
                $tmp['status'] = isset($driver_docs[$dt->id])?$driver_docs[$dt->id]['status']:'';
                $result[] = $tmp;
            }
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'docs'=>$result,
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
    public function uploadDoc(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'doc_id'=>'required',
            'doc_file' => 'required|mimes:jpg,jpeg,png,pdf',
            'expires_at'=>'max:255'
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
            $document = DriverDocument::where('driver_id', $driver->id)
                ->where('document_id', $request->doc_id)
                ->first();
            $filename = time().'_-'.$request->file('doc_file')->getClientOriginalName();
            if ($document != null) {
                $request->file('doc_file')->storeAs('public/driver/documents', $filename);
                $document->update([
                    'url' => url('storage/driver/documents').'/'.$filename,
                    'expires_at'=>$request->expires_at,
                    'status' => 'ASSESSING',
                ]);
                //$a = Storage::disk('document')->get($filename);
                //Storage::move($a, public_path('upload/driver/documents/'.$filename));
                return Utils::makeResponse([
                    'message'=>Constants::$S_SUCCESS_DOCUMENT_UPLOAD_FOR_UPDATE
                ]);
            } else {
                $request->file('doc_file')->storeAs('public/driver/documents', $filename);
                DriverDocument::create([
                    'url' => url('storage/driver/documents').'/'.$filename,
                    'driver_id' => $driver->id,
                    'document_id' => $request->doc_id,
                    'expires_at'=>$request->expires_at,
                    'status' => 'ASSESSING',
                ]);
                return Utils::makeResponse([
                    'message'=>Constants::$S_SUCCESS_DOCUMENT_UPLOAD_FOR_CREATE
                ]);
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
    public function getUserEstimatedFair(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'service_type'=>'required|max:20',
            'meter' => 'required|max:255',
            'seconds'=>'required|max:255'
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
            $kilometer = round($request->meter/1000, 1);
            $minutes = round($request->seconds/60, 2);

            $rental = ceil($request->rental_hours);

            $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
            $service_type = ServiceTypes::findOrFail($request->service_type);

            $price = $service_type->fixed;
            $hour = $service_type->hour;

//            if($service_type->calculator == 'MIN') {
//                $price += $service_type->minute * $minutes;
//            } else if($service_type->calculator == 'HOUR') {
//                $price += $service_type->minute * 60;
//            } else if($service_type->calculator == 'DISTANCE') {
//                $price += ($kilometer * $service_type->price);
//            } else if($service_type->calculator == 'DISTANCEMIN') {
//                $price += ($kilometer * $service_type->price) + ($service_type->minute * $minutes);
//            } else if($service_type->calculator == 'DISTANCEHOUR') {
//                $price += ($kilometer * $service_type->price) + ($rental * $hour);
//            } else {
//                $price += ($kilometer * $service_type->price);
//            }
            $distance_fee = round($kilometer * $service_type->price, 2);
            $price += $distance_fee;

            $tax_price = ( $tax_percentage/100 ) * $price;
            $tax_price = round($tax_price, 2);

            $total = $price + $tax_price;
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'data'=>[
                    'estimated_fare' => round($total,2),
                    'surge_value' => Settings::where('key', 'surge_percentage')->first()->value,
                    'surge_trigger' => Settings::where('key', 'surge_trigger')->first()->value,
                    'tax_fare' => $tax_price,
                    'base_fare' => $service_type->fixed,
                    'distance_fare'=>$distance_fee,
                    'wallet_balance' => Wallet::getMyBalance($user->email)
                ]
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
    public function getDriverEstimatedFair(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'service_type'=>'required|max:20',
            'meter' => 'required|max:255',
            'seconds'=>'required|max:255'
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
            $kilometer = round($request->meter/1000, 1);
            $minutes = round($request->seconds/60, 2);

            $rental = ceil($request->rental_hours);

            $tax_percentage = Settings::where('key', 'tax_percentage')->first()->value;
            $service_type = ServiceTypes::findOrFail($request->service_type);

            $price = $service_type->fixed;
            $hour = $service_type->hour;

//            if($service_type->calculator == 'MIN') {
//                $price += $service_type->minute * $minutes;
//            } else if($service_type->calculator == 'HOUR') {
//                $price += $service_type->minute * 60;
//            } else if($service_type->calculator == 'DISTANCE') {
//                $price += ($kilometer * $service_type->price);
//            } else if($service_type->calculator == 'DISTANCEMIN') {
//                $price += ($kilometer * $service_type->price) + ($service_type->minute * $minutes);
//            } else if($service_type->calculator == 'DISTANCEHOUR') {
//                $price += ($kilometer * $service_type->price) + ($rental * $hour);
//            } else {
//                $price += ($kilometer * $service_type->price);
//            }
            $distance_fee = round($kilometer * $service_type->price, 2);
            $price += $distance_fee;

            $tax_price = ( $tax_percentage/100 ) * $price;
            $tax_price = round($tax_price, 2);

            $total = $price + $tax_price;
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'data'=>[
                    'estimated_fare' => round($total,2),
                    'surge_value' => Settings::where('key', 'surge_percentage')->first()->value,
                    'surge_trigger' => Settings::where('key', 'surge_trigger')->first()->value,
                    'tax_fare' => $tax_price,
                    'base_fare' => $service_type->fixed,
                    'distance_fare'=>$distance_fee,
                    'wallet_balance' => Wallet::getMyBalance($driver->email)
                ]
            ]);
        } catch (Exception $e) {
            //return $e->getMessage();

            return Utils::makeResponse([
                'message'=>Constants::$E_UNKNOWN_ERROR
            ], 0);
        }

    }

}