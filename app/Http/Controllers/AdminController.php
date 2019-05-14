<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Admin;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    //登录接口处理
    public function login(Request $request){

        $user = Admin::where(['name'=>$request->input('username')])->first();
        if(!$user)
            return ['code'=>1001,'msg'=>'用户不存在'];
        if($user->status == 0)
            return ['code'=>1002,'msg'=>'用户已锁定'];
        $password = $request->input('password');
        if(!Hash::check($password, $user->password))
            return ['code'=>1003,'msg'=>'密码错误'];
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        $http = new Client();
        // 发送相关字段到后端应用获取授权令牌
        try{
            $response = $http->post('http://bger.com/oauth/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('services.admins.appid'),
                    'client_secret' => config('services.admins.secret'),
                    'username' => $request->input('username'),  // 这里传递的是用户名
                    'password' => $request->input('password'), // 传递密码信息
                    'provider' => $request->input('provider'), //守卫
                    'scope' => '*'
                ],
                'timeout'=>10
            ]);
            return ['code'=>1000,'msg'=>'success','data'=>json_decode($response->getBody(),true)];
        } catch(RequestException $e){
            if ($e->hasResponse()) {
                return $e->getResponse();
            }else{
                return $e->getRequest();
            }
        }
    }
}