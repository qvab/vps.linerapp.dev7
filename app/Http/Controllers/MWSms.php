<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Http\Requests;
use function MIWR\vd;
use MIWR\SMS;
use Illuminate\Support\Facades\Log;

if (!defined("ROOT_MIWR")) {
  define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
}
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.account.php";
require_once ROOT_MIWR."/class.sms.php";

require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";

class MWSms extends Controller
{
  private $curl;
  private $response;

  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }

  public function getSetting(Account $obAccount)
  {
    $obSms = new SMS();
    $arResponse = $obSms->get($obAccount->id);

    /*
    $arRequest = [
      //p?login=<login>&psw=<password>&phones=<phones>&mes=<message>";
      "login" => "expertkazan",
      "psw" => "9e171423fafc54b35b65ba604b977171f976e149",
      "phones" => "89277567413",
      "mes" => "Оцените слуэбу поддержки от 4 до 10"
    ];
    $this->__requestCURL($arRequest, false, false);
    vd(iconv("cp1251", "UTF-8", $this->response));
   */
    if (!empty($arResponse)) {
      $arResponse["field_setting"] = json_decode($arResponse["field_setting"], true);
      $arJSON = [
        "pipelines" => $arResponse["field_setting"],
        "email" => $arResponse["email"],
        "success" => 1
      ];
    } else {
      $arJSON = ["success" => 0];
    }
    return json_encode($arJSON, JSON_UNESCAPED_UNICODE);
  }


  /**
   * Установка настроек аккаунта
   * @param Account $obAccount
   */
  public function setSetting(Account $obAccount)
  {

    $iAccountId = $obAccount->id;
    $obAmo = new MW_AMO_CRM;
    $obAmo->init($obAccount->subdomain, $obAccount->login, $obAccount->hash);
    $arAccount = $obAmo->getAccount("?with=custom_fields");
    $arCustom = [];
    $arCustomIds = [];
    if (!empty($arAccount["response"]["_embedded"]["custom_fields"]["leads"])) {
      $arCustom = $arAccount["response"]["_embedded"]["custom_fields"]["leads"];
      foreach ($arCustom as $arField) {
        switch ($arField["name"]) {
          case "Оценка №1":
          case "Оценка №2":
          case "Оценка №3":
          case "Оценка №4":
          case "Оценка №5":
          case "Оценка №6":
          case "Оценка №7":
          case "Оценка №8":
          case "Оценка №9":
          case "Оценка №10":
            $arCustomIds[] = [
              "id" => (int)$arField["id"],
              "values" => [
                "values" => 0
              ]
            ];
            break;
        }
      }
    }

    $obSms = new SMS();
    $arResponse = $obSms->get($obAccount->id);
    if (!empty($arResponse)) {
      // Update
      $obSms->update(
        [
          "field_setting" => $_POST["field_setting"],
          "email" => $_POST["email"]
        ], $arResponse["id"]);
    } else {
      // Insert
      $obSms->insert([
        "field_setting" => $_POST["field_setting"],
        "email" => $_POST["email"],
        "account_id" => $obAccount->id,
        "custom_fields" => json_encode($arCustomIds, JSON_UNESCAPED_UNICODE)
      ]);
    }

    $arPipelines = json_decode($_POST["field_setting"], true);
    return json_encode([
      "pipelines" => $arPipelines,
      "email" => $_POST["email"],
      "success" => 1
    ], JSON_UNESCAPED_UNICODE);
  }

  /**
   * Отправка смс через web-hook
   */
  public function getSMS()
  {

    $sPhone = "";
    $pipelineID = false;
    $statusID = false;
    $arCurStatus = false;
    $sSmsText = false;
    $sDomain = $_POST["account"]["subdomain"];

    $obAccount = new \MIWR\Account;
    $arAccount = $obAccount->get($sDomain);
    $obSms = new SMS();
    $arSmsSetting = $obSms->get($arAccount["id"]);
    $arSetting = json_decode($arSmsSetting["field_setting"], true);
    $arCustomSms = json_decode($arSmsSetting["custom_fields"], true);
    $obAmo = new MW_AMO_CRM;
    $obAmo->init($arAccount["subdomain"], $arAccount["login"], $arAccount["hash"]);


    $arLead = $obAmo->getLeadList(["id" => $_POST["leads"]["status"][0]["id"]]);
    //$arLead = $obAmo->getLeadList(["id" => 20792883]);
    if (!empty($arLead["response"]["_embedded"]["items"][0])) {
      $arLead = $arLead["response"]["_embedded"]["items"][0];
      $pipelineID = $arLead["pipeline_id"];
      $statusID = $arLead["status_id"];
      $arContact = $obAmo->getContactsList(["id" => $arLead["main_contact"]["id"]]);
      if (!empty($arContact["response"]["_embedded"]["items"][0])) {
        $arContact = $arContact["response"]["_embedded"]["items"][0]["custom_fields"];
        foreach ($arContact as $val) {
          if ($val["code"] == "PHONE") {
            $sPhone = $val["values"][0]["value"];
          }
        }
      }

      $sSmsText = $arSetting[$pipelineID]["statuses"][$statusID]["sms_text"];
      if (!empty($sSmsText) && !empty($sPhone)) {
        // Отправка sms

        $arRequest = [
          "login" => "mishaninmina", //"expertkazan",
          "psw" => "1993mnxa", //"9e171423fafc54b35b65ba604b977171f976e149",
          "phones" => $sPhone,
          "mes" => $sSmsText
        ];
        $this->__requestCURL($arRequest, false, false);

        Log::info('TEXT SMS', [
          "text" => !empty(iconv("cp1251", "UTF-8", $this->response)) ? iconv("cp1251", "UTF-8", $this->response) : false
        ]);

        $arSmsLog = $obSms->getLog($arAccount["id"], $arLead["id"]);
        if (!empty($arSmsLog)) {
          $obSms->updateLogs([
            "pipeline_id" => $pipelineID,
            "status_id" => $statusID,
            "date_update" => time(),
            "phone" => $this->phone_format($sPhone)["noplus"],
            "lead_name" => $arLead["name"],
          ], $arSmsLog["id"]);
        } else {
          $obSms->insertLogs([
            "account_id" => $arAccount["id"],
            "lead_id" => $arLead["id"],
            "pipeline_id" => $pipelineID,
            "status_id" => $statusID,
            "ratings_list" => json_encode($arCustomSms, JSON_UNESCAPED_UNICODE),
            "date_created" => time(),
            "phone" => $this->phone_format($sPhone)["noplus"],
            "lead_name" => $arLead["name"]
          ]);
        }
        return 1;
      }
    }
  }

  /**
   * Обработка крона
   */
  public function response()
  {
    $obSms = new SMS();
    $obAccount = new \MIWR\Account;
    $obAmo = new MW_AMO_CRM;

    $sPhone = $_POST["phone"];
    $arSmsLog = $obSms->getLogPhone($sPhone);

    $smsResponseText = $_POST["mes"];
    $arCustomFieldsWrite = [];



    $arAccount = $obAccount->getById($arSmsLog["account_id"]);
    $arSmsSetting = $obSms->get($arAccount["id"]);
    $arSetting = json_decode($arSmsSetting["field_setting"], true);
    $arSettingLead = $arSetting[$arSmsLog["pipeline_id"]]["statuses"][$arSmsLog["status_id"]];
    $obAmo->init($arAccount["subdomain"], $arAccount["login"], $arAccount["hash"]);


    Log::info('RESPONSE SMS', [
      "post" => !empty($_POST) ? $_POST : false
    ]);
    $arListRating = json_decode($arSmsLog["ratings_list"], true);

    foreach ($arListRating as $key => $arFieldRating) {
      if (empty($arFieldRating["values"][0]["value"])) {
        $arCustomFieldsWrite[] = [
          "id" => $arFieldRating["id"],
          "values" =>
            [
              [
                "value" => (int)$smsResponseText
              ]
            ]
        ];
        $arListRating[$key]["values"][0]["value"] = (int)$smsResponseText;
        break;
      }
    }

    if (empty($arCustomFieldsWrite)) {
      // Уже больше 10 оценок
      $obSms->updateLogs([
        "ratings_list" => json_encode($arListRating, JSON_UNESCAPED_UNICODE),
        "off" => 1
      ], $arSmsLog["id"]);
      return "max 10 rating";
    }

    $arUpdate = [
      "name" => $arSmsLog["lead_name"].time(),
      "id" => $arSmsLog["lead_id"],

      "custom_fields" => $arCustomFieldsWrite
    ];
    // Обновляем сделку
    $res = $obAmo->updateLead($arUpdate);

    // Обновляем лог
    $obSms->updateLogs([
      "ratings_list" => json_encode($arListRating, JSON_UNESCAPED_UNICODE)
    ], $arSmsLog["id"]);

    if ($smsResponseText < $arSettingLead["rating_warning"]) {
      $header = !empty($header) ? $header : "Оценка ниже {$arSettingLead["rating_warning"]}";
      $to = 'mishanin-miha@yandex.ru';
      $headers = "MIME-Version: 1.0\r\n";
      $headers .= "Content-type: text/html; charset=UTF-8\r\n";
      $headers .= "From: LinerAppWidgets <robot@linerapp.com> \r\n";
      $headers .= "Bcc: mishanin-miha@yandex.ru\r\n";
      $content_client = "linklead: https://linerappwidgets.amocrm.ru/leads/detail/{$arSmsLog["lead_id"]}<br />";
      mail($to, $header, $content_client, $headers);
    }

    // Создаем примечание в сделке
    $arParams55["add"][] = [
      "element_id" => $arSmsLog["lead_id"],
      "element_type" => 2,
      "text" => "...",
      "note_type" => 25,
      'params' => [
        "text" => "Оценка от абонента ({$sPhone}) ".$smsResponseText,
        "service" => "Ответ смс",
      ]
    ];
    $res = $obAmo->addNode($arParams55);

  }

  private function __requestCURL($data, $link = false, $bPost = true, $bAuth = false)
  {
    $link = "https://smsc.ru/sys/send.php?".http_build_query($data);
    $this->curl = curl_init();
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_USERAGENT, "amoCRM-API-client/1.0");
    curl_setopt($this->curl, CURLOPT_URL, $link);
    if (!empty($bPost)) {
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
    } else {
      curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
    }
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($this->curl, CURLOPT_HEADER, false);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    $this->response = curl_exec($this->curl);
  }

  private function phone_format($phone, $format = "", $mask = '#')
  {
    $phone = str_replace(array(")", "(", "-", " "), "", $phone);
    $iLength = strlen($phone);
    if ($iLength > 11) {
      if (substr($phone, 0, 1) == "+") {
        $phones["mask"] = "+7(".substr($phone, 2, 3).")".substr($phone, 5, 3)."-".substr($phone, 8, 2)."-".substr($phone, 10, 2);
        $phones["nomask"] = $phone;
        $phones["noplus"] = substr($phone, 1, 10);
        $phones["default"] = "8".substr($phone, 2, 10);
      } else {
        $phones["mask"] = "+7(".substr($phone, 2, 3).")".substr($phone, 5, 3)."-".substr($phone, 8, 2)."-".substr($phone, 10, 2);
        $phones["nomask"] = "+7".substr($phone, 1, 10);
        $phones["noplus"] = "7".substr($phone, 1, 10);
        $phones["default"] = substr($phone, 0, 11);
      }

    } elseif ($iLength == 11) {
      if (substr($phone, 0, 1) == 7) {
        $phones["mask"] = "+7(".substr($phone, 1, 3).")".substr($phone, 4, 3)."-".substr($phone, 7, 2)."-".substr($phone, 9, 2);
        $phones["nomask"] = "+".$phone;
        $phones["noplus"] = $phone;
        $phones["default"] = "8".substr($phone, 1, 10);
      } else {
        $phones["mask"] = "+7(".substr($phone, 1, 3).")".substr($phone, 4, 3)."-".substr($phone, 7, 2)."-".substr($phone, 9, 2);
        $phones["nomask"] = "+7".substr($phone, 1, 10);
        $phones["noplus"] = "7".substr($phone, 1, 10);
        $phones["default"] = $phone;
      }
    } elseif ($iLength < 11) {
      if (substr($phone, 0, 1) == 9) {
        $phones["mask"] = "+7(".substr($phone, 0, 3).")".substr($phone, 3, 3)."-".substr($phone, 6, 2)."-".substr($phone, 8, 2);
        $phones["nomask"] = "+".$phone;
        $phones["noplus"] = $phone;
        $phones["default"] = "8".substr($phone, 0, 10);
      }
    }
    return $phones;
  }

}