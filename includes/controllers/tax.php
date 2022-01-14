<?php
	class tax extends common {
        public $data = array();
        public $id;
        public $location = array();
        public $result = array();
        public $return = array();

		function add($array) {
            $replace = array();

            $replace[] = "name";
            $replace[] = "federal";
            $replace[] = "state";
			return $this->replace("tax", $array, $replace);
        }
        
        public function getDetails($id, $tag="ref") {
            return $this->getOne("tax", $id, $tag);
        }
    
        public function modifyOne($tag, $value, $id, $ref="ref") {
            return $this->updateOne("tax", $tag, $value, $id,$ref);
        }
    
        public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
            return $this->lists("tax", $start, $limit, $order, $dir, false, $type);
        }
    
        public function listOne($id, $tag="ref") {
            return $this->getOne("tax", $id, $tag);
        }
    
        public function listOneValue($id, $reference) {
            return $this->getOneField("tax", $id, "ref", $reference);
        }
    
        public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
            return $this->sortAll("tax", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
        }
        
        public function apiTax() {
            $query['key'] = location_api;
            $query['lat'] = $this->location['latitude'];
            $query['lon'] = $this->location['longitude'];
            $query['zoom'] = 18;
            $query['format'] = "json";
            

            $url = "https://us1.locationiq.com/v1/reverse.php?".http_build_query($query);

            $headers[] = "Content-Type: application/json";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            $output = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($output, true);
            

            $country = $data['address']['country'];
            $state = $data['address']['state'];

            $this->return = $data['address'];
            $this->return['address'] = $data['display_name'];
            $this->return['tax'] = $this->query("SELECT `province`, `name`, `federal`, `state` FROM `tax` WHERE `country` LIKE '%".$country."%' AND `province` LIKE '%".$state."%'", false, "getRow");

            return $this->return;
        }

        public function initialize_table() {
            //create database
            $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`tax` (
                `ref` INT NOT NULL AUTO_INCREMENT,
                `country` VARCHAR(255) NOT NULL, 
                `province` VARCHAR(255) NOT NULL, 
                `name` VARCHAR(255) NOT NULL, 
                `federal` DOUBLE NOT NULL, 
                `state` DOUBLE NOT NULL, 
                `discount` INT NULL, 
                `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ref`)
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

            $this->query($query);
            $this->query("INSERT IGNORE INTO `tax` (`ref`, `country`, `province`, `name`, `federal`, `state`, `create_time`, `modify_time`) VALUES
            (1, 'Canada', 'Ontario', 'HST', 5, 8, '2020-05-23 01:38:33', '2020-05-23 01:38:33'),
            (2, 'Canada', 'Nova Scotia', 'HST', 5, 10, '2020-05-23 01:38:33', '2020-05-23 01:48:16'),
            (3, 'Canada', 'Alberta', 'GST', 5, 0, '2020-05-23 01:49:34', '2020-05-23 01:49:34'),
            (4, 'Canada', 'British Columbia', 'GST+PST', 5, 7, '2020-05-23 01:49:34', '2020-05-23 01:49:34'),
            (5, 'Canada', 'Manitoba', 'GST+PST', 5, 7, '2020-05-23 01:50:38', '2020-05-23 01:50:38'),
            (6, 'Canada', 'New-Brunswick', 'HST', 5, 10, '2020-05-23 01:50:38', '2020-05-23 01:50:38'),
            (7, 'Canada', 'Saskatchewan', 'GST + PST', 5, 6, '2020-05-23 01:51:42', '2020-05-23 01:55:19'),
            (8, 'Canada', 'Newfoundland and Labrador', 'HST', 5, 10, '2020-05-23 01:51:42', '2020-05-23 01:51:42'),
            (9, 'Canada', 'Northwest Territories', 'GST', 5, 0, '2020-05-23 01:53:07', '2020-05-23 01:53:07'),
            (10, 'Canada', 'Nunavut', 'GST', 5, 0, '2020-05-23 01:53:07', '2020-05-23 01:53:07'),
            (11, 'Canada', 'Prince Edward Island', 'HST', 5, 10, '2020-05-23 01:53:51', '2020-05-23 01:53:51'),
            (12, 'Canada', 'Quebec', 'GST + QST', 5, 9.975, '2020-05-23 01:56:32', '2020-05-23 01:56:32'),
            (13, 'Canada', 'Yukon', 'GST', 5, 0, '2020-05-23 01:56:32', '2020-05-23 01:56:32');");
        }

        public function clear_table() {
            //clear database
            $query = "TRUNCATE `".dbname."`.`options`";

            $this->query($query);
        }

        public function delete_table() {
            //clear database
            $query = "DROP TABLE `".dbname."`.`options`";

            $this->query($query);
        }
	}
?>