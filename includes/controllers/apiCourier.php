<?php
    class apiCourier extends api {
        public function prepFiles($header, $requestLink, $data, $files) {
            global $media;
            $requestData = explode("/", trim($requestLink, "/"));
            $mode = @strtolower($requestData[0]);
            $action = @strtolower($requestData[1]);
            
            if ($this->methodCheck($header['method'], "upload:".$mode)) {
                if ($this->authenticate($header)) {
                    $raw = false;
                    $media->class = "courier";
                    if (($mode == "users") && ($action == "profile")) {
                        $media->type = "profile";
                        if ($data  === false) {
                            $media->file[] = $files['image'];
                        } else {
                            $raw = true;
                            $returnedData = json_decode($data, true);
                            $rawData[] = $returnedData['image'];
                        }
                        $media->user_id = $this->userData['ref'];
                        $media->create($raw, $rawData);

                        $return['success'] = true;
                        $return['results']['complete'] = $media->complete;
                        $return['results']['incomplete'] = $media->incomplete;
                    } else if (($mode == "users") && ($action == "documents")) {
                        $media->type = "documents";

                        if ($data  === false) {
                            $media->file = $media->reArrayFiles($files['image']);
                        } else {
                            $raw = true;
                            $returnedData = json_decode($data, true);
                            $rawData = $returnedData['image'];
                        }
                        $media->user_id = $this->userData['ref'];
                        $media->create($raw, $rawData);
                        $return['success'] = true;
                        $return['results']['complete'] = $media->complete;
                        $return['results']['incomplete'] = $media->incomplete;
                    }
                } else {
                    $return['success'] = false;
                    $return['error']['code'] = 10018;
                    $return['error']["message"] = "Unauthorized";
                }
            } else {
                $return['success'] = false;
                $return['error']['code'] = 10019;
                $return['error']["message"] = "Bad Request";
            }

            return $this->convert_to_json($return);
        }

        public function prep($header, $requestLink, $data) {
            global $usersCourier;
            global $options;
            global $tax;
            global $category;
            global $bankAccount;
            global $orders;
            global $wallet;
            global $inventory;
            global $tickets;

            $requestData = explode("/", $requestLink);
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);
            $string = @strtolower($requestData[2]);
            $page = @strtolower($requestData[3]);

            $location['longitude'] = $header['longitude'];
            $location['latitude'] = $header['latitude'];

            $returnedData = json_decode($data, true);

            if ($mode == "users" && ($action == "authotp" || $action == "activate")) {
                $this->exempt = true;
            }
            $inventory->isCourier = true;

            if (( $header['longitude'] == "" ) || ( $header['latitude'] == "" ) ) {
                $return['success'] = false;
                $return['error']['code'] = 10030;
                $return['error']["message"] = "Bad Request. Compulsory headers are missing";
            } else if ($this->methodCheck($header['method'], $mode.":".$action)) {
                if (($mode == "users") && ($action == "sso")) {
                    $login = $usersCourier->sso($returnedData);
                    if ($login == 0) {
                        $return['success'] = false;
                        $return['error']['code'] = 10001;
                        $return['error']["message"] = "Unauthorized. NO users with the email and password combination was found";
                    } else if ($login == 1) {
                        $return['success'] = false;
                        $return['error']['code'] = 10002;
                        $return['error']["message"] = "Unauthorized. This account has not been confirmed yet, please click on the confirmation link in the email sent";
                        $return['token'] = $this->getToken();  
                    } else if ($login == 3) {
                        $return['success'] = false;
                        $return['error']['code'] = 10003;
                        $return['error']["message"] =  "Not Acceptable. This account has been deactivated. PLease contact us at contactus@Instadoor.ca";
                    } else {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $this->user_id = $usersCourier->id;
                        $this->userData = $usersCourier->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] = $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "login")) {
                    $login = $usersCourier->login($returnedData);
                    if ($login == 0) {
                        $return['success'] = false;
                        $return['error']['code'] = 10001;
                        $return['error']["message"] = "Unauthorized. NO users with the email and password combination was found";
                    } else if ($login == 1) {
                        $return['success'] = false;
                        $return['error']['code'] = 10002;
                        $return['error']["message"] = "Unauthorized. This account has not been confirmed yet, please click on the confirmation link in the email sent";
                        $return['token'] = $this->getToken();
                    } else if ($login == 3) {
                        $return['success'] = false;
                        $return['error']['code'] = 10003;
                        $return['error']["message"] =  "Not Acceptable. This account has been deactivated. PLease contact us at contactus@Instadoor.ca";
                    } else {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $this->user_id = $usersCourier->id;
                        $this->userData = $usersCourier->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] =  $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "otp")) {
                    $usersCourier->email = $string;
                    $otp = $usersCourier->resendOtp();
                    $this->user_id = $usersCourier->id;
                    if ($otp) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->getToken();
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10005;
                        $return['error']['message'] = "Not Found. Can not find traces of this account";
                    }
                } else if (($mode == "users") && ($action == "register")) {
                    $register = $usersCourier->add($returnedData);
                    if ($register) {
                        $return['success'] = true;
                        $return['results'] = "OK";

                        $this->user_id = $usersCourier->id;
                        $this->userData = $usersCourier->getUser();

                        $return['token'] = $this->getToken();
                        $return['OTP'] = $usersCourier->otp;
                        $return['data'] =  $this->userData;
                        $return['data']['token'] = $return['token'];
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10004;
                        $return['error']["message"] =  "Conflict. seems this account already exist, please login or reset your password to continue";
                    }
                } else if (($mode == "users") && ($action == "passwordreset")) {
                    $raw = explode( "_", base64_decode( $returnedData['resetToken'] ) );

                    if (time() < $raw[1]) {
                        $change = $usersCourier->passwordReset($raw[2], $returnedData['password'], $returnedData['otp']);
                        if ($change) { 
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['additional_message'] = "Your new password has been set, you can now login now";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10023;
                            $return['error']['message'] = "We cannot change your password at this time, please confirm the OTP, if you believe this OTP is correct, please try again or contact the administrator";
                        }
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10024;
                        $return['error']['message'] = "OTP has expired";
                    }
                } else if (($mode == "users") && ($action == "recovery")) {
                    $usersCourier->email = $string;
                    $add = $usersCourier->passwordConfirm();
                    $userData = $usersCourier->listOne($add);
                    $expire = time()+(60*60*24);
                    $loginToken = base64_encode(rand()."_".$expire."_".$userData['email']);
                    if ($add) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['message'] = "A OTP has been sent to your email. This OTP expires in 24 hours. Ignore the email if you change your mind at any time";
                        $return['resetToken'] = $loginToken;
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10005;
                        $return['error']['message'] = "Not Found. Can not find traces of this account";
                    }
                } else if (($mode == "items") && ($action == "category")) {
                    $return = $category->retrieveAPI();
                } else if ($mode == "main") {
                    $tax->location = $location;
                    $return['success'] = true;
                    $return['results'] = "OK";
                    $return['options'] = $options->apiOptions();
                    $return['tax'] = $tax->apiTax();

                } else if ($this->authenticate($header)) {
                    //authenticated users only
                    $this->user_id = $this->userData['ref'];
                    if (($mode == "users") && ($action == "logout")) {
                        $usersCourier->id = $this->user_id;
                        $resetpassword = $usersCourier->logout();
                        $return['success'] = true;
                        $return['results'] = "OK";
                    } else if (($mode == "users") && ($action == "authotp")) {
                        $usersCourier->email = $this->userData['email'];
                        $otp = $usersCourier->resendOtp();
                        $this->user_id = $usersCourier->id;
                        if ($otp) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->getToken();
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10005;
                            $return['error']['message'] = "Not Found. Can not find traces of this account";
                        }
                    } else if (($mode == "users") && ($action == "activate")) {
                        $usersCourier->id = $this->user_id;
                        $usersCourier->otp = $returnedData['otp'];
                        
                        $resetpassword = $usersCourier->activateAccount();

                        if ($resetpassword === true) {
                            $this->userData = $usersCourier->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                            $return['data'] =  $this->userData;
                            $return['data']['token'] = $return['token'];

                        } else  if ($resetpassword === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10006;
                            $return['error']["message"] = "Not Found. An error occured validating this account; Invalid OTP";
                        }
                    } else if (($mode == "users") && ($action == "resetpassword")) {
                        $returnedData['id'] = $this->user_id;
                        
                        $resetpassword = $usersCourier->changePassword($returnedData);

                        if ($resetpassword === true) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();

                        } else  if ($resetpassword === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10007;
                            $return['error']["message"] = "Internal Server Error";
                        } else  {
                            $return['success'] = false;
                            $return['error']['code'] = 10008;
                            $return['error']["message"] = "Not Found. An error occured retrieving this account";
                        }
                    } else if (($mode == "users") && ($action == "accountdetails")) {
                        $bankAccount->user_id = $this->user_id;
                        $returnedData['user_type'] = 'courier';
                        $editBankAccount = $bankAccount->add($returnedData);
                        if ($editBankAccount === true) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['data'] = $usersCourier->formatResult($this->userData, true);
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10010;
                            $return['error']["message"] = "Service Unavailable";
                        }
                    } else if (($mode == "users") && ($action == "get")) {
                        $usersCourier->id = $this->user_id;
                        $this->userData = $usersCourier->getUser();
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->refresh();
                        $return['data'] =  $this->userData;
                    } else if (($mode == "users") && ($action == "get")) {
                        $usersCourier->id = $this->user_id;
                        $this->userData = $usersCourier->getUser();
                        $return['success'] = true;
                        $return['results'] = "OK";

                        $return['token'] = $this->refresh();
                        $return['data'] =  $this->userData;
                    } else if (($mode == "users") && ($action == "edit")) {
                        $usersCourier->id = $this->user_id;
                        $editPassword = $usersCourier->edit($returnedData);
                        if ($editPassword == true) {
                            $usersCourier->id = $this->user_id;
                            $this->userData = $usersCourier->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";

                            $return['token'] = $this->refresh();
                            $return['data'] =  $this->userData;
                            $return['data']['token'] = $return['token'];
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10010;
                            $return['error']["message"] = "Service Unavailable";
                        }
                    } else if (($mode == "users") && ($action == "balance")) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['balance'] =  $wallet->balance($this->user_id, "courier");
                        $return['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                    } else if (($mode == "users") && ($action == "requestfunds")) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['balance'] =  $wallet->withdrawalRequest($this->user_id, "courier");
                        $return['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                    } else if (($mode == "users") && ($action == "wallet")) {

                        $wallet->user_id = $this->user_id;
                        $return = $wallet->apiGet($string, "courier", $page);
                    } else if (($mode == "orders") && (($action == "accepted") || ($action == "picked"))) {
                        if ($this->userData['verified'] == "approved") {
                            $orders->isCourier = true;
                            $orders->user_id = $this->userData['ref'];
                            $returnedData['status'] = $action;
                            $return = $orders->changeStatus($returnedData, true);
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "orders") && ($action == "cancel")) {
                        if ($this->userData['verified'] == "approved") {
                            $orders->isCourier = true;
                            $orders->user_id = $this->userData['ref'];
                            $returnedData['status'] = "cancel";
                            $return = $orders->changeStatus($returnedData, true);
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "orders") && ($action == "get")) {
                        if ($this->userData['verified'] == "approved") {
                            $orders->isCourier = true;
                            $orders->location = $location;
                            $orders->user_id = $this->userData['ref'];
                            if (intval($string) > 0) {
                                $orders->id = intval($string);
                                $string = "one";
                            }
                            $return = $orders->getCourier($string, $page);
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "orders") && ($action == "respond")) {
                        if ($this->userData['verified'] == "approved") {
                            $orders->isCourier = true;
                            $orders->user_id = $this->userData['ref'];
                            $return = $orders->respond($returnedData);
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "orders") && ($action == "deliver")) { 
                        if ($this->userData['verified'] == "approved") { 
                            $orders->isCourier = true;
                            $orders->user_id = $this->userData['ref'];
                            $return = $orders->deliver($returnedData['ref']); 
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "orders") && ($action == "location")) {
                        if ($this->userData['verified'] == "approved") { 
                            $orders->isCourier = true;
                            $orders->user_id = $this->userData['ref'];
                            $return = $orders->dumpLocation($returnedData);
                        } else if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        }
                    } else if (($mode == "tickets") && ($action == "add")) {
                        $tickets->courier_id = $this->userData['ref'];
                        $returnedData['last_update_by'] = 'courier'; 
                        $add = $tickets->add($returnedData);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15000;
                            $return['error']['message'] = "An error occured while adding this ticket";
                        }
                    } else if (($mode == "tickets") && ($action == "edit")) {
                        $tickets->user_id = $this->userData['ref'];
                        $add = $tickets->edit($returnedData);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['data'] = $add;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15001;
                            $return['error']['message'] = "An error occured while editing this ticket";
                        }
                    } else if (($mode == "tickets") && ($action == "remove")) {
                        $tickets->id = intval($string);
                        $tickets->user_id = $this->userData['ref'];
                        $add = $tickets->cancelCourier("remove");
                        if ($add) {
                            if ($add < 1) {
                                $return['success'] = false;
                                $return['error']['code'] = 15004;
                                $return['error']['message'] = "You do not have permission to delete this ticket";
                            } else {
                                $return['success'] = true;
                                $return['results'] = "OK";
                            }
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15002;
                            $return['error']['message'] = "An error occured while removing this ticket";
                        }
                    } else if (($mode == "tickets") && ($action == "delete")) {
                        $tickets->id = intval($string);
                        $tickets->user_id = $this->userData['ref'];
                        $add = $tickets->cancelCourier("delete");
                        if ($add) {
                            if ($add < 1) {
                                $return['success'] = false;
                                $return['error']['code'] = 15005;
                                $return['error']['message'] = "You do not have permission to delete this ticket response";
                            } else {
                                $return['success'] = true;
                                $return['results'] = "OK";
                            }
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15003;
                            $return['error']['message'] = "An error occured while deleting this ticket response";
                        }
                    } else if (($mode == "tickets") && ($action == "close")) {
                        $tickets->id = intval($string);
                        $tickets->user_id = $this->userData['ref'];
                        $add = $tickets->closeCourier();
                        if ($add) {
                            if ($add < 1) {
                                $return['success'] = false;
                                $return['error']['code'] = 15005;
                                $return['error']['message'] = "You do not have permission to close this ticket response";
                            } else {
                                $return['success'] = true;
                                $return['results'] = "OK";
                            }
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15003;
                            $return['error']['message'] = "An error occured while deleting this ticket response";
                        }
                    } else if (($mode == "tickets") && ($action == "get")) {
                        if (intval($string) > 0) {
                            $tickets->id = intval($string);
                            $string = "one";
                        } else {
                            $tickets->id = NULL;
                        }
                        $tickets->user_id = $this->userData['ref'];
                        $return = $tickets->getCourier($string);
                    }
                } else {
                    $return['success'] = false;
                    $return['error']['code'] = 10018;
                    $return['error']["message"] = "Unauthorized";
                }
            } else {
                $return['success'] = false;
                $return['error']['code'] = 10019;
                $return['error']["message"] = "Bad Request";
            }
            return $this->convert_to_json($return);
        }

        private function getToken () {
            global $usersCourier;
            $refresh = $usersCourier->listOne($this->user_id);
            if (($refresh['token_refresh'] < time()) || ($refresh['token'] == NULL)) {
                $this->token = $this->user_id.$this->createRandomPassword(15);

                $usersCourier->editOne("token", $this->token, $this->user_id, "ref");
                $usersCourier->editOne("token_refresh", time()+(60*60*24*180), $this->user_id, "ref");
            } else {
                $this->token = $refresh['token'];
            }
            return $this->token;
        }

        private function refresh () {
            global $usersCourier;
            return $usersCourier->listOneValue($this->user_id, "token");
        }

		private function authenticate($header) {
            global $usersCourier;
            if ($header['auth'] != "") {
                $split = explode("_", base64_decode($header['auth']));
                $token = $split[1];
                if ($header['key'] == $split[0]) {
                    if ($this->checkExixst("usersCourier", "token", $token) == 1) {
                        $this->userData = $usersCourier->getDetails($token, "token");

                        if (1 == $this->userData['verified']) {
                            $this->userData['verified'] = "pending";
                        } else if (2 == $this->userData['verified']) {
                            $this->userData['verified'] = "approved";
                        } else if (3 == $this->userData['verified']) {
                            $this->userData['verified'] = "rejected";
                        } else {
                            $this->userData['verified'] = "none";
                        }

                        if (($this->userData['status'] == "ACTIVE") || ($this->userData['status'] == "CHANGE_PASSWORD") || ($this->exempt)) {
                            $_SESSION['courier']['user_id'] = $this->userData['ref'];
                            $this->token = $this->userData['token'];
                            unset($this->userData['password']);
                            unset($this->userData['token']);
                            return true;
                        } else {
                            return false;
                        }
                        // $this->userData = $usersCourier->getDetails($token, "token");
                        // $_SESSION['courier']['user_id'] = $this->userData['ref'];
                        // $this->token = $this->userData['token'];
                        // unset($this->userData['password']);
                        // unset($this->userData['token']);
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        private function methodCheck($method, $type) {
            $array = array();
            if ($method == "POST") {
                $array[] = "users:add";
                $array[] = "users:sso";
                $array[] = "users:login";
                $array[] = "users:register";
                $array[] = "users:requestfunds";
                $array[] = "upload:item";
                $array[] = "upload:items";
                $array[] = "upload:users";
                $array[] = "uploadraw:item";
                $array[] = "uploadraw:items";
                $array[] = "uploadraw:users";
                $array[] = "orders:location";
                $array[] = "tickets:add";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "GET") {
                $array[] = "users:get";
                $array[] = "users:profile";
                $array[] = "users:logout";
                $array[] = "users:otp";
                $array[] = "users:authotp";
                $array[] = "users:recovery";
                $array[] = "users:balance";
                $array[] = "users:wallet";
                $array[] = "orders:get";
                $array[] = "tickets:get";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "PUT") {
                $array[] = "users:edit";
                $array[] = "users:accountdetails";
                $array[] = "users:password";
                $array[] = "users:passwordreset";
                $array[] = "users:resetpassword";
                $array[] = "users:activate";
                $array[] = "orders:respond";
                $array[] = "orders:deliver";
                $array[] = "orders:cancel";
                $array[] = "orders:accepted";
                $array[] = "orders:picked";
                $array[] = "tickets:edit";
                $array[] = "tickets:close";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "DELETE") {
                $array[] = "users:delete";
                $array[] = "tickets:remove";
                $array[] = "tickets:delete";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
    }
?>