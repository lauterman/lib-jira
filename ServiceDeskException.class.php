<?php


/**
 * ServiceDeskException Class
 *
 * @package lib-jira
 * @file ServiceDeskException.class.php
 * @author  JKCampbell <jkc103@psu.edu>
 */

/**
 * Define a custom exception class
 */
class ServiceDeskException extends Exception {

	// class Pest_Exception extends Exception { }
	// class Pest_UnknownResponse extends Pest_Exception { }
	// 401-499  class Pest_ClientError extends Pest_Exception {}
	// 400  class Pest_BadRequest extends Pest_ClientError {}
	// 401  class Pest_Unauthorized extends Pest_ClientError {}
	// 403  class Pest_Forbidden extends Pest_ClientError {}
	// 404  class Pest_NotFound extends Pest_ClientError {}
	// 405  class Pest_MethodNotAllowed extends Pest_ClientError {}
	// 409  class Pest_Conflict extends Pest_ClientError {}
	// 410  class Pest_Gone extends Pest_ClientError {}
	// 422  class Pest_InvalidRecord extends Pest_ClientError {}

	// 500-599 class Pest_ServerError extends Pest_Exception {}

	/*
	 * constructor
	 */
	public function __construct($exception) {

		// build from a message
		if (is_string($exception)) {
			parent :: __construct($exception);
		}

		// build from an Exception object
		if (is_object($exception)) {
			$this->sd_type = get_class($exception);
            $this->sd_message = $exception->getMessage();
			$this->sd_code = $exception->getCode();
			$this->sd_file = $exception->getFile();
			$this->sd_line = $exception->getLine();
			$this->sd_trace = $exception->getTrace();
			$this->sd_previous = $exception->getPrevious();
			//$this->sd_string = $exception->getTraceAsString();
		}
	}
}