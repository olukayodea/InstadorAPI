<?php
    class apiCustomer extends api {
        public function prepFiles($header, $requestLink, $data, $files) {
            global $media;
            $requestData = explode("/", trim($requestLink, "/"));
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);

            if ($this->methodCheck($header['method'], "upload:".$mode)) {
                if ($this->authenticate($header)) {
                    $raw = false;
                    $media->class = "customer";
                    if ($mode == 'item') {
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
                            $rawData[] = $returnedData['image'];
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
            global $usersCustomers;
            global $options;
            global $tax;
            global $category;
            global $cards;
            global $store;
            global $inventory;
            global $orders;
            global $cart;
            global $tickets;
            
            $requestData = explode("/", $requestLink);
            $mode = @strtolower($requestData[0]);
			$action = @strtolower($requestData[1]);
            $string = @strtolower($requestData[2]);
            $page = @strtolower($requestData[3]);
            $extra = @strtolower($requestData[4]);

            $location['longitude'] = $header['longitude'];
            $location['latitude'] = $header['latitude'];
            $location['address'] = $header['address'];

            $returnedData = json_decode($data, true);

            if ($mode == "users" && ($action == "authotp" || $action == "activate")) {
                $this->exempt = true;
            }
            $store->user = true;
            $orders->user = true;

            if (( $header['longitude'] == "" ) || ( $header['latitude'] == "" ) ) {
                $return['success'] = false;
                $return['error']['code'] = 10030;
                $return['error']["message"] = "Bad Request. Compulsory headers are missing";
            } else if ($this->methodCheck($header['method'], $mode.":".$action)) {
                if (($mode == "users") && ($action == "sso")) {
                    $login = $usersCustomers->sso($returnedData);
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
                        $this->user_id = $usersCustomers->id;
                        $this->userData = $usersCustomers->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] =  $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "login")) {
                    $login = $usersCustomers->login($returnedData);
                    if ($login == 0) {
                        $return['success'] = false;
                        $return['error']['code'] = 10001;
                        $return['error']["message"] = "Unauthorized. NO users with the email and password combination was found";
                    } else if ($login == 1) {
                        $return['success'] = false;
                        $return['error']['code'] = 10002;
                        $return['error']["message"] = "Unauthorized. This account has not been confirmed yet, please click on the confirmation link in the email sent";
                        $this->user_id = $usersCustomers->id;
                        $return['token'] = $this->getToken();
                    } else if ($login == 3) {
                        $return['success'] = false;
                        $return['error']['code'] = 10003;
                        $return['error']["message"] =  "Not Acceptable. This account has been deactivated. PLease contact us at contactus@Instadoor.ca";
                    } else {
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $this->user_id = $usersCustomers->id;
                        $this->userData = $usersCustomers->getUser();

                        $return['token'] = $this->getToken();
                        $return['data'] = $this->userData;
                        $return['data']['token'] = $return['token'];
                    }
                } else if (($mode == "users") && ($action == "otp")) {
                    $usersCustomers->email = $string;
                    $otp = $usersCustomers->resendOtp();
                    $this->user_id = $usersCustomers->id;
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
                    $register = $usersCustomers->add($returnedData);
                    if ($register) {
                        $return['success'] = true;
                        $return['results'] = "OK";

                        $this->user_id = $usersCustomers->id;
                        $this->userData = $usersCustomers->getUser();

                        $return['token'] = $this->getToken();
                        $return['OTP'] = $usersCustomers->otp;
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
                        $change = $usersCustomers->passwordReset($raw[2], $returnedData['password'], $returnedData['otp']);
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
                    $usersCustomers->email = $string;
                    $add = $usersCustomers->passwordConfirm();
                    $userData = $usersCustomers->listOne($add);
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
                } else if (($mode == "store") && (($action == "search") || ($action == "get") || ($action == "list"))) {
                    $store->user_id = $this->user_id['ref'];
                    $store->location = $location;
                    if ($action == "list") {
                        $page = intval($string);
                    } else if ($action == "search") {
                        $store->search = $string;
                    } else {
                        $store->id = intval($string);
                    }
                    $return = $store->getAPI( $page );
                } else if (($mode == "items") && ($action == "category")) {
                    $return = $category->retrieveAPI();
                } else if (($mode == "items") && ($action == "category")) {
                    $return = $category->retrieveAPI();
                } else if (($mode == "items") && ($action == "search")) {
                    $inventory->string = $string;
                    $return = $inventory->retrieveAPI( $action );
                } else if (($mode == "items") && ($action == "store")) {
                    $inventory->store_id = intval($string);
                    $inventory->category_id = intval($_REQUEST['cat']);
                    $return = $inventory->retrieveStoreAPI( intval($page) );
                } else if (($mode == "items") && ($action == "get")) {
                    $inventory->string = $string;
                    $return = $inventory->retrieveAPI( $action );
                } else if (($mode == "search") && ($action == "query")) {
                    $return = $this->search($string, $location);
                } else if (($mode == "cart") && ($action == "getdata")) {
                    $return = $cart->getData($returnedData);
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
                        $usersCustomers->id = $this->user_id;
                        $resetpassword = $usersCustomers->logout();
                        $return['success'] = true;
                        $return['results'] = "OK";
                    } else if (($mode == "users") && ($action == "authotp")) {
                        $usersCustomers->email = $this->userData['email'];
                        $otp = $usersCustomers->resendOtp();
                        $this->user_id = $usersCustomers->id;
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
                        $usersCustomers->id = $this->user_id;
                        $usersCustomers->otp = $returnedData['otp'];
                        
                        $resetpassword = $usersCustomers->activateAccount();

                        if ($resetpassword === true) {
                            $this->userData = $usersCustomers->getUser();
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
                        
                        $resetpassword = $usersCustomers->changePassword($returnedData);

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
                        $usersCustomers->id = $this->user_id;
                        $editPassword = $usersCustomers->editPassword($returnedData);
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
                        $usersCustomers->id = $this->user_id;
                        $this->userData = $usersCustomers->getUser();
                        $return['success'] = true;
                        $return['results'] = "OK";
                        $return['token'] = $this->refresh();
                        $return['data'] =  $this->userData;
                    } else if (($mode == "users") && ($action == "edit")) {
                        $usersCustomers->id = $this->user_id;
                        $editPassword = $usersCustomers->edit($returnedData);
                        if ($editPassword == true) {
                            $usersCustomers->id = $this->user_id;
                            $this->userData = $usersCustomers->getUser();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['token'] = $this->refresh();
                            $return['data'] =  $this->userData;
                            
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 10010;
                            $return['error']["message"] = "Service Unavailable";
                        }
                    } else if (($mode == "cart") && (($action == "add") || ($action == "replace") || ($action == "sync"))) {
                        $replace = false;
                        $sync = false;
                        if ($action == "replace") {
                            $replace = true;
                        } else if ($action == "sync") {
                            $sync = true;
                        }
                        $cart->user_id = $this->userData['ref'];
                        $add = $cart->add($returnedData, $replace, $sync);
                        if ($add) {
                            $cartData = $cart->getCart();
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['cartCount'] = count($cartData);
                            $return['cartTotal'] = $cart->cartTotal();
                            $return['cartItem'] = $cartData;;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14000;
                            $return['error']['message'] = "An error occured while adding this item to cart";
                        }
                    } else if (($mode == "cart") && ($action == "edit")) {
                        $cart->user_id = $this->userData['ref'];
                        $Result = $cart->edit($returnedData);
                        if ($Result) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $add = $cart->getCart();
                            $return['cartCount'] = count($add);
                            $return['cartTotal'] = $cart->cartTotal();
                            $return['cartItem'] = $Result;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14001;
                            $return['error']['message'] = "An error occured while editting this item in cart";
                        }
                    } else if (($mode == "cart") && ($action == "get")) {
                        $cart->user_id = $this->userData['ref'];
                        $add = $cart->getCart();
                        $return['success'] = true;
                        $return['cartCount'] = count($add);
                        $return['cartTotal'] = $cart->cartTotal();
                        $return['cartItem'] = $add;
                    } else if (($mode == "cart") && ($action == "remove")) {
                        $cart->user_id = $this->userData['ref'];
                        $cart->id = intval($string);
                        $rem = $cart->remove();
                        if ($rem) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $add = $cart->getCart();
                            $return['cartCount'] = count($add);
                            $return['cartTotal'] = $cart->cartTotal();
                            $return['cartItem'] = $add;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14002;
                            $return['error']['message'] = "An error occured while removing this item from cart";
                        }
                    } else if (($mode == "cart") && ($action == "clear")) {
                        $cart->user_id = $this->userData['ref'];
                        $add = $cart->clear();
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14003;
                            $return['error']['message'] = "An error occured while clearing your cart";
                        }
                    } else if (($mode == "cards") && ($action == "get") && ($string == "default")) {
                        //get one row
                        $cards->user_id = $this->userData['ref'];;
                        $return = $cards->apiGetList("default");
                    } else if (($mode == "cards") && ($action == "get")) {
                        //get one row
                        $cards->user_id = $this->userData['ref'];;
                        $return = $cards->apiGetList("list", false, $page);
                    } else if (($mode == "cards") && ($action == "add")) {
                        $cards->user_id = $this->userData['ref'];;
                        $cards->card_user_token = $this->getToken();
                        $add = $cards->add($returnedData);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "Completed";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14000;
                            $return['error']["message"] = "An error occured while creating this payment card";
                        }
                    } else if (($mode == "cards") && ($action == "makedefault")) {
                        $cards->user_id = $this->userData['ref'];
                        $add = $cards->setDefault($returnedData['ref']);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "Accepted";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14001;
                            $return['error']["message"] = "An error occured while making this payment card default";
                        }
                    } else if (($mode == "cards") && ($action == "delete")) {
                        $cards->user_id = $this->userData['ref'];
                        $cards->id = $string;
                        $add = $cards->remove(true);
                        if ($add === false ) {
                            $return['success'] = false;
                            $return['error']['code'] = 14003;
                            $return['error']["message"] = "An error occured while deleting this payment card, you cannot delete a default card";
                        } else {
                            if ($add === "0000") {
                                $return['success'] = false;
                                $return['error']['code'] = 14005;
                                $return['error']["message"] = "You are not authorized to perform this action";
                            } else {
                                $return['success'] = true;
                                $return['results'] = "Ok";
                            }
                        }
                    } else if (($mode == "cards") && ($action == "changestatus")) {
                        $cards->user_id = $this->userData['ref'];
                        $add = $cards->toggleStatus($returnedData['ref']);
                        if ($add) {
                            $return['success'] = true;
                            $return['results'] = "Ok";
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 14004;
                            $return['error']["message"] = "An error occured while changing this payment card status";
                        }
                    } else if (($mode == "orders") && ($action == "checkout")) {
                        $cart->user_id = $orders->user_id = $this->userData['ref'];
                        $orders->location = $location;
                        $return = $orders->add();
                    } else if (($mode == "orders") && ($action == "paymentupdate")) {
                        $orders->user_id = $this->userData['ref'];
                        $return = $orders->paymentUpdate($returnedData['data']);
                    } else if (($mode == "orders") && ($action == "process")) {
                        $orders->user_id = $this->userData['ref'];
                        $add = $orders->processOrder($returnedData);
                        if ($add === null) {
                            $return['success'] = false;
                            $return['error']['code'] = 13018;
                            $return['error']['message'] = "No payment autorization token present, please create one first";
                        } else if ($add) {
                            $return['success'] = true;
                            $return['results'] = "OK";
                            $return['order'] = $add;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 13001;
                            $return['error']['message'] = "An error occured while processing this order";
                        }
                    } else if (($mode == "orders") && ($action == "get")) {
                        $orders->user_id = $this->userData['ref'];
                        if (intval($string) > 0) {
                            $orders->id = intval($string);
                            $string = "one";
                        }

                        $return = $orders->retrieveAPI($string, $page);
                    } else if (($mode == "orders") && ($action == "flag")) {
                        $orders->user_id = $this->userData['ref'];
                        $add = $orders->flagOrder($returnedData);
                        if ($add) {
                            $return['success'] = true;
                            $return = $add;
                        } else {
                            $return['success'] = false;
                            $return['error']['code'] = 13015;
                            $return['error']['message'] = "An error occured while flagging this order";
                        }
                    } else if (($mode == "tickets") && ($action == "add")) {
                        $tickets->user_id = $this->userData['ref'];
                        $returnedData['last_update_by'] = 'user'; 
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
                        $returnedData['last_update_by'] = 'user'; 
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
                        $add = $tickets->cancelUser("remove");
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
                        $add = $tickets->cancelUser("delete");
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
                        $return = $tickets->getUser($string);
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
            global $usersCustomers;
            $refresh = $usersCustomers->listOne($this->user_id);
            //print_r($refresh);
            if (($refresh['token_refresh'] < time()) || ($refresh['token'] == NULL)) {
                $this->token = $this->user_id.$this->createRandomPassword(15);

                $usersCustomers->editOne("token", $this->token, $this->user_id, "ref");
                $usersCustomers->editOne("token_refresh", time()+(60*60*24*180), $this->user_id, "ref");
            } else {
                $this->token = $refresh['token'];
            }
            return $this->token;
        }

        private function refresh () {
            global $usersCustomers;
            return $usersCustomers->listOneValue($this->user_id, "token");
        }

		private function authenticate($header) {
            global $usersCustomers;
            if ($header['auth'] != "") {
                $split = explode("_", base64_decode($header['auth']));
                $token = $split[1];
                if ($header['key'] == $split[0]) {
                    if ($this->checkExixst("usersCustomers", "token", $token) == 1) {
                        $this->userData = $usersCustomers->getDetails($token, "token");
                        if (($this->userData['status'] == "ACTIVE") || ($this->userData['status'] == "CHANGE_PASSWORD") || ($this->exempt)) {
                            $_SESSION['users']['user_id'] = $this->userData['ref'];
                            $this->token = $this->userData['token'];
                            unset($this->userData['password']);
                            unset($this->userData['token']);
                            return true;
                        } else {
                            return false;
                        }

                        
                        // global $usersCustomers;

                        // $this->userData = $usersCustomers->getDetails($token, "token");
                        // $_SESSION['customer']['user_id'] = $this->userData['ref'];
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
                $array[] = "uploadraw:item";
                $array[] = "uploadraw:items";
                $array[] = "uploadraw:users";
                $array[] = "cart:add";
                $array[] = "cart:replace";
                $array[] = "cart:sync";
                $array[] = "cart:getdata";
                $array[] = "cards:add";
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
                $array[] = "store:get";
                $array[] = "store:list";
                $array[] = "store:search";
                $array[] = "items:category";
                $array[] = "items:search";
                $array[] = "items:get";
                $array[] = "items:store";
                $array[] = "orders:get";
                $array[] = "orders:checkout";
                $array[] = "cart:get";
                $array[] = "cards:get";
                $array[] = "search:query";
                $array[] = "tickets:get";
                $array[] = "main:";
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
                $array[] = "orders:process";
                $array[] = "cart:edit";
                $array[] = "cards:makedefault";
                $array[] = "cards:changestatus";
                $array[] = "orders:flag";
                $array[] = "orders:paymentupdate";
                $array[] = "tickets:edit";
                $array[] = "tickets:close";
                if (array_search($type, $array) === false) {
                    return false;
                } else {
                    return true;
                }
            } else if ($method == "DELETE") {
                $array[] = "users:delete";
                $array[] = "cart:remove";
                $array[] = "cart:clear";
                $array[] = "cards:delete";
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