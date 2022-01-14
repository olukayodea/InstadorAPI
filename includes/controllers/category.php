<?php
class category extends common {
    public $data = array();
    public $id;
    public $user_id;
    public $store_id;
    public $location = array();
    public $result = array();
    public $return = array();
    
    public function add($data) {
        $data['status'] = "ACTIVE";
        $this->id = $this->insert("category", $data);

        return $this->id;
    }

    public function edit($data) {
        $replace = array_keys( $data );

        $this->id = $this->replace("category", $data, $replace);
        if ($this->id) {
            return $this->formatResult( $this->listOne( $this->id ), true, false );
        }
    }
    
    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("category", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function remove() {
        $dontDelete = false;
        if ($this->checkData()) {
            $dontDelete = true;
        }

        if ($dontDelete === true) {
            $this->editOne("status", "DELETED", $this->id);
        } else {
            $this->delete("category",  $this->id);
        }

        return true;
    }

    public function toggleStatus() {
        $data = $this->listOne($this->id);
        if ($data) {
            if ($data['status'] == "ACTIVE") {
                $updateData = "INACTIVE";
            } else if ($data['status'] == "INACTIVE") {
                $updateData = "ACTIVE";
            }

            return $this->modifyOne("status", $updateData, $this->id);
        }
    }

    /**
     * Check if the store has other data in other tables, if there is data @return true else @return false
     */
    private function checkData() {
        global $inventory;
        // get items to delete
        $data = $this->query( "SELECT * FROM `inventory` WHERE FIND_IN_SET('".$this->id."', `category_id`)", false, "list");

        foreach  ($data as $row) {
            $val = explode(",", $row['category_id']);

            $key = array_search($this->id, $val);
            if ($key !== false ) {
                unset($val[$key]);
            }

            $newVal = implode(",", $val);
            $inventory->modifyOne("category_id", $newVal, $row['ref']);
        }

        if (count($data) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("category", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("category", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("category", $id, $tag);
    }

    public function listMultiple($id, $tag="ref") {
        return $this->query("SELECT * FROM `category` WHERE `".$tag."` IN (".$id.")", false, "list");
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("category", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("category", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
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
        $data['ref'] = intval( $data['ref'] );

        $account['online'] = ("ACTIVE" == $data['status']) ? true : false;
        $account['offline'] = ("INACTIVE" == $data['status']) ? true : false;
        $data['categoryStatus'] = $account;

        unset($data['status']);
        unset($data['created_by']);
        unset($data['store_id']);
        unset($data['create_time']);
        unset($data['modify_time']);

        return $data;
    }

    public function retrieveAPI() {
        if ($this->id > 0) {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true );
        } else {
            $this->result = $this->getSortedList("ACTIVE", "status", "store_id", $this->store_id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result );
            $this->return['data'][] = array("ref" => 0, "name" => "Default", "categoryStatus" => ["online" => true, "offline" => false]);
        }
        return $this->return;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`category` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `store_id` INT NOT NULL, 
            `name` varchar(255),
            `status` varchar(255),
            `created_by` INT NOT NULL, 
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`category`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`category`";

        $this->query($query);
    }
}
?>