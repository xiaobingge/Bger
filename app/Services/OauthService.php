<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OauthService {

    protected $_url;

    public function __construct()
    {
        $this->_url = env('APP_URL').'/oauth/token';
    }
    /*
     * 获取token
     */
    public function getOauthToken($client_id,$client_secret,$username,$password,$provider)
    {
        $http = new Client();
        // 发送相关字段到后端应用获取授权令牌
        try{
            $response = $http->post($this->_url, [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'username' => $username,  // 这里传递的是用户名
                    'password' => $password, // 传递密码信息
                    'provider' => $provider, //守卫
                    'scope' => '*'
                ],
                'timeout'=>10
            ]);
            return json_decode($response->getBody(),true);
        } catch(RequestException $e){
            mLog('OauthToken/'. date("Ymd") . '.log', '', ['args' => $e->getRequest(), 'result' =>$e->getResponse()]);
            return false;
        }

    }
}
