#!/usr/bin/php
<?php
require ('localConfig.php');
require_once (JIRA_LIB_PATH . '/lib-jira.php');

$ISSUE_KEY = 'LMS-6772';
$DELETE_FILTER = "Email Parser commented on LMS-6772";
$DELETE = 0;

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
	exit;
}
print "jira client finished logging in\n\n";

//
// get comments
try {
	$rComments = $jiraClient->commentGet($ISSUE_KEY);
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'commentGet exception: ' . $data;
	exit;
}
$data = print_r($rComments, TRUE);
//echo 'commentGet results: ' . $data;

//
// loop over comments and delete them
$comments = $rComments['comments'];
foreach ($comments as $comment) {

	$id = $comment['id'];
	$body = $comment['body'];

	if (preg_match("/$DELETE_FILTER/", $body)) {

        // actually do the delete
		if ($DELETE) {
			echo "deleting comment: $id\n";
			try {
				$jiraClient->commentDelete($ISSUE_KEY, $id);
			} catch (Exception $e) {
				$data = print_r($e, TRUE);
				echo 'commentDelete exception: ' . $data;
				exit;
			}
		}
	} else {
		echo "keeping comment:$id\n[$id] \n$body\n\n\n";
	}
}

//
// logout
print "jira client finished logging out\n";
try {
	$jiraClient->logout();
} catch (Exception $e) {
	$data = print_r($e, TRUE);
	echo 'logout exception: ' . $data;
	exit;
}
print "jira client finished logging out\n\n";
?>
