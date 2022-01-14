<?php
    include_once("../includes/functions.php");
    $uth = explode(" ", apache_request_headers()['Authorization']);

    $data = file_get_contents('php://input');
    $header['key'] = $_SERVER['HTTP_KEY'];
    $header['longitude'] = $_SERVER['HTTP_LONGITUDE'];
    $header['latitude'] = $_SERVER['HTTP_LATITUDE'];
    $header['address'] = $_SERVER['HTTP_ADDRESS'];
    $header['method'] = $_SERVER['REQUEST_METHOD'];
    $header['lang'] = $_SERVER['HTTP_LANG'];
    $header['auth'] = trim($uth[1]);
    
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, DELETE');
    header("Access-Control-Allow-Headers: Authorization, Content-Type, longitude, latitude, address, key, lang, HTTP_KEY, HTTP_LONGITUDE, HTTP_LATITUDE, HTTP_ADDRESS, HTTP_LANG");

    header('content-type: application/json; charset=utf-8');
    if (isset($_REQUEST['customer'])) {
        $apiClass = $apiCustomer;
    } else if (isset($_REQUEST['courier'])) {
        $apiClass = $apiCourier;
    } else if (isset($_REQUEST['site'])) {
        $apiClass = $apiSite;
    } else if (isset($_REQUEST['partner'])) {
        $apiClass = $apiStore;
    }
    echo $apiClass->prep($header, $_REQUEST['request'], $data);
?>