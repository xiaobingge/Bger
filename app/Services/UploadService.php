<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadService {

    public $request;
    public $ossService;
    const TYPE_AVATAR = 'avatar';
    const DRIVER_ALIOSS = 'AliOss';


    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    //文件上传
    public function uploadFiles()
    {
        if (!$this->request->hasFile('file')) {
            return error(1001,'无法获取上传文件');
        }
        $file = $this->request->file('file');
        if ($file->isValid()) {
            // 获取文件相关信息
            $originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $type = $file->getClientMimeType();     // image/jpeg
            $is_water = $this->request->input('is_water') ? 1 : 0;
            $pathName = date('Ymd');
            $uploadFileName = 'images/'.$pathName.'/'.md5($originalName).'.'.$ext;
            if(!empty(env('ACCESS_KEY_ID')) && $this->request->input('driver') == self::DRIVER_ALIOSS){
                $res = $this->uploadAliOss($uploadFileName,$realPath,$type);
            } else {
                $res = $this->uploadToDir($uploadFileName,$realPath,$this->request->input('type'),$is_water);
            }
            if($res === false)
                return error(1002,'文件上传失败');
            else
                return success(['path'=> '/'.$uploadFileName]);
        } else {
            return error(1003,'文件未通过验证');
        }

    }

    //上传到服务器
    protected function uploadToDir($uploadFileName,$realPath,$type,$is_water=0){
//        $img = Image::make($realPath);
//        if($type == self::TYPE_AVATAR)
//            $img->resize(100,100);
//        if($is_water == 1)
//            $img->insert('./logo.png', 'bottom-right', 15, 10);
        //return $img->save(config('filesystems.disks.public.root').'/'.$uploadFileName);
        return Storage::disk('public')->put($uploadFileName, file_get_contents($realPath));
    }


    //上传到阿里云
    protected function uploadAliOss($uploadFileName,$realPath,$type){
        $this->ossService = new OssService();
        return $this->ossService->uploadFile(env('BUCKET_NAME',''), $uploadFileName, $realPath, ['ContentType' =>$type]);
    }

}