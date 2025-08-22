<?php
require "curl.php";
require "func.php";
$LogJson = @file_get_contents("cq_log/".$_POST['logfile']);
if(isset($_GET['debug'])) $LogJson = @file_get_contents("cq_log/".date("Y-m-d")."/{$_GET['debug']}.log"); //DEBUG态获取原始消息
$RawMsgArr = json_decode($LogJson,true);
var_dump($RawMsgArr);
$qquin = $RawMsgArr['user_id'];
$qqname = $RawMsgArr['sender']['nickname']; //发送者QQ&昵称
$msg = $RawMsgArr['raw_message'];
$confs = json_decode(file_get_contents('config.json'),1);
foreach ($confs as $n => $v) $GLOBALS[$n] = $v;
$deal = new datactrl(); //初始化操作类

if(!file_exists("../tmp")) mkdir("../tmp");

if($RawMsgArr['message_type'] == 'private') {
  if(strpos($msg,"反馈") === 0) :
    foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"收到反馈($qquin)：{$msg}");
    $deal -> reply("private",$qquin,"反馈提交成功！");
    exit;
  elseif(strpos($msg,"署名")  !== false && strpos($_msg,"署名") < 10) :
    $msg_t = trim(mb_substr($_msg,5));
    $signature = $msg_t ?? "_dynamic";
    $stats = $deal -> sqlctrl("setsign",[$qquin,$signature]);
    $signature = $msg_t ?? "您的实时昵称";
    $rtx = ($stats == 1) ? "成功设置署名为{$signature}。" : "设置署名失败，请联系管理员！";
    $deal -> reply("private",$qquin,$rtx);
    exit;
  elseif(strpos($msg,"撤稿") === 0):
    $msg_t = trim(mb_substr($msg,3));
    $rts = $deal -> sqlctrl("setcancelled",[$msg_t,$qquin]);
    $deal -> reply("private",$qquin,($rts == 1) ? "成功撤回ID为{$msg_t}的稿件" : "撤稿失败，该稿件已发出/被拒/撤回或输入了错误的稿件ID。");
    if($rts == 1) foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"稿件{$msg_t}已被发稿人撤回。");
    exit;
  elseif(strpos($msg,"删稿") === 0):
    $msg_t = trim(mb_substr($msg,3));
    $rts = $deal -> delqzone($msg_t,$qquin);
    $deal -> reply("private",$qquin,($rts == 1) ? "成功删除了ID为{$msg_t}的稿件（注意，同步在群内的无法撤回）" : "删稿失败，可能是输入了错误的稿件ID。");
    if($rts == 1) foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"稿件{$msg_t}已被发稿人主动删除。");
    exit;
  elseif(strpos($msg,"设置定时")===0): //设置定时 tid 时间格式(2025-07-05 23:00:00)
    $msg_t = explode(" ",$msg);
    $rid = $msg_t[1];
    $setTime = trim(strtotime($msg_t[2]." ".$msg_t[3]));
    $rt = $deal -> sqlctrl('setTime',[$rid,$qquin,$setTime]);
    $deal -> reply('private',$qquin,($rt==1) ? "设置定时成功，稿件{$rid}将在{$msg_t[2]} {$msg_t[3]}发出" : $rt);
    exit;
  endif;
  
  switch($msg) {
    case "投稿":
      $signature = $deal -> sqlctrl('getsign',$qquin) ?? "_dynamic";
      if($signature == "_dynamic") $signature = $qqname;
      touch("../tmp/$qquin.content"); //Step.1 建立临时文件
      $deal -> reply("private",$qquin,"你现在的署名为：$signature\n如需更改，请发送“更改署名+新的署名”");
      $deal -> reply("private",$qquin,"你现在可以开始投稿。\n❗投稿完毕后请务必记得发送“结束投稿”，当然也可以发送“取消投稿”",0);
      break;
      
    case "匿名投稿":
      touch("../tmp/$qquin.content");
      touch("../tmp/$qquin.anony");
      $deal -> reply("private",$qquin,"匿名投稿，启动！\n发送“取消投稿”以取消该次投稿，发送“结束投稿”以完毕该次投稿。");
      break;
      
    case "帮助":
      usleep(rand(100000,4999999));
      $deal -> reply("private",$qquin,"欢迎使用雨中校园墙Bot。\n·投稿请先发送“投稿”，如需匿名请发送“匿名投稿”，在投稿结束后可以设置定时发稿\n·开始投稿后直接发送你要投稿的内容\n·投稿结束前可发送“取消投稿”以放弃投稿\n·在稿件被发送前可发送“撤稿 稿件id”撤回投稿\n·发出后删稿请发送“删稿 稿件id”（仅能删除空间内的）\n\n·机器人暂不支持视频投稿，如有需要请联系管理员\n·联系管理员：反馈+问题（用一条消息发出）；\n\n另：面向高一长期招收内容审核员兼推广员，请发送“反馈+申请审核员”");
      break;
      
    case "结束投稿":
      if(!file_exists("../tmp/$qquin.content")): 
        $deal -> reply("private",$qquin,"请先发送“投稿”");
        break;
      endif;
      $signature = $deal -> sqlctrl('getsign',$qquin);
      if(@file_exists("../tmp/$qquin.anony")) 
        $signature = "匿名投稿";
      $content = @file_get_contents("../tmp/$qquin.content");
      if(!$content) :
        $deal -> reply('private',$qquin,'你还未发送投稿内容');
        break;
        endif;
      $raw = array($qquin,$signature,urlencode($content));
      $rid = $deal -> submit($raw,$_hide);
      if($_hide) $deal -> sqlctrl("setcancelled",[$rid,$qquin]);
      $content = "已收到您的投稿，您的稿件id为：{$rid}。\n⚠️发出后一般不支持撤稿\n❗请务必检查投稿预览，若稿件排版有问题请及时发送“撤稿 {$rid}”撤回稿件重新投稿；\n\n如需为您的稿件设置定时，请发送：“设置定时 {$rid} 2025-01-01 00:00:00”（日期和时间仅做示例 不要漏掉空格 最多可支持一小时后~七日内的定时设置）";
      if(!is_numeric($rid)) $content = $rid;
      $deal -> reply("private",$qquin,$content,0);
      if(is_numeric($rid)) {
        unlink("../tmp/$qquin.content"); @unlink("../tmp/$qquin.anony");
      }
      unset($deal);
      break;
    
    case "取消投稿":
      unlink("../tmp/$qquin.content"); @unlink("../tmp/$qquin.anony");
      $deal -> reply("private",$qquin,"已取消投稿！");
      break;
      
    default:
      if(file_exists("../tmp/$qquin.content")):
        $file = fopen("../tmp/$qquin.content","ab");
        $content = "";
        foreach($RawMsgArr['message'] as $msgs) {
          switch($msgs['type']) {
            case 'text': 
              $ctnt = str_replace("&","%26",str_replace("\n","[/al_p][al_p]",$msgs['data']['text']));
              $content .="[al_p]{$ctnt}[/al_p]";
              break;
            case 'image': 
              $filename = date('Ymd').date('His').rand(10000,99999);
              file_put_contents("../upload/$filename.jpg",curl($msgs['data']['url'],null,1));
              $content .= "[al_image]$filename.jpg[/al_image]";
              break;
            case 'face': 
              $content .= "[al_face]{$msgs['data']['id']}[/al_face]";
              break;
          }
        }
        fwrite($file,$content);
        fclose($file);
        exit;
      else:
        if(strstr($msg,'自动回复')) exit;
        $deal -> reply("private",$qquin,"欢迎投稿🎉发送“帮助”获取使用方法");
      endif;
      break;
    }
}else if($RawMsgArr['message_type'] == "group" && in_array($RawMsgArr['group_id'],$GLOBALS['supergroups']) == 1) {
  $cmd = explode(" ",trim($msg,"/"));
  switch($cmd[0]) {
    case 'send':
    case 'crtimg':
    case 'delqzone';
      $cmd[0] = str_replace("send","sendqzone",$cmd[0]);
      $content = eval('return $deal -> '."{$cmd[0]}({$cmd[1]});");
    break;

    case 'deny':
    case 'undeny':
      $cmd[0] = "set".str_replace("deny","denied",$cmd[0]);
      $content = $deal -> sqlctrl($cmd[0],[$cmd[1],$cmd[2]]);
    break;
    
    case 'sendmsg':
      if($RawMsgArr['user_id'] != $GLOBALS['superadm']) exit;
      $deal -> reply($cmd[1],$cmd[2],$cmd[3]);
      $content = "done.";
      exit;
    break;
  }
  if($cmd[0] == "crtimg") $content = "[CQ:image,url={$GLOBALS['absaddr']}/tmp/{$content}]";
  if(isset($content)) :
    $deal -> reply("group",$RawMsgArr['group_id'],$content);
    if($cmd[0] == 'crtimg') exit;
    foreach ($GLOBALS['supergroups'] as $gid)$deal -> reply("group",$gid,$cmd[0].":".$cmd[1].":".$cmd[2]);
    var_dump($content);
  endif;
}