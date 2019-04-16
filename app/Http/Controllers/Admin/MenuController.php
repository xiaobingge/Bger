<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    //菜单列表
    public function index(Request $request)
    {
        $pid = $request->input('pid');
        $data = DB::table('menus')->where('status',0)->get();
        if($pid > 0){
            $list = DB::table('menus')->where([['status','=',0],['id','=',$pid]])->orWhere([['status','=',0],['parent_id','=',$pid]])->get();
        }else{
            $list = $data;
        }
        //菜单树处理
        $tree = [];
        foreach($data as $k=>$v){
            $item = [];
            $item['id'] = $v->id;
            $item['pId'] = $v->parent_id;
            $item['name'] = $v->menu_name;
            $tree[] = $item;
        }
        return ['total'=>count($data),'items'=>$list,'tree'=>$tree];
    }

    //添加菜单
    public function create(Request $request)
    {
        $data =  $request->all();
        $id = DB::table('menus')->insertGetId($data);
        return ['id'=>$id];

    }

    //编辑菜单
    public function update(Request $request)
    {


    }

    //菜单详情
    public function detail(Request $request)
    {

    }

    //删除菜单
    public function delete(Request $request)
    {


    }

}
