<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('new/ad_auth.inc.php');
require_once('new/CMS.inc.php');
require_once('new/CMS_Outage_Site_Company_Preference.class.php');
require_once('new/CMS_Outage_Site_Company_Select.class.php');
require_once('new/CMS_Outage_Recipient_Multi_Clients.class.php');
require_once('new/CMS_Outage_Site_Select.class.php');
require_once('new/CMS_Outage_test.class.php');	// TEST..

$htmlPage = new Html_Page;
$outagesObj = new CMS_Outage_test;
$updatesObj = new CMS_Outage_Update;

$companyAddressObj = new CW_Company_Address;
$outageRecipientsObj = new CMS_Outage_Recipient;
$outageRecipientsMultiClientsObj = new CMS_Outage_Recipient_Multi_Clients;
$outageSiteCompanyPrefObj = new CMS_Outage_Site_Company_Preference;
$outageSiteCompanySelectObj = new CMS_Outage_Site_Company_Select;
$outageSiteSelectsObj = new CMS_Outage_Site_Select;

$htmlPage->title = 'Approve Outage Update';

$updateId = $_REQUEST['updateId'];
$outageId = $_REQUEST['outageId'];
if (!empty($outageId)) {
	$outage = $outagesObj->getOutageById($outageId);
	if (!$outage) {
		$htmlPage->displayError('Could not create the Outage object.');
		$htmlPage->outputFooter();
		exit();
	}
	
	// Check outage status
	if($outage['status_id'] == 2 || $outage['status_id'] == 3 || $outage['status_id'] == 4) {	// updates possible only on open status
		
		// Check is update has already been approved
		$update = $updatesObj->getUpdateById($updateId);
		if ($update['approved']=='1') {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Outage update has already been approved.');
			$htmlPage->outputFooter();
			exit();
		}
		
		// Update Approved status
		$data = array(
			'approved' => '1',
		);
		$result = $updatesObj->update($updateId, $data);
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not update outage.');
			$htmlPage->outputFooter();
			exit();
		}

		$data = array();
		// update status
		if($outage['status_id']==4){
			$data['status_id'] = 7;		// Closed
			$result = $outagesObj->update($outageId, $data);
			if (!$result) {
				$htmlPage->displayError('Could not update Outage status.');
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
		}
		
		// Update Outage with Resolution details
		if($update['resolution']==1){
			$data['status_id'] = 4;		// Resolution
			$data['updated'] = time();
			$data['updated_by'] = $update['updated_by'];
			$data['end_time'] = $update['end_time'];
			$data['cause_and_action'] = $update['cause_and_action'];
			$data['resolution_summary'] = $update['resolution_summary'];
			
			$result = $outagesObj->update($outageId, $data);
			if (!$result) {
				$htmlPage->displayError('Could not update the Outage object.');
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
		}
		$data = array();
		if($update['cancelled']==1){
			$data['status_id'] = 6;		// Cancelled
			$result = $outagesObj->update($outageId, $data);
			if (!$result) {
				$htmlPage->displayError('Could not update the Outage object.');
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
		}
		
		// Send email to multiple clients
		if($outage['Company_RecID']==19307){	//	March IT
			$recpStr = '';
			$notifiedClients = $outageRecipientsMultiClientsObj->getNotifiedClientsByOutageId($outage['id']);
			// $recpArray = array();
			foreach($notifiedClients as $notifiedClient){
				// $recipient = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
				// $site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
				
				// $recipients = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
				$site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
				// foreach($recipients as $recipient){
					// if($recipient['site_pref_id']==$site['Company_Address'] && !in_array($recipient['to_address'], $recpArray)){
						// $recpArray[] = $recipient['to_address'];
						$result = $outagesObj->sendOutageNotification($outage['id'],  $site['Description'], $notifiedClient['email_address'], 'Update');
						if (!$result) {
							$htmlPage->displayError('Could not send the notification email.');
							displayForm();
							$htmlPage->outputFooter();
							exit();
						}
						$recpStr .= '<br/>' . $site['Description'] . ' - ' . $notifiedClient['email_address'];
					// }
				// }
				
			}
		} else{
			$recpStr = '';
			$recipients = $outageRecipientsObj->getoutageRecipients($outage['id']);
			foreach($recipients as $recipient){
				$sites = $outageSiteSelectsObj->getCompanyAddressRecIds($outage['id']);	// include all affected sites
				$affectedSites = array();
				foreach($sites as $key=>$val){
					$siteArr = $companyAddressObj->getCompanyAddressByRecId($val);
					array_push($affectedSites, $siteArr['Description']);
				}
				$affectedSitesStr = implode(',', $affectedSites);
				$result = $outagesObj->sendOutageNotification($outage['id'], $affectedSitesStr, $recipient, 'Update');
				// echo $result;
				
				// $site = $outageSiteSelectsObj->getCompanyAddressRecIds($outage['id']);
				// $result = $outagesObj->sendOutageNotification($outage['id'],  $site, $recipient);
				if (!$result) {
					$htmlPage->displayError('Could not send the update email.');
					displayForm();
					$htmlPage->outputFooter();
					exit();
				}
				$recpStr .= '<br/>' . $affectedSitesStr . ' - ' . $recipient;
			}
		}
		
		
		// send email to technical services(internal)
		$result = $outagesObj->sendInternalOutageNotification($outage['id'], 'Update');
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not send internal notification.');
			$htmlPage->outputFooter();
			exit();
		}
		
		// send email to board members
		$result = $outagesObj->notifyBoardMembers($outage['id'], 'Update');
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not send notification to board members.');
			$htmlPage->outputFooter();
			exit();
		}
		
		$htmlPage->outputHeader();
		$htmlPage->displayInformation('Outage ' . $outageId . ' update has been approved. Notification(s) have been sent to associated clients.');
		$htmlPage->outputFooter();

	} else {
		$htmlPage->outputHeader();
		$htmlPage->displayInformation('Outage ' . $outageId . ' cannot be updated.');
		$htmlPage->outputFooter();
	}
} else {
	$htmlPage->outputHeader();
	$htmlPage->displayError('Unspecified Outage ID.');
	$htmlPage->outputFooter();
	exit();
}



?>