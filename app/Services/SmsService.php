<?php
namespace App\Services;

use Overtrue\EasySms\EasySms;

class SmsService
{

    private $_config;
    private $smsClient;

    public function __construct()
    {
        $this->_config = config('sms');
        $this->smsClient = new EasySms($this->_config);
    }

    /*
     * 发送短信
     */
    public function sendSMS($mobile,$param,$gateways=[]){
        return $this->smsClient->send($mobile,$param,$gateways);
    }

}
