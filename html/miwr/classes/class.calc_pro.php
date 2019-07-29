<?php

namespace MIWR;
require_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/core.php";

class CalcPro extends core
{
  private $table = "calc_pro";
  private $table_lead = "calc_pro_leads";
  private $obGetList;

  public function getList($arParam = false)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], $arParam);
    return $this->obGetList = $this->__dbGet($arParam);
  }

  public function get($sSubDomain)
  {
    $arParam = $this->__options([
      "table" => $this->table
    ], [
      "limit" => "LIMIT 1",
      "where" => "WHERE account_id = '{$sSubDomain}'",
      "pagination" => false
    ]);
    return $this->__fetch($this->__dbGet($arParam));
  }

  public function getLead($account_id, $lead_id)
  {
    $arParam = $this->__options([
      "table" => $this->table_lead
    ], [
      "limit" => "LIMIT 1",
      "where" => "WHERE account_id = '{$account_id}' AND lead_id ='{$lead_id}'",
      "pagination" => false
    ]);
    return $this->__fetch($this->__dbGet($arParam));
  }


  public function fetch()
  {
    return $this->__fetch($this->obGetList);
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
  public function updateLead($set, $id = false, $sWhere = false)
  {
    $response = $this->__dbUpdate([
      "table" => $this->table_lead,
      "set" => $set,
      "where" => !empty($id) ? "WHERE id = '{$id}'" : $sWhere
    ]);
    return $response;
  }

  public function insert($arFields) {
    $this->__dbInsert([
      "table" => $this->table,
      "fields" => $arFields
    ], true);
  }

  public function insertLead($arFields) {
    $this->__dbInsert([
      "table" => $this->table_lead,
      "fields" => $arFields
    ], true);
  }


}