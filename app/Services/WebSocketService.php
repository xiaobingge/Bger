<?php

namespace App\Services;


use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface{

    public function __construct()
    {

    }

    public function onOpen(Server $server, Request $request)
    {
        // TODO: Implement onOpen() method.
        Log::info('WebSocket 连接建立');
        $server->push($request->fd,'Welcome to WebSocket Server built on LaravelS');
    }


    public function onMessage(Server $server, Frame $frame)
    {
        // TODO: Implement onMessage() method.
        // 调用 push 方法向客户端推送数据
        $server->push($frame->fd, 'This is a message sent from WebSocket Server at ' . date('Y-m-d H:i:s'));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        // TODO: Implement onClose() method.
        Log::info('WebSocket 连接关闭');
    }


}