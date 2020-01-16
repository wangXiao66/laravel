<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Session;
class LoginController extends Controller
{
    /**
     * 回复文本消息
     */
    public  function responseText($msg,$xmlObj)
    {
        echo "<xml>
		  <ToUserName><![CDATA[".$xmlObj->FromUserName."]]></ToUserName>
		  <FromUserName><![CDATA[".$xmlObj->ToUserName."]]></FromUserName>
		  <CreateTime>".time()."</CreateTime>
		  <MsgType><![CDATA[text]]></MsgType>
		  <Content><![CDATA[".$msg."]]></Content>
		</xml>";die;
    }
    //微信开发者配置服务器
    public function index(Request $request){
        //微信接入
        $echostr = $request->input('echostr');
        if(!empty($echostr)){
            //判断来源是否是是微信（验证签名）
            echo $echostr;die;
        }
        $xml = file_get_contents("php://input");//接收原始的xml成json数据流
//        var_dump($xml);
        //方便处理xml=》对象
        $xmlObj = simplexml_load_string($xml);
        //判断用户是否关注
        if($xmlObj->MsgType == 'event' && $xmlObj->Event == 'subscribe'){
            $openid = (string)$xmlObj->FromUserName;//用户openid
            $EventKey = (string)$xmlObj->EventKey;//qrscene_123123
            $status = ltrim($EventKey,'qrscene_');
            if($status){
                //用户扫码登录的程序流程
                Cache::put($status,$openid,20);
                //回复文本消息
                $this->responseText('正在扫码登录中，请稍后',$xmlObj);
            }
        }
        //用户关注过，触发scan扫码事件
        if($xmlObj->MsgType == 'event' && $xmlObj->Event == 'SCAN'){
            $openid = (string)$xmlObj->FromUserName;//用户openid
            $status = (string)$xmlObj->EventKey;//qrscene_123123
            if($status){
                //用户扫码登录的程序流程
                Cache::put($status,$openid,120);
                //回复文本消息,'st
                $this->responseText('正在扫码登录中，请稍后',$xmlObj);
            }
        }
    }

    public function checkWechatLogin(Request $request){
        //检测用户扫码没有
        //用户扫码之后，把openid存入缓存
        //读取缓存里有没有数据 =》知道扫码没有？？
        $statsu =$request->input('status');
        $openid = Cache::get($statsu);
        if(!$openid){
            return json_encode(['ret'=>0,'msg'=>'未扫码']);
        }
        return json_encode(['ret'=>1,'msg'=>'扫码登录成功']);
    }


    function login(){
        $token=$this->getToken();
        $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$token";
        $str=md5(time().rand(1000,9999));
        echo $str;
        $postdata=" {\"action_name\": \"QR_LIMIT_STR_SCENE\", \"action_info\": {\"scene\": {\"scene_str\": \"".$str."\"}}}";
        $data=self::curlpost($url,$postdata);
        $data=json_decode($data,true);
        $url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$data['ticket'];
        return view('login',[
            'data'=>$data,
            'status'=>$str
        ]);
    }
    function login_do(){
        $data=request()->all();
        $check_user=DB::table('user')->where(['user_name'=>$data['user_name']])->first();
        if(!$check_user){
            return redirect('login/login')->withErrors(['账号错误']);
        }elseif($check_user->user_pwd!=$data['user_pwd']){    //密码错误
            //次数超过3次   封停账号  存入错误时间
            if($check_user->user_error_num<=0&&time()<=$check_user->time+3600){
                $min=60-floor((time()-$check_user->time)/60);
                return redirect('login/login')->withErrors(['账号已锁定,还有'.$min.'分钟']);
            }elseif(time()>$check_user->time+3600&&$check_user->user_error_num<=0){  //错误时间到了   清零次数
                DB::table('user')->where(['user_name'=>$data['user_name']])->update(['user_error_num'=>2]);
                return redirect('login/login')->withErrors(['您还有2次机会']);
            }
                //累加次数  刷新错误时间
            $error_num=$check_user->user_error_num-1;
            DB::table('user')->where(['user_name'=>$data['user_name']])->update(['user_error_num'=>$error_num,'time'=>time()]);
            return redirect('login/login')->withErrors(['密码错误，您还有'.$error_num.'次机会']);
        }
        //密码正确  判断是否是在错误时间内
        if(time()<$check_user->time+3600&&$check_user->user_error_num<=0){
            $min=60-floor((time()-$check_user->time)/60);
            return redirect('login/login')->withErrors(['账号已锁定,还有'.$min.'分钟']);
        }else{
            $sessionid=Session::getId();
            DB::table('user')->where(['user_name'=>$data['user_name']])->update(['session_id'=>$sessionid]);
            //清空错误时间 错误次清零
            session(['user_name'=>$data['user_name']]);
            DB::table('user')->where(['user_name'=>$data['user_name']])->update(['user_error_num'=>3,'time'=>null,'operation_time'=>time()]);
            echo '登录成功';
        }
    }
    function check(){
        echo '列表';die;
    }


    public static function curlget($url){
        //初始化
        $ch=curl_init();
        //设置curl
        curl_setopt($ch,CURLOPT_URL,$url);//请求路径
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //返回数据格式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        //执行
        $result=curl_exec($ch);
        //关闭
        curl_close($ch);
        return $result;
    }
    public static function curlpost($url,$postdata){
        //初始化
        $ch=curl_init();
        //设置
        curl_setopt($ch,CURLOPT_URL,$url);  //请求地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  //返回数据格式
        curl_setopt($ch,CURLOPT_POST,1);    //POST提交方式
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata);
        //访问https网站
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        //执行curl
        $result=curl_exec($ch);
        //退出
        curl_close($ch);
        return $result;
    }
    /**
     * 获取access_token令牌
     */
    public static function getToken()
    {
        //缓存里有数据 直接读缓存
        //$accees_token = "";
        $access_token = Cache::get("access_token");
        if(empty($access_token)){
            //缓存里没有数据 调用接口获取 存入缓存
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx56bc090405987ce6&secret=424c43937459db54aebe071dbf5d669f";
            //发请求
            $data = file_get_contents($url);
            $data = json_decode($data,true);
            $access_token = $data['access_token'];
            //存储2小时
            Cache::put("access_token",$access_token,7200);
        }
        return $access_token;
    }
}
