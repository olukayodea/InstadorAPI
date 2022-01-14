<?php
include_once("orderLocation.php");
include_once("orderData.php");
class orders extends orderData {

    public function add() {
        global $tax;
        global $inventory;
        global $store;
        global $options;
        global $cart;

        $items = $cart->getOrder();
        
        if ($items) {
            $insert['user_id'] = $this->user_id;

            $tax->location = $this->location;
            $addressData = $tax->apiTax();

            $insert['tx_id'] = "0";
            $insert['tip'] = "0";
            $insert['notes'] = "";
            if ($this->location['address'] != "") {
                $insert['address'] = $this->location['address'];
            } else {
                $insert['address'] = $addressData['address'];
            }
            $insert['longitude'] = $this->location['longitude'];
            $insert['latitude'] = $this->location['latitude'];
            $insert['service_charge'] = 0;

            $this->clear();
            $add = $this->insert("orders", $insert);
            if ($add) {
                $max_distance = intval($options->get("max_distance"));
                $max_weight = intval($options->get("max_weight"));
                $service_charge = intval($options->get("service_charge"));
                $units = $options->get("units");
                $this->id = $add;

                $orderData['order_id'] = $this->id;

                $delivery_fee = 0;
                $sub_total = 0;
                $total = 0;
                $discount = 0;
                $weight = 0;
                $service = 0;
                $tax = 0;

                $cart->user_id = $this->user_id;

                foreach ($items as $row) {
                    $temp_tax = 0;
                    $inventoryData = $inventory->listOne( $row['item_id'] );
                    $storeData = $store->listOne( $inventoryData['store_id'] );
                    $orderData['store_id'] = $inventoryData['store_id'];
                    $orderData['store_id'] = $inventoryData['store_id'];
                    $orderData['item_id'] = $row['item_id'];
                    $orderData['quantity'] = $row['quantity'];
                    $orderData['amount'] = $inventoryData['amount'];
                    $orderData['sub_total'] = $orderData['quantity']*$orderData['amount'];
                    $orderData['discount'] = (null !== $inventoryData['sales']) ?  $orderData['sub_total']*($inventoryData['sales_value']/100): 0;
                    
                    $orderData['service_charge'] = $service_charge;
                    $service = $service + $service_charge;

                    $distance = $this->distance( $this->location['latitude'], $this->location['longitude'], $storeData['latitude'], $storeData['longitude'] )[$units]['value'];

                    $weight = $weight+($row['quantity']*$inventoryData['weight']);

                    if ($weight > $max_weight) {
                        $this->return['success'] = false;
                        $this->return['error']['code'] = 13016;
                        $this->return['error']['message'] = "An error occured while creating this order, an item in your order is over the max weight limit";
                        $this->return['error']['data'] = $max_weight;
                        return $this->return;
                    }
                    if ($distance > $max_distance) {
                        $this->return['success'] = false;
                        $this->return['error']['code'] = 13017;
                        $this->return['error']['message'] = "An error occured while creating this order, an item's pickup time in your order is farther the max distance limit";
                        $this->return['error']['data'] = $max_distance;
                        return $this->return;
                    }

                    $orderData['delivery_fee'] = $this->deliveryFee($distance, $weight);
                    $orderData['total'] = $orderData['sub_total']-$orderData['discount']+$orderData['delivery_fee'];

                    $delivery_fee = $delivery_fee+$orderData['delivery_fee'];
                    $sub_total = $sub_total+$orderData['sub_total'];
                    $discount = $discount+$orderData['discount'];
                    $total = $total+$orderData['total'];
                    if ($inventoryData['GST'] == 1) {
                        $temp_tax = $temp_tax + $addressData['tax']['federal'];
                    }
                    if ($inventoryData['PST'] == 1) {
                        $temp_tax = $temp_tax + $addressData['tax']['state'];
                    }
                    $taxData = (($temp_tax/100)*$total) + ($delivery_fee*(($addressData['tax']['federal']+$addressData['tax']['state'])/100))+ ($service*(($addressData['tax']['federal']+$addressData['tax']['state'])/100));
                    $tax = $tax+ $taxData;
                    $orderData['tax'] = $taxData;

                    $orderData['hold'] = 0;
                    $orderData['exclude'] = '';
                    
                    $this->addData($orderData);

                }

                $update['sub_total'] = $sub_total;
                $update['service_charge'] = $service;
                $update['discount'] = $discount;
                $update['tip'] = 0;
                $update['delivery_fee'] = $delivery_fee;
                $update['tax'] = $tax;
                $update['total'] = $total+$update['tip']+$update['tax'];

                $this->update("orders", $update, array("ref"=>$this->id));
                $this->return['success'] = true;
                $this->return['order'] = $this->formatResult( $this->listOne($this->id), true);

                return $this->return;
            }
        }

        $this->return['success'] = false;
        $this->return['error']['code'] = 13000;
        $this->return['error']['message'] = "An error occured while creating this order";
        return $this->return;
    }

    private function deliveryFee($distance, $weight) {
        global $options;
        $distance_charge = unserialize( $options->get("distance_charge") );
        $weight_charge = unserialize( $options->get("weight_charge") );

        $total = 0;

        foreach($distance_charge as $dis) {
            if (($dis['min'] <= round($distance, 1)) && (round($distance, 1) <= $dis['max'])) {
                $total = $total + $dis['val'];
            }
        }

        foreach($weight_charge as $weig) {
            if (($weig['min'] <= round($weight)) && (round($weight) <= $weig['max'])) {
                $total = $total + $weig['val'];
            }
        }

        return $total;
    }

    public function processOrder($array) {
        global $transactions;
        global $cart;

        $orderData = $this->getNew();
        if ($orderData) {
            if ((($array['payment_data'] == "") || ($array['payment_data'] == NULL)) && ($orderData['payment_data'] == NULL)) {
                return null;
            } 
            $this->id = $orderData['ref'];
            $trans['user_id'] = $this->user_id;
            $trans['tx_type_id'] = $this->id;
            $trans['tx_type'] = "ORDER";
            $trans['tx_dir'] = "DR";
            $trans['total'] = round($orderData['total']+$array['tip'], 2);
            
            $trans_id = $transactions->add($trans);
            if ($trans_id) {
                $processPay = $this->processPayment($trans_id);
                if ($processPay) {
                    $this->modifyOne("payment_status", "SUCCESS", $this->id);
                    /**
                     * remove this later
                     */
                    // $this->modifyOne("order_status", "PENDING", $this->id);
                    $this->modifyOne("order_status", "PROCESSED", $this->id);
                    $this->modifyOne("notes", $array['notes'], $this->id);
                    $this->modifyOne("tip", $array['tip'], $this->id);
                    $this->modifyOne("tx_id", $trans_id, $this->id);
                    $this->modifyOne("delivery_time", time()+$array['delivery_time'], $this->id);

                    $this->processOrderData($this->id);

                    $cart->user_id = $this->user_id;
                    $cart->clear();
                    return $this->formatResult( $this->listOne($this->id), true);
                }
                return false;
            }
        }

        return false;
    }

    public function flagOrder($array) {
        $data = $this->listOne($array['ref']);
        if ($data) {
            $note = "";
            if ($data['flag_status'] == 0) {
                $this->modifyOne("flag_status", 1, $data['ref']);
            } else {
                $note = $data['flag_note']."<br><br>";
            }
            $note = $note.$array['notes']."<br>".date('l jS \of F Y h:i:s A');
            $this->modifyOne("flag_note", $note, $data['ref']);

            return $this->formatResult($data);
        }
        return false;
    }

    private function listOneOrderCourier($id) {
        return $this->query("SELECT 
        `orderData`.`ref`, `orderData`.`order_id`, `orderData`.`store_id`, `orders`.`notes`, `orders`.`user_id`, `orders`.`tx_id`, `orders`.`order_status`, `orders`.`address`, `orders`.`longitude`, `orders`.`latitude`, `orders`.`create_time`, `orders`.`modify_time`, `orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$this->user_id." AND `orders`.`ref` = ".$id." GROUP BY `orders`.`ref`", false, "getRow");
    }

    private function listOneOrder($id) {
        return $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `storeList`.`user_id` = ".$this->user_id." AND `orders`.`ref` = ".$id." GROUP BY `orders`.`ref`", false, "getRow");
    }

    private function listOneOrderAdmin($id) {
        return $this->query("SELECT * FROM `orders` WHERE `orders`.`ref` = ".$id, false, "getRow");
    }

    public function getLocations() {
        if ($this->listOne($this->id)) {
            $data = $this->query("SELECT * FROM `orderLocation` WHERE `order_id` = ".$this->id." GROUP BY `item_id` ORDER BY `create_time` DESC", false, "list");

            return $this->formatLocationResult($data);
        } else {
            return false;
        }
    }

    private function listAllOrders ($type, $start, $limit) {
        if ($type != "") {
            if ($type == "past") {
                $tag = " AND `orderData`.`status` != 'PENDING'";
            } else {
                $tag = " AND `orderData`.`status` = '".$type."'";
            }
        } else {
            $tag = "";
        }
        
        $return['data'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `storeList`.`user_id` = ".$this->user_id."".$tag." GROUP BY `orders`.`ref` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `storeList` ON `storeList`.`store_id` = `orderData`.`store_id` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `storeList`.`user_id` = ".$this->user_id."".$tag." GROUP BY `orders`.`ref`", false, "count");

        return $return;
    }

    public function listAllOrdersAdmin ($type, $start, $limit) {
        if ($type != "") {
            if ($type == "past") {
                $tag = " AND `orderData`.`status` != 'PENDING'";
            } else if (($type == "DELIVERED") || ($type == "COMPLETED")) {
                $tag = " AND (`orderData`.`status` != 'COMPLETED' OR `orderData`.`status` != 'DELIVERED')";
            } else {
                $tag = " AND `orderData`.`status` = '".$type."'";
            }
            $return['data'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id`, `orders`.`sub_total`, `orders`.`delivery_fee`, `orders`.`service_charge`, `orders`.`delivery_fee`, `orders`.`tip`, `orders`.`discount`, `orders`.`tax`, `orders`.`total` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id`".$tag." GROUP BY `orders`.`ref` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
            $return['counts'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id`".$tag." GROUP BY `orders`.`ref`", false, "count");
        } else {            
            $return['data'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time` FROM `orders` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
            $return['counts'] = $this->query("SELECT `orders`.`ref` FROM `orders`", false, "count");
        }

        return $return;
    }

    public function adminStoreList ($id, $start, $limit) {
        $return['data'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`store_id` = ".$id." GROUP BY `orders`.`ref` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`store_id` = ".$id." GROUP BY `orders`.`ref`", false, "count");

        return $return;
    }

    public function adminCourierList ($id, $start, $limit) {
        $return['data'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`order_status`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `hold` != 1 GROUP BY `orders`.`ref` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT `orders`.`ref`,`orders`.`user_id`,`orders`.`tx_id`,`orders`.`notes`,`orders`.`address`,`orders`.`longitude`,`orders`.`latitude`,`orders`.`create_time`,`orders`.`modify_time`,`orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `hold` != 1 GROUP BY `orders`.`ref`", false, "count");

        return $return;
    }

    private function listAllOrdersCourier ($type, $start, $limit, $extra="") {
        if ($type != "") {
            if ($type == "past") {
                $tag = " AND `orderData`.`status` != 'PENDING'";
            } else {
                $tag = " AND (`orderData`.`status` = '".$type."'";
            }
            if ($extra != "") {
                $tag .= " OR `orderData`.`status` = '".$extra."'";
            }
            if ($type != "past") {
                $tag .= ")";
            }
        } else {
            $tag = "";
        }
        $return['data'] = $this->query("SELECT `orders`.`ref`, `orders`.`user_id`, `orders`.`tx_id`, `orders`.`notes`, `orders`.`order_status`, `orders`.`address`, `orders`.`longitude`, `orders`.`latitude`, `orders`.`create_time`, `orders`.`modify_time`, `orderData`.`store_id` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$this->user_id."".$tag." GROUP BY `orders`.`ref` ORDER BY `orders`.`create_time` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$this->user_id."".$tag." GROUP BY `orders`.`ref`", false, "count");

        return $return;
    }

    public function cancelStoreOrder( $ref ) {
        if (intval($ref)) {
            $returmData = $this->cancelAllStoreOrder($ref);
            if ( $returmData ) {
                $this->return['success'] = true;
                $this->return['data'] = $this->formatStoreResult( $this->listOneOrder($ref), true);

                foreach ($returmData as $row) {
                    $data = $this->listOneData($row);
                    $this->cancelOneStoreOrder($data );

                    $this->refund($data['order_id']);
                    // process refund
                }

                return $this->return;
            }
        }
        $this->return['success'] = false;
        $this->return['error']['code'] = 13008;
        $this->return['error']["message"] = "You can not perform this function on this order";
        return $this->return;
    }

    public function completeStoreOrder( $ref ) {
        if (intval($ref)) {
            $data = $this->listOne($ref);
            if ($data['order_status'] == "PROCESSING") {
                $returmData = $this->completeAllStoreOrder($ref);
                if ( $returmData ) {
                    $this->return['success'] = true;
                    $this->return['data'] = $this->formatStoreResult( $this->listOneOrder($ref), true);
                    return $this->return;
                }
            }
        }
        $this->return['success'] = false;
        $this->return['error']['code'] = 13008;
        $this->return['error']["message"] = "You can not perform this function on this order";
        return $this->return;
    }

    private function getNewCourier() {
        $min_lat = $this->location['latitude'] - search_radius;
        $max_lat = $this->location['latitude'] + search_radius;

        $min_long = $this->location['longitude'] - search_radius;
        $max_long = $this->location['longitude'] + search_radius;

        $this->courierUnHold();
        return $this->query( "SELECT `orderData`.`ref`, `orderData`.`order_id`, `orderData`.`store_id`, `store`.`name`, `store`.`address`, `store`.`city`, `store`.`province`, `store`.`post_code`, `orders`.`notes`, `store`.`longitude`, `store`.`latitude`, SQRT(((`store`.`latitude` - ".$this->location['latitude'].")*(`store`.`latitude` - ".$this->location['latitude'].")) + ((`store`.`longitude` - ".$this->location['longitude'].")*(`store`.`longitude` - ".$this->location['longitude']."))) AS `total` FROM `orderData`, `store`, `orders` WHERE `orderData`.`store_id` = `store`.`ref` AND `orderData`.`hold` = 0 AND `orderData`.`status` = 'PROCESSED' AND NOT FIND_IN_SET(".$this->user_id.", `exclude`) AND `store`.`status` = 'ACTIVE' AND `store`.`latitude` BETWEEN ".$min_lat." AND ".$max_lat." AND `store`.`longitude` BETWEEN ".$min_long." AND ".$max_long." ORDER BY `total` ASC LIMIT 1", false, "getRow" );
    }

    public function getCourier($type = "", $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if ($type == "one") {
            $this->listCourier = true;
            $this->return['success'] = true;
            $this->return['data'] = $this->formatCourierResult( $this->listOneOrderCourier($this->id), true);
            if ($this->return['data'] == false) {
                unset($this->return);
                $this->return['success'] = false;
                $this->return['error']['code'] = 13002;
                $this->return['error']['message'] = "Can not find the order requested";
            }
        } else if ($type == "new") {
            $this->listCourier = false;
            $this->return['success'] = true;
            $this->return['data'] = $this->formatCourierResult( $this->getNewCourier(), true);
  
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            if (strtolower($type) == strtolower("completed")) {
                $status = "COMPLETED";
                $extra = "DELIVERED";
            } else if (strtolower($type) == strtolower("accepted")) {
                $status = "DELIVERY-ACCEPTED";
            } else if (strtolower($type) == strtolower("ongoing")) {
                $status = "DELIVERY-ONGOING";
            } else {
                $this->return['success'] = true;
                $this->return['counts']['current_page'] = 0;
                $this->return['counts']['total_page'] = 0;
                $this->return['counts']['rows_on_current_page'] = 0;
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = 0;
                $this->return['data'] = [];

                return $this->return;
            }
            
            $this->result = $this->listAllOrdersCourier($status, $start, $limit, $extra);

            $this->return['success'] = true;
            $this->return['counts']['current_page'] = intval($page);
            $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
            $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
            $this->return['counts']['max_rows_per_page'] = intval($limit);
            $this->return['counts']['total_rows'] = $this->result['counts'];
            $this->return['data'] = $this->formatStoreResult( $this->result['data'] );
        }

        return $this->return;
    }

    public function modifyStoreOrder($array) {
        return $this->changeStatus($array, false, true);
    }

    public function getAllStoreOrders($type = "", $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if ($type == "one") {
            $this->return['success'] = true;
            $this->return['data'] = $this->formatStoreResult( $this->listOneOrder($this->id), true);
            if ($this->return['data'] == false) {
                unset($this->return);
                $this->return['success'] = false;
                $this->return['error']['code'] = 13002;
                $this->return['error']['message'] = "Can not find the order requested";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            if (strtolower($type) == strtolower("processing")) {
                $status = "PROCESSING";
            } else if (strtolower($type) == strtolower("processed")) {
                $status = "PROCESSED";
            } else if (strtolower($type) == strtolower("cancelled")) {
                $status = "CANCELLED";
            } else if (strtolower($type) == strtolower("completed")) {
                $status = "COMPLETED";
            } else if (strtolower($type) == strtolower("accepted")) {
                $status = "DELIVERY-ACCEPTED";
            } else if (strtolower($type) == strtolower("pickupReady")) {
                $status = "DELIVERY-ONGOING";
            } else if (strtolower($type) == strtolower("past")) {
                $status = "past";
            } else {
                $status = "PENDING";
            }

            $this->result = $this->listAllOrders($status, $start, $limit);

            $this->return['success'] = true;
            $this->return['counts']['current_page'] = intval($page);
            $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
            $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
            $this->return['counts']['max_rows_per_page'] = intval($limit);
            $this->return['counts']['total_rows'] = $this->result['counts'];
            $this->return['data'] = $this->formatStoreResult( $this->result['data'] );
        }

        return $this->return;
    }

    public function getAllStoreOrdersAdmin($type = "", $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if ($type == "one") {
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->listOneOrderAdmin($this->id), true);
            if ($this->return['data'] == false) {
                unset($this->return);
                $this->return['success'] = false;
                $this->return['error']['code'] = 13002;
                $this->return['error']['message'] = "Can not find the order requested";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            if (strtolower($type) == strtolower("processing")) {
                $status = "PROCESSING";
            } else if (strtolower($type) == strtolower("processed")) {
                $status = "PROCESSED";
            } else if (strtolower($type) == strtolower("cancelled")) {
                $status = "CANCELLED";
            } else if (strtolower($type) == strtolower("delivered")) {
                $status = "DELIVERED";
            } else if (strtolower($type) == strtolower("completed")) {
                $status = "COMPLETED";
            } else if (strtolower($type) == strtolower("accepted")) {
                $status = "DELIVERY-ACCEPTED";
            } else if (strtolower($type) == strtolower("pickupReady")) {
                $status = "DELIVERY-ONGOING";
            } else if (strtolower($type) == strtolower("past")) {
                $status = "past";
            } else if (strtolower($type) == strtolower("all")) {
                $status = "";
            } else {
                $status = "PENDING";
            }


            $this->result = $this->listAllOrdersAdmin($status, $start, $limit);

            $this->return['success'] = true;
            $this->return['counts']['current_page'] = intval($page);
            $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
            $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
            $this->return['counts']['max_rows_per_page'] = intval($limit);
            $this->return['counts']['total_rows'] = $this->result['counts'];
            $this->return['data'] = $this->formatResult( $this->result['data'] );
        }

        return $this->return;
    }

    public function changeStatus($array, $courier=false, $stores=false) {
        global $store;
        $type = $array['status'];
        if (strtolower($type) == strtolower("accepted")) {
            $data = $this->query("SELECT * FROM `orderData` WHERE `order_id` = ".$array['ref']." AND `hold` = 1 AND `usersCourier` = ".$this->user_id." LIMIT 1", false, "getRow");
        } else if (strtolower($type) == strtolower("picked")) {
            $data = $this->query("SELECT * FROM `orderData` WHERE `order_id` = ".$array['ref']." AND `status` = 'DELIVERY-ACCEPTED' AND `usersCourier` = ".$this->user_id." LIMIT 1", false, "getRow");
        } else {
            $data = $this->listOneData($array['ref']);
        }

        if ($data) {
            if ($stores === true) {
                if (!$store->finInStore($data['store_id'], $this->user_id)) {
                    $this->return['success'] = false;
                    $this->return['error']['code'] = 13014;
                    $this->return['error']['message'] = "Not authorizdd to perform actions on orders from this store";
                    return $this->return;
                }
            }
            
            if (strtolower($type) == strtolower("process")) {
                $status = "PROCESSING";
            } else if (strtolower($type) == strtolower("pickupReady")) {
                $status = "PROCESSED";
            } else if ((strtolower($type) == strtolower("cancel")) && ($courier === true)) {
                $status = "PROCESSED";
            } else if (strtolower($type) == strtolower("cancel")) {
                $status = "CANCELLED";
            } else if (strtolower($type) == strtolower("accepted")) {
                $status = "DELIVERY-ACCEPTED";
            } else if (strtolower($type) == strtolower("picked")) {
                $status = "DELIVERY-ONGOING";
            } else if (strtolower($type) == strtolower("delivered")) {
                $status = "DELIVERED";
            } else {
                $status = "PENDING";
            }

            // the user will mark it as completed
            if ( $status != "PENDING" && $status != "PROCESSING" && $status != "PROCESSED" && $status != "CANCELLED" && $status != "DELIVERY-ONGOING") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13011;
                $this->return['error']['message'] = "unrecorgnized order status";
            } else if ($data['status'] == "PENDING" && ($status != "PROCESSING" && $status != "CANCELLED")) {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13004;
                $this->return['error']['message'] = "You can only process or cancel this order";
            } else if ($data['status'] == "PROCESSING" && ($status != "PROCESSED" && $status != "CANCELLED")) {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13005;
                $this->return['error']['message'] = "You can only cancel this order or mark it ready for pickup";
            } else if ($data['status'] == "PROCESSED" && $stores === false && $status == "CANCELLED") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13010;
                $this->return['error']['message'] = "This order has been processed and awaiting pickup";
            } else if ($data['status'] == "CANCELLED") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13006;
                $this->return['error']['message'] = "This order has been cancelled";
            } else if ($data['status'] == "FLAGGED") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13012;
                $this->return['error']['message'] = "This order has been flagged";
            } else if ($data['status'] == "COMPLETED") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13007;
                $this->return['error']['message'] = "This order has been delivered";
            } else if ($data['status'] == "NEW") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13009;
                $this->return['error']['message'] = "you can not process this order at this time";
            } else if (($data['status'] == "DELIVERY-ACCEPTED") && ($status != "DELIVERY-ONGOING")) {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13020;
                $this->return['error']['message'] = "This order is already assigned for delivery";
            } else if ($data['status'] == "DELIVERY-ONGOING") {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13008;
                $this->return['error']['message'] = "This order is already out for delivery";
            } else {
                if ((strtolower($type) == strtolower("cancel")) && ($courier === true)) {
                    $this->cancelCourierPickup($data['ref'], $this->user_id );
                } else {
                    if ($this->isCourier === true) {
                        $returnData = $this->listOneOrderCourier($data['order_id']);
                    } else {
                        $returnData = $this->listOneOrder($data['order_id']);
                    }
                    $this->changeOrderDataStatus($data['ref'], $status);
                    $this->return['success'] = true;
                    $this->return['data'] = $this->formatStoreResult( $returnData, true);

                    if ((strtolower($type) == strtolower("cancel")) && ($stores === true)) {
                        // remove the singular item cancelled and refund the money to the refund
                        $this->cancelOneStoreOrder($data );
                        $this->refund($data['order_id']);

                        // process refund
                    }
                }
            }

        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 13013;
            $this->return['error']['message'] = "You can not perform this function on this order";
        }
        return $this->return;
    }

    public function formatCourierResult($data, $single=false) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanCourier($data[$i]);
                }
            } else {
                $data = $this->cleanCourier($data);
            }

            $this->isCourier = false;
        } else {
            return [];
        }
        return $data;
    }

    private function cleanCourier($data) {
        global $usersCustomers;
        global $store;
        global $wallet;
        $order = $this->listOne($data['order_id']);

        $return['ref'] = intval( $data['order_id'] );
        $return['user'] = $usersCustomers->userData( $order['user_id'] );
        $return['notes'] = $data['notes'];
        $return['orderItem'] = $this->getCourierItem($data['order_id'], $data['store_id']);
        if ($this->listCourier) {
            $return['store'] = $store->minimalStore($data['store_id']);
        } else {
            $return['store']['name'] = $data['name'];
            $return['store']['address'] = $data['address'].", ".$data['city'].", ".$data['province'].", ".$data['post_code'];
            $return['store']['longitude'] = $data['longitude'];
            $return['store']['latitude'] = $data['latitude'];
            $return['store']['distance'] = $this->distance($this->location['latitude'],$this->location['longitude'],$data['latitude'],$data['longitude']);
        }
        $return['delivery']['deliveryTime'] = $order['delivery_time'];
        $return['delivery']['address'] = $order['address'];
        $return['delivery']['longitude'] = $order['longitude'];
        $return['delivery']['latitude'] = $order['latitude'];
        $return['delivery']['distance'] = $this->distance($data['latitude'],$data['longitude'],$order['latitude'],$order['longitude']);
        $return['amountDue'] = floatVal($wallet->orderTOtal($data['order_id'], $this->user_id, "courier"));
        $return['created'] = strtotime( $order['create_time'] );
        $return['lastmodified'] = strtotime( $order['modify_time'] );

        return $return;
    }

    public function formatStoreResult($data, $single=false) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanStore($data[$i]);
                }
            } else {
                $data = $this->cleanStore($data);
            }
        } else {
            return [];
        }
        return $data;
    }

    private function cleanStore($data) {
        global $usersCustomers;
        global $store;
        global $wallet;
        $return['ref'] = intval( $data['ref'] );
        $return['user'] = $usersCustomers->userData( $data['user_id'] );
        $return['transactionData'] = intval( $data['tx_id'] );
        $return['notes'] = $data['notes'];
        $return['delivery']['deliveryTime'] = $data['delivery_time'];
        $return['delivery']['address'] = $data['address'];
        $return['delivery']['longitude'] = $data['longitude'];
        $return['delivery']['latitude'] = $data['latitude'];
        if (!$this->isCourier) {
            $account['isNew'] = ("PENDING" == $data['order_status']) ? true : false;
            $account['processing'] = ("PROCESSING" == $data['order_status']) ? true : false;
            $account['pickupReady'] = ("PROCESSED" == $data['order_status']) ? true : false;
            $account['deliveryAccepted'] = ("DELIVERY-ACCEPTED" == $data['order_status']) ? true : false;
            $account['deliveryOngoing'] = ("DELIVERY-ONGOING" == $data['order_status']) ? true : false;
            $account['delivered'] = ("DELIVERED" == $data['order_status']) ? true : false;
            $account['completed'] = ("COMPLETED" == $data['order_status']) ? true : false;
            $account['cancelled'] = ("CANCELLED" == $data['order_status']) ? true : false;
            $account['flagged'] = ("FLAGGED" == $data['order_status']) ? true : false;
            $return['status'] = $account;

            if ($this->admin) {
                $return['orderData'] = $this->formatDataResult( $this->getAdminStoreData($data['ref']));
            } else {
                $return['orderData'] = $this->formatDataResult( $this->getStoreData($data['ref']));
                $return['amountDue'] = floatVal($wallet->orderTOtal($data['ref'], $data['store_id'], "store"));
            }
        } else {
            $return['store'] = $store->minimalStore($data['store_id']);
            $return['orderItem'] = $this->formatDataResult( $this->getCourierData($data['ref']));
            $return['amountDue'] = floatVal($wallet->orderTOtal($data['ref'], $this->user_id, "courier"));
        }
        if ($data['order_status'] == "PENDING") {
            $status = "New";
        } else if ($data['order_status'] == "PROCESSING") {
            $status = "Processing";
        } else if ($data['order_status'] == "PROCESSED") {
            $status = "Pickup Ready";
        } else if ($data['order_status'] == "DELIVERY-ACCEPTED") {
            $status = "Delivery Accepted";
        } else if ($data['order_status'] == "DELIVERY-ONGOING") {
            $status = "Delivery Ongoing";
        } else if ($data['order_status'] == "DELIVERED") {
            $status = "Delivered";
        } else if ($data['order_status'] == "COMPLETED") {
            $status = "Completed";
        } else if ($data['order_status'] == "CANCELLED") {
            $status = "Cancelled";
        } else if ($data['order_status'] == "FLAGGED") {
            $status = "Flagged";
        }

        $return['statusResponse'] = $status;
        $return['created'] = strtotime( $data['create_time'] );
        $return['lastmodified'] = strtotime( $data['modify_time'] );

        return $return;
    }

    private function processPayment($txId) {
        global $transactions;
        /**
         * put payment gateway logic here, for now it will default to true as a complete payment
         * later on, it will carry the gateway status 
         */
        $transactions->modifyOne("gateway_data", serialize(array("status"=>"successful")), $txId);
        $transactions->modifyOne("gateway_status", "SUCCESS", $txId);
        return true;
    }

    public function remove($id) {
        return $this->delete("orders", $id);
    }

    public function clear() {
        $row = $this->getNew();
        $this->remove($row['ref']);
        $this->removeData($row['ref']);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("orders", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("orders", $start, $limit, $order, $dir, false, $type);
    }

    private function getNew() {
        return $this->getSortedList("NEW", "order_status", "user_id", $this->user_id)[0];
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("orders", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("orders", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("orders", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function getOrderList($tag, $status, $start, $limit) {
        if ($status != "NEW") {
            $rem = " `order_status` != 'NEW' AND";
        } else {
            $rem = "";
        }

        if ($this->user === true) {
            $isUser = " AND `user_id` = ".$this->user_id;
        }
        //query for result
        $query = "SELECT * FROM `orders` WHERE".$rem." `".$tag."` = '".$status."'".$isUser." ORDER BY `ref` DESC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT * FROM `orders` WHERE".$rem." `".$tag."` = '".$status."'".$isUser;
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function getOrderAllList($start, $limit) {
        if ($this->user === true) {
            $isUser = " AND `user_id` = ".$this->user_id;
        }
        //query for result
        $query = "SELECT * FROM `orders` WHERE `order_status` != 'NEW'".$isUser." ORDER BY `ref` DESC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT * FROM `orders` WHERE `order_status` != 'NEW'".$isUser;
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function getUserOrderList($tag, $status, $start, $limit) {
        //query for result
        $query = "SELECT * FROM `orders` WHERE `".$tag."` = '".$status."' AND `user_id` = ".$this->user_id." ORDER BY `ref` DESC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT * FROM `orders` WHERE `".$tag."` = '".$status."' AND `user_id` = ".$this->user_id;
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function getUserRecemt() {
        return $this->query("SELECT * FROM `orders` WHERE `order_status` != 'NEW' AND `user_id` = ".$this->user_id." ORDER BY `ref` DESC LIMIT 4", false, "list");
    }

    public function paymentUpdate($payment_data) {
        $orderData = $this->getNew();
        if ($orderData) {
            if ($this->modifyOne("payment_data", $payment_data, $orderData['ref'])) {
                $this->return['success'] = true;
                $this->return['results'] = "OK";
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13019;
                $this->return['error']['message'] = "An error occured while adding payment authorization token";
            }

            return $this->return;
        }
        $this->return['success'] = false;
        $this->return['error']['code'] = 13001;
        $this->return['error']['message'] = "An error occured while processing this order";

        return $this->return;
    }

    public function courierCounter($id) {
        $return['deliveryAccepted'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'DELIVERY-ACCEPTED' GROUP BY `orders`.`ref`", false, "count");
        $return['deliveryOngoing'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'DELIVERY-ONGOING' GROUP BY `orders`.`ref`", false, "count");
        $return['delivered'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'DELIVERED' GROUP BY `orders`.`ref`", false, "count");
        $return['completed'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'COMPLETED' GROUP BY `orders`.`ref`", false, "count");
        $return['cancelled'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'CANCELLED' GROUP BY `orders`.`ref`", false, "count");
        $return['flagged'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orderData`.`usersCourier` = ".$id." AND `orderData`.`status` = 'FLAGGED' GROUP BY `orders`.`ref`", false, "count");

        return $return;
    }

    public function adminCounters($id) {
        $return['processing'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'PROCESSING' GROUP BY `orders`.`ref`", false, "count");
        $return['pickupReady'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'PROCESSED' GROUP BY `orders`.`ref`", false, "count");
        $return['deliveryAccepted'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'DELIVERY-ACCEPTED' GROUP BY `orders`.`ref`", false, "count");
        $return['deliveryOngoing'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'DELIVERY-ONGOING' GROUP BY `orders`.`ref`", false, "count");
        $return['delivered'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'DELIVERED' GROUP BY `orders`.`ref`", false, "count");
        $return['completed'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'COMPLETED' GROUP BY `orders`.`ref`", false, "count");
        $return['cancelled'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'CANCELLED' GROUP BY `orders`.`ref`", false, "count");
        $return['flagged'] = $this->query("SELECT `orders`.`ref` FROM `orderData` INNER JOIN `orders` ON `orders`.`ref` = `orderData`.`order_id` AND `orders`.`user_id` = ".$id." AND `orderData`.`status` = 'FLAGGED' GROUP BY `orders`.`ref`", false, "count");
        return $return;
    }

    public function formatResult($data, $single=false) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->clean($data[$i]);
                }
            } else {
                $data = $this->clean($data);
            }
        } else {
            return [];
        }
        return $data;
    }

    private function clean($data) {
        global $usersCustomers;
        $return['ref'] = intval( $data['ref'] );
        $return['user'] = $usersCustomers->userData( $data['user_id'] );
        $return['transactionData'] = intval( $data['tx_id'] );
        $return['notes'] = $data['notes'];
        $return['delivery']['deliveryTime'] = $data['delivery_time'];
        $return['delivery']['address'] = $data['address'];
        $return['delivery']['longitude'] = $data['longitude'];
        $return['delivery']['latitude'] = $data['latitude'];
        $return['charges']['sub_total'] = floatval($data['sub_total']);
        $return['charges']['delivery_fee'] = floatval($data['delivery_fee']);
        $return['charges']['service_charge'] = floatval($data['service_charge']);
        $return['charges']['refund'] = floatval($data['refund']);
        $return['charges']['tip'] = floatval($data['tip']);
        $return['charges']['discount'] = floatval($data['discount']);
        $return['charges']['tax'] = floatval($data['tax']);
        $return['charges']['total'] = floatval($data['total']);

        $account['cart'] = ("NEW" == $data['order_status']) ? true : false;
        $account['isNew'] = ("PENDING" == $data['order_status']) ? true : false;
        $account['processing'] = ("PROCESSING" == $data['order_status']) ? true : false;
        $account['pickupReady'] = ("PROCESSED" == $data['order_status']) ? true : false;
        $account['deliveryAccepted'] = ("DELIVERY-ACCEPTED" == $data['order_status']) ? true : false;
        $account['deliveryOngoing'] = ("DELIVERY-ONGOING" == $data['order_status']) ? true : false;
        $account['delivered'] = ("DELIVERED" == $data['order_status']) ? true : false;
        $account['completed'] = ("COMPLETED" == $data['order_status']) ? true : false;
        $account['flagged'] = (1 == $data['flag_status']) ? true : false;
        $account['flaggedNote'] = (1 == $data['flag_status']) ? $data['flag_note'] : "";
        $account['cancelled'] = ("CANCELLED" == $data['order_status']) ? true : false;
        $account['paymentPending'] = ("PENDING" == $data['payment_status']) ? true : false;
        $account['paymentFailed'] = ("FAILED" == $data['payment_status']) ? true : false;
        $account['paymentCompleted'] = ("DONE" == $data['payment_status']) ? true : false;
        $return['status'] = $account;
        $return['orderData'] = $this->formatDataResult( $this->getSortedDataList($data['ref'], "order_id"));
        $return['created'] = strtotime( $data['create_time'] );
        $return['lastmodified'] = strtotime( $data['modify_time'] );

        return $return;
    }

    public function retrieveAPI($type, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        
        if ($type == "one") {
            if ($this->id > 0) {
                $resData = $this->listOne($this->id);
                if ($resData['user_id'] == $this->user_id) {
                    $this->return['success'] = true;
                    $this->return['data'] = $this->formatResult($resData, true);

                    if ($this->return['data'] == false) {
                        unset($this->return);
                        $this->return['success'] = false;
                        $this->return['error']['code'] = 13002;
                        $this->return['error']['message'] = "Can not find the order requested";
                    }
                } else {
                    unset($this->return);
                    $this->return['success'] = false;
                    $this->return['error']['code'] = 13002;
                    $this->return['error']['message'] = "Can not find the order requested";
                }
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13002;
                $this->return['error']['message'] = "Can not find the order requested";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;

            $all = false;

            if (strtolower($type) == strtolower("processing")) {
                $tag = "order_status";
                $status = "PENDING";
            } else if (strtolower($type) == strtolower("pickupReady")) {
                $tag = "order_status";
                $status = "PROCESSED";
            } else if (strtolower($type) == strtolower("accepted")) {
                $tag = "order_status";
                $status = "DELIVERY-ACCEPTED";
            } else if (strtolower($type) == strtolower("deliveryOngoing")) {
                $tag = "order_status";
                $status = "DELIVERY-ONGOING";
            } else if (strtolower($type) == strtolower("delivered")) {
                $tag = "order_status";
                $status = "DELIVERED";
            } else if (strtolower($type) == strtolower("completed")) {
                $tag = "order_status";
                $status = "FLAGGED";
            } else if (strtolower($type) == strtolower("flagged")) {
                $tag = "order_status";
                $status = "FLAGGED";
            } else if (strtolower($type) == strtolower("cancelled")) {
                $tag = "order_status";
                $status = "CANCELLED";
            } else if (strtolower($type) == strtolower("paymentPending")) {
                $tag = "payment_status";
                $status = "PENDING";
            } else if (strtolower($type) == strtolower("paymentFailed")) {
                $tag = "payment_status";
                $status = "FAILED";
            } else if (strtolower($type) == strtolower("paymentCompleted")) {
                $tag = "payment_status";
                $status = "DONE";
            } else if (strtolower($type) == strtolower("recent")) {
                $this->return['success'] = true;
                $this->return['data'] = $this->formatResult( $this->getUserRecemt() );
                return $this->return;
            } else {
                $all = true;
            }

            if ($all === false ) {
                $this->user = true;
                $this->return['success'] = true;
                $this->result = $this->getOrderList($tag, $status, $start, $limit);
                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $this->formatResult( $this->result['data'] );
            } else {
                $this->return['success'] = true;
                if ($this->user === true) {
                    $this->result = $this->getOrderAllList($start, $limit);
                } else {
                    $this->result['data'] = $this->getList($start, $limit, 'ref', 'DESC');
                    $this->result['counts'] = $this->getList(false, false, 'ref', 'DESC', 'count');
                }

                $this->return['counts']['current_page'] = intval($page);
                $this->return['counts']['total_page'] = ceil($this->result['counts']/$limit);
                $this->return['counts']['rows_on_current_page'] = count($this->result['data']);
                $this->return['counts']['max_rows_per_page'] = intval($limit);
                $this->return['counts']['total_rows'] = $this->result['counts'];
                $this->return['data'] = $this->formatResult( $this->result['data'] );
            }
        }
        return $this->return;
    }

    private function acceptOrder() {
        $data = $this->query("SELECT `ref` FROM `orderData` WHERE `hold` = 1 AND `usersCourier` = ".$this->user_id, false, "list");

        if ($data) {
            foreach ($data as $row) {
                $this->modifyOneData("hold", 0, $row['ref']);
                $this->modifyOneData("hold_time", NULL, $row['ref']);
                $this->changeOrderDataStatus($row['ref'], "DELIVERY-ACCEPTED");
            }
            $this->return['success'] = true;
            $this->return['results'] = "OK";
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 13003;
            $this->return['error']['message'] = "You can not accept or reject this request again";
        }

        return $this->return;
    }

    private function rejectOrder() {
        $data = $this->query("SELECT `ref` FROM `orderData` WHERE `hold` = 1 AND `usersCourier` = ".$this->user_id, false, "list");

        if ($data) {
            foreach ($data as $row) {
                if (trim($data['exclude']) != "") {
                    $excludeData = explode(",",trim($data['exclude']));
                }
                $excludeData[] = $this->user_id;
                $exclude = implode(",", $excludeData);
                $this->modifyOneData("exclude", $exclude, $row['ref']);
                $this->modifyOneData("hold", 0, $row['ref']);
                $this->modifyOneData("hold_time", NULL, $row['ref']);
                $this->modifyOneData("usersCourier", NULL, $row['ref']);
                unset($excludeData);
            }
            $this->return['success'] = true;
            $this->return['results'] = "OK";
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 13003;
            $this->return['error']['message'] = "You can not accept or reject this request again";
        }
        return $this->return;
    }

    public function deliver($ref) {
        $data = $this->query("SELECT * FROM `orderData` WHERE `status` = 'DELIVERY-ONGOING' AND `usersCourier` = ".$this->user_id." AND `orderData`.`order_id` = ".$ref, false, "list");

        if ($data) {
            foreach ($data as $row) {
                $this->changeOrderDataStatus($row['ref'], "DELIVERED", true);
            }
            $this->return['success'] = true;
            $this->return['results'] = "OK";
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 13003;
            $this->return['error']['message'] = "You can not accept or reject this request again";
        }

        return $this->return;
    }

    public function dumpLocation($array) {
        foreach($array as $row) {
            $data = $this->listOneData($row['item_id']);
            $orderData = $this->listOne($data['order_id']);

            if ($data['status'] == "DELIVERY-ONGOING") {
                $dataSave['order_id'] = $data['order_id'];
                $dataSave['courier_id'] = $this->user_id;
                $dataSave['item_id'] = $data['ref'];
                $dataSave['longitude'] = $row['longitude'];
                $dataSave['latitude'] = $row['latitude'];
                $dataSave['del_longitude'] = $orderData['longitude'];
                $dataSave['del_latitude'] = $orderData['latitude'];

                if ($this->addLocation($dataSave)) {
                    $this->return['success'] = true;
                    $this->return['results'] = "OK";
                } else {
                    $this->return['success'] = false;
                    $this->return['error']['code'] = 13022;
                    $this->return['error']['message'] = "An error occured while logging location for this item";
                }

            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 13021;
                $this->return['error']['message'] = "You can only log locations for items whose delivery is ongoing";
            }
        }

        return $this->return;
    }

    public function respond($data) {
        if ($data['response'] == "yes") {
            return $this->acceptOrder();
        } else {
            return $this->rejectOrder();
        }
    }

    public function miniData($id) {
        $data = $this->listOne($id);
        return array(
            "ref" => intval($data['ref']),
            "address" => $data['address']
        );
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`orders` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `user_id` INT NOT NULL, 
            `tx_id` INT NOT NULL, 
            `tip` DOUBLE NOT NULL, 
            `tax` DOUBLE NOT NULL, 
            `delivery_fee` DOUBLE NOT NULL DEFAULT  0, 
            `sub_total` DOUBLE NOT NULL DEFAULT  0, 
            `discount` DOUBLE NOT NULL DEFAULT  0, 
            `total` DOUBLE NOT NULL DEFAULT  0, 
            `refund` DOUBLE NOT NULL DEFAULT  0, 
            `notes` VARCHAR(255) NULL,
            `delivery_time` VARCHAR(50) NULL,
            `address` VARCHAR(500) NULL,
            `longitude` DOUBLE NOT NULL, 
            `latitude` DOUBLE NOT NULL, 
            `order_status` varchar(20) NOT NULL DEFAULT 'NEW',
            `payment_status` varchar(20) NOT NULL DEFAULT 'NEW',
            `payment_data` varchar(255) NULL,
            `flag_status` INT NOT NULL DEFAULT 0,
            `flag_note` TEXT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`inventory`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`inventory`";

        $this->query($query);
    }
}
?>