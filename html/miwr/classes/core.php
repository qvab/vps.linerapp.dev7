<?php

namespace MIWR;
define("PATH_ROOT", $_SERVER["DOCUMENT_ROOT"]);
function vd($data, $bPrintr = false)
{
  if (!empty($bPrintr)) {
    ?><pre><?php print_r($data); ?></pre><?php
  } else {
    ?>
    <pre><?php var_dump($data); ?></pre><?php
  }
}


class core
{
  protected $pagination;
  protected $dbResource;
  protected $__debug;
  protected $errors = [];
  private $resourceConnect;


  protected function __dbQuery($sStr) {
    return mysqli_query($this->resourceConnect, $sStr);
  }

  protected function __dbError() {
    return mysqli_error($this->resourceConnect);
  }

  protected function nextElement($dbRes)
  {
    return mysqli_fetch_assoc($dbRes);
  }

  protected function __lastElementId() {
    return mysqli_insert_id($this->resourceConnect);
  }

  protected function __dbDisconnect(){
    mysqli_close($this->resourceConnect);
  }

  protected function __dbConnect()
  {
    $bd_host = "localhost";
    $bd_user = "u349676_laravel";
    $bd_password = "1993mnxa";
    $bd_base = "u349676_laravel";
    $this->resourceConnect = mysqli_connect($bd_host, $bd_user, $bd_password, $bd_base);
  }

  protected function __validateData($data = [], $type = false)
  {
    if (empty($type)) {
      if (empty($data["value"])) {
        $this->errors[$data["key"]] = "no data";
        return false;
      } else {
        return true;
      }
    }
    return true;
  }

  protected function responseError()
  {
    return "Error: ".json_encode($this->errors, JSON_UNESCAPED_UNICODE);
  }

  protected function __options($arDefault, $arOptions, $type = false)
  {
    if (!empty($arOptions)) {
      return array_merge($arDefault, $arOptions);
    }
    return $arDefault;
  }


  protected function __dbGet($arParam)
  {
    if (!empty($arParam["pagination"])) {
      $arParam["pagination"] = $this->__options([
        "partSize" => 5,
        "pageSize" => 10,
        "pageVar" => !empty($_GET["page"]) ? $_GET["page"] : 1
      ], $arParam["pagination"]);
    }

    $arParam = $this->__options([
      "limit" => "LIMIT 0, 30",
      "select" => "*",
      "where" => "",
      "order" => "",
    ], $arParam);
    $this->__validateData(["key" => "table", "value" => $arParam["table"]]);
    if (empty($this->errors)) {
      $this->__dbConnect();

      if (!empty($arParam["pagination"])) {
        $this->__pagination($arParam);
        $arParam["limit"] = "LIMIT {$this->pagination["currentPosition"]}, {$arParam["pagination"]["pageSize"]}";
      }
      $sSql = "SELECT {$arParam["select"]} FROM {$arParam["table"]} {$arParam["where"]} {$arParam["order"]} {$arParam["limit"]}";
      $rResponse = $this->__dbQuery($sSql) or die("mysql error: (SQL: ".$sSql.")<br />".$this->__dbError($this->resourceConnect));
      return $rResponse;
    }
    return $this->responseError();
  }

  protected function __fetch($ob = false)
  {
    if (empty($ob)) {
      return $this->nextElement($this->dbResource);
    } else {
      return $this->nextElement($ob);
    }
  }

  protected function __pagination($arParam)
  {
    $sSql = "SELECT count(*) as allElements FROM {$arParam["table"]} {$arParam["where"]}";
    $rResponse = $this->__dbQuery($sSql);
    $arResponse = $this->nextElement($rResponse);
    $this->pagination = [
      "elementsAll" => $arResponse["allElements"],
      "pagesAll" => ceil($arResponse["allElements"] / $arParam["pagination"]["pageSize"]),
      "pageVar" => $arParam["pagination"]["pageVar"],
      "currentPosition" => (($arParam["pagination"]["pageVar"] - 1) * $arParam["pagination"]["pageSize"]),
      "param" => $arParam
    ];
  }


  public function pagination($data = null)
  {
    $data["prefix"] = !$data["prefix"] ? "?page=1" : $data["prefix"];
    $data["query"] = !$data["query"] ? $_GET["page"] : $data["query"];
    $data["query"] = !$data["query"] ? 1 : $data["query"];
    $data["tpl-active"] = !$data["tpl-active"] ? '<li class="paginate_button active"><span>%d</span></li>' : $data["tpl-active"];
    $data["tpl"] = !$data["tpl"] ? '<li class="paginate_button"><a href="?page=%d">%d</a></li>' : $data["tpl"];
    $data["tpl-next"] = !$data["tpl-next"] ? '<li class="paginate_button next"><a href="?page=%d">Вперед</a></li>' : $data["tpl-next"];
    $data["tpl-prev"] = !$data["tpl-prev"] ? '<li class="paginate_button previous"><a href="?page=%d">Назад</a></li>' : $data["tpl-prev"];
    $page = $data["query"];
    $maxEnd = $this->pagination["pagesAll"];
    $part = $this->pagination["param"]["pagination"]["partSize"];
    $balance = ($part * 2);
    if ($maxEnd < ($balance + 1)) {
      $start = 1;
      $end = $maxEnd;
    } elseif ($page > $part && ($maxEnd - $page) < $part) {
      $start = ($maxEnd - $balance);
      $end = $maxEnd;
    } elseif ($page > $part) {
      $start = ($page - $part);
      $end = ($balance + $start);
    } else {
      $start = 1;
      $end = ($balance + 1);
    }
    $html = '<ul class="pagination" style="margin: 0;">';
    for ($i = $start; $i <= $end; $i++) {
      if ($i == $start && (($page - 1) > 0)) {
        $html .= sprintf("".$data["tpl-prev"]."", ($page - 1));
      }
      if ($i == $page) {
        $html .= sprintf("".$data["tpl-active"]."", $i);
      } else {
        $html .= sprintf("".$data["tpl"]."", $i, $i);
      }
      if ($i == $end && (($page - 1) < $end)) {
        $html .= sprintf("".$data["tpl-next"]."", ($page + 1));
      }
    }
    $html .= '</ul>';
    return $html;
  }


  protected function __dbUpdate($arParam)
  {
    $arParam = $this->__options([
      "where" => "",
      "order" => "",
    ], $arParam);
    $this->__validateData(["key" => "table", "value" => $arParam["table"]]);
    $this->__validateData(["key" => "set", "value" => $arParam["set"]]);
    if (empty($this->errors)) {

      foreach ($arParam["set"] as $key => $val) {
        $arParam["set"][$key] = $key." = '".$val."'";
      }
      $arParam["set"] = implode(",", $arParam["set"]);
      $this->__dbConnect();
      $sSql = "UPDATE {$arParam["table"]} SET {$arParam["set"]} {$arParam["where"]}";
      $rResponse = $this->__dbQuery($sSql);
      $rSqlError = $this->__dbError();
      if (!empty($rSqlError)) {
        $this->errors["core"]["__dbUpdate"]["sql"] = $rSqlError;
        return $this->errors;
      } else {
        return $rResponse;
      }
    }
    return false;
  }

  protected function __dbInsert($arParam, $bReplace = false)
  {
    $arParam = $this->__options([
      "where" => "",
      "order" => "",
    ], $arParam);
    $this->__validateData(["key" => "table", "value" => $arParam["table"]]);
    $this->__validateData(["key" => "fields", "value" => $arParam["fields"]]);
    if (empty($this->errors)) {
      $arSet = [];
      $arValues = [];
      $arGroupString = [];
      if (!empty($arParam["fields"]["values"])) {
        foreach ($arParam["fields"]["set"] as $key) {
          $arSet[] = $key;
        }
        foreach ($arParam["fields"]["values"] as $idGroup => $arGroup) {
          $arGroupValues[$idGroup] = [];
          foreach ($arGroup as $itemValue) {
            $arGroupValues[$idGroup][] = "'".$itemValue."'";
          }
          $arGroupString[$idGroup] = "(".implode(",", $arGroupValues[$idGroup]).")";
        }
        $sSet = "(".implode(",", $arSet).")";
        $sValues = implode(",", $arGroupString);
      } else {
        foreach ($arParam["fields"] as $key => $val) {
          $arSet[] = $key;
          $arValues[] = "'".$val."'";
        }
        $sSet = "(".implode(",", $arSet).")";
        $sValues = "(".implode(",", $arValues).")";
      }
      $this->__dbConnect();
      if (empty($bReplace)) {
        $sSql = "INSERT INTO {$arParam["table"]} {$sSet} VALUES {$sValues}";
      } else {
        $sSql = "REPLACE INTO {$arParam["table"]} {$sSet} VALUES {$sValues}";
      }
      $rResponse = $this->__dbQuery($sSql);
      $rSqlError = $this->__dbError();
      if (!empty($rSqlError)) {
        $this->errors["core"]["__dbInsert"]["sql"] = $rSqlError;
        return $this->errors;
      } else {
        return $rResponse;
      }
    }
    return $this->errors;
  }

  protected function __sql($sSQL)
  {
    $this->__dbConnect();
    $rResponse = $this->__dbQuery($sSQL);
    $rSqlError = $this->__dbError();
    if (!empty($rSqlError)) {
      return $rSqlError;
    } else {
      return $rResponse;
    }
  }

  protected function __dbDelete($sWhere, $sTable)
  {
    $sSQL = "DELETE FROM {$sTable} WHERE {$sWhere}";
    return $this->__sql($sSQL);
  }
}