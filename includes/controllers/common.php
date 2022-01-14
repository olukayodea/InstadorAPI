<?php
    class common extends database {
		public $roles = [
			"Site_Admin_Roles" => [
				1 => ["group" => "Customer","role" => "Manage Customers"],
				2 => ["group" => "Store Admin","role" => "Manage Store Admins"],
				3 => ["group" => "Store Admin","role" => "Manage Stores"],
				4 => ["group" => "Store Admin","role" => "Approve Store Admins"],
				5 => ["group" => "Store Admin","role" => "Approve Stores"],
				6 => ["group" => "Courier","role" => "Manage Couriers"],
				7 => ["group" => "Courier","role" => "Approve Couriers"],
				8 => ["group" => "Orders","role" => "Manage Orders"],
				9 => ["group" => "Site Admin","role" => "Manage Site Admins"],
				10 => ["group" => "Wallets","role" => "Manage All Wallets"],
				11 => ["group" => "Wallets","role" => "Manage Store Wallets"],
				12 => ["group" => "Wallets","role" => "Manage Courier Wallets"],
				13 => ["group" => "Inventory","role" => "Manage Store Inventory"],
				14 => ["group" => "Payouts","role" => "Manage Payment Requests"],
				15 => ["group" => "Payouts","role" => "Approve Payment Advices"],
				16 => ["group" => "Settings","role" => "Get Settings"],
				17 => ["group" => "Settings","role" => "Update Settings"]
			],
			"Store_Roles" => [
				20 => ["group" => "Store Admin","role" => "Manage Store"],
				21 => ["group" => "Orders","role" => "Accept/Reject Orders"],
				22 => ["group" => "Wallets","role" => "Manage Store Wallets"],
				23 => ["group" => "Inventory","role" => "Manage Store Inventory"],
				24 => ["group" => "Payouts","role" => "Manage Payment Requests"]
			]
		];

		public function stat() {
			// customer
			global $usersCustomers;
			// courier
			global $usersCourier;
			// stores
			global $store;
			global $usersStoreAdmin;
			// inventory
			global $inventory;
			// orders
			global $orders;

			$return['success'] = true;
			$return['customers'] = $this->formartData( $usersCustomers->listAmin(0, 10), "customers");
			$return['courier']['pending'] = $this->formartData($usersCourier->listCOurierAdmin("pending", 0, 10), 'courier');
			$return['courier']['approved'] = $this->formartData($usersCourier->listCOurierAdmin("approved", 0, 10), 'courier');
			$return['courier']['rejected'] = $this->formartData($usersCourier->listCOurierAdmin("rejected", 0, 10), 'courier');
			$return['courier']['active'] = $this->formartData($usersCourier->listCOurierAdmin("active", 0, 10), 'courier');
			$return['courier']['inactive'] = $this->formartData($usersCourier->listCOurierAdmin("inactive", 0, 10), 'courier');
			$return['courier']['all'] = $this->formartData($usersCourier->listCOurierAdmin("", 0, 10), 'courier');
			$return['inventory'] =  $this->formartData($inventory->listAllAdmin(0, 10), 'inventory');
			$return['store']['pending'] = $this->formartData($store->listStoresAmin("pending", 0, 10), 'store');
			$return['store']['pendingUser'] = $this->formartData($usersStoreAdmin->adminPendingStoreList(0, 10), 'storeUsers');
			$return['store']['approved'] = $this->formartData($store->listStoresAmin("approved", 0, 10), 'store');
			$return['store']['rejected'] = $this->formartData($store->listStoresAmin("rejected", 0, 10), 'store');
			$return['store']['online'] = $this->formartData($store->listStoresAmin("online", 0, 10), 'store');
			$return['store']['offline'] = $this->formartData($store->listStoresAmin("offline", 0, 10), 'store');
			$return['store']['all'] = $this->formartData($store->listStoresAmin("", 0, 10), 'store');
			$return['orders']['new'] = $this->formartData($orders->listAllOrdersAdmin("PENDING", 0, 10), 'orders');
			$return['orders']['processing'] = $this->formartData($orders->listAllOrdersAdmin("PROCESSING", 0, 10), 'orders');
			$return['orders']['pickupReady'] = $this->formartData($orders->listAllOrdersAdmin("PROCESSED", 0, 10), 'orders');
			$return['orders']['cancelled'] = $this->formartData($orders->listAllOrdersAdmin("CANCELLED", 0, 10), 'orders');
			$return['orders']['delivered'] = $this->formartData($orders->listAllOrdersAdmin("DELIVERED", 0, 10), 'orders');
			$return['orders']['accepted'] = $this->formartData($orders->listAllOrdersAdmin("DELIVERY-ACCEPTED", 0, 10), 'orders');
			$return['orders']['deliveryOngoing'] = $this->formartData($orders->listAllOrdersAdmin("DELIVERY-ONGOING", 0, 10), 'orders');
			$return['orders']['flagged'] = $this->formartData($orders->listAllOrdersAdmin("FLAGGED", 0, 10), 'orders');
			$return['orders']['past'] = $this->formartData($orders->listAllOrdersAdmin("past", 0, 10), 'orders');
			$return['orders']['all'] = $this->formartData($orders->listAllOrdersAdmin("", 0, 10), 'orders');

			return $return;
		}

		public function formartData ($data, $class) {
			global $usersCustomers;
			global $usersCourier;
			global $usersStoreAdmin;
			global $inventory;
			global $store;
			global $orders;
			
			if ($class == "storeUsers") {
				$data['data'] = $usersStoreAdmin->formatResult($data['data']);
			} else if ($class == "customers") {
				$usersCustomers->admin = true;
				$data['data'] = $usersCustomers->formatResult($data['data']);
			} else if ($class == "courier") {
				$usersCourier->admin = true;
				$data['data'] = $usersCourier->formatResult($data['data']);
			} else if ($class == "inventory") {
				$inventory->admin = true;
				$data['data'] = $inventory->formatResult($data['data']);
			} else if ($class == "store") {
				$store->admin = true;
				$data['data'] = $store->formatResult($data['data']);
			} else if ($class == "orders") {
				$orders->admin = true;
				$data['data'] = $orders->formatResult($data['data']);
			}

			return $data;
		}

		public function publicRole($key) {
			$roles = array_keys( $this->roles[$key] );

			foreach ($roles as $role) {
				$roleData["group"] = $this->roles[$key][$role]['group'];
				$roleData["role"] = $this->roles[$key][$role]['role'];
				$roleData["role_id"] = $role;

				$data[] = $roleData;
			}

			return $data;
		}

		public function curl_file_get_contents($url) {
			if(strstr($url, "https") == 0) {
				return $this->curl_file_get_contents_https($url);
			}
			else {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($ch);
				curl_close($ch);
				return $data;
			}
		}
		
		public function curl_file_get_contents_https($url) {
			$res = curl_init();
			curl_setopt($res, CURLOPT_URL, $url);
			curl_setopt($res,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($res, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($res, CURLOPT_SSL_VERIFYPEER, false);
			$out = curl_exec($res);
			curl_close($res);
			return $out;
        }
        
		public function get_prep($value) {
			$value = urldecode(htmlentities(strip_tags($value)));
			
			return $value;
		}
		
		public function get_prep2(&$item) {
			$item = htmlentities($item);
		}
		
		public function out_prep($array) {
			if (is_array($array)) {
				if (count($array) > 0) {
					array_walk_recursive($array, array($this, 'get_prep2'));
				}
			}
			return $array;
        }
        
        //send emails
		public function send_mail($from,$to,$subject,$body) {
			$headers = '';
			$headers .= "From: $from\r\n";
			$headers .= "Reply-to: ".replyMail."\r\n";
			$headers .= "Return-Path: ".replyMail."\r\n";
			$headers .= "Organization: SkrinAd\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
			$headers .= "Date: " . date('r', time()) . "\r\n";
		
			if (mail($to,$subject,$body,$headers)) {
				return true;
			} else {
				return false;
			}
			
			// $from_data = explode("<", trim($from, ">"));
			// $to_data = explode("<", trim($to, ">"));
			// $to_email = $to_data[1];
			// $to_name = $to_data[0];
			// $mail = new PHPMailer();
			// $mail->IsSMTP();
			// //$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
			// $mail->SMTPAuth = true; // authentication enabled
			// $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
			// $mail->Host = "a2plcpnl0162.prod.iad2.secureserver.net";
			// $mail->Port = 465; // or 587
			
			// $mail->Username = "noreply@Instadoor.ca";  // SMTP username
            // $mail->Password = "Y_X$N#iah)lW"; // SMTP password
			
			// $mail->From = $from_data[1];
			// $mail->FromName = $from_data[0];
			// $mail->AddAddress($to_email,$to_name);                  // name is optional
			// $mail->AddReplyTo($from_data[1], $from_data[0]);  
			
			// $mail->WordWrap = 50;                                 // set word wrap to 50 characters
            // $mail->IsHTML(true);                                  // set email format to HTML
            // $mail->SMTPOptions = array(
            //     'ssl' => array(
            //     'verify_peer' => false,
            //     'verify_peer_name' => false,
            //     'allow_self_signed' => true
            //     )
            //     );
			
			// $mail->Subject = $subject;
			// $mail->Body    = $body;
			// $mail->AltBody = "This is email is readable only in an HTML enabled browser or reader";
			
			// if(!$mail->Send()) {
			// 	echo "Mailer Error: " . $mail->ErrorInfo;
			// 	error_log("could not send");
			// 	return false;
			// } else {
			// 	return true;
			// }
        }
		
		public function hashPass($string) {
			$count = strlen($string);
			$start = $count/2;
			$list = "";
			for ($i = 0; $i < $start; $i++) {
				$list .= "*";
			}
			$hasPass = substr_replace($string, $list, $start);
			
			return $hasPass;
		}
		
		public function initials($string, $lenght=1) {
			$string = trim($string);
			$words = explode(" ", $string);
			$words = array_filter($words);
			$letters = "";
			foreach ($words as $value) {
				$letters .= strtoupper(substr($value, 0, $lenght)).". ";
			}
			$letters = trim(trim($letters), ".");
			
			return $letters;
		}
		
		public function getExtension($str) {
			$i = strrpos($str,".");
			if (!$i) { return ""; } 
			$l = strlen($str) - $i;
			$ext = substr($str,$i+1,$l);
			return $ext;
		}
		
		public function createRandomPassword($len = 7) { 
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; 
			srand((double)microtime()*1000000); 
			$i = 0; 
			$pass = '' ; 
			$count = strlen($chars);
			while ($i <= $len) { 
				$num = rand() % $count; 
				$tmp = substr($chars, $num, 1); 
				$pass = $pass . $tmp; 
				$i++; 
			} 
			return $pass; 
		}

		public function http2https() {
			//If the HTTPS is not found to be "on"
			if(!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
				//Tell the browser to redirect to the HTTPS URL.
				header("HTTP/1.1 301 Moved Permanently"); 
				header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
				//Prevent the rest of the script from executing.
				exit;
			}
		}

		public function findAddress($array) {
			$query['key'] = location_api;
			$query['lat'] = $array['longitude'];
			$query['lon'] = $array['latitude'];
			$query['zoom'] = 18;
            $query['format'] = "json";
            

            $url = "https://us1.locationiq.com/v1/reverse.php?".http_build_query($query);

            $headers[] = "Content-Type: application/json";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            $output = curl_exec($ch);
            curl_close($ch);
			$data = json_decode($output, true);

			return $data;
		}

		public function getAddress($string) {
			$query['key'] = location_api;
			$query['q'] = $string;
			$query['format'] = "json";
			$query['limit'] = 1;
			
	
			$url = "https://us1.locationiq.com/v1/search.php?".http_build_query($query);
	
			$headers[] = "Content-Type: application/json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			$output = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($output, true);

			return $data[0];
		}

		public function addS($word, $count) {
			if ($count > 1) {
				$two = substr($word, -2); 
				$one = substr($word, -1); 
				if (($two == "ss") || ($two == "sh") || ($two == "ch")) {
					return $word."es";
				} else if (($one == "s") || ($one == "x") || ($one == "z")) {
					return $word."es";
				} else if ($two == "lf") {
					return $word."ves";
				} else if ($two == "ay") {
					return $word."s";
				} else if ($one == "y") {
					return $word."ies";
				} else {
					return $word."s";
				}
			} else {
				return $word;
			}
		}
		
		public function numberPrintFormat($value) {
			if ($value > 999 && $value <= 999999) {
				$result = round(($value / 1000), 2) . ' K';
			} elseif ($value > 999999 && $value < 999999999) {
				$result = round(($value / 1000000), 2) . ' M';
			} elseif ($value > 999999999) {
				$result = round(($value / 1000000000), 2) . ' B';
			} else {
				$result = $value;
			}
			
			return $result;
		}

		public function get_time_stamp($post_time) {
			if (($post_time == "") || ($post_time <1)) {
				return false;
			} else {
				$difference = time() - $post_time;
				$periods = array("sec", "min", "hour", "day", "week",
				"month", "years", "decade","century","millenium");
				$lengths = array("60","60","24","7","4.35","12","10","100","1000");
				
				if ($difference >= 0) { // this was in the past
					$ending = "ago";
				} else { // this was in the future
					$difference = -$difference;
					$ending = "time";
				}
				
				for($j = 0; $difference >= $lengths[$j]; $j++)
				$difference = $difference/$lengths[$j];
				$difference = round($difference);
				
				if($difference != 1) $periods[$j].= "s";
				$text = "$difference $periods[$j] $ending";
				return $text;
			}
		}

		public function print_time($timestamp) {
			if (intval($timestamp) > 0) {
				return date("Y-m-d h:i:s", intval($timestamp));
			} else {
				return "";
			}
		}

		public function distance($lat1, $lon1, $lat2, $lon2) {
			global $options;
			$time_vale = floatval($options->get("time_vale"));
			$pi80 = M_PI / 180;
			$lat1 *= $pi80;
			$lon1 *= $pi80;
			$lat2 *= $pi80;
			$lon2 *= $pi80;
		
			$r = 6372.797;
			$dlat = $lat2 - $lat1;
			$dlon = $lon2 - $lon1;
			$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
			$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
			$km = $r * $c;
			$mile = $km/1.609344;
		
			return array( "metric"=> array("value"=> floatval( round($km, 2) ), "unit"=>"Km", "time"=>ceil(($km*$time_vale))." ".$this->addS("min", ceil(($km*$time_vale)))), "imperial"=> array("value"=>floatval( round($mile, 2) ), "unit"=>"Miles", "time"=>ceil(($mile*$time_vale))." ".$this->addS("min", ceil(($mile*$time_vale)))));
		}

		function getAccessRight($products, $field, $value) {
			foreach($products as $key => $product) {
				if ( $product[$field] == $value ) {
					return $key;
				}
			}
			return false;
		}

		public function search( $query, $location ) {
			global $inventory;
			global $store;
			$inventory->string = $query;
			$return = $inventory->retrieveAPI( "search" );

			$result = array();

			if ($return['success'] === true) {
				foreach ($return['data'] as $row ) {
					$result[] = array("type"=>"item", "ref"=>$row['ref'], "name"=>$row['name']);
				}
			}

			$store->location = $location;
			$store->search = $query;
			$return = $store->getAPI( );

			if ($return['success'] === true) {
				foreach ($return['data'] as $row ) {
					$result[] = array("type"=>"store", "ref"=>$row['ref'], "name"=>$row['name']);
				}
			}

			return $result;
		}
	}
?>