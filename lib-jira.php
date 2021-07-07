<?php


/**
 * JIRA Custom Code library
 *
 * @package lib-jira
 * @file lib-jira.php
 * @created 11.27.2012
 * @author JKCampbell <jimcampbell@psu.edu>
 * 
 * 
 * 
 * 
 * DEBUG_LEVEL - integer [0-6], the higher the value the more information dumped
 * DEBUG_FILE - 
 * DEBUG_TO_FILE - boolean, output to a file
 * DEBUG_TO_WEB - boolean, 
 * DEBUG_SKIP_REDIRECT - boolean, 
 * 
 * JIRA_FROM_EMAIL
 * JIRA_FROM_NAME
 * JIRA_CONTACT
 * 
 * JIRA_FILE_DIR
 * SQL_LITE_FILE_NAME
 * 
 */

include ('pest/PestJSON.php');
include ('mail/MimeMailParser.class.php');
include ('mail/rfc3696_email_validator.php');
include ('PHPMailer/class.phpmailer.php');
include ('ServiceDeskMailParser.class.php');
include ('ServiceDeskRest.class.php');
include ('ServiceDeskException.class.php');
include ('ServiceDeskDatabase.class.php');

define("JIRA_FILE_DIR", "/tmp");
define("JIRA_ATTACHMENT_PREFIX", "attachment-");
define("JIRA_MAILER_HOST", "localhost");

/**
 * 
 * function dprint - debug messages based on debug level
 * 
 * @param -  debug level, output statement
 * @return - message to screen/file based on debug level
 * 
 */
function dprint($level, $message) {

	global $fh;

	if (!DEBUG_LEVEL && !DEBUG_TO_FILE) {
		return;
	}

	$spaces = "";
	for ($i = 0; $i < $level; $i++) {
		$spaces .= " ";
	}

	$temp = '';
	$temp = "*** " . $spaces . $message;
	if (DEBUG_LEVEL >= $level) {
		if (DEBUG_TO_WEB) {
			$output = $temp . "<br />";
			print date(DATE_ATOM) . " " . $output;
		}
		if (DEBUG_TO_TERMINAL) {
			$output = $temp . "\n";
			print date(DATE_ATOM) . " " . $output;
		}
		if (DEBUG_TO_FILE) {
			if (!$fh) {
				$fh = fopen(DEBUG_FILE, 'w') or die("can't open file");
			}
			$output = $temp . "\n";
			fwrite($fh, date(DATE_ATOM) . " " . $output);
		}
	}
	return;
}

/**
 * 
 * function get_unixtime
 * 
 * @param 'YEAR-MONTH-DAY 24H:MINUTE:SECONDS'
 * @return unixtime in seconds
 * 
 */
function get_unixtime($text_date) {

	dprint(3, " ");
	dprint(3, "get_unixtime called");
	dprint(3, "text date: $text_date");

	// split into date :: time
	$parts = preg_split("/\s/", $text_date);

	// process date
	$date = $parts[0];
	$date_parts = preg_split("/-/", $date);
	$year = $date_parts[0];
	$month = $date_parts[1];
	$day = $date_parts[2];

	// process time
	$time = $parts[1];
	$time_parts = preg_split("/:/", $time);
	$hour = $time_parts[0];
	$minute = $time_parts[1];
	$second = $time_parts[2];

	// mktime(hour,minute,second,month,day,year,is_dst)
	$unixtime = mktime($hour, $minute, $second, $month, $day, $year);

	dprint(3, "get_unixtime finished");
	dprint(3, "get_unixtime finished, returning: $unixtime");
	dprint(3, " ");

	return $unixtime;
}

/**
 * 
 * function error_handler
 * 
 * @param  ServiceDeskException object
 * @return none
 * 
 */
function error_handler($error) {

	dprint(3, " ");
	dprint(3, "error_handler called");
	$debug = print_r($error, true);
	dprint(3, "error: $debug");

	// create subject
	$trace = $error->sd_trace;
	$last_trace = count($trace) - 1;

	$class = $trace[$last_trace]['class'];
	$function = $trace[$last_trace]['function'];
	$file = $trace[$last_trace]['file'];
	$filename = end(array_filter(explode('/', $file)));
	$subject = $class . "->" . $function . " Exception (" . $filename . ")";

	// construct email
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = 'localhost';
	$mail->IsHTML(false);

	$mail->From = JIRA_FROM_EMAIL;
	$mail->FromName = JIRA_FROM_NAME;
	$mail->AddAddress(JIRA_CONTACT);
	$mail->Subject = $subject;
	
	// convert object to array and strip sensitive data
	$error = get_object_vars($error);
	foreach($error['sd_trace'] as $key => $trace) {
		
		// strip auth credentials from rest call
		$args = $error['sd_trace'][$key]['args'];
		if(!empty($args)) {
			if($args[0] == '/rest/auth/1/session') {
				unset($error['sd_trace'][$key]['args']);
			}
		}
		
		// strip auth credentials from function call
		$function = $error['sd_trace'][$key]['function'];
		if($function == 'login') {
			unset($error['sd_trace'][$key]['args']);
		}
	}
	
	$debug = print_r($error, true);
	dprint(3, "stripped error: $debug");
		
	$mail->Body = "$debug";	

	// send email
	if (!$mail->Send()) {
		dprint(0, "ERROR!!! Could not send error email notification!  Error: " . $mail->ErrorInfo);
	}

	dprint(3, "error_handler finished, email sent");
	dprint(3, " ");

	return;
}

/**
 * 
 * function error_notify
 * 
 * @param  string error message
 * @return none
 * 
 */
function error_notify($error) {

	dprint(3, " ");
	dprint(3, "error_notify called");
	dprint(3, "error: $error");

	// construct email
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = 'localhost';
	$mail->IsHTML(false);

	$mail->From = JIRA_FROM_EMAIL;
	$mail->FromName = JIRA_FROM_NAME;
	$mail->AddAddress(JIRA_CONTACT);
	$mail->Subject = $error;
	$mail->Body = "$error";	

	// send email
	if (!$mail->Send()) {
		dprint(0, "ERROR!!! Could not send error email notification!  Error: " . $mail->ErrorInfo);
	}

	dprint(3, "error_notify finished, email sent");
	dprint(3, " ");

	return;
}

/**
 * 
 * function error_save_to_db
 * 
 * @param -  object, ->attachments must be array with keys:
 * 				['filename'] - the name to the file
 * 				['content'] = the base64_encoded content of the file
 * @return - 0 = fail, 1 - success
 * 
 */

function error_save_to_db($issue) {

	dprint(3, " ");
	dprint(3, "error_save_to_db called");
	$debug = print_r($issue, true);
	dprint(5, "issue: $debug");

	// create new sqlite db
	$db = new ServiceDeskDatabase(JIRA_FILE_DIR . '/' . SQL_LITE_FILE_NAME);
	try {
		$issue_id = $db->issue_save($issue);
	} catch (ServiceDeskException $e) {
		throw ($e);
	}

	dprint(5, "issue_id: $issue_id");
	dprint(3, "error_save_to_db finished");
	dprint(3, " ");

	return $issue_id;
}

/**
 * 
 * function redirect
 * 
 * @param
 * @return
 * 
 */

function redirect($seconds, $message, $url) {

	dprint(3, "");
	dprint(3, "redirect called");
	dprint(3, "redirecting to url: $url");

	echo "$message";
	if (DEBUG_SKIP_REDIRECT) {
		return;
	}
	echo "<META http-equiv=refresh content=\"$seconds; url='{$url}'\">";

	dprint(3, "redirect finished");
	dprint(3, "");

	return;
}

/**
 * 
 * function string_empty
 * 
 * @param
 * @return
 * 
 */
function string_empty($string) {

	dprint(3, " ");
	dprint(3, "string_empty called");

	$empty = FALSE;
	if (empty ($string) || ctype_space($string) || ($string == null) || (trim($string) == '')) {
		$empty = TRUE;
	}

	dprint(3, "string_empty finished, returning: $empty");
	dprint(3, " ");

	return $empty;
}

/**
 * 
 * function string_sanitize
 * 
 * @param
 * @return
 * 
 */
function string_sanitize($string) {

	dprint(3, " ");
	dprint(3, "string_sanitize called");
	dprint(3, "string: $string");

	// strip control characters except \r, \n, \t - the rest are not valid xml
	// http://www.ascii-code.com
	$string = preg_replace('/[\x00-\x08\x0B-\x1F\x7F\x80-\xFF]/', '', $string);

	// convert unicode to latin
	$string = iconv(mb_detect_encoding($string), 'ISO-8859-15//IGNORE//TRANSLIT', $string);

	// utf encode 
	$string = utf8_encode($string);

	dprint(5, "string_sanitize returning: $string");
	dprint(3, "string_sanitize finished");
	dprint(3, " ");

	return $string;
}

/**
 * 
 * function string_wiki_escape - remove 
 * 
 * @param
 * @return
 * 
 */
function string_wiki_escape($string) {

	dprint(3, " ");
	dprint(3, "string_wiki_escape called");
	dprint(3, "string: $string");

	$wiki_tokens = array (
		"{",
		"}",
		"[",
		"]",
		"!",
//		"*",
//		"-",
//		"_",
//		"^",
//		"+",
//		"#",
//		"|",
//		"?"
	);
	foreach ($wiki_tokens as $escape) {
		$string = str_replace($escape, "\\" . $escape, $string);
	}

	dprint(5, "string_wiki_escape returning: $string");
	dprint(3, "string_wiki_escape finished");
	dprint(3, " ");

	return $string;
}

/**
 * 
 * function string_wiki_unescape - remove 
 * 
 * @param
 * @return
 * 
 */
function string_wiki_unescape($string) {

	dprint(3, " ");
	dprint(3, "string_wiki_unescape called");
	dprint(3, "string: $string");

	$wiki_tokens = array (
		"\\{",
		"\\}",
		"\\[",
		"\\]",
		"\\!",
		"\\*",
		"\\-",
		"\\_",
		"\\^",
		"\\+",
		"\\#",
		"\\|",
		"\\?"
	);
	
	foreach ($wiki_tokens as $unescape) {
		$string = str_replace($unescape, $unescape[1], $string);
	}

	dprint(5, "string_wiki_unescape returning: $string");
	dprint(3, "string_wiki_unescape finished");
	dprint(3, " ");
	
	return $string;
}
?>
