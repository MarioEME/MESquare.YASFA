<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("__FEED_READER__",1);

/*
$date = new DateTime("Thu, 30 Mar 2017 15:49:09 +1000");
echo $date->format('Y-m-d H:i:s e') . "____" . $date->getTimestamp() . "----";
$date->setTimeZone(new DateTimeZone("UTC"));
echo $date->format('Y-m-d H:i:s e') . "_____" . $date->getTimestamp() . "----";
$date->setTimeZone(new DateTimeZone("Europe/Lisbon"));
die($date->format('Y-m-d H:i:s e') . "_____" . $date->getTimestamp() . "----");
*/
include_once "logger.php";
include_once "feedmanager.php";
include_once "service.php";

$logger = new Logger();
$logger->Info("REQUEST START");

try
{
    $service = new Service($logger);
    $service->execute();
}
catch(Exception $ex)
{
    $logger->Error($ex->getMessage());
    echo json_encode(array(
        "status" => "error",
        "data" => array(
            "message" => "service error, please try later",
            "details" => $ex->getMessage()
    )));
}

$logger->Info("REQUEST END");

?>