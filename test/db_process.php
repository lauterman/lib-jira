#!/usr/bin/php
<?php


/**
 * JIRA Custom Code library
 *
 * @package lib-jira
 * @file db_process.php
 * @created 04.23.2013
 * @author JKCampbell <jimcampbell@psu.edu>
 * 
 */

include ('../test/localConfig.php');
require (JIRA_LIB_PATH . '/lib-jira.php');

// create database connection
$db_file = JIRA_FILE_DIR . '/' . SQL_LITE_FILE_NAME;
$jira_db = new ServiceDeskDatabase($db_file);
echo "DB File: $db_file\n";

// get issue ids
try {
	$issue_ids = $jira_db->issue_get_ids();
} catch (ServiceDeskException $e) {
	$data = print_r($e, TRUE);
	echo 'new exception: ' . $data;
}
$debug = print_r($issue_ids, true);
echo "issue_ids: $debug\n\n";

// get individual issues
foreach ($issue_ids as $id) {

	try {
		$issue = $jira_db->issue_get($id);
	} catch (ServiceDeskException $e) {
		$data = print_r($e, TRUE);
		echo 'new exception: ' . $data;
	}
    
    print "\n\n----------------------------\nTHIS IS ISSUE [$id]\n\n" . print_r($issue, TRUE) . "\nEND ISSUE [$id]\n----------------------------\n\n";

    continue;
    
	foreach ($issue as $key => $value) {
        
		if (is_array($value)) {
			$value = print_r($value, true);
		}
		print "$key => $value\n";
        
        
	}
	echo "\n";
}
?>

