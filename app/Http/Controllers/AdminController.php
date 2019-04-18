<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;


class AdminController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.home');
    }

    //获取用户信息
    public function user(Request $request)
    {
        $user =  $request->user();
        $user['avatar'] = "https://avatars2.githubusercontent.com/u/26640264?s=460&v=4";
        $user['introduction'] = "程序员一枚";
        $user['roles'] = ['editor'];
        return ['code'=>1000,'msg'=>'success','data'=>$user];
    }

    //登录接口处理
    public function getToken(Request $request){
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
    //获取权限菜单
    public function menu()
    {
        $data = DB::table('menus')->where([['status','=',1],['menu_type','<',3]])->get();
        $menu = [];
        foreach($data as $k=>$v){
            $item = [];
            if($v->parent_id == 0){
                $item['path'] = $v->uri;
                $item['component'] = 'layout';
                $item['name'] = $v->permission_name;
                $item['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                $menu[$v->id] = $item;
            }else{
                $son = [];
                $son['path'] = $v->uri;
                $son['component'] = $v->permission_name;
                $son['name'] = $v->permission_name;
                $son['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                $menu[$v->parent_id]['children'][] = $son;
            }
        }
        return ['code'=>1000,'msg'=>'success','data'=>array_values($menu)];
    }
}