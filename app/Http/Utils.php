<?php


namespace App\Http;


use App\Http\Models\Settings;
use App\Mail\VerifyCodeMailable;
use Exception;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;

class Utils
{

    static public function makeResponse($data, $status = 1) {

        return response()->json([
            'data' => $data,
            'status' => $status
        ]);

    }

    static public function sendSmsForVerify($phone_number, $verify_code) {

        $twilio_sid = Settings::where('key', 'twilio_sid')->first();
        $twilio_token = Settings::where('key', 'twilio_token')->first();
        $twilio_trial_number = Settings::where('key', 'twilio_trial_number')->first();

        if (isset($twilio_sid)) {
            $twilio_sid = $twilio_sid->value;
        } else {
            $twilio_sid = '';
        }

        if (isset($twilio_token)) {
            $twilio_token = $twilio_token->value;
        } else {
            $twilio_token = '';
        }
        if (isset($twilio_trial_number)) {
            $twilio_trial_number = $twilio_trial_number->value;
        } else {
            $twilio_trial_number = '';
        }
        try {
            $twilio = new Client($twilio_sid, $twilio_token);

            $message = $twilio->messages
                ->create($phone_number, // to
                    array(
                        "from" => $twilio_trial_number,
                        "body" => "Verify Code:" . $verify_code
                    )
                );
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    static public function sendEmailForVerify($email, $verify_code) {

        $result = Mail::to($email)->send(new VerifyCodeMailable([
            'verify_code' => $verify_code, 'view_name'=>'verifycodeemail'
        ]));
        return response()->json([
            'result'=>$result
        ]);
    }

    static public function sendEmailForSOS($email, $msg_text) {
        $email = 'teamric0419@gmail.com';
        $result = Mail::to($email)->send(new VerifyCodeMailable([
            'msg_text' => $msg_text, 'view_name'=>'sosemail'
        ]));
        return response()->json([
            'result'=>$result
        ]);
    }

    static public function paypalfee($amount) {
        $amount_val = (double) $amount;
        return round($amount_val*0.039+0.3,2);
    }

}