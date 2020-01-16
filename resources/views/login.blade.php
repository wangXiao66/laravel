<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<p>
@if(!empty($errors->first()))
    {{$errors->first()}}
@endif
</p>
<form action="{{url('login/login_do')}}" method="post">
    账号<input type="text" name="user_name" id="user_name"><br>
    密码<input type="password" name="user_pwd" id="user_pwd"><br>
    <img src="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={{$data['ticket']}}" width="150px" height="150px">
    <input type="submit" value="登录" id="button">
</form>
</body>
</html>
<script src="/jquery.js"></script>
<script>
    var status = "{{$status}}";
    //js轮询
    var t = setInterval("check();",2000);
    function check(){
        $.ajax({
            url:"{{url('login/checkWechatLogin')}}",
            dataType:"json",
            data:{status:status},
            success:function (res) {
                //返回提示
                if(res.ret == 1){
                    //关闭定时器
                    clearInterval(t);
                    //扫码登录成功
                    alert(res.msg);
                    location.href = "{{url('login/check')}}";
                }
            }
        })
    }

</script>