<?php
function curl($url,$data=null) {
  $ch = curl_init();
  $cu[CURLOPT_URL] = $url;
  $cu[CURLOPT_HEADER] = false;
  $cu[CURLOPT_RETURNTRANSFER] = true;
  $cu[CURLOPT_FOLLOWLOCATION] = true;
  $cu[CURLOPT_POST] = true;
  $cu[CURLOPT_POSTFIELDS] = $data;
  $cu[CURLOPT_SSL_VERIFYPEER] = false;
  $cu[CURLOPT_SSL_VERIFYHOST] = false;
  $cu[CURLOPT_USERAGENT] = "curl/1.0.0";
  $cu[CURLOPT_TIMEOUT] = "5";
  curl_setopt_array($ch, $cu);
  $content = curl_exec($ch);
  curl_close($ch);
  return $content;
}
$time=time().rand(1000,9999);
$tm = '';
for($i=5;$i<=10;$i++) {
  $tm .= $time[$i];
}
$date = date("Y-m-d");
$json = file_get_contents("php://input");
$msginfo = json_decode($json,true);
$confs = json_decode(file_get_contents('config.json'),1);
foreach ($confs as $n => $v) $GLOBALS[$n] = $v;
if(!isset($msginfo['request_type'])) $msginfo['request_type'] = "msg";
if($msginfo['request_type'] == "friend") {
  sleep(rand(5,60));
  $remark = explode("回答:",$msginfo['comment'])[1];
  require 'func.php';
  $deal = new datactrl();
  $confs = json_decode(file_get_contents('config.json'),1);
  foreach ($confs as $n => $v) $GLOBALS[$n] = $v;
  $deal -> sqlctrl('setsign',[$msginfo['user_id'],$remark]);
  curl("{$GLOBALS['apiaddr']}/set_friend_add_request?access_token={$GLOBALS['access_token']}","flag={$msginfo['flag']}&approve=1&remark=$remark");
}
if($msginfo['post_type'] != 'message') exit;
$time = $tm.str_replace("-","",$msginfo['message_id']);
if(strlen($time) != 16) {
  while(strlen($time) < 16) {
    $time = $time."0";
  }
  while(strlen($time) > 16) {
    $time = str_replace($time[17],"",$time);
  }
}
if(!is_dir("cq_log/$date")) mkdir("cq_log/$date");
if(@file_get_contents("cq_log/$date/$time.log")) exit;
file_put_contents("cq_log/$date/$time.log",$json);
exec("nohup curl {$GLOBALS['absaddr']}/api/dealmsg.php --data logfile=$date/$time.log > /dev/null 2>&1 &");
//一个及其蹩脚的假消息队列，曾经是用来防止线程阻塞的（go-cqhttp时代用的，我也不知道为什么要这样写，因为懒得动我的屎山代码就保留了下来...)