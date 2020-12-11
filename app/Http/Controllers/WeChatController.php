<?php

namespace App\Http\Controllers;

use App\User;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Message;
use App\Services\Wechat\TextMessageHandler;
use App\Services\Wechat\EventMessageHandler;
use Illuminate\Support\Facades\DB;

class WeChatController extends  Controller
{
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/page/index';

    public function index()
    {
        $config = config('wechat.official_account.default');
        $app = Factory::officialAccount($config);
        $app->server->push(TextMessageHandler::class, Message::TEXT); // 文本消息
        $app->server->push(EventMessageHandler::class,Message::EVENT);
        $response = $app->server->serve();
        return $response;
    }

    //处理微信授权后的登录逻辑
    public function authLogin(Request $request) {
        $first_login = 0;
        $target = urldecode($request->input('target'));
        if(!empty($target))
            $this->redirectTo = trim($target);
        $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $openId = $user->getId();
        $original = $user->getOriginal();
        $info = User::where(['openid'=>$openId])->first();
        if(!$info){
            //注册用户
            $userInfo = [
                'app_id'=>1,
                'name'=>$this->setUserName($user->getName()),
                'nickname'=>$user->getNickname(),
                'openid'=>$openId,
                'avatar'=>$user->getAvatar(),
                'sex'=>$original['sex'],
                'province'=>$original['province'],
                'city'=>$original['city'],
                'password' => Hash::make(env('DEFAULT_PASSWORD')),
            ];
            $flag = User::create($userInfo);
            if(!$flag){
                cookie('Authorization',-1,0,null,null,false,false);
                return ['code'=>1003,'msg'=>'注册用户失败'];
            }
            $first_login = 1;
        }else{
            if($info->status == 0){
                cookie('Authorization',-1,0,null,null,false,false);
                return ['code'=>1001,'msg'=>'用户被禁用'];
            }
            //如果密码不一致重置密码
            if($info->password != Hash::make(env('DEFAULT_PASSWORD')))
                User::where(['openid'=>$openId])->update(['password' => Hash::make(env('DEFAULT_PASSWORD'))]);
        }
        //获取登录token
        $http = new Client();
        // 发送相关字段到后端应用获取授权令牌
        try{
            $response = $http->post(env('APP_URL').'/oauth/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('services.mashu.appid'),
                    'client_secret' => config('services.mashu.secret'),
                    'username' => $openId,  // 这里传递的是openid
                    'password' => env('DEFAULT_PASSWORD'), // 传递密码信息
                    'provider' => 'users', //守卫
                    'scope' => '*'
                ],
                'timeout'=>10
            ]);
            //设置cookie
            $arr = json_decode($response->getBody(),true);
            //重定向
            if($first_login == 1)
                $this->redirectTo = "/page/index/#/pages/camp/index/index?redirectURL=%2Fpages%2Fuser%2Fchoose%2Findex";
            return redirect($this->redirectTo)
                ->cookie('Authorization',$arr['access_token'],$arr['expires_in'],null,null,false,false);
        } catch(RequestException $e){
            if ($e->hasResponse()) {
                return $e->getResponse();
            }else{
                return['code'=>1002,'msg'=>'登录失败'];
            }
        }
    }
    //创建用户名
    protected function setUserName($name){
       $user =  User::where(['name'=>$name])->first();
       if($user){
           self::setUserName($name.'_'.rand(1,10000));
       }
       return $name;
    }

    //小程序登录
    public function weappLogin(Request $request)
    {
        $code = $request->code;
        // 根据 code 获取微信 openid 和 session_key
        $config = config('wechat.mini_program.default');
        $miniProgram = Factory::miniProgram($config);
        $data = $miniProgram->auth->session($code);
        if (isset($data['errcode'])) {
            return error(1001, 'code已过期或不正确');
        }
        $openId = $data['openid'];
        $nickname = $request->nickname;
        $avatar = str_replace('/132', '/0', $request->avatar);//拿到分辨率高点的头像
        $country = $request->country ? $request->country : '';
        $province = $request->province ? $request->province : '';
        $city = $request->city ? $request->city : '';
        $gender = $request->gender == '1' ? '1' : '2';//没传过性别的就默认女的吧，体验好些
        //找到 openid 对应的用户
        $user = User::where('openid', $openId)->first();
        //没有，就注册一个用户
        if (!$user) {
            $user = User::create([
                'openid' => $openId,
                'password' => Hash::make(env('DEFAULT_PASSWORD')),
                'avatar' => $avatar,
                'nickname' => $nickname,
                'country' => $country,
                'province' => $province,
                'city' => $city,
                'gender' => $gender,
            ]);
        }
        //如果注册过的，就更新下下面的信息
        $attributes['updated_at'] = now();
        $attributes['avatar'] = $avatar;
        if ($nickname) {
            $attributes['nickname'] = $nickname;
        }
        if ($gender) {
            $attributes['gender'] = $gender;
        }
        // 更新用户数据
        $user->update($attributes);
        //获取登录token
        $http = new Client();
        // 发送相关字段到后端应用获取授权令牌
        try {
            $response = $http->post(env('APP_URL') . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('services.users.appid'),
                    'client_secret' => config('services.users.secret'),
                    'username' => $openId,  // 这里传递的是openid
                    'password' => env('DEFAULT_PASSWORD'), // 传递密码信息
                    'provider' => 'users', //守卫
                    'scope' => '*'
                ],
                'timeout' => 10
            ]);
            //设置cookie
            $arr = json_decode($response->getBody(), true);
            return success(['access_token' => $arr['access_token'], 'token_type' => "Bearer", 'expires_in' => $arr['expires_in']]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse();
            } else {
                return ['code' => 1002, 'msg' => '登录失败'];
            }
        }

    }

}