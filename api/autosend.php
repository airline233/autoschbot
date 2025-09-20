<?php
require "curl.php";
require "func.php";
include 'preptable.php';
$confs = json_decode(file_get_contents('config.json'),1);
foreach ($confs as $n => $v) $GLOBALS[$n] = $v;

$deal = new datactrl();
$rt_text = date("Y-m-d H:i:s").": ".$deal -> sendqzone(); 
if(strstr($rt_text,"成功")) foreach($GLOBALS['supergroups'] as $gid) $deal -> reply("group",$gid,$rt_text);
var_dump($rt_text);

$list = scandir('../tmp');
foreach($list as $f) {
    if($f=='.'||$f=='..') continue;
    if(str_ends_with($f,'.content')) $deal -> reply('private', str_replace('.content','',$f), '请记得发送“结束投稿”，否则投稿不会被发出');
}
if(!is_dir('../imgs')) mkdir('../imgs');
$file = '../imgs/'.date('Ymd').'.jpg';
if(!file_exists($file)):
    $url = 'https://cn.bing.com'.json_decode(curl('https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1'),1)['images'][0]['url'];
    file_put_contents($file,curl($url));
endif;
//更新今日bing图片