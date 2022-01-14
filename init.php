<?php
include("includes/functions.php");
if(isset($_REQUEST['request'])) {
    if($_REQUEST['request'] == "initiate") {
        $init->createTables();
    } else if($_REQUEST['request'] == "clear") {
        $init->refresh();
    } else if($_REQUEST['request'] == "remove") {
        $init->remove();
    }
}
?>