<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


include('new/ad_auth.inc.php');
require_once('new/CMS.inc.php');
require_once('new/CMS_Outage_Site_Company_Preference.class.php');
require_once('new/CMS_Outage_Site_Company_Select.class.php');
require_once('new/CMS_Outage_test.class.php');	// TEST..

$htmlPage = new Html_Page;			
$companiesObj = new CW_Company;
$outageTypesObj = new CMS_Outage_Type;
$companyAddressesObj = new CW_Company_Address;
$outagePreferencesObj = new CMS_Outage_Preference;
$outagesObj = new CMS_Outage_test;
			
$outageSiteSelectsObj = new CMS_Outage_Site_Select;	
$timeZonesObj = new CMS_Time_Zone;
$outageServicesObj = new CMS_Outage_Service;
$outageReasonsObj = new CMS_Outage_Reason;
$outageAvailabilitiesObj = new CMS_Outage_Availability;
$membersObj = new CW_Member;
$outageRecipientsObj = new CMS_Outage_Recipient;
$outageSitePreferencesObj = new CMS_Outage_Site_Preference;
$outageSiteCompanyPrefObj = new CMS_Outage_Site_Company_Preference;
$outageSiteCompanySelectObj = new CMS_Outage_Site_Company_Select;

$requiredFields = array('typeId', 'timeZoneId', 'startTime', 'serviceId', 'availabilityId', 'summary', 'memberRecId', 'ticketNo');
$form = $htmlPage->initialiseFormData(array(), $requiredFields);

$requiredFieldsPlanned = array('endTime', 'reasonId');
$form = $htmlPage->initialiseFormData($form, $requiredFieldsPlanned);

$requiredFieldsUnplanned = array();
$form = $htmlPage->initialiseFormData($form, $requiredFieldsUnplanned);

$requiredConditionalOther = array('otherService', 'otherReason', 'diversionCompanyAddressRecId', 'companyAddressRecIds', 'allSitesAffected');
$form = $htmlPage->initialiseFormData($form, $requiredConditionalOther);

$formFields = array('step', 'companyRecId', 'summaryTemplate', 'availabilityDescription', 'serviceDescription', 'timeZoneDescription', 'diversionSiteDescription', 'reasonDescription', 'id', 'customSummary', 'affectedClients', 'clients', 'hardwareFailure', 'faultCaused', 'faultOwned');
$form = $htmlPage->initialiseFormData($form, $formFields);

$fonts = array();
foreach ($form as $key => $value) {
	$fonts[$key] = 'thBlack';
}

$htmlPage->title = 'Log an Outage';

if($form['id']!=''){
	// Load outage object
	$outage = $outagesObj->getOutageById($form['id']);
	if (!$outage) {
		$htmlPage->displayError('Could not create the Outage object.');
		$htmlPage->outputFooter();
		exit();
	}

	// Redirect to outage view
	if($outage['status_id'] != 5){
		//echo $outage['status_id'];
		$htmlPage->redirect = '/cgi/new/Service_Desk/outage_view.test.php?id=' .$outage['id']; // Redirect to editable form TEST..
		$htmlPage->outputHeader();
		exit();
	}
}

if ($form['step']=='create' || $form['step']=='save') {
	
	// echo "<pre>";
	// print_r($form);
	// echo "</pre>";
	// exit();
	
	// Validation
	$missingFields = validateForm();
	if($form['step']=='create' && !empty($missingFields)) {
		// highlight missing
		$fonts = array();
		foreach ($form as $key => $value) {
			if (in_array($key, $missingFields)) {
				$fonts[$key] = 'thRed';
			} else {
				$fonts[$key] = 'thBlack';
			}
		}
		$htmlPage->outputHeader();
		$htmlPage->displayError('One or more required fields contain invalid data or have been left blank.');
		outputJavascript();	
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	if($form['step']=='save' && empty($form['typeId'])){
		$fonts['typeId'] = 'thRed';
		$htmlPage->outputHeader();
		$htmlPage->displayError('Outage Type required to save record.');
		outputJavascript();
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	
	$statusId = 5;	// Approval Pending
	$data = array(
		'status_id' => $statusId,
		'created' => time(),
		'created_by' => $_SESSION['user']['username'],
		'updated' => time(),
		'updated_by' => $_SESSION['user']['username'],
	);
	$data['type_id'] = $form['typeId'];
	if($form['faultOwned']=='1'){
		$data['owned'] = 1;
		// $data['Company_RecID'] = '19307';
		// $form['companyRecId'] = '19307';
	}else if($form['faultOwned']=='0'){
		$data['owned'] = 0;
		// if(!empty($form['companyRecId'])){
			// $data['Company_RecID'] = $form['companyRecId'];
		// }
	}
	if(!empty($form['companyRecId'])){
		$data['Company_RecID'] = $form['companyRecId'];
	}
	if(!empty($form['ticketNo'])){
		$data['SR_Service_RecID'] = $form['ticketNo'];
	}
	if(!empty($form['timeZoneId'])){
		$data['time_zone_id'] = $form['timeZoneId'];
	}
	if(!empty($form['availabilityId'])){
		$data['availability_id'] = $form['availabilityId'];
	}
	if($form['customSummary']!=''){
		$data['summary'] = $form['customSummary'];
		$data['custom_summary'] = 1;
	}
	else{
		$data['custom_summary'] = 0;
		$data['summary'] = $form['summary'];
	}
	if($form['faultCaused']!=''){
		$data['responsible'] = $form['faultCaused'];
	} 
	// else if($form['faultCaused']==''){
		// $data['responsible'] = NULL;
	// }
	if($form['faultOwned']=='0' && $form['faultCaused']=='0'){
		$data['hardware_failure'] = $form['hardwareFailure'];
	}
	if (!empty($form['startTime'])) {
		$data['start_time'] = strtotime($form['startTime']);
	}
	if (!empty($form['endTime'])) {
		$data['end_time'] = strtotime($form['endTime']);
	} 
	else {
		$data['end_time'] = null;
	}
	if($form['serviceId']!=''){
		if ($form['serviceId'] != 'other') {
			$data['service_id'] = $form['serviceId'];
		} else{
			$data['service_id'] = 0;
			$data['service_other'] = $form['otherService'];
		}
	}
	if($form['reasonId']!=''){
		if ($form['reasonId'] != 'other') {
			$data['reason_id'] = $form['reasonId'];
		} else{
			$data['reason_other'] = $form['otherReason'];
		}
	}
	if ($form['serviceId'] == '5' && $form['diversionCompanyAddressRecId']!='') {
		$data['diversion_Company_Address_RecID'] = $form['diversionCompanyAddressRecId'];
	}
	
	
	if($form['id']==''){
		// Create Outage object
		$outageId = $outagesObj->create($data);
		if (!$outageId) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not create the Outage object.');
			outputJavascript();	
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
		$form['id'] = $outageId;
	}else{
		// Update Outage object
		// $outageId = $form['id'];
		$result = $outagesObj->update($form['id'], $data);
		// echo $result;
		// exit();
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not save the Outage object.');
			outputJavascript();	
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
	}
	
	// Affected sites
	$result = $outageSiteSelectsObj->removeCurrentSites($form['id']); // remove current affected sites
	if(!$result){
		$htmlPage->outputHeader();
		$htmlPage->displayError('Could not delete current Outage Sites.');
		outputJavascript();	
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	if ($form['allSitesAffected']) {
		$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($form['companyRecId']);
		foreach ($companyAddressRecIds as $companyAddressRecId) {
			if (!in_array($companyAddressRecId, $outagesObj->blacklistCompanyAddressRecIds)) {
				$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
				if ($companyAddress) {
					$data = array(
						'outage_id' => $form['id'],
						'Company_Address_RecID' => $companyAddress['Company_Address'],
					);
					$result = $outageSiteSelectsObj->create($data);
					if (!$result) {
						$htmlPage->outputHeader();
						$htmlPage->displayError('Could not save the Outage Site object.');
						outputJavascript();	
						displayForm();
						$htmlPage->outputFooter();
						exit();
					}
				}
			}
		}
	} else if($form['companyAddressRecIds']){
		foreach ($form['companyAddressRecIds'] as $companyAddressRecId) {
			$data = array(
				'outage_id' => $form['id'],
				'Company_Address_RecID' => $companyAddressRecId,
			);
			$result = $outageSiteSelectsObj->create($data);
			if (!$result) {
				$htmlPage->outputHeader();
				$htmlPage->displayError('Could not save the Outage Site object.');
				outputJavascript();	
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
		}
	}
	
	// Affected Clients
	$result = $outageSiteCompanySelectObj->delete($form['id']);
	if (!$result) {
		$htmlPage->outputHeader();
		$htmlPage->displayError('Could not delete old Affected Clients object.');
		outputJavascript();	
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	if($form['faultOwned']=='1' && $form['affectedClients']){
		foreach($form['affectedClients'] as $key => $affectedClient){
			// $outageSiteCompanyPrefId = $outageSiteCompanyPrefObj->getIdBySitePrefIdAndCompanyRecId($sitePrefId, $affectedClient);
			$data = array(
				'outage_id' => $form['id'],
				'Company_RecId' => $affectedClient,
				// 'outage_site_company_preference_id' => $outageSiteCompanyPrefId,
			);
			$result = $outageSiteCompanySelectObj->create($data);
			if (!$result) {
				$htmlPage->outputHeader();
				$htmlPage->displayError('Could not save Affected Clients Object.');
				outputJavascript();	
				displayForm();
				$htmlPage->outputFooter();
				exit();
			}
		}
	}
	
	if($form['step']=='save'){
		$htmlPage->outputHeader();
		$htmlPage->displayInformation("Outage " .$form['id'] ." was successfully saved.");
		outputJavascript();	
		displayForm();
		$htmlPage->outputFooter();
		exit();
	}
	else if($form['step']=='create'){
		// Send approval request
		$result = $outagesObj->sendApprovalRequest($form['id'], $form['memberRecId'], '', '');
		
		if (!$result) {
			$htmlPage->outputHeader();
			$htmlPage->displayError('Could not send approval request.');
			outputJavascript();	
			displayForm();
			$htmlPage->outputFooter();
			exit();
		}
		
		$htmlPage->outputHeader();
		$htmlPage->displayInformation("Outage " .$form['id']." has been sent for approval.");
		// outputJavascript();	
		// displayForm();
		$htmlPage->outputFooter();
		exit();
	}
}
else {
	$htmlPage->outputHeader();
	outputJavascript();	
	displayForm();
	$htmlPage->outputFooter();
	exit();
}


function validateForm() {
	global $form, $requiredFields, $requiredFieldsPlanned, $requiredFieldsUnplanned;
	$missingFields = array();
	foreach ($requiredFields as $field) {
		if (empty($form[$field])) {
			$missingFields[] = $field;
		}
	}
	// Sites
	if (!$form['allSitesAffected']) {
		if (empty($form['companyAddressRecIds'])) {
			$missingFields[] = 'companyAddressRecIds';
		}
	}
	// Planned Outage
	if ($form['typeId'] == '1') {
		foreach ($requiredFieldsPlanned as $field) {
			if (empty($form[$field])) {
				$missingFields[] = $field;
			}
		}
	}
	// Unplanned Outage
	if ($form['typeId'] == '2') {
		foreach ($requiredFieldsUnplanned as $field) {
			if (empty($form[$field])) {
				$missingFields[] = $field;
			}
		}
	}
	
	// Service - Other
	if ($form['serviceId'] == 'other') {
		if (empty($form['otherService'])) {
			$missingFields[] = 'serviceId';
		}
	}
	// Service - Other
	if ($form['reasonId'] == 'other') {
		if (empty($form['otherReason'])) {
			$missingFields[] = 'reasonId';
		}
	}
	
	// Fault
	if($form['typeId']=='2' && $form['faultOwned']==''){
		$missingFields[] = 'faultOwned';
		if(empty($form['companyRecId'])){
			$missingFields[] = 'companyRecId';
		}
	} else if($form['faultOwned']=='0' && empty($form['companyRecId'])){
		$missingFields[] = 'companyRecId';
	}
	if($form['faultCaused']==''){
		$missingFields[] = 'faultCaused';
	}
	if($form['faultOwned']=='0' && $form['faultCaused']=='0' && $form['hardwareFailure']==''){
		$missingFields[] = 'hardwareFailure';
	}
	
	
	/*// Diversion Site Id
	if ($form['serviceId'] == '5') {
		if (empty($form['diversionCompanyAddressRecId'])) {
			$missingFields[] = 'diversionCompanyAddressRecId';
		}
	}*/
	return $missingFields;
}

function displayForm() {
	global $form, $companiesObj, $outageTypesObj, $companyAddressesObj, $outageSiteSelectsObj, $outagePreferencesObj, $htmlPage, $timeZonesObj, $outageServicesObj, $outageReasonsObj, $outageAvailabilitiesObj, $fonts, $outagesObj, $membersObj, $outageSitePreferencesObj, $outageSiteCompanyPrefObj, $outageSiteCompanySelectObj;
	$headingWidth = 200;
	
	// Load outage for edit
	$form2 = $htmlPage->initialiseFormData(array(), array('step', 'id'));
	if($form2['step'] == ''){
		$record = $outagesObj->getOutageById($form2['id']);
		$type = $outageTypesObj->getOutageTypeById($record['type_id']);
		
		// Initialize form fields with stored values.
		$form['summaryTemplate'] = $type['summary_template'];
		$form['id'] = $record['id'];
		$form['companyRecId'] = $record['Company_RecID'];
		$form['typeId'] = $record['type_id'];
		$form['timeZoneId'] = $record['time_zone_id'];
		$form['startTime'] = ($record['start_time']!=null) ? (date("d-m-Y h:i a",$record['start_time'])) : '';
		if($record['end_time']!='0' && $record['end_time']!=null){
			$form['endTime'] = date("d-m-Y h:i a",$record['end_time']);
		}
		if($record['service_id'] == 0){
			$form['serviceId'] = 'other';
			$form['otherService'] = $record['service_other'];
		} else {
			$form['serviceId'] = $record['service_id'];
		}
		$form['availabilityId'] = $record['availability_id'];
		$form['reasonId'] = $record['reason_id'];
		$form['otherReason'] = $record['reason_other'];
		$form['diversionCompanyAddressRecId'] = $record['diversion_Company_Address_RecID'];
		$form['faultOwned'] = $record['owned'];
		$form['faultCaused'] = $record['responsible'];
		$form['hardwareFailure'] = $record['hardware_failure'];
		$form['ticketNo'] = $record['SR_Service_RecID'];
		if($record['custom_summary']=='1'){
			$form['customSummary'] = $record['summary'];
		}
		$form['summary']='...';
	
		// Check if all sites are affected
		$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($form['companyRecId']);
		$companyOutageSites = $outageSiteSelectsObj->getCompanyAddressRecIds($form['id']);
		if(($form['step']!='' && $companyAddressRecIds == $companyOutageSites) || ($form['step']=='' && $form['id']!='' && $companyAddressRecIds == $companyOutageSites)){
			$form['allSitesAffected'] = 1;
		}
		
		$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($form['companyRecId']);
		foreach ($companyAddressRecIds as $companyAddressRecId) {
			if (!in_array($companyAddressRecId, $outagesObj->blacklistCompanyAddressRecIds)) {
				$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
				if ($companyAddress) {
					$siteRec = $outageSitePreferencesObj->getSitePreferenceByCompanyRecId($companyAddress['Company_Address']);
					if($siteRec){
						$sitePref[$companyAddress['Company_Address']] = $siteRec['to_address'];
					}
				}
			}
		}
		
	}
	
	// print_r( $outagePreferencesObj->getPreferenceByCompanyRecId(19305));
	echo "
<!-- <form action='' method='post'> -->
<form id='outageForm' name='outageForm' action='' method='post'>";
	
	if($form['step']==''){
	echo"
		<input type='hidden' name='status' id='status' value='new'/>";
	}
	else{
	echo"
		<input type='hidden' name='status' id='status' value=''/>";
	}
	
	// echo "<pre>";
	// print_r($form);
	// echo "</pre>";
	
	////////////////////////////////////////////////////////////////////////////////////////
	
	echo "
<!-- <form id='outageForm' name='outageForm' action='' method='post'> -->
	<input type='hidden' id='step' name='step' value='create' />
	<input type='hidden' id='id' name='id' value='{$form['id']}' />
	<input type='hidden' id='availabilityDescription' name='availabilityDescription' value='{$form['availabilityDescription']}' />
	<input type='hidden' id='serviceDescription' name='serviceDescription' value='{$form['serviceDescription']}' />
	<input type='hidden' id='timeZoneDescription' name='timeZoneDescription' value='{$form['timeZoneDescription']}' />
	<input type='hidden' id='diversionSiteDescription' name='diversionSiteDescription' value='{$form['diversionSiteDescription']}' />
	<input type='hidden' id='reasonDescription' name='reasonDescription' value='{$form['reasonDescription']}' />
	<input type='hidden' id='summaryTemplate' name='summaryTemplate' value='{$form['summaryTemplate']}' />
	<input type='hidden' id='summary' name='summary' value='{$form['summary']}' />
	<br/>
	<br/>
	<br/>
	<div class='ic-bar' id='' name=''>
		<span>Action</span>
		<div class='ic'><a href='/cgi/new/Service_Desk/outage_new.test.php'><img src='/images/icons/flaticons/file68.png' alt='New' title='New'></a></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/note35.png' alt='Edit' title='Edit'></div>
		<div class='ic'><a href='#' onclick='save();'><img src='/images/icons/flaticons/save15.png' alt='Save' title='Save'></a></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/recycling10.png' alt='Delete' title='Delete'></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/copy23.png' alt='Copy' title='Copy'></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/right153.png' alt='Export' title='Export'></div>
		<div class='ic'><a href='/cgi/new/Service_Desk/outage_list.test.php' onclick=''><img id='' name='' src='/images/icons/flaticons/search74.png' alt='Search' title='Search'></a></div>
		<div class='ic'><a href='#' onclick='sendForApproval();'><img id='' name='' src='/images/icons/flaticons/checkmark16.png' alt='Send for Approval' title='Send for Approval'></a></div>
	</div>
	<br/>
	<table>";
	if($form['id']){
		$display = 'block';
	} else{
		$display = 'none';
	}
	echo "
		<tr style='display:$display'>
			<th class='{$fonts['id']}' style='width: {$headingWidth}px;'>Outage ID</th>
			<td>{$form['id']}</td>
		</tr>
	</table>
	<table>
		<tr>
			<th class='{$fonts['typeId']}' style='width:{$headingWidth}px'>Outage Type</th>
			<td>
				<select name='typeId' id='typeId' onchange='updateSummaryTemplate(this.value);toggleResponsibilityInput();toggleReasonInput();toggleHardwaredFailure();'>
					<option></option>";
	$typeIds = $outageTypesObj->getOutageTypeIds();
	foreach ($typeIds as $typeId) {
		$type = $outageTypesObj->getOutageTypeById($typeId);
		if ($type) {
			$selected = '';
			if ($form['typeId'] == $type['id']) {
				$selected = 'selected';
			}
			echo "
					<option value='{$type['id']}' $selected>{$type['title']}</option>";
		}
	}
	echo "
				</select>
			</td>
		</tr>
	</table>";
	$display = 'none';
	$selectedYes = '';
	$selectedNo = '';
	// if($form['typeId']=='2' || $form['typeId']=='3'){	// Unplanned or Unplanned (test only)
		$display='block';
		if($form['faultOwned']=='1'){
			$selectedYes = 'selected';
		}else if($form['faultOwned']=='0'){
			$selectedNo = 'selected';
		}
	// }
	echo "
	<table>
		<tr id='faultOwnedRow' name='faultOwnedRow' style='display:block'>
			<th class='{$fonts['faultOwned']}' style='width:{$headingWidth}px'>Is the device at fault owned by March IT or one of its subcontractors?</th>
			<td>
				<select id='faultOwned' name='faultOwned' onchange='toggleCompany();toggleHardwaredFailure();toggleAffectedCompanies();'>
					<option value=''></option>
					<option value='1' $selectedYes >Yes</option>
					<option value='0' $selectedNo >No</option>
				</select>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<th class='{$fonts['ticketNo']}' style='width: {$headingWidth}px;'>Ticket Number</th>
			<td><input type='text' name='ticketNo' value='{$form['ticketNo']}' onkeyup='' /></td>
		</tr>
		<tr>
			<th class='{$fonts['companyRecId']}' style='width: {$headingWidth}px;'>Company</th>
			<td>";	
	$disabled = '';
	if($form['companyRecId'] == '19307' &&  $form['faultOwned']=='1'){	// March IT
		$disabled = 'disabled';
	}
	echo "
				<select name='companyRecId' id='companyRecId' onchange='updateSites(this.value);toggleAffectedCompanies()' $disabled>
				<option></option>";
	$companyRecIds = $companiesObj->getCompanyRecIds();
	foreach ($companyRecIds as $companyRecId) {
		$company = $companiesObj->getCompanyByRecId($companyRecId);
		if ($company) {
			$selected = '';
			if ($form['companyRecId'] == $company['Company_RecID']) {
				$selected = 'selected';
			}
			echo "
					<option value='{$company['Company_RecID']}' $selected>{$company['Company_Name']}</option>";
		}
	}
	echo "
				</select>
			</td>
		</tr>";
	
	echo "
		<tr>
			<th class='{$fonts['startTime']}'>Start Time</th>
			<td><input type='text' name='startTime' id='startTime' value='{$form['startTime']}' /></td>
		</tr>
		<tr>
			<th class='{$fonts['endTime']}'>Estimated End Time</th>
			<td><input type='text' name='endTime' id='endTime' value='{$form['endTime']}' /></td>
		</tr>
		<tr>
			<th class='{$fonts['timeZoneId']}'>Time Zone</th>
			<td>
				<select id='timeZoneId' name='timeZoneId' onchange='updateTimeZoneDescription(this.value);'>
					<option></option>";
	$timeZoneIds = $timeZonesObj->getTimeZoneIds();
	foreach ($timeZoneIds as $timeZoneId) {
		$timeZone = $timeZonesObj->getTimeZoneById($timeZoneId);
		if ($timeZone) {
			$selected = '';
			if ($timeZone['id'] == $form['timeZoneId']) {
				$selected = 'selected';
			}
			echo "
					<option value='{$timeZone['id']}' $selected>{$timeZone['title']} ({$timeZone['code']})</option>";
		}
	}
	echo "
				</select>
			</td>
		</tr>
		<tr>
			<th class='{$fonts['companyAddressRecIds']}'>Affected Site(s)</th>
			<td>";
	$siteList = array();
	$checked = '';
	$display = 'block';
	if ($form['allSitesAffected']) {
		$checked = 'checked';
		$display = 'none';
	}
	echo "
				<input type='checkbox' id='allSitesAffected' name='allSitesAffected' $checked onclick='toggleAllSitesAffected();updateSummary();' /> All Sites<br />
				<div id='companyAddresses' style='display: {$display};'>";
	$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($form['companyRecId']);
	foreach ($companyAddressRecIds as $companyAddressRecId) {
		if (!in_array($companyAddressRecId, $outagesObj->blacklistCompanyAddressRecIds)) {
			$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
			if ($companyAddress) {
				$checked = '';
				$companyOutageSites = $outageSiteSelectsObj->getCompanyAddressRecIds($form['id']);
				if($form['step'] == ''){
					if (in_array($companyAddress['Company_Address'], $companyOutageSites)) {
						$checked = 'checked';
						$siteList[] = $companyAddress['Company_Address'];
						echo "
							<script type='text/javascript'>
								updateSiteList('{$companyAddress['Company_Address']}', true);
								updateCompanyAddressList('{$companyAddress['Description']}', true);
								updateAffectedClients();
							</script>";
					}
				}
				else {
					if (isset($_POST['companyAddressRecIds']) && in_array($companyAddress['Company_Address'], $_POST['companyAddressRecIds'])) {
						$checked = 'checked';
						// $sites[] = $companyAddress['Company_Address'];
						$siteList[] = $companyAddress['Company_Address'];
						echo "
							<script type='text/javascript'>
								updateSiteList('{$companyAddress['Company_Address']}', true);
								updateCompanyAddressList('{$companyAddress['Description']}', true);
								updateAffectedClients();
							</script>";
					}
				}
				echo "
					<input type='checkbox' class='chkAffectedSites' name='companyAddressRecIds[]' value='{$companyAddress['Company_Address']}' $checked onclick=\"updateCompanyAddressList('{$companyAddress['Description']}', checked);updateSiteList('{$companyAddress['Company_Address']}', checked);updateAffectedClients();updateSummary();\" /> {$companyAddress['Description']}<br />";
			}
		}
	}
	echo "
				</div>
				<span id='loading-sites'></span>
			</td>
		</tr>
	</table>
	
	<table>";
	$display = 'none';
	if($form['faultOwned']=='1'){
		$display = 'block';
	}
	echo "
		<tr id='rowAffectedCompanies' name='rowAffectedCompanies' style='display:$display'>
			<th class='{$fonts['affectedClients']}' style='width:{$headingWidth}px'>Affected Companies</th>
			<td>
				<div id='affectedClients' style=''>";
	// if($siteList){
		// $affectedClients = $outageSiteCompanySelectObj->getClientIdsByOutageId($form['id']);
		// foreach($siteList as $companyAddressRecId){
			// $clientIds = $outageSiteCompanyPrefObj->getClientsByCompanyAddressRecId($companyAddressRecId);
			// if($clientIds){
				// foreach($clientIds as $clientId){
					// $checked = '';
					// if($form['step']=='' && $affectedClients && in_array($clientId['Company_RecId'], $affectedClients)){
						// $checked = 'checked';
					// }
					// else if($form['affectedClients'] && in_array($clientId['Company_RecId'], $form['affectedClients'])){
						// $checked = 'checked';
					// }
					// $client = $companiesObj->getCompanyByRecId($clientId['Company_RecId']);
					// echo "
					// <input type='checkbox' id='affectedClients[]' name='affectedClients[]' $checked onclick='' value='{$client['Company_RecID']}'> {$client['Company_Name']} <br />";
				// }
			// }
			// else{
				// echo "N/A";
			// }
		// }
	// } else{
		// echo "N/A";
	// }
	echo "		</div>
				<span id='loading-clients' name='loading-clients'></span>
			</td>
		</tr>
	</table>
	
	<table>";	
	$selectedYes = '';
	$selectedNo = '';
	if($form['faultCaused']=='1'){
		$selectedYes = 'selected';
	} else if($form['faultCaused']=='0'){
		$selectedNo = 'selected';
	}
	echo "
		<tr id='faultCausedRow' name='faultCausedRow' style=''>
			<th class='{$fonts['faultCaused']}' style='width:{$headingWidth}px'>Is March IT or one of its subcontractors responsible for causing the outage?</th>
			<td>
				<select id='faultCaused' name='faultCaused' onchange='toggleHardwaredFailure();updateSummary();'>
					<option value=''></option>
					<option value='1' $selectedYes >Yes</option>
					<option value='0' $selectedNo >No</option>
			</td>
		</tr>
	</table>";
	$display = 'none';
	$selectedYes = '';
	$selectedNo = '';
	if($form['faultOwned']=='0' && $form['faultCaused']=='0'){
		$display='block';
		if($form['hardwareFailure']=='1'){
			$selectedYes = 'selected';
		}else if($form['hardwareFailure']=='0'){
			$selectedNo = 'selected';
		}
	}
	echo "
	<table>
		<tr id='hardwareFailureRow' name='hardwareFailureRow' style='display:$display'>
			<th class='{$fonts['hardwareFailure']}' style='width:{$headingWidth}px'>Is the outage the result of hardware failure?</th>
			<td>
				<select id='hardwareFailure' name='hardwareFailure' onchange='updateSummary();'>
					<option value=''></option>
					<option value='1' $selectedYes >Yes</option>
					<option value='0' $selectedNo >No</option>
			</td>
		</tr>
	</table>";
	
	echo "
	<table>
		<tr>
			<th class='{$fonts['serviceId']}'  style='width:{$headingWidth}px'>Affected Service</th>
			<td>
				<select id='serviceId' name='serviceId' onchange='updateServiceDescription(this.value);toggleOtherServiceInput();toggleDiversionSiteInput();'>
					<option></option>";
	$serviceIds = $outageServicesObj->getServiceIds();
	foreach ($serviceIds as $serviceId) {
		$service = $outageServicesObj->getServiceById($serviceId);
		if ($service) {
			$selected = '';
			if ($service['id'] == $form['serviceId']) {
				$selected = 'selected';
			}
			$serviceName = ucfirst($service['title']);
			$serviceName = str_replace('Service', '', $serviceName);
			echo "
					<option value='{$service['id']}' $selected>$serviceName</option>";
		}
	}
	echo "";
	$display = 'none';
	if ($form['serviceId'] == 'other') {
		$display = 'block';
		$selected = 'selected';
	}
	echo "
					<option value='other' $selected>Other...</option>
				</select>
				<div id='otherServiceInput' style='display: {$display}; padding-top: 5px;'>
					<input type='text' id='otherService' name='otherService' value='{$form['otherService']}' onChange='updateServiceDescriptionOther(this.value);' onkeydown='updateServiceDescriptionOther(this.value);'/>
				</div>
			</td>
		</tr>
		<tr>
			<th class='{$fonts['availabilityId']}'>Availability</th>
			<td>
				<select id='availabilityId' name='availabilityId' onchange='updateAvailabilityDescription(this.value);'>
					<option></option>";
	$availabilityIds = $outageAvailabilitiesObj->getAvailabilityIds();
	foreach ($availabilityIds as $availabilityId) {
		$availability = $outageAvailabilitiesObj->getAvailabilityById($availabilityId);
		if ($availability) {
			$selected = '';
			if ($availability['id'] == $form['availabilityId']) {
				$selected = 'selected';
			}
			echo "
					<option value='{$availability['id']}' $selected>{$availability['title']}</option>";
		}
	}
	echo "
				</select>
			</td>
		</tr>
	</table>";
	$display = 'none';
	if ($form['typeId'] == 1) {
		$display = 'block';
	}
	echo "
	<div id='reasonInput' style='display: {$display};'>
		<table>
			<tr>
				<th class='{$fonts['reasonId']}' style='width: {$headingWidth}px;'>Reason</th>
				<td>
					<select id='reasonId' name='reasonId' onchange='updateReasonDescription(this.value);toggleOtherReasonInput();'>
						<option></option>";
	$reasonIds = $outageReasonsObj->getReasonIds();
	foreach ($reasonIds as $reasonId) {
		$reason = $outageReasonsObj->getReasonById($reasonId);
		if ($reason) {
			$selected = '';
			if ($reason['id'] == $form['reasonId']) {
				$selected = 'selected';
			}
			echo "
						<option value='{$reason['id']}' $selected>{$reason['title']}</option>";
		}
	}
	$selected = '';
	$display = 'none';
	if ($form['reasonId'] == 'other') {
		$selected = 'selected';
		$display = 'block';
	}
	echo "
						<option value='other' $selected>Other...</option>
					</select>
					<div id='otherReasonInput' style='display: {$display}; padding-top: 5px;'>
						<input type='text' name='otherReason' value='{$form['otherReason']}' onChange='updateReasonDescriptionOther(this.value);' />
					</div>
				</td>
			</tr>
		</table>
	</div>";
	$display = 'none';
	if ($form['serviceId'] == '5') {
		$display = 'block';
	}
	echo "
	<div id='diversionSiteInput' style='display: {$display};'>
		<table>
			<tr>
				<th class='{$fonts['diversionCompanyAddressRecId']}' style='width: {$headingWidth}px;'>Phone Diversion Site</th>
				<td>
					<select id='diversionCompanyAddressRecId' name='diversionCompanyAddressRecId' onchange='updateDiversionSiteDescription(this.value);'>
						<option value=''></option>";
	$companyAddressRecIds = $companyAddressesObj->getCompanyAddressRecIds($form['companyRecId']);
	foreach ($companyAddressRecIds as $companyAddressRecId) {
		if (!in_array($companyAddressRecId, $outagesObj->blacklistCompanyAddressRecIds)) {
			$companyAddress = $companyAddressesObj->getCompanyAddressByRecId($companyAddressRecId);
			if ($companyAddress) {
				$selected = '';
				if ($companyAddress['Company_Address'] == $form['diversionCompanyAddressRecId']) {
					$selected = 'selected';
				}
				echo "
						<option value='{$companyAddress['Company_Address']}' $selected>{$companyAddress['Description']}</option>";
			}
		}
	}
	echo "
					</select>
					&nbsp;<span id='loading-sites2'></span>
				</td>
			</tr>
		</table>
	</div>";
	$display = 'none';
	if ($form['typeId'] == 2) {
		$display = 'block';
	}
	
	$text = '';
	$color = 'black';
	$text = $form['customSummary'];
	if($form['customSummary']==''){
		$displayEdit = '';
		$color = 'black';
	}
	else if($form['customSummary']!=''){
		$displayEdit = 'none';
		$color = 'gray';
	}
	echo "
	<table>
		<tr>
			<th class='' style='width: {$headingWidth}px;'>Summary</th>
			<td><div>
				<div class='' id='btnEdit' style='float:left;padding-right:10px;display: {$displayEdit};'><a href='#' onclick='editSummary(event)'><img src='/images/icons/flaticons/note35.png' height='20' alt='Edit Summary' title='Edit Summary'></a></div>
				<span id='summaryDisplay' name='summaryDisplay' style='color: {$color}; float:left; max-width:900px;' contentEditable=false>{$form['summary']}</span><span id='loading-summary'></span><br/>
				<!-- <button id='btnEdit' style='display: {$displayEdit};' onclick='editSummary(event)'>Edit</button> -->
				</div>
			</td>
		</tr>";
	$display = 'none';
	// if($form['customSummary']==''){		// Loading for the 1st time
		// $display = 'none';
	// }
	if($form['customSummary']!=''){			// Subsequent loads
		$display = '';
	}
	echo"
		<tr id='customSummaryInput' style='display: {$display};'>
			<th class='{$fonts['customSummary']}' style='width: {$headingWidth}px;'>Custom Summary</th>
			<td>
				<div>
					<div class='' id='btnCancel' style='float:left; padding-right:10px;'><a href='#' onclick='cancelSummary(event)'><img src='/images/icons/flaticons/cross83.png' height='20' alt='Cancel Custom Summary' title='Cancel Custom Summary'></a></div>
					<textarea type='text' id='customSummary' name='customSummary'>{$text}</textarea><br/>
					<pre style='margin-left:30px;'><i>*This field does not auto-update. </i></pre>
					<!-- <br/>
					<button id='btnCancel' onclick='cancelSummary(event)'>Cancel</button> -->
				</div>
			</td>
		</tr>
		
		
		<tr>
			<th class='{$fonts['memberRecId']}'>Request Approval from </th>
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
		<tr>
			<td colspan='2'>
				<!-- <input type='submit' id='btnSave' name='btnSave' value='Save' />
				<a href='#'><img src='/images/icons/flaticons/save15.png' alt='Save' title='Save' height=20 style='vertical-align:bottom; padding-right:10px;'></a>
				<input type='submit' id='btnSend' name='btnSend' value='Send for Approval' /> -->
			</td>
		</tr>
	</table>
</form>
<br/><br/><br/><br/>";
}

function outputJavascript() {
	global $outagesObj;
	echo "
<script>
	
	var companyAddressList = [];
	var siteList = [];
	var affectedSiteList = [];
	
	$(document).ready(function(){
		if( ($('#serviceId').val()!='other') ){
			updateServiceDescription($('#serviceId').val());
		}
		else {
			updateServiceDescriptionOther($('#otherService').val());
		}
		updateAvailabilityDescription($('#availabilityId').val());
		if($('#reasonId').val()!='other'){
			updateReasonDescription($('#reasonId').val());
		}
		else {
			updateReasonDescriptionOther($('#otherReason').val());
		}
		updateTimeZoneDescription($('#timeZoneId').val());
		
		// Disable edit on focusout
		$('#summaryDisplay').focusout(function(){
			$(this).attr('contentEditable', false);
			$('#summary').val($('#summaryDisplay').text());	//update summary value
		});
		
		updateAffectedClients(true);
		// console.log($('#id').val());
		// checkSelectedClients($('#id').val());
		updateSummary();
		// console.log(affectedSiteList);
		
	});
	
	// $.when(updateAffectedClients()).done(function(a1){
		// console.log('ajax loaded');
		// checkSelectedClients($('#id').val());
	// });
	
	$(function() {
		$('#startTime').datetimepicker({
			timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				updateSummary();
			}
		});
		$('#endTime').datetimepicker({
			timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				updateSummary();
			}
		});
	});
	
	function toggleCompany(){
		if($('#faultOwned').val() == '1' ){
			$('#companyRecId').val('19307');
			$('#companyRecId').prop('disabled', true);
			updateSites(19307);	// March IT
		} else{
			$('#companyRecId').val('');
			$('#companyRecId').prop('disabled', false);
			updateSites('');
		}
	}
	
	function toggleHardwaredFailure(){
		if( ($('#typeId').val()=='2' || $('#typeId').val()=='3') && $('#faultOwned').val()=='0' && $('#faultCaused').val()=='0' ){
			$('#hardwareFailureRow').css('display', 'block');
		} else{
			$('#hardwareFailureRow').css('display', 'none');
		}
	}
	
	function toggleAffectedCompanies(){
		// if($('#faultOwned').val()=='1' ){
		if($('#companyRecId').val()=='19307' ){					// March IT 
			$('#rowAffectedCompanies').css('display', 'block');
		} else{
			$('#rowAffectedCompanies').css('display', 'none');
		}
	}
	
	function toggleResponsibilityInput() {
		/*// Display for Unplanned Outage only
		if (document.getElementById('typeId').value == '2' || document.getElementById('typeId').value == '3' ) {	// Unplanned/Unplanned (Test)
			// document.getElementById('responsibilityInput').style.display = 'block';
			$('#faultOwnedRow').css('display', 'block');
		} else {
			// document.getElementById('responsibilityInput').style.display = 'none';
			$('#faultOwnedRow').css('display', 'none');
			$('#faultOwned').val('');
			$('#companyRecId').val('');
			$('#companyRecId').prop('disabled', false);
		}*/
	}

	function toggleReasonInput() {
		// Display for Planned Outage only
		if (document.getElementById('typeId').value == '1') {
			document.getElementById('reasonInput').style.display = 'block';
		} else {
			document.getElementById('reasonInput').style.display = 'none';
		}
	}

	function toggleDiversionSiteInput() {
		// Display for Phone System Outage only
		if (document.getElementById('serviceId').value == '5') {
			document.getElementById('diversionSiteInput').style.display = 'block';
		} else {
			document.getElementById('diversionSiteInput').style.display = 'none';
		}
	}

	function updateAvailabilityDescription(availabilityId) {
		if (availabilityId == '') {
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
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('availabilityDescription').value = x[0].getElementsByTagName('description')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageAvailabilityById.php?availabilityId='+availabilityId,true);
		xmlhttp.send();
	}

	function updateServiceDescriptionOther(description) {
		document.getElementById('serviceDescription').value = description;
		updateSummary();
	}

	function updateReasonDescriptionOther(description) {
		document.getElementById('reasonDescription').value = description;
		updateSummary();
	}

	function updateServiceDescription(serviceId) {
		if ((serviceId == '') || (serviceId == 'other')) {
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
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('serviceDescription').value = x[0].getElementsByTagName('title')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageServiceById.php?serviceId='+serviceId,true);
		xmlhttp.send();
	}

	function updateCompanyAddressList(companyAddress, state) {
		// console.log(companyAddress);
		if (state == true) {
			companyAddressList.push(companyAddress);
		} else {
			for(i=companyAddressList.length - 1; i >= 0; i--) {
				if(companyAddressList[i] === companyAddress) {
					companyAddressList.splice(i, 1);
				}
			}
		}
	}

	function updateTimeZoneDescription(timeZoneId) {
		if (timeZoneId == '') {
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
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('timeZoneDescription').value = x[0].getElementsByTagName('code')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getTimeZoneById.php?timeZoneId='+timeZoneId,true);
		xmlhttp.send();
	}

	function updateDiversionSiteDescription(companyAddressRecId) {
		if (companyAddressRecId == '') {
			document.getElementById('diversionSiteDescription').value = '';
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
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('diversionSiteDescription').value = x[0].getElementsByTagName('Description')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getCompanyAddressByRecId.php?companyAddressRecId='+companyAddressRecId,true);
		xmlhttp.send();
	}

	function updateReasonDescription(reasonId) {
		if ((reasonId == '') || (reasonId == 'other')) {
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
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						document.getElementById('reasonDescription').value = x[0].getElementsByTagName('description')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageReasonById.php?reasonId='+reasonId,true);
		xmlhttp.send();
	}
	
	function editSummary(e){
		//enable custom summary input
		e.preventDefault();
		// $('#summaryDisplay').attr('contentEditable', true);
		// $('#summaryDisplay').focus();
		$('#summaryDisplay').css('color', 'gray');
		$('#customSummaryInput').css('display', '');
		$('#customSummary').val($('#summaryDisplay').text());
		$('#btnEdit').css('display', 'none');
	}
	
	function cancelSummary(e){
		//cancel custom summary
		e.preventDefault();		
		$('#customSummaryInput').css('display', 'none');
		$('#btnEdit').css('display', 'block');
		$('#customSummary').val('');
		$('#summaryDisplay').css('color', 'black');
		// $('#btnCancel').css('display', 'none');
	}

	function updateSummary() {
		// get unmerged template
		// ajax to get variables
		// update each variable from form value if not empty
		// update summary and summaryDisplay

		var summaryTemplate = document.getElementById('summaryTemplate').value;
		var summary = summaryTemplate;
		var typeId = document.getElementById('typeId').value;
		if (typeId == '') {
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
					for (i=0;i<x.length;i++) {
						if (x[i].getElementsByTagName('title')[0].hasChildNodes()) {
							var mytitle = x[i].getElementsByTagName('title')[0].childNodes[0].nodeValue;
							var myvar = '['+ mytitle + ']';
							// service
							if (mytitle == 'service') {
								var myvalue = document.getElementById('serviceDescription').value;
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}

							// availability
							if (mytitle == 'availability') {
								var myvalue = document.getElementById('availabilityDescription').value;
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}

							// site
							if (mytitle == 'site'){
								if( $('#companyRecId').val()!=19307) {	// Not March IT
									var myvalue = '';
									if (document.getElementById('allSitesAffected').checked) {
										myvalue = 'all sites';
									// } else if($('.chkAffectedSites:checked').val()!=''){
									} else{
										myvalue = 'the ';
										
										for (j=0;j<companyAddressList.length;j++) {
											if ((companyAddressList.length > 1) && (j > 0)) {
												if (j < companyAddressList.length - 1) {
													myvalue = myvalue + ', ';
												} else {
													myvalue = myvalue + ' and ';
												}
											}
											myvalue = myvalue + companyAddressList[j];
										}
										myvalue = myvalue + ' site';
										if (companyAddressList.length > 1) {
											myvalue = myvalue + 's';
										}
									}
									summary = summary.replace(myvar,myvalue);
								} else{
									summary = summary.replace(myvar,'the specified site(s)');
								}
								
							} 

							// start_time
							if (mytitle == 'start_time') {
								var myvalue = document.getElementById('startTime').value;
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}

							// end_time
							if (mytitle == 'end_time') {
								var myvalue = document.getElementById('endTime').value;
								if (myvalue == '') {
									myvalue = ' unknown';
								}
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}

							// time_zone
							if (mytitle == 'time_zone') {
								var myvalue = document.getElementById('timeZoneDescription').value;
								if (document.getElementById('endTime').value == '') {
									myvalue = '';
									summary = summary.replace(myvar,myvalue);
								} else {
									myvalue = ' ' + myvalue;
								}
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}

							// corrective measures
							if(mytitle == 'corrective_measures'){
								var myvalue = '';
								if ( (document.getElementById('faultOwned').value == '1' && document.getElementById('faultCaused').value == '0') || (document.getElementById('faultOwned').value == '0' && document.getElementById('faultCaused').value == '0' && document.getElementById('hardwareFailure').value == '0') ) {
								// if (myvalue!='') {
									myvalue = '';
								} else{
									myvalue = 'We are working on corrective measures in order to restore the service.';
								}
								if(document.getElementById('faultCaused').value!=''){
									summary = summary.replace(myvar, myvalue);
								}
							}
							
							// diversion
							if (mytitle == 'diversion') {
								var myvalue = document.getElementById('diversionSiteDescription').value;
								if (myvalue != '') {
									if (document.getElementById('typeId').value == 1) {
										// Planned
										myvalue = 'Incoming calls will be diverted to ' + myvalue + '.';
									} else {
										myvalue = 'Incoming calls have been diverted to ' + myvalue + '.';
									}
								}
								summary = summary.replace(myvar,myvalue);
							}

							// reason
							if (mytitle == 'reason') {
								var myvalue = document.getElementById('reasonDescription').value;
								if (myvalue != '') {
									summary = summary.replace(myvar,myvalue);
								}
							}
						}
					}
				}
				document.getElementById('summaryDisplay').innerHTML = summary;
				document.getElementById('summary').value = summary;
			}
		}
		document.getElementById('loading-summary').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getSummaryTemplateVariables.php?typeId='+typeId,true);
		xmlhttp.send();
	}

	function updateSummaryTemplate(typeId) {
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
						document.getElementById('summary').value = x[0].getElementsByTagName('summary_template')[0].childNodes[0].nodeValue;
						document.getElementById('summaryDisplay').innerHTML = x[0].getElementsByTagName('summary_template')[0].childNodes[0].nodeValue;
						document.getElementById('summaryTemplate').value = x[0].getElementsByTagName('summary_template')[0].childNodes[0].nodeValue;
						updateSummary();
					}
				}
			}
		}
		document.getElementById('loading-summary').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageTypeById.php?typeId='+typeId,true);
		xmlhttp.send();
	}

	function toggleOtherServiceInput() {
		if (document.getElementById('serviceId').value == 'other') {
			document.getElementById('otherServiceInput').style.display = 'block';
		} else {
			document.getElementById('otherServiceInput').style.display = 'none';
		}
	}

	function toggleOtherReasonInput() {
		if (document.getElementById('reasonId').value == 'other') {
			document.getElementById('otherReasonInput').style.display = 'block';
		} else {
			document.getElementById('otherReasonInput').style.display = 'none';
		}
	}

	function toggleAllSitesAffected() {
	
		if (document.getElementById('allSitesAffected').checked) {
			document.getElementById('companyAddresses').style.display = 'none';
		} else {
			document.getElementById('companyAddresses').style.display = 'block';
		}
		
		var companyRecId = document.getElementById('companyRecId').value;
		if (companyRecId == '') {
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
				var xmlDoc=xmlhttp.responseXML;
				//var txt='';
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						siteList = [];
						for (i = 0; i < x.length; i++) {
							if (x[i].getElementsByTagName('Company_Address')[0].hasChildNodes()) {
								// select or deselect all sites
								var companyAddressRecId = x[i].getElementsByTagName('Company_Address')[0].childNodes[0].nodeValue;
								if (document.getElementById('allSitesAffected').checked) {
									$('input:checkbox[value=\"' + companyAddressRecId + '\"]').attr('checked', true);
									siteList.push(companyAddressRecId);
								} else {
									$('input:checkbox[value=\"' + companyAddressRecId + '\"]').attr('checked', false);
								}
							}
								
							// select or deselect all addresses
							if (x[i].getElementsByTagName('outage_to_address')[0].hasChildNodes()) {
								var to_address = x[i].getElementsByTagName('outage_to_address')[0].childNodes[0].nodeValue;
								if (document.getElementById('allSitesAffected').checked) {
									$('input:checkbox[value=\"' + to_address + '\"]').attr('checked', true);
								} else {
									$('input:checkbox[value=\"' + to_address + '\"]').attr('checked', false);
								}
							}
						}
						updateAffectedClients();
					}
				}
			}
		}
		xmlhttp.open('GET','/cgi/new/Ajax/getCompanyAddressesByCompanyRecId.php?companyRecId=' + companyRecId + '&outagePreference=1',true);
		xmlhttp.send();
	}

	var blacklistCompanyAddressRecIds=new Array(";
	foreach ($outagesObj->blacklistCompanyAddressRecIds as $companyAddressRecId) {
		echo "'$companyAddressRecId'";
		if ($companyAddressRecId != end($outagesObj->blacklistCompanyAddressRecIds)) {
			echo ',';
		}
	}
	
	echo ");

	function in_array(a, obj) {
		for (var i = 0; i < a.length; i++) {
			if (a[i] === obj) {
				return true;
			}
		}
		return false;
	}

	function updateSites(companyRecId) {
	
		// Update affected sites and affected companies
		siteList = [];
		updateAffectedClients();
		$('#allSitesAffected').attr('checked', false);
		toggleAllSitesAffected();
		
		if (companyAddressList.length > 0) {
			companyAddressList.length = 0;
		}
		if (companyRecId=='') {
			document.getElementById('companyAddresses').innerHTML='';
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
				document.getElementById('loading-sites').innerHTML = '';
				document.getElementById('loading-sites2').innerHTML = '';
				txt='';
				txt2='<option></option>';
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					for (i=0;i<x.length;i++) {
						if (x[i].getElementsByTagName('Company_Address')[0].hasChildNodes()) {
							check = in_array(blacklistCompanyAddressRecIds, x[i].getElementsByTagName('Company_Address')[0].childNodes[0].nodeValue);
							if (check != true) {
								txt=txt + '<input type=\"checkbox\" class=\"chkAffectedSites\" name=\"companyAddressRecIds[]\" id=\"companyAddressRecIds[]\" value=\"' + x[i].getElementsByTagName('Company_Address')[0].childNodes[0].nodeValue +  '\" onclick=\"updateCompanyAddressList(\'' + x[i].getElementsByTagName('Description')[0].childNodes[0].nodeValue + '\', checked);updateSummary();updateSiteList(\'' + x[i].getElementsByTagName('Company_Address')[0].childNodes[0].nodeValue + '\', checked);updateAffectedClients();\" /> ' + x[i].getElementsByTagName('Description')[0].childNodes[0].nodeValue + '<br />';
								txt2=txt2 + '<option value=\"' + x[i].getElementsByTagName('Company_Address')[0].childNodes[0].nodeValue +  '\" /> ' + x[i].getElementsByTagName('Description')[0].childNodes[0].nodeValue + '</option>';
							}
						}
					}
				}
				document.getElementById('companyAddresses').innerHTML=txt;
				document.getElementById('diversionCompanyAddressRecId').innerHTML=txt2;
			}
		}
		document.getElementById('loading-sites').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		document.getElementById('loading-sites2').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getCompanyAddressesByCompanyRecId.php?companyRecId=' + companyRecId + '&outagePreference=1',true);
		xmlhttp.send();
	}
	
	function updateSiteList(siteId, state) {
		if (state == true) {
			siteList.push(siteId);
		} else {
			for(i=siteList.length - 1; i >= 0; i--) {
				if(siteList[i] === siteId) {
					siteList.splice(i, 1);
				}
			}
		}
	}
	
	function updateAffectedClients(onload) {
		// console.log(siteList);
		if (siteList.length==0) {
			document.getElementById('affectedClients').innerHTML='N/A';
			return;
		}
		var queryString = '';
		for(var i = 0; i < siteList.length; i++) {
			queryString += i + '=' + siteList[i];

			//Append an & except after the last element
			if(i < siteList.length - 1) {
			   queryString += '&';
			}
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
				document.getElementById('loading-clients').innerHTML = '';
				var xmlDoc=xmlhttp.responseXML;
				var txt='';
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						affectedSiteList = [];
						for (i = 0; i < x.length; i++) {
							txt=txt + '<input type=\"checkbox\" class=\"affectedClients\" id=\"affectedClients[]\" name=\"affectedClients[]\" onclick=\" updateAffectedSiteList(\'' + x[i].getElementsByTagName('Company_RecId')[0].childNodes[0].nodeValue + '\', checked);\" value=\"' + x[i].getElementsByTagName('Company_RecId')[0].childNodes[0].nodeValue +  '\" /> ' + x[i].getElementsByTagName('Company_Name')[0].childNodes[0].nodeValue + '<br />';
						}
					}
				}
				if(txt!=''){
					document.getElementById('affectedClients').innerHTML=txt;
				} else{
					document.getElementById('affectedClients').innerHTML='N/A';
				}
				if(onload==true){
					checkSelectedClients($('#id').val());
				}
			}
		}
		// console.log (queryString);
		// document.getElementById('loading-clients').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getCompaniesBySitePref.php?'+queryString,true);
		xmlhttp.send();
	}
	
	// CHECK previously selected clients
	function checkSelectedClients(outageId){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			var xmlhttp=new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			var xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
		}
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				document.getElementById('loading-clients').innerHTML = '';
				var xmlDoc=xmlhttp.responseXML;
				var txt='';
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x.length > 0) {
						affectedClientsSelected = [];
						for (i = 0; i < x.length; i++) {
							affectedClientsSelected.push(x[i].getElementsByTagName('Company_RecId')[0].childNodes[0].nodeValue);	
						}
					}
				}
				for(var i=0; i<affectedClientsSelected.length; i++){
					$('.affectedClients').each(function (){
						if($(this).val()==affectedClientsSelected[i]){
							$(this).attr('checked', true);
						}
					});
				}
			}
		}
		// document.getElementById('loading-clients').innerHTML = '<img src=\"/img/ajax-loading.gif\" />';
		xmlhttp.open('GET','/cgi/new/Ajax/getAffectedClientsSelected.php?outageId='+outageId);
		xmlhttp.send();
	}
	
	function updateAffectedSiteList(affectedSiteId, state) {
		if (state == true) {
			affectedSiteList.push(affectedSiteId);
		} else {
			for(i=affectedSiteList.length - 1; i >= 0; i--) {
				if(affectedSiteList[i] === affectedSiteId) {
					affectedSiteList.splice(i, 1);
				}
			}
		}
	}
	
	function save(){
		$('#step').val('save');
		$('#companyRecId').removeAttr('disabled');
		$('#outageForm').submit();
	}
	
	function sendForApproval(){
		$('#companyRecId').removeAttr('disabled');
		$('#outageForm').submit();
	}
	
</script>";
}