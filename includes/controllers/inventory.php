<?php
class inventory extends common {
    public $data = array();
    public $id;
    public $string;
    public $category_id;
    public $user_id;
    public $store_id;
    public $isCourier = false;
    public $admin = false;
    public $showStore = true;
    public $location = array();
    public $result = array();
    public $return = array();

    public function add($array) {
        global $media;
        global $store;
        $data = $array;
        unset($data['image']);
        $storeData = $store->listOne($data['store_id']);

        // if ($storeData['verified'] == 2) {
            $this->store_id = $data['store_id'];
            
            $data['category_id'] = implode(",", $data['category_id']);
            $data['data'] = serialize($data['data']);
            $data['sku'] = $this->confirmUnique($this->createSku($data['name']));

            $this->id = $this->insert( "inventory", $data );
            if ($this->id) {
                foreach ($array['image'] as $logo) {
                    $media->findID($logo, $this->id);
                }
                return array("ID" => $this->id, "SKU"=>$data['sku']);
            }
        // } else if ($storeData['verified'] == 1) {
        //     return null;
        // } else {
        //     return false;
        // }
    }

    private function createSku($name) {
        return strtoupper( substr( $this->store_id.$this->createRandomPassword(6).$name, 0, 10) );
    }
    
    function confirmUnique($key) {
        if ($this->checkExixst("inventory", "sku", $key) == 0) {
            return $key;
        } else {
            return $this->confirmUnique($this->createSku($key));
        }
    }

    public function edit($data) {}

    public function remove($id) {}

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("inventory", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("inventory", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("inventory", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("inventory", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'name', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("inventory", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function searchItem ( $string ) {
        return $this->query("SELECT * FROM `inventory` WHERE ( `sku` LIKE '%".$string."%' OR `name` LIKE '%".$string."%' OR `tags` LIKE '%".$string."%' OR `description` LIKE '%".$string."%' ) AND `status` = 'ACTIVE'", false, "list");
    }

    public function listStores($start, $limit, $admin=false) {
        //query for result

        $tag = "";
        if ($this->category_id > 0) {
            $tag .= " AND FIND_IN_SET('".$this->category_id."', `category_id`)";
        }
        if ($admin == false) {
            $tag .= " AND `status` = 'ACTIVE'";
        }


        $query = "SELECT * FROM `inventory` WHERE `status` != 'DELETED'".$tag." AND `store_id` = ".intval($this->store_id)." ORDER BY `name` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `ref` FROM `inventory` WHERE `status` != 'DELETED'".$tag." AND `store_id` = ".intval($this->store_id);
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function listAllAdmin($start, $limit) {
        $query = "SELECT * FROM `inventory` WHERE `status` != 'DELETED' ORDER BY `name` ASC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `ref` FROM `inventory` WHERE `status` != 'DELETED'";
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
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
        global $store;
        global $category;
        global $media;
        $data['ref'] = intval( $data['ref'] );
        $data['weight'] = floatval( $data['weight'] );
        $data['amount'] = floatval( $data['amount'] );

        $store->user_id = $this->user_id;
        
        if (!$this->isCourier) {
            if (!$this->admin) {
                $data['category'] = $category->formatResult( $category->listMultiple( $data['category_id'] ));
                $data['otherData'] = $this->cleanOtherData(unserialize($data['data']));
            }

            if ($this->showStore == true) {
                $store->minimal = true;
                $data['store'] = $store->formatResult( $store->listOne( $data['store_id']), true);
            }
            $data['tags'] = (null !== $data['tags']) ? explode(",", $data['tags']) : null;
            $account['visible'] = ("ACTIVE" == $data['status']) ? true : false;
            $account['hidden'] = ("INACTIVE" == $data['status']) ? true : false;
        }
        $data['gst'] = (1 == $data['GST']) ? true : false;
        $data['pst'] = (1 == $data['PST']) ? true : false;
        $data['mainImage'] = $media->checkValid($media->getMain("partner", "item", $data['ref']));
        if (!$this->isCourier) {
            $data['gallery'] = $media->getCommon("partner", "item", $data['ref']);
            $data['itemStatus'] = $account;
            if ($data['sales'] == 1) {
                $data['sale']['active'] = true;
                $data['sale']['salesRate'] = $data['sales_value'];
            } else {
                $data['sale']['active'] = false;
            }
        }

        if ($this->isCourier || $this->admin) {
            if ($this->isCourier) {
                unset($data['amount']);
                unset($data['sku']);
                unset($data['mainImage']);

            }
            unset($data['description']);
            unset($data['dietary']);
        }
        $data['creationDate'] = strtotime( $data['create_time'] );
        $data['lastModified'] = strtotime( $data['modify_time'] );
        
        unset($data['dietary']);
        unset($data['commission']);
        unset($data['GST']);
        unset($data['PST']);
        unset($data['sales']);
        unset($data['sales_value']);
        unset($data['data']);
        unset($data['store_id']);
        unset($data['category_id']);
        unset($data['status']);
        unset($data['create_time']);
        unset($data['modify_time']);

        return $data;
    }

    private function cleanOtherData($data) {
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['value'] = explode(",", $data[$i]['value']);
        }
        return $data;
    }

    public function storeCount($id) {
        return $this->query("SELECT COUNT(`ref`) FROM `inventory` WHERE `store_id` =  ".$id." AND `status` !='DELETED'", false, "getCol");
    }

    public function getRandomStore() {
        $this->showStore = false;
        return $this->formatResult( $this->query("SELECT * FROM `inventory` WHERE `store_id` =  ".$this->store_id." AND `status` ='ACTIVE' ORDER BY RAND() LIMIT 1", false, "getRow"), true);
    }

    public function miniData($id) {
        $data = $this->listOne($id);
        return array(
            "ref" => intval($data['ref']),
            "sku" => $data['sku'],
            "name" => $data['name'],
            "amount" => floatval($data['amount']),
            "weight" => floatval($data['weight'])
        );
    }

    public function adminStoreList($id, $start, $limit) {
        $return['data'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', $start, $limit);
        $return['counts'] = $this->getSortedList($id, "store_id", false, false, false, false, "ref", "ASC",'AND', false, false, "count");

        return $return;
    }

    public function retrieveStoreAPI($page, $admin=false) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;
        $this->result = $this->listStores($start, $limit, $admin);
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
        return $this->return;
    }

    public function retrieveAPI($type, $page=1) {
        global $options;
        if ($type == "search" ) {
            $this->result = $this->formatResult( $this->searchItem( $this->string ) );
        } else if ($type == "get" ) {

            if (is_numeric($this->string)) {
                $this->result = $this->formatResult( $this->listOne( $this->string ), true );
            } else {
                $this->result = $this->formatResult( $this->listOne( $this->string, "sku" ), true );
            }
        } else {
            if (intval($page) == 0) {
                $page = 1;
            }
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->listAllAdmin($start, $limit);
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
            return $this->return;
        }

        if ($this->result) {
            $this->return['success'] = true;
            $this->return['results'] = "OK";
            $this->return['data'] = $this->result;
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 11007;
            $this->return['message'] = "No record found";
        }
        return $this->return;
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`inventory` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `store_id` INT NOT NULL, 
            `name` VARCHAR(255) NOT NULL, 
            `category_id` VARCHAR(500) NOT NULL, 
            `amount` DOUBLE NOT NULL, 
            `weight` DOUBLE NOT NULL, 
            `description` TEXT NOT NULL, 
            `sku` VARCHAR(50) NOT NULL, 
            `data` TEXT NULL, 
            `sales` INT NULL, 
            `sales_value` INT NULL, 
            `tags` TEXT NULL, 
            `GST` INT NULL, 
            `PST` INT NULL, 
            `commission` INT NULL, 
            `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`),
            UNIQUE KEY `sku` (`sku`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;
        ALTER TABLE `inventory` ADD `commission` INT NOT NULL AFTER `PST`;";

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