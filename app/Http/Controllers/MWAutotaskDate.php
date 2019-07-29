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
use function MIWR\vd;

define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.autotask_log.php";

class MWAutotaskDate extends Controller
{
  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }

  public function run()
  {
    include_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
    $obAutoTaskLog = new AutotaskLog();
    set_time_limit(3600);
    $start = microtime(true);
    for ($i = 0; $i < 30; $i++) {
      $res = $obAutoTaskLog->delete("time < ".strtotime("now"));
      $arTasks = AutoTask::where([
        ["is_active", "=", 1],
        ["type_interval", "=", "date"],
      ])->distinct()->get([
        'account_id',
        'pipeline',
        'statuses',
        'responsible',
        'schedule',
        'task_type',
        'body',
        'id',
        'date_interval'
      ]);

      $resTask = [];
      $arSetTasks = [];
      $arAddLog = []; // Список лидов на доавбелние в базу
      $arCurrentTime = getdate();
      $sTime = date('d-m-Y 23:59');
      foreach ($arTasks as $arTask) {
        if (empty($arSetTasks[$arTask->account->subdomain])) {
          $arSetTasks[$arTask->account->subdomain] = [];
        }
        $bCycleAuth = true;
        $iCycle = 0;
        while ($bCycleAuth) {
          $obAMO = new MW_AMO_CRM();
          $bAuth = $obAMO->init($arTask->account->subdomain, $arTask->account->login, $arTask->account->hash);
          if (!empty($bAuth["auth"])) {
            $arAddTask = [];
            $bCycleAuth = false; // Выходим из цикла после первой успешной авторизации
            $arLeadsResponse = $obAMO->getLeadList([
              "responsible_user_id" => $arTask->responsible,
              "status" => $arTask->statuses,
            ]);
            if (!empty($arLeadsResponse["response"]["_embedded"]["items"])) {
              $arLeads = $arLeadsResponse["response"]["_embedded"]["items"];
              $arGetLogId = [];
              foreach ($arLeads as $arLead) {
                $arGetLogId[] = $arLead["id"];                            // Собираем ID выбранных лидов
                // Формируем массив для добавление задачи

                $arSetTask = [
                  "element_id" => $arLead["id"],
                  "element_type" => 2,
                  "task_type" => $arTask->task_type,
                  "text" => $arTask->body,
                  "responsible_user_id" => $arLead['responsible_user_id'],
                  "complete_till" => $sTime
                ];

                $arSetTasks[$arTask->account->subdomain][$arLead["id"]] = $arSetTask;  // Для удобного представления делим на субдомены
                $arAddTask[$arLead["id"]] = $arSetTask;                   // Добавляем в массив для добавления через API amo
                // Массив логов для записи
                $arAddLog[$arLead["id"]] = [
                  $arTask->account->id,
                  $arLead["id"],
                  $arTask->id,
                  strtotime("now + ".$arTask->date_interval." seconds")
                ];
              }

              if (!empty($arGetLogId)) {
                $rRes = $obAutoTaskLog->getList([
                  "limit" => "LIMIT 0, 500",
                  "where" => "WHERE account_id='{$arTask->account->id}' AND autotask_id='{$arTask->id}' AND lead_id IN (".implode(",", $arGetLogId).")"
                ]);
                while ($arAutoTaskList = $obAutoTaskLog->fetch($rRes)) {
                  unset(
                    $arAddLog[$arAutoTaskList["lead_id"]],
                    $arAddTask[$arAutoTaskList["lead_id"]],
                    $arSetTasks[$arTask->account->subdomain][$arAutoTaskList["lead_id"]]
                  );
                }
                if (!empty($arAddTask)) {
                  $resTask[$arTask->account->subdomain][] = $obAMO->addTask($arAddTask);
                }
              }
            }
          } else {
            $iCycle++;
          }
          if ($iCycle > 10) {
            $bCycleAuth = false;  // Выходим из цыкла после 10-ти провальных авторизаций
            Log::info("AUTOTASK_LOG Date", ["BAD" => "10", "auth" => $bAuth]);
          }
        }
      }
      // Добавление логов
      if (!empty($arAddLog)) {
        /*Log::info("AUTOTASK_DATE", ["LOG" => $arAddLog]);
        $obAutoTaskLog->insert([
          "set" => ["account_id", "lead_id", "autotask_id", "time"],
          "values" => $arAddLog
        ]);*/
      }
      //vd($arSetTasks);
      //vd($resTask);
      if (microtime(true) - $start > 50) {
        exit();
      }
      sleep(1);
    }
  }
}