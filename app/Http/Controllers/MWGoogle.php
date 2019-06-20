<?php namespace App\Http\Controllers;
require $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";
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
use MIWR\ListFiles;
use MIWR\ListFilesGoogle;
use MIWR\TypeFiles;
use MIWR\DocsSetting;

use function MIWR\vd;

define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.list_files.php";
require_once ROOT_MIWR."/class.list_files_google.php";
require_once ROOT_MIWR."/class.type_files.php";
require_once ROOT_MIWR."/class.docs_setting.php";
require_once $_SERVER["DOCUMENT_ROOT"]."/amo.class.php";
class MWGoogle extends Controller
{


  private
    $obGoogleDrive,
    $obGoogle,
    $arFilesList = [],
    $arFolderList = [],
    $sToken,
    $obAccount;

  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
    // Базове соедение с гуглом
    $redirect_uri = "https://terminal.linerapp.com/docs/auth";
    $this->obGoogle = new \Google_Client();
    $this->obGoogle->setClientId("653148332766-n053ik3hjqgci816mv8cm1nkqbd6k2ki.apps.googleusercontent.com");
    $this->obGoogle->setClientSecret("ZMvnLyjqFQvfw7TOHf6evVL2");
    $this->obGoogle->setAccessType("offline");        // offline access
    $this->obGoogle->setApprovalPrompt('force');
    $this->obGoogle->setIncludeGrantedScopes(true);   // incremental auth
    $this->obGoogle->setRedirectUri($redirect_uri);
    $this->obGoogle->addScope(\Google_Service_Drive::DRIVE);
  }


  public function testToken($sSubdomain)
  {
    $this->obAccount = $obAccount = Account::where(["subdomain" => $sSubdomain])->first();
    if (empty($obAccount->token_google_drive)) {
      return json_encode(["token" => 0], JSON_UNESCAPED_UNICODE);
    } else {
      return json_encode(["token" => $obAccount->token_google_drive], JSON_UNESCAPED_UNICODE);
    }
  }

  public function auth($sSubdomain = false, $bRun = false)
  {
    session_start();
    if (!empty($sSubdomain)) {
      $_SESSION["auth_subdomain"] = $sSubdomain;
    } elseif (!empty($_REQUEST["subdomain"])) {
      $_SESSION["auth_subdomain"] = $_REQUEST["subdomain"];
    } elseif (empty($sSubdomain)) {
      $sSubdomain = $_SESSION["auth_subdomain"];
    }

    $this->obAccount = $obAccount = Account::where(["subdomain" => $sSubdomain])->first();
    if (!empty($bRun) && empty($obAccount->token_google_drive)) {
      return "token_null";
    }
    if (!empty($obAccount->token_google_drive) && empty($_REQUEST["code"])) { // ТОкен есть в БАЗЕ
      // Начинаем работу
      //$obAccount->token_google_drive = json_decode($obAccount->token_google_drive, true);
      $this->obGoogle->setAccessToken($obAccount->token_google_drive);
      $this->obGoogleDrive = new \Google_Service_Drive($this->obGoogle);
    } elseif (empty($_REQUEST["code"])) {
      // Возвращаем ссылку для генерации токена
      $sHeader = filter_var($this->obGoogle->createAuthUrl(), FILTER_SANITIZE_URL);
      return "<script> location.replace('".$sHeader."'); </script>";
    } elseif (!empty($_REQUEST["code"])) {
      // Получаем код, генерируем токен и записываем в базу
      $this->sToken = $this->obGoogle->authenticate($_REQUEST["code"]);
      Account::where(["subdomain" => $sSubdomain])
        ->update([
          "token_google_drive" => json_encode($this->sToken, JSON_UNESCAPED_UNICODE)
        ]);
      vd($_SESSION);
      return '<div style="text-align: center; height: 100%;">
  <div style="display: inline-block; vertical-align: middle; height: 100%;"></div><div style="text-align: center; display: inline-block; vertical-align: middle;"><h3>Авторизация прошла успешно!</h3><p>Можете закрыть это окно</p></div>
</div>';
    }
  }


  /**
   * Метод получения файлов из папки диска
   * @param $idFolder = идинтификатор деректории диска
   * @return mixed = массив файлов в категории
   */
  public function getFilesInFolder($idFolder)
  {
    $this->auth($_REQUEST["subdomain"], true); // Авторизация в гугуле
    $listFiles = $this->obGoogleDrive->files->listFiles([
      "fields" => "nextPageToken, files(id, name, parents, fileExtension, mimeType, size, iconLink, thumbnailLink, webContentLink, webViewLink, createdTime)",
      "q" => "'".$idFolder."' in parents and mimeType!='application/vnd.google-apps.folder'",
      "pageSize" => 40
    ]);
    foreach ($listFiles["files"] as $arFile) {
      $this->arFilesList[] = [
        "id" => $arFile->id,
        "name" => $arFile->name,
        "mimeType" => $arFile->mimeType,
        "parents" => $arFile->parents
      ];
    }
    return json_encode($this->arFilesList, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Получене списка деректорий диска
   * @return array = массив деректорий диск
   */
  public function listFolder()
  {
    $sAuth = $this->auth($_REQUEST["subdomain"], true); // Авторизация в гугуле

    $this->obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();
    $obDocsSetting = new DocsSetting();
    $arSetting = $obDocsSetting->get($this->obAccount->id);

    $this->arFolderList[] = [
      "id" => 0,
      "option" => "--Корневая папка диска--"
    ];
    if ($sAuth != "token_null") {
      $listFiles = $this->obGoogleDrive->files->listFiles([
        "fields" => "nextPageToken, files(id, name, parents, fileExtension, mimeType, size, iconLink, thumbnailLink, webContentLink, webViewLink, createdTime)",
        "q" => "mimeType='application/vnd.google-apps.folder'",
        "pageSize" => 40
      ]);
      foreach ($listFiles["files"] as $arFile) {
        $this->arFolderList[] = [
          "id" => $arFile->id,
          "option" => $arFile->name
        ];
      }
      return json_encode(["list" => $this->arFolderList, "current" => !empty($arSetting["parent"]) ? $arSetting["parent"] : 0], JSON_UNESCAPED_UNICODE);
    } else {
      return $sAuth;
    }
  }

  /**
   * Получене списка файлов диска
   * @return array = массив деректорий диск
   */
  public function listFiles()
  {
    $this->auth($_REQUEST["subdomain"], true); // Авторизация в гугуле
    $listFiles = $this->obGoogleDrive->files->listFiles([
      "fields" => "nextPageToken, files(id, name, parents, fileExtension, mimeType, size, iconLink, thumbnailLink, webContentLink, webViewLink, createdTime)",
      "q" => "mimeType!='application/vnd.google-apps.folder'",
      "pageSize" => 40
    ]);
    foreach ($listFiles["files"] as $arFile) {
      $this->arFilesList[] = [
        "id" => $arFile->id,
        "name" => $arFile->name,
        "mimeType" => $arFile->mimeType,
        "parents" => $arFile->parents
      ];
    }
    vd($this->arFilesList);
  }


  /**
   * Метод загрузки файлов на диск
   * @return string = массив загруженных файлов
   */
  public function upload()
  {
    /*vd($_FILES);
    vd($_REQUEST);
    exit();*/
    $this->auth($_REQUEST["subdomain"], true); // Авторизация в гугуле
    $obListFiles = new ListFiles(); // Класс для работы с таблицей файлов
    $obListFilesGoogle = new ListFilesGoogle(); // Класс для работы с таблицей файлов
    $obDocsSetting = new DocsSetting();
    $arSetting = $obDocsSetting->get($this->obAccount->id);

    $idLisFiles = $obListFiles->insert([
      "account_id" => $this->obAccount->id,
      "name" => $_REQUEST["name"],
      "id_amo" => $_REQUEST["id_amo"],
      "date" => strtotime($_REQUEST["date"]),
      "date_attache" => strtotime("now"),
      "id_type" => !empty($_REQUEST["type"]) ? $_REQUEST["type"] : 0,
      "user" => $_REQUEST["user"]
    ]);

    foreach ($_FILES["linerapp-files"]["name"] as $key => $sName) {
      $arParam = [];
      if (!empty($arSetting["parent"])) {
        $arParam["parents"] = [$arSetting["parent"]];
      }
      $arParam["name"] = $_FILES["linerapp-files"]["name"][$key];
      $fileMetadata = new \Google_Service_Drive_DriveFile($arParam);
      $content = file_get_contents($_FILES["linerapp-files"]["tmp_name"][$key]);
      $file = $this->obGoogleDrive->files->create($fileMetadata, [
        "data" => $content,
        "mimeType" => $_FILES["linerapp-files"]["type"][$key],
        "uploadType" => "multipart",
        "fields" => "id",
      ]);

      $requestUpload = $obListFilesGoogle->insert([
        "account_id" => $this->obAccount->id,
        "name" => $_FILES["linerapp-files"]["name"][$key],
        "mimeType" => $_FILES["linerapp-files"]["type"][$key],
        "size" => $_FILES["linerapp-files"]["size"][$key],
        "google_id_file" => $file->id,
        "id_list_files" => $idLisFiles
      ]);
      unlink($_FILES["linerapp-files"]["tmp_name"][$key]); // Удаляет временный файл
      // printf("File ID: %s\n", $file->id);
    }
    $this->updateField($_REQUEST["id_amo"]);

    return json_encode($_FILES, JSON_UNESCAPED_UNICODE);

  }

  /**
   * Получение файлов к сделке в АМО
   * @return string
   */
  public function getFilesLead()
  {
    $arListIds = $arResponseListFiles = $arTypeFiles = [];
    $obListFiles = new ListFiles(); // Класс для работы с таблицей файлов
    $obListFilesGoogle = new ListFilesGoogle(); // Класс для работы с таблицей файлов
    $obTypeFiles = new TypeFiles(); // типы файлов


    $this->obAccount = $obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();
    $obDocsSetting = new DocsSetting();
    $arSetting = $obDocsSetting->get($this->obAccount->id);

    $arFilesLists = $obListFiles->getList([
      "limit" => "LIMIT 0, 40",
      "where" => "WHERE account_id='{$obAccount->id}' AND id_amo = '{$_REQUEST["id_amo"]}'"
    ]);
    $arFilesListsAll = [];
    while ($arFilesListPrev = $obListFiles->fetch($arFilesLists)) {
      $arFilesListsAll[$arFilesListPrev["id_type"]][] = $arFilesListPrev;
    }

    $obTypeFiles->getList([
      "limit" => "LIMIT 0, 100",
      "where" => "WHERE account_id='{$obAccount->id}'",
      "order" => "ORDER BY name ASC"
    ]);

    $arFilesListsAllFinish = [];
    while ($arTypeFile = $obTypeFiles->fetch()) {
      $arTypeFiles[] = [
        "id" => $arTypeFile["id"],
        "option" => $arTypeFile["name"]
      ];

      $arTypeFilesName[$arTypeFile["id"]] = [
        "id" => $arTypeFile["id"],
        "option" => $arTypeFile["name"]
      ];


      if (!empty($arFilesListsAll[$arTypeFile["id"]])) {
        foreach ($arFilesListsAll[$arTypeFile["id"]] as $arList) {
          $arFilesListsAllFinish[] = $arList;
        }
      }
    }



    foreach ($arFilesListsAllFinish as $arFilesList) {
      $arResponseListFiles[$arFilesList["id"]] = $arFilesList;
      $arResponseListFiles[$arFilesList["id"]]["files"] = [];
      $arResponseListFiles[$arFilesList["id"]]["type"] = !empty($arTypeFilesName[$arFilesList["id_type"]]) ? $arTypeFilesName[$arFilesList["id_type"]] : "Н/Д";
      $arResponseListFiles[$arFilesList["id"]]["date"] = date("d/m/Y", $arResponseListFiles[$arFilesList["id"]]["date"]);
      $arResponseListFiles[$arFilesList["id"]]["user"] = $arResponseListFiles[$arFilesList["id"]]["user"];
      $arResponseListFiles[$arFilesList["id"]]["date_attache"] = !empty($arResponseListFiles[$arFilesList["id"]]["date_attache"]) ? date("d/m/Y", $arResponseListFiles[$arFilesList["id"]]["date_attache"]) : "Н/Д";

      $arListIds[] = $arFilesList["id"];
    }
    //vd($arListIds);
    if (!empty($arListIds)) {
      $arFilesGoogleLists = $obListFilesGoogle->getList([
        "limit" => "LIMIT 0, 100",
        "where" => "WHERE id_list_files IN (".implode(",", $arListIds).")"
      ]);

      while ($arFilesGoogleList = $obListFilesGoogle->fetch()) {
        $arResponseListFiles[$arFilesGoogleList["id_list_files"]]["files"][] = $arFilesGoogleList;
      }
    }

    $arSetting["users"] = json_decode($arSetting["users"], JSON_UNESCAPED_UNICODE);
    $arResponseListFiles = array_values($arResponseListFiles);
    return json_encode(["files" => $arResponseListFiles, "types" => $arTypeFiles, "settings" => $arSetting], JSON_UNESCAPED_UNICODE);
  }

  /**
   * Скачиваине файла с гугул диска, и получение данных из базы сервера
   * @param $idFile
   */
  public function getFile($idFile)
  {
    $obListFilesGoogle = new ListFilesGoogle(); // Класс для работы с таблицей файлов
    $arFile = $obListFilesGoogle->get($idFile);
    $this->obAccount = Account::where(["id" => $arFile["account_id"]])->first();
    $this->auth($this->obAccount->subdomain, true); // Авторизация в гугуле

    $obFile = $this->obGoogleDrive->files->get($idFile, [
      'alt' => 'media'
    ]);

    $content = $obFile->getBody()->getContents();

    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$arFile["name"]);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.$arFile["size"]);
    // читаем файл и отправляем его пользователю
    echo $content;
  }


  /**
   * @param $idFileList = id блока файлов
   * @return string = JSON
   */
  public function deleteFile($idFileList)
  {
    $idLead = false;
    $arJSON = [
      "google" => [],
      "googleList" => [],
      "block" => []
    ];
    $this->auth($_REQUEST["subdomain"], true); // Авторизация в гугуле
    $arFilesId = [];
    $obListFilesGoogle = new ListFilesGoogle(); // Класс для работы с таблицей файлов
    $arListGoogle = $obListFilesGoogle->getList([
      "limit" => "LIMIT 0, 60",
      "where" => "WHERE id_list_files = '{$idFileList}'"
    ]);

    while ($arFileGoogle = $obListFilesGoogle->fetch($arListGoogle)) {
      $arFilesId[] = $arFileGoogle["id"];
      $arJSON["google"][] = $this->obGoogleDrive->files->delete($arFileGoogle["google_id_file"]);
    }

    // Удаление блока
    $obListFiles = new ListFiles();
    $idLead = $obListFiles->get($idFileList)["id_amo"];
    $arJSON["block"] = $obListFiles->delete($idFileList);
    // Удаление файлов из таблицы файлов
    $arJSON["googleList"] = $obListFilesGoogle->delete($arFilesId);
    $this->updateField($idLead);
    return json_encode($arJSON, JSON_UNESCAPED_UNICODE);
  }


  /**
   * Метод получения списка типов данных
   * @param $sSubdomain = субдомен клиента
   * @return string = массив данных
   */
  public function getTypes($sSubdomain)
  {
    $arTypes = [];
    $obTypeFiles = new TypeFiles(); // типы файлов
    if (empty($this->obAccount)) {
      $this->obAccount = Account::where(["subdomain" => $sSubdomain])->first();
    }
    $arLists = $obTypeFiles->getList(["limit" => "LIMIT 0, 50", "where" => "WHERE account_id = '{$this->obAccount->id}'"]);
    while ($arType = $obTypeFiles->fetch()) {
      $arTypes[$arType["id"]] = [
        "name" => $arType["name"],
        "type" => $arType["mineType"]
      ];
    }
    return json_encode($arTypes, JSON_UNESCAPED_UNICODE);
  }


  /**
   * Метод удаления типа документа
   * @param $id = идинтификатор типа
   * @return string
   */
  public function delType($id)
  {

    $obTypeFiles = new TypeFiles(); // типы файлов
    $resDel = $obTypeFiles->delete($id);
    return json_encode($resDel, JSON_UNESCAPED_UNICODE);
  }


  /**
   * Добавление нового типа документа
   * @return string
   */
  public function addType()
  {
    if (empty($this->obAccount)) {
      $this->obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();
    }
    $obTypeFiles = new TypeFiles(); // типы файлов
    $res = $obTypeFiles->insert([
      "account_id" => $this->obAccount->id,
      "name" => $_REQUEST["name"]
    ]);

    return json_encode($res, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Добавление или редактирование настрое
   * @return string
   */
  public function addSetting()
  {
    $this->obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();
    $obAmo = new MW_AMO_CRM();
    $obAmo->init($this->obAccount->subdomain, $this->obAccount->login, $this->obAccount->hash);

    $arAmoAccount = $obAmo->getAccount("?with=custom_fields");
    $arFields = !empty($arAmoAccount["response"]["_embedded"]["custom_fields"]["leads"]) ? $arAmoAccount["response"]["_embedded"]["custom_fields"]["leads"] : false;
    $arFieldsNames = [];
    if (!empty($arFields)) {
      foreach ($arFields as $arField) {
        $arFieldsNames[$arField["name"]] = $arField["id"];
      }
    }

    if (!empty($arFieldsNames["Прикрепленные документы"])) {
      // Поле уже создано
      $idField = $arFieldsNames["Прикрепленные документы"];
    } else {
      // Создаем поле
      $arResponse = $obAmo->addFieldLead(["type" => 1, "name" => "Прикрепленные документы"]);
      $idField = $arResponse[0];
    }

    $obDocsSetting = new DocsSetting();
    $arSetting = $obDocsSetting->get($this->obAccount->id);
    if (!empty($arSetting["id"])) {
      $res = $obDocsSetting->update([
        "account_id" => $this->obAccount->id,
        "parent" => $_REQUEST["parent"],
        "users" => $_REQUEST["users"],
        "id_field" => $idField
      ], $arSetting["id"]);
      return json_encode(["update" => 1, "response" => $res], JSON_UNESCAPED_UNICODE);
    } else {
      $res = $obDocsSetting->insert([
        "account_id" => $this->obAccount->id,
        "parent" => $_REQUEST["parent"],
        "users" => $_REQUEST["users"],
        "id_field" => $idField
      ]);
      return json_encode(["insert" => 1, "response" => $res], JSON_UNESCAPED_UNICODE);
    }
  }

  public function updateField($idLead, $sSubdomain = false)
  {
    $obList = new ListFiles();
    $obTypes = new TypeFiles();

    $obAmo = new MW_AMO_CRM();
    $this->obAccount = Account::where(["subdomain" => $_REQUEST["subdomain"]])->first();
    $obAmo->init($this->obAccount->subdomain, $this->obAccount->login, $this->obAccount->hash);
    $res = $obList->getDist("id_type", "WHERE account_id = '{$this->obAccount->id}' AND id_amo = '{$idLead}'");
    $obDocsSetting = new DocsSetting();
    $arSetting = $obDocsSetting->get($this->obAccount->id);

    $arTypeList = [];
    $arTypeAccountList = [];
    $resType = $obTypes->getList(["limit" => "LIMIT 0, 100", "where" => "WHERE account_id='{$this->obAccount->id}'"]);
    while ($arTypeAccount = $obTypes->fetch($resType)) {
      $arTypeAccountList[$arTypeAccount["id"]] = $arTypeAccount["name"];
    }
    while ($arTypes = $obList->fetch($res)) {
      $arTypeList[] = $arTypeAccountList[$arTypes["id_type"]];
    }

    $arLead = $obAmo->getLeadList(["id" => $idLead]);
    $arCurrentLead = !empty($arLead["response"]["_embedded"]["items"][0]) ? $arLead["response"]["_embedded"]["items"][0] : false;
    $resUpdate = $obAmo->updateLead([
      "id" => $arCurrentLead["id"],
      "name" => $arCurrentLead["name"],
      "custom_fields" => [
        [
          "id" => $arSetting["id_field"],
          "values" => [
            [
              "value" => !empty($arTypeList) ? implode(",", $arTypeList) : ""
            ]
          ]
        ]
      ]
    ]);
    return $resUpdate;
  }


}