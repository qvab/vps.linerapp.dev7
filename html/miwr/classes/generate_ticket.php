<?php

namespace LinerrApp;
require_once $_SERVER["DOCUMENT_ROOT"]."/linerapp/config.php";
//require_once PATH_LINER."/classes/class.GDFConverter.php";

class GenerateTicket
{
  private $arColors = [];
  private $arFonts = [
    "segoeui" => PATH_LINER."/fonts/segoeui.ttf",
    "seguisb" => PATH_LINER."/fonts/seguisb.ttf",
    "segoeuib" => PATH_LINER."/fonts/segoeuib.ttf",
    "arial" => PATH_LINER."/fonts/arial.ttf",

  ];
  public $arStrings = [];
  public $arDefaultStr = [
    ["x" => 120, "y" => 158, "size" => 14],
    ["x" => 90, "y" => 188, "size" => 14],
    ["x" => 425, "y" => 188, "size" => 14],
    ["x" => 110, "y" => 218, "size" => 14],
    ["x" => 385, "y" => 218, "size" => 14],
    ["x" => 110, "y" => 248, "size" => 14],
    ["x" => 183, "y" => 277, "size" => 14],
    ["x" => 220, "y" => 307, "size" => 14],
    ["x" => 205, "y" => 337, "size" => 14],
    ["x" => 200, "y" => 367, "size" => 14],
    ["x" => 460, "y" => 123, "size" => 14],
    ["x" => 670, "y" => 123, "size" => 14],

    // Exursion two string
    ["x" => 650, "y" => 323, "size" => 13],
    ["x" => 555, "y" => 342, "size" => 13],

    // right part
    ["x" => 605, "y" => 371, "size" => 14],
    ["x" => 610, "y" => 410, "size" => 14],
    ["x" => 680, "y" => 444, "size" => 14],
  ];

  private $arDefaultDescription = [
    ["x" => 45, "y" => 45, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 45, "size" => 11],
    ["x" => 60, "y" => 65, "size" => 11],

    ["x" => 45, "y" => 100, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 100, "size" => 11],
    ["x" => 60, "y" => 120, "size" => 11],

    ["x" => 45, "y" => 155, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 155, "size" => 11],
    ["x" => 60, "y" => 175, "size" => 11],

    ["x" => 45, "y" => 210, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 210, "size" => 11],
    ["x" => 60, "y" => 230, "size" => 11],


    ["x" => 45, "y" => 265, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 265, "size" => 11],
    ["x" => 60, "y" => 285, "size" => 11],


    ["x" => 45, "y" => 320, "size" => 12, "font" => "segoeuib"],
    ["x" => 60, "y" => 320, "size" => 11],
    ["x" => 60, "y" => 340, "size" => 11],


    ["x" => 45, "y" => 360, "size" => 11, "font" => "segoeuib"],
    ["x" => 45, "y" => 380, "size" => 11, "font" => "segoeuib"],
    ["x" => 45, "y" => 400, "size" => 11, "font" => "segoeuib"],
    ["x" => 45, "y" => 420, "size" => 11, "font" => "segoeuib"],
    ["x" => 45, "y" => 440, "size" => 11, "font" => "segoeuib"],


  ];



  function __construct()
  {
    $rImageMain = imagecreatetruecolor(807, 468);
    $rColorGray = imagecolorallocate($rImageMain, 62, 62, 62);
    $rColorDarkBlue = imagecolorallocate($rImageMain, 70, 98, 134);
    $rColorWhite = imagecolorallocate($rImageMain, 255, 255, 255);
    $this->arColors["gray"] = &$rColorGray;
    $this->arColors["darkBlue"] = &$rColorDarkBlue;
    $this->arColors["white"] = &$rColorWhite;

  }


  public function generateTicket()
  {
    $rImageMain = imagecreatetruecolor(807, 468);
    $rColorGray = imagecolorallocate($rImageMain, 62, 62, 62);
    $rImage = imagecreatefrompng(PATH_LINER."/ticket_sample.png");
    foreach ($this->arStrings as $key => $arString) {
      $arString = array_merge($this->arDefaultStr[$key], $arString);
      $this->__addString($rImage, $arString);
    }
    header("Content-Type: image/png");
    imagepng($rImage);
  }

  public function generateDescription()
  {
    $rImageMain = imagecreatetruecolor(807, 468);
    imagefill($rImageMain, 0, 0, $this->arColors["white"]);
    foreach ($this->arStrings as $key => $arString) {
      $arString = array_merge($this->arDefaultDescription[$key], $arString);
      $arString["font"] = !empty($arString["font"]) ? $arString["font"] : "seguisb";
      $this->__addString($rImageMain, $arString, "darkBlue", $arString["font"]);
    }
    header("Content-Type: image/png");
    imagepng($rImageMain);
  }



  private function __addString(&$rImage, $arString, $sColor = "gray", $sFont = "segoeui")
  {
    imagefttext($rImage, $arString["size"], 0, $arString["x"], $arString["y"], $this->arColors[$sColor], $this->arFonts[$sFont], $arString["text"]);
  }

}








// https://caribs.linerapp.com/linerapp/classes/generate_ticket.php?ticket&s[][text]=Diatchina%20Anna%2079825439966w&s[][text]=Be%20Live%20PC&s[][text]=5002&s[][text]=2&s[][text]=%20&s[][text]=ruso&s[][text]=14.11&s[][text]=19.11&s[][text]=06.05&s[][text]=bus&s[][text]=14442&s[][text]=125&s[][text]=Saona%20classic&s[][text]=%20&s[][text]=59$&s[][text]=118$&s[][text]=Ekaterina

// https://caribs.linerapp.com/linerapp/classes/generate_ticket.php?desc
