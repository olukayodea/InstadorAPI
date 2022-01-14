<?php
class cart extends common {
    public $data = array();
    public $id;
    public $string;
    public $user_id;
    public $store_id;
    public $location = array();
    public $result = array();
    public $return = array();

    public function add($array, $replace=false, $sync=false) {
        global $inventory;

        if (($replace == true) || ($sync == true)) {
            $data = $array;
            if ($replace == true) {
                $this->clear();
            }
        } else {
            $data[] = $array;
        }

        $count = 0;
        foreach ($data as $row) {
            $checkCart = $this->findInCart($row['item_id']);
            if ($checkCart) {
                $quantity = $checkCart['quantity']+$row['quantity'];
                $this->modifyOne("quantity", $quantity, $checkCart['ref']);
                $count++;
            } else {
                if ($inventory->listOne($row['item_id'])) {
                    $this->insert("cart", array("user_id"=>$this->user_id, "item_id"=>$row['item_id'], "quantity"=>$row['quantity']));
                    $count++;
                }
            }
        }
        if ($count > 0) {
            return true;
        }
        return false;
    }

    private function findInCart ($id) {
        return $this->getSortedList($this->user_id, "user_id", "item_id", $id, false, false, "ref", "ASC", "AND", false, false, "getRow");
    }

    public function edit($array) {
        if (!$this->listOne($array['cart_id'])) {
            return false;
        }

        if ($this->modifyOne( "quantity", $array['quantity'], $array['cart_id'])) {
            return $this->formatResult($this->listOne($array['cart_id']), true);
        }
    }

    public function remove() {
        return $this->delete("cart", $this->id);
    }

    public function clear() {
        return $this->delete("cart", $this->user_id, "user_id");
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("cart", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("cart", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("cart", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("cart", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("cart", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function cartTotal() {
        global $inventory;
        $data = $this->getSortedList($this->user_id, "user_id");
        $total = 0;
        foreach ($data as $row) {
            $oneData = $inventory->listOne($row['item_id']);
            if ($oneData['sales'] == 1) {
                $amount = $oneData["amount"] - (($oneData['sales_value']/100)*$oneData["amount"]);
            } else {
                $amount = $oneData["amount"];
            }
            $itemCost = $row['quantity']*$amount;
            $total = $total+$itemCost;
        }

        return $total;
    }

    public function getData( $array ) {
        global $inventory;
        $total = 0;
        $i = 0;
        foreach ($array as $row) {
            $oneData = $inventory->listOne($row['item_id']);
            if ($oneData['sales'] == 1) {
                $amount = $oneData["amount"] - (($oneData['sales_value']/100)*$oneData["amount"]);
            } else {
                $amount = $oneData["amount"];
            }
            $itemCost = $row['quantity']*$amount;
            $total = $total+$itemCost;

            $data[] = array("ref"=>$i, "item_id"=>$row['item_id'], "quantity"=>$row["quantity"]);
            $i++;
        }

        $this->return['success'] = true;
        $this->return['cartCount'] = count( $data );
        $this->return['cartTotal'] = $total;
        $this->return['cartItem'] = $this->formatResult( $data );

        return $this->return;
    }

    public function getCart() {
        return $this->formatResult( $this->getSortedList($this->user_id, "user_id"));
    }

    public function getOrder() {
        return $this->getSortedList($this->user_id, "user_id");
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
            return $data;
        } else {
            return [];
        }
    }

    private function clean($data) {
        global $inventory;
        $data['ref'] = intval( $data['ref'] );
        $data['item'] = $inventory->formatResult( $inventory->listOne($data['item_id']), true);
        unset($data['data']);
        unset($data['user_id']);
        unset($data['item_id']);
        unset($data['create_time']);
        unset($data['modify_time']);
        return $data;
    }

    public function retrieveAPI($type) {
        return $this->return;
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`cart` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `user_id` INT NOT NULL, 
            `item_id` INT NOT NULL, 
            `quantity` INT NOT NULL, 
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