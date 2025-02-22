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
            background-image: url('bingimg.php');
        }
        
        .container {
            width: 95%;
            max-width: 75%;
            margin: 8% auto;
            background-color: rgba(112,123,124,0.65);
            padding: 6%;
            padding-bottom: 1%;
            padding-top: 3%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5%;
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
            font-family: SiYuanRegular;
            opacity: 0.75;
        }
        
        .content {
            font-size: <?php echo round($width/20); ?>px;
            color: #030303;
            line-height: 1.2;
            margin-bottom: 2px;
            margin-top: 0px;
            opacity: 0.75;
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
    "<img style=\"opacity:1;width:".($width/15)."\" src='emoji.php?faceid=$1' />"],
    
      ['/\[al_image]([0-9]*?.jpg)\[\/al_image\]\[al_image\]([0-9]*?.jpg)\[\/al_image\]/is',
    "<div style='text-align: center'><img style=\"width:45%\" src=\"../upload/$1\" /><img style=\"width:45%\" src=\"../upload/$2\" /></div>"],
    
    ["/\[al_image\]([0-9]*?.jpg)\[\/al_image\]/is",
    "<img style=\"width: 95%; margin: auto;\" src='../upload/$1' />"],
    
    ["/([\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}])/u","<span class='content' style='font-family:NotoColorEmoji'>$1</span>"]
    );
    $content = $_GET['content'];
    foreach ($replaces as $ori => $aft) {
      $content = str_replace($ori,$aft,$content);
    }
    foreach ($pregs as $preg) {
      $content = preg_replace($preg[0],$preg[1],$content);
    }
    echo $content;
    ?>
      <div class="author">
    <?php
      $signature = $_GET['signature'];
      if($signature != "匿名投稿"): 
        $signature .= "({$_GET['qquin']})";
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
        echo "<p style='color:black;font-size:".($width/32)."px;margin-top: 0px; margin-bottom:0px;'>ID:{$_GET['rid']}</p>";
        echo "<center><p style=\"font-size:".($width/38)."px; opacity:0.48; color:black; margin-top:0px; margin-bottom:0px;\">哈喽 歪瑞巴蒂~(＾◇^)/我是雨花台中学全自动万能表白墙，7*24h全天为您服务，最快即刻发出(˵¯͒〰¯͒˵)还在等什么，火速将QQ2060574537推荐给你的同学们吧~</p></center>";
        //<br />另：现招收内容审核员兼推广员5位，只需每日抽出5分钟查看群消息即可<span style='font-family:NotoColorEmoji'>🤓👆🏻</span>享受劲爆内容提前看特权噢<span style='font-family:NotoColorEmoji'>👀</span><br />仅需邀请10名好友添加墙墙就可以咯₍˄·͈༝·͈˄*₎◞ ̑̑具体可私聊墙墙发送“反馈+申请审核员”
        ?>
        </div>
      </div>
    </div>
</body>
</html>