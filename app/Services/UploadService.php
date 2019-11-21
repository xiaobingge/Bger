<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadService {

    public $request;
    public $ossService;

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
            $pathName = date('Ymd');
            $uploadFileName = 'images/'.$pathName.'/'.md5($originalName).'.'.$ext;
            if($this->request->input('driver') == 'oss'){
                $res = $this->uploadAliOss($uploadFileName,$realPath,$type);
            } else {
                $res = $this->uploadToDir($uploadFileName,$realPath);
            }
            if($res === false)
                return error(1002,'文件上传失败');
            else
                return success(['path'=> '/'.$uploadFileName]);
        } else {
            return error(1003,'文件未通过验证');
        }

    }

    protected function uploadToDir($uploadFileName,$realPath){
        return Storage::disk('public')->put($uploadFileName, file_get_contents($realPath));
    }


    protected function uploadAliOss($uploadFileName,$realPath,$type){
        $this->ossService = new OssService();
        return $this->ossService->uploadFile(env('BUCKET_NAME',''), $uploadFileName, $realPath, ['ContentType' =>$type]);
    }
}