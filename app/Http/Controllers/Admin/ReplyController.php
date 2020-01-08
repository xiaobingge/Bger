<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reply;
use App\Models\Rules;
use App\Models\News;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReplyController extends Controller{


    //查询公众号关注回复
    public function getReply(Request $request){
        $rule_id = $request->input('rule_id',0);
        $list = Reply::where(['rule_id'=>$rule_id])->get();
        if(!$list->isEmpty()){
            foreach($list as $k=>&$v){
                if($v->type == 3){
                    $items = News::where(['media_id'=>$v->content])->first();
                    if($items->id)
                        $v->items = \GuzzleHttp\json_decode($items->content,true);
                }
            }
        }
        return success($list);
    }

    //查询回复详情
    public function getReplyDetail(Request $request){
        $id = $request->input('id');
        if(empty($id))
            return error(1001,'参数不能为空');
        $dtl = Reply::where(['id'=>$id])->first();
        return success($dtl);
    }

    //添加编辑回复
    public function handleReply(Request $request){
        $id = $request->input('id');
        $type = $request->input('type');
        $content = $request->input('content');
        $rule_id = $request->input('rule_id') > 0 ? $request->input('rule_id') : 0;
        if(!in_array($type,[1,2,3]))
            return error(1001,'回复类型错误');
        if(empty($content))
            return error(1002,'回复内容为空');
        if(empty($id)){
            //新增回复
            switch($type){
                case 1:
                    Reply::create(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content]);
                    break;
                case 2:
                    $media_id = $this->uploadImage($content);
                    if($media_id === false)
                        return error(1002,'图片素材上传失败');
                    Reply::create(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content,'media_id'=>$media_id]);
                    break;
                case 3:
                    Reply::create(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content,'media_id'=>$content]);
                    break;

            }
        }else{
            //编辑回复
            $reply = Reply::where(['id'=>$id])->first();
            if(empty($reply->id))
                return error(1003,'参数错误');
            if($content == $reply->content && $type == $reply->type)
                return error(1004,'没有内容需要更新');
            switch($type){
                case 1:
                    Reply::where(['id'=>$id])->update(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content]);
                    break;
                case 2:
                    $media_id = $this->uploadImage($content);
                    if($media_id === false)
                        return error(1002,'图片素材上传失败');
                    Reply::where(['id'=>$id])->update(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content,'media_id'=>$media_id]);
                    break;
                case 3:
                    Reply::where(['id'=>$id])->update(['rule_id'=>$rule_id,'type'=>$type,'content'=>$content,'media_id'=>$content]);
                    break;
            }
        }
        return success();
    }

    //删除回复
    public function deleteReply(Request $request){
        $id = $request->input('id');
        if(empty($id))
            return error(1001,'参数不能为空');
        Reply::where(['id'=>$id])->delete();
        return success();

    }
    //查询规则
    public function getRules(Request $request){
        $type = $request->input('type',1);
        $name = $request->input('name');
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $obj = Rules::query();
        if(!empty($type))
            $obj->where(['type'=>$type]);
        if(!empty($name))
            $obj->where(['name'=>$name]);
        $count = $obj->count();
        $obj->orderBy('id', 'desc')->offset(($page-1)*$limit)->limit($limit);
        $rules = $obj->get();
        foreach($rules as $key=>$value){
            $reply = Reply::where(['rule_id'=>$value->id])->get();
            foreach($reply as $k=>&$v){
                if($v->type == 3){
                    $items = News::where(['media_id'=>$v->content])->first();
                    if($items->id)
                        $v->items = \GuzzleHttp\json_decode($items->content,true);
                }
            }
            $value->list = $reply;
            $value->remark = $this->getRemark($reply);
        }
        return success(['total'=>$count,'items'=>$rules]);
    }

    //删除规则
    public function deleteRule(Request $request){
        $id = $request->input('id');
        if(empty($id))
            return error(1001,'参数错误');
        Reply::where(['rule_id'=>$id])->delete();
        Rules::where(['id'=>$id])->delete();
        return success();
    }
    //添加规则
    public function handleRule(Request $request){
        $id = $request->input('id');
        $type = $request->input('type',1);
        $name = $request->input('name');
        $keyword = $request->input('keyword');
        $reply_mode = $request->input('reply_mode',1);
        $match = $request->input('match',1);
        $list = $request->input('list');
        if(empty($name) || empty($keyword))
            return error(1001,'参数不能为空');
        if(!in_array($type,[1,2]))
            return error(1002,'参数错误');
        $is_exist = Rules::where(['keyword'=>$keyword])->get();
        if(empty($id)){
            if($type == 1){
                if(!$is_exist->isEmpty())
                    return error(1003,'二维码标识已存在');
                //创建二维码
                $qr_code = $this->createQrCode($keyword);
                $rule = Rules::create(['name'=>$name,'keyword'=>$keyword,'qr_code'=>$qr_code,'reply_mode'=>$reply_mode]);
            }else{
                if(!$is_exist->isEmpty())
                    return error(1003,'关键词已存在');
                $rule = Rules::create(['name'=>$name,'type'=>$type,'keyword'=>$keyword,'match'=>$match,'reply_mode'=>$reply_mode]);
            }
            if($rule->id > 0){
                if(!empty($list)){
                    foreach($list as $key => $value){
                        $value['rule_id'] = $rule->id;
                        $this->saveReply($value);
                    }
                    $rule->remark = $this->getRemark($list);
                }
            }
            return success($rule);
        }else{
            $rule = Rules::where(['id'=>$id])->first();
            if($type == 1){
                if($keyword != $rule->keyword){
                    $qr_code = $this->createQrCode($keyword);
                    Rules::where(['id'=>$id])->update(['name'=>$name,'keyword'=>$keyword,'qr_code'=>$qr_code,'reply_mode'=>$reply_mode]);
                }else{
                    Rules::where(['id'=>$id])->update(['name'=>$name,'reply_mode'=>$reply_mode]);
                }
            }else{
                Rules::where(['id'=>$id])->update(['name'=>$name,'keyword'=>$keyword,'match'=>$match,'reply_mode'=>$reply_mode]);
            }
            if(!empty($list)){
                foreach($list as $key => $value){
                    $value['rule_id'] = $id;
                    $this->saveReply($value);
                }
                $remark = $this->getRemark($list);
            }else{
                $remark = '';
            }
            return success(['qr_code'=>!empty($qr_code) ? $qr_code : $rule->qr_code,'remark'=>$remark]);
        }
    }

    private function getRemark($reply){
        if(is_object($reply))
            $reply = $reply->toArray();
        $a = $b = $c =  0;
        foreach($reply as $k=>$v){
            if($v['type'] == 1)
                $a += 1;
            if($v['type'] == 2)
                $b += 1;
            if($v['type'] == 3)
                $c += 1;
        }
        $remark = '';
        if($a > 0)
            $remark .= $a.'个文本';
        if($b > 0)
            $remark .=  $a > 0 ? ','.$b.'个图片' : $b.'个图片';
        if($c > 0)
            $remark .=  $a > 0 || $b > 0 ? ','.$c.'个图文' : $c.'个图文';

        return $remark;
    }

    //上传图片素材
    private function uploadImage($content)
    {
        $app = Factory::officialAccount(config('wechat.official_account.default'));
        $result = $app->material->uploadImage(storage_path('app/public').$content);
        if(empty($result['media_id']))
            return false;
        else
            return $result['media_id'];
    }

    //创建二维码
    private function createQrCode($keyword,$type=0,$day=0)
    {
        $app = Factory::officialAccount(config('wechat.official_account.default'));
        if($type == 1 && $day > 0 && $day <=30 )
            $result = $app->qrcode->temporary($keyword, $day * 24 * 3600);
        else
            $result = $app->qrcode->forever($keyword);
        $url = $app->qrcode->url($result['ticket']);
        $content = file_get_contents($url); // 得到二进制图片内容
        //文件地址
        $uploadFileName = '/images/'.date('Ymd').'/'.md5($keyword).'.jpg';
        Storage::disk('public')->put($uploadFileName, $content);
        return $uploadFileName;
    }


    private function saveReply($data){
        if($data['id'] > 0){
            switch($data['type']){
                case 1:
                    Reply::where(['id'=>$data['id']])->update(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content']]);
                    break;
                case 2:
                    $media_id = $this->uploadImage($data['content']);
                    if($media_id === false)
                        return error(1002,'图片素材上传失败');
                    Reply::where(['id'=>$data['id']])->update(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content'],'media_id'=>$media_id]);
                    break;
                case 3:
                    Reply::where(['id'=>$data['id']])->update(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content'],'media_id'=>$data['content']]);
                    break;

            }
        }else{
            switch($data['type']){
                case 1:
                    Reply::create(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content']]);
                    break;
                case 2:
                    $media_id = $this->uploadImage($data['content']);
                    if($media_id === false)
                        return error(1002,'图片素材上传失败');
                    Reply::create(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content'],'media_id'=>$media_id]);
                    break;
                case 3:
                    Reply::create(['rule_id'=>$data['rule_id'],'type'=>$data['type'],'content'=>$data['content'],'media_id'=>$data['content']]);
                    break;
            }
        }

    }



}



























