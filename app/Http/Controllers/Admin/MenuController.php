<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MenuController extends Controller
{

    //树形菜单列表
    public function tree(){



    }

    //菜单列表
    public function index(Request $request)
    {
        $type = $request->input('type');
        if($type == 1){
            $list = [
                [
                    'id'=>1,
                    'icon'=>'list',
                    'name'=>'系统管理',
                    'code'=>'system',
                    'url'=>'',
                    'm_type'=>0,
                    'c_type'=>0,
                    'status'=>1,
                    'sort'=>1
                ]

            ];
        }else{
            $list = [
                [
                    'id'=>1,
                    'icon'=>'list',
                    'name'=>'系统管理',
                    'code'=>'system',
                    'url'=>'',
                    'm_type'=>0,
                    'c_type'=>0,
                    'status'=>1,
                    'sort'=>1,
                    'parent_id'=>0
                ],
                [
                    'id'=>2,
                    'icon'=>'tree-table',
                    'name'=>'机构组织',
                    'code'=>'dept',
                    'url'=>'/system/dept',
                    'm_type'=>1,
                    'c_type'=>0,
                    'status'=>1,
                    'sort'=>2
                ]

            ];
        }

        return ['total'=>2,'items'=>$list];
    }

    //添加菜单
    public function create(Request $request)
    {

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
