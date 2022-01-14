<?php
echo "<pre>";
$product_key = rand();

$u = "http://127.0.0.1/InstaDoorApi/1.0/";
$token = "1G46IKLEHHWE63D6P";
// $u = "http://api.Instadoor.ca/1.0/";
// // $token = "3L8K9WBBGFF3K2HML";
// $token = "15I6Z07KP6UKX35LV";

$latitude = '45.400744';
$longitude = '-75.75198';
$ip = "76.66.86.153";

$gateway_passcode = base64_encode($product_key."_".$token);

$headerArray = array("gateway_passcode"=>$gateway_passcode, "product_key"=>$product_key );
//common factors
$header[] = "Content-Type: application/json";
$header[] = "Authorization: Bearer ".$gateway_passcode;
$header[] ='key: '.$product_key;
$header[] ='longitude: '.$longitude;
$header[] ='latitude: '.$latitude;
$header[] ='lang: en';

/**
 * Customers
 */
// // create user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $array['email'] = 'olukayode.adebiyi@hotmail.co.uk';
// $array['password'] = 'kayode';
// $url = $u."customer/users/register";
// echo post($header, $url, json_encode( $array ) );

// //  Resend OTP
// $url = $u."customer/users/otp/".urlencode("olukayode.adebiyi@hotmail.co.uk");
// echo get($header, $url );

// // activate user
// $array['otp'] = 476778;
// $url = $u."customer/users/activate";
// echo put($header, $url, json_encode( $array ) );

// // login user
// $array['email'] = 'olukayode.adebiyi@hotmail.co.uk';
// $array['password'] = 'lolade';
// $url = $u."customer/users/login";
// echo post($header, $url, json_encode( $array ) );

// // change password
// $array['old_password'] = 'kayode';
// $array['new_password'] = 'kayode';
// $url = $u."customer/users/password";
// echo put($header, $url, json_encode( $array ) );

// // // get profile
// $url = $u."customer/users/get";
// echo get($header, $url );

// // edit user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $url = $u."customer/users/edit";
// echo put($header, $url, json_encode( $array ) );

// //  logout
// $url = $u."customer/users/logout";
// echo get($header, $url );

// // request password
// $url = $u."customer/users/recovery/".urlencode("olukayode.adebiyi@hotmail.co.uk");
// echo get($header, $url );

// // Reset Password
// $array['otp'] = '279442';
// $array['password'] = 'kayode';
// $array['resetToken'] = 'ODIxNjc1NTQzXzE1OTIyNzI3Mjlfb2x1a2F5b2RlLmFkZWJpeWlAaG90bWFpbC5jby51aw==';
// $url = $u."customer/users/passwordReset";
// echo put($header, $url, json_encode( $array ) );

// // Store
// get all store
$url = $u."customer/store/list";
echo get($header, $url );

// // search
// $url = $u."customer/store/search/demo";
// echo get($header, $url );

// // get one store
// $url = $u."customer/store/get/3";
// echo get($header, $url );

// // items
// // get all items
// $url = $u."customer/items/store/3?cat=2";
// echo get($header, $url );

// // search
// $url = $u."customer/items/search/demo";
// echo get($header, $url );

// get one items
// $url = $u."customer/items/get/1";
// echo get($header, $url );

// // Orders
// // Create
// $url = $u."customer/orders/checkout";
// echo get($header, $url );

// // process order
// $array['tip'] = "10";
// $array['notes'] = "i will be home in 3 hours";
// $array['payment_data'] = "fifufufufufuf";
// $array['delivery_time'] = 0;
// $url = $u."customer/orders/process";
// echo put($header, $url, json_encode( $array ) );

// // // update payment order
// $array['order_id'] = 1;
// $array['data'] = "fkhvbdfu67v8uhv7dhjivhgyyfs67";

// $url = $u."customer/orders/paymentUpdate";
// echo put($header, $url, json_encode( $array ) );

// // get order one item
// $url = $u."customer/orders/get/all/1";
// echo get($header, $url );

// // get order one item
// $url = $u."customer/orders/get/2";
// echo get($header, $url );

// // get order recent
// $url = $u."customer/orders/get/recent";
// echo get($header, $url );

// // get order processing item
// $url = $u."customer/orders/get/processing";
// echo get($header, $url );

// // get order pickupReady item
// $url = $u."customer/orders/get/pickupReady";
// echo get($header, $url );

// // get order deliveryOngoing item
// $url = $u."customer/orders/get/deliveryOngoing";
// echo get($header, $url );

// // get order delivered item
// $url = $u."customer/orders/get/delivered";
// echo get($header, $url );

// // get order completed item
// $url = $u."customer/orders/get/completed";
// echo get($header, $url );

// // get order flagged item
// $url = $u."customer/orders/get/flagged";
// echo get($header, $url );

// // get order cancelled item
// $url = $u."customer/orders/get/cancelled";
// echo get($header, $url );

// // get order paymentPending item
// $url = $u."customer/orders/get/paymentPending";
// echo get($header, $url );

// // get order paymentFailed item
// $url = $u."customer/orders/get/paymentFailed";
// echo get($header, $url );

// // get order paymentCompleted item
// $url = $u."customer/orders/get/paymentCompleted";
// echo get($header, $url );

// // // replace Cart
// $array[0]['item_id'] = rand(1,4);
// $array[0]['quantity'] = rand(1,20);
// $array[1]['item_id'] = rand(1,4);
// $array[1]['quantity'] = rand(1,20);
// $array[2]['item_id'] = rand(1,4);
// $array[2]['quantity'] = rand(1,20);
// $url = $u."customer/cart/sync";
// echo post($header, $url, json_encode( $array ) );

// // add to cart
// $array['item_id'] = rand(1,4);
// $array['quantity'] = rand(1,20);
// // $array['data'] = array("Capsule:40", "Color:Green");
// $url = $u."customer/cart/add";
// echo post($header, $url, json_encode( $array ) );

// // edit cart item
// $array['cart_id'] = 3;
// $array['quantity'] = rand(1,20);
// $url = $u."customer/cart/edit";
// echo put($header, $url, json_encode( $array ) );

// // remove cart item
// $url = $u."customer/cart/remove/1";
// echo delete($header, $url, json_encode( $array ) );

// // clear cart
// $url = $u."customer/cart/clear";
// echo delete($header, $url, json_encode( $array ) );

// // get cart
// $url = $u."customer/cart/get";
// echo get($header, $url ) );

// // get cart data
// $array[] = array("item_id"=>1, "quantity"=>2);
// $array[] = array("item_id"=>4, "quantity"=>7);
// $array[] = array("item_id"=>2, "quantity"=>20);
// $url = $u."customer/cart/getData";
// echo post($header, $url, json_encode( $array ) );

// // general search
// $url = $u."customer/search/query/e";
// echo get($header, $url, json_encode( $array ) );

// // Cards
// // Add card
// $array['gateway_token'] = 'c08-3bd46210-38d4-4245-8bd9-6490c0144d37';
// $array['brand'] = 'visa';
// $array['pan'] = '1111';
// $array['expiry_month'] = '12';
// $array['expiry_year'] = '20';
// $url = $u."customer/cards/add";
// echo post($header, $url, json_encode( $array ) );

// // make default
// $array['ref'] = 3;
// $url = $u."customer/cards/makeDefault";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// // remove
// $url = $u."customer/cards/delete/4";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// //change status
// $array['ref'] = 1;
// $url = $u."customer/cards/changeStatus";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// // get all carda
// $url = $u."customer/cards/get";
// echo get($header, $url );

// // get default carda
// $url = $u."customer/cards/get/default";
// echo get($header, $url );

// Ticket
// Add ticket
// $array['title'] = 'random ticket';
// $array['order_id'] = 272;
// $array['message'] = 'this is the message of the ticket, we will continue here later';
// $array['ticket_id'] = NULL;
// $url = $u."customer/tickets/add";
// echo post($header, $url, json_encode( $array ) );

// Edit ticket
// $array['title'] = 'random ticket';
// $array['order_id'] = 272;
// $array['message'] = 'this is the message of the ticket that was edited, we will continue here later';
// $array['ref'] = 2;
// $url = $u."customer/tickets/edit";
// echo put($header, $url, json_encode( $array ) );

// remove
// $url = $u."customer/tickets/delete/3";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // remove
// $url = $u."customer/tickets/remove/1";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // get all ticket
// $url = $u."customer/tickets/get/all";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get new ticket
// $url = $u."customer/tickets/get/new";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."customer/tickets/get/open";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."customer/tickets/get/closed";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get one ticket
// $url = $u."customer/tickets/get/6";
// $json_data = json_encode($array);
// echo get($header, $url);

/**
 * Courier
 */
// // create user
// $array['fname'] = 'kayode'.rand(1,99);
// $array['lname'] = 'adebiyi'.rand(1,99);
// $array['email'] = 'olukayode.adebiyi'.rand(1,99).'@hotmail.co';
// $array['password'] = 'kayode';
// $url = $u."courier/users/register";
// echo post($header, $url, json_encode( $array ) );

// // activate user
// $array['otp'] = 476778;
// $url = $u."courier/users/activate";
// echo put($header, $url, json_encode( $array ) );

// // // resend OTP
// $url = $u."courier/users/authotp";
// echo get($header, $url );

// // login user
// $array['email'] = 'olukayode.adebiyi@hotmail.co.uk';
// $array['password'] = 'kayode';
// $url = $u."courier/users/login";
// echo post($header, $url, json_encode( $array ) );

// // change password
// $array['old_password'] = 'kayode';
// $array['new_password'] = 'kayode';
// $url = $u."courier/users/password";
// echo put($header, $url, json_encode( $array ) );

// // get profile
// $url = $u."courier/users/get";
// echo get($header, $url );

// // get balance
// $url = $u."courier/users/balance";
// echo get($header, $url );

// // get balance
// $url = $u."courier/users/wallet/current";
// echo get($header, $url );

// // request withdrawal
// $url = $u."courier/users/requestFunds";
// echo post($header, $url, json_encode( $array ) );

// // edit user
// $array['transitNumber'] = '12345';
// $array['institutionNunmber'] = '004';
// $array['accountNumber'] = '12345678';
// $url = $u."courier/users/accountDetails";
// echo put($header, $url, json_encode( $array ) );

// // edit user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $url = $u."courier/users/edit";
// echo put($header, $url, json_encode( $array ) );

// //  logout
// $url = $u."courier/users/logout";
// echo get($header, $url );

// // request password
// $url = $u."courier/users/recovery/".urlencode("olukayode.adebiyi@hotmail.co.uk");
// echo get($header, $url );

// Reset Password
// $array['otp'] = '987822';
// $array['password'] = 'kayode';
// $array['resetToken'] = 'MTM0MzM2MDY3Ml8xNTkyMjczMDcwX29sdWtheW9kZS5hZGViaXlpQGhvdG1haWwuY28udWs=';
// $url = $u."courier/users/passwordReset";
// echo put($header, $url, json_encode( $array ) );

// // Orders
// // get new
// $url = $u."courier/orders/get/new";
// echo get($header, $url );

// // // Dump Location
// $array[0]['item_id'] = 1362;
// $array[0]['longitude'] = -75.75192;
// $array[0]['latitude'] = 45.400720;	
// $array[1]['item_id'] = 1363;
// $array[1]['longitude'] = -75.75192;
// $array[1]['latitude'] = 45.400720;	
// $array[2]['item_id'] = 1364;
// $array[2]['longitude'] = -75.75192;
// $array[2]['latitude'] = 45.400720;	
// $array[3]['item_id'] = 1365;
// $array[3]['longitude'] = -75.75192;
// $array[3]['latitude'] = 45.400720;

// $url = $u."courier/orders/location";
// echo post($header, $url, json_encode( $array ) );

// // Accept Order
// $array['response'] = 'yes';
// $url = $u."courier/orders/respond";
// echo put($header, $url, json_encode( $array ) );

// // // Reject Order
// $array['response'] = 'no';
// $url = $u."courier/orders/respond";
// echo put($header, $url, json_encode( $array ) );

// // Deliver Order
// $array['ref'] = 2;
// $url = $u."courier/orders/deliver";
// echo put($header, $url, json_encode( $array ) );

// // get order one item
// $url = $u."courier/orders/get/ongoing";
// echo get($header, $url );

// // get order one item
// $url = $u."courier/orders/get/458";
// echo get($header, $url );

// file upload
// // upload ID
// $array['image'][] = base64_encode(file_get_contents("prog_320x480.jpg"));
// $url = $u."courier/uploadRaw/users/documents";
// $json_data = json_encode($array);
// echo post($header, $url, $json_data);

// Ticket
// // Add ticket
// $array['title'] = 'random ticket';
// $array['order_id'] = 272;
// $array['message'] = 'this is the message of the ticket, we will continue here later';
// $array['ticket_id'] = NULL;
// $url = $u."courier/tickets/add";
// echo post($header, $url, json_encode( $array ) );

// Edit ticket
// $array['title'] = 'random ticket';
// $array['order_id'] = 272;
// $array['message'] = 'this is the message of the ticket that was edited, we will continue here later';
// $array['ref'] = 2;
// $url = $u."courier/tickets/edit";
// echo put($header, $url, json_encode( $array ) );

// remove
// $url = $u."courier/tickets/delete/3";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // remove
// $url = $u."courier/tickets/remove/1";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // get all ticket
// $url = $u."courier/tickets/get/all";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get new ticket
// $url = $u."courier/tickets/get/new";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."courier/tickets/get/open";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."courier/tickets/get/closed";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get one ticket
// $url = $u."courier/tickets/get/6";
// $json_data = json_encode($array);
// echo get($header, $url);

/**
 * Store Admin
 */
// // create user
// $array['fname'] = 'Modupe';
// $array['lname'] = 'Oripeloye';
// $array['email'] = 'zenzen448@yahoo.com';
// $array['password'] = 'Modupe.7';
// $url = $u."partner/users/register";
// echo post($header, $url, json_encode( $array ) );

// // activate user
// $array['otp'] = 476778;
// $url = $u."partner/users/activate";
// echo put($header, $url, json_encode( $array ) );

// // login user
// $array['email'] = 'zenzen448@yahoo.com';
// $array['password'] = 'Modupe.7';
// $url = $u."partner/users/login"; 
// echo post($header, $url, json_encode( $array ) );

// 18662993622
// K2721050
// Confirmation number: I1610754237

// // change password
// $array['old_password'] = 'kayode';
// $array['new_password'] = 'kayode';
// $url = $u."partner/users/password";
// echo put($header, $url, json_encode( $array ) );

// // get profile
// $url = $u."partner/users/get";
// echo get($header, $url );

// // upload ID
// $array['image'][] = base64_encode(file_get_contents("prog_320x480.jpg"));
// $url = $u."partner/uploadRaw/users/documents";
// $json_data = json_encode($array);
// echo post($header, $url, $json_data);

// // edit user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $url = $u."partner/users/edit";
// echo put($header, $url, json_encode( $array ) );

// //  logout
// $url = $u."partner/users/logout";
// echo get($header, $url );

// // request password
// $url = $u."partner/users/recovery/".urlencode("olukayode.adebiyi@hotmail.co.uk");
// echo get($header, $url );

// // Reset Password
// $array['otp'] = '279442';
// $array['password'] = 'kayode';
// $array['resetToken'] = 'ODIxNjc1NTQzXzE1OTIyNzI3Mjlfb2x1a2F5b2RlLmFkZWJpeWlAaG90bWFpbC5jby51aw==';
// $url = $u."partner/users/passwordReset";
// echo put($header, $url, json_encode( $array ) );

// // edit user
// $array['transitNumber'] = '12345';
// $array['institutionNunmber'] = '004';
// $array['accountNumber'] = '12345678';
// $url = $u."partner/users/accountDetails";
// echo put($header, $url, json_encode( $array ) );

// // get roles
// $url = $u."partner/users/roles";
// echo get($header, $url );

// // Store
// Add Store
// $array['name'] = 'Demo Store '.rand();
// $array['address'] = '1244 Donald Street';
// $array['city'] = 'Gloucester';
// $array['province'] = 'ON';
// $array['post_code'] = 'K1J 8V6';
// $array['email'] = rand().'@store.com';
// $array['phone'] = '343-540-7960';
// $array['url'] = 'http://store.com';
// $array['logo'] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $url = $u."partner/store/add";
// echo post($header, $url, json_encode( $array ) );

// // Edit Store
// $array['name'] = 'Edited Demo Store '.rand(1, 9);
// $array['address'] = '1244 Donald Street';
// $array['city'] = 'Gloucester';
// $array['province'] = 'ON';
// $array['post_code'] = 'K1J 8V6';
// $array['email'] = 'demo@store.com';
// $array['phone'] = '343-540-7960';
// $array['url'] = 'http://store.com';
// $array['logo'] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $url = $u."partner/store/edit/1";
// echo put($header, $url, json_encode( $array ) );

// // delete store
// $url = $u."partner/store/delete/5";
// echo delete($header, $url );

// // get all store
// $url = $u."partner/store/list";
// echo get($header, $url );

// // get one store
// $url = $u."partner/store/get/1";
// echo get($header, $url );

// // get balance
// $url = $u."partner/store/balance/1";
// echo get($header, $url );

// // request withdrawal
// $array['store_id'] = 1;
// $url = $u."partner/store/requestFunds";
// echo post($header, $url, json_encode( $array ) );

// // get wallet
// $url = $u."partner/store/wallet/1/current/1";
// echo get($header, $url );

// // create user to store
// $array['role_id'][] = 21;
// $array['role_id'][] = 22;
// $array['role_id'][] = 23;
// $array['store_id'] = rand(1, 100);
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $array['email'] = 'olukayode.'.rand().'@hotmail.ca';
// $url = $u."partner/store/addUser";
// echo post($header, $url, json_encode( $array ) );

// // toggle status
// $url = $u."partner/store/changeStatus/4";
// echo put($header, $url, "" );

// // get all store users
// $url = $u."partner/store/users/1";
// echo get($header, $url );

// // get categories
// $url = $u."partner/store/category/1";
// echo get($header, $url );

// Category
// // Add Category
// $array['name'] = 'Category 1';
// $array['store_id'] = 1;
// $url = $u."partner/category/add";
// echo post($header, $url, json_encode( $array ) );

// // Edit Category
// $array['name'] = 'Category 1'.rand();
// $array['store_id'] = 1;
// $array['ref'] = 7;
// $url = $u."partner/category/edit";
// echo put($header, $url, json_encode( $array ) );

// // toggle status
// $url = $u."partner/category/changeStatus/1";
// echo put($header, $url, "" );

// // delete categories
// $url = $u."partner/category/delete/4";
// echo delete($header, $url );

// Inventory
// // Add Inventory
// $array['name'] = 'Demo Store '.rand();
// $array['category_id'][] = 21;
// $array['category_id'][] = 22;
// $array['category_id'][] = 23;
// $array['store_id'] = 1;
// $array['amount'] = 23;
// $array['description'] = 'this is a description';
// $array['dietary'] = 'None';
// $array['data'][] = array("label"=>"Capsule", "value"=>40);
// $array['data'][] = array("label"=>"Color", "value"=>"Green");
// $array['data'][] = array("label"=>"Type", "value"=>"Gel");
// $array['image'][] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $array['image'][] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $array['image'][] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $array['image'][] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $array['image'][] = 'http://127.0.0.1/InstaDoorApi/files/store/651595465247480.png';
// $url = $u."partner/items/add";
// echo post($header, $url, json_encode( $array ) );

// // search inventory
// $url = $u."partner/items/search/demo";
// echo get($header, $url );

// // get all store item
// $url = $u."partner/items/store/1/2";
// echo get($header, $url );

// // get one store item
// $url = $u."partner/items/get/2";
// echo get($header, $url );

// Ticket
// // Add ticket
// $array['title'] = 'random ticket'.rand();
// $array['store_id'] = 2;
// $array['message'] = 'this is the message of the ticket, we will continue here later';
// $array['ticket_id'] = NULL;
// $url = $u."partner/tickets/add";
// echo post($header, $url, json_encode( $array ) );

// Edit ticket
// $array['title'] = 'random ticket';
// $array['order_id'] = 272;
// $array['message'] = 'this is the message of the ticket that was edited, we will continue here later';
// $array['ref'] = 2;
// $url = $u."partner/tickets/edit";
// echo put($header, $url, json_encode( $array ) );

// remove
// $url = $u."partner/tickets/delete/3";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // remove
// $url = $u."partner/tickets/remove/1";
// $json_data = json_encode($array);
// echo delete($header, $url, $json_data);

// // get all ticket
// $url = $u."partner/tickets/get/all/2";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get new ticket
// $url = $u."partner/tickets/get/new/2";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."partner/tickets/get/open/2";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get all ticket
// $url = $u."partner/tickets/get/closed/2";
// $json_data = json_encode($array);
// echo get($header, $url);

// // get one ticket
// $url = $u."partner/tickets/get/14/2";
// $json_data = json_encode($array);
// echo get($header, $url);

// Orders
// // get order one item
// $url = $u."partner/orders/get/150";
// echo get($header, $url );

// // // get order recent
// $url = $u."partner/orders/get/new";
// echo get($header, $url );

// // // get order recent
// $url = $u."partner/orders/get/past";
// echo get($header, $url );

// // get order processing item
// $url = $u."partner/orders/get/processing";
// echo get($header, $url );

// // get order pickupReady item
// $url = $u."partner/orders/get/pickupReady";
// echo get($header, $url );

// // get order pickupReady item
// $url = $u."partner/orders/get/accepted";
// echo get($header, $url );

// // get order deliveryOngoing item
// $url = $u."partner/orders/get/deliveryOngoing";
// echo get($header, $url );

// // get order delivered item
// $url = $u."partner/orders/get/delivered";
// echo get($header, $url );

// // get order completed item
// $url = $u."partner/orders/get/completed";
// echo get($header, $url );

// // get order flagged item
// $url = $u."partner/orders/get/flagged";
// echo get($header, $url );

// // get order cancelled item
// $url = $u."partner/orders/get/cancelled";
// echo get($header, $url );

// // get order paymentPending item
// $url = $u."partner/orders/get/paymentPending";
// echo get($header, $url );

// // get order paymentFailed item
// $url = $u."partner/orders/get/paymentFailed";
// echo get($header, $url );

// // get order paymentCompleted item
// $url = $u."partner/orders/get/paymentCompleted";
// echo get($header, $url );

// // process order
// $array['status'] = 'process';
// $array['ref'] = 579;
// $url = $u."partner/orders/changeStatus";
// echo put($header, $url, json_encode( $array ) );

// // set order as processed
// $array['ref'] = 301;
// $url = $u."partner/orders/processed";
// echo put($header, $url, json_encode( $array ) );

// // get order cancel order
// $url = $u."partner/orders/cancel/301";
// echo delete($header, $url );

/**
 * Site Admin
 */
// // create user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $array['email'] = 'olukayode.adebiyi@hotmail.co.uk';
// $array['password'] = 'kayode';
// $url = $u."site/users/register";
// echo post($header, $url, json_encode( $array ) );

// // activate user
// $array['otp'] = 476778;
// $url = $u."site/users/activate";
// echo put($header, $url, json_encode( $array ) );

// // login user
// $array['email'] = 'olukayode.adebiyi@hotmail.co.uk';
// $array['password'] = 'lolade';
// $url = $u."site/users/login";
// echo post($header, $url, json_encode( $array ) );

// // change password
// $array['old_password'] = 'kayode';
// $array['new_password'] = 'lolade';
// $url = $u."site/users/password";
// echo put($header, $url, json_encode( $array ) );

// // get profile
// $url = $u."site/users/get";
// echo get($header, $url );

// // edit user
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $url = $u."site/users/edit";
// echo put($header, $url, json_encode( $array ) );

// //  logout
// $url = $u."site/users/logout";
// echo get($header, $url );

// // request password
// $url = $u."site/users/recovery/".urlencode("olukayode.adebiyi@hotmail.co.uk");
// echo get($header, $url );

// // Reset Password
// $array['otp'] = '279442';
// $array['password'] = 'kayode';
// $array['resetToken'] = 'ODIxNjc1NTQzXzE1OTIyNzI3Mjlfb2x1a2F5b2RlLmFkZWJpeWlAaG90bWFpbC5jby51aw==';
// $url = $u."site/users/passwordReset";
// echo put($header, $url, json_encode( $array ) );

// // get roles
// $url = $u."site/users/roles";
// echo get($header, $url );

// // create admin user
// $array['role_id'][] = 1;
// $array['role_id'][] = 2;
// $array['role_id'][] = 3;
// $array['role_id'][] = 4;
// $array['role_id'][] = 5;
// $array['role_id'][] = 6;
// $array['role_id'][] = 7;
// $array['role_id'][] = 8;
// $array['role_id'][] = 9;
// $array['role_id'][] = 10;
// $array['role_id'][] = 11;
// $array['role_id'][] = 12;
// $array['role_id'][] = 13;
// $array['role_id'][] = 14;
// $array['role_id'][] = 15;
// $array['role_id'][] = 16;
// $array['role_id'][] = 17;
// $array['fname'] = 'Tosin';
// $array['lname'] = 'Adelakun';
// $array['email'] = 'adelakuntosinlanre@gmail.com';
// $url = $u."site/users/addUser";
// echo post($header, $url, json_encode( $array ) );

// // create admin user
// $array['role_id'][] = 1;
// $array['role_id'][] = 2;
// $array['role_id'][] = 3;
// $array['role_id'][] = 4;
// $array['role_id'][] = 5;
// $array['role_id'][] = 6;
// $array['role_id'][] = 7;
// $array['role_id'][] = 8;
// $array['role_id'][] = 9;
// $array['role_id'][] = 10;
// $array['role_id'][] = 11;
// $array['role_id'][] = 12;
// $array['role_id'][] = 13;
// $array['role_id'][] = 14;
// $array['role_id'][] = 15;
// $array['role_id'][] = 16;
// $array['role_id'][] = 17;
// $array['ref'] = 1;
// $array['fname'] = 'kayode';
// $array['lname'] = 'adebiyi';
// $array['email'] = 'olukayode.adebiyi@hotmail.ca';
// $url = $u."site/users/editAdmin";
// echo put($header, $url, json_encode( $array ) );

// // get all users
// $url = $u."site/users/list";
// echo get($header, $url );

// // get one user
// $url = $u."site/users/getOne/1";
// echo get($header, $url );

// // deactivate one user
// $url = $u."site/users/reset/2";
// echo put($header, $url, "");

// // delete one user
// $url = $u."site/users/deactivate/2";
// echo put($header, $url, "");

// // delete one user
// $url = $u."site/users/delete/2";
// echo delete($header, $url );

// // Home
// // get states
// $url = $u."site/home/stat";
// echo get($header, $url );

// // Settings
// // get settings
// $url = $u."site/settings/get";
// echo get($header, $url );

// Store
// // get stores
// $url = $u."site/store/get/1";
// echo get($header, $url );

// // edit stores
// $array['email'] = "kayode@email.com";
// $array['phone'] = "23432123454";
// $array['commission'] = "";
// $url = $u."site/store/edit/1";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// //verify store
// $array['response'] = "no";
// $array['response'] = "yes";
// $url = $u."site/store/verify/1";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// //change status
// $url = $u."site/store/changeStatus/1";
// echo put($header, $url, "" );

// // get all
// $url = $u."site/store/get/all/5";
// echo get($header, $url );

// // get users
// $url = $u."site/store/users/1";
// echo get($header, $url );

// // get pending users
// $url = $u."site/store/users/pending";
// echo get($header, $url );

// // get pending users
// $url = $u."site/store/users/get/145";
// echo get($header, $url );

// verify user
// // $array['response'] = "no";
// $array['response'] = "yes";
// $url = $u."site/store/users/verify/1";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// // get pending users
// $url = $u."site/store/users/reset/145";
// echo put($header, $url, $json_data);

// // get orders
// $url = $u."site/store/orders/1";
// echo get($header, $url );

// // get earning
// $url = $u."site/store/wallet/1";
// echo get($header, $url );

// // get inventory
// $url = $u."site/store/inventory/1";
// echo get($header, $url );

// // get pending verification
// $url = $u."site/store/get/pending";
// echo get($header, $url );

// // get approved
// $url = $u."site/store/get/approved";
// echo get($header, $url );

// // get rejected
// $url = $u."site/store/get/rejected";
// echo get($header, $url );

// // get online
// $url = $u."site/store/get/online";
// echo get($header, $url );

// // get offline
// $url = $u."site/store/get/offline";
// echo get($header, $url );

// // Customers
// // get customers
// $url = $u."site/customers/get/1";
// echo get($header, $url );

// // get customers list
// $url = $u."site/customers/list";
// echo get($header, $url );

// // deactivate one user
// $url = $u."site/customers/reset/2";
// echo put($header, $url, "");

// // deactivate one user
// $url = $u."site/customers/deactivate/2";
// echo put($header, $url, "");

// // Courier
// // get customers
// $url = $u."site/courier/get/1";
// echo get($header, $url );

// // get all
// $url = $u."site/courier/list";
// echo get($header, $url );

// // deactivate one user
// $url = $u."site/courier/reset/2";
// echo put($header, $url, "");

// // // deactivate one user
// $url = $u."site/courier/deactivate/2";
// echo put($header, $url, "");

// // get orders
// $url = $u."site/courier/orders/1";
// echo get($header, $url );

// // get one earning
// $url = $u."site/courier/wallet/1";
// echo get($header, $url );

// // get one orders
// $url = $u."site/courier/orders/1";
// echo get($header, $url );

// // get pending verification
// $url = $u."site/courier/get/pending";
// echo get($header, $url );

// // get approved
// $url = $u."site/courier/get/approved";
// echo get($header, $url );

// // get rejected
// $url = $u."site/courier/get/rejected";
// echo get($header, $url );

// //verify courier
// $array['response'] = "no";
// $array['response'] = "yes";
// $url = $u."site/courier/verify/1";
// $json_data = json_encode($array);
// echo put($header, $url, $json_data);

// Orders
// // get order one item
// $url = $u."site/orders/get/458";
// echo get($header, $url );

// // get all orders
// $url = $u."site/orders/get/all";
// echo get($header, $url );

// // get order recent
// $url = $u."site/orders/get/new";
// echo get($header, $url );

// // // get order recent
// $url = $u."site/orders/get/past";
// echo get($header, $url );

// // get order processing item
// $url = $u."site/orders/get/processing";
// echo get($header, $url );

// // get order pickupReady item
// $url = $u."site/orders/get/pickupReady";
// echo get($header, $url );

// // get order accepted item
// $url = $u."site/orders/get/accepted";
// echo get($header, $url );

// // get order deliveryOngoing item
// $url = $u."site/orders/get/deliveryOngoing";
// echo get($header, $url );

// // get order delivered item
// $url = $u."site/orders/get/delivered";
// echo get($header, $url );

// // get order completed item
// $url = $u."site/orders/get/completed";
// echo get($header, $url );

// // get order flagged item
// $url = $u."site/orders/get/flagged";
// echo get($header, $url );

// // get order cancelled item
// $url = $u."site/orders/get/cancelled";
// echo get($header, $url );

// // get order paymentPending item
// $url = $u."site/orders/get/paymentPending";
// echo get($header, $url );

// // get order paymentFailed item
// $url = $u."site/orders/get/paymentFailed";
// echo get($header, $url );

// // get order paymentCompleted item
// $url = $u."site/orders/get/paymentCompleted";
// echo get($header, $url );

// // get location
// $url = $u."site/orders/location/1362";
// echo get($header, $url );

// // get location
// $url = $u."site/orders/locations/458";
// echo get($header, $url );

// Items
// get order one item
// $url = $u."site/items/get/1";
// echo get($header, $url );

// // get order one item
// $url = $u."site/items/list/1";
// echo get($header, $url );

// // Wallet
// // get one
// $url = $u."site/wallet/get/1";
// echo get($header, $url );

// // get all entries
// $url = $u."site/wallet/list/1";
// echo get($header, $url );

// // get all payment request
// $url = $u."site/wallet/request";
// echo get($header, $url );

// // get all payment request
// $url = $u."site/wallet/request/get";
// echo get($header, $url );

// // get all payment advice
// $url = $u."site/wallet/advice";
// echo get($header, $url );

// // get all payment advice
// $url = $u."site/wallet/advice/get";
// echo get($header, $url );

print_r($header);
print_r(json_encode($array));
echo "<br>";
echo $url;

function get($header,$url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function post($header,$url, $data) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function put($header,$url, $data) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function delete($header,$url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
?>