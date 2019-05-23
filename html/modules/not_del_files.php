<?php
namespace MIWR;
header("Access-Control-Allow-Origin: *");
require_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/class.not_del_files.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/class.account.php";
$obNotDekFiles = new NotDelFiles();
$obAccount = new Account();
$arAccount = $obAccount->get($_REQUEST["subdomain"]);
if (!empty($arAccount)) {
  if (isset($_GET["set"])) {
    return $obNotDekFiles->insert([
      "account_id" => $arAccount["id"],
      "setting_add_files" => $_REQUEST["notAddFiles"],
      "setting_not_del_note" => $_REQUEST["notDelNote"],
      "setting_del_files" => $_REQUEST["users"]
    ]);
  } elseif (isset($_GET["get"])) {
    $arSetting = $obNotDekFiles->get($arAccount["id"]);
    $arSetting["setting_del_files"] = json_decode($arSetting["setting_del_files"], true);
    echo json_encode([
      "users" => $arSetting["setting_del_files"],
      "notAddFiles" => $arSetting["setting_add_files"],
      "notDelNote" => $arSetting["setting_not_del_note"]
    ], JSON_UNESCAPED_UNICODE);
  }
}