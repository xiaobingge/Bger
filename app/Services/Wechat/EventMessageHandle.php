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
use EasyWeChat\Factory;

class EventMessageHandler implements  EventHandlerInterface
{
    public $message;

    public function handle($payload = null)
    {
        $this->message=$payload;
        // TODO: Implement handle() method.
        $config = config('wechat.official_account.default');
        $app = Factory::officialAccount($config);
        if(in_array($this->message['Event'],['subscribe','SCAN'])){
            $rule_id = 0;
            if(!empty($this->message['EventKey'])){ //二维码扫描关注
                $arr = explode('_',$this->message['EventKey']);
                $keyword = $this->message['Event'] == 'subscribe' ? $arr[1] : $this->message['EventKey'];
                $rule = Rules::where(['keyword'=>$keyword])->first();
                if(!empty($rule->id))
                    $rule_id = $rule->id;
            }
            $reply = Reply::where(['rule_id'=>$rule_id])->orderBy('id','asc')->get();
            if(!$reply->isEmpty()){
                foreach($reply as $key=>$value){
                    $msg = false;
                    if($value->type == 1){
                        $msg =  new Text($value->content);
                    }elseif($value->type == 2){
                        $msg =  new Image($value->media_id);
                    }
                    if($msg){
                        $app->customer_service->message($msg)->to($this->message['FromUserName'])->send();
                        sleep(1);
                    }

                }
            }
        }

    }

}