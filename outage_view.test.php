<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=ISO-8859-1');	// Encoding to display special characters

include('new/ad_auth.inc.php');
require_once('new/CMS.inc.php');
require_once('new/CMS_Outage_Site_Company_Preference.class.php');
require_once('new/CMS_Outage_Site_Company_Select.class.php');
require_once('new/CMS_Outage_Recipient_Multi_Clients.class.php');
require_once('new/CMS_Outage_Site_Select.class.php');
require_once('new/CMS_Outage_test.class.php');	// TEST..

$htmlPage = new Html_Page;	
$outagesObj = new CMS_Outage_test;

$updateTemplatesObj = new CMS_Outage_Update_Template;
$servicesObj = new CMS_Outage_Service;
$outageUpdatesObj = new CMS_Outage_Update;
$companiesObj = new CW_Company;
$companyAddressObj = new CW_Company_Address;
$outageSitePrefObj = new CMS_Outage_Site_Preference;
$outageRecipientsObj = new CMS_Outage_Recipient;
$outageRecipientsMultiClientsObj = new CMS_Outage_Recipient_Multi_Clients;
$outageSiteCompanyPrefObj = new CMS_Outage_Site_Company_Preference;
$outageSiteCompanySelectObj = new CMS_Outage_Site_Company_Select;
$outageSiteSelectsObj = new CMS_Outage_Site_Select;
$membersObj = new CW_Member;

$outageStatusObj = new CMS_Outage_Status;

$headingWidth = 135;

$form = $htmlPage->initialiseFormData(array(), array('id', 'action', 'step', 'resolution', 'updateTemplateId', 'summaryTemplate', 'summary', 'updateTemplateDescription', 'causeAndAction', 'resolutionSummary', 'companyRecId', 'critical', 'resolutionTime', 'chkResolutionTime', 'clients', 'chkOtherClient', 'otherClient', 'memberRecId'));

$fonts = array();
foreach ($form as $key => $value) {
	$fonts[$key] = 'thBlack';
}

$htmlPage->title = 'View Outage';
$htmlPage->outputHeader();

// Load outage object //
$outage = $outagesObj->getOutageById($form['id']);
if (!$outage) {
	$htmlPage->displayError('Could not create the Outage object.');
	$htmlPage->outputFooter();
	exit();
}
// print_r($outage);
// exit();

// Rediect to editable form
if($outage['status_id'] == 5){
	//echo $outage['status_id'];
	$htmlPage->redirect = '/cgi/new/Service_Desk/outage_new.test.php?id=' .$outage['id']; // Redirect to editable form 
	$htmlPage->outputHeader();
	exit();
}

// Build description of affected services
$serviceDescription = '';
if ($outage['service_id']) {
	$service = $servicesObj->getServiceById($outage['service_id']);
	if ($service) {
		$serviceDescription = $service['title'];
	}
} else {
	$serviceDescription = $outage['service_other'];
}
if (substr($serviceDescription, -6) != 'service') {
	$serviceDescription = $serviceDescription .= ' service';
}
$client_notified = 0;

outputJavascript();

if ($form['step'] == 'create') {
	
	// Check that outage hasn't been closed (possibly by another user) since the page was loaded
	// Yes, there was an incident...
	if ($outage['status_id'] == 7) {
		$htmlPage->displayError('The outage has been closed.');
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	
	// Check if the outage has been cancelled
	if ($outage['status_id'] == 6) {
		$htmlPage->displayError('The outage has been cancelled.');
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	
	// echo '<pre>';
	// print_r($_REQUEST);
	// echo '</pre>';
	// exit();
		
	$missingFields = validateForm();
	if (!empty($missingFields)) {
		// highlight missing
		$fonts = array();
		foreach ($form as $key => $value) {
			if (in_array($key, $missingFields)) {
				$fonts[$key] = 'thRed';
			}
			else {
				$fonts[$key] = 'thBlack';
			}
		}
		// print_r($missingFields);
		$htmlPage->displayError('One or more required fields contain invalid data or have been left blank.');
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	else {
	
		// save outage update
		$data = array(
			'outage_id' => $outage['id'],
			'description' => $form['summary'],
			'created' => time(),
			'created_by' => $_SESSION['user']['username'],
		);
		/*if ($outage['type_id'] == 1) {
			$statusId = 2; // 'Scheduled' for planned outages
		} else{
			$statusId = 3; // 'In Progress'
		}*/
		
		$statusId = $outage['status_id'];
		if ($form['action']=='resolution') {
			$data['resolution'] = 1;
			$data['end_time'] = ($form['chkResolutionTime']=='') ? strtotime($form['resolutionTime']) : time();
			$data['cause_and_action'] = $form['causeAndAction'];
			$data['resolution_summary'] = $form['resolutionSummary'];
			$data['updated_by'] = $_SESSION['user']['username'];
			$statusId = 4; // Resolved
		}
		
		if ($form['action']=='cancel') {
			$data['cancelled'] = 1;
			$statusId = 6; // Cancelled
		}
		
		if($form['action']=='report'){
			$data['report'] = 1;
			$data['end_time'] = ($form['chkResolutionTime']=='') ? strtotime($form['resolutionTime']) : time();
			$data['resolution_summary'] = $form['resolutionSummary'];
			$data['updated_by'] = $_SESSION['user']['username'];
			$statusId = 7; // Closed
		}
		
		if($form['action']=='notify'){
			$recpStr = '';
			// Save selected clients
			$clientData = array();
			if(!empty($form['clients'])){
				foreach ($form['clients'] as $recipient) {
					$recipient = explode(',', $recipient);
					if(sizeof($recipient)>1){	// March IT owned outages					
						$clientData[] = array(
							'outage_id' => $outage['id'],
							'email_address' => $recipient[2],
							'company_recId' => $recipient[0],
							'company_address_recId' => $recipient[1],
						);
					} else{
						$clientData[] = array(
							'outage_id' => $outage['id'],
							'email_address' => $form['otherClient'],
						);
					}
					// $recpStr .= '<br/>' . $recipient[2];
				}
			}
			if($form['chkOtherClient']){
				$clientData[] = array(
					'outage_id' => $outage['id'],
					'email_address' => $form['otherClient'],
				);
				// $recpStr .= '<br/>' . $form['otherClient'];
			}
			
			// print_r($form['clients']);
			// exit();
			
			// Update Outage Recipients
			foreach($clientData as $key => $val){
				$data = array(
					'outage_id' => $val['outage_id'],
					'email_address' => $val['email_address'],
					'created' => time(),
					'created_by' =>  $_SESSION['user']['username'],
				);
				$result = $outageRecipientsObj->create($data);
				if (!$result) {
					$htmlPage->displayError('Could not save the Outage Recipient object.');
					outputJavascript();
					displayForm();
					$htmlPage->outputFooter();
					exit();
				}
				// for multi clients 
				if(isset($val['company_recId'])){
					$data = array(
						'outage_id' => $val['outage_id'],
						'Company_RecId' => $val['company_recId'],
						'site_pref_id' => $val['company_address_recId'],
						'email_address' => $val['email_address'],
					);
					$result = $outageRecipientsMultiClientsObj->create($data);
					if (!$result) {
						$htmlPage->displayError('Could not save the Outage Recipient object.');
						outputJavascript();
						displayForm();
						$htmlPage->outputFooter();
						exit();
					}
				}
			}
			
			// Send notification to other email
			if($form['chkOtherClient']){
				$sites = $outageSiteSelectsObj->getCompanyAddressRecIds($outage['id']);
				$affectedSites = array();
				foreach($sites as $key=>$val){
					$siteArr = $companyAddressObj->getCompanyAddressByRecId($val);
					array_push($affectedSites, $siteArr['Description']);
				}
				$affectedSitesStr = implode(',', $affectedSites);
				$result = $outagesObj->sendOutageNotification($outage['id'], $affectedSitesStr, $form['otherClient']);
				if (!$result) {
					$htmlPage->displayError('Could not send notification.');
					$htmlPage->outputFooter();
					exit(); 
				}
				$recpStr .= '<br/>' . $affectedSitesStr . ' - ' . $form['otherClient'];
				// if($form['clients']){
					// foreach ($form['clients'] as $recipient) {
						// $recipient = explode(',', $recipient);
						// $site = $companyAddressObj->getCompanyAddressByRecId($recipient[1]);
						// $result = $outagesObj->sendOutageNotification($outage['id'], $site['Description'], $recipient[2]);	// affected client
						// if (!$result) {
							// $htmlPage->displayError('Could not send notification.');
							// $htmlPage->outputFooter();
							// exit(); 
						// }
					// }
				// }
			}
			
			// send email to clients
			if($form['clients']){
				foreach ($form['clients'] as $recipient) {
					$recipient = explode(',', $recipient);
					
					// if(sizeof($recipient)>1){	// March IT owned outages
						$site = $companyAddressObj->getCompanyAddressByRecId($recipient[1]);
						$result = $outagesObj->sendOutageNotification($outage['id'], $site['Description'], $recipient[2]);	// affected client
						if (!$result) {
							$htmlPage->displayError('Could not send notification.');
							$htmlPage->outputFooter();
							exit(); 
						}
						$recpStr .= '<br/>' . $site['Description'] . ' - ' . $recipient[2];
					// } else{
						// $result = $outagesObj->sendOutageNotification($outage['id'], $site['Description'], $recipient[0]);
						// if (!$result) {
							// $htmlPage->displayError('Could not send notification.');
							// $htmlPage->outputFooter();
							// exit(); 
						// }
						// $recpStr .= '<br/>' . $site['Description'] . ' - ' . $recipient[0];
					// }
				}
			}
			$client_notified=1;
			
			// send email to technical services (internal)
			/*$result = $outagesObj->sendInternalOutageNotification($outage['id']);
			if (!$result) {
				$htmlPage->outputHeader();
				$htmlPage->displayError('Could not send internal notification.');
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
			}*/

			// Update outage status
			// $statusId = 7; // 'Client Notification Sent'
			$data = array(
				'status_id' => $statusId,
				'client_notified' => 1,
				'updated' => time(),
				'updated_by' => $_SESSION['user']['username'],
			);
			$result = $outagesObj->update($outage['id'], $data);
			if (!$result) {
				$htmlPage->displayError('Could not update the Outage object.');
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
			
			$htmlPage->displayInformation('Outage notification has been sent to the following:<br/> ' . $recpStr);
			outputJavascript();
			displayForm();
			$htmlPage->outputFooter();
			exit();

		}
		
		// Delete unapproved updates for Outage
		$result = $outagesObj->deleteUnapprovedUpdate($outage['id']);
		if(!$result){
			$htmlPage->displayError('Could not delete unapproved updates for Outage.');
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
		
		// Create update record
		$updateId = $outageUpdatesObj->create($data);
		if (!$updateId) {
			$htmlPage->displayError('Could not save the Outage Update object.');
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
	
		// Send Update Approval Request
		$result = $outagesObj->sendUpdateApprovalRequest($outage['id'], $form['memberRecId'], $updateId);
		if (!$result) {
			$htmlPage->displayError('Could not send update request.');
			displayForm();
			$htmlPage->outputFooter();
			exit();
		} else{
			$htmlPage->displayInformation('Update request sent to ' . $form['memberRecId']);
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
		
		/*// Update outage status
		$data = array(
			'status_id' => $statusId,
			'updated' => time(),
			'updated_by' => $_SESSION['user']['username'],
		);
		if ($form['action']=='resolution') {
			$data['end_time'] = ($form['chkResolutionTime']=='') ? strtotime($form['resolutionTime']) : time();		// Change to user input if selected
			$data['cause_and_action'] = $form['causeAndAction'];
			$data['resolution_summary'] = $form['resolutionSummary'];
		}
		
		$result = $outagesObj->update($outage['id'], $data);
		if (!$result) {
			$htmlPage->displayError('Could not update the Outage object.');
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
		
		
		
		// Re-load outage object
		$outage = $outagesObj->getOutageById($outage['id']);
		if (!$outage) {
			$htmlPage->displayError('Could not create the Outage object.');
			$htmlPage->outputFooter();
			exit();
		}
		
		// Send email to multiple clients
		if($outage['Company_RecID']==19307){	//	March IT
			$recpStr = '';
			$notifiedClients = $outageRecipientsMultiClientsObj->getNotifiedClientsByOutageId($outage['id']);
			foreach($notifiedClients as $notifiedClient){
				$recipient = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
				$site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
				$result = $outagesObj->sendOutageNotification($outage['id'],  $site['Description'], $recipient['to_address'], 'Update');
				if (!$result) {
					$htmlPage->displayError('Could not send the notification email.');
					displayForm();
					$htmlPage->outputFooter();
					exit();
				}
				$recpStr .= '<br/>' . $site['Description'] . ' - ' . $recipient['to_address'];
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
		
		if($recpStr == ''){
			$htmlPage->displayInformation('Outage update has been recorded.');
		} else{
			$htmlPage->displayInformation('Outage update has been sent to the following:<br/> ' . $recpStr);
		}
		outputJavascript();
		displayForm();
		if ($outage['status_id'] == 4 ) {
			displayResolutionSummaryForm();
		}
		$htmlPage->outputFooter();
		exit();*/

	}
		
}

if ($form['step'] == 'updateResolutionSummary') {
	
	$data = array(
		'resolution_summary' => $form['resolutionSummary'],
	);
	$result = $outagesObj->update($outage['id'], $data);
	if (!$result) {
		$htmlPage->displayError('Could not update the Outage object.');
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	// Re-load outage object
	$outage = $outagesObj->getOutageById($outage['id']);
	if (!$outage) {
		$htmlPage->displayError('Could not create the Outage object.');
		$htmlPage->outputFooter();
		exit();
	}
}

displayForm();
// if ($outage['status_id'] == 4 ) {
	// displayResolutionSummaryForm();
// }
$htmlPage->outputFooter();

function displayResolutionSummaryForm() {
	global $outage, $form, $headingWidth;
	if (empty($form['resolutionSummary'])) {
		$form['resolutionSummary'] = $outage['resolution_summary'];
	}
	echo "
	<br />
	<h1>Issue and Resolution Summary</h1>
	<form action='' method='post'>
		<input type='hidden' name='step' value='updateResolutionSummary' />
		<br />
		<textarea id='resolutionSummary' name='resolutionSummary' style='width: 720px; height: 100px;' maxlength='1023'>{$form['resolutionSummary']}</textarea>
		<br />
		<br />
		<input type='submit' value='Submit' />
	</form>";
}

function validateForm() {
	global $form, $outage;
	$missingFields = array();
	if($form['action']=='notify'){
		if(empty($form['clients']) && !$form['chkOtherClient'] ){
			$missingFields[] = 'clients';
		}
		if($form['chkOtherClient'] && !$form['otherClient']){
			$missingFields[] = 'clients';
		}
	}
	if($form['action']=='update'){
		if(empty($form['updateTemplateId'])){
			$missingFields[] = 'updateTemplateId';
		}
		if(empty($form['summary'])){
			$missingFields[] = 'summary';
		}
		if(empty($form['memberRecId'])){
			$missingFields[] = 'memberRecId';
		}
	}
	if ($form['action']=='resolution'){
		// if(empty($form['causeAndAction'])) {
			// $missingFields[] = 'causeAndAction';
		// }
		// if(empty($form['resolutionSummary'])) {
			// $missingFields[] = 'resolutionSummary';
		// }
		if(empty($form['summary'])) {
			$missingFields[] = 'summary';
		}
		if($form['chkResolutionTime']=='' && empty($form['resolutionTime'])) {
			$missingFields[] = 'resolutionTime';
		}
		if(empty($form['memberRecId'])){
			$missingFields[] = 'memberRecId';
		}
	}
	if($form['action']=='cancel'){
		if(empty($form['summary'])){
			$missingFields[] = 'summary';
		}
		if(empty($form['memberRecId'])){
			$missingFields[] = 'memberRecId';
		}
	}
	if($form['action']=='report'){
		if(empty($form['summary'])){
			$missingFields[] = 'summary';
		}
		if(empty($form['memberRecId'])){
			$missingFields[] = 'memberRecId';
		}
	}
	// if(!($form['resolution'] || $form['cancelOutageChk'])){
		// $missingFields[] = 'resolution';
		// $missingFields[] = 'cancelOutage';
	// }
	return $missingFields;
}

function outputJavascript() {
	global $serviceDescription;
	echo "
	<script>
	
	$(document).ready(function(){
		// $('#summary').css('display', 'none');
		// if($('#rdoNotify').attr('checked')){
			$('#updateDescriptionInput').css('display', 'none');
			$('#customerFacingUpdate').css('display', 'none');
		// }
		getActionFields();
	});
	
	$(function() {
		$('#resolutionTime').datetimepicker({
			timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				// updateSummary();
			}
		});
	});
	
	function updateUpdateTemplateDescription(updateTemplateId) {
		if (updateTemplateId == '') {
			document.getElementById('updateTemplateDescription').value = '[update_description] ';
			updateSummary();
			return;
		}
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			var xmlhttp=new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			var xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
		}
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				document.getElementById('loading-summary').innerHTML = '';
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('updateTemplateDescription').value = x[0].getElementsByTagName('description')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		document.getElementById('loading-summary').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageUpdateTemplateById.php?id='+updateTemplateId,true);
		xmlhttp.send();
	}

	function updateSummary() {
		var updateTemplateDescription = document.getElementById('updateTemplateDescription').value;
		if (updateTemplateDescription == '') {
			updateTemplateDescription = '[update_description] ';
		}
		var summary = '';
		if ($('#rdoResolution').is(':checked')) {
			summary = 'The outage is now resolved. The " . addslashes($serviceDescription) . " has been restored and is now working. This notice will be updated within 5 business days with further details of the incident including the root cause and a plan for preventative works where necessary.';
		} else if($('#rdoCancel').is(':checked')){
			summary = 'This outage has been cancelled. Please disregard all communication with regards to this outage.';
		} else if($('#rdoReport').is(':checked')){
			summary = \"The root cause of the outage has been identified as {a loss of power to site causing communications equipment to go offline/transmission interference due to inclement weather in the region causing degradation of the communications signal/a configuration issue causing malfunction of a communications device/a hardware fault causing communications equipment to cease functioning/a fault with March IT's upstream provider resulting in a  loss of connection between March IT's telecommunications network and the greater internet}. The outage was resolved by {reconfiguration of a communications device/power-cycling a communications device/physical replacement a communications device}. To avoid reoccurrence of the same issue in future {proposed actions} will be undertaken {on or by date}.\";
		}
		else {
			summary = updateTemplateDescription + ' We will update this notification with more information as soon as it becomes available.';
		}
		document.getElementById('summary').innerHTML = summary;
		document.getElementById('summary').value = summary;
	}

	function confirmAction(e){
		if($('#rdoCancel').is(':checked')){
			if(confirm('Are you sure you want to cancel this outage?')){
				$('#updateForm').submit();
			}
		} else{
			$('#updateForm').submit();
		}
	}
	
	// Update fields as per action selected
	function getActionFields(){
		if($('#rdoUpdate').is(':checked')){
			$('#updateDescriptionInput').css('display', 'block');
			$('#resolutionSummaryInput').css('display', 'none');
			$('#resolutionSummaryInputPadding').css('display', 'block');
			$('#resolutionTimeInput').css('display', 'none');
			$('#customerFacingUpdate').css('display', 'block');
			$('#requestApproval').css('display', 'block');
			$('#client_list').css('display', 'none');
			updateSummary();
		}
		if($('#rdoResolution').is(':checked')){
			$('#updateDescriptionInput').css('display', 'none');
			//$('#resolutionSummaryInput').css('display', 'block');
			$('#resolutionSummaryInputPadding').css('display', 'none');
			$('#resolutionTimeInput').css('display', 'block');
			$('#customerFacingUpdate').css('display', 'block');
			$('#requestApproval').css('display', 'block');
			$('#client_list').css('display', 'none');
			updateSummary();
		}
		if($('#rdoCancel').is(':checked')){
			$('#updateDescriptionInput').css('display', 'none');
			$('#resolutionSummaryInput').css('display', 'none');
			$('#resolutionSummaryInputPadding').css('display', 'block');
			$('#resolutionTimeInput').css('display', 'none');
			$('#customerFacingUpdate').css('display', 'block');
			$('#requestApproval').css('display', 'block');
			$('#client_list').css('display', 'none');
			updateSummary();
		}
		if($('#rdoNotify').is(':checked')){
			$('#updateDescriptionInput').css('display', 'none');
			$('#resolutionSummaryInput').css('display', 'none');
			$('#resolutionSummaryInputPadding').css('display', 'block');
			$('#resolutionTimeInput').css('display', 'none');
			$('#customerFacingUpdate').css('display', 'none');
			$('#requestApproval').css('display', 'none');
			$('#client_list').css('display', 'block');
			// updateSummary();
		}
		if($('#rdoReport').is(':checked')){
			$('#updateDescriptionInput').css('display', 'none');
			//$('#resolutionSummaryInput').css('display', 'block');
			$('#resolutionSummaryInputPadding').css('display', 'none');
			$('#resolutionTimeInput').css('display', 'block');
			$('#customerFacingUpdate').css('display', 'block');
			$('#requestApproval').css('display', 'block');
			$('#client_list').css('display', 'none');
			updateSummary();
		}
	}
	
	function toggleDateTimePicker(checked){
		if(!checked){
			$('#resolutionTimePicker').css('display', 'block');
		} else{
			$('#resolutionTimePicker').css('display', 'none');
		
		}
	}
	
	function toggleOtherClient(){
		if($('#chkOtherClient').attr('checked')){
			$('#otherClient').css('display', 'block');
		} else{
			$('#otherClient').css('display', 'none');
		}
	}
	
	</script>";
}

function displayForm() {
	global $outageStatusObj, $outage, $outagesObj, $outageSiteCompanyPrefObj, $outageSitePrefObj, $updateTemplatesObj, $form, $fonts, $headingWidth, $client_notified, $outageSiteCompanySelectObj, $companiesObj, $companyAddressObj, $outageRecipientsMultiClientsObj, $outageUpdatesObj, $outageRecipientsObj, $membersObj ;
	$table = $outagesObj->generateOutageTableHtmlInternal($outage['id']);
	
	// echo "<pre>";
	// print_r($form);
	// echo "</pre>";
	// $data = array(
		// 'outage_id' => '7777',
		// 'Company_Address_RecID' => '1037',
	// );
	// $test = $outageStatusObj->getStatusByTitleTest('New');
	// print_r($test);
	// echo $recipients;
	// start 1385856000
	// end 1417989138
	// exit();
	
	// var_dump($_SESSION);
	
	// Outage Table
	echo "
	<div style='width: 960px;'>
		$table
	</div>";
	
	// Audit Trail
	echo "
	<br/>
	<h1>Audit Trail</h1>
	<table style='background-color:lightgray; width:960px; '>";	
	// 
	echo "<tr><td style='width:200px; vertical-align:top;'>" . date('j F, Y, g:i a', $outage['created']) . "</td><td  style='padding:2px'><b>Created by " . $outage['created_by'] . ":</b> " . $outage['summary'] . "</td></tr>";
	
	$notifiedClients = $outageRecipientsObj->getOutageRecipientsDetail($outage['id']);
	// print_r($notifiedClients);
	foreach($notifiedClients as $notifiedClient){
		// $recipient = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
		// $site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
		echo "<tr><td style='width:200px; vertical-align:top;'>" . (($notifiedClient['created']==null) ? ' - ' : date('j F, Y, g:i a', $notifiedClient['created']) ) . "</td><td  style='padding:2px'><b>Notified by ". $notifiedClient['created_by'] .":</b> " . $notifiedClient['email_address'] . "</td></tr>";
	}
	
	$outageUpdateIds = $outageUpdatesObj->getUpdateIdsByOutageId($outage['id']);
	foreach($outageUpdateIds as $outageUpdateId){
		$update = $outageUpdatesObj->getUpdateById($outageUpdateId);
		echo "
		<tr><td style='width:200px; vertical-align:top;'>" . date('j F, Y, g:i a', $update['created']) . " </td><td style='padding:2px'><b>" . ( ($update['resolution']==0) ? ($update['report']==1 ? "Incident Summary " : "Update ") : "Resolution ") . " by " . $update['created_by'] . ":</b> " . $update['description'] . "</td></tr>";
	}
	echo "
	</table>";
	
	if (!($outage['status_id'] == '7' || $outage['status_id'] == '6')) {
		// Not Closed or Cancelled
				
		echo "
	<br />
	<h1>Post Outage Update</h1>
	<br />
	<form id='updateForm' name='updateForm' action='' method='post'>
		<input type='hidden' name='step' value='create' />
		<input type='hidden' name='updateTemplateDescription' id='updateTemplateDescription' value='{$form['updateTemplateDescription']}' />
		<input type='hidden' name='summaryTemplate' id='summaryTemplate' value='{$form['summaryTemplate']}' />
		<input type='hidden' name='companyRecId' id='companyRecId' value='{$outage['Company_RecID']}' />
		<input type='hidden' name='critical' id='critical' value='{$outage['critical']}' />
		<table>
			<tr>
				<th class='{$fonts['action']}' style='width: {$headingWidth}px;'>Action</th>
				<td>";
		$checkedNotify = '';
		$checkedUpd = '';
		$checkedRes = '';
		$checkedCan = '';
		$checkedRep = '';
		if($form['step']==''){
				$checkedNotify = 'checked';
		} else{
			if($form['action']=='notify'){
				$checkedNotify = 'checked';
			}
			if($form['action']=='update'){
				$checkedUpd = 'checked';
			}
			if($form['action']=='resolution'){
				$checkedRes = 'checked';
			}
			if($form['action']=='cancel'){
				$checkedCan = 'checked';
			}
			if($form['action']=='report'){
				$checkedRep = 'checked';
			}
		}
		echo"
					<div>
						<input type='radio' id='rdoNotify' name='action' value='notify' $checkedNotify onclick='getActionFields();' /> Notify Client(s)<br/><br/>
						<input type='radio' id='rdoUpdate' name='action' value='update' $checkedUpd onclick='getActionFields();' /> Update<br/><br/>
						<input type='radio' id='rdoResolution' name='action' value='resolution' $checkedRes onclick='getActionFields();' /> Resolution<br/><br/>
						<input type='radio' id='rdoReport' name='action' value='report' $checkedRep onclick='getActionFields();' /> Report and Close<br/><br/>
						<input type='radio' id='rdoCancel' name='action' value='cancel' $checkedCan onclick='getActionFields();' /> Cancel
					</div>
				</td>
			</tr>
		</table>";
		
		$display = '';
		if ($form['action']=='resolution' || $form['action']=='cancel') {
			$display = 'none';
		}
		echo "
		<div id='updateDescriptionInput' style='display: {$display}'>
			<table>
				<tr>
					<th class='{$fonts['updateTemplateId']}' style='width: {$headingWidth}px;'>Update Description</th>
					<td>
						<select id='updateTemplateId' name='updateTemplateId' onchange='updateUpdateTemplateDescription(this.value);'>
							<option></option>";
		$template = 0;
		if ($form['action']=='update') {
			$template = $form['updateTemplateId'];
		}
		$updateTemplateIds = $updateTemplatesObj->getTemplateIds(0);
		foreach ($updateTemplateIds as $updateTemplateId) {
			$updateTemplate = $updateTemplatesObj->getTemplateById($updateTemplateId);
			if ($updateTemplate) {
				$selected = '';
				if ($updateTemplate['id'] == $form['updateTemplateId']) {
					$selected = 'selected';
				}
				echo "
							<option value='{$updateTemplate['id']}' $selected>{$updateTemplate['title']}</option>";
			}
		}
		echo "
							<option>Other...</option>
						</select>
					</td>
				</tr>
			</table>
		</div>";
			
		$display = 'none';
		if ($form['action'] == 'resolution') {
			$display = 'block';
		}
		$checked = 'checked';
		$display2 = 'none';
		if($form['step']!='' && $form['chkResolutionTime']==''){
			$checked='';
			$display2 = 'block';
		}
		echo "	
		<div id='resolutionTimeInput' style='display: {$display};'>
			<table>
				<tr>
					<th class='{$fonts['resolutionTime']}' style='width: {$headingWidth}px;'>Resolution Time</th>
					<td><input type='checkbox' name='chkResolutionTime' id='chkResolutionTime' $checked onclick='toggleDateTimePicker(this.checked);'> Use current timestamp
						<div  name='resolutionTimePicker' id='resolutionTimePicker' style='display:$display2'>
							<input type='text' name='resolutionTime' id='resolutionTime' value='{$form['resolutionTime']}' />
						</div>
					</td>
				</tr>
			</table>
		</div>
		
		<div id='customerFacingUpdate'>
			<table>
				<tr>
					<th class='{$fonts['summary']}' style='width: {$headingWidth}px;'>Update<br />(customer facing)</th>";
		if($form['step']==''){
			if ($form['action'] == 'resolution') {
				$form['summary'] = 'The outage is now resolved. The ' . $serviceDescription . ' has been restored and is now working.';
			} else {
				$updateTemplate = $updateTemplatesObj->getTemplateById($form['updateTemplateId']);
				if ($updateTemplate) {
					$form['summary'] = $updateTemplate['description'];
				} else {
					$form['summary'] = '[update_description] ';
				}
				$form['summary'] .= 'We will update this notification with more information as soon as it becomes available and within the next hour.';
			}
		}
		echo "
					<td><textarea id='summary' name='summary' style='width: 720px; height: 140px;' maxlength='1023'>{$form['summary']}</textarea><span id='loading-summary'></span></td>
				</tr>
			</table>
		</div>";
		$display = 'none';
		// if ($form['action'] == 'resolution') {
			// $display = 'block';
		// }
		echo "
		<div id='resolutionSummaryInput' style='display: {$display};'>
			<table>
				<tr>
					<th class='{$fonts['causeAndAction']}' style='width: {$headingWidth}px;'>Cause or Action Undertaken<br />(customer facing)</th>
					<td>
						<p style='font-style: italic;'>Write a user friendly sentence as to the cause or actions that were undertaken to achieve a resolution.</p>
						<br />
						<textarea id='causeAndAction' name='causeAndAction' style='width: 720px; height: 100px;' maxlength='1023'>{$form['causeAndAction']}</textarea>
					</td>
				</tr>
				<tr>
					<th class='{$fonts['resolutionSummary']}' style='width: {$headingWidth}px;'>Issue and Resolution<br />(internal only)</th>
					<td>
						<p style='font-style: italic;'>A description of the issue and resolution must be provided for the monthly report.  This is not emailed to users.</p>
						<br />
						<textarea id='resolutionSummary' name='resolutionSummary' style='width: 720px; height: 100px;' maxlength='1023'>{$form['resolutionSummary']}</textarea>
					</td>
				</tr>
			</table>
		</div>
		
		<div id='client_list'>
			<table>
				<tr>
					<th class='{$fonts['clients']}' style='width: {$headingWidth}px;'>Client(s)</th>
					<td>";
			
			if($outage['client_notified']!=1 && $client_notified!=1){
				
				/*if($outage['Company_RecID']==19307 || $outage['Company_RecID']==19297){	// MARCH IT and XYZ TEST COMPANY
					$clients = $outageSiteCompanySelectObj->getClientsByOutageId($outage['id']);
					foreach($clients as $client){
						$site_pref_company = $outageSiteCompanyPrefObj->getClientsById($client['outage_site_company_preference_id']);
						if($site_pref_company){
							foreach($site_pref_company as $key=>$site_pref){
								$checked = '';
								if($form['step']!='' && is_array($form['clients']) && in_array($site_pref['to_address'], $form['clients'])){
									$checked = 'checked';
								}
								echo "
					<input type='checkbox' id='clients[]' name='clients[]' $checked value='{$site_pref['to_address']}'> {$site_pref['to_address']} <br/>";
							}
						}
					}
				}
				else{
					$sites = $outagesObj->getSiteIds($outage['id']);
					print_r($sites);
					if($sites){
						foreach($sites as $key=>$siteId){
							$pref = $outageSitePrefObj->getSitePreferenceByCompanyRecId($siteId);
							if($pref){
								$checked = '';
								if($form['step']!='' && is_array($form['clients']) && in_array($pref['to_address'], $form['clients'])){
									$checked = 'checked';
								}
								echo "
						<input type='checkbox' id='clients[]' name='clients[]' $checked value='{$pref['to_address']}'> {$pref['to_address']} <br/>";
							}
						}
					}
				}*/
				
				$sites = $outagesObj->getSiteIds($outage['id']);
				if(!empty($sites)){
					$recpArray = array();
					foreach($sites as $key=>$siteId){
						if($outage['Company_RecID']==19307){ // || $outage['Company_RecID']==19297){		// MARCH IT and XYZ TEST COMPANY
							$clientsSelected = $outageSiteCompanySelectObj->getClientsByOutageId($outage['id']);
							$clients = $outageSiteCompanyPrefObj->getClientsByCompanyAddressRecId($siteId);
							foreach($clients as $key=>$val){
								foreach($clientsSelected as $keySel=>$valSel){
									if($val['Company_RecId']==$valSel['Company_RecId']){
										$checked = 'checked';
										$company = $companiesObj->getCompanyByRecId($val['Company_RecId']);
										$companyAddress = $companyAddressObj->getCompanyAddressByRecId($siteId);
										// $email = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($siteId, $company['Company_RecID']);
										
										$recipients = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($siteId, $company['Company_RecID']);
										foreach($recipients as $recipient){
											if(!in_array($recipient['to_address'], $recpArray)){
												$recpArray[] = $recipient['to_address'];
												if($recipient['site_pref_id']==$siteId){
													echo "
							<input type='checkbox' id='clients[]' name='clients[]' $checked value='{$company['Company_RecID']}, {$companyAddress['Company_Address']}, {$recipient['to_address']}'> {$company['Company_Name']} - {$companyAddress['Description']} ({$recipient['to_address']})
							<br/>";
												}
											}
										}
										
									}
								}
							}
						}
						/* else{
							// $pref = $outageSitePrefObj->getSitePreferenceByCompanyRecId($siteId);	// actually.. getSitePreferenceByCompanyAddressRecID()
							// if($pref){
								// $checked = 'checked';
								if($form['step']!='' && is_array($form['clients']) && in_array($pref['to_address'], $form['clients'])){
									$checked = 'checked';
								}
								// echo "
							// <input type='checkbox' id='clients[]' name='clients[]' $checked value='{$pref['to_address']}'> {$pref['to_address']} <br/>";
							// }
						 }*/
					}
				}
				
				// print_r($outage);
				$otherClient = '';
				$txtDisplay = 'none';
				if($outage['Company_RecID']==19307){
					$display = 'none';
					$checked = '';
				} else{
					$display = 'block';
					$checked = '';
					$otherClient = '';
					if($form['step']!=''){
						if($form['chkOtherClient']){
							$txtDisplay = 'block';
							$checked = 'checked';
							$otherClient = $form['otherClient'];
						}
					}
				}
				echo"
						<div style='display:$display'>
							<input type='checkbox' id='chkOtherClient' name='chkOtherClient' $checked onclick='toggleOtherClient();'> Other (Email ID)<br/>
							<input type='text' id='otherClient' name='otherClient' style='display:$txtDisplay' value='$otherClient'>
						</div>";
			}
			else{
				echo "
					<i>The following client(s) have been notified:</i>";
				
				##### Email Addresses for outages before Outage ID 1532 will not show due to database table restructure #####
				
				$notifiedClients = $outageRecipientsMultiClientsObj->getNotifiedClientsByOutageId($outage['id']);
				if(!empty($notifiedClients)){
					// $recpArray = array();
					foreach($notifiedClients as $notifiedClient){
						// $recipient = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
						// $site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
						// echo '<br/>' . $site['Description'] . ' - ' . $recipient['to_address'];
						
						// $recipients = $outageSiteCompanyPrefObj->getClientBySitePrefIdAndCompanyRecId($notifiedClient['site_pref_id'], $notifiedClient['Company_RecId']);
						$site = $companyAddressObj->getCompanyAddressByRecId($notifiedClient['site_pref_id']);
						// foreach($recipients as $recipient){
							// if($recipient['site_pref_id']==$site['Company_Address'] && !in_array($recipient['to_address'], $recpArray)){
								// $recpArray[] = $recipient['to_address'];
								// echo '<br/>' . $site['Description'] . ' - ' . $recipient['to_address'];
								echo '<br/>' . $site['Description'] . ' - ' . $notifiedClient['email_address'];
							// }
						// }
					}
				} else{
					$notifiedClients = $outageRecipientsObj->getOutageRecipientsDetail($outage['id']);
					foreach($notifiedClients as $notifiedClient){
						echo "<br/>" . $notifiedClient['email_address'] . "</td></tr>";
					}
				}
			
			}
			echo "
					</td>
				</tr>
			</table>
		</div>";
		
		$display = 'none';
		if ($form['action'] == 'update' || $form['action'] == 'resolution' || $form['action'] == 'cancel' ) {
			$display = 'block';
		}
		echo"
		<div id='requestApproval' style='display: {$display};'>
		<table>
			<tr>
				<th class='{$fonts['memberRecId']}' style='width: {$headingWidth}px;'>Request Approval from </th>
				<td>
					<select id='memberRecId' name='memberRecId'  onChange=''>
						<option></option>;
					";
			
		$memberRecIds = $membersObj->getMemberRecIds();
		foreach ($memberRecIds as $memberRecId) {
			$member = $membersObj->getMemberByRecId($memberRecId);
			$selected = '';
			if ($member) {
				if($form['memberRecId'] == $member['Email_Address']){
					$selected = 'selected';
				}
				if($member['Member_RecID']!='117' && $member['Manager']==1){		// 117 -> zAdmin
					// echo $member['Member_RecID'];
					echo "
							<option value='{$member['Email_Address']}' $selected>{$member['First_Name']} {$member['Last_Name']}</option>";
				}
			}
		}
		echo "
					</select>
				</td>
			</tr>
		</table>
		</div>
		<table>
			<tr>
				<td colspan='2'>
					<!-- <input type='submit' value='Submit' onclick='confirmAction(event);'/> -->
					<br/>
					<div class='' style='background-color:rgb(148,145,147); font-weight:bold; padding:4px; height:30px; width:90px; -webkit-filter: invert(100%);'><a href='#' onclick='confirmAction(event);'><div style='float:left; padding:9px; color:black'>Submit</div><div style='float:left; padding:3px;'><img id='' name='' src='/images/icons/flaticons/enter5.png' alt='Submit' title='Submit' height=20 style='margin-top:3px'></div></a></div>
				</td>
			</tr>
		</table>
	</form>";
		$display = 'block';
		if ($form['action'] == 'resolution') {
			$display = 'none';
		}
		echo "
	<div id='resolutionSummaryInputPadding' style='display: {$display};'>
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
	</div><!-- resolutionSummaryInputPadding -->";
	}

}
