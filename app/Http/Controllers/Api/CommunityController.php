<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Communitys;
use App\Models\Contact;
use App\Models\Tags;
use Illuminate\Http\Request;

class CommunityController extends Controller{



    public function getCommunityByLocation(Request $request){
        $lng = $request->input('lng');
        $lat = $request->input('lat');
        $squares = returnSquarePoint($lng, $lat);
        $community = Communitys::where('lat', '>' ,$squares['right-bottom']['lat'])
            ->where('lat' ,'<' ,$squares['left-top']['lat'] )
            ->where('lng','>' ,$squares['left-top']['lng'])
            ->where('lng' ,'<' ,$squares['right-bottom']['lng'])
            ->first();
        if(empty($community))
            return error(1001,'no community');
        else
            return success($community);
    }


    public function getCommunityList(Request $request){
        $list = Communitys::get();
        $data = [];
        $ids = [];
        foreach($list as $k=>$v){
            $ids[] = $v['area_id'];
            $item = [];
            $item['id'] = $v['id'];
            $item['text'] = $v['name'];
            $data[$v['area_id']]['children'][] = $item;
        }
        $area = Area::whereIn('id',$ids)->get()->toArray();
        $arr = array_column($area,'name','id');
        $res = [];
        foreach($data as $k=>$v){
            $op = [];
            $op['text'] = $arr[$k];
            $op['dot'] = false;
            $op['disabled'] = false;
            $op['children'] = $v['children'];
            $res[] = $op;
        }
        return success($res);
    }


    public function getTags(){
        return success(Tags::where('status',1)->get());
    }


    public function getContact(Request $request){

        $id = $request->input('id',0);
        $list = Contact::where('community_id',$id)->get()->toArray();
        $tag_ids = array_column($list,'tag_id');
        $tags = Tags::whereIn('id',$tag_ids)->get()->toArray();
        $tag_arr = array_column($tags,'name','id');
        $common = $vip = [];
        foreach($list as $k=>$v){
            $list[$k]['tag_name'] = $tag_arr[$v['tag_id']];
            if($v['type'] == 0){
                $common[] = $list[$k];
            }else{
                $vip[] = $list[$k];
            }
        }
        $vips = [];
        if(!empty($vip)){
            foreach($vip as $k=>$v){
                $vips[$v['phone']]['info'][] = $v;
                $vips[$v['phone']]['phone'] = $v['phone'];
            }
        }
        $vips = array_chunk($vips,2);
        return success(['list'=>$common,'vips'=>$vips]);
    }

    public function addContact(Request $request){
        $user = $request->user();
        $phone = $request->input('phone');
        $tag_ids = explode(',',$request->input('tag_ids'));
        $community_id = $request->input('com_id');
        if(empty($phone))
            return error(1001,'手机号不能为空');
        if(!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/",$phone))
            return error(1007,'手机格式不对');
        if(empty($tag_ids))
            return error(1002,'标签不能为空');
        $tag_num = Tags::whereIn('id',$tag_ids)->count();
        if($tag_num != count($tag_ids))
            return error(1008,'非法标签');
        if(empty($community_id))
            return error(1003,'请选择一个小区');
        $contact = Contact::where('phone',$phone)->where('community_id',$community_id)->get();
        foreach($contact as $k=>$v){
            if(in_array($v->tag_id,$tag_ids))
                return error(1004,'不能重复提交相同的标签');
        }
        if(count($contact) + count($tag_ids) > 3 )
            return error(1005,'一个小区内同一个手机号只能选择3个标签');
        $num = Contact::where('phone',$phone)->where('community_id' ,'>' ,0 )->groupBy('community_id')->count();
        if($num >= 5 )
            return error(1006,'同一个手机号最多只能服务5个小区');

        foreach($tag_ids as $k=>$v){
            $data = [];
            $data['uid'] = $user->id;
            $data['community_id'] = $community_id;
            $data['tag_id'] = $v;
            $data['phone'] = $phone;
            Contact::create($data);
        }
        return success();
    }









































}






























































?>