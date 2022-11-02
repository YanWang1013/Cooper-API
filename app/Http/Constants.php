<?php


namespace App\Http;



class Constants
{
    static public $USER_STATUS_INIT = 0;
    static public $USER_STATUS_VERIFIED = 1;
    static public $USER_STATUS_ACTIVED = 2;
    static public $USER_STATUS_TRAVELED = 3;

    static public $DRIVER_STATUS_INIT = 0;
    static public $DRIVER_STATUS_VERIFIED = 1;
    static public $DRIVER_STATUS_ACTIVED = 2;
    static public $DRIVER_STATUS_TRAVELED = 3;
    static public $DRIVER_STATUS_DENIED = 4;

    static public $RIDE_STATUS_REQUESTED = 0;
    static public $RIDE_STATUS_CANCELED = 1;
    static public $RIDE_STATUS_ACCEPTED = 2;
    static public $RIDE_STATUS_ARRIVED = 3;
    static public $RIDE_STATUS_STARTED = 4;
    static public $RIDE_STATUS_USERENDED = 5;
    static public $RIDE_STATUS_DRIVERENDED = 6;
    static public $RIDE_STATUS_PAY = 7;
    static public $RIDE_STATUS_PAID = 8;
    static public $RIDE_STATUS_USERRATED = 9;
    static public $RIDE_STATUS_FINISHED = 10;

    static public $COUPON_STATUS_CREATED = 'CREATED';
    static public $COUPON_STATUS_ADDED = 'ADDED';
    static public $COUPON_STATUS_EXPIRED = 'EXPIRED';
    static public $COUPON_STATUS_USED = 'USED';

    static public $SERVICETYPE_STATUS_INIT = 0;
    static public $SERVICETYPE_STATUS_APPROVE = 1;
    static public $SERVICETYPE_STATUS_RELEASE = 2;

    static public $S_SUCCESS_GET = 'S_SUCCESS_GET';
    static public $E_UNKNOWN_ERROR = 'E_UNKNOWN_ERROR';
    static public $S_SUCCESS_SIGNUP = 'S_SUCCESS_SIGNUP';
    static public $E_TWILIO_NUMBER_VERIFY_ERROR = 'E_TWILIO_NUMBER_VERIFY_ERROR';
    static public $S_SUCCESS_RESIGNUP = 'S_SUCCESS_RESIGNUP';
    static public $S_ALREADY_SIGNUP = 'S_ALREADY_SIGNUP';
    static public $E_DUPLICATED_EMAIL = 'E_DUPLICATED_EMAIL';
    static public $E_VERIFICATION_ERROR = 'E_VERIFICATION_ERROR';
    static public $E_VALIDATION_ERROR = 'E_VALIDATION_ERROR';
    static public $E_INVALID_EMAIL = 'E_INVALID_EMAIL';
    static public $E_INVALID_APITOKEN = 'E_INVALID_APITOKEN';
    static public $E_VERIFY_TIMEOUT = 'E_VERIFY_TIMEOUT';
    static public $E_INVALID_SMS_VERIFYCODE = 'E_INVALID_SMS_VERIFYCODE';
    static public $S_SUCCESS_SMS_VERIFY = 'S_SUCCESS_SMS_VERIFY';
    static public $E_INVALID_EMAIL_VERIFYCODE = 'E_INVALID_EMAIL_VERIFYCODE';
    static public $S_SUCCESS_VERIFY = 'S_SUCCESS_VERIFY';
    static public $S_SUCCESS_EMAIL_VERIFY = 'S_SUCCESS_EMAIL_VERIFY';
    static public $E_INVALID_DEVICETOKEN = 'E_INVALID_DEVICETOKEN';
    static public $E_NOT_SMS_VERIFY = 'E_NOT_SMS_VERIFY';
    static public $E_NOT_EMAIL_VERIFY = 'E_NOT_EMAIL_VERIFY';
    static public $E_NOT_VERIFY = 'E_NOT_VERIFY';
    static public $E_WRONG_PASSWORD = 'E_WRONG_PASSWORD';
    static public $S_SUCCESS_LOGIN = 'S_SUCCESS_LOGIN';
    static public $S_RESET_PASSWORD = 'S_RESET_PASSWORD';
    static public $E_INVALID_RESETCODE = 'E_INVALID_RESETCODE';
    static public $S_SUCCESS_RESETPASSWORD = 'S_SUCCESS_RESETPASSWORD';
    static public $S_SUCCESS_CHANGEPASSWORD = 'S_SUCCESS_CHANGEPASSWORD';
    static public $S_SUCCESS_SEND_EMAIL = 'S_SUCCESS_SEND_EMAIL';

    static public $S_SUCCESS_ADDCARD = 'S_SUCCESS_ADDCARD';
    static public $S_SUCCESS_UPDATECARD = 'S_SUCCESS_UPDATECARD';
    static public $S_SUCCESS_DELETECARD = 'S_SUCCESS_DELETECARD';
    static public $S_SUCCESS_GETCARD = 'S_SUCCESS_GETCARD';
    static public $S_SUCCESS_CHARGE = 'S_SUCCESS_CHARGE';

    static public $S_SUCCESS_DOCUMENT_UPLOAD_FOR_CREATE = 'S_SUCCESS_DOCUMENT_UPLOAD_FOR_CREATE';
    static public $S_SUCCESS_DOCUMENT_UPLOAD_FOR_UPDATE = 'S_SUCCESS_DOCUMENT_UPLOAD_FOR_UPDATE';

    static public $S_SUCCESS_UPDATEPROFILE = 'S_SUCCESS_UPDATEPROFILE';

    static public $E_PAYPAL_CONNECTION_TIMEOUT = 'E_PAYPAL_CONNECTION_TIMEOUT';
    static public $E_PAYPAL_UNKNOWN_ERROR = 'E_PAYPAL_UNKNOWN_ERROR';
    static public $S_PAY_CHARGE_SUCCESS = 'S_PAY_CHARGE_SUCCESS';
    static public $E_PAY_CHARGE_ERROR = 'E_PAY_CHARGE_ERROR';
    static public $S_PAY_PAY_SUCCESS = 'S_PAY_PAY_SUCCESS';
    static public $E_PAY_PAY_ERROR = 'E_PAY_PAY_ERROR';
    static public $S_PAY_OUT_SUCCESS = 'S_PAY_OUT_SUCCESS';
    static public $E_PAY_OUT_ERROR = 'E_PAY_OUT_ERROR';
    static public $E_PAY_OUT_NOT_ENOUGH_WALLET = 'E_PAY_OUT_NOT_ENOUGH_WALLET';

    static public $S_SUCCESS_GET_COUPON = 'S_SUCCESS_GET_COUPON';
    static public $NO_SUCH_COUPONCODE = 'NO_SUCH_COUPONCODE';


    static public $E_INVALID_RIDE = 'E_INVALID_RIDE';
    static public $E_INVALID_PAYMENT_METHOD = 'E_INVALID_PAYMENT_METHOD';
    static public $S_SUCCESS_CANCEL = 'S_SUCCESS_CANCEL';
    static public $S_NO_CURRENT_RIDE = 'S_NO_CURRENT_RIDE';

    static public $S_SUCCESS_ADDCARIMAGE = 'S_SUCCESS_ADDCARIMAGE';
    static public $S_SUCCESS_UPDATECARIMAGE = 'S_SUCCESS_UPDATECARIMAGE';
    static public $S_SUCCESS_DELETECARIMAGE = 'S_SUCCESS_DELETECARIMAGE';

    static public $S_SERVICETYPE_NOT_APPROVED = 'S_SERVICETYPE_NOT_APPROVED';
    static public $S_SERVICETYPE_APPROVED_BAD = 'S_SERVICETYPE_APPROVED_BAD';
    static public $S_SERVICETYPE_APPROVED_OK = 'S_SERVICETYPE_APPROVED_OK';
    static public $S_SERVICETYPE_RELEASED = 'S_SERVICETYPE_RELEASED';
}
