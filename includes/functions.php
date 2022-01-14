<?php
    session_start();
    date_default_timezone_set("America/Toronto");

    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);
	
    $pageUR1      = $_SERVER["SERVER_NAME"];
    $curdomain    = trim( str_replace("www.", "", $pageUR1) );
    $local = false;
    if ($curdomain == "api.instadoor.ca") {
        $URL        = "http://api.Instadoor.ca/";
        $servername = "localhost";
        $dbusername = "instadorAPI_user";
        $dbpassword = "D4j7A3jB";
        $dbname     = "instadorAPI";
    } else {
        $URL        = "http://127.0.0.1/InstaDoorApi/";
        $servername = "localhost";
        $dbusername = "root";
        $dbpassword = "root";
        $dbname     = "InstaDoorApi";
        $local      = true;
    }

    define("u_url", "http://instadoor.ca");
    define("p_url", "http://partner.Instadoor.ca");
    define("c_url", "http://deliver.Instadoor.ca");
    define("a_url", "http://admin.instadoor.ca");

    define("local", $local);

    define("location_api", "cf42cbec51a716");

    $replyMail  = "noreply@Instadoor.ca";
    $ip_address = $_SERVER['REMOTE_ADDR'];

    define("search_radius", 1);
    define("search_radius_me", 0.02);

    //get the current server URL
    define("URL", $URL);
    define('API_URL', URL."api/1.0/");
    //get the database server name
    define("servername",  $servername);
    //get the database server username
    define("dbusername",  $dbusername);
    //get the database server password
    define("dbpassword",  $dbpassword);
    //get the database name
    define("dbname",  $dbname);
    define("replyMail",  $replyMail);
    define("ip_address", $ip_address);

    //initiate the database connection and all models
    include_once("database/main.php");
    include_once("database/init.php");
    $database   = new database;
    $db         = $database->connect();

    //include all the common controller methods
	include_once("controllers/mailer/class.phpmailer.php");
    include_once("controllers/common.php");
    include_once("controllers/alerts.php");
    $common     = new common;
    $alerts     = new alerts;

    include_once("controllers/userStoreRoles.php");
    include_once("controllers/userSiteRoles.php");
    include_once("controllers/usersCourier.php");
    include_once("controllers/usersCustomers.php");
    include_once("controllers/usersSiteAdmin.php");
    include_once("controllers/usersStoreAdmin.php");
    include_once("controllers/category.php");
    include_once("controllers/media.php");
    include_once("controllers/store.php");
    include_once("controllers/orders.php");
    include_once("controllers/cart.php");
    include_once("controllers/transactions.php");
    include_once("controllers/wallet.php");
    include_once("controllers/storeList.php");
    include_once("controllers/inventory.php");
    include_once("controllers/alerts.php");
    include_once("controllers/options.php");
    include_once("controllers/media.php");
    include_once("controllers/tax.php");
    include_once("controllers/cards.php");
    include_once("controllers/bankAccount.php");
    include_once("controllers/tickets.php");
    include_once("controllers/api.php");

    $userStoreRoles     = new userStoreRoles;
    $userSiteRoles      = new userSiteRoles;
    $usersCourier       = new usersCourier;
    $usersCustomers     = new usersCustomers;
    $usersSiteAdmin     = new usersSiteAdmin;
    $usersStoreAdmin    = new usersStoreAdmin;
    $category           = new category;
    $media              = new media;
    $store              = new store;
    $orders             = new orders;
    $cart               = new cart;
    $transactions       = new transactions;
    $wallet             = new wallet;
    $storeList          = new storeList;
    $inventory          = new inventory;
    $options            = new options;
    $media              = new media;
    $tax                = new tax;
    $cards              = new cards;
    $bankAccount        = new bankAccount;
    $tickets            = new tickets;
    $api                = new api;

    $init       = new init;
?>