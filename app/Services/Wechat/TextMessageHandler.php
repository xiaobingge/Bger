<?php
/**
 * Created by PhpStorm.
 * User: E431JP
 * Date: 2019/11/15
 * Time: 15:03
 */
namespace App\Services\Wechat;

use \EasyWeChat\Kernel\Contracts\EventHandlerInterface;

class TextMessageHandler implements  EventHandlerInterface
{
    public $message;

    public function handle($payload = null)
    {
        $this->message=$payload;
        // TODO: Implement handle() method.
        if(preg_match('/^[0-9A-Z]{8}+$/',$this->message['Content'])){
            return '';
        }
        return '';
    }

}