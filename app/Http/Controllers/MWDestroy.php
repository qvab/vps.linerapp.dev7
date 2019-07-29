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
use MIWR\License;
use function MIWR\vd;

if (!defined("ROOT_MIWR")) {
  define("ROOT_MIWR", $_SERVER["DOCUMENT_ROOT"]."/miwr/classes");
}
require_once ROOT_MIWR."/core.php";
require_once ROOT_MIWR."/class.license.php";

class MWDestroy extends Controller
{
  public function __construct()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Content-type: text/html; charset=UTF-8");
  }

  public function run($w_code) {

    return 1;
  }
}