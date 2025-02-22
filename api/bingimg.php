<?php
require 'curl.php';
$file = '../img/'.rand(100,150).'.jpg';
if(!file_exists($file)) file_put_contents($file,curl('https://bing.img.run/rand_uhd.php'));
echo file_get_contents($file);
?>