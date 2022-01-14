<?php
class store extends common {
    public $data = array();
    public $id;
    public $user_id;
    public $search;
    public $location = array();
    public $result = array();
    public $return = array();

    public $user = false;
    public $admin = false;
    public $minimal = false;
    
    public function add($data) {
        global $options;
        $this->forwardGeoLocate(trim($data['address']). " ".trim($data['city']). " ".trim($data['province']). " ".trim($data['post_code']));

        $value_array = array('name' => $data['name'],
        'address' => $data['address'],
        'city' => $data['city'],
        'province' => $data['province'],
        'post_code' => $data['post_code'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'logo' => $data['logo'],
        'url' => $data['url'],
        'commission' => intval($options->get("default_commission")),
        'longitude' => $this->location['longitude'],
        'latitude' => $this->location['latitude'],
        'created_by' => $this->user_id,
        'verified' => 1,
        'status' => "INACTIVE");

        if ($data['ref'] != "") {
            $value_array['ref'] = $data['ref'];
        }

        $replace[] = "name";
        $replace[] = "address";
        $replace[] = "city";
        $replace[] = "province";
        $replace[] = "post_code";
        $replace[] = "email";
        $replace[] = "phone";
        $replace[] = "url";
        $replace[] = "logo";
        $replace[] = "longitude";
        $replace[] = "latitude";

        $this->id = $this->replace("store", $value_array, $replace);

        if ($this->id) {
            global $storeList;
            global $userStoreRoles;
            global $media;
            $storeList->store_id = $this->id;
            $storeList->add();

            $media->findID($data['logo'], $this->id);

            $roles = array_keys( $this->roles['Store_Roles'] );
            foreach ( $roles as $role ) {
                $userStoreRoles->role_id = $role;
                $userStoreRoles->store_id = $this->id;
                $userStoreRoles->user_id = $this->user_id;

                $userStoreRoles->addRole();
            }

            $userStoreRoles->store_id = $this->id;
            $userStoreRoles->user_id = $this->user_id;
            $userStoreRoles->modifyInitial();
            return true;
        }
    }

    public function addUser($data) {
        global $usersStoreAdmin;
        // add user to storesList

        $storeData = $this->listOne($data['store_id']);

        // if ($storeData['verified'] == 2) {
            if( $this->findInStore() ) {
                $create = $usersStoreAdmin->addUser($data);
                if ($create) {
                    return true;
                } else {
                    $this->return['success'] = false;
                    $this->return['error']['code'] = 10004;
                    $this->return['error']["message"] =  "Conflict. seems this account already exist, Please create this account with another email address";
                }
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11001;
                $this->return['error']["message"] = "Conflict. seems this account already exist and belongs to this store, Please create this account with another email address";
            }
            
            return $this->return;
        // }  else if ($storeData['verified'] == 1) {
        //     return null;
        // } else {
        //     return false;
        // }
    }

    public function edit($data) {
        $data['ref'] = $this->id;

        if (intval($data['commission']) < 1) {
            $data['commission'] = null;
        }

        if ((isset($data['address'])) && (isset($data['city'])) && (isset($data['province']))) {
            $this->forwardGeoLocate(trim($data['address']). " ".trim($data['city']). " ".trim($data['province']). " ".trim($data['post_code']));
        }
        $replace = array_keys( $data );

        $this->id = $this->replace("store", $data, $replace);
        if ($this->id) {
            return $this->formatResult( $this->listOne( $this->id ), true, false );
        }
    }

    public function remove() {
        global $storeList;
        global $userStoreRoles;
        $dontDelete = false;
        if ($this->checkData()) {
            $dontDelete = true;
        }

        if ($dontDelete === true) {
            $return = $this->modifyOne("status", "DELETED", $this->id);
        } else {
            $return = $this->delete("store",  $this->id);
        }

        if ($return) {
            $storeList->remove($this->id, "store_id");
            $userStoreRoles->removeRole($this->id, "store_id");
            return true;
        }
    }

    /**
     * Check if the store has other data in other tables, if there is data @return true else @return false
     */
    private function checkData() {
        return false;
    }

    public function toggleStatus() {
        $data = $this->listOne($this->id);

        if ($data) {
            if ($data['verified'] == 2)  {
                if ($data['status'] == "ACTIVE") {
                    $updateData = "INACTIVE";
                } else if ($data['status'] == "INACTIVE") {
                    $updateData = "ACTIVE";
                }
                return $this->modifyOne("status", $updateData, $this->id);
            } else if ($data['verified'] == 1)  {
                return "pending_verification";
            } else if ($data['verified'] == 3)  {
                return "verification_failed";
            } else {
                return "unverified";
            }
        } else {
            return false;
        }
    }

    public function verifyStore($data) {
        global $usersSiteAdmin;
        global $alerts;
        $storeData = $this->listOne($this->id);
        if ($storeData) {
            if ($data['response'] == "yes") {
                $verified = 2;
                $active = 'ACTIVE';
                $message = "Thank you for choosing nstaDoor, Your store has been verified and approved, you can now login and start managing the store and recieving orders";
            } else {
                $verified = 3;
                $active = 'INACTIVE';
                $message = "Thank you for choosing nstaDoor, unfortunately Your store has not been verified and approved at this time, you will not be  able to manage this store at this time, you can contact the administrator to learn more";
            }

            if ( $this->modifyOne("verified", $verified, $this->id) ) {
                $this->modifyOne("status", $active, $this->id);
                $userData = $usersSiteAdmin->listOne($storeData['created_by']);
    
                $client = $userData['fname']." ".$userData['lname'];
                $subjectToClient = "RE: Store Verification Notification";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($userData['lname']).
                    '&fname='.urlencode($userData['fname']).
                    '&email='.urlencode($userData['email']).
                    '&tag='.urlencode($message);
                $mailUrl = URL."includes/views/emails/notification.php?store&".$fields;
                
                $messageToClient = $this->curl_file_get_contents($mailUrl);
    
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$userData['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                $alerts->sendEmail($mail);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getDetails($id, $tag="ref") {
        return $this->getOne("store", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("store", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("store", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("store", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("store", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("store", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function getStoreList($longitude, $latitude, $start, $limit) {
        $min_lat = $latitude - search_radius;
        $max_lat = $latitude + search_radius;

        $min_long = $longitude - search_radius;
        $max_long = $longitude + search_radius;
        
        //query for result
        $query = "SELECT `store`.`ref`, `store`.`name`, `store`.`address`, `store`.`city`, `store`.`province`, `store`.`post_code`,`store`.`email`, `store`.`phone`, `store`.`url`, `store`.`logo`, `store`.`longitude`, `store`.`latitude`, SQRT(((`latitude` - ".$latitude.")*(`latitude` - ".$latitude.")) + ((`longitude` - ".$longitude.")*(`longitude` - ".$longitude."))) AS  `total`, `store`.`created_by` FROM `store` INNER JOIN `inventory` ON `store`.`ref` = `inventory`.`store_id` WHERE `store`.`status` = 'ACTIVE' AND `store`.`verified` = 2 AND `store`.`latitude` BETWEEN ".$min_lat." AND ".$max_lat." AND `store`.`longitude` BETWEEN ".$min_long." AND ".$max_long." ORDER BY `total` ASC, `store`.`ref` DESC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT * FROM `store` INNER JOIN `inventory` ON `store`.`ref` = `inventory`.`store_id` WHERE `store`.`status` = 'ACTIVE' AND `store`.`verified` = 2 AND `store`.`latitude` BETWEEN ".$min_lat." AND ".$max_lat." AND `store`.`longitude` BETWEEN ".$min_long." AND ".$max_long;
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function findRemote($string, $location) {
        $min_lat = $location['latitude'] - search_radius;
        $max_lat = $location['latitude'] + search_radius;

        $min_long = $location['longitude'] - search_radius;
        $max_long = $location['longitude'] + search_radius;

        //query for result
        $query = "SELECT `ref`, `name`, `address`, `city`, `province`, `post_code`, `email`, `phone`, `url`, `logo`, `longitude`, `latitude`, SQRT(((`latitude` - ".$location['latitude'].")*(`latitude` - ".$location['latitude'].")) + ((`longitude` - ".$location['longitude'].")*(`longitude` - ".$location['longitude']."))) AS `total`, `created_by` FROM `store` WHERE `status` = 'ACTIVE' AND `verified` = 2 AND `latitude` BETWEEN ".$min_lat." AND ".$max_lat." AND `longitude` BETWEEN ".$min_long." AND ".$max_long." AND (`name` LIKE '%".$string."%' OR `address` LIKE '%".$string."%' OR `url` LIKE '%".$string."%' OR `post_code` LIKE '%".$string."%' OR `phone` LIKE '%".$string."%') ORDER BY `total` ASC, `ref` DESC";
        return $this->query($query, false, "list");
    }

    public function forwardGeoLocate($string) {
        $query['key'] = location_api;
        $query['q'] = $string;
        $query['limit'] = 1;
        $query['bounded'] = 1;
        $query['format'] = "json";
        
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
        
        $this->location['display_name'] = $data[0]['display_name'];
        $this->location['latitude'] = $data[0]['lat'];
        $this->location['longitude'] = $data[0]['lon'];
    }

    public function storeCode($id) {
        return strtoupper(substr( $this->listOneValue( $id, "name" ), 0, 2).(1000000+intval( $id ) ));
    }

    private function stroeStat($id) {
        global $orders;
        global $wallet;
        global $inventory;
        global $storeList;

        $return =  $orders->storeCount($id);
        $return['earning'] = number_format($wallet->total($id), 2);
        $return['inventory'] = number_format($inventory->storeCount($id));
        $return['users'] = number_format($storeList->storeCount($id));

        return $return;
    }

    public function formatResult($data, $single=false, $distance=true) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->clean($data[$i], $distance);
                }
            } else {
                $data = $this->clean($data, $distance);
            }
        } else {
            return [];
        }
        return $data;
    }

    private function clean($data, $distance) {
        global $category;
        global $usersStoreAdmin;
        global $wallet;
        global $media;
        global $inventory;
        global $tickets;
        if (($data['verified'] != 2) && ($data['status'] == "ACTIVE")) {
            $this->modifyOne("status", "INACTIVE", $data['ref']);
        }
        $data['ref'] = intval( $data['ref'] );
        $data['code'] = $this->storeCode( $data['ref'] );

        $data['links'] = array("url"=>$data['url'], "logo"=>$media->checkValid($data['logo']));

        if (($this->admin === true) || ( ($distance === false) && ($this->user === false) ) ) {
            $data['location'] = array("latitude" => $data['latitude'], "longitude" => $data['longitude'] );
        } else {
            $data['location'] = $this->distance( $this->location['latitude'], $this->location['longitude'], $data['latitude'], $data['longitude'] );
        }

        if ($this->minimal === false) {
            if ($this->admin === true) {
                $data['counts'] = $this->stroeStat( $data['ref'] );
            }

            if ($this->user == false) {
                if ($this->admin === true) {
                    $data['wallet']['balance'] = $wallet->balance($data['ref'], "store");
                    $data['wallet']['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                } else {
                $usersStoreAdmin->user_id = $this->user_id;
                    if  ($this->getAccessRight($usersStoreAdmin->getRoles(), "role_id", 22)) {
                        $data['wallet']['balance'] = $wallet->balance($data['ref'], "store");
                        $data['wallet']['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                    }
                }
            }

            if (1 == $data['verified']) {
                $data['verified'] = "pending";
            } else if (2 == $data['verified']) {
                $data['verified'] = "approved";
            } else if (3 == $data['verified']) {
                $data['verified'] = "rejected";
            } else {
                $data['verified'] = "none";
            }

            $data['category'] = $category->formatResult( $category->query("SELECT * FROM `category` WHERE `store_id` = ".$data['ref']." AND `status` != 'DELETED'", false, "list") );

            $account['online'] = ("ACTIVE" == $data['status']) ? true : false;
            $account['offline'] = ("INACTIVE" == $data['status']) ? true : false;
            $account['archived'] = ("DELETED" == $data['status']) ? true : false;
            if ($this->user == false) {
                $tickets->user_id = $data['ref'];
                $data['tickets'] = $tickets->getCounts("store_id");
            } else {
                $inventory->store_id = $data['ref'];
                $data['mainItem'] = $inventory->getRandomStore();
            }
            $data['storeStatus'] = $account;
            $data["createdBy"] = $usersStoreAdmin->userData( $data['created_by'] );
        }
        $data['creationDate'] = strtotime( $data['create_time'] );
        $data['lastModified'] = strtotime( $data['modify_time'] );

        unset($data['created_by']);
        unset($data['latitude']);
        unset($data['longitude']);
        unset($data['total']);
        unset($data['logo']);
        unset($data['url']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['modify_time']);

        return $data;
    }

    public function finInStore($store, $user) {
        return $this->query("SELECT `ref` FROM `storeList` WHERE `store_id` = ".$store." AND `user_id` = ".$user, false, "getCol");
    }

    private function findInStore() {
        return $this->query( "SELECT `store`.`ref`, `store`.`name`, `store`.`address`, `store`.`city`, `store`.`province`, `store`.`post_code`, `store`.`email`, `store`.`phone`, `store`.`url`, `store`.`logo`, `store`.`longitude`, `store`.`latitude` FROM `store` INNER JOIN `storeList` ON `store`.`ref` = `storeList`.`store_id` WHERE `store`.`status` != 'DELETED' AND `store`.`verified` = 2 AND `storeList`.`user_id` = ".intval($this->user_id)." AND `storeList`.`store_id` = ".intval($this->id)." ORDER BY `store`.`name` ASC", false, "getRow" );
    }

    public function listStoresAmin($view, $start=0, $limit=10) {
        $tag = "";
        if ($view == "pending") {
            $tag = " AND `verified` = 1";
        } else if ($view == "approved") {
            $tag = " AND `verified` = 2";
        } else if ($view == "rejected") {
            $tag = " AND `verified` = 3";
        } else if ($view == "online") {
            $tag = " AND `status` = 'ACTIVE'";
        } else if ($view == "offline") {
            $tag = " AND `status` = 'INACTIVE'";
        }

        //query for result
        $query = "SELECT * FROM `store` WHERE `status` != 'DELETED'".$tag." ORDER BY `store`.`name` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `store`.`ref` FROM `store` WHERE `store`.`status` != 'DELETED'".$tag;
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function listStores($limit=6, $start=0) {
        //query for result

        $query = "SELECT `store`.`ref`, `store`.`name`, `store`.`address`, `store`.`city`, `store`.`province`, `store`.`post_code`, `store`.`email`, `store`.`phone`, `store`.`url`, `store`.`logo`, `store`.`longitude`, `store`.`latitude`, `store`.`created_by`, `store`.`status`, `store`.`open_time`, `store`.`close_time`, `store`.`commission`, `store`.`verified` FROM `store` INNER JOIN `storeList` ON `store`.`ref` = `storeList`.`store_id` WHERE `store`.`status` != 'DELETED' AND `storeList`.`user_id` = ".intval($this->user_id)." ORDER BY `store`.`name` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `store`.`ref` FROM `store` INNER JOIN `storeList` ON `store`.`ref` = `storeList`.`store_id` WHERE `store`.`status` != 'DELETED' AND `storeList`.`user_id` = ".intval($this->user_id);
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function listUsers($start, $limit) {
        //query for result
        $query = "SELECT `usersStoreAdmin`.`ref`, `usersStoreAdmin`.`lname`, `usersStoreAdmin`.`fname`, `usersStoreAdmin`.`email`, `usersStoreAdmin`.`status` FROM `usersStoreAdmin` INNER JOIN `storeList` ON `usersStoreAdmin`.`ref` = `storeList`.`user_id` WHERE `usersStoreAdmin`.`status` != 'DELETED' AND `storeList`.`store_id` = ".intval($this->id)." ORDER BY `usersStoreAdmin`.`lname` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `usersStoreAdmin`.`ref` FROM `usersStoreAdmin` INNER JOIN `storeList` ON `usersStoreAdmin`.`ref` = `storeList`.`user_id` WHERE `usersStoreAdmin`.`status` != 'DELETED' AND `storeList`.`store_id` = ".intval($this->id);
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function listStoresOpen($start, $limit) {
        //query for result
        $query = "SELECT * FROM `store`  WHERE `status` = 'ACTIVE' ORDER BY `name` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT * FROM `store`  WHERE `status` = 'ACTIVE'";
        $returm['counts'] = $this->query($query, false, "count");
        return $returm;
    }

    public function storeData( $id ) {
        $data = $this->listOne( $id );
        if ( $data ) {
            $return['storeID'] = intval( $data['ref'] );
            $return['name'] = $data['name'];
            $return['address'] = $data['address'];
            $return['province'] = $data['province'];
            $return['post_code'] = $data['post_code'];
            $return['email'] = $data['email'];

            return $return;
        } else {
            return NULL;
        }
    }

    public function minimalStore( $id ) {
        $data = $this->listOne( $id );
        if ( $data ) {
            $return['name'] = $data['name'];
            $return['address'] = $data['address'].", ".$data['city'].", ".$data['province'].", ".$data['post_code'];
            $return['longitude'] = $data['longitude'];
            $return['latitude'] = $data['latitude'];
            $return['distance'] = $this->distance($this->location['latitude'],$this->location['longitude'],$data['latitude'],$data['longitude']);

            return $return;
        } else {
            return NULL;
        }
    }

    public function retrieveStoreView($view, $page=1) {
        global $options;
        global $storeList;
        global $inventory;
        global $wallet;
        global $usersStoreAdmin;
        global $orders;

        if (intval($page) == 0) {
            $page = 1;
        }
        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;

        if ($view == "one") {
            $this->result = $usersStoreAdmin->listOne($page);
            if ($this->result) {
                $this->return['success'] = true;
                $this->return['data'] = $usersStoreAdmin->formatResult( $this->result, true );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($view == "pending") {
            $this->result = $usersStoreAdmin->adminPendingStoreList($start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                // $usersStoreAdmin->admin = true;
                $this->return['data'] = $usersStoreAdmin->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($view == "users") {
            $this->result = $storeList->adminStoreList($this->id, $start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $usersStoreAdmin->admin = true;
                $this->return['data'] = $usersStoreAdmin->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($view == "wallet") {
            $wallet->view = "house";
            $this->result = $wallet->adminStoreList($this->id, $start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $wallet->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($view == "inventory") {
            $this->result = $inventory->adminStoreList($this->id, $start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $inventory->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($view == "orders") {
            $orders->admin = true;
            $orders->store_id =  $this->id;
            $this->result = $orders->adminStoreList($this->id, $start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $orders->formatStoreResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 11015;
            $this->return['message'] = "An error occured listing this store data";
        }

        return $this->return;
    }

    public function retrieveAdmin($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }

        if ($view === null) {
            $this->return['success'] = false;
            $this->return['error']['code'] = 11012;
            $this->return['message'] = "Missing parameters in URL";
            
            return $this->return;
        } else if ($view == "one") {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true, false );
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->listStoresAmin($view, $start, $limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $this->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        }
        return $this->return;
    }

    public function retrieveAPI( $page=1  ) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }

        if ($this->id > 0) {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true, false );
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->listStores($limit, $start);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $this->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        }
        return $this->return;
    }

    public function retrieveUsers( $page=1 ) {
        global $options;
        global $storeList;
        global $usersStoreAdmin;

        if (intval($page) == 0) {
            $page = 1;
        }

        $storeList->user_id = $this->user_id;
        $storeList->store_id = $this->id;

        if ($storeList->checkInStore()) {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            $this->result = $this->listUsers($start,$limit);
            if ($this->result['counts'] > 0) {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $usersStoreAdmin->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 11008;
            $this->return['message'] = "Not authorizdd to list store users";
        }
        return $this->return;
    }

    public function retrieve( $page=1 ) {
        global $options;
        if (intval($page) == 0) {
            $page = 1;
        }
        
        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;
        if ($this->id > 0) {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true, false );
        } else {
            if ($this->result['counts'] > 0) {
                $this->result = $this->listStoresOpen($start, $limit);
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $this->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        }
        return $this->return;
    }

    public function getAPI($page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }

        if ($this->id > 0) {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true, false );
        } else if ($this->search != "") {
            $this->result = $this->findRemote( $this->search, $this->location );
            if ($this->result) {
                $this->return['success'] = true;
                $this->return['data'] = $this->formatResult( $this->result );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11005;
                $this->return['message'] = "No record found";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            $this->return['success'] = true;
            $this->result = $this->getStoreList($this->location['longitude'], $this->location['latitude'], $start, $limit);
            $this->return['counts']['current_page'] = intval($page);
            $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
            $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
            $this->return['counts']['max_rows_per_page'] = intval($limit);
            $this->return['counts']['total_rows'] = $this->result['counts'];
            $this->return['data'] = $this->formatResult( $this->result['data'] );
        }

        return $this->return;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`store` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `name` varchar(255),
            `address` varchar(255),
            `city` varchar(255),
            `province` varchar(255),
            `post_code` varchar(255),
            `email` varchar(255),
            `phone` varchar(255),
            `url` varchar(255),
            `logo` varchar(255),
            `open_time` varchar(8) DEFAULT '0:00:00',
            `close_time` varchar(8) DEFAULT '23:59:59',
            `longitude` DOUBLE NOT NULL, 
            `latitude` DOUBLE NOT NULL, 
            `commission` INT NULL, 
            `verified` INT, 
            `created_by` int,
            `status` varchar(255),
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`store`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`store`";

        $this->query($query);
    }
}
?>