<?php

function phone_format($phone, $format = "", $mask = '#')
{
  $phone = str_replace(array(")", "(", "-", " "), "", $phone);
  $iLength = strlen($phone);
  var_dump($iLength);
  if ($iLength > 11) {
    if (substr($phone, 0, 1) == "+") {
      $phones["mask"] = "+7(".substr($phone, 2, 3).")".substr($phone, 5, 3)."-".substr($phone, 8, 2)."-".substr($phone, 10, 2);
      $phones["nomask"] = $phone;
      $phones["noplus"] = substr($phone, 1, 10);
      $phones["default"] = "8".substr($phone, 2, 10);
    } else {
      $phones["mask"] = "+7(".substr($phone, 2, 3).")".substr($phone, 5, 3)."-".substr($phone, 8, 2)."-".substr($phone, 10, 2);
      $phones["nomask"] = "+7".substr($phone, 1, 10);
      $phones["noplus"] = "7".substr($phone, 1, 10);
      $phones["default"] = substr($phone, 0, 11);
    }

  } elseif ($iLength == 11) {
    if (substr($phone, 0, 1) == 7) {
      $phones["mask"] = "+7(".substr($phone, 1, 3).")".substr($phone, 4, 3)."-".substr($phone, 7, 2)."-".substr($phone, 9, 2);
      $phones["nomask"] = "+".$phone;
      $phones["noplus"] = $phone;
      $phones["default"] = "8".substr($phone, 1, 10);
    } else {
      $phones["mask"] = "+7(".substr($phone, 1, 3).")".substr($phone, 4, 3)."-".substr($phone, 7, 2)."-".substr($phone, 9, 2);
      $phones["nomask"] = "+7".substr($phone, 1, 10);
      $phones["noplus"] = "7".substr($phone, 1, 10);
      $phones["default"] = $phone;
    }
  } elseif ($iLength < 11) {
    if (substr($phone, 0, 1) == 9) {
      $phones["mask"] = "+7(".substr($phone, 0, 3).")".substr($phone, 3, 3)."-".substr($phone, 6, 2)."-".substr($phone, 8, 2);
      $phones["nomask"] = "+".$phone;
      $phones["noplus"] = $phone;
      $phones["default"] = "8".substr($phone, 0, 10);
    }
  }
  return $phones;
}

?>
<pre>
  <?php print_r(phone_format("+380 999 170 95 13")); ?>
</pre>
