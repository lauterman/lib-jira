#!/usr/bin/php
<?php

include ('localConfig.php');
require (JIRA_LIB_PATH . '/lib-jira.php');

// create database connection
$jira_db = new ServiceDeskDatabase(JIRA_FILE_DIR . '/' . SQL_LITE_FILE_NAME);



// issue save
if (0) {
    
    // create email object
    $issue = new stdClass();
    $issue->to = 'jkc103@psu.edu';
    $issue->from = 'jkc103@hwarang.css.psu.edu';
    $issue->cc = 'cc@psu.edu';
    $issue->bcc = 'bcc@psu.edu';
    $issue->subject = 'This is a test subject';
    $issue->body_text = 'This is the text body';
    $issue->body_html = '<h1><color=red>This is the html body</color></h1>';
    $issue->attachments = array (
        array (
            'filename' => '/tmp/test.txt',
            'content' => file_get_contents('/tmp/test.txt')
        ),
        array (
            'filename' => '/tmp/test.jpg',
            'content' => file_get_contents('/tmp/test.jpg')
        ),

    
    );
    
    try {
        $issue_id = $jira_db->issue_save($issue);
    } catch (ServiceDeskException $e) {
        $data = print_r($e, TRUE);
        echo 'new exception: ' . $data;
    }
    echo "issue_id: $issue_id\n\n";
}

// issues get ids
if (1) {
    try {
        $issue_ids = $jira_db->issue_get_ids();
    } catch (ServiceDeskException $e) {
        $data = print_r($e, TRUE);
        echo 'new exception: ' . $data;
    }
    $debug = print_r($issue_ids, true);
    echo "issue_ids: $debug\n\n";
}

// get issue
if (1) {
	if(!isset($issue_id))
    	$issue_id = 1;
    try {
        $issue = $jira_db->issue_get($issue_id);
    } catch (ServiceDeskException $e) {
        $data = print_r($e, TRUE);
        echo 'new exception: ' . $data;
    }

    foreach ($issue as $key => $value) {
    	if(is_array($value)) {
    	    $value = print_r($value, true);
    	}
        print "$key => $value\n";
    }
    echo "\n";
}

// get attachment
if (0) {
	if(!isset($issue_id))
    	$issue_id = 1;
    try {
        $issue = $jira_db->issue_get($issue_id);
    } catch (ServiceDeskException $e) {
        $data = print_r($e, TRUE);
        echo 'new exception: ' . $data;
    }

	// save attachments to file
	$attachments = $issue->attachments;
	foreach($attachments as $attachment) {
	    $filename = $attachment['attachment_filename'];
	    $content = $attachment['attachment_content'];
	    
	    file_put_contents($filename . '-example_db-out', base64_decode($content));
	}
}

exit;
?>
