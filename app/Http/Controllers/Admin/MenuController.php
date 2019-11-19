<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menus;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class MenuController extends Controller
{
    //菜单列表
    public function index(Request $request)
    {
        $pid = $request->input('pid');
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $sort = $request->input('sort','+id');
        $name = $request->input('menu_name','');
        $export = $request->input('export',0);
        $ep = $sort == '+id' ? 'asc' :'desc';
        $obj = Menus::query();
        if(!empty($name)){
            $obj->where(['menu_name'=>$name]);
        }else{
            if($pid > 0){
                $obj->where(['id'=>$pid])->orWhere(['parent_id'=>$pid]);
            }
        }
        $count = $obj->count();
        if($export != 1){
            $obj->offset(($page-1)*$limit)->limit($limit);
        }
        $list = $obj->orderBy('id',$ep)->get();
        //菜单树处理
        $data = Menus::get();
        $tree = [];
        foreach($data as $k=>$v){
            $item = [];
            $item['id'] = $v->id;
            $item['pId'] = $v->parent_id;
            $item['name'] = $v->menu_name;
            $tree[] = $item;
        }
        return ['code'=>1000,'msg'=>'success','data'=>['total'=>$count,'items'=>$list,'tree'=>$tree]];
    }

    //添加菜单
    public function create(Request $request)
    {
        $data =  $request->all();
        unset($data['_url']);
        if(is_null($data['icon']))
            $data['icon'] = '';
        $app = app();
        $data['guard_name'] = $app['auth']->getDefaultDriver();
        $menu_1 = Menus::where(['uri'=>$data['uri']])->first();
        if($menu_1)
            return ['code'=>1001,'msg'=>'路由地址已存在'];
        //创建权限点
        $permission = Permission::where(['name' => $data['permission_name'], 'guard_name' => $data['guard_name']])->first();
        if($permission){
            return ['code'=>1001,'msg'=>'权限标识已存在'];
        }
        Permission::create(['name'=>$data['permission_name'],'guard_name'=>$data['guard_name']]);
        $id = Menus::insertGetId($data);
        return ['code'=>1000,'msg'=>'success','data'=>['id'=>$id]];
    }

    //编辑菜单
    public function update(Request $request)
    {
        $data = $request->all();
        unset($data['_url']);
        if(is_null($data['icon']))
            $data['icon'] = '';
        $app = app();
        $data['guard_name'] = $app['auth']->getDefaultDriver();
        $id = $data['id'];
        unset($data['id']);
        $menu = Menus::where(['id'=>$id])->first();
        if($menu->uri != $data['uri']){
            $menu_1 = Menus::where(['uri'=>$data['uri']])->first();
            if($menu_1)
                return ['code'=>1001,'msg'=>'路由地址已存在'];
        }
        if($menu->permission_name != $data['permission_name']){
            return ['code'=>1001,'msg'=>'权限标识不可修改'];
        }
        Menus::where(['id'=>$id])->update($data);
        return ['code'=>1000,'msg'=>'success'];
    }

    //删除菜单
    public function delete(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');
        $app = app();
        $guard_name = $app['auth']->getDefaultDriver();
        if(empty($id) || !in_array($type,[1,2,3]))
            return ['code'=>1001,'msg'=>'参数丢失'];
        $flag = $this->deleteMenus($id,$guard_name);
        if($flag)
            return ['code'=>1000,'msg'=>'success'];
        else
            return ['code'=>1002,'msg'=>'参数错误'];
    }

    private function deleteMenus($id,$guard_name)
    {
        $menu = Menus::where(['id'=>$id])->first();
        if(!$menu)
            return false;
        Menus::where(['id'=>$id])->delete();
        Permission::where(['guard_name'=>$guard_name,'name'=>$menu->permission_name])->delete();
        $son = Menus::where(['parent_id'=>$id])->get();
        if(!$son->isEmpty()){
            foreach($son as $k=>$v){
                $this->deleteMenus($v->id,$guard_name);
            }
        }
        return true;
    }

}
