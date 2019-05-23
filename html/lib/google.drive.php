<?php header("Content-type: text/html; charset=UTF-8");

function vd($data, $bPrintr = false)
{
  if (!empty($bPrintr)) {
    ?>
    <pre><?php print_r($data); ?></pre><?php
  } else {
    ?>
    <pre><?php var_dump($data); ?></pre><?php
  }
}


require $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";

session_start();
ob_start();
$redirect_uri = "https://terminal.linerapp.com/lib/google.drive.php";
$client = new Google_Client();
$client->setClientId("653148332766-n053ik3hjqgci816mv8cm1nkqbd6k2ki.apps.googleusercontent.com");
$client->setClientSecret("ZMvnLyjqFQvfw7TOHf6evVL2");
$client->setRedirectUri($redirect_uri);
$client->setAccessType("offline");
$client->addScope(Google_Service_Drive::DRIVE);
// Запрос на подтверждение работы с Google-диском
if (isset($_REQUEST["code"])) {
  $token = $client->authenticate($_REQUEST["code"]);
  $_SESSION["accessToken"] = $token;
  header("Location:".filter_var($redirect_uri, FILTER_SANITIZE_URL));
} elseif (!isset($_SESSION["accessToken"])) {
  header("Location:".filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
}
// Присваиваем защитный токен для работы с Google-диском
$client->setAccessToken($_SESSION["accessToken"]);
$driveService = new Google_Service_Drive($client);







function addFile()
{

  $fileMetadata = new Google_Service_Drive_DriveFile([
    "name" => "photo.jpg"
  ]);
  $content = file_get_contents("photo.jpg");
  $file = $driveService->files->create($fileMetadata, array(
    "data" => $content,
    "mimeType" => "image/jpeg",
    "uploadType" => "multipart",
    "fields" => "id"));
  printf("File ID: %s\n", $file->id);

}


class MWGoogleDrive
{

  public
    $google,
    $arFolderList = [],
    $arFilesList = [];

  function __construct($driveService)
  {
    $this->google = $driveService;

  }

  /**
   * Получене списка деректорий диска
   * @return array = массив деректорий диск
   */
  public function listFolder()
  {
    $listFiles = $this->google->files->listFiles([
      "fields" => "nextPageToken, files(id, name, parents, fileExtension, mimeType, size, iconLink, thumbnailLink, webContentLink, webViewLink, createdTime)",
      "q" => "mimeType='application/vnd.google-apps.folder'"
    ]);
    foreach ($listFiles["files"] as $arFile) {
      $this->arFolderList[] = [
        "id" => $arFile->id,
        "name" => $arFile->name,
        "mimeType" => $arFile->mimeType,
        "parents" => $arFile->parents
      ];
    }
    return $this->arFolderList;
  }

  /**
   * Метод получения файлов из папки диска
   * @param $idFolder = идинтификатор деректории диска
   * @return mixed = массив файлов в категории
   */
  public function getFilesInFolder($idFolder)
  {
    $listFiles = $this->google->files->listFiles([
      "fields" => "nextPageToken, files(id, name, parents, fileExtension, mimeType, size, iconLink, thumbnailLink, webContentLink, webViewLink, createdTime)",
      "q" => "'".$idFolder."' in parents"
    ]);
    foreach ($listFiles["files"] as $arFile) {
      $this->arFilesList[] = [
        "id" => $arFile->id,
        "name" => $arFile->name,
        "mimeType" => $arFile->mimeType,
        "parents" => $arFile->parents
      ];
    }
    return $this->arFilesList;
  }

}

$MWGoogle = new MWGoogleDrive($driveService);

//vd($MWGoogle->listFolder());
vd($MWGoogle->getFilesInFolder("1aNea8H-3bRTbsKdsxKAS6SCkwyNbEmqc"));



/*
// Пересобираем массив для добавления ключа parentId
$files = [];
foreach ($listFiles["modelData"]["files"] as $k => $item) {
  $files[$k] = $item;
  $files[$k]["parentId"] = $item["parents"][0];
  unset($files[$k]["parents"]);
}

*/
/*
function getClient()
{
  $client = new Google_Client();
  $client->setApplicationName("Google Drive API PHP Quickstart");
  $client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);
  $client->setAuthConfig("credentials.json");
  $client->setAccessType("offline");
  $client->setPrompt("select_account consent");

  // Load previously authorized token from a file, if it exists.
  // The file token.json stores the user"s access and refresh tokens, and is
  // created automatically when the authorization flow completes for the first
  // time.
  $tokenPath = "token.json";
  if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
  }

  // If there is no previous token or it"s expired.
  if ($client->isAccessTokenExpired()) {
    // Refresh the token if possible, else fetch a new one.
    if ($client->getRefreshToken()) {
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
      // Request authorization from the user.
      $authUrl = $client->createAuthUrl();
      printf("Open the following link in your browser:\n%s\n", $authUrl);
      print "Enter verification code: ";
      $authCode = trim(fgets(STDIN));

      // Exchange authorization code for an access token.
      $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
      $client->setAccessToken($accessToken);

      // Check to see if there was an error.
      if (array_key_exists("error", $accessToken)) {
        throw new Exception(join(", ", $accessToken));
      }
    }
    // Save the token to a file.
    if (!file_exists(dirname($tokenPath))) {
      mkdir(dirname($tokenPath), 0700, true);
    }
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
  }
  return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Drive($client);

// Print the names and IDs for up to 10 files.
$optParams = array(
  "pageSize" => 10,
  "fields" => "nextPageToken, files(id, name)"
);
$results = $service->files->listFiles($optParams);

if (count($results->getFiles()) == 0) {
  print "No files found.\n";
} else {
  foreach ($results->getFiles() as $file) {
    vd($file->id);
    vd($file->name);

  }
}


$fileMetadata = new Google_Service_Drive_DriveFile([
  "name" => "photo.jpg"
]);
$content = file_get_contents("photo.jpg");
$file = $service->files->create($fileMetadata, array(
  "data" => $content,
  "mimeType" => "image/jpeg",
  "uploadType" => "multipart",
  "fields" => "id"));
printf("File ID: %s\n", $file->id);

*/