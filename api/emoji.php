<?php
$id = $_GET['faceid'];
echo file_get_contents("../emoji/$id/apng/$id.png");
//只是一个用来随机生成背景的文件，模块化 后续方便改背景
?>