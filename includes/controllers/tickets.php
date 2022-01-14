<?php
class tickets extends common {
    public $data = array();
    public $id;
    public $admin_id;
    public $courier_id;
    public $store_id;
    public $user_id;
    public $location = array();
    public $result = array();
    public $return = array();
    public $admin = false;
    
    public function add($data) {
        $data['status'] = "NEW";
        if ($data['last_update_by'] == "store") {
            $data['store_id'] = $this->store_id;
        } else if ($data['last_update_by'] == "admin") {
            $data['admin_id'] = $this->admin_id;
        } else if ($data['last_update_by'] == "courier") {
            $data['courier_id'] = $this->courier_id;
        } else {
            $data['user_id'] = $this->user_id;
        }

        $this->id = $this->insert("tickets", $data);

        $data = $this->listOne($this->id);
        if ($data['ticket_id'] === NULL) {
            $this->editOne("ticket_id", $this->id, $this->id);

            if ($data['last_update_by'] == "admin") {
                $ticketData = $this->listOne($data['ticket_id']);
                $this->editOne("user_id", $ticketData['user_id'], $this->id);
                $this->editOne("store_id", $ticketData['store_id'], $this->id);
                $this->editOne("courier_id", $ticketData['courier_id'], $this->id);
            }

        } else {
            $this->editOne("status", "UPDATED", $this->id, "ticket_id");
        }
        $this->editOne("last_update_by", $data['last_update_by'], $this->id);

        return $this->id;
    }

    public function cancelUser($type) {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['user_id'])) {
                return -1;
            }

            if ($type == "remove") {
                $tag = "ticket_id";
            } else {
                $tag = "ref";
            }

            return $this->remove($tag);
        } else {
            return false;
        }
    }

    public function cancelStore($type) {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['store_id'])) {
                return -1;
            }

            if ($type == "remove") {
                $tag = "ticket_id";
            } else {
                $tag = "ref";
            }

            return $this->remove($tag);
        } else {
            return false;
        }
    }

    public function cancelCourier($type) {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['courier_id'])) {
                return -1;
            }

            if ($type == "remove") {
                $tag = "ticket_id";
            } else {
                $tag = "ref";
            }

            return $this->remove($tag);
        } else {
            return false;
        }
    }

    public function cancelAdmin($type) {
        $data = $this->listOne($this->id);
        if ($data) {
            if ($type == "remove") {
                $tag = "ticket_id";
            } else {
                $tag = "ref";
            }

            return $this->remove($tag);
        } else {
            return false;
        }
    }

    public function closeUser() {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['user_id'])) {
                return -1;
            }
            return $this->editOne("status", "CLOSED", $this->id, "ticket_id");
        } else {
            return false;
        }
    }

    public function closeCourier() {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['courier_id'])) {
                return -1;
            }
            return $this->editOne("status", "CLOSED", $this->id, "ticket_id");
        } else {
            return false;
        }
    }

    public function closeStore() {
        $data = $this->listOne($this->id);
        if ($data) {
            if (intval($this->user_id) != intval($data['store_id'])) {
                return -1;
            }
            return $this->editOne("status", "CLOSED", $this->id, "ticket_id");
        } else {
            return false;
        }
    }

    public function closeAdmin() {
        $data = $this->listOne($this->id);
        if ($data) {
            return $this->editOne("status", "CLOSED", $this->id, "ticket_id");
        } else {
            return false;
        }
    }

    public function edit($data) {
        $replace = array_keys( $data );

        $this->id = $this->replace("tickets", $data, $replace);
        if ($this->id) {
            return $this->formatResult( $this->listOne( $this->id ), true );
        }
    }
    
    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("tickets", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function remove($tag="ticket_id") {
        return $this->editOne("status", "DELETED", $this->id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("tickets", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("tickets", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("tickets", $id, $tag);
    }

    public function listMultiple($id, $tag="ref") {
        return $this->query("SELECT * FROM `tickets` WHERE `".$tag."` IN (".$id.")", false, "list");
    }

    private function getAll($type, $view, $start, $limit) {
        $tag = "";
        if (strtolower($view) == "closed") {
            $tag = " AND `status` = 'CLOSED'";
        } else if (strtolower($view) == "open") {
            $tag = " AND `status` = 'OPENED'";
        } else if (strtolower($view) == "new") {
            $tag = " AND (`status` = 'UPDATED' OR `status` = 'NEW')";
        }

        $filter = "";
        if ($this->admin === false) {
            if  ($type == "store_id") {
                $filter = "`".$type."` = ".$this->store_id." AND ";
            } else {
                $filter = "`".$type."` = ".$this->user_id." AND ";
            }
        }

        //query for result
        $query = "SELECT * FROM `tickets` WHERE ".$filter."`status` != 'DELETED'".$tag." GROUP BY `ticket_id` ORDER BY `create_time` DESC LIMIT ".$start.",".$limit;
        $returm['data'] = $this->query($query, false, "list");
        
        //query for pagination count
        $query = "SELECT `ref` FROM `tickets` WHERE ".$filter."`status` != 'DELETED'".$tag." GROUP BY `ticket_id`";
        $returm['counts'] = $this->query($query, false, "count");

        return $returm;
    }

    public function getCounts($type) {
        $filter = "";
        if ($this->admin === false) {
            $filter = "`".$type."` = ".$this->user_id." AND ";
        }

        $returm['open'] = $this->query("SELECT `ref` FROM `tickets` WHERE ".$filter."`status` != 'DELETED' AND `status` = 'OPENED' GROUP BY `ticket_id`", false, "count");
        $returm['closed'] = $this->query("SELECT `ref` FROM `tickets` WHERE ".$filter."`status` != 'DELETED' AND `status` = 'CLOSED' GROUP BY `ticket_id`", false, "count");
        $returm['new'] = $this->query("SELECT `ref` FROM `tickets` WHERE ".$filter."`status` != 'DELETED' AND (`status` = 'UPDATED' OR `status` = 'NEW') GROUP BY `ticket_id`", false, "count");
        $returm['all'] = $this->query(trim("SELECT `ref` FROM `tickets` WHERE ".$filter."`status` != 'DELETED'", "AND ")." GROUP BY `ticket_id`", false, "count");

        return $returm;
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("tickets", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("tickets", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
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
        global $store;
        global $usersCustomers;
        global $usersCourier;
        global $usersStoreAdmin;
        $return['ref'] = intval($data['ref']);
        $return['ticket_id'] = intval($data['ticket_id']);
        $return['store'] = $store->storeData($data['store_id']);
        $return['user'] = $usersCustomers->userData( $data['user_id'] );
        $return['courier'] = $usersCourier->userData( $data['courier_id'] );
        $return['admin'] = $usersStoreAdmin->userData( $data['admin_id'] );
        $return['order'] = intval($data['order_id']);
        $return['title'] = $data['title'];
        $return['message'] = $data['message'];
        $account['isNew'] = ("NEW" == $data['status']) ? true : false;
        $account['closed'] = ("CLOSED" == $data['status']) ? true : false;
        $account['new'] = ("UPDATED" == $data['status']) ? true : false;
        $account['opened'] = ("OPENED" == $data['status']) ? true : false;
        $return['status'] = $account;
        $return['created'] = strtotime( $data['create_time'] );
        $return['lastmodified'] = strtotime( $data['modify_time'] );
        return $return;
    }

    public function getUser($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if (intval($this->id) > 0) {
            $data = $this->listOne($this->id);
            $this->result = $this->getSortedList($this->id, "ticket_id", "user_id", $this->user_id);
            if ($this->result) {
                if ((intval($data['last_update_by']) != 'user') && ($data['status'] == 'UPDATED')) {
                    $this->modifyOne("status", "OPENED", $this->id, "ticket_id");
                }
                $this->return['success'] = true;
                $this->return['mainData'] = $this->formatResult( $data, true );
                $this->return['data'] = $this->formatResult( $this->result );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->getAll("user_id", $view, $start, $limit);
            $this->return['success'] = true;

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
        }
        return $this->return;
    }

    public function getAmin($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if (intval($this->id) > 0) {
            $data = $this->listOne($this->id);
            $this->result = $this->getSortedList($this->id, "ticket_id", "admin_id", $this->user_id);
            if ($this->result) {
                if (($data['status'] == 'NEW') || ($data['status'] == 'UPDATED')) {
                    $this->modifyOne("status", "OPENED", $this->id, "ticket_id");
                }
                $this->return['success'] = true;
                $this->return['mainData'] = $this->formatResult( $data, true );
                $this->return['data'] = $this->formatResult( $this->result );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->getAll("admin_id", $view, $start, $limit);
            $this->return['success'] = true;

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
        }
        return $this->return;
    }

    public function getStore($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if (intval($this->id) > 0) {
            $data = $this->listOne($this->id);
            $this->result = $this->getSortedList($this->id, "ticket_id", "store_id", $this->store_id);
            if ($this->result) {
                if ((intval($data['last_update_by']) != 'store') && ($data['status'] == 'UPDATED')) {
                    $this->modifyOne("status", "OPENED", $this->id, "ticket_id");
                }
                $this->return['success'] = true;
                $this->return['mainData'] = $this->formatResult( $data, true );
                $this->return['data'] = $this->formatResult( $this->result );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->getAll("store_id", $view, $start, $limit);
            $this->return['success'] = true;

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
        }
        return $this->return;
    }

    public function getCourier($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }
        if (intval($this->id) > 0) {
            $data = $this->listOne($this->id);
            $this->result = $this->getSortedList($this->id, "ticket_id", "courier_id", $this->user_id);
            if ($this->result) {
                if ((intval($data['last_update_by']) != 'courier') && ($data['status'] == 'UPDATED')) {
                    $this->modifyOne("status", "OPENED", $this->id, "ticket_id");
                }
                $this->return['success'] = true;
                $this->return['mainData'] = $this->formatResult( $data, true );
                $this->return['data'] = $this->formatResult( $this->result );
            } else {
                $this->return['success'] = false;
                $this->return['error']['code'] = 11007;
                $this->return['message'] = "No record found";
            }
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->getAll("courier_id", $view, $start, $limit);
            $this->return['success'] = true;

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
        }
        return $this->return;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`tickets` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `ticket_id` INT NULL, 
            `store_id` INT NULL, 
            `user_id` INT NULL, 
            `courier_id` INT NULL, 
            `admin_id` INT NULL, 
            `order_id` INT NULL, 
            `title` varchar(255) NOT NULL,
            `message` TEXT NOT NULL,
            `last_update_by` varchar(20) NOT NULL,
            `status` varchar(255),
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`tickets`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE IF EXISTS `".dbname."`.`tickets`";

        $this->query($query);
    }
}
?>