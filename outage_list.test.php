<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('new/ad_auth.inc.php');
require_once('new/CMS.inc.php');
require_once('new/CMS_Outage_test.class.php');	// TEST..

$htmlPage = new Html_Page;	
$outagesObj = new CMS_Outage_test;

$companiesObj = new CW_Company;
$companyAddressObj = new CW_Company_Address;
$outageTypesObj = new CMS_Outage_Type;
$outageStatusesObj = new CMS_Outage_Status;
$timeZonesObj = new CMS_Time_Zone;
$config = new Config;


$searchFields = array('step', 'orderBy', 'order', 'id', 'company', 'affected_companies', 'affected_sites', 'outage_type', 'start_time_from', 'start_time_to', 'end_time_from', 'end_time_to', 'status', 'responsible', 'ticket_no', 'client_notified');
$form = $htmlPage->initialiseFormData(array(), $searchFields);

$tdAlignCenter = 'text-align:center;';
$tdAlignRight = 'text-align:right;';
$tdAlignBottom = "valign='bottom'";

if ($form['step'] == 'go') {
	getResult();
	// echo 'export to excel..<br/>';
	// print_r($form);
} else{
	$htmlPage->title = 'Outage Register';
	$htmlPage->outputHeader();
	outputJavascript();
	displayContent();
	$htmlPage->outputFooter();
}

function displayContent() {
	global $htmlPage, $tdAlignBottom, $outagesObj, $companiesObj, $companyAddressObj, $outageTypesObj, $outageStatusesObj, $timeZonesObj;
	echo "
	
	<br/>
	<br/>
	<br/>
	<div class='ic-bar' id='' name=''>
		<span>Action</span>
		<!-- <input type='submit' id='' name='' value='Submit' onclick='submit();'> -->
		<div class='ic'><a class='ico' href='/cgi/new/Service_Desk/outage_new.test.php'><img src='/images/icons/flaticons/file68.png' alt='New' title='New'></a></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/note35.png' alt='Edit' title='Edit'></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/save15.png' alt='Save' title='Save'></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/recycling10.png' alt='Delete' title='Delete'></div>
		<div class='ic-inactive'><img src='/images/icons/flaticons/copy23.png' alt='Copy' title='Copy'></div>
		<div class='ic'><a href='#' onclick='exportToExcel();'><img src='/images/icons/flaticons/right153.png' alt='Export' title='Export'></a></div>
		<div class='ic'><a href='#' onclick='submit();'><img id='' name='' src='/images/icons/flaticons/search74.png' alt='Search' title='Search'></a></div>
	</div>
	<br/>
	<form id='outageRegisterForm' name='outageRegisterForm' action='' method='post'>
		<input type='hidden' name='step' value='go' />
		<input type='hidden' id='orderBy' name='orderBy' value='' />
		<input type='hidden' id='order' name='order' value='' />
		<table class='tableHeader'>
			<col width='6%'>
			<col width='12%'>
			<col width='12%'>
			<col width='12%'>
			<col width='6%'>
			<col width='5.05%'>	<!-- start time -->
			<col width='5.05%'>
			<col width='5.05%'>	<!-- end time -->
			<col width='5.05%'>
			<col width='6%'>
			<col width='12%'>
			<col width='6%'>
			<col width='6%'>
			<tr>
				<th><a href=\"#\" onclick=\"orderResults('id');\">Outage ID</a></th>
				<th><a href=\"#\" onclick=\"orderResults('Company_RecID');\">Company</a></th>
				<th><a href=\"#\" onclick=\"orderResults('affected_companies');\">Affected Companies</a></th>
				<th><a href=\"#\" onclick=\"orderResults('affected_sites');\">Affected Sites</a></th>
				<th><a href=\"#\" onclick=\"orderResults('type_id');\">Outage Type</a></th>
				<th colspan='2'><a href=\"#\" onclick=\"orderResults('start_time');\">Start Time</a></th>
				<th colspan='2'><a href=\"#\" onclick=\"orderResults('end_time');\">End Time</a></th>
				<th><a href=\"#\" onclick=\"orderResults('status_id');\">Status</a></th>
				<th><a href=\"#\" onclick=\"orderResults('responsible');\">Is March IT or any of its sub-contractors responsible for causing the outage?</a></th>
				<th style='text-align:right'><a href=\"#\" onclick=\"orderResults('SR_Service_RecID');\">Ticket #</a></th>
				<th style='text-align:center'>Duration</th>
				<!-- <th><a href=\"#\" onclick=\"orderResults('client_notified');\">Client Notified?</a></th> -->
			</tr>
			<tr>
				<td $tdAlignBottom ><input class='input' type='text' style='width:100%' id='id' name='id'></td>
				<td $tdAlignBottom >
					<input list='companyList' id='company' name='company' style='width:100%' >
					<datalist id='companyList' name='companyList'>
						<select>
							<option></option>";
$companyRecIds = $companiesObj->getCompanyRecIds();
if($companyRecIds){
	foreach($companyRecIds as $companyRecId){
		$company = $companiesObj->getCompanyByRecId($companyRecId);
		if($company['Delete_Flag']!=1){
			echo "			<option id='{$company['Company_RecID']}' value='{$company['Company_Name']}'></option>";
		}
	}
}
echo "
						</select>
					</datalist>
				</td>
				<td $tdAlignBottom >
					<!--<input class='input' type='text' style='width:100%' id='affected_companies' name='affected_companies'>-->
					<input list='companyList' id='affected_companies' name='affected_companies' style='width:100%' >
					<datalist id='companyList' name='companyList'>
						<select>
							<option></option>";
$companyRecIds = $companiesObj->getCompanyRecIds();
if($companyRecIds){
	foreach($companyRecIds as $companyRecId){
		$company = $companiesObj->getCompanyByRecId($companyRecId);
		if($company['Delete_Flag']!=1){
			echo "			<option id='{$company['Company_RecID']}' value='{$company['Company_Name']}'></option>";
		}
	}
}
echo "
						</select>
					</datalist>
				</td>
				<td $tdAlignBottom >
					<!--<input class='input' type='text' style='width:100%' id='affected_sites' name='affected_sites'>-->
					<input list='siteList' id='affected_sites' name='affected_sites' style='width:100%' >
					<datalist id='siteList' name='siteList'>
						<select>
							<option></option>";
$companyAddressRecIds = $companyAddressObj->getCompanyAddressRecIdsAll();
if($companyAddressRecIds){
	foreach($companyAddressRecIds as $companyAddressRecId){
		$companyAddress = $companyAddressObj->getCompanyAddressByRecId($companyAddressRecId);
		if($companyAddress['Inactive_Flag']!=1){
			echo "			<option id='{$companyAddress['Company_Address']}' value='{$companyAddress['Description']}'></option>";
		}
	}
}
echo "
						</select>
					</datalist>
				</td>
				<td $tdAlignBottom >
					<select style='width:100%' id='outage_type' name='outage_type'>
						<option value=''></option>";
	
	$outageIds = $outageTypesObj->getOutageTypeIds();
	foreach($outageIds as $outageId){
		$outage = $outageTypesObj->getOutagetypeById($outageId);
		if($outage){
			echo "
						<option value='{$outage['id']}'>{$outage['title']}</option>";
		}
	}
	echo "
					</select>
				</td>
				<td $tdAlignBottom style='border-right:none;'>From:<input class='input' type='text' style='width:100%' id='start_time_from' name='start_time_from'></td>
				<td $tdAlignBottom >To:<input class='input' type='text' style='width:100%' id='start_time_to' name='start_time_to'></td>
				<td $tdAlignBottom style='border-right:none;'>From:<input class='input' type='text' style='width:100%' id='end_time_from' name='end_time_from'></td>
				<td $tdAlignBottom >To:<input class='input' type='text' style='width:100%' id='end_time_to' name='end_time_to'></td>
				<td $tdAlignBottom >
					<select style='width:100%' id='status' name='status'>
						<option value=''></option>";
	$outageStatusIds = $outageStatusesObj->getOutageStatusIds();
	foreach($outageStatusIds as $outageStatusId){
		$status = $outageStatusesObj->getStatusById($outageStatusId);
		if($status){
			echo "
						<option value='{$status['id']}'>{$status['title']}</option>";
		}
	}
	echo "
					</select>
				</td>
				<td $tdAlignBottom >
					<select style='width:100%' id='responsible' name='responsible'>
						<option value=''></option>
						<option value='1'>Yes</option>
						<option value='0'>No</option>
					</select>
				</td>
				<td $tdAlignBottom ><input class='input' type='text' style='width:100%' id='ticket_no' name='ticket_no'></td>
				<td style='text-align:center'><img id='' name='' height=20 width=20 src='/images/icons/flaticons/alarm31.png' alt='' title=''></td>
				<!-- <td $tdAlignBottom >
					<select style='width:100%' id='client_notified' name='client_notified'>
						<option value=''></option>
						<option value='1'>Yes</option>
						<option value='0'>No</option>
					</select>
				</td> -->
			</tr>
		</table>
	</form>";
	
	echo "
		<div id='searchResults'></div>
		<div style='text-align: center; width: 100%;'>
			<span id='loading-results'></span>
		</div>
		<div id='searchResultPages'>
			<p>&nbsp;</p>
			<br />
			<p>&nbsp;</p>
		</div>
		<br/>
		<br/>
		<br/>
		";
}

function outputJavascript(){
	global $config;
	
	echo "
	<script>

	var page = 1;
	var orderBy = 'id';
	var order = 'desc';
	var resultsPerPage = " . $config->resultsPerPage . ";
	var linksPerPage = " . $config->linksPerPage . ";
	var outage_duration = [];
	
	$(document).ready(function(){
		resetPage();
		resetOrder();
		updateResults();
		updateSearchResultPages();
	
		// Submit search form on pressing enter
		$('input').keypress(function(e){
			if(e.which==13){
				resetPage();
				resetOrder();
				updateResults();
				updateSearchResultPages();
				return false;
			}
		});
		
	});
	
	$(function() {
		$('#start_time_from').datepicker({
			// timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				// updateSummary();
			}
		});
		$('#start_time_to').datepicker({
			// timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				// updateSummary();
			}
		});
		$('#end_time_from').datepicker({
			// timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				// updateSummary();
			}
		});
		$('#end_time_to').datepicker({
			// timeFormat: 'hh:mm tt',
			dateFormat: 'dd-mm-yy',
			onSelect: function(dateText) {
				// updateSummary();
			}
		});
	});
	
	function exportToExcel(){
		// alert('exporting..');
		$('#outageRegisterForm').submit();
	}
	
	function resetPage() {
		page = 1;
	}
	
	function resetOrder() {
		orderBy = 'id';
		order = 'desc';
	}

	function showResultPage(newPage) {
		page = newPage;
		updateResults();
		updateSearchResultPages();
	}
	
	function orderResults(newOrderBy) {
		if (orderBy == newOrderBy) {
			if (order == 'asc') {
				order = 'desc';
			} else {
				order = 'asc';
			}
		} else {
			order = 'asc';
		}
		orderBy = newOrderBy;
		$('#orderBy').val(newOrderBy);
		$('#order').val(order);
		// console.log($('#orderBy').val());
		// console.log($('#order').val());
		page = 1;
		updateResults();
		updateSearchResultPages();
	}
	
	function updateResults() {
		var fields = new Array('id', 'company', 'affected_companies', 'affected_sites', 'outage_type', 'start_time', 'end_time', 'status', 'responsible', 'ticket_no', 'duration'); //, 'client_notified');
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			var xmlhttp=new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			var xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
		}
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				document.getElementById('loading-results').innerHTML = '';
				var txt='<table class=\"searchResults\"><tr><col width=\"6%\"><col width=\"12%\"><col width=\"12%\"><col width=\"12%\"><col width=\"6%\"><col width=\"10%\"><col width=\"10%\"><col width=\"6%\"><col width=\"12%\"><col width=\"6%\"><col width=\"6%\">';
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					for (i=0;i<x.length;i++) {
						if (x[i].getElementsByTagName('id')[0].hasChildNodes()) {
							var outage_id = x[i].getElementsByTagName('id')[0].childNodes[0].nodeValue;
							txt = txt + '<tr>';
							txt = txt + '<td><a href=\'outage_view.test.php?id=' + x[i].getElementsByTagName(fields[0])[0].childNodes[0].nodeValue + '\'>' + x[i].getElementsByTagName(fields[0])[0].childNodes[0].nodeValue + '</a></td>';
							for (j=1; j < fields.length; j++) {
								// Get Start Time based on Time Zone
								time_zone = x[i].getElementsByTagName('time_zone')[0].childNodes[0].nodeValue;
								// if(time_zone == 'AEST'){
									st_time = x[i].getElementsByTagName('str_start_time')[0].childNodes[0].nodeValue;
								// }
								// else if(time_zone == 'AEDT'){
									// st_time = x[i].getElementsByTagName('str_start_time')[0].childNodes[0].nodeValue + 1*60*60 ;
								// }
								// else if(time_zone == 'AWST'){
									// st_time = x[i].getElementsByTagName('str_start_time')[0].childNodes[0].nodeValue - 2*60*60 ;
								// }
								// else if(time_zone == 'ACST'){
									// st_time = x[i].getElementsByTagName('str_start_time')[0].childNodes[0].nodeValue - 0.5*60*60 ;
								// }
								// else if(time_zone == 'ACDT'){
									// st_time = x[i].getElementsByTagName('str_start_time')[0].childNodes[0].nodeValue + 0.5*60*60 ;
								// }
								if(fields[j]=='ticket_no'){
									txt = txt + '<td style=\'text-align:right;\'>';
									if (x[i].getElementsByTagName(fields[j])[0].hasChildNodes()) {
										txt = txt + x[i].getElementsByTagName(fields[j])[0].childNodes[0].nodeValue;
									}
									txt = txt + '</td>';
								}
								else if(fields[j]=='duration'){
									current_id = 'ctr_'+ outage_id;
									if(x[i].getElementsByTagName('status')[0].childNodes[0].nodeValue != 'Resolved' && x[i].getElementsByTagName('status')[0].childNodes[0].nodeValue != 'Cancelled' && x[i].getElementsByTagName('status')[0].childNodes[0].nodeValue != 'Closed'){
										outage_duration.push({id:current_id, time:st_time, timeZone:time_zone});
									}
									txt = txt + '<td id=\'ctr_'+ outage_id +'\' style=\'text-align:right;\'> - </td>';
								}
								else{
									txt = txt + '<td>';
									if (x[i].getElementsByTagName(fields[j])[0].hasChildNodes()) {
										txt = txt + x[i].getElementsByTagName(fields[j])[0].childNodes[0].nodeValue;
									}
									txt = txt + '</td>';
								}
							}
						}
					}
				}
		
				txt = txt + '</table>';
				document.getElementById('searchResults').innerHTML=txt;
			}
		}
		var search = '';
		if (document.getElementById('id').value != '') {
			search = search + '&id=' + document.getElementById('id').value;
		}
		if (document.getElementById('company').value != '') {
			search = search + '&company=' + document.getElementById('company').value;
		}
		if (document.getElementById('outage_type').value != '') {
			search = search + '&outage_type=' + document.getElementById('outage_type').value;
		}
		if (document.getElementById('status').value != '') {
			search = search + '&status=' + document.getElementById('status').value;
		}
		if (document.getElementById('start_time_from').value != '') {
			search = search + '&start_time_from=' + document.getElementById('start_time_from').value;
		}
		if (document.getElementById('start_time_to').value != '') {
			search = search + '&start_time_to=' + document.getElementById('start_time_to').value;
		}
		if (document.getElementById('end_time_from').value != '') {
			search = search + '&end_time_from=' + document.getElementById('end_time_from').value;
		}
		if (document.getElementById('end_time_to').value != '') {
			search = search + '&end_time_to=' + document.getElementById('end_time_to').value;
		}
		if (document.getElementById('ticket_no').value != '') {
			search = search + '&ticket_no=' + document.getElementById('ticket_no').value;
		}
		if (document.getElementById('affected_companies').value != '') {
			search = search + '&affected_companies=' + document.getElementById('affected_companies').value;
		}
		if (document.getElementById('affected_sites').value != '') {
			search = search + '&affected_sites=' + document.getElementById('affected_sites').value;
		}
		if (document.getElementById('responsible').value != '') {
			search = search + '&responsible=' + document.getElementById('responsible').value;
		}
		// if (document.getElementById('client_notified').value != '') {
			// search = search + '&client_notified=' + document.getElementById('client_notified').value;
		// }
		search = search + '&page=' + page;
		document.getElementById('searchResults').innerHTML='';
		document.getElementById('loading-results').innerHTML = '<img src=\"/img/ajax-loader-bar.gif\" />';
		var queryStr = 'orderBy=' + orderBy + '&order=' + order + search;
		// console.log(queryStr);
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageRegisterResults.php?' + queryStr,true);
		xmlhttp.send();
	}
	
	function updateSearchResultPages() {
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			var xmlhttp=new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			var xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');
		}
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				var txt='<p style=\"text-align: center;\">Results Page:</p><br /><p style=\"text-align: center;\">';
				var xmlDoc=xmlhttp.responseXML;
				if (xmlDoc) {
					var x=xmlDoc.getElementsByTagName('row');
					if (x[0].getElementsByTagName('count')[0].hasChildNodes()) {
						var resultCount = x[0].getElementsByTagName('count')[0].childNodes[0].nodeValue;
						var totalPages = Math.ceil(resultCount / resultsPerPage);
						var firstPage = (page - Math.floor(linksPerPage / 2));
						if (firstPage < 1) {
							firstPage = 1;
						}
						if (totalPages > linksPerPage) {
							if (page > (totalPages - (linksPerPage - 1))) {
								firstPage = (totalPages - (linksPerPage - 1));
							}
						}
						var lastPage = (firstPage + (linksPerPage - 1));
						if (lastPage > totalPages) {
							lastPage = totalPages;
						}
						var thishref = '';
						if (firstPage > 1) {
							txt = txt + '<a href=\"#\" onclick=\"showResultPage(1)\">1</a>&nbsp;&nbsp;...&nbsp;&nbsp;';
						}
						for (i=firstPage; i <= lastPage; i++) {
							if (i == page) {
								txt = txt + i + '&nbsp;&nbsp;';
							} else {
								txt = txt + '<a href=\"#\" onclick=\"showResultPage(' + i + ')\">' + i + '</a>&nbsp;&nbsp;';
							}
						}
						if (lastPage < totalPages) {
							txt = txt + '...&nbsp;&nbsp;<a href=\"#\" onclick=\"showResultPage(' + totalPages + ')\">' + totalPages + '</a>';
						}
						txt = txt + '</p>';

					}
				}
				document.getElementById('searchResultPages').innerHTML=txt;
			}
		}
		document.getElementById('searchResultPages').innerHTML='';
		var search = '';
		
		if (document.getElementById('id').value != '') {
			search = search + '&id=' + document.getElementById('id').value;
		}
		if (document.getElementById('company').value != '') {
			search = search + '&company=' + document.getElementById('company').value;
		}
		if (document.getElementById('outage_type').value != '') {
			search = search + '&outage_type=' + document.getElementById('outage_type').value;
		}
		if (document.getElementById('status').value != '') {
			search = search + '&status=' + document.getElementById('status').value;
		}
		if (document.getElementById('start_time_from').value != '') {
			search = search + '&start_time_from=' + document.getElementById('start_time_from').value;
		}
		if (document.getElementById('start_time_to').value != '') {
			search = search + '&start_time_to=' + document.getElementById('start_time_to').value;
		}
		if (document.getElementById('end_time_from').value != '') {
			search = search + '&end_time_from=' + document.getElementById('end_time_from').value;
		}
		if (document.getElementById('end_time_to').value != '') {
			search = search + '&end_time_to=' + document.getElementById('end_time_to').value;
		}
		if (document.getElementById('ticket_no').value != '') {
			search = search + '&ticket_no=' + document.getElementById('ticket_no').value;
		}
		if (document.getElementById('affected_companies').value != '') {
			search = search + '&affected_companies=' + document.getElementById('affected_companies').value;
		}
		if (document.getElementById('affected_sites').value != '') {
			search = search + '&affected_sites=' + document.getElementById('affected_sites').value;
		}
		if (document.getElementById('responsible').value != '') {
			search = search + '&responsible=' + document.getElementById('responsible').value;
		}
		// if (document.getElementById('client_notified').value != '') {
			// search = search + '&client_notified=' + document.getElementById('client_notified').value;
		// }
		search = search + '&page=' + page;
		// console.log(search);
		xmlhttp.open('GET','/cgi/new/Ajax/getOutageRegisterResultsCount.php?search=1'+search,true);
		xmlhttp.send();
	}
	
	function submit(){
		resetPage();
		resetOrder();
		updateResults();
		updateSearchResultPages();
		return false;
	}
	
	function displayDuration(){
		now = new Date().getTime();
		offset = new Date().getTimezoneOffset();
		// console.log(offset);
		for(var key in outage_duration){
			id = outage_duration[key]['id'];
			if(offset == -600){	// Brisbane Time at User end
				st_time = Number(outage_duration[key]['time']);
				if(outage_duration[key]['timeZone'] == 'AEDT'){	// Sydney Time for Outage Start Time
					st_time += 1*60*60;
				} else if(outage_duration[key]['timeZone'] == 'AWST'){	// WA Time for Outage Start Time
					st_time -= 2*60*60;
				}else if(outage_duration[key]['timeZone'] == 'ACST'){	// NT Time for Outage Start Time
					st_time -= 0.5*60*60;
				}else if(outage_duration[key]['timeZone'] == 'ACDT'){	// SA Time for Outage Start Time
					st_time += 0.5*60*60;
				}
			} else{
				break;
			}
			// console.log(st_time);
			diff = now - st_time*1000;
			hours = diff / 36e5;
			minutes  = Math.floor(( diff % 36e5) / 6e4);
			seconds  = Math.floor(( diff % 6e4) / 1000 );
			if(hours<=0){
				if(hours<0 && hours > -1){
					hours = '-' + Math.ceil(hours);
				} else{
					hours = Math.ceil(hours);
				}
				$('#'+id).html(hours +'h '+ minutes*-1 +'m '+ seconds*-1 +'s');
			} else{
				hours = Math.floor(hours);
				$('#'+id).html(hours +'h '+ minutes +'m '+ seconds +'s');
			}
		}
	}
	
	setInterval(function(){
		displayDuration();
	}, 1000);
	
	</script>";

}


function getResult() {
	global $htmlPage, $form, $common, $config, $companiesObj, $companyAddressObj, $outageTypesObj, $outagesObj, $timeZonesObj, $outageStatusesObj;

	// Connet to database
	$conn = new PDO("mysql:host=$config->mit_cms_db_host;dbname=$config->mit_cms_db", $config->mit_cms_db_username, $config->mit_cms_db_password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	// Insert data
	$objWorksheet = $objPHPExcel->setActiveSheetIndex(0);

	$headings = array(
		'Outage ID' => 10,
		'Company' => 25,
		'Affected Companies' => 30,
		'Affected Sites' => 30,
		'Outage Type' => 15,
		'Start Time' => 20,
		'End Time' => 20,
		'Status' => 15,
		'Is March IT or any of its sub-contractors responsible for causing the outage?' => 20,
		'Ticket #' => 10,
		
	);
	
	$columnnIndex = 0;
	$rowIndex = 1;
	foreach ($headings as $heading => $columnWidth) {
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $heading);
		$objWorksheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($columnnIndex))->setWidth($columnWidth);
		$columnnIndex++;
	}
	$rowIndex++;
	
	// Order By Clause
	$orderByClauses = array(
		'id' => 'id',
		'Company_RecID' => 'Company_RecID',
		'affected_companies' => 'affected_companies',
		'affected_sites' => 'affected_sites',
		'type_id' => 'type_id',
		'start_time' => 'start_time',
		'end_time' => 'end_time',
		'status_id' => 'status_id',
		'responsible' => 'responsible',
		// 'ticket_no' => '',
	);

	// Where Clause
	$whereClauses = array();

	// outage id
	if (!empty($form['id'])) {
		$whereClauses[] = "id like '%" . mysql_real_escape_string($form['id']) . "%'";
	}

	// company
	if (!empty($form['company'])) {
		// $whereClauses[] = "Company_RecID = '" . mysql_real_escape_string($form['company']) . "'";
		$companyRecIds = $companiesObj->getCompanyRecIdsByName($form['company'], true);
		$whereClauseStr = '(';
		foreach($companyRecIds as $companyRecId){
			$whereClauseStr .= "Company_RecID = '" . $companyRecId . "' or ";
		}
		$whereClauseStr = substr($whereClauseStr, 0, -3);
		$whereClauses[] = $whereClauseStr . ')';
	}

	// outage type
	if (!empty($form['outage_type'])) {
		$whereClauses[] = "type_id = '" . mysql_real_escape_string($form['outage_type']) . "'";
	}

	// clint notified
	if (!empty($form['client_notified'])) {
		$whereClauses[] = "client_notified = '" . $form['client_notified'] . "'";
	}

	// responsible
	if ($form['responsible']=='1' || $form['responsible']=='0') {
		$whereClauses[] = "responsible = '" . $form['responsible'] . "'";
	}

	// Affected Companies
	if (!empty($form['affected_companies'])) {
		$companyRecIds = $companiesObj->getCompanyRecIdsByName($form['affected_companies'], true);
		$whereClauseStr = '(';
		foreach($companyRecIds as $companyRecId){
			$whereClauseStr .= "affected_companies like '%" . $companyRecId . "%' or ";
		}
		$whereClauseStr = substr($whereClauseStr, 0, -3);
		$whereClauses[] = $whereClauseStr . ')';
	}

	// Affected Sites
	if (!empty($form['affected_sites'])) {
		$companyAddressRecIds = $companyAddressObj->getCompanyAddressRecIdsByDescription($form['affected_sites'], true);
		$whereClauseStr = '(';
		foreach($companyAddressRecIds as $companyAddressRecId){
			$whereClauseStr .= "affected_sites like '%" . $companyAddressRecId . "%' or ";
		}
		$whereClauseStr = substr($whereClauseStr, 0, -3);
		$whereClauses[] = $whereClauseStr . ')';
	}

	// status
	if (!empty($form['status'])) {
		// $status_id = $outageStatusesObj->getStatusByTitle($form['status']);
		$whereClauses[] = "status_id = '" . mysql_real_escape_string($form['status']) . "'";
	}

	// start time to and from range
	if (!empty($form['start_time_from']) && !empty($form['start_time_to'])) {
		$start_time_to = strtotime('+23 hours 59 minutes', mysql_real_escape_string(strtotime($form['start_time_to'])));
		$whereClauses[] = "start_time >= '" . strtotime(mysql_real_escape_string($form['start_time_from'])) . "' and start_time <= '" . $start_time_to . "'";
	}
	// start time from only
	else if (!empty($form['start_time_from']) && empty($form['start_time_to'])) {
		$whereClauses[] = "start_time >= '" . strtotime(mysql_real_escape_string($form['start_time_from'])) . "'";
	}
	// start time to only
	else if (empty($form['start_time_from']) && !empty($form['start_time_to'])) {
		$start_time_to = strtotime('+23 hours 59 minutes', mysql_real_escape_string(strtotime($form['start_time_to'])));
		$whereClauses[] = "start_time <= '" . $start_time_to . "'";
	}
	// end time to and from range
	if (!empty($form['end_time_from']) && !empty($form['end_time_to'])) {
		$end_time_to = strtotime('+23 hours 59 minutes', mysql_real_escape_string(strtotime($form['end_time_to'])));
		$whereClauses[] = "end_time >= '" . strtotime(mysql_real_escape_string($form['end_time_from'])) . "' and end_time <= '" . $end_time_to . "'";
	}
	// end time from only
	else if (!empty($form['end_time_from']) && empty($form['end_time_to'])) {
		$whereClauses[] = "end_time >= '" . strtotime(mysql_real_escape_string($form['end_time_from'])) . "'";
	}
	// end time to only
	else if (empty($form['end_time_from']) && !empty($form['end_time_to'])) {
		$end_time_to = strtotime('+23 hours 59 minutes', mysql_real_escape_string(strtotime($form['end_time_to'])));
		$whereClauses[] = "end_time <= '" . $end_time_to . "'";
	}

	// ticket #
	if (!empty($form['ticket_no'])) {
		$whereClauses[] = "SR_Service_RecID like '%" . mysql_real_escape_string($form['ticket_no']) . "%'";
	}
	
	// SQL statement
	$query = 'select * from ( select @row:=@row+1 as row, id, Company_RecID, type_id, time_zone_id, start_time, end_time, status_id, responsible, client_notified, affected_companies, affected_sites, SR_Service_RecID from vw_outage_register, (select @row:=0) as t where 1=1 and deleted is NULL';
	
	// where clause
	if (!empty($whereClauses)) {
		$query .= '
			and ';
	}
	foreach ($whereClauses as $whereClause) {
		$query .= $whereClause . ' and ';
	}
	if (!empty($whereClauses)) {
		$query = substr($query, 0, -4);
	}
	
	// order by
	if (empty($form['orderBy'])) {
		$form['orderBy'] = 'id';
	}
	if (empty($form['order'])) {
		$form['order'] = 'desc';
	}
	$query .= ' order by ' . $form['orderBy'] . ' ' . $form['order'] . ', ';
	// foreach ($orderByClauses as $key => $value) {
		// if ($key != $form['orderBy']) {
			// $query .= $value . ' ' . $form['order'] . ', ';
		// }
	// }
	$query = substr($query, 0, -2);
	$query .= '
		) as RowConstrainedResult
		order by row';
	// echo $query;
	// exit();

	$stmt = $conn->query($query);
	
	while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
	// while ($row = mysql_fetch_assoc($result)) {
		// Company Name
		$companyName = '';
		$company = $companiesObj->getCompanyByRecId($row['Company_RecID']);
		if ($company) {
			$companyName = $company['Company_Name'];
		}

		// Outage Type
		$typeName = '';
		$type = $outageTypesObj->getOutageTypeById($row['type_id']);
		if ($type) {
			$typeName = $type['title'];
		}
		// Time zone
		$timeZoneCode = '';
		$timeZone = $timeZonesObj->getTimeZoneById($row['time_zone_id']);
		if ($timeZone) {
			$timeZoneCode = $timeZone['code'];
		}
		// Time
		$startTime = ($row['start_time']!=null) ? date("d/m/Y h:i a", $row['start_time']) . ' ' . $timeZone['code'] : '' ;
		$endTime = ($row['end_time']!=null) ? date("d/m/Y h:i a", $row['end_time']) . ' ' . $timeZone['code'] : '' ;
		
		// Status
		$statusName = '';
		$status = $outageStatusesObj->getStatusById($row['status_id']);
		if ($status) {
			$statusName = $status['title'];
		}
		// Affected Companies
		$affectedCompanies = array();
		$clientRecIds = explode(',', $row['affected_companies']);
		foreach($clientRecIds as $clientRecId){
			$client = $companiesObj->getCompanyByRecId($clientRecId);
			$affectedCompanies[] = $client['Company_Name'];
		}
		$affectedCompaniesStr = implode(', ', $affectedCompanies);
		
		// Affected Sites
		$affectedSites = array();
		$siteRecIds = explode(',', $row['affected_sites']);
		foreach($siteRecIds as $siteRecId){
			$site = $companyAddressObj->getCompanyAddressByRecId($siteRecId);
			$affectedSites[] = $site['Description'];
		}
		$affectedSitesStr = implode(', ', $affectedSites);
	
		$columnnIndex = 0;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $row['id']); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $companyName); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $affectedCompaniesStr); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $affectedSitesStr); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $typeName); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $startTime); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $endTime); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $statusName); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, ($row['responsible']==1)?'Yes':'No'); $columnnIndex++;
		$objWorksheet->setCellValueByColumnAndRow($columnnIndex, $rowIndex, $row['SR_Service_RecID']); $columnnIndex++;
		$rowIndex++;
	}
	
	$heading = array(
		'font' => array(
			'bold' => true,
			'size' => '11'
		)
	);
	$columnIndex = PHPExcel_Cell::stringFromColumnIndex(count($headings));
	$objWorksheet->getStyle('A1:' . $columnIndex . '1')->applyFromArray($heading);

	$objWorksheet->freezePaneByColumnAndRow(0, 2);

	// Detect browser
	$extension = '.xlsx';
	if (stripos($_SERVER['HTTP_USER_AGENT'], 'chrome') !== false) {
		$extension = '';
	}

	// Redirect output to a client’s web browser (Excel2007)
	$filename = 'Outage Register (' . date('d-m-Y') . ')' . $extension;
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="' . $filename . '"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}

