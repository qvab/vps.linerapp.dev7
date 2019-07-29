<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Http\Requests;
use function MIWR\vd;
use MIWR\CalcPro;

if (!defined("ROOT_MIWR")) {
  define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
}
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.account.php";
require_once ROOT_MIWR."/class.calc_pro.php";

require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";

class MWCalcPro extends Controller
{
  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }

  public function getSetting(Account $obAccount)
  {
    $obCalcPro = new CalcPro();
    $arResponse = $obCalcPro->get($obAccount->id)["fields"];
    return $arResponse; //json_encode($arResponse, JSON_UNESCAPED_UNICODE);
  }


  public function getLead(Account $obAccount)
  {
    $obCalcPro = new CalcPro();
    $arResponse = $obCalcPro->getLead($obAccount->id, $_POST["lead_id"])["fields"];
    return !empty($arResponse) ? $arResponse : "[]";
  }

  public function saveLead(Account $obAccount)
  {
    $obCalcPro = new CalcPro();
    $idLine = $obCalcPro->getLead($obAccount->id, $_POST["lead_id"])["id"];
    if (!empty($idLine)) {
      $arResponse = $obCalcPro->updateLead([
        "account_id" => $obAccount->id,
        "lead_id" => $_POST["lead_id"],
        "price" => $_POST["price"],
        "profit" => $_POST["profit"],
        "cost" => $_POST["cost"],
        "data_update" => time(),
        "fields" => $_POST["fields"]
      ], $idLine);
    } else {
      $arResponse = $obCalcPro->insertLead([
        "account_id" => $obAccount->id,
        "lead_id" => $_POST["lead_id"],
        "price" => $_POST["price"],
        "profit" => $_POST["profit"],
        "cost" => $_POST["cost"],
        "date_created" => time(),
        "fields" => $_POST["fields"]
      ]);
    }
    return !empty($_POST["fields"]) ? $_POST["fields"] : "[]";
  }


  public function setSetting(Account $obAccount)
  {
    $obCalcPro = new CalcPro();
    $arCalcPro = $obCalcPro->get($obAccount->id);
    $amo = new MW_AMO_CRM();
    $amo->init($obAccount->subdomain, $obAccount->login, $obAccount->hash);
    $arCustomFields = $amo->getAccount("?with=custom_fields");
    $bFlag = false;
    $res = false;
    if (!empty($arCustomFields["response"]["_embedded"]["custom_fields"]["leads"])) {
      $arCustomFields = $arCustomFields["response"]["_embedded"]["custom_fields"]["leads"];
      foreach ($arCustomFields as $key => $arField) {
        if ($arField["name"] == "Затраты факт") {
          $bFlag = true;
        }
        if ($arField["name"] == "Прибыль") {
          $bFlag = true;
        }
      }
    }
    if (empty($arCalcPro)) {
      $res = $obCalcPro->insert(["fields" => "[]", "account_id" => $obAccount->id]);
      if (!empty($bFlag)) {
        $amo->addFieldLead(["name" => "Прибыль", "type" => 1, "is_editable" => 0]);
        $amo->addFieldLead(["name" => "Затраты факт", "type" => 1, "is_editable" => 0]);
      }
    }
    return json_encode(["add" => $res, "field" => $bFlag], JSON_UNESCAPED_UNICODE);
  }

  public function editSetting(Account $obAccount)
  {
    $sFields = $_POST["fields"];
    $obCalcPro = new CalcPro();
    $arCalcPro = $obCalcPro->get($obAccount->id);
    if (!empty($arCalcPro)) {
      $res = $obCalcPro->update(["fields" => $sFields], false, "WHERE account_id='".$obAccount->id."'");
    } else {
      $res = $obCalcPro->insert(["fields" => $sFields, "account_id" => $obAccount->id]);
    }
    return $sFields;
  }
}