<?php
	class alerts extends common {
		function sendEmail($array) {
			$from = $array['from'];
			$to = $array['to'];
			$subject = $array['subject'];
			$body = $array['body'];
			
			$send = $this->send_mail($from,$to,$subject,$body);
		
			if ($send) {
				return true;
			} else {
				return false;
			}
		}
	}
?>