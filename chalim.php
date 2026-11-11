<?php
$bot_token ="";
$sudo_port = "";
$dev_id = "@YtonriIR";
$dev_name = "@YtonriIR";
$sudo_user = [""];
$id_sender = "";

date_default_timezone_set("Asia/Tehran");

$start_date = "";
$end_date   = "";

$today = strtotime(date("Y/m/d"));
$expire = strtotime($end_date);
$days_left = ceil(($expire-$today)/86400);
if($days_left<0) $days_left=0;

$dateBuy = $start_date;
$expir = $end_date." (".$days_left." Day)";

$file = "emad-44-firebase-adminsdk-fbsvc-cf17551ae6.json";
$project_id = "emad-44";
?>