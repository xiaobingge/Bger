<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Auth;
use App\Admin;

class RoleController extends Controller
{

    public function index()
    {
        $roles = DB::table('roles')->get();
        return ['code'=>1000,'data'=>$roles];
    }

    public function create(Request $request)
    {
        $app = app();
        $guard_name  = $app['auth']->getDefaultDriver();
        $name = $request->input('name');
        if(empty($name))
            return ['code'=>1001,'msg'=>'角色名称不能为空'];
        $role = DB::table('roles')->where(['name'=>$name,'guard_name'=>$guard_name])->first();
        if($role)
            return ['code'=>1002,'msg'=>'角色名称已存在'];
        $role = Role::create(['name'=>$name,'guard_name'=>$guard_name]);
        return ['code'=>1000,'data'=>$role];
    }

    public function update(Request $request)
    {
        $name = $request->input('name');
        $id = $request->input('id');
        if(empty($name))
            return ['code'=>1001,'msg'=>'角色名称不能为空'];
        if(empty($id))
            return ['code'=>1001,'msg'=>'ID不能为空'];
        $role = DB::table('roles')->where(['id'=>$id])->first();
        if($role->name == $name)
            return ['code'=>1003,'msg'=>'无更新内容'];
        DB::table('roles')->where(['id'=>$id])->update(['name'=>$name]);
        return ['code'=>1000,'msg'=>'success'];
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        if(empty($id))
            return ['code'=>1001,'msg'=>'ID不能为空'];
        DB::table('roles')->where(['id'=>$id])->delete();
        return ['code'=>1000,'msg'=>'success'];
    }

    public function getPermission(Request $request)
    {
        $data = [];
        $role_id =  $request->input('id',0);
        if(empty($role_id))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $role = Role::findById($role_id);
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
                if($role->hasPermissionTo($value->permission_name)){
                    $item['checked'] = true;
                }else{
                    $item['checked'] = false;
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
        $role_id =  $request->input('role_id',0);
        $ids = $request->input('ids');
        if(empty($role_id) || empty($ids))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $menu = DB::table('menus')->whereIn('id',$ids)->get();
        $role = Role::findById($role_id);
        $permissions = [];
        foreach($menu as $key=>$value){
            $permissions[] = $value->permission_name;
        }
        $role->syncPermissions($permissions);
        return ['code'=>1000];
    }

    public function getUsers(Request $request){
        $data = [];
        $role_id =  $request->input('id',0);
        if(empty($role_id))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $role = Role::findById($role_id);
        $users = Admin::get();
        foreach($users as $key=>$value){
            $data['users'][] = $value;
            if($value->hasRole($role->name)){
                $data['binds'][] = $value->id;
            }
        }
        return ['code'=>1000,'data'=>$data];
    }

    public function bindUsers(Request $request){
        $role_id =  $request->input('id',0);
        $uids = $request->input('uids');
        if(empty($role_id))
            return ['code'=>1001,'msg'=>'参数缺失'];
        $role = Role::findById($role_id);
        $users = Admin::role($role->name)->get();
        $bind = $r_bind =  $unbind = [];
        if(!empty($uids)){
            if($users->isEmpty()){
                foreach($users as $k=>$v){
                    if(!in_array($v->id,$uids)){
                        $unbind[] = $v->id;
                    }else{
                        $r_bind[] = $v->id;
                    }
                }
                $bind = array_diff($uids,$r_bind);
            }else{
                $bind = $uids;
            }
        }else{
            if(!$users->isEmpty()){
                foreach($users as $k=>$v){
                    $unbind[] = $v->id;
                }
            }else{
                return ['code'=>1001,'msg'=>'请选择一个用户授权'];
            }
        }
        if(!empty($unbind)){
           $users_1 = Admin::whereIn('id',$unbind)->get();
           foreach($users_1 as $k=>$v){
               $v->removeRole($role->name);
           }
        }
        if(!empty($bind)){
           $users_2 =  Admin::whereIn('id',$bind)->get();
            foreach($users_2 as $k=>$v){
                $v->assignRole($role->name);
            }
        }
        return ['code'=>1000];
    }

}
