#!/usr/bin/php
<?php

require ('../test/localConfig.php');
require_once (JIRA_LIB_PATH . '/lib-jira.php');

//
// client create
print "creating jira client\n";
$jiraClient = new stdClass();
try {
	$jiraClient = new ServiceDeskRest(JIRA_REST_HOST);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'new exception: ' . $data;
}
print "finished creating jira client\n\n";

//
// client login
print "jira client logging in\n";
try {
	$jiraClient->login(JIRA_RPC_USERNAME, JIRA_RPC_PASSWORD);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'login exception: ' . $data;
	//error_handler($e);
	exit;
}
print "jira client finished logging in\n\n";


//
// get alowable fields
try {
	$projectKeys = '?projectKeys=GRAD';
	$issuetypeIds = '&issuetypeIds=110';
	$expand = '&expand=projects.issuetypes.fields';

	$queryUrl = '/' . $projectKeys . $issuetypeIds . $expand;

	$results = $jiraClient->issueMetaGet($queryUrl);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'issueMetaGet exception: ' . $data;
	exit;
}

$fields = $results['projects'][0]['issuetypes'][0]['fields'];
foreach ($fields as $key => $value ) {
    print $value['name'] . "\t\t\t[$key]\n";
}
//$data = print_r($results, TRUE);
//echo 'issueMetaGet results: ' . $data;



//
// logout
try {
    $jiraClient->logout();
} catch (Exception $e) {
    $data = print_r($e, TRUE);
    echo 'logout exception: ' . $data;
    exit;
}
?>