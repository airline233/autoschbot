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
for($i=5;$i<=10;$i++) {
  $tm .= $time[$i];
}
$date = date("Y-m-d");
$json = file_get_contents("php://input");
$msginfo = json_decode($json,true);
if($msginfo['request_type'] == "friend") {
  $remark = explode("回答:",$msginfo['comment'])[1];
  //file_put_contents("tmp",$remark);
  //$data = json_encode(array("approve" => 1,"remark" => $remark));
  require 'func.php';
  $deal = new datactrl();
  $deal -> sqlctrl('setsign',[$msginfo['user_id'],$remark]);
  curl("http://127.0.0.1:15000/set_friend_add_request?access_token=al233","flag={$msginfo['flag']}&approve=1&remark=$remark");
  //file_put_contents("tmp_",$rtc);
  //die($data);
}
if($msginfo['post_type'] != 'message') exit;
$time = $tm.str_replace("-",null,$msginfo['message_id']);
if(strlen($time) != 16) {
  while(strlen($time) < 16) {
    $time = $time."0";
  }
  while(strlen($time) > 16) {
    $time = str_replace($time[17],null,$time);
  }
}
if(!is_dir("cq_log/$date")) mkdir("cq_log/$date");
if(@file_get_contents("cq_log/$date/$time.log")) exit;
file_put_contents("cq_log/$date/$time.log",$json);

exec("nohup curl http://127.0.0.1:15001/api/dealmsg.php --data logfile=$date/$time.log > /dev/null &");
//echo json_encode(array("message" => "hi"));