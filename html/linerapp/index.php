<?php
	// https://terminal.linerapp.com/linerapp/index.php

	require_once "app/config.php";
	require_once "app/auth.php";
	require_once "app/leads.php";
	require_once "app/functions.php";

	if($_POST['action'] == 'add-lead'){
		$auth = new \Auth();
		$out = $auth->getOut();
		$response=json_decode($out,true);
		$response=$response['response'];
		if(isset($response['auth'])){

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
					]
				]
			];

			$leads = new \Leads();
			$res = $leads->createLead($arrLead);

			writeToLog($res, "Check respons");

			$statusTest = 'ok';

$tema = "Запрос тестового периода";
    $to = 'mishanin-miha@yandex.ru';
    $headers= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: LinerAppWidgets <robot@linerapp.com> \r\n";
    $headers .= "Bcc: mishanin-miha@yandex.ru\r\n";
    $content_client = "name: {$_POST["name"]}<br />
				                   domain: {$_POST["domain"]}<br />";
    mail($to, $tema, $content_client, $headers);


		} else {
      $statusTest = 'error';
		}
	}
?>