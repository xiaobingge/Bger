<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $users = Admin::get();
        foreach($users as $key=>$value){
            $value->roles = $value->hasAllRoles(Role::all());
        }
        return ['code'=>1000,'data'=>['items'=>$users,'total'=>$users->count()]];
    }

    public function create(Request $request)
    {
        $data = $request->all();
        if(empty($data['password']))
            $data['password'] = 123456;
        $validator = Validator::make($data, [
            'name' => 'required|string|max:191|unique:admins',
            'email' => 'required|string|email|max:191|unique:admins',
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            $error = \GuzzleHttp\json_decode($validator->errors(),true);
            if(!empty($error['name'])){
                $msg = $error['name'];
            }elseif(!empty($error['email'])){
                $msg = $error['email'];
            }else{
                $msg = $error['password'];
            }
            return ['code'=>1001,'msg'=>$msg];
        }else{
            $res = Admin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            return ['code'=>1000,'data'=>$res];
        }
    }

    public function update(Request $request){
        $uid = $request->input('id');
        $role_ids = $request->input('selected_ids');
        $user = Admin::find($uid);
        if(empty($role_ids)){
            $roles = $user->hasAllRoles(Role::all());
            if(false === $roles){
                return ['code'=>1001,'msg'=>'请选择一个角色'];
            }else{
                foreach($roles as $k=>$v){
                    $user->removeRole($v->name);
                }
            }
        }else{
            $user->syncRoles($role_ids);
        }
        return ['code'=>1000,'data'=>$user];
    }

    public function getRoles()
    {
        $roles = Role::all();
        return ['code'=>1000,'data'=>['roles'=>$roles]];
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
