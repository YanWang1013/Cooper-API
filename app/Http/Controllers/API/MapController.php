<?php
namespace App\Http\Controllers\API;

use App\Http\Models\DocType;
use App\Http\Constants;
use App\Http\Models\DriverDocument;
use App\Http\Models\Drivers;
use App\Http\Models\ServiceTypes;
use App\Http\Models\Users;
use App\Http\Utils;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;
use Setting;

class MapController extends Controller
{

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function getSearchPlaces(Request $request)
    {
        $rules=[
            'api_token'=>'required|max:255',
            'search_key'=>'max:255'
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
            $sql =  "SELECT a.*, b.* ".
                    "FROM t_places AS a, t_place_types AS b ".
                    "WHERE a.type_id = b.id ".
                    "   AND (a.name LIKE '%".$request->search_key."%' OR b.google_string LIKE '%".$request->search_key."%' OR a.address LIKE '%".$request->search_key."%')";
            $result = DB::select($sql);
            return Utils::makeResponse([
                'message'=>Constants::$S_SUCCESS_GET,
                'places'=>$result
            ]);
        } catch (Exception $e) {
            return $e->getMessage();

//            return Utils::makeResponse([
//                'message'=>Constants::$E_UNKNOWN_ERROR
//            ], 0);
        }

    }


}

