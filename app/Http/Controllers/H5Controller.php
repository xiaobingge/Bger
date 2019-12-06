<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use EasyWeChat\Factory;


class H5Controller extends  Controller
{

    public function index(Request $request)
    {
          $auth_domain = $wx_config = '';
          if(empty($request->cookie('auth_domain')))
              $auth_domain = cookie('auth_domain',env('AUTH_DOMAIN'),0,null,null,false,false);
          $app = Factory::officialAccount(config(\sprintf('wechat.official_account.%s', 'default'), []));
          $json_conf = $app->jssdk->buildConfig(array(), false, false, true);
          $wx_config = cookie('wx_config',$json_conf,0,null,null,false,false);
          $res = response()->view('mashu');
          $res->cookie($wx_config);
          if(!empty($auth_domain))
              $res->cookie($auth_domain);
          return  $res;
    }
}