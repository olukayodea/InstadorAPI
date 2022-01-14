<?php
class cards extends common {
    public $data = array();
    public $id;
    public $user_id;
    public $store_id;
    public $location = array();
    public $result = array();
    public $return = array();
    
    public function add($data) {
        global $usersCustomers;
        global $alerts;

        if ($this->checkExixst("cards", "user_id", $this->user_id) < 1) {
            $is_default = 1;
        } else {
            $is_default = 0;
        }
        $data['status'] = "ACTIVE";
        $data['is_default'] = $is_default;
        $data['user_id'] = $this->user_id;
        $this->id = $this->insert("cards", $data);
        if ($this->id) {
            //send email
            $tag = "you have added card **** **** **** ".$data['pan']." expiring ".$data['expiry_month']."/".$data['expiry_year']." to your account. <a href='".u_url."cards'>Sigin in</a> to your Account to learn more";

            $user_data = $usersCustomers->listOne($this->user_id);
            $client = $user_data['last_name']." ".$user_data['other_names'];
            $subjectToClient = "Payment Card Update";
            $contact = "Instadoor <".replyMail.">";
            
            $fields = 'subject='.urlencode($subjectToClient).
                '&last_name='.urlencode($user_data['last_name']).
                '&other_names='.urlencode($user_data['other_names']).
                '&email='.urlencode($user_data['email']).
                '&tag='.urlencode(htmlentities($tag));
            $mailUrl = URL."includes/views/emails/notification.php?".$fields;
            $messageToClient = $this->curl_file_get_contents($mailUrl);
            
            $mail['from'] = $contact;
            $mail['to'] = $client." <".$user_data['email'].">";
            $mail['subject'] = $subjectToClient;
            $mail['body'] = $messageToClient;
            
            $alerts->sendEmail($mail, true);
        }
        return $this->id;
    }
    
    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("cards", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function remove($user=false) {
        $dontDelete = false;
        $data = $this->listOne($this->id);
        if ($this->checkData()) {
            $dontDelete = true;
        }

        if (($user == false) || (($user == true) && ($this->user_id == $data['user_id']))) {
            if ($data['is_default'] == 0) {
                if ($dontDelete === true) {
                    return $this->editOne("status", "DELETED", $this->id);
                } else {
                    return $this->delete("cards",  $this->id);
                }
            } else {
                return false;
            }
        } else {
            return "0000";
        }

        return false;
    }

    /**
     * Check if the store has other data in other tables, if there is data @return true else @return false
     */
    private function checkData() {
        // get items to delete

        return false;
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("cards", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("cards", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("cards", $id, $tag);
    }

    function setDefault($id) {
        $data = $this->getSortedList($this->user_id, "user_id", "is_default", 1, false, false, "ref", "ASC", "AND", false, false, "getRow");
        $this->updateOne("cards", "is_default", 0, $data['ref'], "ref");
        $this->updateOne("cards", "is_default", 1, $id, "ref");
        return true;
    }

    function toggleStatus($id) {
        $data = $this->listOne($id);
        if ($data['status'] == "ACTIVE") {
            $updateData = "INACTIVE";
        } else if ($data['status'] == "INACTIVE") {
            $updateData = "ACTIVE";
        }

        $this->updateOne("cards", "status", $updateData, $id, "ref");
        return true;
    }

    public function listMultiple($id, $tag="ref") {
        return $this->query("SELECT * FROM `cards` WHERE `".$tag."` IN (".$id.")", false, "list");
    }

    public function listOneValue($id, $reference, $ref="ref") {
        return $this->getOneField("cards", $id, $ref, $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("cards", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

        
    private function listAllUserData($user, $start, $limit) {
        $return['list'] = $this->getSortedList($user, "user_id", false, false, false, false, "is_default", "DESC", "AND", $start, $limit);
        $return['listCount'] = $this->getSortedList($user, "user_id", false, false, false, false, "is_default", "DESC", "AND", false, false, "count");

        return $return;
    }

    public function getCards() {
        return $this->formatResult($this->getSortedList($this->user_id, "user_id"));
    }

    public function formatResult($data, $single=false ) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->clean($data[$i]);
                }
            } else {
                $data = $this->clean($data);
            }
        }else {
            return [];
        }
        return $data;
    }

    private function clean($data) {
        $expiry = strtotime("31-".$data['expiry_month']."-20".$data['expiry_year']);
        $data['ref'] = intval( $data['ref'] );
        $data['pan'] = "**** **** **** "+$data['pan'];

        $account['active'] = ("ACTIVE" == $data['status']) ? true : false;
        $account['inactive'] = ("ACTIVE" != $data['status']) ? true : false;
        $account['expired'] = false;
        if ($expiry < time()) {
            $account['expired'] = true;
            $account['active'] = false;
            $account['inactive'] = false;
        }
        $data['cardStatus'] = $account;
        $data['isDefault'] = (0 == $data['is_default']) ? false : true;
        unset($data['is_default']);
        unset($data['user_id']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['modify_time']);

        return $data;
    }

    public function apiGetList($type, $page=1) {
        global $options;
        if (intval($page) == 0) {
            $page = 1;
        }
        $current = intval($page)-1;
        
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;
        if ($type == "default") {
            $data = $this->getSortedList($this->user_id, "user_id", "is_default", 1, false, false, "ref", "ASC", "AND", false, false, "getRow");
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $data, true);
        } else if ($type == "list") {
            $result = $this->listAllUserData($this->user_id, $start, $limit);
            
            $this->return['success'] = true;
            $this->return['counts']['current_page'] = intval($page);
            $this->return['counts']['total_page'] = ceil($result['listCount']/$limit);
            $this->return['counts']['rows_on_current_page'] = count($result['list']);
            $this->return['counts']['max_rows_per_page'] = intval($limit);
            $this->return['counts']['total_rows'] = $result['listCount'];
            $this->return['data'] = $this->formatResult( $result['list'] );
        }

        return $this->return;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`cards` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `user_id` INT NOT NULL, 
            `pan` VARCHAR(4) NOT NULL, 
            `brand` VARCHAR(10) NOT NULL, 
            `expiry_year` VARCHAR(2) NOT NULL, 
            `expiry_month` VARCHAR(2) NULL, 
            `gateway_token` VARCHAR(1000) NULL,
            `is_default` INT NOT NULL DEFAULT 0, 
            `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`cards`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`cards`";

        $this->query($query);
    }
}
?>