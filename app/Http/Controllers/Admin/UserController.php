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
        $type = $request->input('type');
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $sort = $request->input('sort','+id');
        $keyword = $request->input('keyword','');
        $export = $request->input('export',0);
        $ep = $sort == '+id' ? 'asc' :'desc';
        $obj = Admin::where('id','>',0);
        if(!empty($type) && !empty($keyword)){
            switch($type){
                case 1:
                    $obj = Admin::where(['name'=>$keyword]);
                    break;
                case 2:
                    $obj = Admin::where(['email'=>$keyword]);
                    break;
            }
        }
        $count = $obj->count();
        if($export != 1){
            $obj->offset(($page-1)*$limit)->limit($limit);
        }
        $users = $obj->orderBy('id',$ep)->get();
        foreach($users as $key=>$value){
            $value->roles = $value->hasAllRoles(Role::all());
        }
        return ['code'=>1000,'data'=>['items'=>$users,'total'=>$count]];
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
            $roles = $user->getRoleNames();
            if(false === $roles){
                return ['code'=>1001,'msg'=>'请选择一个角色'];
            }else{
                foreach($roles as $k=>$v){
                    $user->removeRole($v);
                }
            }
        }else{
            $user->syncRoles($role_ids);
        }
        return ['code'=>1000,'data'=>$user];
    }

    public function getPermission(Request $request)
    {
        $data = [];
        $uid =  $request->input('id',0);
        if(empty($uid))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $user = Admin::find($uid);
        $menu = DB::table('menus')->get();
        $arr = $top = [];
        foreach($menu as $key=>$value){
            $item = [];
            if($value->parent_id == 0){
                $top[$value->id]['name'] = $value->menu_name;
            }else{
                $item['id'] = $value->id;
                $item['name'] = $value->menu_name;
                $item['parent_id'] = $value->parent_id;
                $item['checked'] = false;
                $permissions = $user->getDirectPermissions();
                foreach($permissions as $k=>$v){
                    if($v->name == $value->permission_name)
                        $item['checked'] = true;
                }
                $arr[$value->parent_id][] = $item;
            }
        }
        foreach($top  as $k=>$v){
            $dd['id'] = $k;
            $dd['name'] = $v['name'];
            $dd['isIndeterminate'] = false;
            $dd['menu'] = !empty($arr[$k]) ? $arr[$k] : [];
            if(!empty($dd['menu'])){
                foreach($dd['menu'] as $kk=>$vv){
                    if(!empty($arr[$vv['id']])){
                        $dd['menu'][$kk]['son'] = $arr[$vv['id']];
                    }
                }
            }
            $data[]  = $dd;
        }
        return ['code'=>1000,'data'=>$data];
    }

    public function setPermission(Request $request){
        $uid =  $request->input('uid',0);
        $ids = $request->input('ids');
        if(empty($uid) || empty($ids))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $menu = DB::table('menus')->whereIn('id',$ids)->get();
        $user = Admin::find($uid);
        $permissions = [];
        foreach($menu as $key=>$value){
            $permissions[] = $value->permission_name;
        }
        $user->syncPermissions($permissions);
        return ['code'=>1000];
    }

    public function updateStatus(Request $request)
    {
        $uid =  $request->input('id',0);
        $type = $request->input('type',0);
        if(empty($uid) || !in_array($type,[0,1]))
            return ['code'=>1001,'msg'=>'参数缺失'];
        Admin::where(['id'=>$uid])->update(['status'=>$type]);
        return ['code'=>1000];
    }

    public function getRoles()
    {
        $roles = Role::all();
        return ['code'=>1000,'data'=>['roles'=>$roles]];
    }

    public function updatePassword(Request $request){
        $user =  $request->user();
        $password = $request->input('password','');
        if(empty($password))
            return ['code'=>1001,'msg'=>'参数错误'];
        Admin::where(['id'=>$user->id])->update(['password'=> Hash::make($password)]);
        return ['code'=>1000];
    }
    //获取用户信息
    public function user(Request $request)
    {
        $user =  $request->user();
        $user['avatar'] = "https://avatars2.githubusercontent.com/u/26640264?s=460&v=4";
        $user['introduction'] = "程序员一枚";
        $user['roles'] = ['edit'];
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
                if(($user->hasPermissionTo($v->permission_name) || in_array($user->name,config('auth.administrators'))) && !empty($menu[$v->parent_id])){
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
