<?php
namespace App\Services;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

}