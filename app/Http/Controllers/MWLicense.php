<?php namespace App\Http\Controllers;

use App\Models\CalcField;
use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use AmoCRM\Client as AmoCRMClient;
use App\Models\Calculator;
use App\Models\Account;
use App\Models\Distribution;
use App\Models\AutoTask;
use App\Models\ResponsibleStage;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;
use MIWR\Copylead;
use MIWR\AutotaskLog;
use MIWR\License;
use function MIWR\vd;

if (!defined("ROOT_MIWR")) {
  define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
}
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.license.php";
require_once ROOT_MIWR."/class.account.php";

require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";

class MWLicense extends Controller
{
  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }

  public function get(Account $obAccount, $w_code)
  {
    $obLicense = new License();
    $arLicense = $obLicense->get($obAccount->id, $w_code);
    return json_encode([
      "status" => $arLicense["status"],
      "lifeTime" => $arLicense["time_setting"],
      "w_code" => $arLicense["w_code"],
      "request_pay" => $arLicense["request_pay"]
    ], JSON_UNESCAPED_UNICODE);
  }


  private function push($ac_id, $w_code, $header = false)
  {
    $header = !empty($header) ? $header : "Запрос тестового периода";
    $to = 'mishanin-miha@yandex.ru';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: LinerAppWidgets <robot@linerapp.com> \r\n";
    $headers .= "Bcc: mishanin-miha@yandex.ru\r\n";
    $content_client = "ID account: {$ac_id}<br />
				                   w_code: {$w_code}<br />";
    mail($to, $header, $content_client, $headers);
  }

  public function post(Account $obAccount, $w_code)
  {
    $obLicense = new License();
    $arLicense = $obLicense->get($obAccount->id, $w_code);
    return json_encode([
      "status" => $arLicense["status"],
      "lifeTime" => $arLicense["time_setting"],
      "w_code" => $arLicense["w_code"],
      "request_pay" => $arLicense["request_pay"]
    ], JSON_UNESCAPED_UNICODE);
  }

  public function add($account_id, $w_code, $phone, $bPayment)
  {
    include_once $_SERVER["DOCUMENT_ROOT"]."/linerapp/function.php";
    $obLicense = new License();
    $arLicense = $obLicense->get($account_id, $w_code);
    if (empty($arLicense)) {
      $res = requestTest();
      $lead_id = !empty($res["leads"]["add"][0]["id"]) ? $res["leads"]["add"][0]["id"]: 0;
      $obLicense->insert([
        "account_id" => $account_id,
        "w_code" => $w_code,
        "time_setting" => strtotime("now + 1 week"),
        "status" => "t",
        "date_created" => time(),
        "date_created_text" => date("d.m.Y"),
        "phone" => $phone,
        "lead_id" => $lead_id
      ]);
      if (!empty($bPayment)) {
        $this->__payment($account_id, $w_code);
      }
      $this->push($account_id, $w_code);
    }
  }

  public function payment(Account $obAccount, $w_code)
  {
    include_once $_SERVER["DOCUMENT_ROOT"]."/linerapp/app/config.php";
    $obConfig = new \Config();
    $obAmo = new MW_AMO_CRM();
    $obLicense = new License();
    $arLicense = $obLicense->get($obAccount->id, $w_code);
    $arTask = [
      0 => [
        "task_type" => 1546066,
        "responsible_user_id" => 1210237,
        "complete_till" => date('d-m-Y 23:59')
      ]
    ];
    if (!empty($arLicense["lead_id"])) {
      $arTask[0]["element_id"] = $arLicense["lead_id"];
      $arTask[0]["element_type"] = 2;
    }
    $obAmo->init($obConfig->subdomain, $obConfig->login, $obConfig->hash);
    $res = $obAmo->addTask($arTask);
    $obLicense->update(["request_pay" => 1, "request_pay_date" => time()], $arLicense["id"]);
    $this->push($obAccount->id, $w_code, "Запрос оплаты");
    return json_encode($res, JSON_UNESCAPED_UNICODE);
  }

  // Для тестово GET
  public function __payment($id_account, $w_code)
  {
    include_once $_SERVER["DOCUMENT_ROOT"]."/linerapp/app/config.php";
    $obConfig = new \Config();
    $obAmo = new MW_AMO_CRM();
    $obLicense = new License();
    $arLicense = $obLicense->get($id_account, $w_code);
    $arTask = [
      0 => [
        "task_type" => 1546066,
        "responsible_user_id" => 1210237,
        "complete_till" => date('d-m-Y 23:59')
      ]
    ];
    if (!empty($arLicense["lead_id"])) {
      $arTask[0]["element_id"] = $arLicense["lead_id"];
      $arTask[0]["element_type"] = 2;
    }
    $obAmo->init($obConfig->subdomain, $obConfig->login, $obConfig->hash);
    $res = $obAmo->addTask($arTask);
    $obLicense->update(["request_pay" => 1, "request_pay_date" => time()], $arLicense["id"]);
    $this->push($id_account, $w_code, "Запрос оплаты");
    return json_encode($res, JSON_UNESCAPED_UNICODE);
  }
}