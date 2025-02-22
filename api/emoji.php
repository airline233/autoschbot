<?php
//header();
$id = $_GET['faceid'];
echo file_get_contents("../emoji/$id/apng/$id.png");