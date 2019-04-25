<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $users = DB::table('admins')->get();
        return ['code'=>1000,'data'=>['items'=>$users,'total'=>$users->count()]];
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

    //获取权限菜单
    public function menu(Request $request)
    {
        $user =  $request->user();
        $data = DB::table('menus')->where([['status','=',1],['menu_type','<',3]])->orderBy('sort','asc')->get();
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
                if($user->hasPermissionTo($v->permission_name)){
                    $son = [];
                    $son['path'] = $v->uri;
                    $son['component'] = $v->permission_name;
                    $son['name'] = $v->permission_name;
                    $son['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                    $menu[$v->parent_id]['children'][] = $son;
                }
            }
        }
        foreach($menu as $k=>$v){
            if(empty($v['children']))
                unset($menu[$k]);
        }
        return ['code'=>1000,'msg'=>'success','data'=>array_values($menu)];
    }
}
