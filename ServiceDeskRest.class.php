<?php


/**
 * JIRA REST library
 *
 * @package lib-jira
 * @file ServiceDeskRest.class.php
 * @created 11.15.2012
 * @author JKCampbell <jimcampbell@psu.edu>
 * http://docs.atlassian.com/jira/REST/latest
 */

class ServiceDeskRest {

	private $host;
	private $username;
	private $password;

	private $pclient;

	/**
	 * build! destroy!  
	 * 
	 */
	function __construct($host) {
		$this->host = $host;
		$this->pclient = new PestJSON($host);
	}

	function __destruct() {
	}

	/**
	 * function setHost - sets the host of a jira REST session
	 * 
	 * @param   $host - string, the URL of the jira REST server
	 * @return  
	 * 
	 */
	public function setHost($host) {
		$this->host = $host;

		try {
			$this->pclient = new PestJSON($host);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}
	}

	/**
	 * function login - logs into a jira REST session
	 * 
	 * @param   $username - string, jira username
	 * @param   $password - string, jira password
	 * @return  $this->auth - string, jira authorization string
	 * 
	 */
	public function login($username, $password) {

		$this->username = $username;
		$this->password = $password;

		try {
			$this->auth = $this->pclient->post('/rest/auth/1/session', array (
				'username' => $username,
				'password' => $password
			));
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $this->auth;
	}

	/**
	 * function logout - destroys the jira REST session
	 * 
	 * @param  
	 * @return  
	 * 
	 */
	public function logout() {
		try {
			$results = $this->pclient->delete('/rest/auth/1/session');
			$this->pclient->__destruct();
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}
	}

	/**
	 * function issueAssign - assign a jira issue to a user
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $rUser - string, the jira user
	 * @return  $results - array, complete jira issues with all fields
	 * 
	 */
	public function issueAssign($rIssueKey, $rUser) {

		$userArray = array ();
		$userArray['name'] = $rUser;

		try {
			$results = $this->pclient->put('/rest/api/2/issue/' . $rIssueKey . '/assignee', $userArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueCreate - creates a jira issue
	 * 
	 * @param   $issueObject - object (project, issuetype, and summary are required)
	 * @return  $results - array, a jira issue stub
	 * 
	 */
	public function issueCreate($issueObject) {

		// create issue array
		$issueArray = array (
			'fields' => array (
				'project' => array (
					'key' => $issueObject->project
				),
				'issuetype' => array (
					'id' => $issueObject->type
				),

				
			)
		);

		// for code that uses deprecated notation
		if (property_exists($issueObject, 'description')) {
			$issueArray['fields']['description'] = $issueObject->description;
		}
		if (property_exists($issueObject, 'summary')) {
			$issueArray['fields']['summary'] = $issueObject->summary;
		}

		// add custom fields
		foreach ($issueObject->fields as $key => $value) {
			$issueArray['fields'][$key] = $value;
		}

		// create issue
		try {
			$results = $this->pclient->post('/rest/api/2/issue', $issueArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueGet - get a jira issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $results - array, complete jira issues with all fields
	 * 
	 */
	public function issueGet($rIssueKey) {

		try {
			$results = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueDelete - delete a jira issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $subtasks - boolean, true or false to delete subtasks
	 * @return  $results - deleted
	 * 
	 */
	public function issueDelete($rIssueKey, $subtasks = false) {

		try {
			$results = $this->pclient->delete('/rest/api/2/issue/' . $rIssueKey . '?deleteSubtasks=' . $subtasks);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueKeyChanged - see if issue key changed
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $results - boolean, new key if changed, 0 if not
	 * 
	 */
	public function issueKeyChanged($rIssueKey) {

		try {
			$rIssue = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey. '?fields=key');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		if ($rIssue['key'] != $rIssueKey) {
			return $rIssue['key'];
		} else {
			return 0;
		}
	}

	/**
	 * function issueMetaGet - return issue search results
	 * 
	 * @param   $queryURL - string, the jira query URL
	 * @return  $results - array, results of the query
	 * 
	 */
	public function issueMetaGet($queryURL) {
		try {
			$results = $this->pclient->get('/rest/api/2/issue/createmeta' . $queryURL);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueSearch - get a list of issues via jql
	 * 
	 * @param   
	 * @return  $results - array, stubs of issues
	 * 
	 * https://confluence.atlassian.com/display/JIRA/Advanced+Searching
	 * 
	 */
	public function issueSearch($queryObject) {

		$queryArray = array ();
		foreach ($queryObject as $key => $value) {
			$queryArray[$key] = $value;
		}

		try {
			$results = $this->pclient->post('/rest/api/2/search', $queryArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueUpdate - update a jira issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $issueObject - object, fields set to new values
	 * @return  $results - array, a jira issue stub
	 * 
	 */
	public function issueUpdate($rIssueKey, $issueObject) {

		// create issue array
		$issueArray = array ();
		foreach ($issueObject->fields as $key => $value) {
			$issueArray['fields'][$key] = $value;
		}

		try {
			$results = $this->pclient->put('/rest/api/2/issue/' . $rIssueKey, $issueArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function issueUserAccess - return issue search results
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $username - string, jira username
	 * @return  $results - array, user stubs of users that can browse the issue
	 * 
	 * admin perms required to see who has browse access to issues
	 * 
	 */
	public function issueUserAccess($rIssueKey, $username) {

		$queryURL = '?username=' . $username;
		$queryURL .= '&issueKey=' . $rIssueKey;

		try {
			$results = $this->pclient->get('/rest/api/2/user/viewissue/search' . $queryURL);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function commentCreate - add a comment to an issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $results - array, the full comment
	 * 
	 */
	public function commentCreate($rIssueKey, $comment) {

		$commentArray = array ();
		$commentArray['body'] = $comment;

		try {
			$results = $this->pclient->post('/rest/api/2/issue/' . $rIssueKey . '/comment', $commentArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function commentDelete - delete an issue's comment
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $rCommentId - string, the comment id
	 * @return  $results - array, full comments for the given issue
	 * 
	 */
	public function commentDelete($rIssueKey, $rCommentId) {

		try {
			$results = $this->pclient->delete('/rest/api/2/issue/' . $rIssueKey . '/comment/' . $rCommentId);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function commentGet - return an issue's comments
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $results - array, full comments for the given issue
	 * 
	 */
	public function commentGet($rIssueKey) {

		try {
			$results = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey . '/comment');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function commentGetId - return an issue's comment by Id
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $rCommentId - string, the comment id
	 * @return  $results - array, full comments for the given issue
	 * 
	 */
	public function commentGetId($rIssueKey, $rCommentId) {

		try {
			$results = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey . '/comment/' . $rCommentId);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function transitionGet - get possible transitions for an issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $results - array, transition ids
	 * 
	 */
	public function transitionGet($rIssueKey) {

		try {
			$rTransitions = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey . '/transitions');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		$transitions = $rTransitions['transitions'];

		return $transitions;
	}

	/**
	 * function componentGet - get possible components for a project
	 * 
	 * @param   $rProjectKey - string, the jira project key
	 * @return  $results - array, component ids and names
	 * 
	 */
	public function componentGet($rProjectKey) {

		try {
			$rComponents = $this->pclient->get('/rest/api/2/project/' . $rProjectKey . '/components');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		$components = array();
		foreach ($rComponents as $component) {
			$id = $component['id'];
			$name = $component['name'];
			$components[$id] = $name;
		}

		return $components;
	}

	/**
	 * function transitionDo - transition an issue through the workflow
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $transitionObject - object, the jira issue with transition and field changes
	 * @return  $results - array, full transitions
	 * 
	 */
	public function transitionDo($rIssueKey, $transitionID) {

		// create issue array
		$transitionArray = array ();
		$transitionArray['transition'] = array (
			'id' => $transitionID
		);

		try {
			$results = $this->pclient->post('/rest/api/2/issue/' . $rIssueKey . '/transitions', $transitionArray);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function attachmentCreate - add an attachment to an issue
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @param   $filenames - array, names of attachment files
	 * @param   $filedata - array, base64 encoded file attachment data
	 * @return  $results - array, stub attachment
	 * 
	 */
	public function attachmentCreate($rIssueKey, $filename) {

		// curl via command line
		$host = $this->host . '/rest/api/2/issue/' . $rIssueKey . '/attachments';
		$command = "curl --silent --insecure -u $this->username:$this->password -X POST -H 'X-Atlassian-Token: nocheck' -F 'file=@$filename' $host";
		$output = shell_exec($command);
		$results = json_decode($output);

		if (!$results) {
			$exception = new ServiceDeskException('Create of attachment failed.');
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function attachmentGet - get an issue's attachments
	 * 
	 * @param   $rIssueKey - string, the jira issue key
	 * @return  $attachments - array, attachments stubs
	 * 
	 */
	public function attachmentGet($rIssueKey) {

		$attachments = array ();

		// get attachment ids
		try {
			$results = $this->pclient->get('/rest/api/2/issue/' . $rIssueKey . '?fields=attachment');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		// get individual attachments
		$stubs = $results['fields']['attachment'];
		//		foreach ($stubs as $attachment) {
		//
		//			$attachmentId = $attachment['id'];
		//			try {
		//				$results = $this->pclient->get('/rest/api/2/attachment/' . $attachmentId);
		//			} catch (Exception $e) {
		//				$exception = new ServiceDeskException($e);
		//				throw ($exception);
		//			}
		//			$attachments[] = $results;
		//		}

		return $stubs;
	}

	/**
	 * function attachmentGetContent - get attachment content
	 * 
	 * @param   $url - string, the url of the attachment
	 * @return  $content - array, the content of the attachment, 0 - failure
	 * 
	 */
	public function attachmentGetContent($url) {

		// curl via command line
		$command = "curl --silent --fail --insecure -u $this->username:$this->password -H 'X-Atlassian-Token: nocheck' $url";
		$content = shell_exec($command);

		if (!$content) {
			$exception = new ServiceDeskException('Get attachment content failed.');
			throw ($exception);
		}

		return $content;
	}

	/**
	 * function projectGet - get a list of all projects
	 * 
	 * @param   
	 * @return  $results - array, stubs of projects
	 * 
	 */
	public function projectGet() {

		try {
			$results = $this->pclient->get('/rest/api/2/project');
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	/**
	 * function userGet - get Jira user
	 * 
	 * @param   $username - string, the username you want to fetch
	 * @return  
	 * 
	 */

	public function userGet($username) {
		
		try {
			$results = $this->pclient->get('/rest/api/2/user?username=' . $username);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;		
	}

	/**
	 * function userPermission - get Jira user permissions
	 * 
	 * @param   $username - string, the username you want to check for permissions
	 * @param   $permission - string, the permission you are checking for
	 * @param   $project - string, the project you want to check for user permissions
	 * @return  
	 * 
	 */

	public function userPermission($username, $project, $permission = 'CREATE_ISSUE') {
		
		try {
			$results = $this->pclient->get('/rest/api/2/user/permission/search?username=' . $username . '&permissions=' . $permission . '&projectKey=' . $project);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;		
	}

	/**
	 * function userSwitch - switch loggen in Jira user
	 * 
	 * @param   $username - string, the username you want to switch to
	 * @return  
	 * 
	 */

	public function userSwitch($username) {
		
		// reset user password
		$password = $this->generatePassword();
		
		try {
			$results = $this->pclient->put('/rest/api/2/user/password?username=' . $username, $password);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		// logout of REST
		$this->logout();

		// login to REST with new user
		$this->login($username, $password);

		return $this;		
	}

	/**
	 * function worklogUpdate - update an issue's worklog
	 * 
	 * @param   $key - issue key to update
	 * @param   $worklog - a worklog array
	 * @return  $results - array, stubs of projects
	 * 
	 */

	public function worklogUpdate($key, $worklog) {

		try {
			$results = $this->pclient->post('/rest/api/2/issue/' . $key . '/worklog?adjustEstimate=auto', $worklog);
		} catch (Exception $e) {
			$exception = new ServiceDeskException($e);
			throw ($exception);
		}

		return $results;
	}

	private function generatePassword($len = 16) {

		if (@ is_readable('/dev/urandom')) {
			$f = fopen('/dev/urandom', 'r');
			$urandom = fread($f, $len);
			fclose($f);
		}

		$return = '';
		for ($i = 0; $i < $len; ++ $i) {
			if (!isset ($urandom)) {
				if ($i % 2 == 0)
					mt_srand(time() % 2147 * 1000000 + (double) microtime() * 1000000);
				$rand = 48 + mt_rand() % 64;
			} else
				$rand = 48 + ord($urandom[$i]) % 64;

			if ($rand > 57)
				$rand += 7;
			if ($rand > 90)
				$rand += 6;

			if ($rand == 123)
				$rand = 45;
			if ($rand == 124)
				$rand = 46;
			$return .= chr($rand);
		}

		return $return;
	}
}
?>
