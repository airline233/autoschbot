<?php
$table = "A".date("Ym");
$confs = json_decode(file_get_contents('config.json'),1);
foreach ($confs as $n => $v) $GLOBALS[$n] = $v;
if(date("d") == 1 && date("H") == 0 || $_GET['debug'] == 1) {//若为该月第一天0点的自动任务，建新表
    if(!file_exists("{$GLOBALS['database_path']}/".date("Y").".db")) touch("{$GLOBALS['database_path']}/".date("Y").".db");
    $pdo = new PDO("sqlite:{$GLOBALS['database_path']}/".date("Y").".db");
    //$sql = 'DROP TABLE `A202410`';
    //var_dump($pdo -> exec($sql));
    $rid0 = date("Ym")."0000";
    $sql = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        qquin TEXT NOT NULL,
        signature TEXT NOT NULL,
        content TEXT NOT NULL,
        status NUMERIC NOT NULL DEFAULT 0,
        setTime TEXT DEFAULT NULL,
        tid TEXT DEFAULT NULL
      );';
      $sql1 = 'INSERT INTO '.$table.' (`id`, `qquin`, `signature`, `content`, `status`) VALUES ('.$rid0.',\'null\',\'null\',\'null\',1);';
    var_dump($pdo->exec($sql));
    var_dump($pdo -> exec($sql1));
    unset($sql);
    @shell_exec("rm -r ../tmp/*"); //每月清空tmp目录，上个月的投稿不被发出就是不被发出了~
}
?>