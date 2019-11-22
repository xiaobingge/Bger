<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\Menus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

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
        $obj = Admin::query();
        $obj->where('id','>',0);
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
            $value->apps = !empty($value->app_ids) ? Apps::whereIn('id',explode(',',$value->app_ids))->get() : '';
        }
        return success(['items'=>$users,'total'=>$count]);
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
            return error(1001,$msg);
        }else{
            //应用处理
            $app_ids = $request->input('selected_app_ids');
            $res = Admin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'app_ids' => !empty($app_ids) ? implode(',',$app_ids): '',
            ]);
            $res['status'] = 1;
            //角色处理
            $role_ids = $request->input('selected_ids');
            if(!empty($role_ids)){
                $res->syncRoles($role_ids);
            }
            return success($res);
        }
    }

    public function update(Request $request){
        $uid = $request->input('id');
        $role_ids = $request->input('selected_ids');
        $app_ids = $request->input('selected_app_ids');
        $avatar = $request->input('avatar');
        $app_str = !empty($app_ids) ? implode(',',$app_ids) : '';
        Admin::where(['id'=>$uid])->update(['app_ids'=>$app_str,'avatar'=>$avatar]);
        $user = Admin::find($uid);
        if(empty($role_ids)){
            $roles = $user->getRoleNames();
            if(false === $roles){
                return error(1001,'请选择一个角色');
            }else{
                foreach($roles as $k=>$v){
                    $user->removeRole($v);
                }
            }
        }else{
            $user->syncRoles($role_ids);
        }
        $user->apps = !empty($app_ids) ? Apps::whereIn('id',$app_ids)->get() : '';
        return success($user);
    }

    //删除数据处理
    public function delete(Request $request){
        $id = $request->input('id');
        if(empty($id))
            return error(1001,'参数丢失');
        $user = Admin::where(['id'=>$id])->first();
        if(empty($user->id))
            return error(1002,'用户不存在');
        if(in_array($user->name,config('auth.administrators')))
            return error(1003,'系統用戶不能刪除');
        //角色清空
        $user->syncRoles([]);
        //权限清空
        $user->syncPermissions([]);
        //删除用户
        Admin::where(['id'=>$id])->delete();
        return success();
    }

    public function getPermission(Request $request)
    {
        $data = [];
        $uid =  $request->input('id',0);
        if(empty($uid))
            return error(1001,'参数缺失');
        $user = Admin::find($uid);
        $menu = Menus::get();
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
        return success($data);
    }

    public function setPermission(Request $request){
        $uid =  $request->input('uid',0);
        $ids = $request->input('ids');
        if(empty($uid))
            return error(1001,'参数缺失');
        $user = Admin::find($uid);
        if(!empty($ids)){
            $menu = Menus::whereIn('id',$ids)->get();
            $permissions = [];
            foreach($menu as $key=>$value){
                $permissions[] = $value->permission_name;
            }
            $user->syncPermissions($permissions);
        }else{
            $permissions = $user->getDirectPermissions();
            if($permissions){
                foreach($permissions as $k=>$v){
                    $user->revokePermissionTo($v->name);
                }
            }else{
                return error(1002,'请至少选择一个权限');
            }
        }
        return success();
    }
    //锁定用户
    public function updateStatus(Request $request)
    {
        $uid =  $request->input('id',0);
        $type = $request->input('type',0);
        if(empty($uid) || !in_array($type,[0,1]))
            return error(1001,'参数缺失');
        Admin::where(['id'=>$uid])->update(['status'=>$type]);
        //删除token
        return success();
    }

    //修改密码
    public function updatePassword(Request $request){
        $user =  $request->user();
        $password = $request->input('password','');
        if(empty($password))
            return error(1001,'参数错误');
        Admin::where(['id'=>$user->id])->update(['password'=> Hash::make($password)]);
        return success();
    }
    //获取用户信息
    public function user(Request $request)
    {
        $user =  $request->user();
        $user['apps'] = !empty($user->app_ids) ? Apps::whereIn('id',explode(',',$user->app_ids))->get() : '';
        return success($user);
    }

    //获取权限菜单
    public function menu(Request $request)
    {
        //开发阶段每次清理下权限缓存
        if(env('APP_DEBUG'))
            Cache::forget(config('permission.cache.key'));
        $user =  $request->user();
        $data = Menus::where([['menu_type','<',3]])->orderBy('menu_type','asc')->orderBy('sort','asc')->get();
        $menu = [];
        foreach($data as $k=>$v){
            $item = [];
            if($v->parent_id == 0){
                $item['path'] = $v->uri;
                $item['component'] = 'layout';
                $item['name'] = $v->permission_name;
                $item['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                if($v->status == 0)
                    $item['hidden'] = true;
                $menu[$v->id] = $item;
            }else{
                if($user->hasPermissionTo($v->permission_name) || in_array($user->name,config('auth.administrators'))){
                    $son = [];
                    $son['path'] = $v->uri;
                    $son['component'] = $v->permission_name;
                    $son['name'] = $v->permission_name;
                    $son['meta'] = ['title'=>$v->menu_name,'icon'=>$v->icon];
                    if($v->status == 0)
                        $son['hidden'] = true;
                    $menu[$v->parent_id]['children'][] = $son;
                }
            }
        }
        foreach($menu as $k=>$v){
            if(empty($v['children']))
                unset($menu[$k]);
        }
        return success(array_values($menu));
    }

    //获取角色
    public function getRoles()
    {
        $roles = Role::all();
        $apps = Apps::where(['status'=>1])->get();
        return success(['roles'=>$roles,'apps'=>$apps]);
    }

}
