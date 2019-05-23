<?php header("Access-Control-Allow-Origin: *");
$sMd5 = "";
if (!empty($_REQUEST["user"])) {
  $_REQUEST["w_code"] = !empty($_REQUEST["w_code"]) ? $_REQUEST["w_code"] : "undefined";
  $_REQUEST["user"] = !empty($_REQUEST["user"]) ? $_REQUEST["user"] : "undefined";
  $sMd5 = md5($_REQUEST["user"].$_REQUEST["w_code"]."1993mnxa");
} else {
  echo 100;
}
if ($_REQUEST["view"]) {
  echo $sMd5;
} elseif ($_REQUEST["pass"] != $sMd5) {
  echo 0;
} else {
  echo $sMd5;
}