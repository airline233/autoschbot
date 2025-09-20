<?php
require 'curl.php';
header('Content-Type: image');
if(!is_dir('../imgs')) mkdir('../imgs');
$file = '../imgs/'.date('Ymd').'.jpg';
if(!file_exists($file)):
    $url = 'https://cn.bing.com'.json_decode(curl('https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1'),1)['images'][0]['url'];
    file_put_contents($file,curl($url));
endif;
echo file_get_contents($file);
?>