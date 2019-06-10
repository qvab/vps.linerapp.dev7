<?php
/**
 * Created by PhpStorm.
 * User: work-1
 * Date: 20.05.2019
 * Time: 14:48
 */

$subdomian =  trim($_GET["user"]);
$w_code =  $_GET["w_code"];
$date = strtotime("now");
$date_text = date("Y-m-d", strtotime("now"));
preg_match('/[a-zA-Z0-9\-]{1,99}/', $subdomian, $match);

$link = mysql_connect("localhost", "u349676_laravel", "1993mnxa");

mysql_select_db("u349676_laravel");

if((empty($subdomian) == false) and ($subdomian == $match[0])) {
$query = sprintf('INSERT INTO generate_keys (subdomain, w_code, date, date_text) VALUES("%s","%s","%s","%s")',
                                                        mysql_real_escape_string($subdomian),
                                                        mysql_real_escape_string($w_code),
                                                        mysql_real_escape_string($date),
                                                        mysql_real_escape_string($date_text));


$result = mysql_query($query, $link) or die(mysql_error());

mysql_close($link);



echo $result;

}