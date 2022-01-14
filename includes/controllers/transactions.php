<?php
class transactions extends common {
    public $data = array();
    public $id;
    public $string;
    public $user_id;
    public $store_id;
    public $location = array();
    public $result = array();
    public $return = array();

    public function add($array) {
        return $this->insert("transactions", $array);
    }

    public function remove($id) {
        return $this->delete("transactions", $id);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("transactions", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("transactions", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("transactions", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("transactions", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("transactions", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
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
        return $data;
    }

    public function retrieveAPI($type) {
        return $this->return;
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`transactions` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `user_id` INT NOT NULL, 
            `tx_type_id` INT NOT NULL, 
            `tx_type` VARCHAR(50) NOT NULL,
            `tx_dir` VARCHAR(3) NOT NULL DEFAULT 'CR',
            `account_id` INT NOT NULL, 
            `total` DOUBLE NOT NULL, 
            `gateway_data` TEXT NULL, 
            `gateway_status` VARCHAR(50) NOT NULL DEFAULT 'NEW',
            `status` varchar(20) NOT NULL DEFAULT 'NEW',
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