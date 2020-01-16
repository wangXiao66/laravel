<?php

namespace App\Http\Middleware;
use DB;
use Illuminate\Support\Facades\Session;
use Closure;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        //判断是否是在其他浏览器登录
        $session_id=Session::getId();
        $user_name=session('user_name');
        $user=DB::table('user')->where(['user_name'=>$user_name])->first();
        if($user->session_id!=$session_id){
            return redirect('login/login')->withErrors(['您已在别处登录，下线']);//在别处已经登录  下线
        }else{
            if(time()>=$user->operation_time+60){
                return redirect('login/login')->withErrors(['您已长时间未操作，所以现在退出登录']);
            }
            //没有超过20分钟  累加时间
            DB::table('user')->where(['user_name'=>$user_name])->update(['operation_time'=>time()]);
            return $next($request);
        }

    }
}
