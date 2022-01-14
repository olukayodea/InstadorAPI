<?php
class usersStoreAdmin extends userStoreRoles {
    public $data = array();
    public $id;
    public $location = array();
    public $account_id;
    public $otp;
    public $email;
    public $apiLink;
    
    public $admin = false;
    
    public function add($data) {
        $data['email'] = trim($data['email']);
        $password = $data['password'];
        $data['password'] = md5(sha1($data['password']));

        $data['verified'] = 0;
        $data['status'] = "NEW";
        if (isset($data['ssoToken'])) {
            $data['ssoToken'] = md5(sha1($data['ssoToken']));
            $data['status'] = "ACTIVE";
        }
        
        $data['activation_token'] = $this->otp = rand(111111, 999999);
        $this->account_id = $data['email'];
        $roles = array_keys( $this->roles['Store_Roles'] );
        if ( $this->checkExixst("usersStoreAdmin", "email", $this->account_id) == 0) {
            $this->id = $this->insert("usersStoreAdmin", $data);
            if ($this->id) {
                foreach ( $roles as $role ) {
                    $this->role_id = $role;
                    $this->store_id = 0;
                    $this->user_id = $this->id;

                    $this->addRole();
                }
                
                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Welcome to Instadoor";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&otp='.urlencode($this->otp).
                    '&data='.urlencode($password);
                $mailUrl = URL."includes/views/emails/welcome.php?store&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);
                
                return array( "ref" => $this->id, "pin" => $this->otp );
            }
        } else {
            return false;
        }
    }

    public function addUser($data) {
        global $storeList;
        $data['email'] = trim($data['email']);
        $password = $this->createRandomPassword();
        $data['password'] = md5(sha1( $password ));

        $data['activation_token'] = $this->otp = rand(111111, 999999);
        $this->account_id = $data['email'];
        $roles = $data['role_id'];
        if ( $this->checkExixst("usersStoreAdmin", "email", $this->account_id) == 0) {

            $store_id = $data['store_id'];
            $data['verified'] = 2;
            $data['status'] = "CHANGE_PASSWORD";
            unset( $data['store_id'] );
            unset( $data['role_id'] );
            $this->id = $this->insert("usersStoreAdmin", $data);
            if ($this->id) {
                foreach ( $roles as $role ) {
                    $this->role_id = $role;
                    $this->store_id = $store_id;
                    $this->user_id = $this->id;

                    $this->addRole();
                }

                $storeList->user_id = $this->id;
                $storeList->store_id = $store_id;
                $storeList->add();

                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Welcome to Instadoor";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&role_id='.urlencode($data['role_id']).
                    '&store_id='.urlencode($store_id).
                    '&data='.urlencode($password);
                $mailUrl = URL."includes/views/emails/welcomeUser.php?store&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);

                return $this->id;
            }
        }
    }

    public function passwordConfirm() {
        if ( $this->checkExixst("usersStoreAdmin", "email", $this->email) == 1) {
            $data = $this->listOne($this->email, "email"); 

            $this->id = $data['ref'];
            $this->otp = rand(111111, 999999);
            $this->modifyOne("activation_token", $this->otp, $this->id);

            $client = $data['lname']." ".$data['fname'];
            $subjectToClient = "Password Modification Request";
            $contact = "Instadoor <".replyMail.">";
            
            $fields = 'subject='.urlencode($subjectToClient).
                '&lname='.urlencode($data['lname']).
                '&fname='.urlencode($data['fname']).
                '&email='.urlencode($data['email']).
                '&otp='.urlencode($this->otp);
            
            $mailUrl = URL."includes/views/emails/passwordResetNotification.php?store&".$fields;
            $messageToClient = $this->curl_file_get_contents($mailUrl);
            
            $mail['from'] = $contact;
            $mail['to'] = $client." <".$data['email'].">";
            $mail['subject'] = $subjectToClient;
            $mail['body'] = $messageToClient;
            
            global $alerts;
            $alerts->sendEmail($mail);
            
            return $this->id;
        } else {
            return false;
        }
    }

    function passwordReset($email, $password, $pin) {
        $check = $this->checkExixst("usersStoreAdmin", "email", $email);
        
        if ($check == 1) {
            $data = $this->listOne($email, "email");
            if ($pin == $data['activation_token']) {
                $this->update(
                    "usersStoreAdmin", array('password' => md5(sha1($password)),'status' => "ACTIVE"),
                    array("ref" => $data['ref'])
                );
                
                $client = $data['name'];
                $subjectToClient = "Password Reset Notification";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&password='.urlencode($password);
                $mailUrl = URL."includes/views/emails/passwordNotification.php?store&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                $alerts = new alerts;
                $alerts->sendEmail($mail);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function logout() {
        $this->editOne("token", NULL, $this->id);
        $this->editOne("token_refresh", NULL, $this->id);
    }

    public function resendOtp () {
        $id = $this->query("SELECT `ref` FROM `usersStoreAdmin` WHERE `email` = '".$this->email."'", false, "getCol");

        if ($id) {
            $this->id = $id;
            $this->otp = rand(111111, 999999);
            $this->editOne("activation_token", $this->otp, $this->id);

            $data = $this->listOne($this->id);
            $tag = "You recently requested a new activation pin.  Please find below your activation pin<br><br><strong><span style='font-size: 20px'>".$this->otp."</span></strong><br><br>If you did not make this request, please ignore this message";

            $client = $data['lname']." ".$data['fname'];
            $subjectToClient = "New OTP Request";
            $contact = "Instadoor <".replyMail.">";
            
            $fields = 'subject='.urlencode($subjectToClient).
                '&lname='.urlencode($data['lname']).
                '&fname='.urlencode($data['fname']).
                '&email='.urlencode($data['email']).
                '&otp='.urlencode($this->otp);
            $mailUrl = URL."includes/views/emails/otp.php?store&".$fields;
            $messageToClient = $this->curl_file_get_contents($mailUrl);
            
            $mail['from'] = $contact;
            $mail['to'] = $client." <".$data['email'].">";
            $mail['subject'] = $subjectToClient;
            $mail['body'] = $messageToClient;
            
            global $alerts;
            $alerts->sendEmail($mail);

            return true;
        } else {
            return false;
        }
    }

    public function activateAccount() {
        $data = $this->query("SELECT `ref` FROM `usersStoreAdmin` WHERE `ref` = ".$this->id." AND `activation_token` = '".$this->otp."'", false, "count");

        if ($data == 1) {
            $this->editOne("status", "ACTIVE", $this->id);
            return true;
        } else {
            return false;
        }
    }
    
    public function sso($array) {
        $channel = $array['channel'];
        $ssoToken = $array['ssoToken'];
        
        $row = $this->query("SELECT * FROM `usersStoreAdmin` WHERE `ssoToken` = :ssoToken AND `ssoToken` = :ssoToken AND `status` != 'DELETED'", array(':channel' => $channel,':ssoToken' => md5(sha1($ssoToken))), "getRow");

        
        if (intval($row['ref']) > 0) {
            $status = $row['status'];
            $this->id = $row['ref'];

            if ($status == "NEW") {
                return 1;
            } else if ($status == "INACTIVE") {
                return 3;
            } else {
                return $row;
            }
        } else {
            return 0;
        }
    }

    public function login($array) {
        $email = $array['email'];
        $password = $array['password'];
        
        $row = $this->query("SELECT * FROM `usersStoreAdmin` WHERE `email` = :email AND `password` = :password AND `status` != 'DELETED'", array(':email' => $email,':password' => md5(sha1($password))), "getRow");

        
        if (intval($row['ref']) > 0) {
            $status = $row['status'];
            $this->id = $row['ref'];

            if ($status == "NEW") {
                return 1;
            } else if ($status == "INACTIVE") {
                return 3;
            } else if ($status == "CHANGE_PASSWORD") {
                return 4;
            } else {
                return $row;
            }
        } else {
            return 0;
        }
    }

    public function passwordRequest() {
        $this->account_id = $this->email;
        return $this->listOne( $this->email, "email" )['ref'];
    }

    public function changePassword($array) {
        if ($this->checkExixst("usersStoreAdmin", "ref", $array['ref']) == 1) {

            $data = $this->listOne($array['ref'], "ref");

            if ($this->modifyOne("password", md5(sha1($array['password'])), $data['ref'], "ref")) {
                $tag = "Your password was changed successfully. <a href='".URL."'>Sign in</a> to your Instadoor Account to learn more";

                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Password Modification Update";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&tag='.urlencode($tag);
                $mailUrl = URL."includes/views/emails/notification.php?store&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);
                return true;
            } else {
                return false;
            }
   
        } else {
            return 1;
        }
    }

    public function reset() {
        $password = $this->createRandomPassword();
        $array['password'] = md5(sha1( $password ));

        $array['activation_token'] = $this->otp = rand(111111, 999999);

        if ($this->modifyOne("password", $array['password'], $this->id)) {
            $this->modifyOne("status", "CHANGE_PASSWORD", $this->id);
            $this->modifyOne("activation_token", $array['activation_token'], $this->id);

            $token = $this->user_id.$this->createRandomPassword(15);

            $this->modifyOne("token", $token, $this->id, "ref");
            $this->modifyOne("token_refresh", time()+(60*60*24*180), $this->id, "ref");

            $data = $this->listOne($this->id);

            $client = $data['lname']." ".$data['fname'];
            $subjectToClient = "New Password Notification";
            $contact = "Instadoor <".replyMail.">";
            
            $fields = 'subject='.urlencode($subjectToClient).
                '&lname='.urlencode($data['lname']).
                '&fname='.urlencode($data['fname']).
                '&email='.urlencode($data['email']).
                '&data='.urlencode($password);
            $mailUrl = URL."includes/views/emails/userPasswordReset.php?store&".$fields;
            $messageToClient = $this->curl_file_get_contents($mailUrl);
            
            $mail['from'] = $contact;
            $mail['to'] = $client." <".$data['email'].">";
            $mail['subject'] = $subjectToClient;
            $mail['body'] = $messageToClient;
            
            global $alerts;
            $alerts->sendEmail($mail);
            
            $this->return['success'] = true;
            $this->return['results'] = "OK";
            return $this->return;
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10021;
            $this->return['message'] = "An error occured while reseting this user password";
            return $this->return;
        }
    }

    public function editPassword($array) {
        $data = $this->listOne($this->id, "ref");

        if (($data['password'] == md5(sha1($array['old_password']))) || ($data['status'] == "CHANGE_PASSWORD")) {
            if ($this->editOne("password", md5(sha1($array['new_password'])), $this->id)) {
                if ($data['status'] == "CHANGE_PASSWORD") {
                    $this->modifyOne("status", "ACTIVE", $data['ref'], "ref");
                    $tag = "Your account was activated and y";
                } else {
                    $tag = "Y";
                }

                $tag .= "our password was changed successfully. <a href='".p_url."'>Sign in</a> to your Instadoor Account to learn more";

                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Password Modification Update";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&tag='.urlencode($tag);
                $mailUrl = URL."includes/views/emails/notification.php?store&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);

                return true;
            }
        } else {
            return false;
        }
    }

    public function edit($data) {
        $data['ref'] = $this->id;

        unset($data['email']);
        unset($data['password']);

        $replace = array_keys( $data );

        return $this->replace("usersStoreAdmin", $data, $replace);
    }

    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("usersStoreAdmin", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function remove($id) {}

    public function getDetails($id, $tag="ref") {
        return $this->getOne("usersStoreAdmin", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("usersStoreAdmin", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("usersStoreAdmin", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("usersStoreAdmin", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("usersStoreAdmin", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("usersStoreAdmin", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function adminPendingStoreList($start, $limit) {
        $return['data'] = $this->getSortedList(1, "verified", false, false, false, false, "ref", "ASC",'AND', $start, $limit);
        $return['counts'] = $this->getSortedList(1, "verified", false, false, false, false, "ref", "ASC",'AND', false, false, "count");

        return $return;
    }

    public function deactivate() {
        if ($this->modifyOne("status", "INAVTIVE", $this->id)) {
            $this->return['success'] = true;
            $this->return['results'] = "OK";
            return $this->return;
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10025;
            $this->return['message'] = "An error occured while deactivating this user";
            return $this->return;
        }
    }

    public function verifyCourier($data) {
        global $alerts;
        $storeData = $this->listOne($this->id);

        if ($storeData) {
            if ($data['response'] == "yes") {
                $verified = 2;
                $message = "Thank you for choosing nstaDoor, Your courier account has been verified and approved, you can now login and start managing and recieving recieving";
            } else {
                $verified = 3;
                $message = "Thank you for choosing nstaDoor, unfortunately Your courier account has not been verified and approved at this time, you will not be  able to manage this account at this time, you can contact the administrator to learn more";
            }

            if ( $this->modifyOne("verified", $verified, $this->id) ) {
                $userData = $this->listOne($this->id);
    
                $client = $userData['fname']." ".$userData['lname'];
                $subjectToClient = "RE: Account Verification Notification";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($userData['lname']).
                    '&fname='.urlencode($userData['fname']).
                    '&email='.urlencode($userData['email']).
                    '&tag='.urlencode($message);
                $mailUrl = URL."includes/views/emails/notification.php?store&".$fields;
                
                $messageToClient = $this->curl_file_get_contents($mailUrl);
    
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$userData['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                $alerts->sendEmail($mail);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
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

    public function userData( $id ) {
        $data = $this->listOne( $id );
        if ( $data ) {
            $return['userId'] = intval( $data['ref'] );
            $return['firstName'] = $data['fname'];
            $return['lastName'] = $data['lname'];
            if (0 == $data['verified']) {
                $return['verified'] = "new";
            } else if (1 == $data['verified']) {
                $return['verified'] = "pending";
            } else if (2 == $data['verified']) {
                $return['verified'] = "approved";
            } else if (3 == $data['verified']) {
                $return['verified'] = "rejected";
            } else {
                $return['verified'] = "none";
            }

            return $return;
        } else {
            return NULL;
        }
    }

    private function clean($data) {
        global $media;
        global $bankAccount;
        if ($this->admin) {
            $data = $this->listOne($data['user_id']);
            $this->user_id = $data['user_id'];
        }
        global $store;
        $this->user_id = $data['ref'];
        $store->user_id = $data['ref'];
        $return['ref'] = intval( $data['ref'] );
        $return['firstName'] = $data['fname'];
        $return['lastName'] = $data['lname'];
        $return['emailAddress'] = $data['email'];
        $return['url']['profilePicture'] = "https://ui-avatars.com/api/?name=".urlencode($data['fname']. " ".$data['lname'])."&width=100&bold=true";
        $return['url']['profile'] = "";
        $return['document'] = $media->getMultiData( $data['document']);
        $return['bankAccount'] = $bankAccount->formatResult($bankAccount->listOne($data['ref'], "store"), true);
        if (0 == $data['verified']) {
            $return['verified'] = "new";
        } else if (1 == $data['verified']) {
            $return['verified'] = "pending";
        } else if (2 == $data['verified']) {
            $return['verified'] = "approved";
        } else if (3 == $data['verified']) {
            $return['verified'] = "rejected";
        } else {
            $return['verified'] = "none";
        }
        $account['activeAccount'] = ("ACTIVE" == $data['status']) ? true : false;
        $account['newAccount'] = ("NEW" == $data['status']) ? true : false;
        $account['inactiveAccount'] = ("INAVTIVE" == $data['status']) ? true : false;
        $account['passwordChange'] = ("CHANGE_PASSWORD" == $data['status']) ? true : false;
        $return['stores'] = $store->formatResult( $store->listStores()["data"], false, false );
        $return['roles'] = $this->getRoles();
        $return['creationDate'] = strtotime( $data['create_time'] );
        $return['lastModified'] = strtotime( $data['modify_time'] );
        $return['accountStatus'] = $account;
        return $return;
    }

    function getUser() {
        $data = $this->listOne($this->id);
        return $this->formatResult($data, true);
    }
    
    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`usersStoreAdmin` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `lname` VARCHAR(50) NOT NULL, 
            `fname` VARCHAR(50) NOT NULL, 
            `email` VARCHAR(255) NOT NULL, 
            `password` VARCHAR(1000) NOT NULL,
            `verified` INT NULL, 
            `document` VARCHAR(255) NULL,
            `activation_token` VARCHAR(10) NULL,
            `firebase_token` VARCHAR(255) NULL,
            `token` VARCHAR(50) NULL,
            `token_refresh` VARCHAR(20) NULL,
            `status` varchar(20) NOT NULL DEFAULT 'NEW',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`usersStoreAdmin`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`usersStoreAdmin`";

        $this->query($query);
    }
}
?>