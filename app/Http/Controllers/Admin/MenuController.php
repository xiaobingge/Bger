<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menus;
use Illuminate\Http\Request;
use OpenApi\Annotations\Flow;
use OpenApi\Annotations\OpenApi;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="dbger 项目接口文档",
 *     @OA\Contact(
 *         name="小兵哥",
 *         url="http://www.dbger.com",
 *         email="676826479@qq.com"
 *     )
 * ),
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST
 * ),
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST_API
 * ),
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     description="Use a global client_id / client_secret and your email / password combo to obtain a token",
 *     name="api_key_security",
 *     in="header",
 *     scheme="http",
 *     securityScheme="api_key_security",
 *     @OA\Flow(
 *         flow="password",
 *         authorizationUrl="/oauth/authorize",
 *         tokenUrl="/oauth/token",
 *         refreshUrl="/oauth/token/refresh",
 *         scopes={}
 *     )
 * )
 */

class MenuController extends Controller
{

    /**
     * @OA\Get(
     *     path="/menu/index",
     *     operationId="Menu",
     *     tags={"菜单管理"},
     *     summary="菜单列表",
     *     description="返回菜单列表信息",
     *     @OA\Parameter(
     *         name="pid",
     *         description="父级ID",
     *         required=false,
     *         in="query",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         description="页数",
     *         required=false,
     *         in="query",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         description="每页显示限制",
     *         required=false,
     *         in="query",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         description="id排序 +id 正序 -id 倒序",
     *         required=false,
     *         in="query",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="menu_name",
     *         description="菜单名称",
     *         required=false,
     *         in="query",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success"
     *     ),
     *     security={
     *         {"api_key_security": {}},
     *     }
     * )
     */
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
        return success(['total'=>$count,'items'=>$list,'tree'=>$tree]);
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
            return error(1001,'路由地址已存在');
        //创建权限点
        $permission = Permission::where(['name' => $data['permission_name'], 'guard_name' => $data['guard_name']])->first();
        if($permission){
            return error(1002,'权限标识已存在');
        }
        Permission::create(['name'=>$data['permission_name'],'guard_name'=>$data['guard_name']]);
        $id = Menus::insertGetId($data);
        return success(['id'=>$id]);
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
                return error(1001,'路由地址已存在');
        }
        if($menu->permission_name != $data['permission_name']){
            return error(1002,'权限标识不可修改');
        }
        Menus::where(['id'=>$id])->update($data);
        return success();
    }

    //删除菜单
    public function delete(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');
        $app = app();
        $guard_name = $app['auth']->getDefaultDriver();
        if(empty($id) || !in_array($type,[1,2,3]))
            return error(1001,'参数丢失');
        $flag = $this->deleteMenus($id,$guard_name);
        if($flag)
            return success();
        else
            return error(1002,'参数错误');
    }

    //递归处理
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
