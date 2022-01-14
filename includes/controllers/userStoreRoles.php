<?php
class userStoreRoles extends common {
    public $user_id;
    public $store_id;
    public $role_id;
    
    public function addRole() {
        return $this->insert("userStoreRoles", array( "role_id" => $this->role_id, "store_id" => $this->store_id, "user_id" => $this->user_id ));
    }

    public function removeRole($id, $ref="ref") {
        return $this->delete("userStoreRoles", $id, $ref);
    }

    public function modifyOneRole($tag, $value, $id, $ref="ref") {
        return $this->updateOne("userStoreRoles", $tag, $value, $id,$ref);
    }

    public function listOneRole($id, $tag="ref") {
        return $this->getOne("userStoreRoles", $id, $tag);
    }

    public function listOneValueRole($id, $reference) {
        return $this->getOneField("userStoreRoles", $id, "ref", $reference);
    }

    public function getSortedListRole($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("userStoreRoles", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function modifyInitial() {
        if ( $this->getSortedListRole(0, 'store_id', "user_id", $this->user_id)) {
            return $this->query("DELETE FROM `userStoreRoles` WHERE `store_id` = 0 AND `user_id` = $this->user_id");
        }
    }

    public function formatRoleResult($data, $single=false) {
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

    public function getRoles() {
        $data = $this->getUserRoles();
        foreach ($data as $row) {
            $roleData = $this->roles['Store_Roles'][$row['role_id']];
            $roleData['role_id'] = $row['role_id'];
            $roleData['store'] = $this->getUserRolesStore( $row['role_id'] );
            $return[] = $roleData;
        }

        return $return;
    }

    private function getUserRoles() {
        $data = $this->query("SELECT `role_id` FROM `userStoreRoles` WHERE `user_id` = $this->user_id GROUP BY `role_id` ORDER BY `role_id`", false, "list");
        return $data;
    }

    private function getUserRolesStore($role_id) {
        $data = $this->query("SELECT `userStoreRoles`.`store_id`, `store`.`name` FROM `userStoreRoles` INNER JOIN `store` ON `userStoreRoles`.`store_id` = `store`.`ref` WHERE `user_id` = $this->user_id AND `role_id` = $role_id", false, "list");
        return $data;
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`userStoreRoles` (
            `ref` INT NOT NULL AUTO_INCREMENT,
            `store_id` INT NOT NULL, 
            `role_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`userStoreRoles`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`userStoreRoles`";

        $this->query($query);
    }
}
?>