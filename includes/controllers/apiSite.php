<?php
    class apiSite extends api {
        public function prepFiles($header, $requestLink, $data, $files) {
            global $media;
            $requestData = explode("/", trim($requestLink, "/"));
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);

            if ($this->methodCheck($header['method'], "upload:".$mode)) {
                if ($this->authenticate($header)) {
                    $raw = false;
                    $media->class = "site";
                    if ($mode == 'store') {
                        $media->class = "partner";
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
                            $rawData[] = $returnedData['image'];
                        }
                        $media->user_id = $this->userData['ref'];
                        $media->create($raw, $rawData);
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
            global $usersSiteAdmin;
            global $usersStoreAdmin;
            global $usersCourier;
            global $usersCustomers;
            global $options;
            global $tax;
            global $category;
            global $store;
            global $orders;
            global $inventory;
            global $wallet;
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

            if ($this->methodCheck($header['method'], $mode.":".$action)) {
                if (($mode == "users") && ($action == "login")) {
                    $login = $usersSiteAdmin->login($returnedData);
                    if ($login == 0) {
                        $return['success'] = false;
                        $return['error']['code'] = 10001;
                        $return['error']["message"] = "Unauthorized. No users with the email and password combination was found";
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
                        $this->user_id = $usersSiteAdmin->id;
                        $this->userData = $usersSiteAdmin->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] = $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "otp")) {
                    $usersSiteAdmin->email = $string;
                    $otp = $usersSiteAdmin->resendOtp();
                    $this->user_id = $usersSiteAdmin->id;
                    if ($otp) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->getToken();
                    } else {
                        $return['success'] = false;
                        $return['error']['code'] = 10005;
                        $return['error']['message'] = "Not Found. Can not find traces of this account";
                    }
                } else if (($mode == "users") && ($action == "passwordreset")) {
                    $raw = explode( "_", base64_decode( $returnedData['resetToken'] ) );

                    if (time() < $raw[1]) {
                        $change = $usersSiteAdmin->passwordReset($raw[2], $returnedData['password'], $returnedData['otp']);
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
                    $usersSiteAdmin->email = $string;
                    $add = $usersSiteAdmin->passwordConfirm();
                    $userData = $usersSiteAdmin->listOne($add);
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
                } else if ($mode == "main") {
                    $tax->location = $location;
                    $return['success'] = true;
                    $return['results'] = "OK";
                    $return['options'] = $options->apiOptions();
                    $return['tax'] = $tax->apiTax();

                } else if ($this->authenticate($header)) {
                    $tickets->admin = true;
                    //authenticated users only
                    $this->user_id = $this->userData['ref'];
                    if (($mode == "users") && ($action == "logout")) {
                        $usersSiteAdmin->id = $this->user_id;
                        $resetpassword = $usersSiteAdmin->logout();
                        $return['success'] = true;
                        $return['results'] = "OK";
                    } else if (($mode == "users") && ($action == "authotp")) {
                        $usersSiteAdmin->email = $this->userData['email'];
                        $otp = $usersSiteAdmin->resendOtp();
                        $this->user_id = $usersSiteAdmin->id;
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
                        $usersSiteAdmin->id = $this->user_id;
                        $usersSiteAdmin->otp = $returnedData['otp'];
                        
                        $resetpassword = $usersSiteAdmin->activateAccount();

                        if ($resetpassword === true) {
                            $this->userData = $usersSiteAdmin->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                            $return['data'] = $this->userData;
                            $return['data']['token'] = $return['token'];

                        } else  if ($resetpassword === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10006;
                            $return['error']["message"] = "Not Found. An error occured validating this account; Invalid OTP";
                        }
                    } else if (($mode == "users") && ($action == "resetpassword")) {
                        $returnedData['id'] = $this->user_id;
                        
                        $resetpassword = $usersSiteAdmin->changePassword($returnedData);

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
                    } else if (($mode == "users") && ($action == "password")) {
                        $usersSiteAdmin->id = $this->user_id;
                        $editPassword = $usersSiteAdmin->editPassword($returnedData);
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
                        $usersSiteAdmin->id = $this->user_id;
                        $this->userData = $usersSiteAdmin->getUser();
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->refresh();
                        $return['data'] = $this->userData;
                    } else if (($mode == "users") && ($action == "adduser")) {
                        $usersSiteAdmin->user_id = $this->user_id;
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {
                            /**
                             * add the users in a store
                             */ 
                            $usersSiteAdmin->id = $returnedData['store_id'];
                            $add = $usersSiteAdmin->addUser($returnedData);
                            if ($add) {
                               if ($add === true) {
                                    $return['success'] = true;
                                    $return['results'] = "OK";

                                    $this->user_id = $usersSiteAdmin->id;
                                    $this->userData = $usersSiteAdmin->getUser();

                                    $return['token'] = $this->getToken();
                                    $return['data'] =  $this->userData;
                                    $return['data']['token'] = $return['token'];

                                } else {
                                    $return = $add;
                                }
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 10015;
                                $return['error']['message'] = "Not Implemented, An error occured while creating this user";
                            }
                        }
                    } else if (($mode == "users") && ($action == "edit")) {
                        $usersSiteAdmin->id = $this->user_id;
                        $editPassword = $usersSiteAdmin->edit($returnedData);
                        if ($editPassword == true) {
                            $usersSiteAdmin->id = $this->user_id;
                            $this->userData = $usersSiteAdmin->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                            $return['data'] = $this->userData;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10010;
                            $return['error']["message"] = "Service Unavailable";
                        }
                    } else if (($mode == "users") && ($action == "roles")) {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['data'] = $this->publicRole('Site_Admin_Roles'); 
                    } else if (($mode == "users") && ($action == "list")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {
                            $view = "list";

                            $return = $usersSiteAdmin->retrieveAdmin($view, $string);
                        }
                    } else if (($mode == "users") && ($action == "getone")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {
                            $view = "one";
                            $usersSiteAdmin->id = intval($string);
                            $return = $usersSiteAdmin->retrieveAdmin($view, $page);
                        }
                    } else if (($mode == "users") && ($action == "reset")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersSiteAdmin->user_id = $this->user_id;                     
                            $usersSiteAdmin->id = intval($string);
                            $return = $usersSiteAdmin->reset();
                        }
                    } else if (($mode == "users") && ($action == "editadmin")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersSiteAdmin->user_id = $this->user_id;
                            $return = $usersSiteAdmin->editadmin($returnedData);
                        }
                    } else if (($mode == "users") && ($action == "delete")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersSiteAdmin->user_id = $this->user_id;                     
                            $usersSiteAdmin->id = intval($string);
                            $return = $usersSiteAdmin->remove();
                        }
                    } else if (($mode == "users") && ($action == "deactivate")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersSiteAdmin->user_id = $this->user_id;                     
                            $usersSiteAdmin->id = intval($string);
                            $return = $usersSiteAdmin->deactivate();
                        }
                    } else if (($mode == "home") && ($action == "stat")) {
                        $return = $this->stat();
                    } else if (($mode == "category") && ($action == "add")) {
                    } else if (($mode == "category") && ($action == "edit")) {
                    } else if (($mode == "category") && ($action == "get")) {
                        $category->id = intval($string);
                        $return = $category->retrieveAPI();
                    } else if (($mode == "category") && ($action == "delete")) {
                    } else if (($mode == "store") && ($action == "get")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 3) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
                        } else {
                            $store->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $store->id = intval($string);
                            } else if (trim($string) != "") {
                                $view = $string;
                            } else {
                                $view = null;
                            }
                            $return = $store->retrieveAdmin($view, $page);
                        }
                    } else if (($mode == "store") && ($action == "wallet")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 11) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11016;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores wallet";
                        } else {
                            $store->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $store->id = intval($string);
                                $return = $store->retrieveStoreView("wallet", $page);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11014;
                                $return['error']["message"] = "The store is not selected";
                            }
                        }
                    } else if (($mode == "store") && ($action == "users")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 2) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores users";
                        } else {
                            $store->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $store->id = intval($string);
                                $return = $store->retrieveStoreView("users", $page);
                            } else if ($string == "pending") {
                                $return = $store->retrieveStoreView("pending", $page);
                            } else if ($string == "get") {
                                $return = $store->retrieveStoreView("one", $page);
                            } else if ($string == "verify") {

                                if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                                    $return['success'] = false;
                                    $return['error']['code'] = 10014;
                                    $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                                } else {
                                    $usersStoreAdmin->user_id = $page;
                                    $usersStoreAdmin->id = intval($page);
                                    if ($usersStoreAdmin->verifyCourier($returnedData)) {
                                        $return = $store->retrieveStoreView("one", $page);
                                    } else {
                                        $return['success'] = false;
                                        $return['error']['code'] = 11021;
                                        $return['message'] = "An error occured fetching this store user data";
                                    }
                                }
                            } else if ($string == "deactivate") {
                                if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                                    $return['success'] = false;
                                    $return['error']['code'] = 10014;
                                    $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                                } else {       
                                    $usersStoreAdmin->user_id = $page;
                                    $usersStoreAdmin->id = intval($page);
                                    $return = $usersStoreAdmin->deactivate();
                                }
                            } else if ($string == "reset") {
                                if  ($this->getAccessRight($this->userRoles, "role_id", 9) === false) {
                                    $return['success'] = false;
                                    $return['error']['code'] = 10014;
                                    $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                                } else {       
                                    $usersStoreAdmin->admin = true;
                                    $usersStoreAdmin->user_id = $page;
                                    $usersStoreAdmin->id = intval($page);
                                    $return = $usersStoreAdmin->reset();
                                }
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11014;
                                $return['error']["message"] = "The store is not selected";
                            }
                        }
                    } else if (($mode == "store") && ($action == "inventory")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 13) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11017;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores inventory";
                        } else {
                            $store->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $store->id = intval($string);
                                $return = $store->retrieveStoreView("inventory", $page);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11014;
                                $return['error']["message"] = "The store is not selected";
                            }
                        }
                    } else if (($mode == "store") && ($action == "orders")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 8) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11018;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores orders";
                        } else {
                            $store->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $store->id = intval($string);
                                $return = $store->retrieveStoreView("orders", $page);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11014;
                                $return['error']["message"] = "The store is not selected";
                            }
                        }
                    } else if (($mode == "store") && ($action == "edit")) {
                        $store->admin === true;
                        $store->user_id = $this->user_id;
                        $store->id = intval($string);
                        if  ($this->getAccessRight($this->userRoles, "role_id", 3) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
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
                    } else if (($mode == "store") && ($action == "changestatus")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 3) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
                        } else {
                            $store->user_id = $this->user_id;
                            $store->id = intval($string);
                            if ($store->toggleStatus()) {
                                $add = $store->retrieveAPI();
                                $return['success'] = true;
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11007;
                                $return['message'] = "No record found";
                            }
                        }
                    } else if (($mode == "store") && ($action == "verify")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 5) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11013;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage store verification";
                        } else {
                            $store->user_id = $this->user_id;
                            $store->id = intval($string);
                            if ($store->verifyStore($returnedData)) {
                                $add = $store->retrieveAPI();
                                $return['success'] = true;
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 11019;
                                $return['message'] = "An error occured fetching this store data";
                            }
                        }
                    } else if (($mode == "settings") && ($action == "update")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 17) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 16001;
                            $return['error']["message"] = "You are logged in with an account not authorized to update settings";
                        } else {
                        }
                    } else if (($mode == "settings") && ($action == "get")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 17) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 16002;
                            $return['error']["message"] = "You are logged in with an account not authorized to pull settings";
                        } else {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['data'] = $options->apiOptions();
                        }
                    } else if (($mode == "customers") && ($action == "list")) {
                        $usersCustomers->admin = true;
                        if  ($this->getAccessRight($this->userRoles, "role_id", 1) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 17001;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage customers";
                        } else {
                            $view = "list";
                            $return = $usersCustomers->retrieveAdmin($view, $string);
                        }
                    } else if (($mode == "customers") && ($action == "get")) {
                        $usersCustomers->admin = true;
                        if  ($this->getAccessRight($this->userRoles, "role_id", 1) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 17001;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage customers";
                        } else {
                            $view = "one";
                            $usersCustomers->id = intval($string);
                            $return = $usersCustomers->retrieveAdmin($view, $page);
                        }
                    } else if (($mode == "customers") && ($action == "reset")) {
                        $usersCustomers->admin = true;
                        if  ($this->getAccessRight($this->userRoles, "role_id", 1) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 17001;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage customers";
                        } else {       
                            $usersCustomers->user_id = $this->user_id;                     
                            $usersCustomers->id = intval($string);
                            $return = $usersCustomers->reset();
                        }
                    } else if (($mode == "customers") && ($action == "deactivate")) {
                        $usersCustomers->admin = true;
                        if  ($this->getAccessRight($this->userRoles, "role_id", 1) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 17001;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage customers";
                        } else {       
                            $usersCustomers->user_id = $this->user_id;                     
                            $usersCustomers->id = intval($string);
                            $return = $usersCustomers->deactivate();
                        }
                    } else if (($mode == "courier") && ($action == "get")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 6) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
                        } else {
                            $usersCourier->admin = true;
                            if (is_numeric($string)) {
                                $view = "one";
                                $usersCourier->id = intval($string);
                            } else {
                                $view = $string;
                            }
                            $return = $usersCourier->retrieveAdmin($view, $page);
                        }
                    } else if (($mode == "courier") && ($action == "list")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 6) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
                        } else {
                            $usersCourier->admin = true;
                            $view = "list";
                            $return = $usersCourier->retrieveAdmin($view, $string);
                        }
                    } else if (($mode == "courier") && ($action == "reset")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 6) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersCourier->user_id = $this->user_id;                     
                            $usersCourier->id = intval($string);
                            $return = $usersCourier->reset();
                        }
                    } else if (($mode == "courier") && ($action == "deactivate")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 6) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 10014;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage users";
                        } else {       
                            $usersCourier->user_id = $this->user_id;                     
                            $usersCourier->id = intval($string);
                            $return = $usersCourier->deactivate();
                        }
                    } else if (($mode == "courier") && ($action == "verify")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 7) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11020;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier verification";
                        } else {
                            $usersCourier->user_id = $this->user_id;
                            $usersCourier->id = intval($string);
                            if ($usersCourier->verifyCourier($returnedData)) {
                                $add = $usersCourier->retrieveAdmin("one");
                                $return['success'] = true;
                                $return['data'] = $add;
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 18002;
                                $return['message'] = "An error occured fetching this courier data";
                            }
                        }
                    } else if (($mode == "courier") && ($action == "wallet")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 12) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 18003;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier wallet";
                        } else {
                            $usersCourier->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $usersCourier->id = intval($string);
                                $return = $usersCourier->retrieveCourierView("wallet", $page);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 18005;
                                $return['error']["message"] = "The courier is not selected";
                            }
                        }
                    } else if (($mode == "courier") && ($action == "orders")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 8) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 18004;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier orders";
                        } else {
                            $usersCourier->admin = true;
                            if (intval($string) > 0) {
                                $view = "one";
                                $usersCourier->id = intval($string);
                                $return = $usersCourier->retrieveCourierView("orders", $page);
                            } else {
                                $return['success'] = false;
                                $return['error']['code'] = 18005;
                                $return['error']["message"] = "The courier is not selected";
                            }
                        }
                    } else if (($mode == "orders") && ($action == "get")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 8) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11020;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier verification";
                        } else {
                            $orders->admin = true;
                            $orders->user_id = $this->userData['ref'];
                            $orders->location = $location;
                            if (intval($string) > 0) {
                                $orders->id = intval($string);
                                $string = "one";
                            }
                            $return = $orders->getAllStoreOrdersAdmin($string, $page);
                        }
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
                    } else if (($mode == "orders") && ($action == "location")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 8) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11020;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier verification";
                        } else {
                            $orders->admin = true;
                            $orders->item_id = intval($string);
                            $get = $orders->getLocation();
                            if ($get) {
                                $return['success'] = true;
                                $return['data'] = $get;
                            } else {
                                $return = false;
                                $return['error']['code'] = 13024;
                                $return['error']["message"] = "An error occured while retrieveing the location for this item";
                            }
                        }
                    } else if (($mode == "orders") && ($action == "locations")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 8) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11020;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage courier verification";
                        } else {
                            $orders->admin = true;
                            $orders->id = intval($string);
                            $get = $orders->getLocations();
                            if ($get) {
                                $return['success'] = true;
                                $return['data'] = $get;
                            } else {
                                $return = false;
                                $return['error']['code'] = 13023;
                                $return['error']["message"] = "An error occured while retrieveing locations for this order";
                            }
                        }
                    } else if (($mode == "items") && (($action == "get") || ($action == "list"))) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 3) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 11011;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage stores";
                        } else {
                            $inventory->admin = true;
                            $store->admin = true;
                            $inventory->string = $string;
                            $return = $inventory->retrieveAPI( $action, $page );
                        }
                    } else if (($mode == "wallet") && (($action == "get") || ($action == "list"))) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 10) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 18006;
                            $return['error']["message"] = "You are logged in with an account not authorized to manage all wallet";
                        } else {
                            $wallet->admin = true;
                            if ((intval($string) > 0) && ($action == "get")) {
                                $wallet->id = intval($string);
                                $wallet->pageView = "one";
                            } else {
                                $wallet->pageView = "list";
                            }
                            $return = $wallet->getAllView($page);
                        }
                        
                    } else if (($mode == "wallet") && ($action == "request")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 14) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 19001;
                            $return['error']["message"] = "You are logged in with an account not authorized to Manage Payment Requests";
                        } else {
                            if ($string == "get") {
                                $wallet->user_id = $this->user_id;
                                $return = $wallet->getallWithdrawalRequest();
                            } else {
                                $wallet->user_id = $this->user_id;
                                $return = $wallet->allWithdrawalRequest();
                            }
                        }
                    } else if (($mode == "wallet") && ($action == "advice")) {
                        if  ($this->getAccessRight($this->userRoles, "role_id", 15) === false) {
                            $return['success'] = false;
                            $return['error']['code'] = 19002;
                            $return['error']["message"] = "You are logged in with an account not authorized to Approve Payment Advices";
                        } else {
                            if ($string == "get") {
                                $wallet->user_id = $this->user_id;
                                $return = $wallet->getPreviousList(intval($page));
                            } else {
                                $wallet->user_id = $this->user_id;
                                $return = $wallet->generatePaymentAdvice();
                            }
                        }
                    } else if (($mode == "tickets") && ($action == "add")) {
                        $tickets->admin_id = $this->userData['ref'];
                        $returnedData['last_update_by'] = 'admin'; 
                        $add = $tickets->add($returnedData);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 15000;
                            $return['error']['message'] = "An error occured while adding this ticket";
                        }
                    } else if (($mode == "tickets") && ($action == "remove")) {
                        $tickets->id = intval($string);
                        $tickets->user_id = $this->userData['ref'];
                        $add = $tickets->cancelAdmin("remove");
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
                        $add = $tickets->cancelAdmin("delete");
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
                        $add = $tickets->closeUser();
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
                        $return = $tickets->getAmin($string);
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
            global $usersSiteAdmin;
            $refresh = $usersSiteAdmin->listOne($this->user_id);
            if (($refresh['token_refresh'] < time()) || ($refresh['token'] == NULL)) {
                $this->token = $this->user_id.$this->createRandomPassword(15);

                $usersSiteAdmin->editOne("token", $this->token, $this->user_id, "ref");
                $usersSiteAdmin->editOne("token_refresh", time()+(60*60*24*180), $this->user_id, "ref");
            } else {
                $this->token = $refresh['token'];
            }
            return $this->token;
        }

        private function refresh () {
            global $usersSiteAdmin;
            return $usersSiteAdmin->listOneValue($this->user_id, "token");
        }

		private function authenticate($header) {
            global $usersSiteAdmin;
            global $userSiteRoles;
            if ($header['auth'] != "") {
                $split = explode("_", base64_decode($header['auth']));
                $token = $split[1];
                if ($header['key'] == $split[0]) {
                    if ($this->checkExixst("usersSiteAdmin", "token", $token) == 1) {


                        $this->userData = $usersSiteAdmin->getDetails($token, "token");
                        
                        $userSiteRoles->user_id = $this->userData['ref'];
                        if (($this->userData['status'] == "ACTIVE") || ($this->userData['status'] == "CHANGE_PASSWORD") || ($this->exempt)) {
                            $this->userRoles = $userSiteRoles->getRoles();
                            $_SESSION['site']['user_id'] = $this->userData['ref'];
                            $this->token = $this->userData['token'];
                            unset($this->userData['password']);
                            unset($this->userData['token']);


                            $_SESSION['storeAdmin']['user_id'] = $this->userData['ref'];
                            $this->token = $this->userData['token'];
                            unset($this->userData['password']);
                            unset($this->userData['token']);

                            return true;
                        } else {
                            return false;
                        }

                        // $this->userData = $usersSiteAdmin->getDetails($token, "token");
                        // $_SESSION['site']['user_id'] = $this->userData['ref'];
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
                $array[] = "users:adduser";
                $array[] = "upload:item";
                $array[] = "upload:items";
                $array[] = "upload:users";
                $array[] = "uploadraw:item";
                $array[] = "uploadraw:items";
                $array[] = "uploadraw:users";
                $array[] = "tickets:add";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "GET") {
                $array[] = "users:get";
                $array[] = "users:list";
                $array[] = "users:getone";
                $array[] = "users:profile";
                $array[] = "users:logout";
                $array[] = "users:otp";
                $array[] = "users:authotp";
                $array[] = "users:recovery";
                $array[] = "users:roles";
                $array[] = "home:stat";
                $array[] = "courier:list";
                $array[] = "courier:get";
                $array[] = "store:get";
                $array[] = "store:users";
                $array[] = "store:inventory";
                $array[] = "store:wallet";
                $array[] = "courier:wallet";
                $array[] = "store:orders";
                $array[] = "courier:orders";
                $array[] = "customers:get";
                $array[] = "customers:list";
                $array[] = "settings:get";
                $array[] = "orders:get";
                $array[] = "items:get";
                $array[] = "items:list";
                $array[] = "orders:location";
                $array[] = "orders:locations";
                $array[] = "wallet:get";
                $array[] = "wallet:request";
                $array[] = "wallet:advice";
                $array[] = "wallet:list";
                $array[] = "tickets:get";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "PUT") {
                $array[] = "users:edit";
                $array[] = "users:editadmin";
                $array[] = "users:password";
                $array[] = "users:passwordreset";
                $array[] = "users:resetpassword";
                $array[] = "users:activate";
                $array[] = "users:deactivate";
                $array[] = "users:reset";
                $array[] = "courier:deactivate";
                $array[] = "courier:reset";
                $array[] = "courier:verify";
                $array[] = "store:changestatus";
                $array[] = "store:verify";
                $array[] = "store:users";
                $array[] = "store:edit";
                $array[] = "settings:update";
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