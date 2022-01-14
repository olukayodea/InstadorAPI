<?php
class usersCustomers extends common {
    public $data = array();
    public $id;
    public $location = array();
    public $account_id;
    public $otp;
    public $email;
    public $apiLink;
    public $admin;
    
    public function add($data) {
        $data['email'] = trim($data['email']);
        $password = $data['password'];
        $data['password'] = md5(sha1($data['password']));

        $data['status'] = "NEW";
        if (isset($data['ssoToken'])) {
            $data['ssoToken'] = md5(sha1($data['ssoToken']));
            $data['status'] = "ACTIVE";
        }
        
        $data['activation_token'] = $this->otp = rand(111111, 999999);

        $this->account_id = $data['email'];
        if ( $this->checkExixst("usersCustomers", "email", $this->account_id) == 0) {
            $this->id = $this->insert("usersCustomers", $data);
            if ($this->id) {
                $client = $data['lname']." ".$data['fname'];
                $subjectToClient = "Welcome to Instadoor";
                $contact = "Instadoor <".replyMail.">";
                
                $fields = 'subject='.urlencode($subjectToClient).
                    '&lname='.urlencode($data['lname']).
                    '&fname='.urlencode($data['fname']).
                    '&email='.urlencode($data['email']).
                    '&otp='.urlencode($this->otp).
                    '&data='.urlencode($password);
                $mailUrl = URL."includes/views/emails/welcome.php?".$fields;
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

    public function passwordConfirm() {

        if ( $this->checkExixst("usersCustomers", "email", $this->email) == 1) {
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
            
            $mailUrl = URL."includes/views/emails/passwordResetNotification.php?".$fields;
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
        $check = $this->checkExixst("usersCustomers", "email", $email);
        
        if ($check == 1) {
            $data = $this->listOne($email, "email");
            if ($pin == $data['activation_token']) {
                $this->update(
                    "usersCustomers", array('password' => md5(sha1($password)),'status' => "ACTIVE"),
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
                $mailUrl = URL."includes/views/emails/passwordNotification.php?".$fields;
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
        $id = $this->query("SELECT `ref` FROM `usersCustomers` WHERE `email` = '".$this->email."'", false, "getCol");

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
            $mailUrl = URL."includes/views/emails/otp.php?".$fields;
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
        $data = $this->query("SELECT `ref` FROM `usersCustomers` WHERE `ref` = ".$this->id." AND `activation_token` = '".$this->otp."'", false, "count");

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
        
        $row = $this->query("SELECT * FROM `usersCustomers` WHERE `ssoToken` = :ssoToken AND `ssoToken` = :ssoToken AND `status` != 'DELETED'", array(':channel' => $channel,':ssoToken' => md5(sha1($ssoToken))), "getRow");

        
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
        
        $row = $this->query("SELECT * FROM `usersCustomers` WHERE `email` = :email AND `password` = :password AND `status` != 'DELETED'", array(':email' => $email,':password' => md5(sha1($password))), "getRow");

        
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
        if ($this->checkExixst("usersCustomers", "ref", $array['ref']) == 1) {

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
                $mailUrl = URL."includes/views/emails/notification.php?".$fields;
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
        $oldPassword = $this->listOneValue($this->id, "password");
        if ($oldPassword == md5(sha1($array['old_password']))) {
            return $this->editOne("password", md5(sha1($array['new_password'])), $this->id);
        } else {
            return false;
        }
    }

    public function edit($data) {
        $data['ref'] = $this->id;

        unset($data['email']);
        unset($data['password']);

        $replace = array_keys( $data );

        return $this->replace("usersCustomers", $data, $replace);
    }

    public function editOne($key, $value, $id, $title='ref') {
        if ($this->updateOne("usersCustomers", $key, $value, $id, $title)) {	
            return true;
        } else {
            return false;
        }
    }

    public function remove($id) {}

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
            $mailUrl = URL."includes/views/emails/userPasswordReset.php?".$fields;
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

    public function getDetails($id, $tag="ref") {
        return $this->getOne("usersCustomers", $id, $tag);
    }

    public function modifyOne($tag, $value, $id, $ref="ref") {
        return $this->updateOne("usersCustomers", $tag, $value, $id,$ref);
    }

    public function getList($start=false, $limit=false, $order="ref", $dir="ASC", $type="list") {
        return $this->lists("usersCustomers", $start, $limit, $order, $dir, false, $type);
    }

    public function listOne($id, $tag="ref") {
        return $this->getOne("usersCustomers", $id, $tag);
    }

    public function listOneValue($id, $reference) {
        return $this->getOneField("usersCustomers", $id, "ref", $reference);
    }

    public function getSortedList($id, $tag, $tag2 = false, $id2 = false, $tag3 = false, $id3 = false, $order = 'ref', $dir = "ASC", $logic = "AND", $start = false, $limit = false, $type="list") {
        return $this->sortAll("usersCustomers", $id, $tag, $tag2, $id2, $tag3, $id3, $order, $dir, $logic, $start, $limit, $type);
    }

    public function listAmin($start, $limit)  {
        $return['data'] = $this->getList($start, $limit, "lname");
        $return['counts'] = $this->getList(false, false, "lname", "ASC", "count");

        return $return;
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
            return $return;
        } else {
            return NULL;
        }
    }

    private function clean($data) {
        global $cart;
        global $cards;
        global $orders;
        global $tickets;
        $cart->user_id = $cards->user_id = $data['ref'];
        $add = $cart->getCart();

        $return['ref'] = intval( $data['ref'] );
        $return['firstName'] = $data['fname'];
        $return['lastName'] = $data['lname'];
        $return['emailAddress'] = $data['email'];
        $return['url']['profilePicture'] = "https://ui-avatars.com/api/?name=".urlencode($data['fname']. " ".$data['lname'])."&width=100&bold=true";
        $return['url']['profile'] = "";
        $return['verified'] = (1 == $return['verified']) ? true : false;
        $tickets->user_id = $data['ref'];
        $return['tickets'] = $tickets->getCounts("user_id");
        // $return['cards'] = $cards->getCards();
        $return['cart']['cartCount'] = count($add);
        $return['cart']['cartTotal'] = $cart->cartTotal();
        if ($this->admin) {
            $return['orders'] = $orders->adminCounters( intval( $data['ref'] ) );
        }
        $account['activeAccount'] = ("ACTIVE" == $data['status']) ? true : false;
        $account['newAccount'] = ("NEW" == $data['status']) ? true : false;
        $account['inactiveAccount'] = ("INAVTIVE" == $data['status']) ? true : false;
        $account['passwordChange'] = ("CHANGE_PASSWORD" == $data['status']) ? true : false;
        $return['creationDate'] = strtotime( $data['create_time'] );
        $return['lastModified'] = strtotime( $data['modify_time'] );
        $return['accountStatus'] = $account;
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

    function getUser() {
        $data = $this->listOne($this->id);
        return $this->formatResult($data, true);
    }

    public function initialize_table() {
        //create database
        $query = "CREATE TABLE IF NOT EXISTS `".dbname."`.`usersCustomers` (
            `ref` INT NOT NULL AUTO_INCREMENT, 
            `lname` VARCHAR(50) NOT NULL, 
            `fname` VARCHAR(50) NOT NULL, 
            `email` VARCHAR(255) NOT NULL, 
            `password` VARCHAR(1000) NOT NULL,
            `document` VARCHAR(255) NULL,
            `channel` VARCHAR(50) NULL,
            `ssoToken` VARCHAR(255) NULL,
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
        $query = "TRUNCATE `".dbname."`.`usersCustomers`";

        $this->query($query);
    }

    public function delete_table() {
        //clear database
        $query = "DROP TABLE `".dbname."`.`usersCustomers`";

        $this->query($query);
    }
}
?>