<?php

namespace App\Http\Controllers;

use function MIWR\vd;

function currentTimeByZone($sZone = "Europe/Moscow")
{
  $date = new \DateTime("now", new \DateTimeZone($sZone));
  return $date->getTimestamp();
}

function vd2($data, $bPrintr = false)
{
  if (!empty($bPrintr)) {
    ?>
    <pre><?php print_r($data); ?></pre><?php
  } else {
    ?>
    <pre><?php var_dump($data); ?></pre><?php
  }
}


class MW_AMO_CRM
{

  protected $curl;
  public $confing;
  public $errors = [];
  public $debug = [];


  public function errors()
  {
    ?>
    <pre><?php var_dump($this->errors); ?></pre><?php
  }

  public function debug()
  {
    ?>
    <pre><?php var_dump($this->debug); ?></pre><?php
  }

  public function __initHeader($data, $link, $bPost = true, $bAuth = false)
  {
    $this->curl = curl_init();
    if (!empty($this->curl)) {
      curl_reset($this->curl);
    }
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_USERAGENT, "amoCRM-API-client/1.0");
    curl_setopt($this->curl, CURLOPT_URL, $link);
    if (!empty($bPost)) {
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
      curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
    }
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($this->curl, CURLOPT_HEADER, false);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__)."/cookies/".$this->confing["subdomain"]."_cookie.txt");
    if (!empty($bAuth)) {
      curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__)."/cookies/".$this->confing["subdomain"]."_cookie.txt");
    }
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
  }

  public function curlDistribution($arParams)
  {
    $this->__initHeader($arParams, "https://terminal.linerapp.com/leads/distribution", true);
    return $out = curl_exec($this->curl);
  }

  public function init($subdomain = false, $login = false, $hash = false)
  {
    if (empty($this->confing)) {
      $this->confing = [
        "subdomain" => $subdomain,
        "login" => $login,
        "hash" => $hash
      ];
      $user = array(
        "USER_LOGIN" => $login,
        "USER_HASH" => $hash
      );
    } else {
      $user = array(
        "USER_LOGIN" => $this->confing["login"],
        "USER_HASH" => $this->confing["hash"]
      );
    }
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/private/api/auth.php?type=json";
    $this->__initHeader($user, $link, true, true);
    $out = curl_exec($this->curl);
    $curlInfo = curl_getinfo($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    $code = (int)$code;
    $errors = array(
      301 => "Moved permanently",
      400 => "Bad request",
      401 => "Unauthorized",
      403 => "Forbidden",
      404 => "Not found",
      500 => "Internal server error",
      502 => "Bad gateway",
      503 => "Service unavailable"
    );
    $Response = json_decode($out, true);
    if (!empty($Response["response"]["auth"])) {
      $this->debug["init"] = "Авторизация прошла успешно";
      $bAuth = true;
    } else {
      $this->errors["init"] = "Авторизация не удалась";
      $bAuth = false;
    }
    curl_close($this->curl);
    return ["response" => $Response, "code" => $code, "auth" => $bAuth, "info" => $curlInfo];
  }

  private function __addField($type, $data)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/fields";
    $fields["add"] = [
      [
        "name" => $data["name"],
        "field_type" => $data["field_type"],
        "element_type" => $type,
        "origin" => $data["origin"],
        "is_editable" => $data["is_editable"],
        "is_deletable" => $data["is_deletable"],
        "is_visible" => $data["is_visible"]
      ]
    ];
    $this->debug["__addField"] = $fields;
    $this->__initHeader($fields, $link);
  }


  public function addFieldLead($data)
  {
    $this->__addField(2,
      [
        "name" => $data["name"],
        "field_type" => $data["type"],
        "origin" => md5($this->confing["subdomain"])."_linerapp_distr",
        "is_editable" => !empty($data["is_editable"]) ? $data["is_editable"] : 1,
        "is_deletable" => 1,
        "is_visible" => 1,
      ]);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    $code = (int)$code;
    $errors = [
      301 => 'Moved permanently',
      400 => 'Bad request',
      401 => 'Unauthorized',
      403 => 'Forbidden',
      404 => 'Not found',
      500 => 'Internal server error',
      502 => 'Bad gateway',
      503 => 'Service unavailable'
    ];
    $this->debug["addFieldLead"]["code"] = $code;
    $Response = json_decode($out, true);
    $this->debug["addFieldLead"]["response"] = $Response;
    $Response = $Response['_embedded']['items'];
    $output = []; //'ID добавленных полей:'.PHP_EOL;
    foreach ($Response as $v) {
      if (is_array($v)) {
        $output[] = $v["id"]; // .= $v['id'].PHP_EOL;
      }
    }
    curl_close($this->curl);
    return $output;
  }

  public function updateContacts($data)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/contacts";

    $arData["updated_at"] = strtotime("now");
    foreach ($data as $k => $v) {
      $arData[$k] = $v;
    }
    $fields["update"][] = array_merge($arData, $data);
    $this->debug["updateContacts"] = $fields;
    $this->__initHeader($fields, $link);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["code" => $code, "response" => json_decode($out, true)];
  }

  public function updateLead($data)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/leads";
    $arData["updated_at"] = strtotime("now");
    foreach ($data as $k => $v) {
      $arData[$k] = $v;
    }
    $fields["update"][] = array_merge($arData, $data);
    vd($fields);
    $this->debug["updateLead"] = $fields;
    $this->__initHeader($fields, $link);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["code" => $code, "response" => json_decode($out, true)];
  }


  public function updateCompany($data)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/companies";
    $arData["updated_at"] = strtotime("now");
    foreach ($data as $k => $v) {
      $arData[$k] = $v;
    }
    $fields["update"][] = array_merge($arData, $data);
    $this->debug["updateLead"] = $fields;
    $this->__initHeader($fields, $link);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["code" => $code, "response" => json_decode($out, true)];
  }


  public function noteGetList($idLead, $type = false)
  {
    if (!empty($type)) {
      $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/notes?type=lead&note_type=".$type."&limit_rows=100&element_id=".$idLead;
    } else {
      $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/notes?type=lead&limit_rows=100&element_id=".$idLead;
    }
    $arData["updated_at"] = strtotime("now");
    $this->__initHeader([], $link, false);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["response" => json_decode($out, true), "code" => $code];
  }


  public function addNode($arParams)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/notes";
    $arData["updated_at"] = strtotime("now");
    $this->__initHeader($arParams, $link);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["response" => json_decode($out, true), "code" => $code];
  }


  /**
   * @return array
   */
  public function pipelineGetList()
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/pipelines";
    $arData["updated_at"] = strtotime("now");

    $iInc = 0;
    $bFlag = true;
    $arResponses = [];
    while ($bFlag) {
      $iInc++;
      if ($iInc > 9) {
        $bFlag = false;
      }
      $this->__initHeader([], $link, false);
      $out = curl_exec($this->curl);
      $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      $arResponses[] = [
        "content" => json_decode($out, true),
        "code" => $code,
        "inc" => $iInc
      ];
      if ($code != 200 && $code != 204) {
        if ($iInc > 9) {
          return ["error" => 1, "response" => $arResponses];
        }
      } else {
        $bFlag = false;
        return ["response" => json_decode($out, true), "code" => $code];
      }
      sleep(1);
    }
  }


  public function getLeadList($arParams = false)
  {
    if (!empty($arParams)) {
      $sQuery = "?".http_build_query($arParams)."&limit_rows=500";
    } else {
      $sQuery = "";
    }

    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/leads".$sQuery;
    $arData["updated_at"] = strtotime("now");
    $iInc = 0;
    $bFlag = true;
    $arResponses = [];
    while ($bFlag) {
      $iInc++;
      if ($iInc > 9) {
        $bFlag = false;
      }
      $this->__initHeader([], $link, false);
      $out = curl_exec($this->curl);
      $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      $arResponses[] = [
        "content" => json_decode($out, true),
        "code" => $code,
        "inc" => $iInc
      ];
      if ($code != 200 && $code != 204) {
        if ($iInc > 9) {
          return ["error" => 1, "response" => $arResponses];
        }
      } else {
        $bFlag = false;
        return ["response" => json_decode($out, true), "code" => $code];
      }
      sleep(1);
    }
  }


  /**
   * Добавление новой сделки
   * @param $arParams
   * @return array
   */
  public function addLead($arParams)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/leads";
    $arData = [];
    $fields["add"][] = $arParams;
    $iInc = 0;
    $bFlag = true;
    while ($bFlag) {
      $iInc++;
      if ($iInc > 9) {
        $bFlag = false;
      }
      $this->__initHeader($fields, $link);
      $out = curl_exec($this->curl);
      $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      $arResponses[] = [
        "content" => json_decode($out, true),
        "code" => $code,
        "inc" => $iInc
      ];
      if ($code != 200 && $code != 204) {
        if ($iInc > 9) {
          return ["error" => 1, "response" => $arResponses];
        }
      } else {
        $bFlag = false;
        return ["response" => json_decode($out, true), "code" => $code];
      }
      sleep(1);
    }
  }


  public function getContactsList($arParams = false)
  {
    if (!empty($arParams)) {
      $sQuery = "?".http_build_query($arParams);
    } else {
      $sQuery = "";
    }
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/contacts".$sQuery;
    $arData["updated_at"] = strtotime("now");
    $this->__initHeader([], $link, false);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["response" => json_decode($out, true), "code" => $code];
  }


  public function getCompany($idCompany)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/companies?id=".$idCompany;
    $arData["updated_at"] = strtotime("now");
    $this->__initHeader([], $link, false);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["response" => json_decode($out, true), "code" => $code];
  }


  public function getCompanyList($arCompanies)
  {
    if (!empty($arCompanies)) {
      $sQuery = "?".http_build_query($arCompanies)."&limit_rows=500";
    } else {
      $sQuery = "";
    }
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/companies".$sQuery;
    $arData["updated_at"] = strtotime("now");
    $this->__initHeader([], $link, false);
    $out = curl_exec($this->curl);
    $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    return ["response" => json_decode($out, true), "code" => $code];
  }


  public function getAccount($sWith = "")
  {
    set_time_limit(36000);
    $sWith = !empty($sWith) ? $sWith : "";
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/account".$sWith;
    $arData["updated_at"] = strtotime("now");
    $iInc = 0;
    $bFlag = true;
    $arResponses = [];

    while ($bFlag) {
      $iInc++;
      if ($iInc > 9) {
        $bFlag = false;
      }
      if ($iInc > 0) {
        $arAuth = $this->init();
      }
      $this->__initHeader([], $link, false);
      $out = curl_exec($this->curl);
      $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      curl_close($this->curl);
      $arResponses[] = [
        "content" => json_decode($out, true),
        "code" => $code,
        "inc" => $iInc
      ];
      if ($code != 200 && $code != 204) {
        if ($iInc > 9) {
          return ["error" => 1, "response" => $arResponses];
        }
      } else {
        $bFlag = false;
        return ["response" => json_decode($out, true), "code" => $code, "incs" => $iInc];
      }
      sleep(1);
    }
  }


  public function addTask($arData)
  {
    $link = "https://".$this->confing["subdomain"].".amocrm.ru/api/v2/tasks";
    $arParams["add"] = $arData;
    $iInc = 0;
    $bFlag = true;
    while ($bFlag) {
      $iInc++;
      if ($iInc > 9) {
        $bFlag = false;
      }
      $this->__initHeader($arParams, $link);
      $out = curl_exec($this->curl);
      $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      $arResponses[] = [
        "content" => json_decode($out, true),
        "code" => $code,
        "inc" => $iInc
      ];
      if ($code != 200 && $code != 204) {
        if ($iInc > 9) {
          return ["error" => 1, "response" => $arResponses, "confing" => $this->confing];
        }
      } else {
        $bFlag = false;
        return ["response" => json_decode($out, true), "code" => $code, "confing" => $this->confing];
      }
      sleep(1);
    }
  }

}



