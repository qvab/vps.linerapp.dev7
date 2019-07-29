<?php namespace App\Http\Controllers;
header("Content-type: text/html; charset=UTF-8");

define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");

use function MIWR\vd;

use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use AmoCRM\Client as AmoCRMClient;
use MIWR\Account;
use MIWR\CalcField;
use MIWR\Calculator;

class MWCalc
{

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


}
