<?php
class orderLocation extends common {
    public $data = array();
    public $id;
    public $string;
    public $user_id;
    public $store_id;
    public $order_id;
    public $item_id;
    public $isCourier = false;
    public $user = false;
    public $admin = false;
    public $listCourier = false;
    public $location = array();
    public $result = array();
    public $return = array();

    public function addLocation($array) {
        return $this->insert("orderLocation", $array);
    }

    public function removeLocation($id, $ref="order_id") {
        return $this->delete("orderLocation", $id, $ref);
    }

    public function modifyOneLocation($tag, $value, $id, $ref="ref") {
        return $this->updateOne("orderLocation", $tag, $value, $id,$ref);
    }

    public function listOneLocation($id, $tag="ref") {
        return $this->getOne("orderLocation", $id, $tag);
    }

    public function getSortedLocationList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("orderLocation", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function formatLocationResult($data, $single=false) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanLocation($data[$i]);
                }
            } else {
                $data = $this->cleanLocation($data);
            }
        } else {
            return [];
        }
        return $data;
    }

    private function cleanLocation($data) {
        if ($data) {
            $return['status'] = true;
            $return['longitude'] = floatval(round($data['longitude'], 6));
            $return['latitude'] = floatval(round($data['latitude'], 6));
            $return['distanceToDeliver'] = $this->distance( $data['latitude'], $data['longitude'], $data['del_latitude'], $data['del_longitude'] );
            $return['time']['timestamp'] = strtotime($data['create_time']);
            $return['time']['label'] = $this->get_time_stamp($return['time']['timestamp']);
            $return['time']['label2'] = $this->print_time($return['time']['timestamp']);
        } else {
            $return['status'] = false;
        }
        return $return;
    }
    
    public function initialize_location_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`orderLocation` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `order_id` INT NOT NULL, 
            `courier_id` INT NOT NULL, 
            `item_id` INT NOT NULL,
            `longitude` DOUBLE NOT NULL, 
            `latitude` DOUBLE NOT NULL,
            `del_longitude` DOUBLE NOT NULL, 
            `del_latitude` DOUBLE NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_location_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`orderLocation`";

        $this->query($query);
    }

    public function delete_location_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`orderLocation`";

        $this->query($query);
    }
}
?>