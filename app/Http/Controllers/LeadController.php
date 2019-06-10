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
use function MIWR\vd;

define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");

class LeadController extends Controller
{
  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
  }

  public function addAccount(Request $request)
  {
    $account = $this->__addAccount($request);

    return response()->json([
      'response' => (bool)$account
    ]);
  }

  public function calc(Request $request, AmoCrmManager $amocrm)
  {
    try {
      if ($request->input('leads.update.0.custom_fields')) {
        Log::info('Request from amo change lead', [
          'data' => $request->input('leads')
        ]);

        $client = $amocrm->getClient();
        $all_custom_fields = $client->account->apiCurrent()['custom_fields']['leads'];

        $custom_fields = $request->input('leads.update.0.custom_fields');

        for ($i = 0; $i < count($all_custom_fields); $i++) {

          for ($j = 0; $j < count($custom_fields); $j++) {
            if ($custom_fields[$j]['id'] == $all_custom_fields[$i]['id']) {
              $client->fields->add($custom_fields[$j]['name'], [$custom_fields[$j]['id'], $custom_fields[$j]['values'][0]['value']]);
              break;
            } else {
              $client->fields->add($all_custom_fields[$i]['name'], [$all_custom_fields[$i]['id'], false]);
            }
          }
        }

        if ($client->fields->get('БЮДЖЕТ')[1] &&
          ($client->fields->get('Видео продакшн')[1]
            || $client->fields->get('Звукорежиссер')[1]
            || $client->fields->get('Сценарий')[1]
            || $client->fields->get('Съемочная группа')[1]
            || $client->fields->get('Носители языка')[1]
            || $client->fields->get('Диктор')[1]
            || $client->fields->get('Диктор 2')[1]
            || $client->fields->get('Диктор 3')[1]
            || $client->fields->get('Диктор 4')[1]
            || $client->fields->get('Диктор 5')[1]
            || $client->fields->get('Продакшн')[1]
            || $client->fields->get('Реклама радио')[1]
            || $client->fields->get('Реклама ТВ')[1]
            || $client->fields->get('Реклама Инет')[1])
        ) {
          $calc = Calculator::find((int)$request->input('leads.update.0.id'));
          $cost = (int)$client->fields->get('БЮДЖЕТ')[1];
          $cost_price = (int)$client->fields->get('Видео продакшн')[1] + (int)$client->fields->get('Звукорежиссер')[1] + (int)$client->fields->get('Сценарий')[1] + (int)$client->fields->get('Съемочная группа')[1] + (int)$client->fields->get('Носители языка')[1] + (int)$client->fields->get('Диктор')[1] + (int)$client->fields->get('Диктор 2')[1] + (int)$client->fields->get('Диктор 3')[1] + (int)$client->fields->get('Диктор 4')[1] + (int)$client->fields->get('Диктор 5')[1] + (int)$client->fields->get('Продакшн')[1] + (int)$client->fields->get('Реклама радио')[1] + (int)$client->fields->get('Реклама ТВ')[1] + (int)$client->fields->get('Реклама Инет')[1];


          if (is_null($calc)) {
            Calculator::insert([
              'id' => (int)$request->input('leads.update.0.id'),
              'subdomain' => $request->input('account.subdomain'),
              'cost' => $cost,
              'cost_price' => $cost_price,
            ]);

            $lead = $client->lead;
            $lead->addCustomField($client->fields->get('Прибыль')[0], $cost - $cost_price);
            $lead->addCustomField($client->fields->get('Затраты факт')[0], $cost_price);
            sleep(1);
            $response = $lead->apiUpdate((int)$request->input('leads.update.0.id'), 'now');

            Log::info('The record has been inserted successfully', [
              'data' => $request->input('leads')
            ]);

            return response()->json([
              'success' => [
                'data' => 'The record has been inserted successfully'
              ]
            ]);
          } else {
            if ($calc->cost === $cost && $calc->cost_price === $cost_price) {
              Log::info('Unchanged calc->cost: $calc->cost, calc->cost_price: $calc->cost_price, cost: $cost, cost_price: $cost_price', [
                'data' => $request->input('leads')
              ]);

              return response()->json([
                'error' => [
                  'message' => 'Unchanged'
                ]
              ]);
            } else {
              $calc->cost = $cost;
              $calc->cost_price = $cost_price;

              $calc->save();

              $lead = $client->lead;
              $lead->addCustomField($client->fields->get('Прибыль')[0], $cost - $cost_price);
              $lead->addCustomField($client->fields->get('Затраты факт')[0], $cost_price);
              sleep(1);
              $response = $lead->apiUpdate((int)$request->input('leads.update.0.id'), 'now');

              Log::info('success', [
                'response' => $response
              ]);

              return response()->json([
                'success' => [
                  'data' => $response
                ]
              ]);
            }
          }
        } else {
          Log::info('Required fields are missed', [
            'data' => $request->input('leads')
          ]);

          return response()->json([
            'error' => [
              'message' => 'Required fields are missed'
            ]
          ]);
        }
      }
    } catch (\Exception $e) {
      abort(400, $e->getMessage());
    }
  }

  public function extcalc(Request $request)
  {
    try {
      if ($request->input('hash')) {
        $account = $this->__addAccount($request);
        Log::info('add account for calc', [
          "account_action" => !empty($account['action']) ? $account['action'] : false
        ]);
        $isFiled = $account->calc->fields;
        if ($account['action'] === 'create') {
          if (empty($isFiled)) {
            $calc = \App\Models\CalcField::insert([
              'account_id' => $account['account']->id,
              'fields' => $request->input('fields')
            ]);
          } else {
            $calc = \App\Models\CalcField::where([
              'account_id' => $account['account']->id
            ])->update([
              'fields' => $request->input('fields'),
            ]);
          }
          $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/extcalc', 'update_lead');
          require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
          $amoMW = new MW_AMO_CRM;
          $amoMW->init($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amoMW->addFieldLead(["name" => "Прибыль", "type" => 1, "is_editable" => 0]);
          $amoMW->addFieldLead(["name" => "Затраты факт", "type" => 1, "is_editable" => 0]);
        } else {

          $calc = \App\Models\CalcField::where([
            'account_id' => $account['account']->id
          ])->update([
            'fields' => $request->input('fields'),
          ]);
          if (empty($isFiled)) {
            $calc = \App\Models\CalcField::insert([
              'account_id' => $account['account']->id,
              'fields' => $request->input('fields')
            ]);
            $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
            $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/extcalc', 'update_lead');
            require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
            $amoMW = new MW_AMO_CRM;
            $amoMW->init($request->input('subdomain'), $request->input('login'), $request->input('hash'));
            $amoMW->addFieldLead(["name" => "Прибыль", "type" => 1, "is_editable" => 0]);
            $amoMW->addFieldLead(["name" => "Затраты факт", "type" => 1, "is_editable" => 0]);
          }
        }

        return response()->json([
          'success' => (bool)$calc
        ]);
      }

      if ($request->input('leads.update.0.custom_fields') && ($cost = $request->input('leads.update.0.price'))) {

        $account = Account::where('subdomain', $request->input('account.subdomain'))->first();
        $amo = new AmoCRMClient($account->subdomain, $account->login, $account->hash);
        $all_custom_fields = $amo->account->apiCurrent()['custom_fields']['leads'];
        $custom_fields = $request->input('leads.update.0.custom_fields');
        $cost_price = 0;


        for ($i = 0; $i < count($all_custom_fields); $i++) {
          for ($j = 0; $j < count($custom_fields); $j++) {
            if ($custom_fields[$j]['id'] == $all_custom_fields[$i]['id']) {
              $amo->fields->add($custom_fields[$j]['name'], [$custom_fields[$j]['id'], $custom_fields[$j]['values'][0]['value']]);
              break;
            } else {
              $amo->fields->add($all_custom_fields[$i]['name'], [$all_custom_fields[$i]['id'], false]);
            }
          }
        }

        foreach ($account->calc->fields as $field) {
          $cost_price += $amo->fields->get($field)[1];
        }

        if ($cost_price > 0) {
          $calc = Calculator::where([
            'id' => (int)$request->input('leads.update.0.id'),
            'subdomain' => $request->input('account.subdomain')
          ])->first();

          if (is_null($calc)) {
            Calculator::insert([
              'id' => (int)$request->input('leads.update.0.id'),
              'subdomain' => $request->input('account.subdomain'),
              'cost' => $cost,
              'cost_price' => $cost_price,
            ]);

            $lead = $amo->lead;
            $lead->addCustomField($amo->fields->get('Прибыль')[0], $cost - $cost_price);
            $lead->addCustomField($amo->fields->get('Затраты факт')[0], $cost_price);
            //sleep(1);
            $response = $lead->apiUpdate((int)$request->input('leads.update.0.id'), 'now');

            Log::info('The record has been inserted successfully', [
              'data' => $request->input('leads')
            ]);

            return response()->json([
              'success' => [
                'data' => 'The record has been inserted successfully'
              ]
            ]);
          } else {
            if ($calc->cost === $cost && $calc->cost_price === $cost_price) {
              Log::info('Unchanged calc->cost: $calc->cost, calc->cost_price: $calc->cost_price, cost: $cost, cost_price: $cost_price', [
                'data' => $request->input('leads')
              ]);

              return response()->json([
                'error' => [
                  'message' => 'Unchanged'
                ]
              ]);
            } else {
              $calc->cost = $cost;
              $calc->cost_price = $cost_price;

              $calc->save();

              $lead = $amo->lead;
              $lead->addCustomField($amo->fields->get('Прибыль')[0], $cost - $cost_price);
              $lead->addCustomField($amo->fields->get('Затраты факт')[0], $cost_price);
              sleep(1);
              $response = $lead->apiUpdate((int)$request->input('leads.update.0.id'), 'now');

              /*Log::info('success cacl', [
                'response' => $response
              ]);
*/
              $misha["data"] = $response;
              return response()->json(
                [
                  'success' => [$misha]
                ]
              );
            }
          }
        } else {
          Log::info('Required fields are missed', [
            'data' => $request->input('leads')
          ]);

          return response()->json([
            'error' => [
              'message' => 'Required fields are missed'
            ]
          ]);
        }

      }
    } catch (\Exception $e) {
      abort(400, $e->getMessage());
    }
  }


  public function testCopyLead()
  {
    $account = Account::where('subdomain', "linerappwidgets")->first();
    $amocrm = new AmoCRMClient($account->subdomain, $account->login, $account->hash);

    $amoAccount = $amocrm->account->apiCurrent();

    $lead = $amocrm->lead->apiList([
      'id' => 13555273,
    ]);

  }

  public function respCopyLead(Request $request)
  {
    Log::info("respCopyLead", [
      'data' => $_POST
    ]);


    $account = Account::where('subdomain', $request->input('subdomain'))->first();
    include_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
    $amo = new MW_AMO_CRM;
    include_once ROOT_MIWR."/class.copylead.php";
    $obCopy = new Copylead();
    $arCopy = $obCopy->get($account->id);
    $Response = $amo->init($account->subdomain, $account->login, $account->hash);
    $arResponse = 0;
    if (!empty($arCopy["copy_resp"])) {
      $arParams = [
        "add" => [
          [
            "element_id" => $_POST["lead_id"],
            "element_type" => 2,
            "text" => "",
            "note_type" => 25,
            "created_at" => time(),
            "created_by" => $_POST["userID"],
            'params' => [
              'text' => 'Смена ответственного: с '.$_POST["respCurrent"].' на '.$_POST["respChange"],
              'service' => 'Смена отвественного (для виджета)',
            ]
          ]
        ]
      ];
      $arResponse = $amo->addNode($arParams);
    }
    return response()->json([
      'params' => $arResponse
    ]);
  }


  public function setCopyLead(Request $request)
  {
    $account = $this->__addAccount($request);
    include_once ROOT_MIWR."/class.copylead.php";
    $obCopyLead = new Copylead();
    $params = $obCopyLead->insert([
      "account_id" => $account["account"]->id,
      "copy_note" => $_POST["copy_note"],
      "copy_status" => $_POST["copy_status"],
      "copy_resp" => $_POST["copy_resp"],
    ]);
    //$amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/copy/resp', 'responsible_lead');
    return response()->json([
      'response' => (bool)$account,
      'params' => $params
    ]);
  }

  public function copy3(Request $request)
  {
    $tags = [];
    // try {
    if ($lead_id = $request->input('lead_id')) {
      include_once ROOT_MIWR."/class.copylead.php";
      require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
      $account = Account::where('subdomain', $request->input('subdomain'))->first();
      $amocrm = new AmoCRMClient($account->subdomain, $account->login, $account->hash);
      $lead = $amocrm->lead->apiList([
        'id' => (int)$lead_id,
      ]);

      //vd($lead, true);


      $amoMW = new MW_AMO_CRM;
      $amoMW->init($account->subdomain, $account->login, $account->hash);
      $obCopy = new Copylead();
      $arCopy = $obCopy->get($account->id);


      unset($lead[0]['id']);
      $lead[0]['name'] = $lead[0]['name']." (Copy)";
      $copy_lead = $amocrm->lead;

      foreach ($lead[0] as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $item) {
            if ($key === 'tags') {
              $tags[] = $item['name'];
            }

            if ($key === 'custom_fields') {
              if (count($item['values']) > 1) {
                $copy_lead->addCustomMultiField($item['id'], $item['values']);
              } else {
                if (!isset($item['values'][0]['enum'])) {
                  $copy_lead->addCustomField($item['id'], $item['values'][0]['value']);
                } else {
                  $copy_lead->addCustomField($item['id'], $item['values'][0]['value'], $item['values'][0]['enum']);
                }
              }
            }
          }
        } else {
          $copy_lead[$key] = $value;
        }
        //$copy_lead["contacts_id"] = [35130177];
        $copy_lead['tags'] = $tags;
      }

      $id = $copy_lead->apiAdd();

      $contacts = $amocrm->contact->apiLinks([
        'deals_link' => $lead_id
      ]);

      if (!empty($contacts)) {
        foreach (array_column($contacts, 'contact_id') as $key => $value) {
          $contact_data = $amocrm->contact->apiList([
            'id' => $value,
          ]);
          array_push($contact_data[0]['linked_leads_id'], $id);
          $res = $amoMW->updateContacts(["id" => $value, "updated_at" => time(), "linked_leads_id" => $contact_data[0]['linked_leads_id']]);
          //vd($contact_data[0]['linked_leads_id']);
          sleep(1);
        }
      }


      $amoMW2 = new MW_AMO_CRM;
      $amoMW2->init($account->subdomain, $account->login, $account->hash);
      if (!empty($arCopy["copy_resp"])) {
        $arListCopyResp = $amoMW->noteGetList($lead_id, 25);
        if (!empty($arListCopyResp["response"])) {
          foreach ($arListCopyResp["response"]["_embedded"]["items"] as $data) {
            $arParams22["add"][] =
              [
                "element_id" => $id,
                "responsible_user_id" => $data["responsible_user_id"],
                "element_type" => 2,
                "text" => "",
                "note_type" => 25,
                "created_at" => $data["created_at"],
                "created_by" => $data["created_by"],
                "params" => [
                  "text" => $data["text"] ? $data["text"] : $data["params"],
                  "service" => "Смена отвественного",
                ]
              ];
          }
          $res = $amoMW2->addNode($arParams22);
        }
      }

      if (!empty($arCopy["copy_note"])) {
        $arList_4 = $amoMW->noteGetList($lead_id, 4);
        $arList_5 = $amoMW->noteGetList($lead_id, 5);
        if (!empty($arList_4["response"])) {
          foreach ($arList_4["response"]["_embedded"]["items"] as $data) {
            $arParams33["add"][] = [
              "created_by" => $data["created_by"],
              "created_at" => $data["created_at"],
              "updated_at" => $data["updated_at"],
              "account_id" => $data["account_id"],
              "group_id" => $data["group_id"],
              "element_type" => $data["element_type"],
              "element_id" => $id,
              "note_type" => $data["note_type"],
              "text" => !empty($data["text"]) ? $data["text"] : $data["params"],
            ];
          }
          $res = $amoMW2->addNode($arParams33);
        }

        if (!empty($arList_5["response"])) {
          foreach ($arList_5["response"]["_embedded"]["items"] as $data) {
            //vd($data);
            $arParams44["add"][] = [
              "created_by" => $data["created_by"],
              "created_at" => $data["created_at"],
              "updated_at" => $data["updated_at"],
              "account_id" => $data["account_id"],
              "group_id" => $data["group_id"],
              "element_type" => $data["element_type"],
              "element_id" => $id,
              "note_type" => $data["note_type"],
              "attachment" => $data["attachment"],
              "text" => $data["params"]["TEXT"],
              'params' => [
                'TEXT' => $data["params"]["TEXT"],
                'HTML' => $data["params"]["HTML"],
              ]
            ];
          }
          $res = $amoMW2->addNode($arParams44);
          /*vd($res);
          vd($arParams44);*/

        }
      }

      if (!empty($arCopy["copy_status"])) {
        $arListCopyStatus = $amoMW->noteGetList($lead_id, 3);
        if (!empty($arListCopyStatus["response"])) {
          $arPipelines = $amoMW2->pipelineGetList()["response"]["_embedded"]["items"];
          $amoMW3 = new MW_AMO_CRM;
          $amoMW3->init($account->subdomain, $account->login, $account->hash);
          foreach ($arListCopyStatus["response"]["_embedded"]["items"] as $data) {
            $arParams55["add"][] = [
              "element_id" => $id,
              "responsible_user_id" => $data["responsible_user_id"],
              "element_type" => 2,
              "text" => "...",
              "note_type" => 25,
              "created_at" => $data["created_at"],
              "created_by" => $data["created_by"],
              'params' => [
                "text" => "Новый этап: ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["name"]." ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["statuses"][$data["params"]["STATUS_NEW"]]["name"]." из ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["statuses"][$data["params"]["STATUS_OLD"]]["name"],
                "service" => "Смена статуса",
              ]
            ];
          }
          $res = $amoMW3->addNode($arParams55);
        }
      }


      return response()->json([
        'response' => [
          'status' => 'ok',
          'data' => "",
          'redirect' => "/leads/detail/".$id
        ],
      ]);
    } else {
      return response()->json([
        'error' => 'Required fields are missed'
      ], 400);
    }
    /* } catch (\Exception $e) {
       abort(400, $e->getMessage());
     }*/
  }

  public function copy(Request $request)
  {
    $tags = [];
    // try {
    if ($lead_id = $request->input('lead_id')) {
      require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
      include_once ROOT_MIWR."/class.copylead.php";
      $account = Account::where('subdomain', $request->input('subdomain'))->first();
      $obCopy = new Copylead();
      $arCopy = $obCopy->get($account->id);

      $amoMW = new MW_AMO_CRM;
      $res = $amoMW->init($account->subdomain, $account->login, $account->hash);
      $arLead = $amoMW->getLeadList(["id" => $lead_id]);
      if (empty($arLead["error"])) {
        $currentLead = $arLead["response"]["_embedded"]["items"][0];
        $currentLead["name"] = $currentLead["name"]." (copy widget)";

        if (!empty($currentLead["company"]["id"])) {
          $currentLead["company_id"] = $currentLead["company"]["id"];
        }
        if (!empty($currentLead["contacts"]["id"])) {
          $currentLead["contacts_id"] = $currentLead["contacts"]["id"];
        }
        if (!empty($currentLead["pipeline"]["id"])) {
          $currentLead["pipeline_id"] = $currentLead["pipeline"]["id"];
        }

        unset(
          $currentLead["id"],
          $currentLead["updated_at"],
          $currentLead["account_id"],
          $currentLead["is_deleted"],
          $currentLead["is_deleted"],
          $currentLead["group_id"],
          $currentLead["closed_at"],
          $currentLead["closest_task_at"],
          $currentLead["company"],
          $currentLead["contacts"],
          $currentLead["loss_reason_id"],
          $currentLead["pipeline"],
          $currentLead["_links"],
          $currentLead["main_contact"],
          $currentLead["created_by"],
          $currentLead["tags"],
          $currentLead["created_at"]
        );

        foreach ($currentLead["custom_fields"] as $key => $val) {
          unset($currentLead["custom_fields"][$key]["name"], $currentLead["custom_fields"][$key]["is_system"]);
        }


        $arAddLead = $amoMW->addLead($currentLead);
        if (empty($arAddLead["error"])) {
          $id = $arAddLead["response"]["_embedded"]["items"][0]["id"]; // id новой добавленной сделки
          if (!empty($arCopy["copy_resp"])) {
            $arListCopyResp = $amoMW->noteGetList($lead_id, 25);
            if (!empty($arListCopyResp["response"]["_embedded"])) {
              foreach ($arListCopyResp["response"]["_embedded"]["items"] as $data) {
                $arParams22["add"][] =
                  [
                    "element_id" => $id,
                    "responsible_user_id" => $data["responsible_user_id"],
                    "element_type" => 2,
                    "text" => "",
                    "note_type" => 25,
                    "created_at" => $data["created_at"],
                    "created_by" => $data["created_by"],
                    "params" => [
                      "text" => $data["text"] ? $data["text"] : $data["params"],
                      "service" => "Смена отвественного",
                    ]
                  ];
              }
              $res = $amoMW->addNode($arParams22);
            }
          }

          if (!empty($arCopy["copy_note"])) {
            $arList_4 = $amoMW->noteGetList($lead_id, 4);
            $arList_5 = $amoMW->noteGetList($lead_id, 5);
            if (!empty($arList_4["response"]["_embedded"])) {
              foreach ($arList_4["response"]["_embedded"]["items"] as $data) {
                $arParams33["add"][] = [
                  "created_by" => $data["created_by"],
                  "created_at" => $data["created_at"],
                  "updated_at" => $data["updated_at"],
                  "account_id" => $data["account_id"],
                  "group_id" => $data["group_id"],
                  "element_type" => $data["element_type"],
                  "element_id" => $id,
                  "note_type" => $data["note_type"],
                  "text" => !empty($data["text"]) ? $data["text"] : $data["params"],
                ];
              }
              $res = $amoMW->addNode($arParams33);
            }

            if (!empty($arList_5["response"]["_embedded"])) {
              foreach ($arList_5["response"]["_embedded"]["items"] as $data) {
                //vd($data);
                $arParams44["add"][] = [
                  "created_by" => $data["created_by"],
                  "created_at" => $data["created_at"],
                  "updated_at" => $data["updated_at"],
                  "account_id" => $data["account_id"],
                  "group_id" => $data["group_id"],
                  "element_type" => $data["element_type"],
                  "element_id" => $id,
                  "note_type" => $data["note_type"],
                  "attachment" => $data["attachment"],
                  "text" => $data["params"]["TEXT"],
                  'params' => [
                    'TEXT' => $data["params"]["TEXT"],
                    'HTML' => $data["params"]["HTML"],
                  ]
                ];
              }
              $res = $amoMW->addNode($arParams44);
              /*vd($res);
              vd($arParams44);*/

            }
          }

          if (!empty($arCopy["copy_status"])) {
            $arListCopyStatus = $amoMW->noteGetList($lead_id, 3);
            if (!empty($arListCopyStatus["response"]) && empty($arListCopyStatus["error"])) {
              $arPipelinesData = $amoMW->pipelineGetList(); //["response"]["_embedded"]["items"];
              if (!empty($arPipelinesData["error"])) {
                $arPipelines = $arPipelinesData["response"]["_embedded"]["items"];
                foreach ($arListCopyStatus["response"]["_embedded"]["items"] as $data) {
                  $arParams55["add"][] = [
                    "element_id" => $id,
                    "responsible_user_id" => $data["responsible_user_id"],
                    "element_type" => 2,
                    "text" => "...",
                    "note_type" => 25,
                    "created_at" => $data["created_at"],
                    "created_by" => $data["created_by"],
                    'params' => [
                      "text" => "Новый этап: ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["name"]." ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["statuses"][$data["params"]["STATUS_NEW"]]["name"]." из ".$arPipelines[$data["params"]["PIPELINE_ID_OLD"]]["statuses"][$data["params"]["STATUS_OLD"]]["name"],
                      "service" => "Смена статуса",
                    ]
                  ];
                }
                $res = $amoMW->addNode($arParams55);
              }
            }
          }
          return response()->json([
            'response' => [
              'status' => 'ok',
              'data' => "",
              'redirect' => "/leads/detail/".$id
            ],
          ]);
        }
      }
    } else {
      return response()->json([
        'error' => 'Required fields are missed'
      ], 400);
    }
    /* } catch (\Exception $e) {
       abort(400, $e->getMessage());
     }*/
  }

  /**
   * created by MishaninLAB.ru 03.1.2018
   * @param Request $request
   */
  public function distributionDataSet(Request $request)
  {
    $arUsers = json_decode($request->input("users"));
    $amo = new AmoCRMClient($request->input("subdomain"), $request->input("login"), $request->input("hash"));
    $arAccountAMO = $amo->account->apiCurrent();
    $obAccount = Account::where("subdomain", $request->input('subdomain'))->first();
    $distribution = Account::where("subdomain", $request->input('subdomain'))->with("distribution")->first();
    $arPipelines = $amo->pipelines->apiList();
    $arForJSONPipeline = [];
    foreach ($arPipelines as $arPipeline) {
      $arForJSONPipeline[$arPipeline["id"]] = [
        "name" => $arPipeline["name"]
      ];
      foreach ($arUsers as $id => $arUser) {
        $arForJSONPipeline[$arPipeline["id"]]["fields"][$id] = [
          "rate" => $arUser->rate,
          "id" => $id,
          "name" => $arUser->name
        ];
      }
    }

    $arDistDB = $distribution->distribution->fields;
    if (empty($arDistDB)) {
      return json_encode(["data" => $arForJSONPipeline, "field_my_lead" => $distribution->distribution->field_my_lead], JSON_UNESCAPED_UNICODE);
    } else {
      foreach ($arDistDB as $key => $val) {
        if (empty($arForJSONPipeline[$key])) {
          unset($arDistDB[$key]);
        } elseif ($arForJSONPipeline[$key]["name"] != $arDistDB[$key]["name"]) {
          $arDistDB[$key]["name"] = $arForJSONPipeline[$key]["name"];
        }
        foreach ($val["fields"] as $idUser => $arDataUsers) {
          if (empty($arForJSONPipeline[$key]["fields"][$idUser])) {
            unset($arDistDB[$key]["fields"][$idUser]);
          } elseif ($arForJSONPipeline[$key]["fields"][$idUser]["name"] != $arDistDB[$key]["fields"][$idUser]["name"]) {
            $arDistDB[$key]["fields"][$idUser]["name"] = $arForJSONPipeline[$key]["fields"][$idUser]["name"];
          }
        }
      }
      Distribution::where([
        'account_id' => $obAccount->id
      ])->update([
        'fields' => json_encode($arDistDB, JSON_UNESCAPED_UNICODE),
        "active_widget" => 1,
        "timezone" => $arAccountAMO["timezone"]
      ]);
      return json_encode(["dataset" => true, "data" => $arDistDB, "field_my_lead" => $distribution->distribution->field_my_lead], JSON_UNESCAPED_UNICODE);
    }
  }

  public function destroyDistribution(Account $account)
  {
    $amo = new AmoCRMClient($account->subdomain, $account->login, $account->hash);
    $amo->webhooks->apiUnsubscribe('https://terminal.linerapp.com/leads/distribution', 'add_lead');
    if (strpos($_SERVER["HTTP_REFERER"], "settings/widgets")) {
      Distribution::where(["account_id" => $account->id])->update(["active_widget" => 0]);
      return json_encode(["status" => "destroy"], JSON_UNESCAPED_UNICODE);
    }
    return json_encode(["status" => "not referer"], JSON_UNESCAPED_UNICODE);
  }


  /**
   * Метод цикличной отработки через кщт
   * @return string
   */
  public function cronDistribution()
  {
    set_time_limit(36000);
    $start = microtime(true);
    for ($i = 0; $i < 30; $i++) {
      $res = [];
      include_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/class.distrbution.php";
      $mwDist = new \MIWR\DistributionLog();
      $arAccounts = Distribution::where([["cron_update", "<", strtotime("now")], ["active_widget", "=", 1]])->with("account")->get();
      if (!empty($arAccounts)) {
        foreach ($arAccounts as $arAccount) {
          $arParam = [];
          require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
          $amo = new MW_AMO_CRM;
          $amo->init($arAccount["account"]->subdomain, $arAccount["account"]->login, $arAccount["account"]->hash);
          if (!empty($arAccount->timezone)) {
            $iCurrentTimeInAccount = currentTimeByZone($arAccount->timezone);
          } else {
            $iCurrentTimeInAccount = currentTimeByZone();
          }
          $arResponse = $amo->getLeadList([
            //"limit_rows" => 15,
            "filter" => [
              "date_create" => [
                "from" =>  ($iCurrentTimeInAccount - 300),
                "to" => $iCurrentTimeInAccount
              ],
              "active" => 1
            ]
          ]);
          if (!empty($arResponse["response"]["_embedded"]["items"])) {
            foreach ($arResponse["response"]["_embedded"]["items"] as $arLead) {
              $bMyLead = false;
              if (!empty($arLead["custom_fields"])) {
                foreach ($arLead["custom_fields"] as $arField) {
                  if ($arField["name"] == "Моя сделка") {
                    $bMyLead = true;
                  }
                }
              }
              if (empty($bMyLead) && empty($mwDist->get($arAccount["account"]->id, $arLead["id"]))) {
                $arParam = [
                  "leads" => [
                    "add" => [
                      [
                        "id" => $arLead["id"],
                        "account_id" => $arLead["account_id"],
                        "pipeline_id" => $arLead["pipeline"]["id"],
                        "custom_fields" => [
                          [
                            "name" => "Моя сделка"
                          ]
                        ]
                      ]
                    ]
                  ],
                  "account" => [
                    "subdomain" => $arAccount["account"]->subdomain,
                    "id" => "",
                    "login" => $arAccount["account"]->login,
                  ],
                  "subdomain" => $arAccount["account"]->subdomain,
                  "login" => $arAccount["account"]->login,

                ];
                Distribution::where(["account_id" => $arAccount["account"]->id])->update(["cron_update" => strtotime("now + 30 seconds")]);
                $request = new \Illuminate\Http\Request();
                $request->replace($arParam);
                $res[] = $this->distribution($request);
              }
            }
          }
        }
      }
      if (microtime(true) - $start > 60) {
        exit();
      }
      sleep(1);
    }
    return json_encode($res, JSON_UNESCAPED_UNICODE);
  }


  public function distribution(Request $request)
  {
    Log::info("init dist request NEW AJAX", [
      "request" => !empty($request) ? $request : false,
      "post" => !empty($_POST) ? $_POST : false

    ]);
    if ($request->input('hash')) {
      $amo = new AmoCRMClient($request->input("subdomain"), $request->input("login"), $request->input("hash"));
      $arAccountAMO = $amo->account->apiCurrent();
      $account = $this->__addAccount($request);
      if ($account['action'] === 'create') {

        if (!empty($request->input("field_my_lead"))) {
          require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
          $amo = new MW_AMO_CRM;
          $amo->init($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->addFieldLead(["name" => "Моя сделка", "type" => 3]);
        }

        $distribution = Distribution::insert([
          'account_id' => $account['account']->id,
          'fields' => $request->input('libra'),
          'field_my_lead' => $request->input('field_my_lead'),
          "active_widget" => 1,
          "timezone" => $arAccountAMO["timezone"]
        ]);

        $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
        $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/distribution', 'add_lead');

      } else {
        $test_my_lead = Distribution::where(['account_id' => $account['account']->id])->first();
        $result_field_my_lead = $test_my_lead["field_my_lead"];
        if ($test_my_lead["field_my_lead"] != 1 && !empty($request->input("field_my_lead"))) {
          require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
          $amo = new MW_AMO_CRM;
          $amo->init($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->addFieldLead(["name" => "Моя сделка", "type" => 3]);
          $result_field_my_lead = 1;
        }

        if (!empty($test_my_lead)) {
          $distribution = Distribution::where([
            'account_id' => $account['account']->id
          ])->update([
            'fields' => $request->input('libra'),
            'field_my_lead' => $result_field_my_lead,
            "active_widget" => 1,
            "timezone" => $arAccountAMO["timezone"]
          ]);
        } else {
          $distribution = Distribution::insert([
            'account_id' => $account['account']->id,
            'fields' => $request->input('libra'),
            'field_my_lead' => !empty($result_field_my_lead),
            "active_widget" => 1,
            "timezone" => $arAccountAMO["timezone"]
          ]);

          $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/distribution', 'add_lead');
        }
      }
      return response()->json([
        'success' => (bool)$distribution
      ]);
    }
    $lead_id = $request->input('leads.add.0.id');
    $arMyLead = $request->input('leads.add.0.custom_fields');
    $bMyLead = false;
    if (!empty($arMyLead)) {
      foreach ($arMyLead as $arField) {
        if ($arField["name"] == "Моя сделка" || $arField["name"] == "Моя+сделка") {
          if (!empty($arField["values"])) {
            if (gettype($arField["values"]) == "array") {
              if (!empty($arField["values"][0]["value"])) {
                $bMyLead = true;
              }
            } else {
              if (!empty($arField["values"])) {
                $bMyLead = true;
              }
            }
          }
        }
      }
    }


    if (!empty($lead_id) && empty($bMyLead)) {
      if (isset($_POST["ajax"])) {
        return response()->json([
          "success" => "is lead",
        ]);
      }
      /***
       *
       *  Начало манипуляций
       *
       */

      include_once $_SERVER["DOCUMENT_ROOT"]."/miwr/classes/class.distrbution.php";
      $mwDist = new \MIWR\DistributionLog();
      $currentPipeline = $request->input('leads.add.0.pipeline_id');                 // ID воронки
      Log::info("currentPipeline ", [
        "currentPipeline" => !empty($currentPipeline) ? $currentPipeline : false
      ]);
      $iTotalBall = 100000;
      $arError = [
        "initSuccess" => 0,
        ["message" => "Неудалось найти акаунт в базе данных или воронку"],
        ["message" => "Неудалось подключиться к amoCRM"],
        ["message" => "Не найдены поля в базе"],
      ];
      $account = Account::where("subdomain", $request->input('account.subdomain'))->with("distribution")->first();
      if (empty($mwDist->get($account->id, $lead_id))) {
        $responseLog = $mwDist->insert([
          "account_id" => $account->id,
          "lead_id" => $lead_id,
          "type_function" => isset($_POST["ajax"]) ? 1 : 0,
          "run" => 1
        ]);
        if (!empty($account) && !empty($currentPipeline)) {
          $arError["initSuccess"]++;
          $amo = new AmoCRMClient($account->subdomain, $account->login, $account->hash);
          if (!empty($amo)) {                                                                   // Успешное подключение
            $arError["initSuccess"]++;
            $iTotalTasks = [];
            $iExpTask = [];
            if (!empty($account->distribution->fields)) {                                       // Проверка наличия полей для распределение заявок
              $arError["initSuccess"]++;
              $arPipelineUsers = [];                                                            // Массив с данными пользователей для распределения
              $bCalcExpTask = true;
              $iPipelineId = $currentPipeline;
              $arPipeline = $account->distribution->fields[$currentPipeline];
              $iStartBall = 0;
              if (!empty($arPipeline["fields"])) {
                $arPipelineUsers[$iPipelineId] = [
                  "name" => $arPipeline["name"],
                  "considerationExpTasks" => !empty($arPipeline["considerationExpTasks"]) ? $arPipeline["considerationExpTasks"] : 0,
                  "fields" => []
                ];
                foreach ($arPipeline["fields"] as $iUserId => $arUser) {
                  if ($arUser["rate"] == 0) {
                    continue;
                  }
                  if (!empty($bCalcExpTask)) {
                    $arTasks = $amo->task->apiList([
                      "type" => "lead",
                      "responsible_user_id" => (int)$iUserId,
                    ]);
                    $iTotalTasks[$iUserId] = $iExpTask[$iUserId] = 0;
                    foreach ($arTasks as $task) {
                      if (strtotime(date("d-m-Y H:i:s")) >= $task["complete_till"]) {
                        $iExpTask[$iUserId]++;                                                                // Количество просроченных задач
                      }
                      $iTotalTasks[$iUserId]++;                                                               // Количество всего задач
                    }
                  }
                  $iMinusAllBall = ($iTotalBall - ($iTotalBall - ($iTotalBall * ($arUser["rate"] / 100))));   // Фрмируем рейтинг
                  $arPipelineUsers[$iPipelineId]["fields"][$iUserId] =
                    [
                      "id" => $iUserId,
                      "rate" => $arUser["rate"],
                      "randMin" => $iStartBall,
                      "randMax" => ($iStartBall + $iMinusAllBall),
                      "expTasks" => $iExpTask[$iUserId],
                      "totalTasks" => $iTotalTasks[$iUserId]
                    ];
                  $iStartBall += $iMinusAllBall;                                                              // Общее количество баллов
                }
                $arPipelineUsers[$iPipelineId]["allBall"] = $iStartBall;
              }
              $bCalcExpTask = false;
            }
          }
        }
        if ($arError["initSuccess"] < (count($arError["initSuccess"] - 1))) {
          $arError[($arError["initSuccess"] + 1)]["message"];
          Log::info("errors ", [
            "error" => !empty($arError) ? $arError : false
          ]);
          return response()->json([
            'success' => false,
            "error" => !empty($arError) ? $arError : false
          ]);
        } else {
          // Выбираем соотвествующую воронку
          $arCurrentPipeline = !empty($arPipelineUsers[$currentPipeline]) ? $arPipelineUsers[$currentPipeline] : $arPipelineUsers[0];
          $currentUser = false;
          if (!empty($arCurrentPipeline["considerationExpTasks"])) {
            // Формируем рейтинг по количесту просрочек
            $arUserConExpTasks = [];
            $arExpTasks = [];
            foreach ($arCurrentPipeline["fields"] as $arUser) {
              $arExpTasks[] = $arUser["expTasks"];
              $arUserConExpTasks[$arUser["expTasks"]][] = $arUser;
            }
            $iMaxExpTasks = min($arExpTasks);
            if (count($arUserConExpTasks[$iMaxExpTasks]) > 1) {
              $iStartRand = $arCurrentPipeline["allBall"];
              $arCurrentPipeline["allBall"] = 0;
              foreach ($arUserConExpTasks[$iMaxExpTasks] as $arUser) {

                if ($arUser["randMax"] > $arCurrentPipeline["allBall"]) {
                  $arCurrentPipeline["allBall"] = $arUser["randMax"];
                }

                if ($iStartRand > $arUser["randMin"]) {
                  $iStartRand = $arUser["randMin"];
                }
              }
              $iRand = rand($iStartRand, $arCurrentPipeline["allBall"]);
              foreach ($arUserConExpTasks[$iMaxExpTasks] as $arUser) {
                if ($iRand <= $arUser["randMax"] && $iRand >= $arUser["randMin"]) {
                  $currentUser = $arUser;                                                                         // Выбираем пользователя
                  break;
                }
              }
              Log::info("success arCurrentPipeline", [
                'iStartRand' => $iStartRand,
              ]);
            } else {
              $currentUser = $arUserConExpTasks[$iMaxExpTasks][0];
            }
          } else {
            // Формируем рейтинг без учета просрочек
            $iRand = rand(0, $arCurrentPipeline["allBall"]);
            foreach ($arCurrentPipeline["fields"] as $arUser) {
              if ($iRand <= $arUser["randMax"] && $iRand >= $arUser["randMin"]) {
                $currentUser = $arUser;                                                                           // Выбираем пользователя
                break;
              }
            }
          }
          // Выполняем запросы к amoCRM
          if (!empty($currentUser)) {

            include_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";

            $amoMW = new  MW_AMO_CRM();
            $amoMW->init($account->subdomain, $account->login, $account->hash);
            $amo = new AmoCRMClient($account->subdomain, $account->login, $account->hash);
            $lead_all_fields = $amo->lead->apiList(["id" => $lead_id]);
            $lead = $amo->lead;
            $lead["responsible_user_id"] = $currentUser["id"];
            $responseLead = $amoMW->updateLead([
              "id" => (int)$lead_id,
              "responsible_user_id" => $currentUser["id"]
            ]);
            $company = $amo->company;
            $company["responsible_user_id"] = $currentUser["id"];
            $responseCompany = $amoMW->updateCompany([
              "id" => (int)$lead_all_fields[0]["linked_company_id"],
              "responsible_user_id" => $currentUser["id"]
            ]);
            $contact = $amo->contact;
            $contact["responsible_user_id"] = $currentUser["id"];
            $responseContact = $amoMW->updateContacts([
              "id" => (int)$lead_all_fields[0]["main_contact_id"],
              "responsible_user_id" => $currentUser["id"]
            ]);

            $task = $amo->task;
            $task["element_id"] = (int)$lead_id;
            $task["element_type"] = 2;
            $task["task_type"] = 1;
            $task["text"] = "Cвязаться с клиентом";
            $task["responsible_user_id"] = $currentUser["id"];
            $task["complete_till"] = date("d-m-Y 20:59");
            $id = $task->apiAdd();


            Log::info("success user", [
              "responseLead" => $responseLead,
              /*"responseContact" => $responseContact,
              "responseCompany" => $responseCompany,
              "data" => ["task_id" => $id],
              "currentUser" => $currentUser,
              "lead_all_fields" => $lead_all_fields,
              "lead_id" => $lead_id,
              "leadUpdate" =>$lead*/
            ]);


            /**
             * Запись в базу что лид распределился
             */


            return response()->json([
              "success" => $responseLead,
              "data" => ["task_id" => $id]
            ]);

          }
        }
      } else {
        return response()->json([
          "success" => "is lead",
        ]);
      }
    }
  }

  /**
   * AutoTask's controllers
   */

  public
  function saveAutoTask(Requests\StoreAutoTask $request)
  {
    try {
      $account = Account::where('subdomain', $request->input('subdomain'))->first();

      if ($account->autoTask->count() >= 10) {
        return response()->json([
          'error' => 'Вы не можете добавлять больше 10 задач'
        ]);
      }

      $response = AutoTask::create([
        'account_id' => $account->id,
        'pipeline' => $request->input('pipeline'),
        'statuses' => $request->input('statuses'),
        'responsible' => $request->input('responsible'),
        'schedule' => $request->input('schedule'),
        'task_type' => $request->input('task_type'),
        'body' => $request->input('body'),
      ]);

      if ($response) {
        return response()->json([
          'success' => true
        ]);
      } else {
        return response()->json([
          'error' => 'Упс что-то пошло не так'
        ]);
      }
    } catch (\Exception $e) {
      abort(400, $e->getMessage());
    }
  }

  public
  function updateAutoTaskById(Requests\StoreAutoTask $request, Account $account, AutoTask $autotask)
  {
    $autotask = AutoTask::where([
      'account_id' => $account->id,
      'id' => $autotask->id
    ])->first();

    $response = $autotask->update([
      'pipeline' => $request->input('pipeline'),
      'statuses' => $request->input('statuses'),
      'responsible' => $request->input('responsible'),
      'schedule' => $request->input('schedule'),
      'task_type' => $request->input('task_type'),
      'body' => $request->input('body')
    ]);

    return response()->json([
      'response' => $response
    ]);
  }

  public function setTask()
  {
    file_put_contents(storage_path('app/cron.txt'), print_r(date('d.m.Y H:i:s'), true));
    //exit();
    //echo date('D', strtotime('-6 day')); exit();
    $tasks = AutoTask::where('schedule', 'LIKE', '%'.date('D').'%')->distinct()->get([
      'account_id',
      'pipeline',
      'statuses',
      'responsible',
      'schedule',
      'task_type',
      'body'
    ]);

    foreach ($tasks as $autotask) {
      $amo = new AmoCRMClient($autotask->account->subdomain, $autotask->account->login, $autotask->account->hash);
      $leads = $amo->lead->apiList([
        'responsible_user_id' => $autotask->responsible,
        'status' => $autotask->statuses
      ]);

      foreach ($leads as $lead) {
        $task = $amo->task;
        $task['element_id'] = (int)$lead['id'];
        $task['element_type'] = 2;
        $task['task_type'] = $autotask->task_type;
        $task['text'] = $autotask->body;
        $task['responsible_user_id'] = $lead['responsible_user_id'];
        $task['complete_till'] = date('d-m-Y 23:59');
        $task->apiAdd();
      }
    }
  }

  public function getAllAutoTask(Request $request, Account $account)
  {
    return response()->json([
      'total' => $account->autoTask->count(),
      'autotasks' => $account->autoTask
    ]);
  }

  public function getAutoTaskById(Request $request, Account $account, AutoTask $autotask)
  {
    $response = AutoTask::where([
      'account_id' => $account->id,
      'id' => $autotask->id
    ])->first();

    return response()->json([
      'response' => $response
    ]);
  }

  public
  function deleteAutoTaskById(Request $request, Account $account, AutoTask $autotask)
  {
    $response = AutoTask::where([
      'account_id' => $account->id,
      'id' => $autotask->id
    ])->delete();

    return response()->json([
      'response' => (bool)$response
    ]);
  }

  public
  function changeRespStage(Request $request)
  {
    try {
      if ($request->input('hash')) {
        $fields = json_encode([
          'responsible' => $request->input('responsible'),
          'statuses' => $request->input('statuses'),
          'reverse_order_status' => $request->input('reverse_order_status')
        ]);
        $account = $this->__addAccount($request);

        if ($account['action'] === 'create') {
          $respstage = ResponsibleStage::insert([
            'account_id' => $account['account']->id,
            'fields' => $fields,
            'statuses' => $request->input('pipelines')
          ]);

          $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'responsible_lead');
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'status_lead');

        } else {
          $respstage = ResponsibleStage::where([
            'account_id' => $account['account']->id
          ])->update([
            'fields' => $fields,
            'statuses' => $request->input('pipelines')
          ]);

          if (!$respstage) {
            $respstage = ResponsibleStage::insert([
              'account_id' => $account['account']->id,
              'fields' => $fields,
              'statuses' => $request->input('pipelines')
            ]);
            $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
            $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'responsible_lead');
            $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'status_lead');
          }
        }


        return response()->json([
          'success' => (bool)$respstage
        ]);
      }

      if ($request->input('leads.status') || $request->input('leads.responsible')) {
        $account = Account::where('subdomain', $request->input('account.subdomain'))->with('respStage')->first();
        $modified_user_id = $request->input('leads.status.0.modified_user_id') ? $request->input('leads.status.0.modified_user_id') : $request->input('leads.responsible.0.modified_user_id');
        $lead_id = $request->input('leads.status.0.id') ? $request->input('leads.status.0.id') : $request->input('leads.responsible.0.id');
        $action = $request->input('leads.status') ? 'status' : 'responsible';
        $is_not_allowed = false;

        if ($account) {
          $amo = new AmoCRMClient($account->subdomain, $account->login, $account->hash);

          $mylead = $amo->lead->apiList([
            'id' => (int)$lead_id,
          ]);
          /*Log::info("init changeRespStage", [
            "mylead" => !empty($mylead) ? $mylead : false
          ]);
  */
          foreach ($amo->account->apiCurrent()['users'] as $user) {
            if ($user['id'] === $modified_user_id) {
              if ($user['is_admin']) {
                return "skipping";
              }
            }
          }

          if ($action == 'status') {
            $setting_status_id = isset($account->respStage->fields['statuses'][$request->input('leads.status.0.pipeline_id')]) ? $account->respStage->fields['statuses'][$request->input('leads.status.0.pipeline_id')] : null;

            if ($setting_status_id) {
              $new_status_index = null;
              $current_status_index = null;
              $setting_status_index = null;

              foreach ($account->respStage->statuses[$request->input('leads.status.0.pipeline_id')] as $key => $status) {
                if ($status['id'] == $setting_status_id)
                  $setting_status_index = $key;

                if ($status['id'] == $request->input('leads.status.0.status_id'))
                  $new_status_index = $key;

                if ($status['id'] == $request->input('leads.status.0.old_status_id'))
                  $current_status_index = $key;
              }

              if ($account->respStage->fields['reverse_order_status']) {
                //if ($current_status_index >= $new_status_index)
                if ($new_status_index < $current_status_index)
                  $is_not_allowed = true;
                //return "It's not allowed to change the status of the lead to the previous stage";
              }

              if ($new_status_index < $setting_status_index)
                $is_not_allowed = true;
              //return "It's not allowed to change the status of the lead";
            } else
              return 'there is no the status in the settings - skipping';
          } elseif ($action == 'responsible') {
            if (isset($account->respStage->fields['responsible'][$modified_user_id]))
              return "there is id of the user in the settings - skipping";
            else
              $is_not_allowed = true;
          }

          if ($is_not_allowed) {
            /*$log = \App\Models\ResponsibleStageLog::where([
                'subdomain' => $request->input('account.subdomain'),
                'lead_id' => $lead_id,
                'type' => $action
            ])->first();*/

            //if(is_null($log) || ($log->counter % 2 == 0)) {
            /*if(is_null($log)) {
                echo "I am here";
                \App\Models\ResponsibleStageLog::insert([
                    'subdomain' => $request->input('account.subdomain'),
                    'lead_id' => $lead_id,
                    'counter' => 1,
                    'status_id' => @$request->input('leads.status.0.status_id'),
                    'old_status_id' => @$request->input('leads.status.0.old_status_id'),
                    'responsible_user_id' => @$request->input('leads.responsible.0.responsible_user_id'),
                    'old_responsible_user_id' => @$request->input('leads.responsible.0.old_responsible_user_id'),
                    'type' => $action
                ]);
            }
            else {
                echo "I am here2";
                $log->counter = $log->counter + 1;
            $log->save();
            }*/

            $lead = $amo->lead;

            if ($action === 'status') {
              $lead['status_id'] = $request->input('leads.status.0.old_status_id');
            } else {
              $lead['responsible_user_id'] = $request->input('leads.responsible.0.old_responsible_user_id');
            }
            sleep(5);
            $response = $lead->apiUpdate($lead_id, 'now');
            //}
            /*else {
              echo "I am here3";
              $log->counter = $log->counter + 1;
              $log->save();
            }*/
          }
        }
      }

      //file_put_contents(storage_path('app/' . md5(microtime())), print_r($request->all(), true));

    } catch (\Exception $e) {
      Log::error($e->getMessage());
      abort(400, $e->getMessage());
    }
  }

  public
  function getRespStage(Request $request, Account $account)
  {

    if ($request->input('hash')) {
      $fields = json_encode([
        'responsible' => $request->input('responsible'),
        'statuses' => $request->input('statuses'),
        'reverse_order_status' => $request->input('reverse_order_status')
      ]);
      $account = $this->__addAccount($request);

      if ($account['action'] === 'create') {
        $respstage = ResponsibleStage::insert([
          'account_id' => $account['account']->id,
          'fields' => $fields,
          'statuses' => $request->input('pipelines')
        ]);

        $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
        $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'responsible_lead');
        $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'status_lead');

      } else {
        $respstage = ResponsibleStage::where([
          'account_id' => $account['account']->id
        ])->update([
          'fields' => $fields,
          'statuses' => $request->input('pipelines')
        ]);

        if (!$respstage) {
          $respstage = ResponsibleStage::insert([
            'account_id' => $account['account']->id,
            'fields' => $fields,
            'statuses' => $request->input('pipelines')
          ]);
          $amo = new AmoCRMClient($request->input('subdomain'), $request->input('login'), $request->input('hash'));
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'responsible_lead');
          $amo->webhooks->apiSubscribe('https://terminal.linerapp.com/leads/change/respstage', 'status_lead');
        }
      }

      return response()->json([
        'success' => (bool)$respstage
      ]);
    }
    return false;
  }

  private
  function __addAccount($request)
  {
    $request->validate([
      'subdomain' => 'required|string',
      'login' => 'required|string',
      'hash' => 'required|string',
    ]);

    $data = [];
    $account = Account::where('subdomain', $request->input('subdomain'))->first();

    if (is_null($account)) {
      $account = new Account([
        'subdomain' => $request->input('subdomain'),
        'login' => $request->input('login'),
        'hash' => $request->input('hash'),
      ]);

      $account->save();

      $data = ['action' => 'create', 'account' => $account];
    } else {
      $account = Account::where([
        'id' => $account->id
      ])->first();

      $account->update([
        'login' => $request->input('login'),
        'hash' => $request->input('hash')
      ]);

      $data = ['action' => 'update', 'account' => $account];
    }

    return $data;
  }


  public
  function testReq(Request $request)
  {
    Log::info('TEST_REQUEST', [
      'data' => $request
    ]);
    return 1;

  }

}
