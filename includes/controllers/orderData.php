<?php
class orderData extends orderLocation {

    public function addData($array) {
        return $this->insert("orderData", $array);
    }

    public function removeData($id, $ref="order_id") {
        return $this->delete("orderData", $id, $ref);
    }

    public function getLocation() {
        if ($this->listOneData($this->item_id)) {
            $data = $this->query("SELECT * FROM `orderLocation` WHERE `item_id` = ".$this->item_id." ORDER BY `create_time` DESC", false, "getRow");

            return $this->formatLocationResult($data, true);
        } else {
            return false;
        }
    }

    public function storeCount($id) {
        $return['total'] = $this->query("SELECT COUNT(`ref`) FROM `orderData` WHERE `store_id` =  ".$id." AND `status` !='NEW'", false, "getCol");
        $return['cancelled'] = $this->query("SELECT COUNT(`ref`) FROM `orderData` WHERE `store_id` =  ".$id." AND `status` ='CANCELLED'", false, "getCol");
        $return['processed'] = $this->query("SELECT COUNT(`ref`) FROM `orderData` WHERE `store_id` =  ".$id." AND `status` ='COMPLETED'", false, "getCol");

        return $return;
    }

    public function getCourierData($order_id) {
        return $this->query("SELECT `orderData`.`ref`, `orderData`.`order_id`, `orderData`.`store_id`, `orderData`.`item_id`, `orderData`.`quantity`, `orderData`.`amount`, `orderData`.`discount`, `orderData`.`delivery_fee`, `orderData`.`sub_total`, `orderData`.`total`, `orderData`.`usersCourier`, `orderData`.`status` FROM `orderData` WHERE `orderData`.`usersCourier` = ".$this->user_id." AND `orderData`.`order_id` = ".$order_id, false, "list");
    }

    public function getStoreData($order_id) {
        return $this->query("SELECT `orderData`.`ref`, `orderData`.`order_id`, `orderData`.`store_id`, `orderData`.`item_id`, `orderData`.`quantity`, `orderData`.`amount`, `orderData`.`discount`, `orderData`.`delivery_fee`, `orderData`.`sub_total`, `orderData`.`total`, `orderData`.`usersCourier`, `orderData`.`status` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` AND `storeList`.`user_id` = ".$this->user_id." AND `orderData`.`order_id` = ".$order_id, false, "list");
    }

    public function getAdminStoreData($order_id) {
        return $this->query("SELECT `orderData`.`ref`, `orderData`.`order_id`, `orderData`.`store_id`, `orderData`.`item_id`, `orderData`.`quantity`, `orderData`.`amount`, `orderData`.`discount`, `orderData`.`delivery_fee`, `orderData`.`sub_total`, `orderData`.`total`, `orderData`.`usersCourier`, `orderData`.`status` FROM `orderData` WHERE `orderData`.`store_id` = ".$this->store_id." AND `orderData`.`order_id` = ".$order_id, false, "list");
    }

    public function cancelAllStoreOrder($order_id) {
        $list = array();
        $data = $this->query("SELECT `orderData`.`ref` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` AND `storeList`.`user_id` = ".$this->user_id." AND `orderData`.`order_id` = ".$order_id, false, "list");

        foreach ($data as $row) {
            $list[] = $row['ref'];
            $this->modifyOneData("usersCourier", NULL, $row['ref']);
            $this->changeOrderDataStatus($row['ref'], "CANCELLED");
        }
        if (count($list) > 0) {
            return $list;
        }
    }

    public function completeAllStoreOrder($order_id) {
        $data = $this->query("SELECT `orderData`.`ref` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` AND `storeList`.`user_id` = ".$this->user_id." AND `orderData`.`status` != 'CANCELLED' AND `orderData`.`order_id` = ".$order_id, false, "list");

        foreach ($data as $row) {
            $this->changeOrderDataStatus($row['ref'], "PROCESSED");
        }

        return true;
    }

    public function processOrderData($id) {
        global $store;
        global $inventory;
        global $usersStoreAdmin;
        /**
         * remove this later
         */
        // $this->modifyOneData("status", "PENDING", $id, "order_id");
        $this->modifyOneData("status", "PROCESSED", $id, "order_id");

        $list = $this->getSortedDataList($id, "order_id");

        $tag = "The following items have been ordered in your store<br><br>";
        foreach ($list as $row) {
            $storeData = $store->listOne($row['store_id']);
            $tag .= $row['quantity']." ".$this->addS("Piece", $row['quantity'])." of ".$inventory->listOneValue( $row['item_id'], "name")."<br>";

            if ($storeData['email'] != "") {
                $items[$row['store_id']][0]  = $storeData['email'];
            }
            $items[$row['store_id']][1]  = $usersStoreAdmin->listOneValue( $storeData['created_by'], "email");
        }

        $tag .= "Please <a href='".p_url."/orders'>login</a> to your account to process this order";

        foreach ($items as $key) {
            foreach ($key as $email) {
                $subjectToClient = "New Order Notification";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode("").
                    '&fname='.urlencode($storeData['name']).
                    '&email='.urlencode($email).
                    '&tag='.urlencode($tag);
                $mailUrl = URL."includes/views/emails/notification.php?store&".$fields;
                
                $messageToClient = $this->curl_file_get_contents($mailUrl);

                $mail['from'] = $contact;
                $mail['to'] = $storeData['name']." <".$email.">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);
            }
        }
    }

    public function changeOrderDataStatus($id, $status, $delivery=false) {
        global $usersCustomers;
        $this->modifyOneData("status", $status, $id);
        $data = $this->listOneData($id);

        $sortedData = $this->getSortedDataList($data['order_id'], "order_id");
        $sortedDataStatus = $this->getSimilar($data['order_id'], $status);

        if (count($sortedDataStatus) >= count($sortedData)) {
            $this->updateOne("orders", "order_status", $status, $data['order_id']);

            if ($status == "DELIVERED") {
                $this->deliver($data['order_id']);
                $this->updateOne("orders", "order_status", "COMPLETED", $data['order_id']);
            }
        }

        if ($data['status'] == "PENDING") {
            $status = "New";
        } else if ($data['status'] == "PROCESSING") {
            $status = "Processing";
        } else if ($data['status'] == "PROCESSED") {
            $status = "Pickup Ready";
        } else if ($data['status'] == "DELIVERY-ACCEPTED") {
            $status = "Delivery Accepted by Courier";
        } else if ($data['status'] == "DELIVERY-ONGOING") {
            $status = "Delivery Ongoing";
        } else if ($data['status'] == "DELIVERED") {
            $status = "Delivered";
        } else if ($data['status'] == "COMPLETED") {
            $status = "Completed";
        } else if ($data['status'] == "CANCELLED") {
            $status = "Cancelled";
        } else if ($data['status'] == "FLAGGED") {
            $status = "Flagged";
        }

        $userData = $usersCustomers->listOne($this->query("SELECT `user_id` FROM `orders` WHERE `ref` = ".$data['order_id'], false, "getCol"));

        $tag = "Your order with ID #".$data['order_id']." has been updated. One or more item's status has been changed to ".$status.". <a href='".u_url."'>Log in</a> to your Instadoor Account to learn more";

        $client = $userData['lname']." ".$userData['fname'];
        $subjectToClient = "Updates on Order #".$data['order_id'];
        $contact = "Instadoor <".replyMail.">";
        
        $fields = 'subject='.urlencode($subjectToClient).
            '&lname='.urlencode($userData['lname']).
            '&fname='.urlencode($userData['fname']).
            '&email='.urlencode($userData['email']).
            '&tag='.urlencode($tag);
        $mailUrl = URL."includes/views/emails/notification.php?".$fields;
        $messageToClient = $this->curl_file_get_contents($mailUrl);
        
        $mail['from'] = $contact;
        $mail['to'] = $client." <".$userData['email'].">";
        $mail['subject'] = $subjectToClient;
        $mail['body'] = $messageToClient;
        
        global $alerts;
        $alerts->sendEmail($mail);

        $this->remitToWallet($data);
    }

    private function remitToWallet($data) {
        global $wallet;

        if (($data['status'] == "DELIVERED") || ($data['status'] == "COMPLETED")) {
            $storeCommission = $this->getStoreCommission($data['store_id'], $data['item_id'], $data['amount']);
            $courierCommission = $this->getCourierCommission($data['delivery_fee']);

            $add['order_id'] = $data['order_id'];
            $add['store_id'] = $data['store_id'];
            $add['order_item_id'] = $data['item_id'];
            $add['courier_id'] = $data['usersCourier'];
            $add['total'] = $data['total'];
            $add['courier'] = ($data['delivery_fee']-$courierCommission);
            $add['store'] = ($data['amount']-$storeCommission);
            $add['instadoor'] = $data['service_charge']+$storeCommission+$courierCommission;
            $add['trans_id'] = $this->getTxId($data['order_id']);
            $add['tax'] = $data['tax'];
            $add['fulfil_store'] = 0;
            $add['fulfil_courier'] = 0;
            $add['fulfil_store_date'] = strtotime("next Friday");
            $add['fulfil_courier_date'] = strtotime("next Friday");
            
            return $wallet->add($add);
                
        }
        
    }

    private function getTxId( $order_id ) {
        return $this->query("SELECT `tx_id` FROM `orders` WHERE `ref` = ".$order_id, false, "getCol");
    }

    private function getStoreCommission($store_id, $item_id, $total) {
        global $inventory;
        global $store;
        global $options;
        $itemCommission = $inventory->listOneValue($item_id, "commission");
        $storeCommission = $store->listOneValue($store_id, "commission");

        if (intval($itemCommission) > 0) {
            return (($itemCommission/100) *  $total);
        } else if (intval($storeCommission) > 0) {
            return (($storeCommission/100) *  $total);
        } else {
            return ((intval($options->get("default_commission"))/100) *  $total);
        }
    }

    private function getCourierCommission( $total) {
        global $options;
        return ((intval($options->get("default_commission_courier"))/100) *  $total);
    }

    private function deliver($id) {
        return $this->query("UPDATE `orderData` SET `status` = 'COMPLETED' WHERE `status` = 'DELIVERED' AND `order_id` = ".$id);
    }

    private function getSimilar($order_id, $status) {
        return $this->query("SELECT * FROM `orderData` WHERE (`status` = 'CANCELLED' OR `status` = '".$status."') AND `order_id` = ".$order_id, false, "list");
    }

    public function refund($id) {
        $refAmount = $this->query("SELECT SUM(`total`) FROM `orderData` WHERE `status` = 'CANCELLED' AND `order_id` = ".$id, false, "getCol");
        return $this->query("UPDATE `orders` SET  `refund` = ".$refAmount." WHERE `ref` = ".$id);
    }

    public function modifyOneData($tag, $value, $id, $ref="ref") {
        return $this->updateOne("orderData", $tag, $value, $id,$ref);
    }

    public function listOneData($id, $tag="ref") {
        return $this->getOne("orderData", $id, $tag);
    }

    public function cancelCourierPickup($id, $user_id) {
        return $this->query("UPDATE `orderData` SET `usersCourier` = null WHERE `order_id` = ".$id." AND `usersCourier` = ".$user_id);
    }

    public function cancelOneStoreOrder ($data) {
        if ($this->modifyOneData("status", "CANCELLED", $data['ref'])) {
            $this->modifyOneData("usersCourier", NULL, $data['ref']);

            // notify the user and the courier if the order is with a courier
            return true;
        }

        return false;
    }

    public function getSortedDataList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("orderData", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function courierHold($ref) {
        global $options;
        $hold_time = time()+intval($options->get("delivery_hold"));
        $this->modifyOneData("usersCourier", $this->user_id, $ref);
        $this->modifyOneData("hold", 1, $ref);
        $this->modifyOneData("hold_time", $hold_time, $ref);
    }

    public function courierUnHold() {
        return $this->query("UPDATE `orderData` SET `usersCourier` = NULL, `hold` = 0, `hold_time` = NULL WHERE `usersCourier` = ".$this->user_id." AND `status` = 'PROCESSED'");
    }

    public function getCourierItem($order_id, $user_id) {
        $this->isCourier = true;
        if ($this->listCourier == true) {
            $data = $this->query("SELECT * FROM `orderData` WHERE `order_id` = ".$order_id." AND `usersCourier` = ".$this->user_id, false, "list");
        } else {
            $data = $this->query("SELECT * FROM `orderData` WHERE `order_id` = ".$order_id." AND `store_id` = ".$user_id." AND `status` = 'PROCESSED'", false, "list");
            foreach ( $data as $row ) {
                $this->courierHold($row['ref']);
            }
        }
        return $this->formatDataResult($data);
    }

    public function formatDataResult($data, $single=false) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanData($data[$i]);
                }
            } else {
                $data = $this->cleanData($data);
            }
        } else {
            return [];
        }
        return $data;
    }

    private function cleanData($data) {
        global $inventory;
        
        $inventory->user_id = $this->user_id;
        $inventory->admin = $this->admin;
        $return['ref'] = intval( $data['ref'] );
        $return = $inventory->formatResult(  $inventory->listOne( $data['item_id'] ), true );
        $return['quantity'] = intval($data['quantity']);
        $return['charges']['delivery_fee'] = floatval($data['delivery_fee']);
        if (!$this->isCourier) {
            $return['charges']['sub_total'] = floatval($data['sub_total']);
            $return['charges']['discount'] = floatval($data['discount']);
            $return['charges']['total'] = floatval($data['total']);
            $return['charges']['refund'] = floatval($data['refund']);
            $return['data'] = explode(", ", trim($data['data']));

            $account['cart'] = ("NEW" == $data['status']) ? true : false;
            $account['isNew'] = ("PENDING" == $data['status']) ? true : false;
            $account['processing'] = ("PROCESSING" == $data['status']) ? true : false;
            $account['pickupReady'] = ("PROCESSED" == $data['status']) ? true : false;
            $account['flagged'] = ("FLAGGED" == $data['status']) ? true : false;
        }
        $account['deliveryAccepted'] = ("DELIVERY-ACCEPTED" == $data['status']) ? true : false;
        $account['deliveryOngoing'] = ("DELIVERY-ONGOING" == $data['status']) ? true : false;
        if (("DELIVERED" == $data['status']) || ("COMPLETED" == $data['status'])) {
            $account['delivered'] = true;
        } else {
            $account['delivered'] = false;
        }
        $account['cancelled'] = ("CANCELLED" == $data['status']) ? true : false;
        $return['orderStatus'] = $account;

        if ($data['status'] == "PENDING") {
            $status = "New";
        } else if ($data['status'] == "PROCESSING") {
            $status = "Processing";
        } else if ($data['status'] == "PROCESSED") {
            $status = "Pickup Ready";
        } else if ($data['status'] == "DELIVERY-ACCEPTED") {
            $status = "Delivery Accepted";
        } else if ($data['status'] == "DELIVERY-ONGOING") {
            $status = "Delivery Ongoing";
        } else if ($data['status'] == "DELIVERED") {
            $status = "Delivered";
        } else if ($data['status'] == "COMPLETED") {
            $status = "Completed";
        } else if ($data['status'] == "CANCELLED") {
            $status = "Cancelled";
        } else if ($data['status'] == "FLAGGED") {
            $status = "Flagged";
        }

        $return['statusResponse'] = $status;
        if ($this->admin) {
            $this->item_id = intval( $data['ref'] );
            $return['location'] = $this->getLocation();
        }

        return $return;
    }
    
    public function initialize_data_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`orderData` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `order_id` INT NOT NULL, 
            `store_id` INT NOT NULL, 
            `item_id` INT NOT NULL, 
            `quantity` INT NOT NULL, 
            `amount` INT NOT NULL, 
            `discount` DOUBLE NOT NULL, 
            `delivery_fee` DOUBLE NOT NULL, 
            `sub_total` DOUBLE NOT NULL, 
            `tax` DOUBLE NOT NULL, 
            `service_charge` DOUBLE NOT NULL, 
            `total` DOUBLE NOT NULL, 
            `usersCourier` INT NULL, 
            `status` varchar(20) NOT NULL DEFAULT 'NEW',
            `hold` INT NOT NULL,
            `hold_time` varchar(50) NULL,
            `exclude` TEXT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_data_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`orderData`";

        $this->query($query);
    }

    public function delete_data_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`orderData`";

        $this->query($query);
    }
}
?>