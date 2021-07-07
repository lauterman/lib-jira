<?php


/**
 * JIRA REST library
 *
 * @package lib-jira
 * @file ServiceDeskDatabase.class.php
 * @created 12.13.2012
 * @author JKCampbell <jimcampbell@psu.edu>
 */

/**
* DATABASE TABLES
* -----------------------------------------------------------
* 
* SD_ISSUE
* ------------------------------
* | issue_id        | INT (PK) |
* | serialized_data | STRING   |
* | created_date    | DATE     |
* ------------------------------
* 
* SD_ATTACHMENT
* ---------------------------------
* | attachment_id       | INT (PK)|
* | issue_id            | INT     |
* | attachment_filename | STRING  |
* | attachment_content  | BLOB    |
* ---------------------------------
* 
*/

class ServiceDeskDatabase extends SQLite3 {

	private $db_filename;
	private $attachment_tables_created = false;
	private $email_tables_created = false;

	/**
	 * build! destroy!  
	 * 
	 */

	function __construct($db_filename) {
		$this->open($db_filename);
		$this->exec('CREATE TABLE IF NOT EXISTS sd_issue (issue_id INTEGER PRIMARY KEY, issue_serialized BLOB, created_date DATE)');
		$this->exec('CREATE TABLE IF NOT EXISTS sd_attachment (attachment_id INTEGER PRIMARY KEY, issue_id INTEGER, attachment_path STRING, attachment_filename STRING, attachment_type STRING, attachment_content BLOB)');
		$this->db_filename = $db_filename;
	}

	function __destruct() {
		// this caused insert errors
		//$this->close();
	}

	/**
	 * db_error - throws an ServiceDeskException built with db error
	 * 
	 * @param   none
	 * @return  exception, with db error code and message
	 *  
	 */

	private function db_error() {

		// get db errors
		$lastErrorCode = $this->lastErrorCode();
		$lastErrorMsg = $this->lastErrorMsg();

		// build ServiceDeskException
		$e = new ServiceDeskException($lastErrorMsg);
		$e->sd_code = $lastErrorCode;
		$e->sd_message = $lastErrorMsg;

		throw ($e);
	}

	/**
	 * db_exec - xecute a result-less query against a given database
	 * 
	 * @param   string, an INSERT, UPDATE, or DELETE query
	 * @return  true - success
	 * @throws  ServiceDeskException on error
	 *  
	 */

	public function db_exec($query) {
		if (!$this->exec($query)) {
			$this->db_error();
		}

		return 1;
	}

	/**
	 * function issue_delete - delete issue from database
	 * 
	 * @param   int, issue_id
	 * @return  1 - success
	 * @throws  ServiceDeskException on error
	 * 
	 */
	 
	public function issue_delete($issue_id) {

		// delete issue        
		$stmt = $this->prepare("DELETE FROM sd_issue WHERE issue_id=:issue_id");
		$stmt->bindValue(':issue_id', $issue_id, SQLITE3_INTEGER);
		if (!$results = $stmt->execute()) {
			$this->db_error();
		}

		// delete attachments        
		$stmt = $this->prepare("DELETE FROM sd_attachment WHERE issue_id=:issue_id");
		$stmt->bindValue(':issue_id', $issue_id, SQLITE3_INTEGER);
		if (!$results = $stmt->execute()) {
			$this->db_error();
		}

		return 1;
	}

	/**
	 * function issue_get - get full issue
	 * 
	 * @param   int, issue_id
	 * @return  object, full object of issue
	 * 
	 */
	 
	public function issue_get($issue_id) {

		// get issue        
		$stmt = $this->prepare("SELECT * FROM sd_issue WHERE issue_id=:issue_id");
		$stmt->bindValue(':issue_id', $issue_id, SQLITE3_INTEGER);
		if (!$results = $stmt->execute()) {
			$this->db_error();
		}

		// build issue object
		$row_count = 0;
		$issue = new stdClass();
		while ($row = $results->fetchArray()) {
			$issue_id = $row['issue_id'];
			$issue_serialized = $row['issue_serialized'];
			$issue_unserialized = unserialize($issue_serialized);

			$issue = $issue_unserialized;
			$issue->issue_id = $issue_id;
			$row_count++;
		}

		// no issue
		if (!$row_count) {
			return $issue;
		}

		// get issue attachments
		$issue->attachments = array ();

		$stmt = $this->prepare("SELECT * FROM sd_attachment WHERE issue_id=:issue_id");
		$stmt->bindValue(':issue_id', $issue_id, SQLITE3_INTEGER);
		$results = $stmt->execute();
		while ($row = $results->fetchArray()) {

			// build attachments
			$attachment = array ();
			foreach ($row as $key => $value) {
				if (!is_int($key)) {
					$attachment[$key] = $value;
				}
			}
			$issue->attachments[] = $attachment;
		}

		return $issue;
	}

	/**
	 * function issue_get_ids - get issue_ids of all issues
	 * 
	 * @param   none
	 * @return  array, an array of issue_ids
	 * 
	 */
	 
	public function issue_get_ids() {

		$ret = array ();

		// get issue ids
		$query = "SELECT issue_id from sd_issue";
		$results = $this->query($query);
		while ($row = $results->fetchArray()) {
			$ret[] = $row['issue_id'];
		}

		return $ret;
	}

	/**
	 * function issue_save - save an email to the database
	 * 
	 * @param   $email - object, the email headers, text, and html body
	 * @return  beer
	 * 
	 */
	 
	public function issue_save($email) {

		// remove attachments from email object
		$attachments = array ();
		if (property_exists($email, 'attachments')) {
			$attachments = $email->attachments;
		}
		$email->attachments = array ();

		// insert issue
		$time = time();
		$issue_serialized = serialize($email);
		$issue_escaped = $this->escapeString($issue_serialized);
		$query = "INSERT INTO sd_issue (issue_serialized, created_date) VALUES ('" . $issue_escaped . "', $time)";
		if (!$result = $this->exec($query)) {
			$this->db_error();
		}

		// get issue id
		$issue_id = $this->lastInsertRowID();

		// save attachments to a different table
		if ($attachments) {
			$this->attachment_save($issue_id, $attachments);
		}

		return $issue_id;
	}

	/**
	 * function attachment_delete - delete attachments from database
	 * 
	 * @param   int, issue_id
	 * @param   int, attachment_id
	 * @return  1 - success
	 * @throws  ServiceDeskException on error
	 * 
	 */
	 
	public function attachment_delete($issue_id, $attachment_id) {

		// delete issue        
		$stmt = $this->prepare("DELETE FROM sd_attachment WHERE issue_id=:issue_id AND attachment_id=:attachment_id");
		$stmt->bindValue(':issue_id', $issue_id, SQLITE3_INTEGER);
		$stmt->bindValue(':attachment_id', $attachment_id, SQLITE3_INTEGER);
		if (!$results = $stmt->execute()) {
			$this->db_error();
		}

		return 1;
	}
	
	/**
	 * function attachment_save - save an attachment to the database
	 * 
	 * @param   int, issue_id in database
	 * @param   array, attachment filenames and base64_encoded content
	 * @return  
	 * 
	 */

	private function attachment_save($issue_id, $attachments) {

		$attachment_ids = array ();

		// loop over each attachment
		foreach ($attachments as $attachment) {

			$attachment_path = $attachment['attachment_path'];
			$attachment_filename = $attachment['attachment_filename'];
			$attachment_type = $attachment['attachment_type'];
			$attachment_content = $attachment['attachment_content'];

			// insert attachment
			$attachment_path_escaped = $this->escapeString($attachment_path);
			$attachment_filename_escaped = $this->escapeString($attachment_filename);
			$attachment_type_escaped = $this->escapeString($attachment_type);
			$attachment_content_encoded = base64_encode($attachment_content);
			$query = "INSERT INTO sd_attachment (issue_id, attachment_path, attachment_filename, attachment_type, attachment_content) VALUES ($issue_id, '$attachment_path_escaped', '$attachment_filename_escaped', '$attachment_type_escaped', '$attachment_content_encoded')";
			if (!$result = $this->exec($query)) {
				$this->db_error();
			}

			// get attachment id
			$attachment_id = $this->lastInsertRowID();
			$attachment_ids[] = $attachment_id;
		}

		return $attachment_ids;
	}

}
?>
