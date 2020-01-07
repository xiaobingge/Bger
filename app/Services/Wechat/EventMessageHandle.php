<?php
/**
 * Created by PhpStorm.
 * User: E431JP
 * Date: 2019/12/20
 * Time: 13:53
 */
namespace App\Services\Wechat;

use App\Models\Reply;
use App\Models\Rules;
use \EasyWeChat\Kernel\Contracts\EventHandlerInterface;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Media;
use EasyWeChat\Factory;


class EventMessageHandler implements  EventHandlerInterface
{
    public $message;

    public function handle($payload = null)
    {
        $this->message=$payload;
        if(in_array($this->message['Event'],['subscribe','SCAN'])){
            $rule_id = 0;
            $rule = false;
            if(!empty($this->message['EventKey'])) { //二维码扫描关注
                $arr = explode('_', $this->message['EventKey']);
                $keyword = $this->message['Event'] == 'subscribe' ? $arr[1] : $this->message['EventKey'];
                $rule = Rules::where(['keyword' => $keyword])->first();
                if (!empty($rule->id))
                    $rule_id = $rule->id;
            }
            $reply = Reply::where(['rule_id' => $rule_id])->orderBy('id', 'asc')->get();
            if(!$reply->isEmpty()){
                $items = [];
                foreach($reply as $key=>$value){
                    if($value->type == 1){
                        $items[] =  new Text($value->content);
                    }elseif($value->type == 2){
                        $items[] =  new Image($value->media_id);
                    }elseif($value->type == 3){
                        $items[]  = new Media($value->media_id, 'mpnews');
                    }
                }
                if(!empty($items)){
                    $config = config('wechat.official_account.default');
                    $app = Factory::officialAccount($config);
                    if(($rule && $rule->reply_mode == 1) || empty($this->message['EventKey'])){ //全部回复
                        foreach($items as $msg ){
                            $app->customer_service->message($msg)->to($this->message['FromUserName'])->send();
                            sleep(1);
                        }
                    }else{   //随机回复一条
                        $mod = array_rand($items);
                        $app->customer_service->message($items[$mod])->to($this->message['FromUserName'])->send();
                        //素材消息不支持被动回复 走客服接口
                        //return $items[$mod];
                    }

                }
            }

        }

    }

}