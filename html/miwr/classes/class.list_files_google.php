<?php

namespace MIWR;
require_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/core.php";

class ListFilesGoogle extends core
{
  private $table = "list_files_google";
  private $obGetList;

  public function getList($arParam = false)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], $arParam);
    return $this->obGetList = $this->__dbGet($arParam);
  }

  public function get($mixSearch)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], [
      "limit" => "LIMIT 1",
      "where" => "WHERE google_id_file = '{$mixSearch}'",
      "pagination" => false
    ]);
    return $this->__fetch($this->__dbGet($arParam));
  }

  public function delete($mixSearch)
  {

    if (gettype($mixSearch) == "array") {
      $sWhere = "id IN (".implode(",", $mixSearch).")";
    } else {
      $sWhere = "id = '{$mixSearch}'";
    }
    $arParam = $this->__options([
      "table" => $this->table
    ], [
      "where" => $sWhere,
    ]);
    return $this->__dbDelete($arParam["where"], $arParam["table"]);
  }



  public function fetch($res = false)
  {
    return $this->__fetch(!empty($res) ? $res : $this->obGetList);
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

  public function getDist($sFiled = "")
  {
    $this->__dbGet([
      "table" => $this->table,
      "select" => "DISTINCT ".$sFiled,
      "limit" => "",
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
    return $this->__dbInsert([
      "table" => $this->table,
      "fields" => $arFields
    ], true);
  }


}