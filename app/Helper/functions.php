<?php
/**
 * 获取阿里云原图地址
 * @param $path
 * @return string
 */
function getOssImageUrl($path){

    return strpos($path, '//') === 0 || strpos($path, 'http') === 0 ? $path : env('UPLOAD_DOMAIN') . $path;

}

/*
 * 获取本地图片地址
 */
function getImageUrl($path){
    return strpos($path, '//') === 0 || strpos($path, 'http') === 0 ? $path : env('APP_URL').'/storage'. $path;
}

//hashids 加密
function hashEncrypt($id,$secret='',$length=8){
    $hashids = new Hashids\Hashids($secret,$length);
    return $hashids->encode($id);
}

//hashids 解密
function hashDecrypt($str,$secret='',$length=8){
    $hashids = new Hashids\Hashids($secret,$length);
    return $hashids->decode($str);
}

/*
 * 计算时间差
 */
function timeDiff($startTime, $endTime)
{
    if ($startTime > $endTime) {
        return ["day" => 0, "hour" => 0, "min" => 0, "sec" => 0];
    }

    //计算天数
    $timeDiff = $endTime - $startTime;
    $days = intval($timeDiff / 86400);
    //计算小时数
    $remain = $timeDiff % 86400;
    $hours = intval($remain / 3600);
    //计算分钟数
    $remain = $remain % 3600;
    $mins = intval($remain / 60);
    //计算秒数
    $secs = $remain % 60;

    return ["day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs];
}
/**
 * Urlsafe base64 encode
 *
 * @param string $data
 *
 * @return string
 */
function base64_urlSafeEncode($data){
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($data));
}

/**
 * Urlsafe base64 decode
 *
 * @param string $str
 *
 * @return string
 */
function base64_urlSafeDecode($str){
    $find = array('-', '_');
    $replace = array('+', '/');
    return base64_decode(str_replace($find, $replace, $str));
}

/**
 * 自定义写日志
 *
 * @param  [type] $filename [指定路径]
 * @param  [type] $msg      [提示信息]
 * @param  array $context [上下文]
 *
 * @return [type]           [description]
 */
function mLog($filename, $msg, $context = [])
{
    $prefix = current(explode('/', $filename)) ?: 'local';
    $log    = new Monolog\Logger($prefix);
    $log->pushHandler(
        new Monolog\Handler\StreamHandler(
            storage_path('logs/' . $filename),
            Monolog\Logger::INFO
        )
    );
    $log->addInfo($msg, $context);
}

/**
 * 获取数组或对象符合条件的值
 * @param array|object $arr
 * @param string $key 条件key
 * @param mixed $val 条件val
 * @param string $field 返回字段
 * @param int $type 1:单个 其它多个
 * @return array|string
 */
function array_getval(&$arr, $key, $val, $field='*', $type=1){
    $data = ($field == '*' || $type != 1) ? [] : '';
    foreach ($arr as $v){
        if(is_array($v)){
            if($v[$key] == $val){
                if($type == 1){
                    return $field == '*' ? $v : (isset($v[$field])?$v[$field]:'');
                }else{
                    $data[] = $field == '*' ? $v : (isset($v[$field])?$v[$field]:'');
                }
            }
        }else{
            if($v->$key == $val){
                if($type == 1) {
                    return $field == '*' ? $v : (isset($v->$field) ? $v->$field : '');
                }else{
                    $data[] = $field == '*' ? $v : (isset($v->$field) ? $v->$field : '');
                }
            }
        }
    }
    return $data;
}


/**
 * 获取当前控制器名
 *
 * @return string
 */
function getCurrentControllerName()
{
    return getCurrentAction()['controller'];
}

/**
 * 获取当前方法名
 *
 * @return string
 */
function getCurrentMethodName()
{
    return getCurrentAction()['method'];
}

/**
 * 获取当前控制器与方法
 *
 * @return array
 */
function getCurrentAction()
{
    $action = \Route::current()->getActionName();
    list($class, $method) = explode('@', $action);
    return ['controller' => $class, 'method' => $method];
}

/*
 * 成功返回
 */
function success($data='')
{
    return ['code'=>1000,'data'=>$data];
}

/*
 * 失败返回
 */
function error($code,$msg){

    return ['code'=>$code,'msg'=>$msg];
}


if (!function_exists('getSql')) {
    function getSql ()
    {
        DB::listen(function ($sql) {
            $singleSql = $sql->sql;
            if ($sql->bindings) {
                foreach ($sql->bindings as $replace) {
                    $value = is_numeric($replace) ? $replace : "'" . $replace . "'";
                    $singleSql = preg_replace('/\?/', $value, $singleSql, 1);
                }
                dump($singleSql);
            } else {
                dump($singleSql);
            }
        });
    }
}




















































?>