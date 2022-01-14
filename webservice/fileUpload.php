<?php
    $nofollow = true;
    include_once("../includes/functions.php");
    if (apache_request_headers()['Authorization']) {
        $uth = explode(" ", apache_request_headers()['Authorization']);
    } else {
        $uth = explode(" ", apache_request_headers()['authorization']);
    }

    $data = file_get_contents('php://input');
    $header['key'] = $_SERVER['HTTP_KEY'];
    $header['longitude'] = $_SERVER['HTTP_LONGITUDE'];
    $header['latitude'] = $_SERVER['HTTP_LATITUDE'];
    $header['method'] = $_SERVER['REQUEST_METHOD'];
    $header['lang'] = $_SERVER['HTTP_LANG'];
    $header['auth'] = trim($uth[1]);

    $request = $_REQUEST['request'];
    
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, DELETE');
    header("Access-Control-Allow-Headers: Authorization, Content-Type, longitude, latitude, key, lang, HTTP_KEY, HTTP_LONGITUDE, HTTP_LATITUDE, HTTP_LANG");

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

    if (!isset($_REQUEST['raw']))  {
        $data = false;
    }
    
    echo $apiClass->prepFiles($header, $request, $data, $_FILES);
?>