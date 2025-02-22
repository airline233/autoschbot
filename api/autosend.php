<?php
require "curl.php";
require "func.php";
include 'preptable.php';
$groupids = array('_1234567800' => 1,'_2234567800' => 1);
$deal = new datactrl();
$rt_text = date("Y-m-d H:i:s").": ".$deal -> sendqzone(); 
if(strstr($rt_text,"成功")) foreach ($groupids as $grpid => $v) if($v) $deal -> reply('group',str_replace("_","",$grpid),$rt_text);
if(date('H') == 6 || date('H') == 22 || $_GET['debug'] == 1): 
  $deal -> reply("group",'1234567800',"1");
  /*$rs = json_decode(curl('https://zj.v.api.aa1.cn/api/weibo-rs/'),1);
  $rsl = "[al_h1]今日热搜：[/al_h1]";
  $count = 1;
  foreach($rs['data'] as  $v) {
    if($count>18) continue;
    $rsl .= "[al_p]$count. {$v['title']}[/al_p]";
    $count++;
  }
  $deal -> crtimg([
  'id' => '00000000',
  'content' => $rsl,
  'qquin' => '2060574537',
  'signature' => '做一个年轻人向往的校园墙',
  'timestamp' => date('Y-m-d')
  ]);
  //echo shell_exec("python3 qzone-next/sample.py ../tmp/00000000.jpg");
  $deal -> reply('group',' ','今日热搜来咯~还没加墙墙的记得加一下哇[CQ:image,url=http://127.0.0.1:15001/tmp/00000000.jpg]');*/
  //热搜接口被墙
endif;
var_dump($rt_text);
for($i=1;$i<6;$i++) file_put_contents('../img/'.rand(100,150).'.jpg',curl('https://bing.img.run/rand_uhd.php')); //更新5张图片