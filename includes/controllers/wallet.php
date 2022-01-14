<?php
include_once("walletAdvise.php");
include_once("walletRequest.php");
class wallet extends walletRequest {

    function add($array) {
        return $this->insert("wallet", $array);
    }
    
    public function getDetails($id, $tag="ref") {
        return $this->getOne("wallet", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("wallet", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("wallet", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("wallet", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("wallet", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("wallet", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function allWithdrawalRequest() {
        $batch = time().$this->createRandomPassword(10);
        // get for store
        $data = $this->query("SELECT * FROM `wallet` WHERE `fulfil_store` = 0", false, "list");

        foreach($data as $row) {
            $insertData['request_fee'] = 0;
            $insertData['wallet_id'] = $row['ref'];
            $insertData['user_type'] = "store";
            $insertData['user_type_id'] = $row['store_id'];
            $insertData['user_type_slug'] = $insertData['user_type']."_".$insertData['user_type_id'];
            $insertData['amount'] = $row['store'];
            $insertData['created_by'] = $this->user_id;
            $insertData['batch_id'] = $batch;

            $this->addRequest($insertData);

            $this->query("UPDATE `wallet` SET `batch_id` = '".$batch."', `fulfil_store` = 1  WHERE `fulfil_store` = 0 AND `ref` = ".$row['ref']);
        }

        // get for courier
        $data = $this->query("SELECT * FROM `wallet` WHERE `fulfil_courier` = 0", false, "list");

        foreach($data as $row) {
            $insertData['request_fee'] = 0;
            $insertData['wallet_id'] = $row['ref'];
            $insertData['user_type'] = "courier";
            $insertData['user_type_id'] = $row['courier_id'];
            $insertData['user_type_slug'] = $insertData['user_type']."_".$insertData['user_type_id'];
            $insertData['amount'] = $row['courier'];
            $insertData['created_by'] = $this->user_id;
            $insertData['batch_id'] = $batch;

            $this->addRequest($insertData);

            $this->query("UPDATE `wallet` SET `batch_id` = '".$batch."', `fulfil_courier` = 1  WHERE `fulfil_courier` = 0 AND `ref` = ".$row['ref']);
        }

        // return $this->balance($id, $type);
        $return['success'] = true;
        $return['data'] = $this->formatRequestResult( $this->generaList() );

        return $return;
    }

    public function getallWithdrawalRequest() {
        $return['success'] = true;
        $return['data'] = $this->formatRequestResult( $this->generaList() );

        return $return;
    }

    public function withdrawalRequest($id, $type="store") {
        $batch = time().$this->createRandomPassword(10);
        if ($type == "courier") {
            $tag = "courier";
            $label = "courier_id";
            $label2 = "fulfil_courier";
        } else if ($type == "store") {
            $tag = "store";
            $label = "store_id";
            $label2 = "fulfil_store";
        } else {
            return 0;
        }

        $data = $this->query("SELECT * FROM `wallet` WHERE `".$label."` = ".$id." AND `".$label2."` = 0", false, "list");

        foreach($data as $row) {
            $insertData['request_fee'] = 1;
            $insertData['wallet_id'] = $row['ref'];
            $insertData['user_type'] = $tag;
            $insertData['user_type_id'] = $id;
            $insertData['user_type_slug'] = $insertData['user_type']."_".$insertData['user_type_id'];
            if ($type == "courier") {
                $insertData['amount'] = $row['courier'];
            } else if ($type == "store") {
                $insertData['amount'] = $row['store'];
            } 
            $insertData['batch_id'] = $batch;

            $this->addRequest($insertData);

            $this->query("UPDATE `wallet` SET `batch_id` = '".$batch."', `".$label2."` = 1  WHERE `".$label."` = ".$id." AND `".$label2."` = 0 AND `ref` = ".$row['ref']);
        }

        return $this->balance($id, $type);
    }

    public function balance($id, $type="store") {
        if ($type == "courier") {
            $tag = "courier";
            $label = "courier_id";
            $label2 = "fulfil_courier";
        } else if ($type == "store") {
            $tag = "store";
            $label = "store_id";
            $label2 = "fulfil_store";
        } else {
            return 0;
        }

        $result['available'] = round(floatval($this->query("SELECT SUM(`".$tag."`) FROM `wallet` WHERE `".$label."` = ".$id." AND `".$label2."` = 0", false, "getCol")), 2);
        $result['current'] = round(floatval($this->query("SELECT SUM(`".$tag."`) FROM `wallet` WHERE `".$label."` = ".$id." AND `".$label2."` < 2", false, "getCol")), 2);

        return $result;
    }

    public function total($id, $type="store") {
        if ($type == "courier") {
            $tag = "courier";
            $label = "courier_id";
            $label2 = "fulfil_courier";
        } else if ($type == "store") {
            $tag = "store";
            $label = "store_id";
            $label2 = "fulfil_store";
        } else {
            return 0;
        }

        return floatval($this->query("SELECT SUM(`".$tag."`) FROM `wallet` WHERE `".$label."` = ".$id." AND `".$label2."` < 2", false, "getCol"));
    }

    public function adminCourierList($id, $start, $limit) {
        $return['data'] = $this->getSortedList($id, "courier_id", false, false, false, false, "ref", "ASC",'AND', $start, $limit);
        $return['counts'] = $this->getSortedList($id, "courier_id", false, false, false, false, "ref", "ASC",'AND', false, false, "count");

        return $return;
    }

    public function adminAllList($start, $limit) {
        $return['data'] = $this->getList($start, $limit, "modify_time", "DESC");
        $return['counts'] = $this->getList(false, false, "modify_time", "DESC", "count");

        return $return;
    }

    public function adminStoreList($id, $start, $limit) {
        $return['data'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', $start, $limit);
        $return['counts'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', false, false, "count");

        return $return;
    }

    private function getWalletList($view, $tag, $start, $limit) {
        if ($tag == "courier") {
            $label = "courier_id";
            $label2 = "fulfil_courier";
        } else if ($tag == "store") {
            $label = "store_id";
            $label2 = "fulfil_store";
        }

        if ($view == "current") {
            $check = 0;
        } else {
            $check = 1;
        }

        $return['data'] = $this->query("SELECT * FROM `wallet` WHERE `".$label."` = ".$this->user_id." AND `".$label2."` = ".$check." ORDER BY `ref` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT * FROM `wallet` WHERE `".$label."` = ".$this->user_id." AND `".$label2."` = ".$check, false, "count");

        return $return;
    }

    public function orderTOtal($order_id, $user_id, $user_type='store' ) {
        if ($user_type == "store") {
            $tag = "store";
            $type = "store_id";
        } else if ($user_type == "courier") {
            $tag = "courier";
            $type = "courier_id";
        } else {
            return null;
        }
        return $this->query( "SELECT SUM(`".$tag."`) FROM `wallet` WHERE `order_id` = ".$order_id." AND `".$type."` = ".$user_id, false, "getCol");

    }

    public function formatResult($data, $single=false ) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->clean($data[$i], true);
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
        global $inventory;
        global $orders;
        global $store;
        global $usersCourier;
        $data['ref'] = intval($data['ref']);
        $data['order'] = $orders->miniData( $data['order_id']);
        $data['item'] = $inventory->miniData( $data['order_item_id']);
        $data['transactionID'] = intval($data['trans_id']);

        if ($this->view == "courier") {
            $data['courierData'] = $usersCourier->userData($data['courier_id']);
            $data['amount'] = ($data['courier'] < 0 ? "(".abs($data['courier']).")" : floatval(round($data['courier'], 2)));
        } else if ($this->view == "store") {
            $data['storeData'] = $store->storeData($data['store_id']);
            $data['amount'] = ($data['store'] < 0 ? "(".abs($data['store']).")" : floatval(round($data['store'], 2)));
            $data['charges'] = ($data['instadoor'] < 0 ? "(".abs($data['instadoor']).")" : floatval(round($data['instadoor'], 2)));
        } else if ($this->view == "house") {
            $data['courierData'] = $usersCourier->userData($data['courier_id']);
            $data['storeData'] = $store->storeData($data['store_id']);
            $data['amount']['total'] = floatval($data['total']);
            $data['amount']['house'] = ($data['instadoor'] < 0 ? "(".abs($data['instadoor']).")" : floatval(round($data['instadoor'], 2)));
            $data['amount']['courier'] = ($data['courier'] < 0 ? "(".abs($data['courier']).")" : floatval(round($data['courier'], 2)));
            $data['amount']['store'] = ($data['store'] < 0 ? "(".abs($data['store']).")" : floatval(round($data['store'], 2)));
            $data['tax'] = floatval($data['tax']);
        }
        $data['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
        $data['created'] = strtotime( $data['create_time'] );
        $data['lastmodified'] = strtotime( $data['modify_time'] );
        
        unset($data['order_id']);
        unset($data['order_item_id']);
        unset($data['trans_id']);
        unset($data['courier_id']);
        unset($data['store_id']);
        unset($data['total']);
        unset($data['courier']);
        unset($data['store']);
        unset($data['instadoor']);
        unset($data['tax']);
        unset($data['fulfil_store']);
        unset($data['fulfil_courier']);
        unset($data['fulfil_store_date']);
        unset($data['fulfil_courier_date']);
        unset($data['fulfil_store']);
        unset($data['create_time']);
        unset($data['modify_time']);
        return $data;
    }

    public function apiGet($view, $type, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }

        if ($type == "courier") {
            $tag = "courier";
            $this->view = "courier";
        } else if ($type == "store") {
            $tag = "store";
            $this->view = "store";
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 15003;
            $this->return['error']["message"] =  "You can not retrieve the wallet entries at this time";

            return $this->return;
        }

        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;
        

        $this->return['success'] = true;
        $this->result = $this->getWalletList($view, $tag, $start, $limit);
        $this->return['counts']['current_page'] = intval($page);
        $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
        $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
        $this->return['counts']['max_rows_per_page'] = intval($limit);
        $this->return['counts']['total_rows'] = $this->result['counts'];
        $this->return['data'] = $this->formatResult( $this->result['data'] );

        return $this->return;
    }

    public function getAllView($page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;

        $this->view = "house";
        if ($this->pageView == "one") {
            $this->result = $this->listOne($this->id);
            if ($this->result) {
                $this->return['success'] = true;
                $this->return['data'] = $this->formatResult( $this->result, true);
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else if ($this->pageView == "list") {
            $this->result = $this->adminAllList($start, $limit);
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
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 18001;
            $this->return['message'] = "An error occured listing this courier data";
        }

        return $this->return;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`wallet` (
            `ref` INT NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL, 
            `batch_id` VARCHAR(50) NULL, 
            `order_item_id` INT NOT NULL, 
            `trans_id` INT NOT NULL, 
            `courier_id` INT NOT NULL, 
            `store_id` INT NOT NULL, 
            `total` DOUBLE NOT NULL, 
            `courier` DOUBLE NOT NULL, 
            `store` DOUBLE NOT NULL, 
            `instadoor` DOUBLE NOT NULL, 
            `tax` DOUBLE NOT NULL, 
            `fulfil_store` INT NULL, 
            `fulfil_courier` INT NULL, 
            `fulfil_store_date` VARCHAR(50) NOT NULL, 
            `fulfil_courier_date` VARCHAR(50) NOT NULL, 
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;
        
        ALTER TABLE `wallet` ADD `batch_id` VARCHAR(50) NULL AFTER `order_id`;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`options`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`options`";

        $this->query($query);
    }
}
?>