<?php
	include_once("../../functions.php");
	$lname = $common->get_prep($_REQUEST['lname']);		
    $fname = $common->get_prep($_REQUEST['fname']);
	$email = $common->get_prep($_REQUEST['email']);
	$subject = $common->get_prep($_REQUEST['subject']);
	$password = $common->get_prep($_REQUEST['data']);
    $role_id = $common->get_prep($_REQUEST['role_id']);
    $store_id = $common->get_prep($_REQUEST['store_id']);

    $storeData = $store->listOne($store_id);
    
    if (isset($_REQUEST['admin'])) {
        $url = a_url;
    } else if (isset($_REQUEST['courier'])) {
        $url = c_url;
    } else if (isset($_REQUEST['store'])) {
        $url = p_url;
    } else {
        $url = u_url;
    }
    
	$getname = explode(" ", $fname);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="uft-8">
	<title><?=$subject ?></title>
</head>
<body style='font-family:Verdana; font-size: 12px; color:#777;' >
	<table width='630'  cellspacing='0' style='border: solid 20px #f3f3f3; margin:40px auto; background-color:#fff;' align='center'>
		<tr>
			<td align='center'>
			<br><img src='<?php echo URL;?>common/files/logo' width="200"><br>
			<div style='border-bottom: 1px solid #f3f3f3; width: 80%; margin:20px 0;'></div>
			</td>
		</tr>
		<tr>
			<td colspan='3'  align='center'>
				<h2>Welcome to Instadoor</h2>
                <p>Dear <?php echo ucwords(strtolower($getname[0])); ?>, </p>
                <p>Please save this email for future use. Your account enables you to login to the system. Your details are now saved as below:</p>
                <p><strong>Login Details</strong><hr>
                <p>URL: <strong><?=$url."/login"; ?></strong><br>
                Email: <strong><?=$email; ?></strong><br>
                Password: <strong><?=$password; ?></strong> <i>You will be asked to change this password the first time you login</i></p>
                <p>Your saved details (You can change these anytime) <hr>
                <p><strong>Contact Information</strong><br>
                Last Name: <?php echo ucwords(strtolower($lname)); ?><br>
                Other Name: <?php echo ucwords(strtolower($fname)); ?><br>
                Display Name: <?php echo ucwords(strtolower($display_name)); ?></p>
                <p>Your Store details <hr>
                <p><strong>Store Information</strong><br>
                Name: <?php echo ucwords(strtolower($storeData['name'])); ?><br>
                Street: <?php echo ucwords(strtolower($storeData['address'])); ?><br>
                City: <?php echo ucwords(strtolower($storeData['city'])); ?><br>
                State/Province: <?php echo ucwords(strtolower($storeData['province'])); ?><br>
                ZIP/Postal Code: <?php echo ucwords(strtolower($storeData['post_code'])); ?><br></p>
				</a><br><br><br>
			</td>
        </tr>
        <tr bgcolor='#f3f3f3'>
            <td height='50' colspan='3' align='center' style='padding:4px;'>
                <small>This email is intended for <strong><?=$lname; ?> <?=$fname; ?></strong>, please do not reply directly to this email. This email was sent from a notification-only address that cannot accept incoming email.</small><br><br>
                <small><strong>Protect Your Password</strong><br>
                Be alert to emails that request account information or urgent action.  Be cautious of websites with irregular addresses or those that offer unofficial payments to Instadoor or other private accounts.</small>
            </td>
        </tr>
		<tr bgcolor='#f3f3f3'>
			<td height='50' colspan='3' align='center' style='padding:4px;'>
				<P style="font-weight: bold;font-size: 11px;">
					<a href="<?php echo $url; ?>" style="text-decoration: none; color: #666;">HOME</a> |
					<a href="<?php echo $url; ?>/about" style="text-decoration: none; color: #666;">ABOUT US</a> |
					<a href="<?php echo $url; ?>/help" style="text-decoration: none; color: #666;">HELP</a>
				</P>
				&copy;<?php echo date("Y"); ?>, Instadoor  All Rights Reserved
			</td>
		</tr>  
	</table>
</body>
</html>