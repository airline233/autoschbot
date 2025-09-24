<?php
class datactrl {
  function sqlctrl($type, $datain = null) {
    /*直接涉及数据库的操作:insert get* set*
      表名：Ym(202410)
      表结构：
      id timestamp qquin signature content status[0未发送;1已发送(定时也算已发);5主动撤稿;7拒稿;] time
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
    $table = 'A' . date("Ym", time() - 60);
    $pdo = new PDO("sqlite:{$GLOBALS['database_path']}/" . date("Y", time() - 60) . ".db");
    $_pdo = new PDO("sqlite:{$GLOBALS['database_path']}/permanence");

    switch ($type) {
      case 'insert':
      /*
      ** 创建新投稿(done)
      ** 传入数据：[qquin,signature,content]
      */
        $stmt = $pdo->prepare("INSERT INTO $table (`qquin`, `signature`, `content`) VALUES (?, ?, ?)");
        $stmt->execute([$datain[0], $datain[1], $datain[2]]);
        $rid = $pdo->lastInsertId();
        return $rid;
      
      case 'setdenied':
      /*
      ** 拒稿(done)
      ** 传入数据：[rid,reason]
      */
        $rid = intval($datain[0]);
        $reason = $datain[1];
        $stmt = $pdo->prepare("UPDATE $table SET `status`=7 WHERE `id`=?");
        $stmt->execute([$rid]);
        $stmt = $pdo->prepare("SELECT `qquin` FROM $table WHERE `id`=?");
        $stmt->execute([$rid]);
        $qquin = $stmt->fetchColumn();
        $this->reply('private', $qquin, "对不起，您的稿件{$rid}由于“{$reason}”被拒收，请修改不适宜内容后重新投稿");
        @unlink("../tmp/$rid.jpg");
        return 1;

      case 'setundenied':
      /*
      ** 取消拒稿（done）
      ** 传入数据：[rid]
      */
        $rid = intval($datain[0]);
        $stmt = $pdo->prepare("UPDATE $table SET `status`=0 WHERE `id`=?");
        $stmt->execute([$rid]);
        $stmt = $pdo->prepare("SELECT `qquin` FROM $table WHERE `id`=?");
        $stmt->execute([$rid]);
        $qquin = $stmt->fetchColumn();
        $this->reply('private', $qquin, "您的稿件{$rid}已被重新接收，请耐心等待发送。");
        return 1;

      case 'getallids':
      /*
      ** 获取单QQ某月所有投稿（咕）
      */
        $qquin = intval($datain);
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE qquin=?");
        $stmt->execute([$qquin]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);

      case 'getunsentcontents':
      /*
      ** 获取所有未发送的内容
      ** 返回格式：array(
                   [稿件信息1],
                   [稿件信息2],
                   ...)
      */
      //输入指定RID查询
        if ($datain) {
          $rid = intval($datain);
          $month_part = floor($rid / 10000);
          $table_name = 'A' . $month_part;
          $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE id=?");
          $stmt->execute([$rid]);
        } else {
          $stmt = $pdo->prepare("SELECT * FROM $table WHERE status=0 and timestamp<=datetime(?, 'unixepoch')");
          $stmt->execute([time() - 600]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

      case 'setsent': //设置发送态 （done）
        $id = $datain[0];
        $tid = $datain[1];
        $stmt = $pdo->prepare("UPDATE $table SET `status`=1,`tid`=? WHERE `id`=?");
        $stmt->execute([$tid,$id]);
        return 1;

      case 'setcancelled': //撤稿（done）
        $stmt = $pdo->prepare("UPDATE $table SET `status`=5 WHERE `id`=? AND `qquin`=? AND `status`=0");
        $stmt->execute([round($datain[0]), intval($datain[1])]);
        return ($stmt->rowCount() == 1) ? 1 : 0;

      case 'setsign':
      /*
      ** 设置署名
      ** params = [qquin,signature]
      ** return = true/error_info
      */
        $qquin = intval($datain[0]);
        $signature = trim($datain[1]);

        if (!$this->sqlctrl('getsign', $qquin)) {
          $stmt = $_pdo->prepare("INSERT INTO `users` (`qquin`, `sign`) VALUES (?, ?)");
          $stmt->execute([$qquin, $signature]);
        } else {
          $stmt = $_pdo->prepare("UPDATE `users` SET `sign`=? WHERE `qquin`=?");
          $stmt->execute([$signature, $qquin]);
        }
        return ($stmt->rowCount() == 1) ? 1 : 0;

      case 'getsign':
      /*
      ** 获取署名
      ** params = qquin
      ** return = original_signature ?? null
      */
        $qquin = intval($datain);
        $stmt = $_pdo->prepare("SELECT `sign` FROM `users` WHERE `qquin`=?");
        $stmt->execute([$qquin]);
        return $stmt->fetchColumn();

      case 'setTime':
      /*
      ** 设置定时发稿
      ** parans = [rid,qquin,timestamp]
      ** return = true ?? errorinfo
      */
        $rid = $datain[0];
        $qquin = $datain[1];
        $setTime = $datain[2];
        if(!is_numeric($rid) || $setTime - time() > 7*24*60*60 || $setTime < time()+3600) return 'Invaild ID or time';
        $stmt = $pdo->prepare("UPDATE $table SET `setTime`=? WHERE `id`=? AND `qquin`=? AND `status` = 0");
        $stmt -> execute([$setTime,$rid, $qquin]);
        return ($stmt->rowCount() == 1) ? 1 : 0;
      
      case 'getblacklists':
        return $_pdo -> query('SELECT * FROM `users` WHERE `banned` = 1') -> fetchAll(PDO::FETCH_ASSOC);
          
      case 'setban':
        if($datain[2] == 1) 
          $datain[0] = $pdo -> query("SELECT `qquin` FROM `{$table}` WHERE `id` = {$datain[0]}") ->fetchColumn();
        return $_pdo -> exec("UPDATE `users` SET `banned` = {$datain[1]} WHERE `qquin` = {$datain[0]}");
        
      case 'query':
        return $pdo -> query("SELECT `qquin` FROM `{$table}` WHERE `id` = {$datain}") ->fetchColumn();
          
      default:
        return "undefined func";
    }
  }

  function reply($type, $qquin, $msg, $sleep=1) {
    $msg = urlencode(trim($msg));
    if($sleep === 1) $sleep = rand(5,15); 
    elseif(!$sleep) $sleep = 3;
    elseif($sleep === 0) $sleep = 1;
    $subtype = ($type == 'private') ? 'user' : 'group';
    $url = "{$GLOBALS['apiaddr']}/send_{$type}_msg?access_token={$GLOBALS['access_token']}";
    $data = "message=$msg&{$subtype}_id=$qquin";
    $command = "nohup bash -c 'sleep {$sleep} && curl -X POST -d \"$data\" \"$url\" > /dev/null 2>&1' &";
    pclose(popen($command, 'r'));
    //后台异步执行发送消息，避免阻塞线程导致莫名其妙的bug
    return true;
  }

  function sendqzone($_rid = null, $time = null) { //发空间 目前是一条稿件对应一条说说，图片单独发出
    require_once('qzone.class.php');
    $instance = new qzone($GLOBALS['apiaddr'],$GLOBALS['access_token']);
    $origin = $this->sqlctrl('getunsentcontents', $_rid);
    $_ridtxt = ($_rid) ? "该稿件已发送或已拒稿" : "暂无待发送的稿件";
    foreach($origin as $v)  $this->sqlctrl('setsent', [$v['id'],'']); //预标记已发
    if (!$origin[0]) return $_ridtxt;
    $imgs = "";
    foreach ($origin as $v) {
        $rid = $v['id'];
        if (!$rid) continue;
        $imgpath = "../tmp/$rid.jpg";
        $content = urldecode($v['content']);
        $setTime = $v['setTime'];
        $addimgs = [];
        if(strstr($content,'image')) :
            preg_match_all("/\[al_image\](.*?)\[\/al_image\]/s",$content,$addimgs);
            $addimgs = $addimgs[1];
            endif;
        if (!file_exists($imgpath))
            $this->crtimg($v);
        $imgs = $instance -> upload($imgpath,'file')."\t";
        foreach($addimgs as $img) $imgs .= $instance -> upload("../upload/".$img,'file')."\t";
        $tid = $instance -> publish($rid,1,rtrim($imgs,"\t"),$setTime);
        $this->sqlctrl('setsent', [$v['id'],$tid]); //更新Tid
        if(is_array($tid)):
            $json = json_encode($tid);
            $sendrt .= "$rid error！！！{$json}[CQ:at,qq={$GLOBALS['superadmin']}]\n";
            continue;
            endif;
        $this->reply("private", $v['qquin'], ($setTime) ? "您的稿件{$rid}已登记定时，将在".date("Y-m-d H:i:s",$setTime)."发出。\n注意：定时稿件不会在各年级群内同步" : "您的稿件{$rid}已被发出。",0);
        $sendrt .= $rid . " ";
        $rids .= $rid.",";
        $sendrt .= "动态发布成功，tid为".$tid.',';
        if(isset($setTime)) continue;
        usleep(500000);
        $groups = $GLOBALS['sync_groups'];
        $sendcontent = "[CQ:image,url={$GLOBALS['absaddr']}/tmp/{$rid}.jpg]";
        foreach ($addimgs as $img) 
            $sendcontent .= "[CQ:image,url={$GLOBALS['absaddr']}/upload/{$img}]";
        foreach($groups as $gid)
            $this->reply("group", $gid, $sendcontent,0);
    }
    foreach (explode(" ", $content) as $path)
      @unlink($path);
    return rtrim($sendrt,',');
  }

  function delqzone($_rid, $qq = null) { //删稿 传入rid和qq号（可选 便于管理员删稿）
    require_once('qzone.class.php');
    $rids = explode(',', $_rid);
    $_rt = '';
    foreach($rids as $rid) {
      if(!is_numeric($rid)) return 'Invaild ID';
      $origin = $this->sqlctrl('getunsentcontents', $rid)[0];
      $tid = $origin['tid'];
      if($origin['qquin'] != $qq && isset($qq)) return 0;
      $instance = new qzone($GLOBALS['apiaddr'],$GLOBALS['access_token']);
      $rt = $instance -> delete($tid);
      if(!$qq && $rt['code'] == 1) $this -> reply('private',$origin['qquin'],"您的稿件{$_rid}已被管理员手动删除，有疑问请发送“反馈+内容”（用一条消息发出）");
      $_rt .= $rt['code'].':'.$rt['msg'].':'.$tid."\n";
    }
    return trim($_rt,"\n");
  }

  function submit($raw, $_hide = null) {
    $rid = $this->sqlctrl('insert', $raw);
    $this->crtimg($rid);
    if(strstr(urldecode($raw[2]),'image')) :
      preg_match_all("/\[al_image\](.*?)\[\/al_image\]/s",urldecode($raw[2]),$addimgs);
      $addimgs = $addimgs[1];
      endif;
    $imgs = "";
    foreach ($addimgs as $img)
      $imgs .= "[CQ:image,url={$GLOBALS['absaddr']}/upload/{$img}]";
    $msg = "收到投稿,ID:{$rid}：[CQ:image,url={$GLOBALS['absaddr']}/tmp/{$rid}.jpg]".$imgs;
    if($_hide) exit;
    $this->reply('private', $raw[0], $msg, 0);
    foreach ($GLOBALS['supergroups'] as $gid) $this->reply("group", $gid, $msg, 600); //延迟10min发出，给发稿人反悔的机会，防止审核过快
    return $rid;
  }

  function crtimg($values) {
    if (!is_array($values)): 
      $_v = explode(',',$values);
      $values = $this->sqlctrl('getunsentcontents', $_v[0])[0];
      $values['bg'] = $_v[1];
    endif;
    $rid = $values['id'];
    $imgpath = "../tmp/{$rid}.jpg";
    @unlink($imgpath);
    $len = strlen(str_replace('al_p', '', $values['content']));
    $width = 1080;
    if ($len <= 50 * 3) {
      $width = 1080;
      $values['content'] = str_replace("al_p", "al_h1", $values['content']);
    }
    if (strpos($values['content'], "image")) {
      preg_match('/\[al_image\]([0-9]*.jpg)\[\/al_image\]/', urldecode($values['content']), $imgpaths);
      $imgsize = getimagesize("../upload/" . $imgpaths[1]);
      $width = round(max($imgsize[0], $imgsize[1]) * 0.92);
      if ($width < 1080) $width = 1080;
    }
    if ($width > 2160) $width = 2160;
    if ($width < 1080) $width = 1080;
    $values['content'] = base64_encode($values['content']);
    $url = "{$GLOBALS['absaddr']}/api/crtimg.php?content={$values['content']}&date={$values['timestamp']}&signature={$values['signature']}&qquin={$values['qquin']}&rid=$rid&width=$width&bg={$values['bg']}";
    $shell = "sudo playwright screenshot --viewport-size={$width},720 --wait-for-timeout=1000 \"{$url}\"  {$imgpath} --full-page 2>&1"; //改用playwright截图
    exec($shell, $rt, $return_var);
    if(!file_exists($imgpath)) :
      exec('sudo playwright install', $rt, $return_var);
      exec($shell, $rt, $return_var);
      file_put_contents("../tmp/{$rid}.log", $shell.implode("\n", $rt)."\nreturn_var:{$return_var}");
      endif;
    if(!file_exists($imgpath)) return "error!!!";
    return "$rid.jpg";
  }
}
?>