<?php
  class api extends common {
    public $user_id;
    public $merch_id;
    public $loc_id;
    public $token;
    public $mode;
    public $view;
    public $exempt = false;
    public $userData = array();
    public $userRoles = array();
    
		public function convert_to_json($data) {
			return json_encode($data, JSON_PRETTY_PRINT);
    }
  }
  include_once("apiCourier.php");
  include_once("apiCustomer.php");
  include_once("apiSite.php");
  include_once("apiStore.php");

  $apiCourier     = new apiCourier;
  $apiCustomer    = new apiCustomer;
  $apiSite        = new apiSite;
  $apiStore       = new apiStore;
?>