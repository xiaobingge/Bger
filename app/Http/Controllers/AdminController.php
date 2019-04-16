<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $menu = [[
            'path'=>'/',
            'component'=>'layout',
            'meta'=>['title'=>'系统','icon'=>'excel'],
            'children'=>[
                ['path'=>'binge/list','component'=>'list.a','meta'=>['title'=>'菜单管理']],
                ['path'=>'binge/group','component'=>'group','meta'=>['title'=>'用户组']]
            ]
        ]];
        return $menu;

    }


    public function mList(Request $request)
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
}