<?php
namespace App\Services;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;

class ToolsService {

    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $res = '';
        $way = $this->request->input('way');
        $type = $this->request->input('type');
        if($way == 'encrypt'){
            switch($type){
                case 'id':
                    $id = $this->request->input('id');
                    if(!is_numeric($id))
                        return error(1001,'ID必须为数字');
                    $res = hashEncrypt($id);
                    break;
                case 'md5':
                    $res = md5($this->request->input('md5'));
                    break;
                case 'url':
                    $res = urlencode($this->request->input('url'));
                    break;
                case 'base64':
                    $res = base64_encode($this->request->input('base64'));
                    break;
                case 'time':
                    $res = strtotime($this->request->input('time'));
                    break;
                case 'code':
                    $code = $this->request->input('code');
                    $size = $this->request->input('size');
                    if(empty($code))
                        return error(1002,'请输入二维码内容');
                    if(empty($size))
                        $size = 200;
                    $sizes = explode('x',$size);
                    if($sizes[0] != $sizes[1])
                        return error(1001,'二维码尺寸必须为正方形');
                    $size = $sizes[0];
                    $res = 'qr.png';
                    QrCode::format('png')->size($size)->generate($code,storage_path('app/public/'.$res));
                    break;
            }
        }elseif($way == 'decrypt'){
            switch($type){
                case 'id':
                    $res = hashDecrypt($this->request->input('id'));
                    break;
                case 'url':
                    $res = urldecode($this->request->input('url'));
                    break;
                case 'base64':
                    $res = base64_decode($this->request->input('base64'));
                    break;
                case 'time':
                    $res = date('Y-m-d H:i:s',$this->request->input('time'));
                    break;
            }
        }
        return success($res);
    }

//    //获取env配置
//    public function getEnvConf(){
//        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
//        $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));
//        $items = [];
//        foreach($contentArray as $key=>$value){
//            if(!empty($value)){
//                $index = strpos($value,'=');
//                $item = [];
//                $item[] = substr($value,0,$index);
//                $item[] = substr($value,$index+1);
//                $items[] = $item;
//            }
//        }
//        return success($items);
//    }
//
//    //修改env配置
//    public function updateEnvConf(){
//        $data = $this->request->all();
//        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
//        $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));
//        $contentArray->transform(function ($item) use ($data){
//            foreach ($data as $key => $value){
//                if(str_contains($item, $key)){
//                    return $key . '=' . $value;
//                }
//            }
//            return $item;
//        });
//        $content = implode($contentArray->toArray(), "\n");
//        File::put($envPath, $content);
//        return success();
//    }
}