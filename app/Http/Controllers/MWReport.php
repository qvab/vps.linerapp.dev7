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
use MIWR\Report;
use MIWR\ListFiles;

use function MIWR\vd;

include $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");

class MWReport extends Controller
{

  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }


  public function download(Account $obAccount, $user)
  {

    include_once ROOT_MIWR."/class.report.php";
    require_once ROOT_MIWR."/class.list_files.php";
    $obReport = new Report();
    $obFiles = new ListFiles();
    $iCurrentUser = $user;
    $amo = new MW_AMO_CRM();
    $amo->init($obAccount->subdomain, $obAccount->login, $obAccount->hash);
    $arAccount = $amo->getAccount("?with=users");
    $arPipelines = $amo->getAccount("?with=pipelines")["response"]["_embedded"]["pipelines"];
    $arRules = $arAccount["response"]["_embedded"]["users"][$iCurrentUser]["rights"];
    $bFlagInit = true;
    $bFlagResp = false;
    if ($arRules["lead_view"] == "D") {
      $bFlagInit = false;
      return json_encode(["error" => "У данного пользователя нету доступа к сделкам"], JSON_UNESCAPED_UNICODE); // Выход из приложения
    } elseif ($arRules["lead_view"] == "M") {
      $bFlagResp = true;
    }

    $arAllStatuses = [];
    $arListGetStatuses = [];
    foreach ($arPipelines as $arPipeline) {
      foreach ($arPipeline["statuses"] as $key => $val) {
        if ($val["id"] != 142 && $val["id"] != 143) {
          $arAllStatuses[$val["id"]] = $val["name"];
        }
      }
    }
    if (!empty($arRules["by_status"]["leads"])) {
      $arRulesLeads = $arRules["by_status"]["leads"];
      foreach ($arRulesLeads as $arRulesForLead) {
        foreach ($arRulesForLead as $id => $arRulesForStatus) {
          if (($id != 142 && $id != 143) && $arRulesForStatus["view"] == "D") {
            unset($arAllStatuses[$id]);
          }
        }
      }
    }
    $arPipelines = json_decode($obReport->get($obAccount->id)["params"], true);
    if (empty($arPipelines)) {
      $bFlagInit = false;
      return "Ошибка: Не выбраны этапы в настройках виджета";
    }

    foreach ($arPipelines as $arStatuses) {
      if (!empty($arStatuses["statuses"])) {
        foreach ($arStatuses["statuses"] as $iStatus) {
          if (!empty($arAllStatuses[$iStatus])) {
            $arListGetStatuses[] = $iStatus;
          }
        }
      }
    }

    $arQuery["status"] = $arListGetStatuses;
    if (empty($arListGetStatuses)) {
      return "Ошибка: Не выбраны этапы в настройках виджета";
    }

    if (!empty($bFlagResp)) {
      $arQuery["responsible_user_id"] = $iCurrentUser;
    }
    $arLeads = $amo->getLeadList($arQuery);
    $arAllLeads = [];
    $arIdLead = [];
    $sOrderDate = '';
    if (!empty($arLeads["response"]["_embedded"]["items"])) {
      $arLeads = $arLeads["response"]["_embedded"]["items"];
      foreach ($arLeads as $arLead) {
        $sSchemaPayment = $sCommentPayment = $sNoteLead = '';
        $arCompany = "Не заполненно";
        if (!empty($arLead["company"]["id"])) {
          $arCompany = $amo->getCompany($arLead["company"]["id"])["response"]["_embedded"]["items"][0]["name"];
        }
        $arPayment = [];
        foreach ($arLead["custom_fields"] as $arCustom) {
          switch ($arCustom["name"]) {
            case 'Платеж 1':
            case 'Платеж 2':
            case 'Платеж 3':
            case 'Платеж 4':
            case 'Платеж 5':
            case 'Платеж 7':
            case 'Платеж 8':
            case 'Платеж 9':
            case 'Платеж 10':
              $num = str_replace("Платеж ", "", $arCustom["name"]);
              $arPayment[$num]["payment"] = $arCustom["values"][0]["value"];
              break;
            case 'Остаток 1':
            case 'Остаток 2':
            case 'Остаток 3':
            case 'Остаток 4':
            case 'Остаток 5':
            case 'Остаток 7':
            case 'Остаток 8':
            case 'Остаток 9':
            case 'Остаток 10':
              $num = str_replace("Остаток ", "", $arCustom["name"]);
              $arPayment[$num]["rest"] = $arCustom["values"][0]["value"];
              break;
            case 'Схема оплаты':
              $sSchemaPayment = $arCustom["values"][0]["value"];
              break;
            case 'Комментарий по оплате':
              $sCommentPayment = $arCustom["values"][0]["value"];
              break;
            case 'Примечание сделки':
              $sNoteLead = $arCustom["values"][0]["value"];
              break;
            case 'Срок договора':
              $sOrderDate = $arCustom["values"][0]["value"];
              break;


          }
        }

        $arAllLeads[$arLead["id"]] = [
          "name" => $arLead["name"],
          "sale" => $arLead["sale"],
          "types_1" => !empty($arPipelines[$arLead["pipeline"]["id"]]["types_1"]) ? $arPipelines[$arLead["pipeline"]["id"]]["types_1"] : [],
          "types_2" => !empty($arPipelines[$arLead["pipeline"]["id"]]["types_2"]) ? $arPipelines[$arLead["pipeline"]["id"]]["types_2"] : [],
          "files_1" => [],
          "files_2" => [],
          "payment" => $arPayment,
          "company" => $arCompany,
          "schema" => $sSchemaPayment,
          "commentPayment" => $sCommentPayment,
          "noteLead" => $sNoteLead,
          "dateOrder" => explode(" ", $sOrderDate)[0],

        ];
        $arIdLead[] = $arLead["id"];
      }
    }
    $obFiles->getList([
      "limit" => "LIMIT 0, 500",
      "where" => "WHERE account_id = '{$obAccount->id}' AND id_amo IN (".implode(",", $arIdLead).")",
      "select" => "id_type,id_amo,name,date,date_attache"
    ]);

    while ($arFiles = $obFiles->fetch()) {
      if (!empty($arAllLeads[$arFiles["id_amo"]]["types_1"][$arFiles["id_type"]])) {
        $arAllLeads[$arFiles["id_amo"]]["files_1"][] =
          [
            "name" => $arFiles["name"],
            "date" => date("d.m.Y", $arFiles["date"]),
            "date_attache" => !empty($arFiles["date_attache"]) ? date("d.m.Y", $arFiles["date_attache"]) : "Н/Д",
          ];
      }
      if (!empty($arAllLeads[$arFiles["id_amo"]]["types_2"][$arFiles["id_type"]])) {
        $arAllLeads[$arFiles["id_amo"]]["files_2"][] =
          [
            "name" => $arFiles["name"],
            "date" => date("d.m.Y", $arFiles["date"]),
            "date_attache" => !empty($arFiles["date_attache"]) ? date("d.m.Y", $arFiles["date_attache"]) : "Н/Д",
          ];
      }
    }

    include_once $_SERVER["DOCUMENT_ROOT"]."/miwr/ex/Classes/PHPExcel.php";
    $obExcel = new \PHPExcel();
    $obExcel->setActiveSheetIndex(0);
    $sheet = $obExcel->getActiveSheet();
    $sheet->setTitle('Отчет');
    $iFilesInc = [0 => 0, 1 => 0, 2 => 0];

    $arCells = [
      "A" => "№",
      "B" => "Клиент",
      "C" => "Обмерочник",
      "D" => "Обмерочник\n дата прикрепления",
      "E" => "Платежка",
      "F" => "Платежка\n дата прикрепления",
      "G" => "Договор",
      "H" => "Условия оплаты",
      "I" => "Оплата",
      "J" => "Остаток",
      "K" => "Срок договора",
      "L" => "Сумма",
      "M" => "Примечание"
    ];


    foreach ($arCells as $key => $val) {
      $sheet->setCellValue($key."1", $val);
      $sheet->getStyle($key."1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
      $sheet->getStyle($key."1")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
      $sheet->getStyle($key."1")->getAlignment()->setWrapText(true);
    }

    $i = 2;
    foreach ($arAllLeads as $arAllLead) {

      $sPaymentPay = [];
      $sPaymentRest = [];
      if (!empty($arAllLead["payment"])) {
        foreach ($arAllLead["payment"] as $arPayment) {
          if (isset($arPayment["payment"])) {
            $sPaymentPay[] = $arPayment["payment"];
          }
          if (isset($arPayment["rest"])) {
            $sPaymentRest[] = $arPayment["rest"];
          }
        }
        $iFilesInc[0] = $iFiles = $i;
        foreach ($sPaymentPay as $key => $val) {
          if (isset($sPaymentPay[$key])) {
            $sheet->setCellValue("I".$iFiles, $sPaymentPay[$key]);
            $sheet->getStyle("I".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I".$iFiles)->getAlignment()->setWrapText(true);
            $sheet->getStyle("I".$iFiles)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->getStyle("I".$iFiles)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
          }
          if (isset($sPaymentRest[$key])) {
            $sheet->setCellValue("J".$iFiles, $sPaymentRest[$key]);
            $sheet->getStyle("J".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J".$iFiles)->getAlignment()->setWrapText(true);
            $sheet->getStyle("J".$iFiles)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->getStyle("J".$iFiles)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
          }
          $iFiles++;
        }
        $iFilesInc[0] = ($iFiles);
      }
      $arMainCell = [
        "A",
        "B",
        "G",
        "H",
        "K",
        "L",
        "M"
      ];

      $sheet->setCellValue("A".$i, ($i - 1));
      $sheet->setCellValue("B".$i, $arAllLead["company"]);
      $sheet->setCellValue("G".$i, $arAllLead["name"]);
      $sheet->setCellValue("H".$i, $arAllLead["schema"]."\n".$arAllLead["commentPayment"]);

      $sheet->setCellValue("K".$i, $arAllLead["dateOrder"]);

      $sheet->setCellValue("L".$i, $arAllLead["sale"]);
      $sheet->getStyle("L".$i)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

      $sheet->setCellValue("M".$i, $arAllLead["noteLead"]);

      foreach ($arMainCell as $sMainCell) {
        $sheet->getStyle($sMainCell.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $sheet->getStyle($sMainCell.$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($sMainCell.$i)->getAlignment()->setWrapText(true);
      }



      if (!empty($arAllLead["files_1"])) {
        $iFilesInc[1] = $iFiles = $i;
        foreach ($arAllLead["files_1"] as $arFile) {
          $sFiles = $arFile["name"]."\n".$arFile["date"];
          $sheet->setCellValue("C".$iFiles, $sFiles);
          $sheet->getStyle("C".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $sheet->getStyle("C".$iFiles)->getAlignment()->setWrapText(true);
          $sheet->setCellValue("D".$iFiles, $arFile["date_attache"]);
          $sheet->getStyle("D".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $sheet->getStyle("D".$iFiles)->getAlignment()->setWrapText(true);
          $iFiles++;
        }
        $iFilesInc[1] = $iFiles;
        if ($iFilesInc[0] > $iFiles) {
          $iFiles = $iFilesInc[0];
        }
      }

      if (!empty($arAllLead["files_2"])) {
        $iFilesInc[2] = $iFiles = $i;
        foreach ($arAllLead["files_2"] as $arFile) {
          $sFiles = $arFile["name"]."\n".$arFile["date"];
          $sheet->setCellValue("E".$iFiles, $sFiles);
          $sheet->getStyle("E".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $sheet->getStyle("E".$iFiles)->getAlignment()->setWrapText(true);
          $sheet->setCellValue("F".$iFiles, $arFile["date_attache"]);
          $sheet->getStyle("F".$iFiles)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $sheet->getStyle("F".$iFiles)->getAlignment()->setWrapText(true);
          $iFiles++;
        }
        $iFilesInc[2] = $iFiles;
        if ($iFilesInc[1] > $iFiles) {
          $iFiles = $iFilesInc[1];
        }
      }

      if (!empty($iFiles)) {
        $iFiles = max($iFilesInc);
        foreach ($arMainCell as $sMainCell) {
          $arNumbers = [];
          for ($inc = $i; $inc < $iFiles; $inc++) {
            $arNumbers[] = $sMainCell.$inc;
          }
          if (count($arNumbers) > 1) {
            $sheet->mergeCells($arNumbers[0].":".$arNumbers[(count($arNumbers) - 1)]);
          }
          //vd($arNumbers);
        }
        $i = ($iFiles - 1);
      }

      foreach ($arCells as $key => $name) {
        $sheet->getStyle($key.$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($key.$i)->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension($key)->setAutoSize(true);
      }
      $i++;
    }


    header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
    header("Last-Modified: ".gmdate("D,d M YH:i:s")." GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=otchet_".date("m_d_Y").".xls");
    $objWriter = new \PHPExcel_Writer_Excel5($obExcel);
    $objWriter->save('php://output');


  }


  /**
   * Установка парамтреров
   * @return string = JSON
   */
  public function set()
  {
    include_once ROOT_MIWR."/class.report.php";
    $obReport = new Report();
    $obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();

    $arJSON = [];
    if (!empty($_REQUEST["statuses"])) {
      foreach ($_REQUEST["statuses"] as $idPipeline => $arPipeline) {
        foreach ($arPipeline as $iStatus) {
          $arJSON[$idPipeline]["statuses"][$iStatus] = $iStatus;
        }
      }
    }
    if (!empty($_REQUEST["types_1"])) {
      foreach ($_REQUEST["types_1"] as $idPipeline => $arPipeline) {
        foreach ($arPipeline as $iStatus) {
          $arJSON[$idPipeline]["types_1"][$iStatus] = $iStatus;
        }
      }
    }

    if (!empty($_REQUEST["types_2"])) {
      foreach ($_REQUEST["types_2"] as $idPipeline => $arPipeline) {
        foreach ($arPipeline as $iStatus) {
          $arJSON[$idPipeline]["types_2"][$iStatus] = $iStatus;
        }
      }
    }
    $arJSON["success"] = "Данные успешно сохранены";
    $sJSON = json_encode($arJSON, JSON_UNESCAPED_UNICODE);
    $obReport->insert([
      "account_id" => $obAccount->id,
      "params" => $sJSON
    ]);
    return $sJSON;
  }

  /**
   * Получение настроек
   */
  public function get($account_id)
  {
    include_once ROOT_MIWR."/class.report.php";
    $obReport = new Report();
    $obAccount = Account::where(["subdomain" => $account_id])->first();
    if (!empty($obAccount->id)) {
      $sParams = $obReport->get($obAccount->id);
      if (!empty($sParams)) {
        return $sParams["params"];
      } else {
        return json_encode(["no_data" => 1]);
      }
    }
    return json_encode(["no_data" => 1]);
  }

}