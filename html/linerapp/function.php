<?php
// https://terminal.linerapp.com/linerapp/index.php
function requestTest()
{
  include_once "app/config.php";
  include_once "app/auth.php";
  include_once "app/leads.php";
  include_once "app/functions.php";
  if ($_POST['action'] == 'add-lead') {
    $auth = new \Auth();
    $out = $auth->getOut();
    $response = json_decode($out, true);
    $response = $response['response'];
    if (isset($response['auth'])) {
      $arrLead = [
        [
          'name' => $_POST['name'],
          'pipeline_id' => 1229323,
          'status_id' => 20488456,
          'custom_fields' => [
            [
              'id' => 462285,
              'values' => [
                [
                  'value' => $_POST['amohash']
                ]
              ]
            ],
            [
              'id' => 462287,
              'values' => [
                [
                  'value' => $_POST['amouser']
                ]
              ]
            ],
            [
              'id' => 462289,
              'values' => [
                [
                  'value' => $_POST['domain']
                ]
              ]
            ],
            [
              'id' => 464563,
              'values' => [
                [
                  'value' => $_POST['id_client']
                ]
              ]
            ],
            [
              'id' => 474525,
              'values' => [
                [
                  'value' => !empty($_POST['phone_contact']) ? $_POST['phone_contact'] : 0
                ]
              ]
            ],

          ]
        ]
      ];

      $leads = new \Leads();
      $res = $leads->createLead($arrLead);
      \writeToLog($res, "Check respons");
      return $res;
    } else {
      return 'error';
    }
  }
}
?>