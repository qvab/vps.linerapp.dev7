<?php

namespace UQLinerApp;
class LACore
{
  protected $resourceConnect;
  public $DB;

  protected function __dbConnect()
  {
    if (empty($this->DB)) {
      $bd_host = "localhost";
      $bd_user = "u349676_laravel";
      $bd_password = "1993mnxa";
      $bd_base = "u349676_laravel";
      $this->DB =  new \mysqli($bd_host, $bd_user, $bd_password, $bd_base);
    }
  }


  protected function dbQuery($sSql) {
    $this->__dbConnect();
    return $this->DB->query($sSql);
  }

}