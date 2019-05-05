<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\DB;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uri = \Request::path();
        $data = DB::table('menus')->where(['uri'=>'/'.$uri])->first();
        if( ($data  && $request->user()->can($data->permission_name) || in_array($request->user()->name,config('auth.administrators')))){
            return $next($request);
        }else{
            return  response(['code'=>1024,'msg'=>'您没权限操作，请联系管理员']);
        }
    }
}
