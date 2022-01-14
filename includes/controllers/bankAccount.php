<?php
class bankAccount extends common {
    public $data = array();
    public $id;
    public $user_id;
    public $store_id;
    public $location = array();
    public $result = array();
    public $return = array();
    
    public function add($data) {
        $data["user_id"] = $this->user_id;

        $replace[] = "transitNumber";
        $replace[] = "institutionNunmber";
        $replace[] = "accountNumber";
        $this->replace("bankAccount", $data, $replace);

        return true;
    }

    public function listOne($id, $user_type = 'store', $tag="user_id") {
        return $this->sortAll("bankAccount", $id, $tag, "user_type", $user_type, false, false, "user_id", "ASC", "AND", false, false, "getRow");
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

    private function clean($data, $showDate=false) {
        if ($showDate == true) {
            $data['lastModification'] = $data['modify_time'];
        }
        unset($data['ref']);
        unset($data['user_id']);
        unset($data['modify_time']);

        return $data;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`bankAccount` (
            `user_id` INT NOT NULL, 
            `user_type` varchar(10),
            `transitNumber` varchar(255),
            `institutionNunmber` varchar(255),
            `accountNumber` varchar(255),
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`,`user_type`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`bankAccount`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`bankAccount`";

        $this->query($query);
    }
}
?>