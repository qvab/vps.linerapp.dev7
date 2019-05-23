<?php

namespace UQLinerApp;
require_once $_SERVER["DOCUMENT_ROOT"]."/modules/class/core.php";

class LANotDelFiles extends LACore
{


  private $dbTable = 'not_del_files';
  private $dbFields = ["id", "account_id", "setting_del_files", "setting_add_files"];

  function __construct()
  {


  }

  public function getConf()
  {

    $sSql = "SELECT files.setting_del_files, files.setting_add_files, files.setting_not_del_note FROM account AS ac LEFT JOIN not_del_files AS files ON ac.id = files.account_id
WHERE files.account_id IS NOT NULL AND ac.subdomain = '{$_REQUEST["subdomain"]}' LIMIT 1";
    $rResponse = $this->dbQuery($sSql);
    $arResponse = $rResponse->fetch_assoc();
    $arResponse["setting_del_files"] = json_decode($arResponse["setting_del_files"], true);
    return json_encode(["users" => $arResponse["setting_del_files"], "notAddFiles" => $arResponse["setting_add_files"], "notDelNote" => $arResponse["setting_not_del_note"]]);
  }

  public function setConf()
  {

    $arParams = $_REQUEST["users"];
    $iNotAddFile = $_REQUEST["notAddFiles"];
    $iNotDelNote = $_REQUEST["notDelNote"];

    $sSql = "UPDATE account AS ac LEFT JOIN not_del_files AS files ON ac.id = files.account_id
SET files.setting_del_files = '{$arParams}', files.setting_add_files = '{$iNotAddFile}', files.setting_not_del_note = '{$iNotDelNote}'
WHERE files.account_id IS NOT NULL AND ac.subdomain = '{$_REQUEST["subdomain"]}'";
    $rResponse = $this->dbQuery($sSql);

  }


}