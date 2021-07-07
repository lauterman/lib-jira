#!/usr/bin/php
<?php
require ('../lib-jira.php');

/*
 * create database connection
 */
$jira_db = new ServiceDeskDatabase('/tmp/jira.sql');

/*
 * get issue ids
 */

$issue_ids = array ();
try {
	$issue_ids = $jira_db->issue_get_ids();
} catch (ServiceDeskException $e) {
	$data = print_r($e, TRUE);
	echo 'new exception: ' . $data;
}

/*
 * loop over issues
 */

foreach ($issue_ids as $issue_id) {

	echo "\n\n===================== ISSUE [$issue_id] =====================\n\n";

	// get issues
	try {
		$issue = $jira_db->issue_get($issue_id);
	} catch (ServiceDeskException $e) {
		$data = print_r($e, TRUE);
		echo 'new exception: ' . $data;
	}

	foreach ($issue as $key => $value) {
		if (is_array($value)) {
			$value = print_r("[$key] => " . $value, true);
		}

		if ($key != 'attachments') {
			echo "[$key] => $value\n";
		}
	}

	// save attachments to file
	$attachments = $issue->attachments;
	foreach ($attachments as $attachment) {
		$filename = $attachment['attachment_filename'];
		$content = $attachment['attachment_content'];

		echo "[attachment-filename] => $filename\n";

		file_put_contents($filename . '-sqlite_db-out', base64_decode($content));
	}
}