#!/usr/bin/php
<?php
include ('localConfig.php');
require JIRA_LIB_PATH . '/lib-jira.php';

//
// create jira client
try {
	$jiraClient = new ServiceDeskRest(JIRA_REST_HOST);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'new exception: ' . $data;
}

//
// login
try {
	$jiraClient->login(JIRA_RPC_USERNAME, JIRA_RPC_PASSWORD);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'login exception: '; // . $data;
	exit;
}

// get user
try {
	$results = $jiraClient->userPermission('abc123', 'SDDEV', 'CREATE_ATTACHMENT');
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'permission exception: ' . $data;
	$results = 0;
}

if ($results) {
	$data = print_r($results, true);
	print "permissions: $data\n\n";
} else {
	print "NO PERMISSIONS\n\n";
}

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
