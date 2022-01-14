<?php
class storeList extends common {
    public $data = array();
    public $id;
    public $store_id;
    public $user_id;
    public $result = array();
    public $return = array();
    
    public function add() {
        return $this->insert("storeList", array( "store_id" => $this->store_id, "user_id" => $this->user_id ));
    }

    public function remove($id, $ref="ref") {
        return $this->delete("storeList", $id, $ref);
    }

    public function getDetails($id, $tag="ref") {
        return $this->getOne("storeList", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("storeList", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("storeList", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("storeList", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("storeList", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("storeList", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function checkInStore() {
        return $this->query("SELECT `ref` FROM `storeList` WHERE `store_id` = :store_id AND `user_id` = :user_id", array(":store_id"=>$this->store_id, ":user_id"=>$this->user_id), "getCol");
    } 

    public function storeCount($id) {
        return $this->query("SELECT COUNT(`ref`) FROM `storeList` WHERE `store_id` =  ".$id, false, "getCol");
    }

    public function adminStoreList($id, $start, $limit) {
        $return['data'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', $start, $limit);
        $return['counts'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', false, false, "count");

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
        $data['store_id'] = intval( $data['ref'] );

        if ($data['verified'] == 1 ) {
            $data['verified'] = true;
        } else {
            $data['verified'] = false;
        }

        $data['url'] = array("store_url"=>$data['store_url'], "store_logo"=>$data['store_logo']);

        $data['location'] = $this->distance( $this->location['latitude'], $this->location['longitude'], $data['latitude'], $data['longitude'] );
        unset($data['total']);
        unset($data['store_logo']);
        unset($data['store_url']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['modify_time']);

        return $data;
    }

    public function listStores($string) {
        $data = explode(",", $string);

        foreach ( $data as $storeList ) {
            $return[] = $this->clean( $this->listOne($storeList));
        }
        return $return;
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`storeList` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `store_id` int NOT NULL,
            `user_id` int NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`storeList`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`storeList`";

        $this->query($query);
    }
}
?>