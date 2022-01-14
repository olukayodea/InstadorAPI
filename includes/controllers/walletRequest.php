
<?php
class walletRequest extends walletAdvise {
    public function addRequest($array) {
        return $this->insert("walletRequest", $array);
    }

    public function generaList() {
        return $this->query("SELECT SUM(`amount`) AS `amount`, `user_type`, `user_type_id`, `user_type_slug`, `batch_id` FROM `walletRequest` WHERE `downloaded` < 1 GROUP BY `user_type_slug` ORDER BY `user_type_slug`", false, "list");
    }

    public function formatRequestResult($data, $single=false ) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanRequest($data[$i], true);
                }
            } else {
                $data = $this->cleanRequest($data);
            }
        }else {
            return [];
        }
        return $data;
    }

    private function cleanRequest($data) {
        global $usersStoreAdmin;
        global $usersCourier;
        global $usersSiteAdmin;

        global $bankAccount;
        $data['amount'] = floatval(round($data['amount'], 2));
        $data['user_type_id'] = intval($data['user_type_id']);
        if ($data['user_type'] == "courier") {
            $data['user'] = $usersCourier->userData($data['user_type_id']);
            $data['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($data['user_type_id'], "courier"), true);
        } else if ($data['user_type'] == "store") {
            $data['user'] = $usersStoreAdmin->userData($data['user_type_id']);
            $data['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($data['user_type_id'], "store"), true);
        }
        $data['created_by'] = $usersSiteAdmin->userData($data['created_by']);

        unset($data['user_type_slug']);
        return $data;
    }

    public function initialize_request_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`walletRequest` (
            `ref` INT NOT NULL AUTO_INCREMENT,
            `batch_id` VARCHAR(50) NOT NULL, 
            `wallet_id` INT NOT NULL, 
            `user_type` VARCHAR(50) NOT NULL, 
            `user_type_id` INT NOT NULL,
            `user_type_slug` VARCHAR(50) NOT NULL, 
            `amount` DOUBLE NOT NULL, 
            `request_fee` INT NOT NULL,
            `downloaded` INT NOT NULL,
            `created_by` INT NOT NULL,
            `downloaded_by` INT NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_request_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`walletRequest`";

        $this->query($query);
    }

    public function delete_request_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`walletRequest`";

        $this->query($query);
    }
}
?>