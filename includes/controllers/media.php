<?php
class media extends common {
    public $user_id;
    public $type;
    public $class;
    public $file = array();
    public $complete = array();
    public $incomplete = array();

    public function create($rawData=false, $file=false) {
        global $usersCourier;
        global $usersCustomers;
        global $usersSiteAdmin;
        global $usersStoreAdmin;

        if ($this->class == "courier") {
            $apiClass = $usersCourier;
        } else if ($this->class == "customer") {
            $apiClass = $usersCustomers;
        } else if ($this->class == "site") {
            $apiClass = $usersSiteAdmin;
        } else if ($this->class == "partner") {
            $apiClass = $usersStoreAdmin;
        }

        if ($rawData === false) {
            $file = $this->file;
        }
        foreach($file as $raw) {
            if ($rawData === false) {
                $uploadFile = $this->uploadFile($raw);
            } else {
                $uploadFile = $this->getImageFrom64($raw);
            }
            if ($uploadFile['title'] == "OK") {
                $data['class'] = $this->class;
                $data['resource'] = $this->type;
                $data['media_type'] = $uploadFile['type'];
                $data['media_url'] = $uploadFile['desc'];
                $data['size'] = $uploadFile['size'];
                if ($this->type == "item") {
                    $data['expiryTime'] = time()+(60*60*24);
                } else {
                    $data['expiryTime'] = NULL;
                }
                
                $id = $this->insert("media", $data);

                if ($id) {
                    if ($this->type == "profile") {
                        $this->modifyOne("resource_id", $this->user_id, $id);
                    } else if ($this->type == "documents") {
                        $docData = explode(",", $apiClass->listOneValue($this->user_id, "document") );
                        $docData[] = $id;
                        $apiClass->editOne("document", implode(",", $docData), $this->user_id);
                        $apiClass->editOne("verified", 1, $this->user_id);
                    }
                }

                $this->complete[] = $this->formatResult( $this->listOne($id),  true, true, false);
                $this->incomplete = false;
            } else {
                $this->complete = false;
                $this->incomplete[] = $uploadFile;
            }
        }
    }

    public function remove($id, $ref="post_id") {
        $data = $this->getOne("media", $id, $ref);
        $dirArray['user_id'] = $data['user_id'];
        $dirArray['post_id'] = $data['post_id'];
        unset($data);
        $remove = $this->delete("media", $id, $ref);
        $remove = true;
        if ($remove){
            $this->deleteDir($this->cleanUrl()."/media/request/".$id."/");
            return true;
        } else {
            return false;
        }
    }

    public function removeOne($id, $api=false) {
        $data = $this->getOne("media", $id);
        $remove = $this->delete("media", $id, "ref");
        if ($api == true) {
            $i = "../";
        } else {
            $i = $this->cleanUrl();
        }
        if ($remove){
            @unlink($i."/".$data['media_url']);
            return true;
        } else {
            return false;
        }
    }

    private function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = @glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                @unlink($file);
            }
        }
        rmdir($dirPath);
    }

    private function cleanUrl() {
        $string = explode("/admin", getcwd());

        return $string[0];
    }

    public function getCover($id, $sort='post_id') {
        $data = $this->getOne("media", $id, $sort);
        if ($data['media_url'] != "") {
            return URL.$data['media_url'];
        } else {
            return URL."img/no_image.png"; 
        }
    }

    function mediaDefault() {
        return URL."img/no_image.png"; 
    }

    function reArrayFiles($file_post) {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
    
        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

    function getImageFrom64($image) {
        $fileName = rand(10, 99).time().rand(100, 999);

        if ( local === true) {
            $userDoc = $_SERVER['DOCUMENT_ROOT']."/InstaDoorApi/userFiles/".$this->class."/".$this->type;
        } else {
            $userDoc = $_SERVER['DOCUMENT_ROOT']."/userFiles/".$this->class."/".$this->type;
        }

        //$imageType = mime_content_type($image);
        // $extension = explode('/', $imageType)[1];
        $imageType = "image/jpg";
        $extension = "jpg";

        if ($this->type == "profile") {
            $fileName = $this->class."_".$this->type."_".$this->user_id.".".$extension;
        } else {
            $fileName = rand(10, 99).time().rand(100, 999).".".$extension;
        }
                
        if(!is_dir($userDoc)) {
            mkdir($userDoc, 0777, true);
        }

        if (file_put_contents($userDoc.'/'.$fileName, base64_decode($image))) {
            $msg['title'] = "OK";
            $msg['desc'] = $fileName;
            $msg['type'] = $imageType;
            $msg['size'] =(int) (strlen(rtrim($image, '=')) * 3 / 4);
        } else {
            $msg['success'] = false;
            $msg['error']['code'] = 10045;
            $msg['error']["message"] = "a file upload error occured";
        }

        return $msg;
    }
    
    function uploadFile($array) {
        global $options;
        ini_set("memory_limit", "200000000");
        $uploadedfile = $array['tmp_name'];
        $msg = array();
        
        $size = intval($options->get("max_file_size"))*(1024*1024);
        if ($array["error"] == 1) {
            $msg['success'] = false;
            $msg['error']['code'] = 10040;
            $msg['error']["message"] = "The uploaded file exceeds the mazimum upload file limit";
        } else if ($array["error"] == 2 ) {
            $msg['success'] = false;
            $msg['error']['code'] = 10040;
            $msg['error']["message"] = "The uploaded file exceeds the mazimum upload file limit";
        } else if ($array["error"] == 3) {
            $msg['success'] = false;
            $msg['error']['code'] = 10041;
            $msg['error']["message"] = "The uploaded file was only partially uploaded, please re-upload file";
        } else if ($array["error"] == 4) {
            $msg['success'] = false;
            $msg['error']['code'] = 10042;
            $msg['error']["message"] = "Missing file, please check the uploaded file and try again";
        } else if ($array["error"] == 6) {
            $msg['success'] = false;
            $msg['error']['code'] = 10043;
            $msg['error']["message"] = "Missing a temporary folder, contact the website administrator";
        } else if ($array["error"] == 7) {
            $msg['success'] = false;
            $msg['error']['code'] = 10044;
            $msg['error']["message"] = "Failed to write file to disk, contact the administrator";
        } else if ($array["error"] == 0) {
            $media_file = stripslashes($array['name']);
            $uploadedfile = $array['tmp_name']; 
            $extension = $this->getExtension($media_file);
            $extension = strtolower($extension);
            
            if ( local === true) {
                $userDoc = $_SERVER['DOCUMENT_ROOT']."/InstaDoorApi/userFiles/".$this->class."/".$this->type;
            } else {
                $userDoc = $_SERVER['DOCUMENT_ROOT']."/userFiles/".$this->class."/".$this->type;
            }
            
            if($array['size'] < $size) {
                if ($this->type == "profile") {
                    $file = $this->class."_".$this->type."_".$this->user_id.".".$extension;
                } else {
                    $file = rand(10, 99).time().rand(100, 999).".".$extension;
                }
                
                if(!is_dir($userDoc)) {
                    mkdir($userDoc, 0777, true);
                }
                
                $newFile = $userDoc."/".$file;
                $move = move_uploaded_file($uploadedfile, $newFile);
                
                if ($move) {
                    $msg['title'] = "OK";
                    $msg['desc'] = $file;
                    $msg['type'] = $array['type'];
                    $msg['size'] = $array['size'];
                } else {
                    $msg['success'] = false;
                    $msg['error']['code'] = 10045;
                    $msg['error']["message"] = "a file upload error occured";
                }
            } else {
                $msg['success'] = false;
                $msg['error']['code'] = 10046;
                $msg['error']["message"] = "the file exceed the maximum file size";
            }
        }
        return $msg;
    }

    public function findID($path, $id) {
        $brokenPath = explode("/", $path);
        $count = count($brokenPath)-1;

        $file = $brokenPath[$count];
        $mediaId = $this->listOneValue($file, "ref", "media_url");

        $this->modifyOne("resource_id", $id, $mediaId);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("media", $tag, $value, $id,$ref);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("media", $id, $tag);
    }

    public function listMultiple($ids) {
        if ($ids != "") {
            return $this->query("SELECT * FROM `media` WHERE `ref` IN (".trim($ids, ",").")", false, "list");
        }
    }

    public function listOneValue($id, $reference, $ref="ref") {
        return $this->getOneField("media", $id, $ref, $reference);
    }

    public function getMain($class, $resource, $id) {
        $data = $this->query("SELECT * FROM `media` WHERE `class` = '".$class."' AND `resource` = '".$resource."' AND `resource_id` = '".$id."'  ORDER BY `ref` ASC LIMIT 1", false, "getRow");
        return $this->formatResult($data, true);
    }

    public function getCommon($class, $resource, $id, $type="list") {
        $data = $this->query("SELECT * FROM `media` WHERE `class` = '".$class."' AND `resource` = '".$resource."' AND `resource_id` = '".$id."'  ORDER BY `ref` ASC", false, $type);

        if ($type == "list") {
            $single = false;
        } else {
            $single = true;
        }
        return $this->formatResult($data, $single);
    }

    public function getSingle($id) {
        $data = $this->query("SELECT * FROM `media` WHERE `ref` = '".$id."'  ORDER BY `ref` ASC", false, "getRow");

        return $this->formatResult($data, true);
    }

    public function formatResult($data, $single=false, $showRef=false, $isProfile=false) {
        if (($data) || ($isProfile)) {
            if ($single === false) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i] = $this->clean($data[$i], $showRef, $isProfile);
                }
            } else {
                $data = $this->clean($data, $showRef, $isProfile);
            }
        } else {
            if ($single === false) {
                $data[] = $this->clean(false, $single);
            } else {
                $data = $this->clean(false, $single);
            }
        } 
        return $data;
    }

    private function clean($data, $showRef=false, $isProfile=false) {
        global $users;
        if ( ( $isProfile != false ) && ($data == false) ) {
            $return['url'] = "https://ui-avatars.com/api/?name=".urlencode($users->listOneValue($isProfile, "fname"))."&width=100&bold=true";
            $return['mimeType'] = "image/png";
        } else if (!$data) {
            $return['url'] = URL."common/partner/files/item/noimage.png";
            $return['mimeType'] = "image/png";
        } else {
            $return = $data;
            if ($showRef === true) {
                $return['ref'] = intval($return['ref']);
            }  else {
                unset($return['ref']);
            }

            $return['url'] = (NULL !== $return['media_url']) ? $this->checkValid(URL."common/".$return['class']."/files/".$return['resource']."/".$return['media_url']) : URL."common/partner/files/item/noimage.png";
            $return['mimeType'] = $return['media_type'];
            $return['fileSize']['Byte'] = intval($return['size']);
            $return['fileSize']['KiloByte'] = round(($return['size']/(1024)), 2);
            $return['fileSize']['MegaByte'] = round(($return['size']/(1024*1024)), 2);
            if (strpos($return['mimeType'], 'image') !== false) {
                $return['isImage'] = true;
            } else {
                $return['isImage'] = false;
            }
            $return['expireBy'] = (NULL !== $return['expiryTime']) ? intval($return['expiryTime']) : NULL;
            unset($return['class']);
            unset($return['resource']);
            unset($return['resource_id']);
            unset($return['media_type']);
            unset($return['media_url']);
            unset($return['size']);
            unset($return['media_url']);
            unset($return['create_time']);
            unset($return['modify_time']);
            unset($return['expiryTime']);
        }

        return $return;
    }

    public function checkValid($file) {
        if ($file === null) {
            return URL."common/partner/files/item/noimage.png";
        }
        $status = get_headers($file, 1)[0];
        if ($status == 'HTTP/1.1 404 Not Found') {
            return URL."common/partner/files/item/noimage.png";
        }
        return $file;
    }

    public function getMultiData($list) {
        $return = [];
        $data = explode(",", $list);
        
        foreach($data as $row) {
            if (intval(trim($row)) > 0) {
                $return[] = $this->getSingle(intval(trim($row)));
            }
        }

        return $return;
    }

    public function getFileData($class, $resource, $resource_id, $data) {
        $resData = $this->getCommon($class, $resource, $resource_id, "getRow");
        
        if (!$resData) {
            if ($resource == "profile") {
                return "https://ui-avatars.com/api/?name=".urlencode($data["fname"])."&width=100&bold=true";
            } else {
                return URL."common/partner/files/item/noimage.png";
            }
        } else {
            return $resData['url'];
        }
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`media` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `class` VARCHAR(50) NOT NULL, 
            `resource` VARCHAR(50) NOT NULL, 
            `resource_id` INT NULL, 
            `media_type` VARCHAR(50) NOT NULL, 
            `media_url` VARCHAR(255) NOT NULL, 
            `size` DOUBLE NOT NULL, 
            `expiryTime` VARCHAR(50) NULL, 
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`,`media_url`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`media`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`media`";

        $this->query($query);
    }
}
?>