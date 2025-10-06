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

try{
$qqinfo = curl("{$GLOBALS['apiaddr']}/get_stranger_info?access_token={$GLOBALS['access_token']}","user_id=$qquin");
$qqlevel = json_decode($qqinfo,1)['data']['qqLevel'];
if(is_numeric($qqlevel) && $qqlevel < $GLOBALS['minlevel']) :
  file_put_contents("../tmp/$qquin.ban.log",$qqinfo);
  exit; //QQ等级限制
endif;
foreach ($deal -> sqlctrl('getblacklists') as $line) if(array_search($qquin,$line) ) exit;

if($GLOBALS['maintain'] == 1 && $qquin != $GLOBALS['superadmin']) exit; //维护模式

if(!file_exists("../tmp")) mkdir("../tmp");

if($RawMsgArr['message_type'] == 'private') {
  if(str_starts_with($msg,'反馈')) :
    foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"收到反馈($qquin)：{$msg}");
    $deal -> reply("private",$qquin,"反馈提交成功！");
    exit;
  elseif(str_starts_with($msg,'设置署名') || str_starts_with($msg,'修改署名')) :
    $msg_t = trim(mb_substr($msg,4));
    $signature = $msg_t ?? "_dynamic";
    if(str_contains($signature,'CQ:face')) :
      preg_match('/id=(\d+)/',$signature,$faceid);
      $signature = "[al_face]".$faceid[1]."[/al_face]";
    endif;
    $stats = $deal -> sqlctrl("setsign",[$qquin,$signature]);
    $signature = $msg_t ?? "您的昵称({$qqname})";
    $rtx = ($stats == 1) ? "成功设置署名为{$signature}。" : "设置署名失败，请联系管理员！";
    $deal -> reply("private",$qquin,$rtx);
    exit;
  elseif(str_starts_with($msg,"撤稿") || str_starts_with($msg,"删稿")):
    $msg_t = trim(mb_substr($msg,2));
    $rid = '';
    if(is_numeric($msg_t)) {
      $rid = $msg_t;
      while(strlen($rid) < 4) $rid = '0' . $rid;
      if(strlen($rid) == 4) $rid = date("Ym") . $rid;
    }
    $his_submits = $deal -> sqlctrl("get_ones_submits",$qquin);
    if(count($his_submits) == 0) {
      $ReplyContent = "你还没有任何投稿记录，无法进行撤稿/删稿操作";
      goto reply;
    }
    usort($his_submits, function($a, $b) {
      return $b['id'] <=> $a['id'];
    });
    $rid = in_array($rid,array_column($his_submits,'id')) ? $rid : $his_submits[0]['id']; //未输入id或输入id错误时，默认取投稿人最新稿件的id

    $SubmitDetail = $his_submits[array_search($rid,array_column($his_submits,'id'))] ?? '';
    $method = $SubmitDetail['status'];
    $TimeDelta = $SubmitDetail['timestamp'] - time();
    $SleepSeconds = ($TimeDelta > 0) ? $TimeDelta : 'real0';
    switch($method) {
      case 0:
        $ReturnCode = $deal -> sqlctrl("setcancelled",[$rid,$qquin]);
        $ReplyContent = ($ReturnCode == 1) ? "成功撤回ID为{$rid}的稿件" : "出现未知问题导致ID为{$rid}的稿件撤回失败，请尝试通过“反馈”联系管理员";
        if($ReturnCode == 1) foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"稿件{$rid}已被发稿人主动撤回。",$SleepSeconds);
        break;
      case 1:
        $ReturnCode = $deal -> delqzone($rid,$qquin)['code'];
        $ReplyContent = ($ReturnCode == 1) ? "成功删除了ID为{$rid}的稿件（注意，同步在群内的无法删除）" : "删除ID为{$rid}的稿件失败，可能是输入了错误的稿件ID。";
        if($ReturnCode == 1) foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,"稿件{$rid}已被发稿人主动删除。");
        break;
      default:
        $ReplyContent = "提交失败，稿件{$rid}已被拒/撤回或输入了错误的稿件ID。";
    }
    reply:
    $deal -> reply('private',$qquin,$ReplyContent);
    exit;
  elseif(str_starts_with($msg,"设置定时")): //设置定时 tid 时间格式(2025-07-05 23:00:00)
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
      $deal -> reply("private",$qquin,"你现在的署名为：{$signature}\n如需更改，请发送“更改署名+新的署名”", 'real0');
      $deal -> reply("private",$qquin,"你现在可以开始发送投稿内容。\n❗投稿完毕后请务必记得发送“结束投稿”，如果想放弃投稿请发送“取消投稿”",0);
      break;
      
    case "匿名投稿":
      touch("../tmp/$qquin.content");
      touch("../tmp/$qquin.anony");
      $deal -> reply("private",$qquin,"匿名投稿，启动！你现在可以开始发送投稿内容\n发送“取消投稿”以取消该次投稿，发送“结束投稿”以完毕该次投稿。",0);
      break;
      
    case "帮助":
      $TmpFile = "../tmp/{$qquin}_last_request_help_timestamp";
      $LastRequestTimestamp = @file_get_contents($TmpFile);
      if($LastRequestTimestamp != null && $LastRequestTimestamp - time() < 60) exit;
      file_put_contents($TmpFile,time());
      $SleepSeconds = rand(15,60);
      $deal -> reply("private",$qquin,"欢迎使用雨中校园墙Bot。\n·投稿请先发送“投稿”，如需匿名请发送“匿名投稿”，在投稿结束后可以设置定时发稿\n·开始投稿后直接发送你要投稿的内容\n·投稿结束前可发送“取消投稿”以放弃投稿\n·你可以随时发送“撤稿 稿件id”撤回你的投稿（发出后仅能删除空间内的）\n\n·机器人暂不支持视频投稿，如有需要请联系管理员\n·联系管理员：反馈+问题（用一条消息发出）",$SleepSeconds);
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
      $rid = $deal -> submit($raw,[$qqlevel]);
      $simplified_rid = str_replace('0','',substr($rid,-4));
      $content = "已收到您的投稿，您的稿件id为：{$rid}。\n❗请务必检查投稿预览，若稿件排版有问题请及时发送“撤稿 {$simplified_rid}”撤回稿件重新投稿；\n\n如需为您的稿件设置定时，发送：“设置定时 {$simplified_rid} 2025-01-01 00:00:00”\n此处日期和时间仅做示例 请不要漏掉空格 定时仅支持一小时后~七日内\n\n注意：如果你的设定的时间前没有被审核通过，不会被发出";
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

    case "实名认证":
      if(file_exists("../tmp/$qquin.auth")) :
        $deal -> reply("private",$qquin,"你已经在认证流程中，请完成认证后再尝试");
        break;
      endif;
      file_put_contents("../tmp/$qquin.auth",json_encode(['step'=>0,'data'=>[],'starttime'=>time()]));
      $deal -> reply("private",$qquin,"现在开始实名认证流程\n你随时可以发送“取消认证”来终止本次流程",'real0');
      $deal -> reply("private",$qquin,"声明：数据不是找学校老师拿的，也不会轻易给学校老师，墙墙永远是站在你们这边（只是因为之前有人说开小号造谣的问题）\n相信咱的隐私保障，骂学校什么的还是畅所欲言的，而且就连审核员也看不见你们的信息，放心就好~",'real0');
      $deal -> reply("private",$qquin,"Step 0:请选择校区（本部/岱山/国高）（本部国高与岱山国高均发送“国高”）",'real0');
      break;
    
    case "取消认证":
      if(!file_exists("../tmp/$qquin.auth")) :
        $deal -> reply("private",$qquin,"你并不在认证流程中");
        break;
      endif;
      
      $auth = json_decode(file_get_contents("../tmp/$qquin.auth"),1);
      if($auth['step'] > 2) {
        $deal -> reply("private",$qquin,"您当前已进行到第{$auth['step']}步，无法放弃认证");
      }else {
        unlink("../tmp/$qquin.auth");
        $deal -> reply("private",$qquin,"已放弃本次认证");
      }
      break;
    
    default:
      if(file_exists("../tmp/$qquin.content")): //投稿交互流程
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
              if($msgs['data']['sub_type'] == 0) $content .= "[al_image]$filename.jpg[/al_image]";
              else $content .= "[al_sticker]$filename.jpg[/al_sticker]";
              break;
            case 'face': 
              $content .= "[al_face]{$msgs['data']['id']}[/al_face]";
              break;
          }
        }
        fwrite($file,$content);
        fclose($file);
        exit;
      
      elseif(file_exists("../tmp/$qquin.auth")): //实名认证交互流程
        $auth = json_decode(file_get_contents("../tmp/$qquin.auth"),1);
        switch($auth['step']) {
          case 0:
            $campus = trim($msg);
            if(!in_array($campus,["本部","岱山","国高"])) {
              $deal -> reply("private",$qquin,"校区输入错误，请重新输入",'real0');
              break;
            }
            $resolv = array("本部"=>"YZ","岱山"=>"DS","国高"=>"IS");
            $auth['data']['campus'] = $resolv[$campus];
            $auth['step'] = 1;
            file_put_contents("../tmp/$qquin.auth",json_encode($auth));
            $deal -> reply("private",$qquin,"已记录校区：{$campus}\nStep 1:请发送你的真实姓名",'real0');
            break;
          
          case 1: 
            $realname = trim($msg);
            if(mb_strlen($realname) < 2 || mb_strlen($realname) > 5 || preg_match('/[^\x{4e00}-\x{9fa5}]/u',$realname) || str_contains($realname,'管理员') || str_contains($realname,'雨中') || in_array($realname,["本部","岱山","国高"])) {
              $deal -> reply("private",$qquin,"姓名有误？请重新输入（生僻字请通过“反馈”联系管理员处理）",'real0');
              break;
            }
            $auth['data']['realname'] = $realname;
            $auth['step'] = 2;
            file_put_contents("../tmp/$qquin.auth",json_encode($auth));
            if($auth['data']['campus'] == "IS") $deal -> reply("private",$qquin,"已记录姓名：{$realname}\nStep 2:请发送你的行政班（如：G10A）",'real0');
            else $deal -> reply("private",$qquin,"已记录姓名：{$realname}\nStep 2:请发送你的准考证号(智学网号 202x0xxx)",'real0');
            break;
          
          case 2:
            if($auth['data']['campus'] == "IS") :
              $class = strtoupper(trim($msg));
              if(!preg_match('/^[A-Z]\d{2}[A-Z]$/',$class)) {
                $deal -> reply("private",$qquin,"国高班级格式错误，请重新输入。如确认没问题请联系管理员处理\n若校区选择错误，请发送“取消认证”后重新开始实名流程",'real0');
                break;
              }
              unlink("../tmp/$qquin.auth");
              $deal -> reply("private",$qquin,"你提交的的认证信息为：\n校区：{$auth['data']['campus']}\n姓名：{$auth['data']['realname']}\n班级：{$msg}\n已提交身份审核，请耐心等待审核结果，在审核通过前你仍不能投稿（特殊情况请通过“反馈”联系管理员）",'real0');
              $auth['data']['class'] = trim($msg);
              $randtime = rand(360,36000);
              $auth['data']['begintime'] = time() + $randtime; //6min-10h
              $deal -> sqlctrl('setauth',[$qquin,$auth['data']]);
              $deal -> reply("private",$qquin,"你的身份审核已通过！现在你可以正常投稿",$randtime);
              break;
            endif;
            $examid = trim($msg);
            if(!is_numeric($examid) || strlen($examid) != 9) {
              $deal -> reply("private",$qquin,"准考证号({$examid})格式疑似错误?请重新输入。如确定没问题请联系管理员处理",'real0');
              break;
            }
            $auth['data']['examid'] = $examid;
            $auth['step'] = 3;
            file_put_contents("../tmp/$qquin.auth",json_encode($auth));
            $realdata = json_decode(file_get_contents('data.json'),1)[$auth['data']['campus'].$auth['data']['examid']] ?? null;
            if(!$realdata) {
              unlink("../tmp/$qquin.auth");
              $deal -> reply("private",$qquin,"未查询到你的准考证号信息，请确认准考证号是否无误，若再次尝试仍无法解决可尝试通过“反馈”联系管理员处理",'real0');
              break;
            }
            if($realdata['RealName'] != $auth['data']['realname']) {
              unlink("../tmp/$qquin.auth");
              $deal -> reply("private",$qquin,"你输入的姓名({$auth['data']['realname']})与准考证号({$auth['data']['examid']})不符，若确认无误请尝试再次提交实名认证，若仍无法通过通过“反馈”联系管理员处理",'real0');
              break;
            }
            $realphone = $realdata['Mobile'];
            $first3 = substr($realphone,0,3);
            $last4 = substr($realphone,-4);
            $hidephone = $first3."****".$last4;
            $phones = [$hidephone,$first3."****".(intval($last4)+4),$first3."****".(intval($last4)-2),$first3."****".(intval($last4)+2)
            ];
            shuffle($phones);
            while($phones[3] == $hidephone) shuffle($phones);
            unset($phones[3]);
            $auth['data']['phones'] = $phones;
            $auth['data']['realinfo'] = $realdata;
            file_put_contents("../tmp/$qquin.auth",json_encode($auth));
            $deal -> reply("private",$qquin,"已记录准考证号：{$examid}\nStep 3:请在以下选项中选择你家长或你本人的手机号\nA.{$phones[0]}\nB.{$phones[1]}\nC.{$phones[2]}\n只需要发送选项字母");
            break;

          case 3:
            $opt = strtoupper(trim($msg));
            if(!in_array($opt,["A","B","C","D"])) {
              $deal -> reply("private",$qquin,"选项输入错误，请重新输入",'real0');
              break;
            }
            $resolv = ['A'=>0,'B'=>1,'C'=>2,'D'=>3];
            $opt = $resolv[$opt];
            $selphone = $auth['data']['phones'][$opt];
            $first3 = substr($auth['data']['realinfo']['Mobile'],0,3);
            $last4 = substr($auth['data']['realinfo']['Mobile'],-4);
            $truemobile = $first3."****".$last4;
            if($selphone == $truemobile) {
              $realavatar = $auth['data']['realinfo']['Avatar'];
              if(!$realavatar) {
                $deal -> reply("private",$qquin,"你提交的认证信息为：\n姓名：{$auth['data']['realinfo']['RealName']}\n手机号：{$auth['data']['selphone']}\n准考证号：{$auth['data']['realinfo']['UserName']}\n已提交身份审核，请耐心等待结果（在此期间你依然无法使用投稿功能）",'real0');
                $costtime = time() - $auth['starttime'];
                $randtime = rand(360+$costtime*30,$costtime*60+36000);
                if($randtime > 25000) {
                  $deal -> reply("private",$qquin,"对不起，您未通过身份审核，请确认您使用本人身份进行认证。如确认无误，可再次提交实名认证（失败次数过多会被封禁权限，请知晓）",$randtime);
                  unlink("../tmp/$qquin.auth");
                  break;
                }
                $auth['data']['begintime'] = time() + $randtime;
                $deal -> sqlctrl('setauth',[$qquin,$auth['data']['realinfo']]);
                $deal -> reply("private",$qquin,"你的身份审核已通过！现在你可以正常投稿",$randtime);
                unlink("../tmp/$qquin.auth");
                break;
              }
              $infos = json_decode(file_get_contents('data.json'),1);
              $id = $auth['data']['examid']-1;
              for($fakeavatar1 = '';$fakeavatar1 == null; $id--)$fakeavatar1 = $infos[$auth['data']['campus'].$id]['Avatar'] ?? null;
              $id = $auth['data']['examid']+1;
              for($fakeavatar2 ='';$fakeavatar2 == null; $id++)$fakeavatar2 = $infos[$auth['data']['campus'].$id]['Avatar'] ?? null;
              $avatars = [$realavatar,$fakeavatar1,$fakeavatar2];
              shuffle($avatars);
              $auth['data']['selphone'] = $selphone;
              $auth['step'] = 4;
              $auth['data']['avatars'] = $avatars;
              file_put_contents("../tmp/$qquin.auth",json_encode($auth));
              $avatarurl = "https://mgr.yhtzx.net/api/media/file/download?path=";
              $deal -> reply("private",$qquin,"已记录手机号：{$selphone}\nStep 4:请从下列头像中选择你父母或你本人微信正在或曾经使用过的那一个\nA.[CQ:image,url={$avatarurl}{$avatars[0]}]\nB.[CQ:image,url={$avatarurl}{$avatars[1]}]\nC.[CQ:image,url={$avatarurl}{$avatars[2]}]\n只需要发送选项字母",'real0');
            }else {
              unlink("../tmp/$qquin.auth");
              $deal -> reply("private",$qquin,"你选择的手机号与系统记录不符，认证失败。你将无法再次进行身份认证，如有疑问请通过“反馈”联系管理员处理",'real0');
              $deal -> sqlctrl('setban',[$qquin,1]);
              break;
            }
            break;

          case 4:
            $opt = strtoupper(trim($msg));
            if(!in_array($opt,["A","B","C"])) {
              $deal -> reply("private",$qquin,"选项输入错误，请重新输入",'real0');
              break;
            }
            $resolv = ['A'=>0,'B'=>1,'C'=>2,'D'=>3];
            $selavatar = $auth['data']['avatars'][$resolv[$opt]];
            if($selavatar == $auth['data']['realinfo']['Avatar']) {
              $avatarurl = "https://mgr.yhtzx.net/api/media/file/download?path=";
              $deal -> reply("private",$qquin,"已选择头像（只作为审核依据）：[CQ:image,url={$avatarurl}{$selavatar}]\n你提交的认证信息为：\n姓名：{$auth['data']['realinfo']['RealName']}\n手机号：{$auth['data']['selphone']}\n准考证号：{$auth['data']['examid']}\n已提交身份审核，请耐心等待结果（在此期间你依然无法使用投稿功能）",'real0');
              $costtime = time() - $auth['starttime'];
              $randtime = rand(360+$costtime*30,$costtime*60+36000);
              if($randtime > 25000) {
                $deal -> reply("private",$qquin,"对不起，您未通过身份审核，请确认您使用本人身份进行认证。如确认无误，可再次提交实名认证（失败次数过多会被封禁权限，请知晓）",$randtime);
                unlink("../tmp/$qquin.auth");
                break;
              }
              $auth['data']['begintime'] = time() + $randtime;
              $deal -> sqlctrl('setauth',[$qquin,$auth['data']['realinfo']]);
              $deal -> reply("private",$qquin,"你的身份审核已通过！现在你可以正常投稿",$randtime);
              unlink("../tmp/$qquin.auth");
              break;
            }else {
              unlink("../tmp/$qquin.auth");
              $deal -> reply("private",$qquin,"你选择的头像与系统记录不符，认证失败。你将无法再次进行身份认证，如有疑问请通过“反馈”联系管理员处理",'real0');
              $deal -> sqlctrl('setban',[$qquin,1]);
              break;
            }
        }
      else:
        if(str_contains($msg,'自动回复')) exit; //防止循环
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
      if(!isset($cmd[1])) $cmd[1] = "";
      $content = eval('return $deal -> '."{$cmd[0]}('{$cmd[1]}');");
    break;

    case 'deny':
    case 'undeny':
    case 'ban':
      $cmd[0] = "set".str_replace("deny","denied",$cmd[0]);
      if(!isset($cmd[3])) $cmd[3] = '';
      $content = $deal -> sqlctrl($cmd[0],[$cmd[1],$cmd[2],$cmd[3]]);
    break;
    
    case 'sendmsg':
    case 'reply':
      if($RawMsgArr['user_id'] != $GLOBALS['superadmin']) exit;
      $deal -> reply($cmd[1],$cmd[2],$cmd[3]);
      $content = "done.";
    break;
    
    case 'query':
      if($RawMsgArr['user_id'] != $GLOBALS['superadmin']) exit;
      $content = $deal -> sqlctrl($cmd[0],$cmd[1]);
      if($cmd[0] == 'query') $content = "[CQ:contact,type=qq,id={$content}]";
    break;
    
    case 'getblacklists':
    case 'getallids':
      if(!isset($cmd[1])) $cmd[1] = '';
      $content = json_encode($deal -> sqlctrl($cmd[0],$cmd[1]));
    break;

    case 'AI_Review':
      $review_result = $deal -> AI_Review($cmd[1]);
      if($review_result['code'] != 200) $content = $review_result['msg'];
      else {
        $review_result_arr = json_decode($review_result['data'],1);
        if(is_array($review_result_arr)) {
          $content = "ID:{$cmd[1]},AI意见:";
          $content .= $review_result_arr['is_compliant'] == 1 ? '通过' : '拒绝';
          $content .= "\n理由：{$review_result_arr['reason']}";
          $content .= $review_result_arr['violation_category'] ?? "\n违规分类：{$review_result_arr['violation_category']}";
          $content .= "\n\n置信度：{$review_result_arr['confidence_score']}";
        }else {
          $content = json_encode($review_result);
        }
      }
      break;
  }
  if($cmd[0] == "crtimg") $content = "[CQ:image,url={$GLOBALS['absaddr']}/tmp/{$content}]";
  if(isset($content)) :
    $deal -> reply("group",$RawMsgArr['group_id'],$content);
    if($cmd[0] == 'crtimg') exit;
    //foreach ($GLOBALS['supergroups'] as $gid)$deal -> reply("group",$gid,$cmd[0].":".$cmd[1].":".$cmd[2]);
    var_dump($content);
  endif;
}}catch(Throwable $t) {
    $msg = "错误信息: " . $e->getMessage() . "\n";
    $msg .= "文件: " . $e->getFile() . "\n";
    $msg .= "行号: " . $e->getLine();
    $deal -> reply("private",$GLOBALS['superadmin'],$msg);
}
?>