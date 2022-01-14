<?php
class walletAdvise extends common {
    public $data = array();
    public $id;
    public $user_id;
    public $location = array();
    public $result = array();
    public $return = array();

    public $pageView;
    public $view;

    public $admin = false;

    public function addAdvise($array) {
        return $this->insert("walletAdvise", $array);
    }

    private function batchList() {
        return $this->query("SELECT `batch_id` FROM `walletRequest` WHERE `downloaded` < 1 GROUP BY `batch_id`", false, "list");
    }

    private function getBatch($id) {
        return $this->query("SELECT * FROM `walletRequest` WHERE `batch_id` = '".$id."' AND `downloaded` < 1", false, "list");
    }

    public function generatePaymentAdvice() {
        $data = $this->batchList();
        return $this->runGenerator($data, false);
    }

    private function markAsDownloaded($id) {
        return $this->query("UPDATE `walletRequest` SET `downloaded` = 1, `downloaded_by` = ".$this->user_id." WHERE `ref` = ".$id);
    }

    private function markAsComplete($id, $type) {
        if ($type == 'courier') {
            $tag = 'fulfil_courier';
        } else if ($type == 'store') {
            $tag = 'fulfil_store';
        }
        return $this->query("UPDATE `wallet` SET `".$tag."` = 2 WHERE `ref` = ".$id);
    }

    private function getSum($batch, $ids) {
        if (count($ids) > 0) {
            return $this->query("SELECT SUM(`amount`) AS `amount`, `user_type`, `user_type_id`, `user_type_slug`, `batch_id` FROM `walletRequest` WHERE `downloaded` = 1 AND `batch_id` = '".$batch."' AND `ref` IN (".implode(",", $ids).") GROUP BY `user_type_slug` ORDER BY `user_type_slug`", false, "list");
        }
    }

    private function runGenerator($data) {
        global $usersStoreAdmin;
        global $usersCourier;
        global $bankAccount;
        global $options;

        $csvData = array();
        $csvData[] = array(
            "Batch",
            "First Name",
            "Last Name",
            "User Type",
            "Amount",
            "Transit Number",
            "Institution Number",
            "Account Number",
            "Print Date"
        );

        foreach ($data as $row) {
            global $wallet;
            $batchData = $this->getBatch($row['batch_id']);
            $temRef = array();
            foreach ($batchData as $batchRow) {
                 if ($batchRow['user_type'] == "courier") {
                    $batchRow['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($batchRow['user_type_id'], "courier"), true);
                } else if ($batchRow['user_type'] == "store") {
                    $batchRow['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($batchRow['user_type_id'], "store"), true);
                }
                if (!empty($batchRow['bankAccount'])) {
                    if ($this->markAsDownloaded($batchRow['ref'])) {
                        $this->markAsComplete($batchRow['wallet_id'], $batchRow['user_type']);
                    }
                    $temRef[] = $batchRow['ref'];
                }
            }

            $summary = $this->getSum($row['batch_id'], $temRef);
            foreach ($summary as $sumData) {
                $newLine = array();
                if ($sumData['user_type'] == "courier") {
                    $sumData['user'] = $usersCourier->userData($sumData['user_type_id']);
                    $sumData['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($sumData['user_type_id'], "courier"), true);
                    $store_id = 0;
                    $store = 0;
                    $courier_id = $sumData['user_type_id'];
                    $courier = 0-floatval(round($sumData['amount'],  2));
                } else if ($sumData['user_type'] == "store") {
                    $sumData['user'] = $usersStoreAdmin->userData($sumData['user_type_id']);
                    $sumData['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($sumData['user_type_id'], "store"), true);
                    $courier_id = 0;
                    $courier = 0;
                    $store_id = $sumData['user_type_id'];
                    $store = 0-floatval(round($sumData['amount'],  2));
                }

                if (!empty($sumData['bankAccount'])) {
                    $newLine[] = $addAdviseData['batch'] = $sumData['batch_id'];
                    $newLine[] = $addAdviseData['firstName'] = $sumData['user']['firstName'];
                    $newLine[] = $addAdviseData['lastName'] = $sumData['user']['lastName'];
                    $newLine[] = $addAdviseData['type'] = $sumData['user_type'];
                    $newLine[] = $addAdviseData['amount'] = round($sumData['amount'],  2);
                    $newLine[] = $addAdviseData['transitNumber'] = $sumData['bankAccount']['transitNumber'];
                    $newLine[] =  $addAdviseData['institutionNunmber'] = $sumData['bankAccount']['institutionNunmber'];
                    $newLine[] = $addAdviseData['accountNumber'] = $sumData['bankAccount']['accountNumber'];
                    $newLine[] = date('l jS \of F Y h:i:s A');

                    $csvData[] = $newLine;
                    $this->addAdvise($addAdviseData);

                    
                    $add['order_id'] = 0;
                    $add['store_id'] = $store_id;
                    $add['order_item_id'] = 0;
                    $add['courier_id'] = $courier_id;
                    $add['total'] = 0-floatval($addAdviseData['amount']);
                    $add['courier'] = $courier;
                    $add['store'] = $store;
                    $add['instadoor'] = 0;
                    $add['trans_id'] = 0;
                    $add['tax'] = 0;
                    $add['fulfil_store'] = 2;
                    $add['fulfil_courier'] = 2;
                    $add['fulfil_store_date'] = time();
                    $add['fulfil_courier_date'] = time();

                    $wallet->add($add);
                }
            }
        }

        $limit = intval($options->get("resultPerPage"));

        $otherData = $this->getPrevious(0, $limit);
        $return['success'] = true;
        $return['counts']['current_page'] = intval(1);
        $return['counts']['total_page'] = ceil($otherData['counts']/$limit);
        $return['counts']['rows_on_current_page'] = count($otherData['data']);
        $return['counts']['max_rows_per_page'] = intval($limit);
        $return['counts']['total_rows'] = $otherData['counts'];
        $return['link'] = $this->downloadCsv($csvData);
        $return['data'] = $this->formatAdviceResult($otherData['data']);

        return $return;
    }

    public function getPreviousList($page) {
        global $options;
        $current = (intval($page) > 0) ? (intval($page)-1) : 0;
        $limit = intval($options->get("resultPerPage"));
        $start = $current*$limit;

        $otherData = $this->getPrevious($start, $limit);
        $return['success'] = true;
        $return['counts']['current_page'] = intval(1);
        $return['counts']['total_page'] = ceil($otherData['counts']/$limit);
        $return['counts']['rows_on_current_page'] = count($otherData['data']);
        $return['counts']['max_rows_per_page'] = intval($limit);
        $return['counts']['total_rows'] = $otherData['counts'];
        $return['data'] = $this->formatAdviceResult($otherData['data']);

        return $return;

    }

    private function formatAdviceResult($data, $single=false ) {
        if ($data) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->cleanAdvice($data[$i], true);
                }
            } else {
                $data = $this->cleanAdvice($data);
            }
        }else {
            return [];
        }
        return $data;
    }

    private function cleanAdvice($data) {
        $filename = $data['batch'].".csv";
        if (!file_exists($filename)) {
            $this->generateSingle($data['batch']);
        }
        $data['link'] = URL."download/payment/singlePaymentAdvice/".$filename;
        return $data;
    }

    private function generateSingle($batch) {
        $csvData[] = array(
            "Batch",
            "First Name",
            "Last Name",
            "User Type",
            "Amount",
            "Transit Number",
            "Institution Number",
            "Account Number",
            "Print Date"
        );

        $data = $this->query("SELECT `batch`, `firstName`, `lastName`, `type`, `amount`, `transitNumber`, `institutionNunmber`, `accountNumber`, `create_time` FROM `walletAdvise` WHERE `batch` = '".$batch."'", false, "list");
        foreach($data as $row) {
            $newLine = array();
            $newLine[] = $row['batch'];
            $newLine[] = $row['firstName'];
            $newLine[] = $row['lastName'];
            $newLine[] = $row['type'];
            $newLine[] = round($row['amount'],  2);
            $newLine[] = $row['transitNumber'];
            $newLine[] = $row['institutionNunmber'];
            $newLine[] = $row['accountNumber'];
            $newLine[] = date('l jS \of F Y h:i:s A');
            $csvData[] = $newLine;
        }        
        $this->downloadCsv($csvData, $batch);

        return $batch.".csv";
    }

    private function downloadCsv($data, $filename="INSTA_PAYMENT_ADVISE") {
        $f = fopen ($filename.'.csv','w');
    
        $csv = "";
        foreach ($data as $record){
            $csv.= implode(",", $record)."\n"; //Append data to csv
        }
        fwrite($f, $csv);
        fclose($f);
        return URL."download/payment/paymentAdvice";
    }

    private function getPrevious($start, $limit ) {
        $return['data'] = $this->query("SELECT COUNT(*) AS `recordCount`, `batch`, `create_time` FROM `walletAdvise` GROUP BY `batch` ORDER BY `create_time` DESC LIMIT ".$start.",".$limit, false, "list");
        $return['counts'] = $this->query("SELECT * FROM `walletAdvise` GROUP BY `batch`", false, "count");

        return $return;
    }

    public function initialize_advice_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`walletAdvise` (
            `ref` INT NOT NULL AUTO_INCREMENT,
            `batch` VARCHAR(50) NOT NULL, 
            `firstName` VARCHAR(255) NOT NULL,
            `lastName` VARCHAR(255) NOT NULL, 
            `type` VARCHAR(50) NOT NULL,
            `amount` DOUBLE NOT NULL, 
            `transitNumber` VARCHAR(50) NOT NULL,
            `institutionNunmber` VARCHAR(50) NOT NULL,
            `accountNumber` VARCHAR(50) NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_advice_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`walletAdvise`";

        $this->query($query);
    }

    public function delete_advice_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`walletAdvise`";

        $this->query($query);
    }
}
?>