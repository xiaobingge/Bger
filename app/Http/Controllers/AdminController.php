<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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

    public function user(Request $request)
    {
        $user =  $request->user();
        $user['avatar'] = "https://avatars2.githubusercontent.com/u/26640264?s=460&v=4";
        $user['introduction'] = "程序员一枚";
        $user['roles'] = ['editor'];
        return $user;
    }

    public function menu()
    {
        $data = DB::table('menus')->where([['status','=',0],['menu_type','<',3]])->get();
        $menu = [];
        foreach($data as $k=>$v){
            $item = [];
            if($v->parent_id == 0){
                $item['path'] = $v->uri;
                $item['component'] = 'layout';
                $item['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                $menu[$v->id] = $item;
            }else{
                $son = [];
                $son['path'] = $v->uri;
                $son['component'] = $v->permission_name;
                $son['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                $menu[$v->parent_id]['children'][] = $son;
            }
        }
        return array_values($menu);
    }
}