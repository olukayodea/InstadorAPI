<?php
    class apiStore extends api {
        public function prepFiles($header, $requestLink, $data, $files) {
            global $media;
            $requestData = explode("/", trim($requestLink, "/"));
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);

            if ($this->methodCheck($header['method'], "upload:".$mode)) {
                if ($this->authenticate($header)) {
                    $raw = false;
                    $media->class = "partner";
                    if ($mode == 'store') {
                        $media->type = "store";
                        if ($data  === false) {
                            $media->file[] = $files['image'];
                        } else {
                            $raw = true;
                            $returnedData = json_decode($data, true);
                            $rawData[] = $returnedData['image'];
                        }

                        $media->create($raw, $rawData);
                        $return['success'] = true;
                        $return['results']['complete'] = $media->complete;
                        $return['results']['incomplete'] = $media->incomplete;
                    } else if ($mode == 'item') {
                        $media->type = "item";
                        if ($data  === false) {
                            $media->file[] = $files['image'];
                        } else {
                            $raw = true;
                            $returnedData = json_decode($data, true);
                            $rawData[] = $returnedData['image'];
                        }

                        $media->create($raw, $rawData);
                        $return['success'] = true;
                        $return['results']['complete'] = $media->complete;
                        $return['results']['incomplete'] = $media->incomplete;
                    } else if ($mode == 'items') {
                        $media->type = "item";
                        if ($data  === false) {
                            $media->file = $media->reArrayFiles($files['image']);
                        } else {
                            $raw = true;
                            $returnedData = json_decode($data, true);
                            $rawData = $returnedData['image'];
                        }
                        $media->create($raw, $rawData);

                        $return['success'] = true;
                        $return['results']['complete'] = $media->complete;
                        $return['results']['incomplete'] = $media->incomplete;
                    } else if (($mode == "users") && ($action == "profile")) {
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
            global $inventory;
            global $usersStoreAdmin;
            global $options;
            global $tax;
            global $store;
            global $storeList;
            global $orders;
            global $category;
            global $wallet;
            global $bankAccount;
            global $tickets;

            $requestData = explode("/", $requestLink);
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);
            $string = @strtolower($requestData[2]);
            $page = @strtolower($requestData[3]);
            $page2 = @strtolower($requestData[4]);

            $location['longitude'] = $header['longitude'];
            $location['latitude'] = $header['latitude'];

            $returnedData = json_decode($data, true);

            if ($mode == "users" && ($action == "authotp" || $action == "activate")) {
                $this->exempt = true;
            }

            if (( $header['longitude'] == "" ) || ( $header['latitude'] == "" ) ) {
                $return['success'] = false;
                $return['error']['code'] = 10030;
                $return['error']["message"] = "Bad Request. Compulsory headers are missing";
            } else if ($this->methodCheck($header['method'], $mode.":".$action)) {
                if (($mode == "users") && ($action == "sso")) {
                    $login = $usersStoreAdmin->sso($returnedData);
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
                        $this->user_id = $usersStoreAdmin->id;
                        $this->userData = $usersStoreAdmin->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] = $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "login")) {
                    $login = $usersStoreAdmin->login($returnedData);
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
                    } else if ($login == 4) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['status'] = "CHANGE_PASSWORD";
                        $this->user_id = $usersStoreAdmin->id;
                        $return['token'] = $this->getToken();
                    } else {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $this->user_id = $usersStoreAdmin->id;
                        $this->userData = $usersStoreAdmin->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] =  $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "otp")) {
                    $usersStoreAdmin->email = $string;
                    $otp = $usersStoreAdmin->resendOtp();
                    $this->user_id = $usersStoreAdmin->id;
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
                    $register = $usersStoreAdmin->add($returnedData);
                    if ($register) {
                        $return['success'] = true;
                        $return['results'] = "OK";

                        $this->user_id = $usersStoreAdmin->id;
                        $this->userData = $usersStoreAdmin->getUser();

                        $return['token'] = $this->getToken();
                        $return['OTP'] = $usersStoreAdmin->otp;
                        $return['data'] = $this->userData;
                        $return['data']['token'] = $return['token'];
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10004;
                        $return['error']["message"] =  "Conflict. seems this account already exist, please login or reset your password to continue";
                    }
                } else if (($mode == "users") && ($action == "passwordreset")) {
                    $raw = explode( "_", base64_decode( $returnedData['resetToken'] ) );

                    if (time() < $raw[1]) {
                        $change = $usersStoreAdmin->passwordReset($raw[2], $returnedData['password'], $returnedData['otp']);
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
                    $usersStoreAdmin->email = $string;
                    $add = $usersStoreAdmin->passwordConfirm();
                    $userData = $usersStoreAdmin->listOne($add);
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
                } else if (($mode == "store") && ($action == "category")) {
                    $category->store_id = intval($string);
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
                        $usersStoreAdmin->id = $this->user_id;
                        $resetpassword = $usersStoreAdmin->logout();
                        $return['success'] = true;
                        $return['results'] = "OK";
                    } else if (($mode == "users") && ($action == "authotp")) {
                        $usersStoreAdmin->email = $this->userData['email'];
                        $otp = $usersStoreAdmin->resendOtp();
                        $this->user_id = $usersStoreAdmin->id;
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
                        $usersStoreAdmin->id = $this->user_id;
                        $usersStoreAdmin->otp = $returnedData['otp'];
                        
                        $resetpassword = $usersStoreAdmin->activateAccount();

                        if ($resetpassword === true) {
                            $this->userData = $usersStoreAdmin->getUser();
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
                        
                        $resetpassword = $usersStoreAdmin->changePassword($returnedData);

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
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11003;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store user, please contact the store administrator or store owner";
                        } else {
                            $bankAccount->user_id = $this->user_id;
                            $returnedData['user_type'] = 'store';
                            $editBankAccount = $bankAccount->add($returnedData);
                            if ($editBankAccount === true) {
                                $return['success'] = true;
                                $return['results'] = "OK";
                                $return['data'] = $usersStoreAdmin->formatResult($this->userData, true);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 10010;
                                $return['error']["message"] = "Service Unavailable";
                            }
                        }
                    } else if (($mode == "users") && ($action == "password")) {
                        $usersStoreAdmin->id = $this->user_id;
                        $editPassword = $usersStoreAdmin->editPassword($returnedData);
                        if ($editPassword === true) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10009;
                            $return['error']["message"] = "Unauthorized. invalid password";
                        }
                    } else if (($mode == "users") && ($action == "get")) {
                        $usersStoreAdmin->id = $this->user_id;
                        $this->userData = $usersStoreAdmin->getUser();
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->refresh();
                        $return['data'] =  $this->userData;
                    } else if (($mode == "users") && ($action == "edit")) {
                        $usersStoreAdmin->id = $this->user_id;
                        $editPassword = $usersStoreAdmin->edit($returnedData);
                        if ($editPassword == true) {
                            $usersStoreAdmin->id = $this->user_id;
                            $this->userData = $usersStoreAdmin->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                            $return['data'] =  $this->userData;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10010;
                            $return['error']["message"] = "Service Unavailable";
                        }
                    } else if (($mode == "users") && ($action == "roles")) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['data'] = $this->publicRole('Store_Roles');
                    } else if (($mode == "store") && ($action == "balance")) {
                        $wallet->user_id = $this->user_id;
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 22) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11010;
                            $return['error']["message"] = "You are logged in with an account not authorized to retrieve store balance";
                        } else {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['balance'] =  $wallet->balance(intval($string), "store");
                            $return['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                        }
                    } else if (($mode == "store") && ($action == "requestfunds")) {
                        $wallet->user_id = $this->user_id;
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 22) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11024;
                            $return['error']["message"] = "You are logged in with an account not authorized to request store balance";
                        } else {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['balance'] =  $wallet->withdrawalRequest(intval($returnedData['store_id']), "store");
                            $return['payOutDate'] = date("d-m-Y",  strtotime("next Friday"));
                        }
                    } else if (($mode == "store") && ($action == "wallet")) {

                        $wallet->user_id = intval($string);
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 22) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11010;
                            $return['error']["message"] = "You are logged in with an account not authorized to retrieve store balance";
                        } else {
                            $return = $wallet->apiGet($page, "store", $page2);
                        }
                    } else if (($mode == "store") && ($action == "adduser")) {
                        $store->user_id = $this->user_id;
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11003;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store user, please contact the store administrator or store owner";
                        } else {
                            /**
                             * add the users in a store
                             */
                            $store->id = $returnedData['store_id'];
                            $add = $store->addUser($returnedData);
                            if ($add === false) {
                                $return['success'] = false;
                                $return['error']['code'] = 12001;
                                $return['error']['message'] = "Cannot complete action, this store verification has failed";
                            } else if ($add === null) {
                                $return['success'] = false;
                                $return['error']['code'] = 12002;
                                $return['error']['message'] = "Cannot complete action, this store is pending verification";
                            } else if ($add === true) {
                                $return['success'] = true;
                                $return['results'] = "OK";

                                $this->user_id = $usersStoreAdmin->id;
                                $this->userData = $usersStoreAdmin->getUser();

                                $return['token'] = $this->getToken();
                                $return['data'] =  $this->userData;
                                $return['data']['token'] = $return['token'];

                            } else {
                                $return = $add;
                            }
                    
                        }
                    } else if (($mode == "store") && ($action == "add")) {
                        $returnedData['created_by'] = $this->user_id;
                        $store->user_id = $this->user_id;
                        $storeList->user_id = $this->user_id;

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11001;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store, please contact the store administrator";
                        } else {
                            $add = $store->add($returnedData);
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Created";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11000;
                                $return['message'] = "Not Implemented, An error occured while adding this store";
                            }
                        }
                    } else if (($mode == "store") && ($action == "edit")) {
                        $store->user_id = $this->user_id;
                        $storeList->user_id = $this->user_id;
                        $store->id = intval($string);
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11002;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage any store, please contact the store administrator";
                         } else {
                            $add = $store->edit($returnedData);
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Edited";
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11004;
                                $return['message'] = "Not Implemented, An error occured while editing this store";
                            }
                        }
                    } else if (($mode == "store") && (($action == "get") || ($action == "list"))) {
                        $store->user_id = $this->user_id;
                        if ($action == "list") {
                            $page = intval($string);
                        } else {
                            $store->id = intval($string);
                        }
                        $return = $store->retrieveAPI( $page );
                    } else if (($mode == "store") && ($action == "users")) {
                        $store->user_id = $this->user_id;
                        $store->id = intval($string);
                        $return = $store->retrieveUsers( $page );
                    } else if (($mode == "store") && ($action == "changestatus")) {

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11002;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage any store, please contact the store administrator";
                         } else {
                            $store->user_id = $this->user_id;
                            $store->id = intval($string);

                            $data = $store->toggleStatus();
                            if ($data) {
                                if ($data == "pending_verification") {
                                    $return['success'] = false;
                                    $return['error']['code'] = 12002;
                                    $return['message'] = "Cannot complete action, this store is pending verification";
                                } else if ($data == "verification_failed") {
                                    $return['success'] = false;
                                    $return['error']['code'] = 12001;
                                    $return['message'] = "Cannot complete action, this store verification has failed";
                                } else if ($data == "unverified") {
                                    $return['success'] = false;
                                    $return['error']['code'] = 12002;
                                    $return['message'] = "Cannot complete action, this store is pending verification";
                                } else {
                                    $add = $store->retrieveAPI();
                                    $return['success'] = true;
                                    $return['data'] = $add;
                                }
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11007;
                                $return['message'] = "No record found";
                            }
                        }
                    } else if (($mode == "store") && ($action == "delete")) {
                        $store->id = intval($string);
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11002;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage any store, please contact the store administrator";
                         } else {
                            $remove = $store->remove($string);
                            if ($remove) {
                                $return['success'] = true;
                                $return['results'] = "OK";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11006;
                                $return['message'] = "Not Implemented, An error occured while removing this store";
                            }
                        }
                    } else if (($mode == "items") && ($action == "add")) {
                        $inventory->user_id = $this->user_id;
                        if ($this->getAccessRight($this->userRoles, "role_id", 23) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11002;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage any inventory, please contact the store administrator";
                        } else {
                            $add = $inventory->add($returnedData);
                            if ($add === false) {
                                $return['success'] = false;
                                $return['error']['code'] = 12001;
                                $return['error']['message'] = "This store verification has failed";
                            } else if ($add === null) {
                                $return['success'] = false;
                                $return['error']['code'] = 12002;
                                $return['error']['message'] = "This store is pending verification";
                            } else if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Added";
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 12003;
                                $return['error']['message'] = "Not Implemented, An error occured while adding this item";
                            }
                        }
                    } else if (($mode == "items") && ($action == "search")) {
                        $inventory->string = $string;
                        $return = $inventory->retrieveAPI( $action );
                    } else if (($mode == "items") && ($action == "store")) {
                        $inventory->user_id = $this->user_id;
                        $inventory->store_id = intval($string);
                        $return = $inventory->retrieveStoreAPI( intval($page), true );
                    } else if (($mode == "items") && ($action == "get")) {
                        $inventory->string = $string;
                        $return = $inventory->retrieveAPI( $action );
                    } else if (($mode == "orders") && ($action == "get")) {    
                        $orders->user_id = $this->userData['ref'];
                        $orders->location = $location;
                        if (intval($string) > 0) {
                            $orders->id = intval($string);
                            $string = "one";
                        }
                        $return = $orders->getAllStoreOrders($string, $page);
                    } else if (($mode == "orders") && ($action == "changestatus")) {
                        $orders->location = $location;
                        if ((strtolower($returnedData['status']) == strtolower("process")) || (strtolower($returnedData['status']) == strtolower("cancel"))) {
                            $orders->user_id = $this->userData['ref'];
                            $return = $orders->modifyStoreOrder($returnedData);
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 13008;
                            $return['error']["message"] = "You can not perform this function on this order";
                        }
                    } else if (($mode == "orders") && ($action == "cancel")) {
                        $orders->user_id = $this->userData['ref'];
                        $return = $orders->cancelStoreOrder($string);
                    } else if (($mode == "orders") && ($action == "processed")) {
                        $orders->user_id = $this->userData['ref'];
                        $return = $orders->completeStoreOrder($returnedData['ref']);
                    } else if (($mode == "category") && ($action == "add")) {
                        $returnedData['created_by'] = $this->user_id;
                        $category->user_id = $this->user_id;

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11009;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store category, please contact the store administrator";
                        } else {
                            $add = $category->add($returnedData);
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Created";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11021;
                                $return['message'] = "Not Implemented, An error occured while adding this store category";
                            }
                        }
                    } else if (($mode == "category") && ($action == "edit")) {
                        $category->user_id = $this->user_id;

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11021;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store category, please contact the store administrator";
                        } else {
                            $add = $category->edit($returnedData);
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Created";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11022;
                                $return['message'] = "Not Implemented, An error occured while editing this store category";
                            }
                        }
                    } else if (($mode == "category") && ($action == "changestatus")) {
                        $category->user_id = $this->user_id;
                        $category->id = intval($string);

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11009;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store category, please contact the store administrator";
                        } else {
                            if ($category->toggleStatus()) {
                                $add = $category->retrieveAPI();
                                $return['success'] = true;
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11007;
                                $return['message'] = "No record found";
                            }
                        }
                    } else if (($mode == "category") && ($action == "delete")) {
                        $category->user_id = $this->user_id;
                        $category->id = intval($string);

                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11009;
                            $return['error']["message"] = "You are logged in with an account not authorized to add any store category, please contact the store administrator";
                        } else {
                            $add = $category->remove();
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "Deleted";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11023;
                                $return['message'] = "Not Implemented, An error occured while deleting this store category";
                            }
                        }
                    } else if (($mode == "tickets") && ($action == "add")) {
                        if ($this->userData['verified'] == "pending") {
                            $return['success'] = false;
                            $return['error']['code'] = 10011;
                            $return['error']["message"] = "Your account is pending verification";
                        } else if ($this->userData['verified'] == "rejected") {
                            $return['success'] = false;
                            $return['error']['code'] = 10012;
                            $return['error']["message"] = "Your account verification has been rejected, please verify again";
                        } else if ($this->userData['verified'] == "none") {
                            $return['success'] = false;
                            $return['error']['code'] = 10013;
                            $return['error']["message"] = "Your account is yet to be verified";
                        } else if ($this->getAccessRight($this->userRoles, "role_id", 20) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11002;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage any store, please contact the store administrator";
                         } else {
                            $tickets->store_id = $returnedData['store_id'];
                            $returnedData['last_update_by'] = 'store'; 
                            $add = $tickets->add($returnedData);
                            if ($add) {
                                $return['success'] = true;
                                $return['results'] = "OK";
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 15000;
                                $return['error']['message'] = "An error occured while adding this ticket";
                            }
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
                        $add = $tickets->cancelStore("remove");
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
                        $add = $tickets->cancelStore("delete");
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
                        $add = $tickets->closeStore();
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
                        // $page is the store ID;
                        // $page2 is the page
                        if (intval($string) > 0) {
                            $tickets->id = intval($string);
                            $string = "one";
                        } else {
                            $tickets->id = NULL;
                        }
                        $tickets->store_id = $page;
                        $return = $tickets->getStore($string);
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
            global $usersStoreAdmin;
            $refresh = $usersStoreAdmin->listOne($this->user_id);
            if (($refresh['token_refresh'] < time()) || ($refresh['token'] == NULL)) {
                $this->token = $this->user_id.$this->createRandomPassword(15);

                $usersStoreAdmin->editOne("token", $this->token, $this->user_id, "ref");
                $usersStoreAdmin->editOne("token_refresh", time()+(60*60*24*180), $this->user_id, "ref");
            } else {
                $this->token = $refresh['token'];
            }
            return $this->token;
        }

        private function refresh () {
            global $usersStoreAdmin;
            return $usersStoreAdmin->listOneValue($this->user_id, "token");
        }

		private function authenticate($header) {
            if ($header['auth'] != "") {
                $split = explode("_", base64_decode($header['auth']));
                $token = $split[1];
                if ($header['key'] == $split[0]) {
                    if ($this->checkExixst("usersStoreAdmin", "token", $token) == 1) {

                        global $usersStoreAdmin;
                        global $userStoreRoles;
                        
                        $this->userData = $usersStoreAdmin->getDetails($token, "token");

                        if (1 == $this->userData['verified']) {
                            $this->userData['verified'] = "pending";
                        } else if (2 == $this->userData['verified']) {
                            $this->userData['verified'] = "approved";
                        } else if (3 == $this->userData['verified']) {
                            $this->userData['verified'] = "rejected";
                        } else {
                            $this->userData['verified'] = "none";
                        }

                        $userStoreRoles->user_id = $this->userData['ref'];
                        if (($this->userData['status'] == "ACTIVE") || ($this->userData['status'] == "CHANGE_PASSWORD") || ($this->exempt)) {
                            $this->userRoles = $userStoreRoles->getRoles();
                            $_SESSION['storeAdmin']['user_id'] = $this->userData['ref'];
                            $this->token = $this->userData['token'];
                            unset($this->userData['password']);
                            unset($this->userData['token']);
                            return true;
                        } else {
                            return false;
                        }

                        // $userStoreRoles->user_id = $this->userData['ref'];
                        // $this->userRoles = $userStoreRoles->getRoles();
                        // $_SESSION['storeAdmin']['user_id'] = $this->userData['ref'];
                        // $this->token = $this->userData['token'];
                        // unset($this->userData['password']);
                        // unset($this->userData['token']);
                        // return true;
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
                $array[] = "upload:item";
                $array[] = "upload:items";
                $array[] = "upload:users";
                $array[] = "upload:store";
                $array[] = "uploadraw:item";
                $array[] = "uploadraw:items";
                $array[] = "uploadraw:users";
                $array[] = "uploadraw:store";
                $array[] = "store:add";
                $array[] = "store:requestfunds";
                $array[] = "store:adduser";
                $array[] = "category:add";
                $array[] = "items:add";
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
                $array[] = "users:roles";
                $array[] = "store:balance";
                $array[] = "store:wallet";
                $array[] = "store:get";
                $array[] = "store:list";
                $array[] = "store:users";
                $array[] = "store:category";
                $array[] = "items:search";
                $array[] = "items:get";
                $array[] = "items:store";
                $array[] = "orders:get";
                $array[] = "tickets:get";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "PUT") {
                $array[] = "users:edit";
                $array[] = "users:password";
                $array[] = "users:passwordreset";
                $array[] = "users:resetpassword";
                $array[] = "users:activate";
                $array[] = "users:accountdetails";
                $array[] = "category:edit";
                $array[] = "store:edit";
                $array[] = "category:changestatus";
                $array[] = "store:changestatus";
                $array[] = "orders:changestatus";
                $array[] = "orders:processed";
                $array[] = "tickets:edit";
                $array[] = "tickets:close";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "DELETE") {
                $array[] = "users:delete";
                $array[] = "store:delete";
                $array[] = "orders:cancel";
                $array[] = "category:delete";
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