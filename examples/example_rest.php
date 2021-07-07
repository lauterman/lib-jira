#!/usr/bin/php
<?php
include ('../test/localConfig.php');
require JIRA_LIB_PATH . '/lib-jira.php';

// test data
define("JIRA_PROJECT_KEY", "SD");
define("JIRA_ISSUE_TYPE_ISSUE_ID", "6");
define("TEST_ISSUE_KEY", "ITSD-23871");
define("NEW_USER", "jkc103");

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

//
// componentGet
if (0) {

    try {
        $rComponents = $jiraClient->componentGet('ASAS');
    } catch (Exception $e) {
        $data = print_r($e, TRUE);
        echo 'componentGet exception: ' . $data;
        exit;
    }
    $data = print_r($rComponents, TRUE);
    echo 'componentGet results: ' . $data;
}

//
// issueGet
if (0) {

    try {
        $rIssue = $jiraClient->issueGet('ITSD-19442');
    } catch (Exception $e) {
        $data = print_r($e, TRUE);
        echo 'issueGet exception: ' . $data;
        exit;
    }
    $data = print_r($rIssue, TRUE);
    echo 'issueGet results: ' . $data;
	
	
}

//
// issueMetaGet
// http://docs.atlassian.com/jira/REST/latest/#id252031
if (0) {

    try {
//      $projectKeys = '?projectKeys=' . JIRA_PROJECT_KEY;
//      $issuetypeIds = '&issuetypeIds=' . JIRA_ISSUE_TYPE_ISSUE_ID;
        $projectKeys = '?projectKeys=ITSD';
        $issuetypeIds = '&issuetypeIds=40';
        $expand = '&expand=projects.issuetypes.fields';

        $queryUrl = '/' . $projectKeys . $issuetypeIds . $expand;

        $results = $jiraClient->issueMetaGet($queryUrl);
    } catch (Exception $e) {
        $data = print_r($e, TRUE);
        echo 'issueMetaGet exception: ' . $data;
        exit;
    }
    $data = print_r($results, TRUE);
    echo 'issueMetaGet results: ' . $data;
}

//
// issueCreate
if (0) {

	// create issue class
	$issue = new StdClass();
	$issue->project = 'ITSD';
	$issue->type = JIRA_ISSUE_TYPE_ISSUE_ID;
	$issue->type = 6;
	$issue->summary = 'This is a Jim Test';
	$issue->description = 'Four score and seven years ago';

	// create array of custom fields [id] = value
	$fields = array ();
	$fields['priority'] = array('id' => '3');
	//	$fields[JIRA_FIELD_LAST_NAME] = 'Campbell';
	//	$fields[JIRA_FIELD_USERID] = 'jkc103';
	//	$fields[JIRA_FIELD_CUSTOMER_EMAIL] = 'jkc103@psu.edu';
	//	$fields[JIRA_FIELD_CONTACT_CHANNEL] = array (
	//		'id' => JIRA_FIELD_CONTACT_CHANNEL_VALUE
	//	);
	$issue->fields = $fields;

	try {
		$rIssue = $jiraClient->issueCreate($issue);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'issueCreate exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'issueCreate results: ' . $data;
}

//
// issueKeyChanged
if (0) {

	try {
		$rIssue = $jiraClient->issueKeyChanged($key);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'issueKeyChanged exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'issueKeyChanged results: ' . $data;
}

//
// issueSearch
if (0) {

	$queryObject = new stdClass();
	$queryObject->jql = 'project=SD and assignee=jkc103';
	$queryObject->startAt = 0;
	$queryObject->maxResults = 15;
	$queryObject->fields = array (
		'summary',
		'description',
		'status',
		'assignee'
	);

	try {
		$rIssue = $jiraClient->issueSearch($queryObject);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'issueSearch exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'issueSearch results: ' . $data;

}

//
// issueUpdate
if (0) {

	// create issue class
	$issue = new StdClass();
	$issue->key = TEST_ISSUE_KEY;
	$issue->summary = 'Wakka Wakka Wakka Wakka';
	//$issue->description = 'This is a Frank Schmank test';

	// create array of custom fields [id] = value
	$fields = array ();
	//    $fields[JIRA_FIELD_FIRST_NAME] = 'Frank';
	//    $fields[JIRA_FIELD_LAST_NAME] = 'Schmank';
	//    $fields[JIRA_FIELD_USERID] = 'fms007';
	//    $fields[JIRA_FIELD_CUSTOMER_EMAIL] = 'abc123@psu.edu';
	//	    $fields[JIRA_FIELD_CONTACT_CHANNEL] = array (
	//	        'id' => JIRA_FIELD_CONTACT_CHANNEL_VALUE
	//	    );

	if (empty ($fields)) {
		$fields['summary'] = 'Wakka Wakka Wakka Wakka';
	}
	$issue->fields = $fields;
	
	try {
		$rIssue = $jiraClient->issueUpdate($issue->key, $issue);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'issueUpdate exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo "\n\nissueUpdate results: " . $data . "\n\n";
}

//
// issueUserAccess
// admin perms required to see who has browse access to issues
if (0) {
	try {
		$rIssue = $jiraClient->issueUserAccess(TEST_ISSUE_KEY, JIRA_RPC_USERNAME);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'issueUserAccess exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'issueUserAccess results: ' . $data;
}

//
// commentCreate
if (0) {
	try {
		$rIssue = $jiraClient->commentCreate(TEST_ISSUE_KEY, 'I AM GETTING THE HANG OF THIS');
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'commentCreate exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'commentCreate results: ' . $data;
}

//
// commentGet
if (0) {
	try {
		$rIssue = $jiraClient->commentGet(TEST_ISSUE_KEY);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'commentGet exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'commentGet results: ' . $data;
}

//
// transitionGet
if (0) {
	try {
		$rIssue = $jiraClient->transitionGet(TEST_ISSUE_KEY);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'transitionGet exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'transitionGet results: ' . $data;
}

//
// transitionDo
if (0) {

	try {
		$rIssue = $jiraClient->transitionDo(TEST_ISSUE_KEY, 31);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'transitionDo exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'transitionDo results: ' . $data;
}

//
// attachmentCreate
if (0) {

	$attachment = '/home/jkc103/top.txt';

	try {
		$rIssue = $jiraClient->attachmentCreate(TEST_ISSUE_KEY, $attachment);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'attachmentCreate exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'attachmentCreate results: ' . $data;
}

//
// attachmentGet
if (1) {

	try {
		$rIssue = $jiraClient->attachmentGet(TEST_ISSUE_KEY);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'attachmentGet exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'attachmentGet results: ' . $data;
}

//
// projectGet
if (0) {
	try {
		$rIssue = $jiraClient->projectGet();
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'projectGet exception: ' . $data;
		exit;
	}
	$data = print_r($rIssue, TRUE);
	echo 'projectGet results: ' . $data;
}

//
// userSwitch
if (0) {
	try {
		$jiraClient->userSwitch(NEW_USER);
	} catch (Exception $e) {
		$data = print_r($e, TRUE);
		echo 'userSwitch exception: ' . $data;
		exit;
	}
	$data = print_r($jiraClient, TRUE);
	echo 'userSwitch results: ' . $data;
}

//
// worklog
if (0) {
	try {
        $worklog = array ();
        $worklog['comment'] = 'I did nothing.';
        $worklog['timeSpent'] = '10m';
        $rIssue = $jiraClient->worklogUpdate($issueKey, $worklog);
	} catch (Exception $e) {
        $data = print_r($e, TRUE);
        echo 'worklogUpdate exception: ' . $data;
        exit;
    }
    $data = print_r($jiraClient, TRUE);
    echo 'worklogUpdate results: ' . $data;
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
