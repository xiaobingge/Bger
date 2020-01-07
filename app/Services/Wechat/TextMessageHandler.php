<?php
/**
 * Created by PhpStorm.
 * User: E431JP
 * Date: 2019/11/15
 * Time: 15:03
 */
namespace App\Services\Wechat;

use \EasyWeChat\Kernel\Contracts\EventHandlerInterface;
use App\Models\Rules;
use App\Models\Reply;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Media;
use EasyWeChat\Factory;

class TextMessageHandler implements  EventHandlerInterface
{
    public $message;

    public function handle($payload = null)
    {
        $this->message=$payload;
        // TODO: Implement handle() method.
        //全匹配查询
        $rule = Rules::where(['keyword'=>$this->message['Content']])->first();
        if(empty($rule->id)){
            //半匹配查询
            $rule = Rules::where('keyword','like',$this->message['Content'].'%')->first();
        }
        if(!empty($rule->id)){
            $rule_id = $rule->id;
            $reply = Reply::where(['rule_id'=>$rule_id])->orderBy('id','asc')->get();
            if(!$reply->isEmpty()){
                $items = [];
                foreach($reply as $key=>$value){
                    if($value->type == 1){
                        $items[] =  new Text($value->content);
                    }elseif($value->type == 2){
                        $items[] =  new Image($value->media_id);
                    }elseif($value->type == 3){
                        $items[] = new Media($value->media_id);
                    }
                }
                if(!empty($items)){
                    if($rule->reply_mode == 1){ //全部回复
                        $config = config('wechat.official_account.default');
                        $app = Factory::officialAccount($config);
                        foreach($items as $msg ){
                            $app->customer_service->message($msg)->to($this->message['FromUserName'])->send();
                            sleep(1);
                        }
                    }else{   //随机回复一条
                        $mod = array_rand($items);
                        return $items[$mod];
                    }

                }
            }
        }
    }

}