<?php
require "curl.php";
require "func.php";
$LogJson = @file_get_contents("cq_log/".$_POST['logfile']);
if($_GET['debug']) $LogJson = @file_get_contents("cq_log/".date("Y-m-d")."/{$_GET['debug']}.log"); //DEBUG态获取原始消息
$RawMsgArr = json_decode($LogJson,true);
var_dump($RawMsgArr);
$qquin = $RawMsgArr['user_id'];
$qqname = $RawMsgArr['sender']['nickname']; //发送者QQ&昵称
$msg = $RawMsgArr['raw_message'];
$deal = new datactrl(); //初始化操作类
$configs = $deal -> sqlctrl('getconfigs');
var_dump($configs);
$superadm = ;
$groupids = ['_1234567800' => 1,'_2234567800' => 1];
if(!file_exists("../tmp")) mkdir("../tmp");
if($RawMsgArr['message_type'] == 'private') {
  $_msg = ".".$msg;
  //$_msg = explode(" ",$msg);
  if(strpos($_msg,"反馈")) :
    $deal -> reply("private",$superadm,"收到反馈($qquin)：{$msg_t}");
    $deal -> reply("private",$qquin,"反馈提交成功！");
    exit;
  elseif(strpos($_msg,"更改署名") || strpos($_msg,"设置署名")) :
    $msg_t = trim(mb_substr($_msg,5));
    $signature = $msg_t ?? "_dynamic";
    $stats = $deal -> sqlctrl("setsign",[$qquin,$signature]);
    $signature = $msg_t ?? "您的实时昵称";
    $rtx = ($stats == 1) ? "成功设置署名为{$signature}。" : "设置署名失败，请联系管理员！";
    $deal -> reply("private",$qquin,$rtx);
    exit;
  elseif(strpos($_msg,"撤稿")):
    $msg_t = trim(mb_substr($_msg,3));
    $rts = $deal -> sqlctrl("setcancelled",[$msg_t,$qquin]);
    $deal -> reply("private",$qquin,($rts == 1) ? "成功撤回ID为{$msg_t}的稿件" : "撤稿失败，该稿件已发出/被拒/撤回或输入了错误的稿件ID。");
    if($rts == 1) foreach($groupids as $grpid => $v) if($v == 1) $deal -> reply("group",str_replace("_","",$grpid),"稿件{$msg_t}已被发稿人撤回。");
    exit;
  endif;
  
  switch($msg) {
    case "投稿":
      $signature = $deal -> sqlctrl('getsign',$qquin);
      if(!$signature): 
        $deal -> reply("private",$qquin,"你需要先为你的投稿“设置署名”。（直接发送：设置署名 xxx）");
        exit;
      endif;
      if($signature == "_dynamic") $signature = $qqname;
      $deal -> reply("private",$qquin,"你现在可以开始投稿。现在你可以一次性(这不是必须遵守的)将文字和图片全部发出，发出时将会保留文字与图片的搭配顺序。\n请注意，投稿过程中机器人不会有任何回复。无论你何时想放弃投稿，都可以发送“取消投稿”以提前中止投稿流程。（已发送的稿件不支持撤回）\n请记得发送“结束投稿”以结束投稿流程！（只有结束流程投稿才会被发送）");
      $deal -> reply("private",$qquin,"你现在的署名为：$signature\n如需更改，请发送“更改署名+新的署名”");
      touch("../tmp/$qquin.content"); //Step.1 建立临时文件
      break;
      
    case "匿名投稿":
      $deal -> reply("private",$qquin,"匿名投稿，启动！\n发送“取消投稿”以取消该次投稿，发送“结束投稿”以完毕该次投稿。");
      touch("../tmp/$qquin.content");
      touch("../tmp/$qquin.anony");
      break;
      
    case "帮助":
      usleep(rand(100000,9999999));
      $deal -> reply("private",$qquin,"欢迎使用雨中校园墙Bot。\n投稿请先发送“投稿”，如需匿名请发送“匿名投稿”。\n在稿件被发送前可发送“撤稿+id”撤回投稿，如撤稿 2024100191。\n机器人暂不支持定时发稿、视频投稿，请耐心等待更新。\n如有问题可发送：反馈+问题（用一条消息发出）；");
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
      //$rid = $deal -> sqlctrl('insert',$raw);
      $rid = $deal -> submit($raw);
      $content = "已收到您的投稿，您的稿件id为：{$rid}。\n❗请务必检查投稿预览，若稿件排版有问题可发送“撤稿 {$rid}”撤回稿件重新投稿；\n若投稿内容不违反规则将会在24小时内发出。感谢您对雨中万能墙的支持\nps.发出后撤稿请在“撤稿”前加上发送“反馈”";
      if(!is_numeric($rid)) $content = $rid;
      $deal -> reply("private",$qquin,$content);
      //$deal -> reply("private",$qquin,"机器人发稿功能正在维护中，预计最迟7日中午12点前恢复正常。您的投稿已入库，维护结束后会统一发出。");
      //$deal -> sendqzone();
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
        sleep(rand(1,3));
        //if(rand(1000,9999)<5000) exit;
        $deal -> reply("private",$qquin,"欢迎投稿🎉发送“帮助”获取使用方法");
      endif;
      break;
    }
}else if($RawMsgArr['message_type'] == "group" && $groupids["_{$RawMsgArr['group_id']}"] == 1) {
  $cmd = explode(" ",trim($msg,"/"));
  switch($cmd[0]) {
    case 'send':
    case 'crtimg':
      $cmd[0] = str_replace("send","sendqzone",$cmd[0]);
      $content = eval('return $deal -> '."{$cmd[0]}({$cmd[1]});");
    break;

    case 'deny':
    case 'undeny':
      $cmd[0] = "set".str_replace("deny","denied",$cmd[0]);
      $content = $deal -> sqlctrl($cmd[0],[$cmd[1],$cmd[2]]);
    break;
    
    case 'sendmsg':
      if($RawMsgArr['user_id'] != $superadm) exit;
      $deal -> reply($cmd[1],$cmd[2],$cmd[3]);
      $content = "done.";
      exit;
    break;
  }
  if($cmd[0] == "crtimg") $content = "[CQ:image,url=http://127.0.0.1:15001/tmp/{$content}]";
  if(isset($content)) :
    $deal -> reply("group",$RawMsgArr['group_id'],$content);
    if($cmd[0] == 'crtimg') exit;
    $deal -> reply("group",1234567800,$cmd[0].":".$cmd[1].":".$cmd[2]);
    $deal -> reply("group",2234567800,$cmd[0].":".$cmd[1].":".$cmd[2]);
    var_dump($content);
  endif;
}