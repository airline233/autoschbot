<?php
$table = "A".date("Ym");
//$test = 1;
if(date("d") == 1 && date("H") == 0 || $_GET['debug'] == 1) {//若为该月第一天0点的自动任务，建新表
    if(!file_exists("../databases/".date("Y").".db")) touch("../databases/".date("Y").".db");
    $pdo = new PDO("sqlite:../databases/".date("Y").".db");
    //$sql = 'DROP TABLE `A202410`';
    //var_dump($pdo -> exec($sql));
    $rid0 = date("Ym")."0000";
    $sql = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        qquin TEXT NOT NULL,
        signature TEXT NOT NULL,
        content TEXT NOT NULL,
        ifdenied BOOLEAN DEFAULT 0,
        ifcancelled BOOLEAN DEFAULT 0,
        ifsent BOOLEAN DEFAULT 0
      );';
      //$sql1 = 'UPDATE sqlite_sequence SET seq = '.$rid0.' WHERE name = \''.$table.'\';'; ##IT NOT WORKS
      //var_dump($sql);
      $sql1 = 'INSERT INTO '.$table.' (`id`, `qquin`, `signature`, `content`, `ifcancelled`) VALUES ('.$rid0.',\'null\',\'null\',\'null\',1);';
    var_dump($pdo->exec($sql));
    var_dump($pdo -> exec($sql1));
    unset($sql);
}
?>