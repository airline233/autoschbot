<?php
class datactrl {
  function sqlctrl($type,$datain=null) {
    /*直接涉及数据库的操作:insert get* set*
      表名：Ym(202410)
      表结构：
      id timestamp qquin signature content image ifdenied ifcancelled ifsent
      其中id,timestamp,if*无需在insert时传入
      type定义：
        insert(array(date,qquin,signature,content,image));
        deny(id)
        undeny(id)
        cancel([id,qquin])
        getallids(qquin)
        getunsentcontents()
        remove:undenied
    */
    $table = 'A'.date("Ym",time()-60);
    $pdo = new PDO("sqlite:../databases/".date("Y",time()-60).".db"); //获取一分钟前对应的年份
    $_pdo = new PDO("sqlite:../databases/permanence"); //持久化数据库
    switch ($type){
      case 'insert': 
      /*
      ** 创建新投稿(done)
      ** 传入数据：[qquin,signature,content]
      */
        foreach($datain as $v) {
            if($value) $value .= ',';
            $value .= "'$v'";
        }
        $sql = "INSERT INTO $table (`qquin`, `signature`, `content`) VALUES ($value);";
        $result = $pdo -> exec($sql);
        unset($sql); unset($value);
        if($result == 1) {
            $rid = $pdo -> query("SELECT `id` FROM $table ORDER BY `id` DESC LIMIT 1") -> fetch()[0];
            return $rid;
        }
        return 'Fatal error:请联系管理人员'.implode(",",$pdo -> errorInfo());
      break;
      
      case 'setdenied': 
      /*
      ** 拒稿(done)
      ** 传入数据：[rid,reason]
      */
        $rid = $datain[0]; $reason = $datain[1];
        $pdo->exec("UPDATE $table SET `ifdenied`=1 WHERE `id`=$rid");
        $qquin = $pdo -> query("SELECT `qquin` FROM $table WHERE `id`=$rid") -> fetch()[0];
        $this -> reply('private',$qquin,"对不起，您的稿件{$rid}由于“{$reason}”被拒收，请修改不适宜内容后重新投稿");
        unset($pdo);
        @unlink("../tmp/$rid.jpg");
        return 1;
      break;
      
      case 'setundenied': 
      /* 
      ** 取消拒稿（done）
      ** 传入数据：[rid]
      */
        $rid = $datain[0];
        $pdo -> exec("UPDATE $table SET `ifdenied`=0 WHERE `id`=$rid");
        $qquin = $pdo -> query("SELECT `qquin` FROM $table WHERE `id`=$rid") -> fetch()[0];
        $this -> reply('private',$qquin,"您的稿件{$rid}已被重新接收，请耐心等待发送。");
        unset($pdo);
        return 1;
      break;
      
      case 'getallids': 
      /*
      ** 获取单QQ某月所有投稿（咕）
      */
        $pdo -> exec("SELECT {$datain['month']} WHERE qquin={$datain['month']}");
        unset($pdo);
      break;
      
      case 'getunsentcontents': 
      /* 
      ** 获取所有未发送的内容
      ** 返回格式：array(
                   [稿件信息1],
                   [稿件信息2],
                   ...)
      */
        $qry = "SELECT * FROM $table WHERE ";
        if($datain) : //输入指定RID查询
          $qry .= "id=$datain;";
          $qry = str_replace($table,"A".floor($datain/10000),$qry); //rid格式：2024100001 取“202410”
          //return $qry;
          return array($pdo -> query($qry) -> fetch());
        endif;
        $qry .= "ifdenied=0 AND ifcancelled=0 AND ifsent=0;";
        //return $qry;
        $res = $pdo -> query($qry);
        $result = array();
        while($row = $res ->fetch(PDO::FETCH_ASSOC)) $result[]=$row;
        return $result;
      break;
      
      case 'setsent': //设置发送态 （done）
        $pdo->exec("UPDATE $table SET `ifsent`=1 WHERE `id`=$datain");
        unset($pdo);
        return 1;
      break;
      
      case 'setcancelled': //撤稿（done）
        if(!is_numeric($datain[0])) return 0;
        $rts = $pdo->exec("UPDATE $table SET `ifcancelled`=1 WHERE `id`='".round($datain[0])."' AND `qquin`='{$datain[1]}' AND `ifsent`=0 AND `ifdenied`=0 AND `ifcancelled`=0;"); //防注入
        unset($pdo);
        return ($rts == 1) ? 1 : 0;
        //return $rts;
      break;
      
      case 'setsign':
      /*
      ** 设置署名
      ** params = [qquin,signature]
      ** return = true/error_info
      */
        $qquin = $datain[0];
        $signature = $datain[1];
        if(!$this -> sqlctrl('getsign',$qquin)):
          $stats = $_pdo -> exec("INSERT INTO `users` (`qquin`,`sign`) VALUES ('{$qquin}','{$signature}')");
        else:
          $stats = $_pdo -> exec("UPDATE `users` SET `sign`='{$signature}' WHERE `qquin` = '{$qquin}'");
        endif;
        return ($stats == 1) ? 1 : $stats;
      break;
      
      case 'getsign':
      /*
      ** 获取署名
      ** params = qquin
      ** return = original_signature ?? null
      */
        $qquin = $datain;
        return $_pdo -> query("SELECT `sign` FROM `users` WHERE `qquin` = {$qquin}") -> fetch()[0];
      break;
      
      case 'getconfigs': //获取所有配置信息
        $res = $_pdo -> query("SELECT * FROM `configurations` WHERE 1;");
        while($row = $res ->fetch(PDO::FETCH_ASSOC)) $result[]=$row;
        return $result;
      break;
      
      default:
        return "undefined func";
      break;
    }
  }
  
  function reply($type,$qquin,$msg) {
    $apiaddress = "http://127.0.0.1:15000"; //webhook地址
    $token = ""; //WEBHOOK的token，可空
    $msg = trim($msg);
    if($type == 'private') return curl("$apiaddress/send_private_msg?access_token=$token","message=$msg&user_id=$qquin");
    if($type == 'group') return curl("$apiaddress/send_group_msg?access_token=$token","message=$msg&group_id=$qquin");
  }
  
  function sendqzone($_rid=null) { //发空间（为保证观感，单次最高9条）
    $origin = $this -> sqlctrl('getunsentcontents',$_rid);
    $_ridtxt = ($_rid) ? "该稿件已发送或已拒稿" : "暂无待发送的稿件";
    //return $origin;
    if(!$origin[0]) return $_ridtxt;
    $arr = array();
    $arrs = array();
    foreach($origin as $v) {
      if(count($arr) == 9) {
        $arrs[] = $arr;
        unset($arr);
      }
      $arr[] = $v;
    }
    $arrs[] = $arr;
    foreach($arrs as $arr) {
      $content = "";
      foreach($arr as $v) {
        $rid = $v['id'];
        if(!$rid) continue;
        $imgpath = "../tmp/$rid.jpg";
        if(!file_exists($imgpath)) 
          $this -> crtimg($v);
        $content .= " $imgpath";
        $this -> sqlctrl('setsent',$v['id']);
        $this -> reply("private",$v['qquin'],"您的稿件{$rid}已被发出。");
        $this -> reply("group",'同步群号',"[CQ:image,url=http://127.0.0.1:15001/tmp/{$rid}.jpg]");
        @unlink("../upload/".$v['image']);
        $sendrt .= $rid." ";
      }
      $sendrt .= shell_exec("python3 qzone-next/sample.py".$content);
      shell_exec("python qzone.py ");
    }
    foreach(explode(" ",$content) as $path) 
      @unlink($path); 
    return $sendrt;
  }
  
  function submit($raw,$_hide=null) {
    $rid = $this -> sqlctrl('insert',$raw);
    $this -> crtimg($rid);
    $msg = "收到投稿,ID:{$rid}：[CQ:image,url=http://127.0.0.1:15001/tmp/{$rid}.jpg]";
    $this -> reply("group",管理群,$msg);
    if(!$_hide) $this -> reply("group",审核群,$msg);
    $this -> reply('private',$raw[0],$msg);
    return $rid;
  }
  
  function crtimg($values) {
    if(is_numeric($values)) $values = $this -> sqlctrl('getunsentcontents',$values)[0];
    //return $values;
    $rid = $values['id'];
    $imgpath = "../tmp/{$rid}.jpg";
    @unlink($imgpath);
    $len = strlen(str_replace('al_p','',$values['content']));
    if($len <= 50*3) {
      $width = 1080;
      $values['content'] = str_replace("al_p","al_h1",$values['content']);
    }
    if(strpos($values['content'],"image")) {
      preg_match('/\[al_image\]([0-9]*.jpg)\[\/al_image\]/',urldecode($values['content']),$imgpaths);
      $imgsize = getimagesize("../upload/".$imgpaths[1]);
      $width = round(max($imgsize[0],$imgsize[1])*0.92);
      if($width < 1080) $width = 1080;
    }
    if($width > 2160) $width = 2160;
    if(!$width||$width<1080) $width = 1080;
    $url = "http://127.0.0.1:15001/api/crtimg.php?content={$values['content']}&date={$values['timestamp']}&signature={$values['signature']}&qquin={$values['qquin']}&rid=$rid&width=$width";
     $shell = "wkhtmltoimage --width $width --quality 100 \"$url\" $imgpath";
     exec($shell,$rt);
     return "$rid.jpg";
  }
}