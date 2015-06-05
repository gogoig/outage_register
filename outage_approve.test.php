<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


include('new/ad_auth.inc.php');
require_once('new/CMS.inc.php');
require_once('new/CMS_Outage_Site_Company_Preference.class.php');
require_once('new/CMS_Outage_Site_Company_Select.class.php');
require_once('new/CMS_Outage_test.class.php');	// TEST..

$htmlPage = new Html_Page;
$outagesObj = new CMS_Outage_test;

$htmlPage->title = 'Approve Outage';

$outageId = $_REQUEST['id'];
if (!empty($outageId)) {
	$outage = $outagesObj->getOutageById($outageId);
	if (!$outage) {
		$htmlPage->displayError('Could not create the Outage object.');
		$htmlPage->outputFooter();
		exit();
	}
	
	// Check if outage is pending approval
	if($outage['status_id'] == 5) {
		//update the status
		if ($outage['type_id'] == 1) {
			$data = array(
				'status_id' => '2', // 'Scheduled' for Planned Outages
			);
		} else {
			$data = array(
				'status_id' => '3', // 'In Progress' for Unplanned Outages
			);
		}
		$result = $outagesObj->update($outageId, $data);
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not update outage.');
			$htmlPage->outputFooter();
			exit();
		}

		// Send out internal notification
		$result = $outagesObj->sendInternalOutageNotification($outageId);
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not send notification.');
			$htmlPage->outputFooter();
			exit();
		}
		
		// send email to board members
		$result = $outagesObj->notifyBoardMembers($outage['id']);
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not send notification to board members.');
			$htmlPage->outputFooter();
			exit();
		}

		/*if ($outage['type_id'] == 2) {
			//update the status for Unplanned
			$data = array(
				'status_id' => '3', // 'In Progress'
			);
			$result = $outagesObj->update($outageId, $data);	
			if (!$result) {
				$htmlPage->outputHeader();
				$htmlPage->displayError('Could not update outage.');
				$htmlPage->outputFooter();
				exit();
			}
		}*/

		// $recipients = $outagesObj->getRecipientsByOutage($outageId);
		// $recpInfo = '';
		// foreach($recipients as $recp){
			// $recpInfo .= $recp . '<br />' . "\n";
		// }
		$htmlPage->outputHeader();
		// if ($recpInfo != '') {
			// $htmlPage->displayInformation('Outage ' . $outageId . ' has been approved and sent to the following addresses:<br />' . $recpInfo);
		// } else {
			// $htmlPage->displayInformation('Outage ' . $outageId . ' has been approved. There were no recipientsrecipients selected for notification.');
			$htmlPage->displayInformation('Outage ' . $outageId . ' has been approved. Notification has been sent to March IT Technical Services.');
		// }
		$htmlPage->outputFooter();

	} else {
		$htmlPage->outputHeader();
		$htmlPage->displayInformation('Outage ' . $outageId . ' has already been approved.');
		$htmlPage->outputFooter();
	}
} else {
	$htmlPage->outputHeader();
	$htmlPage->displayError('Unspecified Outage ID.');
	$htmlPage->outputFooter();
	exit();
}