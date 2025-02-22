<?php
function curl($url,$data=null,$ua=null) {
  $ch = curl_init();
  $cu[CURLOPT_URL] = $url;
  $cu[CURLOPT_HEADER] = false;
  $cu[CURLOPT_RETURNTRANSFER] = true;
  $cu[CURLOPT_FOLLOWLOCATION] = true;
  if($data) {
    $cu[CURLOPT_POST] = true;
    $cu[CURLOPT_POSTFIELDS] = $data;
  }
  $cu[CURLOPT_SSL_VERIFYPEER] = false;
  $cu[CURLOPT_SSL_VERIFYHOST] = false;
  $cu[CURLOPT_USERAGENT] = "curl/1.0.0";
  if($ua) $cu[CURLOPT_USERAGENT] = "Mozilla/5.0 (Linux; Android 10; MI 8 Lite Build/QKQ1.190910.002) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.101 Mobile Safari/537.36";
  $cu[CURLOPT_TIMEOUT] = "5";
  curl_setopt_array($ch, $cu);
  $content = curl_exec($ch);
  curl_close($ch);
  return $content;
}
?>