<?php
namespace App\Services;

use OSS\OssClient;

class OssService
{

    private $AccessKeyId;
    private $AccessKeySecret;
    private $EndPoint;
    private $ossClient;

    public function __construct()
    {
        $this->AccessKeyId = env('ACCESS_KEY_ID', '');
        $this->AccessKeySecret = env('ACCESS_KEY_SECRET', '');
        $this->EndPoint = env('ENDPOINT','');
        $this->ossClient = new OssClient(
            $this->AccessKeyId,
            $this->AccessKeySecret,
            $this->EndPoint
        );
    }
    /**
     * 使用上传文件
     * @param  string bucket名称
     * @param  string 上传之后的 OSS object 名称
     * @param  string 上传文件路径
     * @return boolean 上传是否成功
     */
    public function uploadFile($bucketName, $ossKey, $filePath, $options = [])
    {
        return $this->ossClient->uploadFile($bucketName,$ossKey, $filePath,$options);
    }

    /**
     * 使用删除文件
     * @param  string bucket名称
     * @param  string 目标 OSS object 名称
     * @return boolean 删除是否成功
     */
    public function deleteObject($bucketName, $ossKey)
    {
        return $this->ossClient->deleteObject($bucketName, $ossKey);
    }

    /*
     * 获取公开文件的 URL
     */
    public function getUrl($bucketName,$ossKey)
    {
        return 'http://'.$bucketName.'.'.$this->EndPoint.'/'.$ossKey;
    }

    /*
     * 获取临时加密文件地址
     */
    public function signUrl($bucketName,$ossKey,$timeout)
    {
        return $this->ossClient->signUrl($bucketName,$ossKey,$timeout);
    }

    /*
     *处理base64图片上传
     */
    public function base64ImageUpload($bucketName,$ossKey='', $imgBase64)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/' , $imgBase64 , $res)) {
            if($ossKey == ''){
                //获取图片类型
                $ext = $res[2];
                //上传oss的图片地址
                $ossKey = 'mashu/'.date('Ymd/His').'/'.md5(time().rand(1,9999)).'.'.$ext;
            }
            // 临时文件
            $tmpfname = tempnam("/tmp/", "FOO");
            $handle = fopen($tmpfname, "w");
            if (fwrite($handle,base64_decode(str_replace($res[1],'', $imgBase64)))){
                // 上传oss
                $res =  $this->ossClient->uploadFile($bucketName, $ossKey, $tmpfname);
                fclose($handle);
                unlink($tmpfname);
                if($res === false){
                    return '';
                }else{
                    return '/'.$ossKey;
                }
            }else{
                return '';
            }
        }else{
            return '';
        }
    }

}