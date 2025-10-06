<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <?php 
    $width = $_GET['width'] ?? 1024;
    echo "<meta name=\"viewport\" content=\"width=$width, initial-scale=1.0\">";
    ?>
    <style>
        @font-face {
          font-family: 'SiYuanRegular';
          src: url('../fonts/SourceHanSerifCN-Medium-6.woff2') format('woff2');
        }
        @font-face {
          font-family: 'NotoColorEmoji';
          src: url('../fonts/NotoColorEmoji.woff2') format('woff2');
        }
        
        body {
            font-family: SiYuanRegular;
            margin: 0;
            padding: 2% auto;
            <?php
            if(!isset($_GET['bg'])) echo "background-image: url('bingimg.php');";
            else echo "background:".$_GET['bg'].";";
            ?>
            width: 100%;
            height: 100%;
            backdrop-filter: blur(10px);
        }
        
        .container {
            width: 95%;
            max-width: 75%;
            margin: 8% auto;
            background-color: rgba(255,255,255,0.75);/* rgba(112,123,124,0.8); */
            padding: 6%;
            padding-bottom: 1%;
            padding-top: 3%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: <?php echo round($width/40); ?>px;
        }
        
        .author {  
            width: 100%;
            display: inline-block; /* 使容器宽度仅包含其内容 */ 
            vertical-align: middle; /* 垂直对齐容器内的内容 */ 
            margin-bottom: 0px; 
        }  
  
        .text-container {  
          display: inline-block; /* 使文本容器成为行内块级元素，以便可以设置垂直对齐和边距 */  
          vertical-align: middle; /* 垂直对齐文本容器内的内容 */
          text-align: right; /* 如果需要文本右对齐 */  
          margin-left: auto;
          margin-right: 0px;
          margin-bottom: 0px;
          float: right;
        }  
  
        .content-h1 {
            font-size: 90px;
            color: #030303;
            line-height: 1.3;
            font-family: SiYuanRegular, NotoColorEmoji, sans-serif;
            opacity: 0.9;
        }
        
        .content {
            font-size: <?php echo round($width/20); ?>px;
            color: #030303;
            line-height: 1.35;
            margin-bottom: 2px;
            margin-top: 0px;
            opacity: 0.9;
            font-weight: bold;
            font-family: SiYuanRegular, NotoColorEmoji, sans-serif;
        }
      .outlined {
           -webkit-text-stroke: <?php echo round($width/800); ?>px white;
      }
    </style>
</head>
<body>
    <div class="container">
    <?php
    $replaces = array(
      "[al_p]" => "<br /><span class='content'>",
      "[/al_p]" => "</span>",
      "[al_h1]" => "<br /><span class='content-h1'>",
      "[/al_h1]" => "</span>",
      "\n" => "<br />",
      "%26" => "&"
    );
    
    $pregs = array(
      ["/\[al_face\](.*?)\[\/al_face\]/is",
    "<img style=\"opacity:1;width:".($width/15)."px\" src='emoji.php?faceid=$1' />"],
    
      ['/\[al_image]([0-9]*?.jpg)\[\/al_image\]\[al_image\]([0-9]*?.jpg)\[\/al_image\]/is',
    "<div style='text-align: center'><img style=\"width:45%\" src=\"../upload/$1\" /><img style=\"width:45%\" src=\"../upload/$2\" /></div>"],
    
      ["/\[al_image\]([0-9]*?.jpg)\[\/al_image\]/is",
    "<img style=\"width: 95%; margin: auto;\" src='../upload/$1' />"]
    
      //["/([\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}])/u","<span class='content' style='font-family:NotoColorEmoji'>$1</span>"]
    );

    // Markdown 转 HTML 的正则规则
    $markdown_pregs = array(
      // 标题
      array('/^(#{6})\s*(.*?)$/m', '<h6>$2</h6>'),
      array('/^(#{5})\s*(.*?)$/m', '<h5>$2</h5>'),
      array('/^(#{4})\s*(.*?)$/m', '<h4>$2</h4>'),
      array('/^(#{3})\s*(.*?)$/m', '<h3>$2</h3>'),
      array('/^(#{2})\s*(.*?)$/m', '<h2>$2</h2>'),
      array('/^(#{1})\s*(.*?)$/m', '<h1>$2</h1>'),
      // 粗体
      array('/\*\*(.*?)\*\*/s', '<strong>$1</strong>'),
      // 斜体
      array('/\*(.*?)\*/s', '<em>$1</em>'),
      // 链接
      array('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>'),
      // 图片
      array('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1">'),
      // 行内代码
      array('/`([^`]+)`/', '<code>$1</code>'),
      // 无序列表
      array('/^\s*[\*\-\+]\s+(.*)$/m', '<li>$1</li>'),
      array('/~~(.*?)~~/s', '<del>$1</del>'),
    );
    $content = str_replace('al_sticker','al_image', urldecode(base64_decode($_GET['content'])));

    foreach (array_merge($pregs, $markdown_pregs) as [$pattern, $replacement]) {
      $content = preg_replace($pattern, $replacement, $content);
    }

    $content = strtr($content, $replaces);

    
    echo $content;
    ?>
      <div class="author">
    <?php
      $signature = $_GET['signature'];
      if(!str_contains($signature,"匿名")): 
        $signature .= "({$_GET['qquin']})";
        $signature = preg_replace("/\[al_face\](.*?)\[\/al_face\]/is",
    "<img style=\"opacity:1;width:".($width/25)."px\" src='emoji.php?faceid=$1' />", $signature);
        $qlogo = "<img style=\"width:10%; border-radius: 35%; vertical-align: middle; float:left;\" src=\"http://q2.qlogo.cn/headimg_dl?dst_uin={$_GET['qquin']}&spec=100\"></img>";
        echo $qlogo;
        endif;
      $date = new datetime($_GET['date']);
      $date -> modify("+8 hours");
      $date = $date -> format("Y-m-d");
      if(!$date) $date = date("Y-m-d");
      ?>
        <div class="text-container">  
        <?php
        echo "<p style=\"font-size:".($width/32)."px; opacity:0.6; color:black; margin-top:0px; margin-right:0px; margin-bottom:0px;\">$signature $date</p>";
        echo "<p style='color:yellow;font-size:".($width/32)."px;margin-top: 0px; margin-bottom:0px;'>ID:{$_GET['rid']}</p>";
        echo "<center><p class='outlined' style=\"font-size:".($width/20)."px; opacity:0.88; color: #32A0A8; margin-top:0px; margin-bottom:0px;\">还！没！加！墙！墙！的！快！来！加！我！QQ2060574537</p></center>";
        ?>
        </div>
      </div>
    </div>
</body>
</html>