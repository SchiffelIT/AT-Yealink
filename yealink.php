<?php

define("AT_USER", "");
define("AT_PASS", "");
define("AT_INTEGRATION_CODE", "");


function request($endpoint, $method="GET", $params=array()) {
	// create curl resource
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "https://webservices18.autotask.net/ATServicesRest/V1.0" . $endpoint);

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	//set http method
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	
	//set auth header
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('UserName: ' . AT_USER, 'Secret: ' . AT_PASS, 'ApiIntegrationCode: ' . AT_INTEGRATION_CODE));
	
	//set body params
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);
	
	return $output;
}

$contacts = json_decode(request("/Contacts/query?search=" . urlencode('{"filter":[{"op":"gt","field":"id","value":"0"}]}')));
$companies = json_decode(request("/Companies/query?search=" . urlencode('{"filter":[{"op":"gt","field":"id","value":"0"}]}')));

$phonebook = array();

foreach($contacts->items as $contact) {
	if(property_exists($contact, "phone") && $contact->phone != "")
		$phonebook[trim($contact->phone)] = trim($contact->firstName . " " . $contact->lastName);
	if(property_exists($contact, "mobilePhone") && $contact->mobilePhone != "")
		$phonebook[trim($contact->mobilePhone)] = trim($contact->firstName . " " . $contact->lastName);
	if(property_exists($contact, "alternatePhone") && $contact->alternatePhone != "")
		$phonebook[trim($contact->alternatePhone)] = trim($contact->firstName . " " . $contact->lastName);
}

foreach($companies->items as $company) {
	if(property_exists($company, "phone") && $company->phone != "" && !array_key_exists($company->phone, $phonebook))
		$phonebook[trim($company->phone)] = trim($company->companyName);
	if(property_exists($company, "alternatePhone1") && $company->alternatePhone1 != "" && !array_key_exists($company->alternatePhone1, $phonebook))
		$phonebook[trim($company->alternatePhone1)] = trim($company->companyName);
	if(property_exists($company, "alternatePhone2") && $company->alternatePhone2 != "" && !array_key_exists($company->alternatePhone2, $phonebook))
		$phonebook[trim($company->alternatePhone2)] = trim($company->companyName);
}

$xmlPhonebook = array();

foreach($phonebook as $number => $name) {
	if(array_key_exists($name, $xmlPhonebook)) {
		$xmlPhonebook[$name][] = $number;
	} else {
		$xmlPhonebook[$name] = array($number);
	}
}

$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><YealinkIPPhoneDirectory></YealinkIPPhoneDirectory>");

foreach($xmlPhonebook as $name => $numbers) {
	$directoryEntry = $xml->addChild("DirectoryEntry");
	$directoryEntry->addChild("Name", str_replace("&", "&amp;", $name));
	
	foreach($numbers as $number) {
		$directoryEntry->addChild("Telephone", $number);
	}
}

header('Content-type: application/xml');
echo $xml->asXML();

?>