<?php
class usersSiteAdmin extends userSiteRoles {
    public $data = array();
    public $id;
    public $location = array();
    public $account_id;
    public $otp;
    public $email;
    public $apiLink;

    public function add() {
        $data = array(
            "fname" => "kayode",
            "lname" => "adebiyi",
            "email" => "olukayode.adebiyi@hotmail.co.uk",
            "password" => "kayode"
        );

        $password = $data['password'];
        $data['password'] = md5(sha1($data['password']));
        $data['status'] = "ACTIVE";
        
        $data['activation_token'] = $this->otp = rand(111111, 999999);

        $this->account_id = $data['email'];
        $roles = array_keys( $this->roles['Site_Admin_Roles'] );
        if ( $this->checkExixst("usersSiteAdmin", "email", $this->account_id) == 0) {
            $this->id = $this->insert("usersSiteAdmin", $data);
            if ($this->id) {
                foreach ( $roles as $role ) {
                    $this->role_id = $role;
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
                $mailUrl = URL."includes/views/emails/welcome.php?admin&".$fields;
                $messageToClient = $this->curl_file_get_contents($mailUrl);
                
                $mail['from'] = $contact;
                $mail['to'] = $client." <".$data['email'].">";
                $mail['subject'] = $subjectToClient;
                $mail['body'] = $messageToClient;
                
                global $alerts;
                $alerts->sendEmail($mail);
            }

            return true;
        } else {
            return false;
        }
    }

    public function addUser($data) {
        $data['email'] = trim($data['email']);
        $password = $this->createRandomPassword();
        $data['password'] = md5(sha1( $password ));

        $data['activation_token'] = $this->otp = rand(111111, 999999);
        $this->account_id = $data['email'];
        $roles = $data['role_id'];
        if ( $this->checkExixst("usersSiteAdmin", "email", $this->account_id) == 0) {

            $data['status'] = "CHANGE_PASSWORD";
            unset( $data['role_id'] );
            $this->id = $this->insert("usersSiteAdmin", $data);
            if ($this->id) {
                foreach ( $roles as $role ) {
                    $this->role_id = $role;
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
                    '&data='.urlencode($password);
                $mailUrl = URL."includes/views/emails/welcomeUserAdmin.php?admin&".$fields;
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
            $return['success'] = false;
            $return['error']['code'] = 10016;
            $return['error']['message'] = "Traces of this administrator exist already";

            return $return;
        }
    }

    public function passwordConfirm() {

        if ( $this->checkExixst("usersSiteAdmin", "email", $this->email) == 1) {
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
            
            $mailUrl = URL."includes/views/emails/passwordResetNotification.php?admin&".$fields;
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
        $check = $this->checkExixst("usersSiteAdmin", "email", $email);
        
        if ($check == 1) {
            $data = $this->listOne($email, "email");
            if ($pin == $data['activation_token']) {
                $this->update(
                    "usersSiteAdmin", array('password' => md5(sha1($password)),'status' => "ACTIVE"),
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
                $mailUrl = URL."includes/views/emails/passwordNotification.php?admin&".$fields;
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
        $id = $this->query("SELECT `ref` FROM `usersSiteAdmin` WHERE `email` = '".$this->email."'", false, "getCol");

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
            $mailUrl = URL."includes/views/emails/otp.php?admin&".$fields;
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
        $data = $this->query("SELECT `ref` FROM `usersSiteAdmin` WHERE `ref` = ".$this->id." AND `activation_token` = '".$this->otp."'", false, "count");

        if ($data == 1) {
            $this->editOne("status", "ACTIVE", $this->id);
            return true;
        } else {
            return false;
        }
    }

    public function login($array) {
        $email = $array['email'];
        $password = $array['password'];
        
        $row = $this->query("SELECT * FROM `usersSiteAdmin` WHERE `email` = :email AND `password` = :password AND `status` != 'DELETED'", array(':email' => $email,':password' => md5(sha1($password))), "getRow");
        
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

    public function passwordRequest() {
        $this->account_id = $this->email;
        return $this->listOne( $this->email, "email" )['ref'];
    }

    public function changePassword($array) {
        if ($this->checkExixst("usersSiteAdmin", "ref", $array['ref']) == 1) {

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
                $mailUrl = URL."includes/views/emails/notification.php?admin&".$fields;
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

                $tag .= "our password was changed successfully. <a href='".a_url."'>Sign in</a> to your Instadoor Account to learn more";

                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Password Modification Update";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&tag='.urlencode($tag);
                $mailUrl = URL."includes/views/emails/notification.php?admin&".$fields;
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

        return $this->replace("usersSiteAdmin", $data, $replace);
    }

    public function editadmin($data) {
        if ($data['ref'] ==$this->user_id) {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10026;
            $this->return['message'] = "You can not edit yourself in this view, use the edit profile function instead";
            return $this->return;
        }
        $roles = $data['role_id'];
        unset($data['email']);
        unset($data['role_id']);

        $replace = array_keys( $data );

        if ( $this->replace("usersSiteAdmin", $data, $replace) ) {
            $this->removeRole($data['ref'], "user_id");
            foreach ( $roles as $role ) {
                $this->role_id = $role;
                $this->user_id = $data['ref'];

                $this->addRole();
            }

            $this->return['success'] = true;
            $this->return['results'] = "OK";
            return $this->return;
        }

        $this->return['success'] = false;
        $this->return['error']['code'] = 10010;
        $this->return['error']["message"] = "Service Unavailable";
        return $this->return;
    }

    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("usersSiteAdmin", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function reset() {
        if ($this->id ==$this->user_id) {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10027;
            $this->return['message'] = "You can not reset yourself in this view, use the edit password function instead";
            return $this->return;
        }

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
            $mailUrl = URL."includes/views/emails/userPasswordReset.php?admin&".$fields;
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

    public function deactivate() {
        if ($this->id ==$this->user_id) {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10022;
            $this->return['message'] = "You can not deactivate yourself";
            return $this->return;
        }

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

    public function remove() {
        if ($this->id ==$this->user_id) {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10017;
            $this->return['message'] = "You can not delete yourself";
            return $this->return;
        }

        if ($this->modifyOne("status", "DELETED", $this->id)) {
            $this->removeRole($this->id, "user_id");
            $this->return['success'] = true;
            $this->return['results'] = "OK";
            return $this->return;
        } else {
            $this->return['success'] = false;
            $this->return['error']['code'] = 10020;
            $this->return['message'] = "An error occured while deleting this user";
            return $this->return;
        }
    }

    public function getDetails($id, $tag="ref") {
        return $this->getOne("usersSiteAdmin", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("usersSiteAdmin", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("usersSiteAdmin", $start, $limit, $order, $dir, "`status` != 'DELETED'", $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("usersSiteAdmin", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("usersSiteAdmin", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("usersSiteAdmin", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
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
        global $tickets;
        $data = $this->listOne( $id );
        if ( $data ) {
            $return['userId'] = intval( $data['ref'] );
            $return['firstName'] = $data['fname'];
            $return['lastName'] = $data['lname'];
            $tickets->admin = true;
            $tickets->user_id = $data['ref'];
            $return['tickets'] = $tickets->getCounts("admin_id");
            return $return;
        } else {
            return NULL;
        }
    }

    private function clean($data) {
        global $tickets;
        $this->user_id = $data['ref'];
        $return['ref'] = intval( $data['ref'] );
        $return['firstName'] = $data['fname'];
        $return['lastName'] = $data['lname'];
        $return['emailAddress'] = $data['email'];
        $return['url']['profilePicture'] = "https://ui-avatars.com/api/?name=".urlencode($data['fname']. " ".$data['lname'])."&width=100&bold=true";
        $return['verified'] = (1 == $return['verified']) ? true : false;
        $tickets->admin = true;
        $tickets->user_id = $data['ref'];
        $return['tickets'] = $tickets->getCounts("admin_id");
        $account['activeAccount'] = ("ACTIVE" == $data['status']) ? true : false;
        $account['newAccount'] = ("NEW" == $data['status']) ? true : false;
        $account['passwordChange'] = ("CHANGE_PASSWORD" == $data['status']) ? true : false;
        $account['inactiveAccount'] = ("INAVTIVE" == $data['status']) ? true : false;
        $return['accountStatus'] = $account;
        $return['roles'] = $this->getRoles();
        $return['creationDate'] = strtotime( $data['create_time'] );
        $return['lastModified'] = strtotime( $data['modify_time'] );

        return $return;
    }

    function getUser() {
        $data = $this->listOne($this->id);
        return $this->formatResult($data, true);
    }

    private function listAmin($start, $limit)  {
        $return['data'] = $this->getList($start, $limit, "lname");
        $return['counts'] = $this->getList(false, false, "lname", "ASC", "count");

        return $return;
    }

    public function retrieveAdmin($view, $page=1) {
        global $options;

        if (intval($page) == 0) {
            $page = 1;
        }

        if ($view == "one") {
            $this->result = $this->listOne($this->id);
            $this->return['success'] = true;
            $this->return['data'] = $this->formatResult( $this->result, true );
        } else {
            $current = (intval($page) > 0) ? (intval($page)-1) : 0;
            $limit = intval($options->get("resultPerPage"));
            $start = $current*$limit;
            $this->result = $this->listAmin($start, $limit);
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
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`usersSiteAdmin` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `lname` VARCHAR(50) NOT NULL, 
            `fname` VARCHAR(50) NOT NULL, 
            `email` VARCHAR(255) NOT NULL, 
            `password` VARCHAR(1000) NOT NULL,
            `document` VARCHAR(255) NULL,
            `activation_token` VARCHAR(10) NULL,
            `token` VARCHAR(50) NULL,
            `token_refresh` VARCHAR(20) NULL,
            `status` varchar(20) NOT NULL DEFAULT 'NEW',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;";

        $this->query($query);
        $this->add();
    }

    public function clear_table() {
        //clear database
        $query = "TRUNCATE `".dbname."`.`usersSiteAdmin`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`usersSiteAdmin`";

        $this->query($query);
    }
}
?>