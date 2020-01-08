<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Models\News;
use Illuminate\Support\Facades\Cache;
use EasyWeChat\Kernel\Messages\Article;
class WechatController extends Controller{

    private $app;
    private $menu_key = 'wechat.menu.item';

    public function __construct()
    {
        $config = config('wechat.official_account.default');
        $this->app = Factory::officialAccount($config);
    }

    //获取菜单
    public function getMenus()
    {
        $list = Cache::get($this->menu_key);
        if(empty($list)){
            $list = $this->app->menu->list();
            Cache::put($this->menu_key,$list,60);
        }
        $data = !empty($list['menu']) ? $list['menu'] : ['button'=>[]];
        return success($data);
    }

    //自定义菜单
    public function setMenus(Request $request)
    {
        $this->app->menu->create($request->input('button'));
        Cache::forget($this->menu_key);
        return success();
    }

    //同步微信端图文素材最新的100条
    public function sysMaterial(){
        $stats = $this->app->material->stats();
        if(!empty($stats['errcode']))
            return error($stats['errcode'],$stats['errmsg']);
        $total = $stats['news_count'];
        $page_size = 20;
        $ceil = ceil($total/$page_size);
        $ceil = $ceil > 5 ? 5 :$ceil;
        for($i = 0;$i < $ceil;$i++ ){
            $list = $this->app->material->list('news',$i*$page_size, $page_size);
            if(!empty($list['errcode']))
                return error($list['errcode'],$list['errmsg']);
            foreach($list['item'] as $k=>$v){
                $item = [];
                $item['media_id'] = $v['media_id'];
                $item['content'] = json_encode($v['content']);
                $flag = News::where(['media_id'=>$v['media_id']])->get();
                if($flag->isEmpty())
                    News::insert($item);
                else
                    News::where(['media_id'=>$v['media_id']])->update($item);
            }
        }
        return success();
    }

    //获取图文永久素材最新100条
    public function selectMaterial(Request $request)
    {
        $reply = $request->input('reply');
        $list = News::limit(100)->orderBy('id','desc')->get();
        foreach($list as $k=>$v){
            $content = \GuzzleHttp\json_decode($v['content'],true);
            //发送图文消息（点击跳转到图文消息页面） 图文消息条数限制在1条以内
            if($reply == 1 && count($content['news_item']) > 1) {
                unset($list[$k]);
                continue;
            }
            $list[$k]['content'] = $content;
        }
        return success($list);
    }


    public function getMaterial(Request $request)
    {
        $media_id = $request->input('media_id');
        $items = News::where(['media_id'=>$media_id])->first();
        $items['content'] = \GuzzleHttp\json_decode($items->content,true);
        return success($items);
    }

    public function setMaterial()
    {

//        $article = new Article([
//            'title' => '早起的虫儿被鸟吃！',
//            'thumb_media_id' => '6-JpkLMz8H7cNF9UeShlsD-HS5gCxvrFqa3xP4tIdac', // 封面图片 mediaId
//            'author' => 'overtrue', // 作者
//            'show_cover' => 1, // 是否在文章内容显示封面图片
//            'digest' => '这里是文章摘要',
//            'content' => '这里是文章内容，你可以放很长的内容',
//            'source_url' => 'https://www.easywechat.com',
//            //...
//        ]);
//
//        $article2 = new Article([
//            'title' => '风太大闪到腰了',
//            'thumb_media_id' => '6-JpkLMz8H7cNF9UeShlsNPf-u1Ec7W9uDHla-kmk98', // 封面图片 mediaId
//            'author' => 'overtrue', // 作者
//            'show_cover' => 1, // 是否在文章内容显示封面图片
//            'digest' => '这里是文章摘要',
//            'content' => '这里是文章内容，你可以放很长的内容',
//            'source_url' => 'https://www.easywechat.com',
//            //...
//        ]);
//
//        $a = $this->app->material->uploadArticle($article2);
//
//        dd($a);
    }

}