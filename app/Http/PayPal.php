<?php
namespace App\Http;

use App\Http\Models\Settings;

/**
 * Class PayPal
 * @package App
 */
class Paypal{
    //private $URI_LIVE = "https://api.sandbox.paypal.com/v1/";
    private $client_id;
    private $secret;

    /**
     * Constructor
     *
     * Handles oauth 2 bearer token fetch
     * @link https://developer.paypal.com/webapps/developer/docs/api/#authentication--headers
     */
    public function __construct(){
        $postvals = "grant_type=client_credentials";
        $uri = "https://api.sandbox.paypal.com/v1/oauth2/token";

        $auth_response = self::curl($uri, 'POST', $postvals, true);
        $this->access_token = $auth_response['body']->access_token;
        $this->token_type = $auth_response['body']->token_type;
        echo $this->access_token; exit;
    }

    /**
     * Process Credit Card Payment
     *
     * Processes a credit card payment
     * @link https://developer.paypal.com/webapps/developer/docs/api/#create-a-payment
     */
    static public function aaa() {
        $postvals = "grant_type=client_credentials";
        $uri = "https://api.sandbox.paypal.com/v1/oauth2/token";

        $auth_response = curl($uri, 'POST', $postvals, true);
        $access_token = $auth_response['body']->access_token;
        $token_type = $auth_response['body']->token_type;
        return $access_token;
    }
    static public function process_cc_payment($request){
        $uri = "https://api.sandbox.paypal.com/v1/payments/payment";
        return self::curl($uri, 'POST', json_encode($request));
    }

    /**
     * Process PayPal Payment
     *
     * Processes a PayPal payment and redirects the user to log in and authorize
     * @link https://developer.paypal.com/webapps/developer/docs/api/#create-a-payment
     */
    static public function process_pp_payment($request){
        $uri = "https://api.sandbox.paypal.com/v1/payments/payment";
        $response = self::curl($uri, 'POST', json_encode($request));
        $redirect = $response['body']->links[1]->href;
        setcookie("id", $response['body']->id, time() + 3600);
        header('Location: ' . $redirect);
    }

    /**
     * Fetch Payments
     *
     * Returns a series of previous payments made to the application account
     * @link https://developer.paypal.com/webapps/developer/docs/api/#list-payment-resources
     */
    static public function fetch_payments($args){
        $query_string = '';
        if (count($args) > 0){ $query_string = '?' . http_build_query($args); }

        $uri = "https://api.sandbox.paypal.com/v1/payments/payment" . $query_string;
        return self::curl($uri, 'GET');
    }

    /**
     * Fetch Single Payment
     *
     * Returns a single payment made to the application account
     * @link https://developer.paypal.com/webapps/developer/docs/api/#look-up-a-payment-resource
     */
    static public function fetch_single_payment($id){
        $uri = "https://api.sandbox.paypal.com/v1/payments/payment/$id";
        return self::curl($uri, 'GET');
    }

    /**
     * Execute Approved Payment
     *
     * Executes an approved paypal payment
     * @link https://developer.paypal.com/webapps/developer/docs/api/#execute-an-approved-paypal-payment
     */
    static public function execute_payment($id, $request){
        $uri = "https://api.sandbox.paypal.com/v1/payments/payment/$id/execute/";
        setcookie("id","", time() - 3600);
        return self::curl($uri, 'POST', json_encode($request));
    }

    /**
     * Capture Authorization
     *
     * Capture a previous payment authorization.  First need to call process_payment with an intent of "authorize"
     * @link https://developer.paypal.com/webapps/developer/docs/api/#capture-an-authorization
     */
    static public function capture_authorization($id, $request){
        $uri = "https://api.sandbox.paypal.com/v1/payments/authorization/$id/capture";
        return self::curl($uri, 'POST', json_encode($request));
    }

    /**
     * Refund Sale
     *
     * Refunds a previous payment
     * @link https://developer.paypal.com/webapps/developer/docs/api/#refunds
     */
    static public function refund_sale($sale_id){
        $uri = "https://api.sandbox.paypal.com/v1/payments/sale/$sale_id/refund";
        return self::curl($uri, 'POST', '{}');
    }

    /**
     * Fetch Refund
     *
     * Returns a single refund details object
     * @link https://developer.paypal.com/webapps/developer/docs/api/#look-up-a-refund
     */
    static public function fetch_refund($id){
        $uri = "https://api.sandbox.paypal.com/v1/refund/$id";
        return self::curl($uri, 'GET');
    }

    /**
     * Store Credit Card
     *
     * Stores a credit card value in the vault
     * @link https://developer.paypal.com/webapps/developer/docs/api/#store-a-credit-card
     */
    static public function store_cc($cc_object){
        $uri = "https://api.sandbox.paypal.com/v1/vault/credit-card";
        return self::curl($uri, 'POST', json_encode($cc_object));
    }

    /**
     * Fetch Credit Card
     *
     * Fetches a credit card object from the vault
     * @link https://developer.paypal.com/webapps/developer/docs/api/#look-up-a-stored-credit-card
     */
    static public function fetch_cc($cc_id){
        $uri = "https://api.sandbox.paypal.com/v1/vault/credit-card/$cc_id";
        return self::curl($uri, 'GET');
    }

    /**
     * cURL
     *
     * Handles GET / POST requests for auth requests
     * @link http://php.net/manual/en/book.curl.php
     */
    private function curl($url, $method = 'GET', $postvals = null, $auth = false){
        $ch = curl_init($url);

        //if we are sending request to obtain bearer token
        if ($auth){
            $client_id = env('PAYPAL_CLIENT_ID');
            $secret = env('PAYPAL_SECRET');
            $headers = array("Accept: application/json", "Accept-Language: en_US");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $client_id . ":" .$secret);
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            //if we are sending request with the bearer token for protected resources
        } else {
            $headers = array("Content-Type:application/json", "Authorization:{$this->token_type} {$this->access_token}");
        }

        $options = array(
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_TIMEOUT => 10
        );


        if ($method == 'POST'){
            $options[CURLOPT_POSTFIELDS] = $postvals;
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $header = substr($response, 0, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
        $body = json_decode(substr($response, curl_getinfo($ch,CURLINFO_HEADER_SIZE)));
        curl_close($ch);

        return array('header' => $header, 'body' => $body);
    }
}