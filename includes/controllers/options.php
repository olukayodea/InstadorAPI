<?php
	class options extends database {
		function add($name, $value) {
            $data = array();
            $replace = array();
            $data['name'] = $name;
            $data['value'] = $value;

            $replace[] = "value";
			return $this->replace("options", $data, $replace);
		}
		
		function remove($name) {
            return $this->delete("options", $name, "name");
		}
		
		function get($name) {
            return $this->getOneField("options", $name, "name", "value");
        }
        
        public function apiOptions() {
            $data = $this->query("SELECT * FROM `options` WHERE `name` != 'minStoreRemote'", false, "list");

            foreach($data as $row) {
                if ($row['options'] == "array") {
                    $array = unserialize($row['value']);
                    $value = NULL;
                } else {
                    $array = [];
                    $value = trim($row['value']);
                }
                $options = NULL;

                if (($row['options'] != "array") && ($row['options'] !== NULL)) {
                    $options = explode(",", $row['options']);
                }
                $this->return[] = array('label' => $row['label'], 'name' => $row['name'], 'value'  =>  $value, 'list'  =>  $array, "type"=> $row['type'], "validate"=> $row['validate'], 'options'=> $options);
            }

            return $this->return;
        }

        public function initialize_table() {
            //create database
            $distnace[] = array("min"=>0, "max"=>0.9, "val"=> 1);
            $distnace[] = array("min"=>1, "max"=>1.9, "val"=> 2);
            $distnace[] = array("min"=>2, "max"=>2.9, "val"=> 3);
            $distnace[] = array("min"=>3, "max"=>3.9, "val"=> 4);
            $distnace[] = array("min"=>4, "max"=>5.9, "val"=> 4.5);
            $distnace[] = array("min"=>6, "max"=>7.9, "val"=> 5.25);
            $distnace[] = array("min"=>8, "max"=>9.9, "val"=> 5.75);
            $distnace[] = array("min"=>10, "max"=>14.9, "val"=> 7);
            $distnace[] = array("min"=>15, "max"=>19.9, "val"=> 9);
            $distnace[] = array("min"=>20, "max"=>24.9, "val"=> 12);
            $distnace[] = array("min"=>25, "max"=>29.9, "val"=> 14);
            $distnace[] = array("min"=>30, "max"=>34.9, "val"=> 15.5);
            $distnace[] = array("min"=>35, "max"=>39.9, "val"=> 17.5);
            $distance_charge = serialize($distnace);

            $weight[] = array("min"=>20, "max"=>30, "val"=> 0.75);
            $weight[] = array("min"=>31, "max"=>40, "val"=> 1.50);
            $weight[] = array("min"=>41, "max"=>50, "val"=> 2);
            $weight[] = array("min"=>51, "max"=>60, "val"=> 2.50);
            $weight[] = array("min"=>61, "max"=>70, "val"=> 3);
            $weight[] = array("min"=>71, "max"=>80, "val"=> 3.5);
            $weight[] = array("min"=>61, "max"=>90, "val"=> 4);
            $weight[] = array("min"=>71, "max"=>100, "val"=> 4.5);
            $weight_charge = serialize($weight);

            $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`options` (
                `ref` INT NOT NULL AUTO_INCREMENT,
                `label` VARCHAR(255) NOT NULL, 
                `name` VARCHAR(255) NOT NULL, 
                `type` VARCHAR(255) NOT NULL, 
                `validate` VARCHAR(255) NOT NULL, 
                `value` TEXT NULL, 
                `options` VARCHAR(255) NULL, 
                `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ref`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8;

            INSERT IGNORE INTO `options` (`ref`, `label`, `name`, `type`, `validate`, `value`, `options`, `create_time`, `modify_time`) VALUES
            (1, 'Results Per Page', 'resultPerPage', 'textbox', 'number', '10', NULL, '2020-04-25 01:25:58', '2020-11-29 06:56:15'),
            (2, 'Max File Size', 'max_file_size', 'textbox', 'number', '2', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:16'),
            (3, 'Maximum Request', 'max_request', 'textbox', 'number', '10', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:12'),
            (4, 'Delivery Refresh Time', 'delivery_hold', 'textbox', 'number', '2', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:14'),
            (5, 'Service Charge', 'service_charge', 'textbox', 'number', '1', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:11'),
            (6, 'Distance Charge (km)', 'distance_charge', 'multiple-rows', 'number', '".$distance_charge."','array', '2020-06-07 15:12:43', '2020-11-29 06:56:08'),
            (7, 'Weight Charges (kg)', 'weight_charge', 'multiple-rows', 'number', '".$weight_charge."', 'array', '2020-06-07 15:12:43', '2020-11-29 06:56:08'),
            (8, 'Maximum Distance', 'max_distance', 'textbox', 'number', '39.9', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:05'),
            (9, 'Maximum Weight', 'max_weight', 'textbox', 'number', '100', NULL, '2020-06-07 15:12:43', '2020-11-29 06:56:04'),
            (10, 'Unit of Mesurement', 'units', 'dropdown', 'NULL', 'metric', 'imperial,metric', '2020-06-07 15:12:43', '2020-11-29 06:56:02'),
            (11, 'Default Store Commission', 'default_commission', 'textbox', 'number', '10', NULL, '2020-06-07 15:12:43', '2020-11-29 06:55:57'),
            (12, 'Default Courier Commission', 'default_commission_courier', 'textbox', 'number', '10', NULL, '2020-06-07 15:12:43', '2020-11-29 06:55:54'),
            (13, 'Time per Distance', 'time_vale', 'textbox', 'number', '0.6', NULL, '2020-06-07 15:12:43', '2020-11-29 06:55:57');";

            $this->query($query);
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