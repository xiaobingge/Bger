<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OauthService;
use Illuminate\Http\Request;
use App\Admin;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

    //登录接口处理
    public function login(Request $request,OauthService $oauthService){

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = Admin::where(['name'=>$request->input('username')])->first();
        if(!$user)
            return error(1001,'用户不存在');

        if($user->status == 0)
            return error(1002,'用户已锁定');

        $password = $request->input('password');
        if(!Hash::check($password, $user->password))
            return error(1003,'密码错误');

        $data = $oauthService->getOauthToken(config('services.admins.appid'),config('services.admins.secret'),$request->input('username'),$request->input('password'),$request->input('provider'));
        if($data !== false)
            return success($data);
        else
            return error(1004,'用户认证失败');
    }
}
