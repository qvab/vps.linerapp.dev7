<?php

namespace MIWR;
require_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/core.php";

class AutotaskLog extends core
{
  private $table = "autotask_log";
  private $obGetList;

  public function getList($arParam = false)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], $arParam);
    return $this->obGetList = $this->__dbGet($arParam);
  }

  public function delete($mixSearch)
  {

    $arParam = $this->__options([
      "table" => $this->table
    ], [
      "where" => $mixSearch,
    ]);
    return $this->__dbDelete($arParam["where"], $arParam["table"]);
  }


  public function get($mixSearch)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], [
      "limit" => "LIMIT 1",
      "where" => "WHERE id = '{$mixSearch}'",
      "pagination" => false
    ]);
    return $this->__fetch($this->__dbGet($arParam));
  }

  public function fetch($res = false)
  {
    return $this->__fetch(empty($res) ? $this->obGetList : $res);
  }

  public function getCount($sWhere = "")
  {
    $this->__dbGet([
      "table" => $this->table,
      "select" => "count(id) as count",
      "limit" => "",
      "where" => $sWhere ? "WHERE ".$sWhere : ""
    ]);
    return $this->fetch()["count"];
  }

  public function getMax($sField, $sWhere = false)
  {
    if (!empty($sField)) {
      $this->__dbGet([
        "table" => $this->table,
        "select" => "max(".$sField.") as max",
        "limit" => "",
        "where" => $sWhere ? "WHERE ".$sWhere : ""
      ]);
      return $this->fetch()["max"];
    } else {
      $this->errors[$this->table]["getMax"] = '$sField is NULL';
    }
    return false;
  }

  public function getDist($sFiled = "", $sWhere = "")
  {
    return $this->__dbGet([
      "table" => $this->table,
      "select" => "DISTINCT ".$sFiled,
      "limit" => "",
      "where" => $sWhere
    ]);
  }

  public function update($set, $id = false, $sWhere = false)
  {
    $response = $this->__dbUpdate([
      "table" => $this->table,
      "set" => $set,
      "where" => !empty($id) ? "WHERE id = '{$id}'" : $sWhere
    ]);
    return $response;
  }

  public function insert($arFields) {
    $res = $this->__dbInsert([
      "table" => $this->table,
      "fields" => $arFields
    ], true);
    if ($res == true) {
      return $this->__lastElementId();
    } else {
      return $res;
    }
  }


}