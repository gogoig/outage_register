<?php

class CMS_Outage_test {

	var $blacklistCompanyAddressRecIds = array(
		1085,
		1086,
		1125,
	);

	var $colourScheme = array(
		'client_pending' => array(
			'th' => array(
				'r' => 135,
				'g' => 102,
				'b' => 170,
			),
			'td' => array(
				'r' => 195,
				'g' => 185,
				'b' => 233,
			),
		),
		'planned' => array(
			'th' => array(
				'r' =>  79,
				'g' => 129,
				'b' => 189,
			),
			'td' => array(
				'r' => 184,
				'g' => 204,
				'b' => 228,
			),
		),
		'unplanned' => array(
			'th' => array(
				'r' => 192,
				'g' =>  80,
				'b' =>  77,
			),
			'td' => array(
				'r' => 229,
				'g' => 184,
				'b' => 183,
			),
		),
		'resolution' => array(
			'th' => array(
				'r' => 155,
				'g' => 187,
				'b' =>  89,
			),
			'td' => array(
				'r' => 214,
				'g' => 227,
				'b' => 188,
			),
		),
		'unplannedAmber' => array(
			'th' => array(
				'r' => 219,
				'g' => 170,
				'b' => 42,
			),
			'td' => array(
				'r' => 245,
				'g' => 225,
				'b' => 146,
			),
		),
	);
	
	/*************** PDO ******************/
	function create($data) {
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Prepare query
			$str = array();
			foreach ($data as $key => $value) {
				$str[] = "$key = :$key";
			}
			$str =implode(', ', $str);
			$query = sprintf('insert into outage set %s', $str);
			// return $query;
			
			$stmt = $conn->prepare($query);
			$stmt->execute($data);
			
			// return last insert id
			return $conn->lastInsertId();
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
		
	}

	/*************** PDO ******************/
	function update($id, $data) {
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Prepare query
			$str = array();
			foreach ($data as $key => $value) {
				$str[] = "$key = :$key";
			}
			// if(!array_key_exists('endTime', $data)){
				// $str[] = "end_time = NULL";
			// }
			$str =implode(', ', $str);
			$query = sprintf('update outage set %s where id=:id', $str);
			// return $query;
			
			$stmt = $conn->prepare($query);
			// $stmt->bindParam(':id', $id, PDO::PARAM_STR);
			$data[':id'] = $id;
			$stmt->execute($data);
			
			return 1;
			
		}catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}

	/*************** PDO ******************/
	function getOutageIds() {
		// Load config for database credentials
		$config = new Config;
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->query('select id from outage order by id desc');
			
			// Fetch result
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}
			
			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	
	}

	/*************** PDO ******************/
	function getMarchItCausedOutageIds($startRange = false, $endRange = false) {
		try{
			// Load config for database credentials
			$config = new Config;
		
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Query
			$query = "select id from outage
			where Company_RecID != '19297'
			and type_id = 2
			and status_id != '6'
			and responsible = '1'";
			if ($startRange) {
				$query .= "
				and end_time >= :startRange";
			}
			if ($endRange) {
				$query .= "
				and end_time <= :endRange";
			}
			$query .= "
				order by id desc";
				
			// SQL statement
			$stmt = $conn->prepare($query);
			if ($startRange) {
				$stmt->bindParam(':startRange', $startRange, PDO::PARAM_STR);
			}
			if ($endRange) {
				$stmt->bindParam(':endRange', $endRange, PDO::PARAM_STR);				
			}
			$stmt->execute();
			// return $query;
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	/*************** PDO ******************/
	function getCriticalOutageIdsByCompanyAndDateRange($companyRecId, $startRange, $endRange) {
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Query
			$stmt = $conn->prepare("select id from outage where Company_RecID = :companyRecId and start_time <= :startRange and critical = '1' order by id desc");
			$stmt->execute(array('companyRecId'=>$companyRecId, 'startRange'=>$startRange));
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	/*************** PDO ******************/
	function getOutageIdsByCompanyAndDateRange($companyRecId, $startRange, $endRange){
		// Load config for database credentials
		$config = new Config;

		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Query
			$stmt = $conn->prepare("select id from outage where Company_RecID = :companyRecId and start_time >= :startRange and start_time <= :endRange order by id desc");
			$stmt->execute(array('companyRecId'=>$companyRecId, 'startRange'=>$startRange, 'endRange'=>$endRange));
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
		
	}

	/*************** PDO ******************/
	function getOutageById($id){
		// Load config for database credentials
		$config = new Config;
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('select * from outage where id = :id limit 1');
			$stmt->execute(array('id'=>$id));
			
			// Fetch result
			$row=$stmt->fetch(PDO::FETCH_ASSOC);
			
			// Return result
			return $row;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	/*************** PDO ******************/
	function getRecipientsByOutage($id){
		// Load config for database credentials
		$config = new Config;
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('select email_address from outage_recipient where outage_id = :id');
			$stmt->execute(array('id'=>$id));
			
			// Fetch result
			$recipients = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$recipients[] = $row['email_address'];
			}
			
			// Return result
			return $recipients;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	function sendOutageNotification($outageId, $affectedSite, $recipient, $action = 'Notification') {
		$outageTypesObj = new CMS_Outage_Type;
		$outagePreferencesObj = new CMS_Outage_Preference;
		$emailFootersObj = new CMS_Email_Footer;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		
		// $outageRecp = $this->getRecipientsByOutage($outageId);		
		// if(!empty($outageRecp)){
			// foreach($outageRecp as $id=>$recp){
				$fromAddress = 'support@marchit.com.au';
				$fromName = 'March IT';
				// $preferences = $outagePreferencesObj->getPreferenceByCompanyRecId($outage['Company_RecID']);
				// if ($preferences) {
					// $fromAddress = $preferences['from_address'];
					// $fromName = $preferences['from_name'];
				// }
				$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
				// $siteList = $this->getSiteList($outage['id']);
				
				$word = $action;
				if($action=='Update' && $outage['status_id']==4){
					$word = 'Resolution';
				}
				if($action=='Update' && $outage['status_id']==6){
					$word = 'Cancellation';
				}
				$subject = $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $affectedSite . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);

				// Create email
				$mail = new PHPMailer();
				$mail->IsSMTP();  // telling the class to use SMTP
				$mail->Host = "localhost"; // SMTP server
				$mail->IsHTML(true);

				$mail->From = $fromAddress;
				$mail->FromName = $fromName;
				
				$mail->AddAddress($recipient);
				
				// $mail->AddBCC('matt.mulhall@marchit.com.au', 'Matt Mulhall');
				$mail->AddBCC('gaurav.gogoi@marchit.com.au', 'Gaurav Gogoi');

				$mail->Subject  = $subject;

				$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
				$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';
				
				// $affectedClient = ;
				$table = $this->generateOutageTableHtml($outage['id'], $affectedSite);
				$mail->Body = $table;

				$mail->Body .= "
		<p style='$pStyle'>If you have any questions or enquiries regarding the outage, please don't hesitate to contact us.</p>
		<p style='$pStyle'>Regards,</p>";
				$pStyle = $fontStyle .= ' font-size: 15px; color: rgb(16, 37, 63); font-weight: bold;';
				$mail->Body .= "
		<p style='$pStyle'>March IT</p>";
				$mail->Body .= $emailFootersObj->getFooterMarchIT();

				// Send Email
				if(!$mail->Send()){
					return false;
				}
				
			// }
			return true;
		// }
	}

	// Internal Notification
	function sendInternalOutageNotification($outageId, $action='Notification'){
		$outageTypesObj = new CMS_Outage_Type;
		$outagePreferencesObj = new CMS_Outage_Preference;
		$emailFootersObj = new CMS_Email_Footer;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		$outageRecp = $this->getRecipientsByOutage($outageId);
		
		$fromAddress = 'support@marchit.com.au';
		$fromName = 'March IT';
		// $preferences = $outagePreferencesObj->getPreferenceByCompanyRecId($outage['Company_RecID']);
		// if ($preferences) {
			// $fromAddress = $preferences['from_address'];
			// $fromName = $preferences['from_name'];
		// }
		
		$word = $action;
		if($action=='Update' && $outage['status_id']==4){
			$word = 'Resolution';
		}
		if($action=='Update' && $outage['status_id']==6){
			$word = 'Cancellation';
		}
		
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		$siteList = $this->getSiteList($outage['id']);
		// $word = 'Notification';
		// $subject = $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);
		$subject = $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);

		// Create email
		$mail = new PHPMailer();
		$mail->IsSMTP();  // telling the class to use SMTP
		$mail->Host = "localhost"; // SMTP server
		$mail->IsHTML(true);

		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
			
		if ($outage['Company_RecID'] != 19297 || $outage['Company_RecID'] != 19493) { // not XYZ Test Company or XYZ Test 2
			// $mail->AddAddress('tech@marchit.com.au', 'Technical Services');
		}
		// $mail->AddBCC('matt.mulhall@marchit.com.au', 'Matt Mulhall');
		$mail->AddBCC('gaurav.gogoi@marchit.com.au', 'Gaurav Gogoi');

		$mail->Subject  = $subject;

		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';
		
		$table = $this->generateOutageTableHtmlInternal($outage['id']);
		$mail->Body = $table;

		$mail->Body .= "
<p style='$pStyle'>If you have any questions or enquiries regarding the outage, please don't hesitate to contact us.</p>
<p style='$pStyle'>Regards,</p>";
		$pStyle = $fontStyle .= ' font-size: 15px; color: rgb(16, 37, 63); font-weight: bold;';
		$mail->Body .= "
<p style='$pStyle'>March IT</p>";
		$mail->Body .= $emailFootersObj->getFooterMarchIT();

		// Send Email
		return $mail->Send();
	}
	
	// Notify Board Members
	function notifyBoardMembers($outageId, $action = 'Notification'){
		$outageTypesObj = new CMS_Outage_Type;
		$outagePreferencesObj = new CMS_Outage_Preference;
		$emailFootersObj = new CMS_Email_Footer;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		$outageRecp = $this->getRecipientsByOutage($outageId);
		
		$fromAddress = 'support@marchit.com.au';
		$fromName = 'March IT';
		// $preferences = $outagePreferencesObj->getPreferenceByCompanyRecId($outage['Company_RecID']);
		// if ($preferences) {
			// $fromAddress = $preferences['from_address'];
			// $fromName = $preferences['from_name'];
		// }
		
		$word = $action;
		if($action=='Update' && $outage['status_id']==4){
			$word = 'Resolution';
		}
		if($action=='Update' && $outage['status_id']==6){
			$word = 'Cancellation';
		}
				
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		$siteList = $this->getSiteList($outage['id']);
		// $word = 'Notification';
		// $subject = $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);
		$subject = $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);

		// Create email
		$mail = new PHPMailer();
		$mail->IsSMTP();  // telling the class to use SMTP
		$mail->Host = "localhost"; // SMTP server
		$mail->IsHTML(true);

		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		
		if ($outage['Company_RecID'] != 19297 || $outage['Company_RecID'] != 19493) { // not XYZ Test Company or XYZ Test 2
			// $mail->AddAddress('geoff.marsh@marchit.com.au', 'Geoff Marsh');
			// $mail->AddAddress('chris.canty@marchit.com.au', 'Chris Canty');
			// $mail->AddAddress('mike.marsh@marchit.com.au', 'Mike Marsh');
		}
		// $mail->AddBCC('matt.mulhall@marchit.com.au', 'Matt Mulhall');
		$mail->AddBCC('gaurav.gogoi@marchit.com.au', 'Gaurav Gogoi');

		$mail->Subject  = $subject;

		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';
		
		$table = $this->generateOutageTableHtmlInternal($outage['id']);
		$mail->Body = $table;

		$mail->Body .= "
<p style='$pStyle'>If you have any questions or enquiries regarding the outage, please don't hesitate to contact us.</p>
<p style='$pStyle'>Regards,</p>";
		$pStyle = $fontStyle .= ' font-size: 15px; color: rgb(16, 37, 63); font-weight: bold;';
		$mail->Body .= "
<p style='$pStyle'>March IT</p>";
		$mail->Body .= $emailFootersObj->getFooterMarchIT();

		// Send Email
		return $mail->Send();
	}
	
	// New Outage Approval Request
	function sendApprovalRequest($outageId, $member, $recipients, $otherRecipient){
		if (isset($_SESSION['user']['username'])) {
			if (strtolower($_SESSION['user']['username']) == 'patrick.sy') {
				return;
			}
		}
		$outageTypesObj = new CMS_Outage_Type;
		$outagePreferencesObj = new CMS_Outage_Preference;
		$emailFootersObj = new CMS_Email_Footer;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		// $fromAddress = 'support@marchit.com.au';
		// $fromName = 'March IT';
		$fromAddress =  $_SESSION['user']['mail'];
		$fromName = $_SESSION['user']['givenname'] . ' ' . $_SESSION['user']['sn'];
		// $preferences = $outagePreferencesObj->getPreferenceByCompanyRecId($outage['Company_RecID']);
		// if ($preferences) {
			// $fromAddress = $preferences['from_address'];
			// $fromName = $preferences['from_name'];
		// }
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		$siteList = $this->getSiteList($outage['id']);
		$word = '';
		
		$subject = 'Approval Required - ';
		$subject .= $type['title'] . ' Outage ' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);
		
		// Create email
		$mail = new PHPMailer();
		$mail->IsSMTP();  // telling the class to use SMTP
		$mail->Host = "localhost"; // SMTP server
		$mail->IsHTML(true);

		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		$mail->AddAddress($member);
		// $mail->AddBCC('matt.mulhall@marchit.com.au', 'Matt Mulhall');
		$mail->AddBCC('gaurav.gogoi@marchit.com.au', 'Gaurav Gogoi');

		$mail->Subject  = $subject;
		
		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';

		$mail->Body = "
<p style='$pStyle'>The following Outage has been logged and is awaiting approval. <br/><br/>";
// Notification will be sent to the following recipient(s):<br/>";
			
		// if(!empty($recipients)){
			// foreach($recipients as $key=>$recp){
				// $mail->Body .= $recp ."<br/>";
			// }
		// }
		// if($otherRecipient!=''){
			// $mail->Body .= $otherRecipient ."<br/>";
		// }
		$mail->Body .= "</p>";
		$mail->Body .= "<p style='$pStyle'>Click <a href='http://cms.marchit.com.au/cgi/new/Service_Desk/outage_new.test.php?id=" .$outageId ."'> here</a> to make changes.</p>
<p style='$pStyle'>Click <a href='http://cms.marchit.com.au/cgi/new/Service_Desk/outage_approve.test.php?id=" .$outageId ."'>here</a> to approve.</p>";	// test...

		$table = $this->generateOutageTableHtmlInternal($outage['id']);
		$mail->Body .= $table;
		
		$mail->Body .= "
<p style='$pStyle'>Regards,</p>";
			$pStyle = $fontStyle .= ' font-size: 15px; color: rgb(16, 37, 63); font-weight: bold;';
			$mail->Body .= "
<p style='$pStyle'>March IT</p>";
			$mail->Body .= $emailFootersObj->getFooterMarchIT();
		//}
		
		// Send Email
		return $mail->Send();
	}
	
	// Outage Update Approval Request
	function sendUpdateApprovalRequest($outageId, $member, $updateId){
		if (isset($_SESSION['user']['username'])) {
			if (strtolower($_SESSION['user']['username']) == 'patrick.sy') {
				return;
			}
		}
		$outageTypesObj = new CMS_Outage_Type;
		$outagePreferencesObj = new CMS_Outage_Preference;
		$emailFootersObj = new CMS_Email_Footer;

		// $result = $this->deleteUnapprovedUpdate($outageId);
		// if(!$result){
			// return;
		// }
		
		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		$fromAddress = 'support@marchit.com.au';
		$fromName = 'March IT';
		$fromAddress =  $_SESSION['user']['mail'];
		$fromName = $_SESSION['user']['givenname'] . ' ' . $_SESSION['user']['sn'];
		// $preferences = $outagePreferencesObj->getPreferenceByCompanyRecId($outage['Company_RecID']);
		// if ($preferences) {
			// $fromAddress = $preferences['from_address'];
			// $fromName = $preferences['from_name'];
		// }
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		$siteList = $this->getSiteList($outage['id']);
		$word = '';
		
		$subject = 'Approval Required - ';
		$subject .= $type['title'] . ' Outage Update' . $word . ' ' . chr(45) . ' ' . $siteList . ' ' .  chr(45) . ' ' . date("j/m/Y", $outage['start_time']);
		
		// Create email
		$mail = new PHPMailer();
		$mail->IsSMTP();  // telling the class to use SMTP
		$mail->Host = "localhost"; // SMTP server
		$mail->IsHTML(true);

		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		$mail->AddAddress($member);
		// $mail->AddBCC('matt.mulhall@marchit.com.au', 'Matt Mulhall');
		$mail->AddBCC('gaurav.gogoi@marchit.com.au', 'Gaurav Gogoi');

		$mail->Subject  = $subject;
		
		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';

		$mail->Body = "
<p style='$pStyle'>The following Outage update has been logged and is awaiting approval. <br/><br/>";

		$mail->Body .= "</p>";
		$mail->Body .= "<p style='$pStyle'>Click <a href='http://cms.marchit.com.au/cgi/new/Service_Desk/outage_update_approve.test.php?updateId=" .$updateId ."&outageId=" .$outageId. "'>here</a> to approve.</p>";	// test...

		$table = $this->generateOutageTableHtmlInternal($outage['id'], 0);
		$mail->Body .= $table;
		
		$mail->Body .= "
<p style='$pStyle'>Regards,</p>";
			$pStyle = $fontStyle .= ' font-size: 15px; color: rgb(16, 37, 63); font-weight: bold;';
			$mail->Body .= "
<p style='$pStyle'>March IT</p>";
			$mail->Body .= $emailFootersObj->getFooterMarchIT();
		//}
		
		// Send Email
		return $mail->Send();
	}
	
	// Delete unapproved update
	function deleteUnapprovedUpdate($outageId){
		// Load config for database credentials
		$config = new Config;
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('delete from outage_update where outage_id = :outage_id and approved=0');
			$stmt->execute(array('outage_id'=>$outageId));
			
			// Return result
			return 1;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	// Sent in email notification
	function generateOutageTableHtml($outageId, $affectedSite) {
		$outageTypesObj = new CMS_Outage_Type;
		$outageServicesObj = new CMS_Outage_Service;
		$timeZonesObj = new CMS_Time_Zone;
		$updatesObj = new CMS_Outage_Update;
		$companiesObj = new CW_Company;
		$outageThingsObj = new CMS_Outage_Thing;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		// $companyName = '';
		// $company = $companiesObj->getCompanyByRecId($outage['Company_RecID']);
		// if ($company) {
			// $companyName = $company['Company_Name'];
		// } 
		$siteList = $this->getSiteList($outage['id']);
		
		$timeZone = $timeZonesObj->getTimeZoneById($outage['time_zone_id']);
		$endTime = '';
		if ($outage['end_time']) {
			if ($outage['type_id'] == 1) {
				// Planned - show actual timestamp as it was entered by a human and offset depending on time zone (ha!)
				$endTime = date("j/m/Y g:i a", $outage['end_time']) . ' ' . $timeZone['code'];
			} else {
				// Unplanned - show offset timestamp because it was automatically stamped by the system at AEST
				$endTime = date("j/m/Y g:i a", ($outage['end_time'] + ($timeZone['variance'] * 60 * 60))) . ' ' . $timeZone['code'];
			}
		}

		$affectedServices = ucfirst($outage['service_other']);
		$service = $outageServicesObj->getServiceById($outage['service_id']);
		if ($service) {
			$affectedServices = ucfirst($service['title']);
		}
		
		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';
		$thStyle = $pStyle . ' padding: 5px; font-weight: bold; width: 190px;';
		$tdStyle = $pStyle . ' padding: 5px;';
		$headerTxt = '';
		// if($outage['client_notified']==0){
			// Pending client notification
			// $thStyle .= ' background-color: rgb(' . $this->colourScheme['client_pending']['th']['r'] . ', ' . $this->colourScheme['client_pending']['th']['g'] . ', ' . $this->colourScheme['client_pending']['th']['b'] . '); color: #ffffff;';
			// $tdStyle .= ' background-color: rgb(' . $this->colourScheme['client_pending']['td']['r'] . ', ' . $this->colourScheme['client_pending']['td']['g'] . ', ' . $this->colourScheme['client_pending']['td']['b'] . ');';
		// } else{
			if ($outage['type_id'] == 1) {
				// Planned
				$thStyle .= ' background-color: rgb(' . $this->colourScheme['planned']['th']['r'] . ', ' . $this->colourScheme['planned']['th']['g'] . ', ' . $this->colourScheme['planned']['th']['b'] . '); color: #ffffff;';
				$tdStyle .= ' background-color: rgb(' . $this->colourScheme['planned']['td']['r'] . ', ' . $this->colourScheme['planned']['td']['g'] . ', ' . $this->colourScheme['planned']['td']['b'] . ');';
				$headerTxt = 'Planned Outage Notification';
			}
			if ($outage['type_id'] == 2 || $outage['type_id'] == 3) { // Unplanned or Unplanned (test only)
				// Unplanned
				if($outage['owned']==1 && $outage['responsible']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==1 && $outage['responsible']==0){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['th']['r'] . ', ' . $this->colourScheme['unplannedAmber']['th']['g'] . ', ' . $this->colourScheme['unplannedAmber']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['td']['r'] . ', ' . $this->colourScheme['unplannedAmber']['td']['g'] . ', ' . $this->colourScheme['unplannedAmber']['td']['b'] . ');';
					$headerTxt = 'Site Outage Recorded<br/>March IT has detected a loss of service. The issue has been investigated and attributed to other factors at site.';
				}
				if($outage['owned']==0 && $outage['responsible']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==0 && $outage['responsible']==0 && $outage['hardware_failure']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==0 && $outage['responsible']==0 && $outage['hardware_failure']==0){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['th']['r'] . ', ' . $this->colourScheme['unplannedAmber']['th']['g'] . ', ' . $this->colourScheme['unplannedAmber']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['td']['r'] . ', ' . $this->colourScheme['unplannedAmber']['td']['g'] . ', ' . $this->colourScheme['unplannedAmber']['td']['b'] . ');';
					$headerTxt = 'Site Outage Recorded<br/>March IT has detected a loss of service. The issue has been investigated and attributed to other factors at site.';
				}
			}
			if ($outage['status_id'] == 4 || $outage['status_id'] == 6 || $outage['status_id'] == 7) {
				// Resolution
				$thStyle .= ' background-color: rgb(' . $this->colourScheme['resolution']['th']['r'] . ', ' . $this->colourScheme['resolution']['th']['g'] . ', ' . $this->colourScheme['resolution']['th']['b'] . '); color: #ffffff;';
				$tdStyle .= ' background-color: rgb(' . $this->colourScheme['resolution']['td']['r'] . ', ' . $this->colourScheme['resolution']['td']['g'] . ', ' . $this->colourScheme['resolution']['td']['b'] . ');';
			}
		// }
		
		$table = "<table style='width: 100%;'>
	<tr>
		<th colspan=2 style='$thStyle; text-align:center'>$headerTxt</th>
	</tr>
	<tr>
		<th style='$thStyle'>Outage ID</th>
		<td style='$tdStyle'>{$outage['id']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>Outage Type</th>
		<td style='$tdStyle'>{$type['title']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>Start Time</th>
		<td style='$tdStyle'>" . date("j/m/Y g:i a", $outage['start_time']) . " {$timeZone['code']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>End Time</th>
		<td style='$tdStyle'>$endTime</td>
	</tr>";
		// $table .= "
	// <tr>
		// <th style='$thStyle'>Company</th>
		// <td style='$tdStyle'>$companyName</td>
	// </tr>";
		$table .= "
	<tr>
		<th style='$thStyle'>Affected Sites</th>";
		if($outage['Company_RecID']==19307){
			$table .= "
		<td style='$tdStyle'>$affectedSite</td>";
		} else{
			$table .= "
		<td style='$tdStyle'>$siteList</td>";
		}
		$table .= "
	</tr>
	<tr>
		<th style='$thStyle'>Affected Services</th>
		<td style='$tdStyle'>$affectedServices</td>
	</tr>
	<tr>
		<th style='$thStyle'>Summary</th>
		<td style='$tdStyle'>{$outage['summary']}</td>
	</tr>";
		$updateIds = $updatesObj->getUpdateIdsByOutageId($outage['id']);
		$i = 1;
		foreach ($updateIds as $updateId) {
			$update = $updatesObj->getUpdateById($updateId);
			if ($update) {
				$title = 'Update ' . $i;
				$updateTime = $update['created'];
				if ($update['resolution']) {
					$title = 'Resolution';
					$updateTime = $outage['end_time'];
				}
				$timeZone = $timeZonesObj->getTimeZoneById($outage['time_zone_id']);
				$timestamp = date("j/m/Y g:i a", ($updateTime + ($timeZone['variance'] * 60 * 60))) . ' ' . $timeZone['code'];
				// incident summary header
				if($update['report']){
					$title = 'Incident Summary';
					$timestamp = '';
				}
				$table .= "
	<tr>
		<th style='$thStyle'>
			$title<br />
			$timestamp
		</th>
		<td style='$tdStyle'>
			{$update['description']}";
				if ($update['resolution']) {
					$table .= "
			<br />
			<br />
			{$outage['cause_and_action']}";
				}
				$table .= "
		</td>
	</tr>";
				$i++;
			}
		}
		$table .= " 
</table>";
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; padding: 5px;';
		$things = '';
		$thingIds = $outageThingsObj->getThingIdsByOutageId($outage['id']);
		$outputHeader = 0;
		foreach ($thingIds as $thingId) {
			$thing = $outageThingsObj->getThingById($thingId);
			if ($thing) {
				if ($outputHeader < 1) {
					$table .= "
<br />
<p style='$pStyle'>Important things to remember:</p>
<ul>";
					$outputHeader++;
				}
				$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; padding: 5px 0 5px 20px;';
				$table .= "
	<li style='$pStyle'>{$thing['description']}</li>";
			}
		}
		if ($outputHeader > 0) {
			$table .= "
</ul>";
		}
		return $table;
	}

	// Displayed on outage_view page
	function generateOutageTableHtmlInternal($outageId, $approved=1) {
		$outageTypesObj = new CMS_Outage_Type;
		$outageServicesObj = new CMS_Outage_Service;
		$timeZonesObj = new CMS_Time_Zone;
		$updatesObj = new CMS_Outage_Update;
		$companiesObj = new CW_Company;
		$outageThingsObj = new CMS_Outage_Thing;
		$outageSiteCompanySelectObj = new CMS_Outage_Site_Company_Select;
		$outageSiteCompanyPrefObj = new CMS_Outage_Site_Company_Preference;
		
		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}
		
		$type = $outageTypesObj->getOutageTypeById($outage['type_id']);
		$companyName = '';
		$company = $companiesObj->getCompanyByRecId($outage['Company_RecID']);
		if ($company) {
			$companyName = $company['Company_Name'];
		}
		$clientIds =  $outageSiteCompanySelectObj->getClientIdsByOutageId($outage['id']);
		// $affectedClientIds = $outageSiteCompanyPrefObj->getClientsByCompanyAddressRecId($companyAddressRecId);
		// $affectedCompanies = implode(',', $clientIds);
		$affectedCompanies = array();
		if(!empty($clientIds)){
			foreach($clientIds as $clientId){
				$client = $companiesObj->getCompanyByRecId($clientId);
				$affectedCompanies[] = $client['Company_Name'];
			}
		}
		$affectedCompanies = implode(', ', $affectedCompanies);
		$siteList = $this->getSiteList($outage['id']);
		
		$timeZone = $timeZonesObj->getTimeZoneById($outage['time_zone_id']);
		$endTime = '';
		if ($outage['end_time']) {
			if ($outage['type_id'] == 1) {
				// Planned - show actual timestamp as it was entered by a human and offset depending on time zone (ha!)
				$endTime = date("j/m/Y g:i a", $outage['end_time']) . ' ' . $timeZone['code'];
			} else {
				// Unplanned - show offset timestamp because it was automatically stamped by the system at AEST
				$endTime = date("j/m/Y g:i a", ($outage['end_time'] + ($timeZone['variance'] * 60 * 60))) . ' ' . $timeZone['code'];
			}
		}
		// unapproved resolution time
		$updateIds = $updatesObj->getUpdateIdsByOutageId($outage['id'], $approved);
		$i = 1;
		foreach ($updateIds as $updateId) {
			$update = $updatesObj->getUpdateById($updateId);
			if ($update['resolution']==1) {
				$endTime = date("j/m/Y g:i a", $update['end_time']) . ' ' . $timeZone['code'];
			}
		}

		$affectedServices = ucfirst($outage['service_other']);
		$service = $outageServicesObj->getServiceById($outage['service_id']);
		if ($service) {
			$affectedServices = ucfirst($service['title']);
		}
		
		$fontStyle = 'font-family: Arial, Helvetica, sans-serif;';
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; text-align: left;';
		$thStyle = $pStyle . ' padding: 5px; font-weight: bold; width: 190px;';
		$tdStyle = $pStyle . ' padding: 5px;';
		$headerTxt = '';
		// if($outage['client_notified']==0){
			// Pending client notification
			// $thStyle .= ' background-color: rgb(' . $this->colourScheme['client_pending']['th']['r'] . ', ' . $this->colourScheme['client_pending']['th']['g'] . ', ' . $this->colourScheme['client_pending']['th']['b'] . '); color: #ffffff;';
			// $tdStyle .= ' background-color: rgb(' . $this->colourScheme['client_pending']['td']['r'] . ', ' . $this->colourScheme['client_pending']['td']['g'] . ', ' . $this->colourScheme['client_pending']['td']['b'] . ');';
		// } else{
			if ($outage['type_id'] == 1) {
				// Planned
				$thStyle .= ' background-color: rgb(' . $this->colourScheme['planned']['th']['r'] . ', ' . $this->colourScheme['planned']['th']['g'] . ', ' . $this->colourScheme['planned']['th']['b'] . '); color: #ffffff;';
				$tdStyle .= ' background-color: rgb(' . $this->colourScheme['planned']['td']['r'] . ', ' . $this->colourScheme['planned']['td']['g'] . ', ' . $this->colourScheme['planned']['td']['b'] . ');';
				$headerTxt = 'Planned Outage Notification';
			}
			if ($outage['type_id'] == 2 || $outage['type_id'] == 3) {	// Unplanned & Unplanned (test only)
				// Unplanned
				if($outage['owned']==1 && $outage['responsible']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==1 && $outage['responsible']==0){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['th']['r'] . ', ' . $this->colourScheme['unplannedAmber']['th']['g'] . ', ' . $this->colourScheme['unplannedAmber']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['td']['r'] . ', ' . $this->colourScheme['unplannedAmber']['td']['g'] . ', ' . $this->colourScheme['unplannedAmber']['td']['b'] . ');';
					$headerTxt = 'Site Outage Recorded<br/>March IT has detected a loss of service. The issue has been investigated and attributed to other factors at site.';
				}
				if($outage['owned']==0 && $outage['responsible']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==0 && $outage['responsible']==0 && $outage['hardware_failure']==1){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['th']['r'] . ', ' . $this->colourScheme['unplanned']['th']['g'] . ', ' . $this->colourScheme['unplanned']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplanned']['td']['r'] . ', ' . $this->colourScheme['unplanned']['td']['g'] . ', ' . $this->colourScheme['unplanned']['td']['b'] . ');';
					$headerTxt = 'Critical Outage Notification<br/>There has been a loss of service on infrastructure supported by March IT. We are working to resolve the issue as soon as possible.';
				}
				if($outage['owned']==0 && $outage['responsible']==0 && $outage['hardware_failure']==0){
					$thStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['th']['r'] . ', ' . $this->colourScheme['unplannedAmber']['th']['g'] . ', ' . $this->colourScheme['unplannedAmber']['th']['b'] . '); color: #ffffff;';
					$tdStyle .= ' background-color: rgb(' . $this->colourScheme['unplannedAmber']['td']['r'] . ', ' . $this->colourScheme['unplannedAmber']['td']['g'] . ', ' . $this->colourScheme['unplannedAmber']['td']['b'] . ');';
					$headerTxt = 'Site Outage Recorded<br/>March IT has detected a loss of service. The issue has been investigated and attributed to other factors at site.';
				}
			}
			if ($outage['status_id'] == 4 || $outage['status_id'] == 6 || $outage['status_id'] == 7) {
				// Resolution
				$thStyle .= ' background-color: rgb(' . $this->colourScheme['resolution']['th']['r'] . ', ' . $this->colourScheme['resolution']['th']['g'] . ', ' . $this->colourScheme['resolution']['th']['b'] . '); color: #ffffff;';
				$tdStyle .= ' background-color: rgb(' . $this->colourScheme['resolution']['td']['r'] . ', ' . $this->colourScheme['resolution']['td']['g'] . ', ' . $this->colourScheme['resolution']['td']['b'] . ');';
			}
		// }
		
		$table = "<table style='width: 100%;'>
	<tr>
		<th colspan=2 style='$thStyle; text-align:center'>$headerTxt</th>
	</tr>
	<tr>
		<th style='$thStyle'>Outage ID</th>
		<td style='$tdStyle'>{$outage['id']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>Outage Type</th>
		<td style='$tdStyle'>{$type['title']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>Start Time</th>
		<td style='$tdStyle'>" . date("j/m/Y g:i a", $outage['start_time']) . " {$timeZone['code']}</td>
	</tr>
	<tr>
		<th style='$thStyle'>End Time</th>
		<td style='$tdStyle'>$endTime</td>
	</tr>";
		$table .= "
	<tr>
		<th style='$thStyle'>Company</th>
		<td style='$tdStyle'>$companyName</td>
	</tr>";
		if($outage['Company_RecID']==19307){	// March IT
			$table .= "
	<tr>
		<th style='$thStyle'>Affected Companies</th>
		<td style='$tdStyle'>$affectedCompanies</td>
	</tr>";
		}
		$table .= "
	<tr>
		<th style='$thStyle'>Affected Sites</th>
		<td style='$tdStyle'>$siteList</td>
	</tr>
	<tr>
		<th style='$thStyle'>Affected Services</th>
		<td style='$tdStyle'>$affectedServices</td>
	</tr>
	<tr>
		<th style='$thStyle'>Summary</th>
		<td style='$tdStyle'>{$outage['summary']}</td>
	</tr>";
		$updateIds = $updatesObj->getUpdateIdsByOutageId($outage['id'], $approved);
		$i = 1;
		foreach ($updateIds as $updateId) {
			$update = $updatesObj->getUpdateById($updateId);
			if ($update) {
				$title = 'Update ' . $i;
				// $updateTime = $update['created'];
				$updateTime = date("j/m/Y g:i a", ($update['created'] + ($timeZone['variance'] * 60 * 60))) . ' ' . $timeZone['code'];
				if ($update['resolution']) {
					$title = 'Resolution';
					$updateTime = $endTime;
				}
				$timeZone = $timeZonesObj->getTimeZoneById($outage['time_zone_id']);
				$timestamp = $updateTime;
				// $timestamp = date("j/m/Y g:i a", ($updateTime + ($timeZone['variance'] * 60 * 60))) . ' ' . $timeZone['code'];
				// incident summary header
				if($update['report']){
					$title = 'Incident Summary';
					$timestamp = '';
				}
				// $updateStr = utf8_decode($update['description']);
				// $updateStr = htmlspecialchars($update['description'], ENT_QUOTES);
				$table .= "
	<tr>
		<th style='$thStyle'>
			$title<br />
			$timestamp
		</th>
		<td style='$tdStyle'>
		{$update['description']}";
				if ($update['resolution']) {
					$table .= "
			<br />
			<br />
			{$update['cause_and_action']}";
				}
				$table .= "
		</td>
	</tr>";
				$i++;
			}
		}
		$table .= " 
</table>";
		$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; padding: 5px;';
		$things = '';
		$thingIds = $outageThingsObj->getThingIdsByOutageId($outage['id']);
		$outputHeader = 0;
		foreach ($thingIds as $thingId) {
			$thing = $outageThingsObj->getThingById($thingId);
			if ($thing) {
				if ($outputHeader < 1) {
					$table .= "
<br />
<p style='$pStyle'>Important things to remember:</p>
<ul>";
					$outputHeader++;
				}
				$pStyle = $fontStyle .= ' font-size: 15px; line-height: 110%; padding: 5px 0 5px 20px;';
				$table .= "
	<li style='$pStyle'>{$thing['description']}</li>";
			}
		}
		if ($outputHeader > 0) {
			$table .= "
</ul>";
		}
		return $table;
	}

	function getSiteList($outageId) {
		$outageSiteSelectsObj = new CMS_Outage_Site_Select;
		$companyAddressesObj = new CW_Company_Address;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}

		$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($outage['Company_RecID']);
		$outageCompanyAddressRecIds = $outageSiteSelectsObj->getCompanyAddressRecIds($outage['id']);
		$miss = 0;
		foreach ($companyAddressRecIds as $companyAddressRecId) {
			if (!in_array($companyAddressRecId, $this->blacklistCompanyAddressRecIds)) {
				if (!in_array($companyAddressRecId, $outageCompanyAddressRecIds)) {
					$miss++;
				}
			}
		}
		// if ($miss < 1) {
			// return 'All sites';
		// }
		$companyAddressList = array();
		foreach ($outageCompanyAddressRecIds as $companyAddressRecId) {
			$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
			if ($companyAddress) {
				$companyAddressList[] = $companyAddress['Description'];
			}
		}
		$siteList = '';
		for ($i = 0; $i < count($companyAddressList); $i++) {
			if ((count($companyAddressList) > 1) and ($i > 0)) {
				if ($i < (count($companyAddressList) - 1)) {
					$siteList .= ', ';
				} else {
					$siteList .= ' and ';
				}
			}
			$siteList .= $companyAddressList[$i];
		}
		return $siteList;
	}
	
	function getSiteIds($outageId){
		$outageSiteSelectsObj = new CMS_Outage_Site_Select;
		$companyAddressesObj = new CW_Company_Address;

		$outage = $this->getOutageById($outageId);
		if (!$outage) {
			return;
		}

		$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($outage['Company_RecID']);
		$outageCompanyAddressRecIds = $outageSiteSelectsObj->getCompanyAddressRecIds($outage['id']);
		$miss = 0;
		foreach ($companyAddressRecIds as $companyAddressRecId) {
			if (!in_array($companyAddressRecId, $this->blacklistCompanyAddressRecIds)) {
				if (!in_array($companyAddressRecId, $outageCompanyAddressRecIds)) {
					$miss++;
				}
			}
		}
		// if ($miss < 1) {
			// return 'All sites';
		// }
		$companyAddressList = array();
		foreach ($outageCompanyAddressRecIds as $companyAddressRecId) {
			$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
			if ($companyAddress) {
				$companyAddressList[] = $companyAddress['Company_Address'];
			}
		}
		return $companyAddressList;
	}
	
	function getOutageDuration($outageId) {
		$outage = $this->getOutageById($outageId);
		$timeZonesObj = new CMS_Time_Zone;
		if (!$outage) {
			return;
		}
		if (!$outage['end_time']) {
			return;
		}
		$timeZone = $timeZonesObj->getTimeZoneById($outage['time_zone_id']);
		if (!$timeZone) {
			return;
		}
		if ($outage['type_id'] == 1) {
			// Planned - show actual timestamp as it was entered by a human and offset depending on time zone (ha!)
			$endTime = $outage['end_time'];
		} else {
			// Unplanned - show offset timestamp because it was automatically stamped by the system at AEST
			$endTime = ($outage['end_time'] + ($timeZone['variance'] * 60 * 60));
		}
		return (($endTime - $outage['start_time']) / (60 * 60));
	}

	/*************** PDO ******************/
	function getOutageIdsByDateRange($startDate, $endDate, $includeXyzTestCompany = false) {
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL query
			$query = "select id
				from outage
				where start_time >= :startDate
				and start_time <= :endDate";
			if (!$includeXyzTestCompany) {
				$query .= "";	// ************** and Company_RecID != '19297'
			}
			$query .= "
				order by id";
			
			// Prepare and execute
			$stmt = $conn->prepare($query);
			if ($startDate) {
				$stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
			}
			if ($endDate) {
				$stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
			}
			// return $query;
			$stmt->execute();
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
		
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	/*************** PDO ******************/
	function getTicketByOutage($outageId){
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('select SR_Service_RecID from outage where id = :outageId limit 1');
			$stmt->execute(array('outageId'=>$outageId));
			
			// Fetch result
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// Return result
			return $row;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
		
	}

	// get Scheduled (Planned) outages
	function getScheduledOutages($startTime){
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('select id from outage where status_id = 2 and start_time <= :startTime and deleted is NULL' );
			$stmt->execute(array('startTime'=>$startTime));
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
	// get In Progress (Planned) outages prior to given time
	function getInprogressOutages($now, $endTime){
		// Load config for database credentials
		$config = new Config;
		
		try{
			// Connet to database
			$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// SQL statement
			$stmt = $conn->prepare('select id from outage where status_id = 3 and end_time between :now and :endTime and deleted is NULL' );
			$stmt->execute(array('endTime'=>$endTime, 'now'=>$now));
			
			// Store result in array
			$outageIds = array();
			while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
				$outageIds[] = $row['id'];
			}

			// Return result
			return $outageIds;
			
		} catch(PDOException $e){
			// return $e->getMessage();
			return;
		}
	}
	
}

