<?php // -*- c-basic-offset: 2 -*-

/**
 * Pest is a REST client for PHP.
 *
 * See http://github.com/educoder/pest for details.
 *
 * This code is licensed for use, modification, and distribution
 * under the terms of the MIT License (see http://en.wikipedia.org/wiki/MIT_License)
 */
class Pest {
  public $curl_opts = array(
  	CURLOPT_RETURNTRANSFER => true,  // return result instead of echoing
  	CURLOPT_SSL_VERIFYPEER => false, // stop cURL from verifying the peer's certificate
  	CURLOPT_FOLLOWLOCATION => true,  // follow redirects, Location: headers
  	CURLOPT_MAXREDIRS      => 10,    // but dont redirect more than 10 times
    CURLINFO_HEADER_OUT    => true,
    CURLOPT_COOKIEFILE => '/tmp/pestcookies.txt',
    CURLOPT_COOKIEJAR => '/tmp/pestcookies.txt',
    CURLOPT_COOKIESESSION => true,
  );

  public $base_url;
  
  public $last_response;
  public $last_request;

  private $curl;
  
  public function __construct($base_url) {
    if (!function_exists('curl_init')) {
  	    throw new Exception('CURL module not available! Pest requires CURL. See http://php.net/manual/en/book.curl.php');
  	}
    
    $this->base_url = $base_url;
    $this->curl = curl_init($base_url);
  }

  public function __destruct() {

  }
  
  public function get($url) {
    $headers = array();
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'GET';
    $this->curl_opts[CURLOPT_HTTPHEADER] = array();
    $this->curl_opts[CURLOPT_POSTFIELDS] = NULL;

    $this->prepRequest($url);
    $body = $this->doRequest();
    $body = $this->processBody($body);
    
    return $body;
  }
  
  public function post($url, $data, $headers=array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data;
        
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'POST';
    $headers[] = 'Content-Length: '.strlen($data);
    $this->curl_opts[CURLOPT_HTTPHEADER] = $headers;
    $this->curl_opts[CURLOPT_POSTFIELDS] = $data;
    
    $this->prepRequest($url);
    $body = $this->doRequest();
    $body = $this->processBody($body);
    
    return $body;
  }
  
  public function put($url, $data, $headers=array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data; 
    
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
    $headers[] = 'Content-Length: '.strlen($data);
    $this->curl_opts[CURLOPT_HTTPHEADER] = $headers;
    $this->curl_opts[CURLOPT_POSTFIELDS] = $data;
    
    $this->prepRequest($url);
    $body = $this->doRequest();
    $body = $this->processBody($body);
    
    return $body;
  }
  
  public function delete($url) {
    $this->curl_opts[CURLOPT_HTTPHEADER] = array();
    $this->culr_opts[CURLOPT_POSTFIELDS] = NULL;
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    
    $curl = $this->prepRequest($url);
    $body = $this->doRequest($curl);
    
    $body = $this->processBody($body);
    
    return $body;
  }
  
  public function lastBody() {
    return $this->last_response['body'];
  }
  
  public function lastStatus() {
    return $this->last_response['meta']['http_code'];
  }
  
  protected function processBody($body) {
    // Override this in classes that extend Pest.
    // The body of every GET/POST/PUT/DELETE response goes through 
    // here prior to being returned.
    return $body;
  }
  
  protected function processError($body) {
    // Override this in classes that extend Pest.
    // The body of every erroneous (non-2xx/3xx) GET/POST/PUT/DELETE  
    // response goes through here prior to being used as the 'message'
    // of the resulting Pest_Exception
    return $body;
  }

  
  protected function prepRequest($url) {
    if (strncmp($url, $this->base_url, strlen($this->base_url)) != 0) {
      $url = $this->base_url . $url;
    }
    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt_array($this->curl, $this->curl_opts); 
      
    $this->last_request = array(
      'url' => $url
    );
    
    if (isset($this->curl_opts[CURLOPT_CUSTOMREQUEST]))
      $this->last_request['method'] = $this->curl_opts[CURLOPT_CUSTOMREQUEST];
    else
      $this->last_request['method'] = 'GET';
    
    if (isset($this->curl_opts[CURLOPT_POSTFIELDS]))
      $this->last_request['data'] = $this->curl_opts[CURLOPT_POSTFIELDS];
  }
  
  private function doRequest() {
    $body = curl_exec($this->curl);
    $meta = curl_getinfo($this->curl);

    $this->curl_opts[CURLOPT_COOKIESESSION] = false; // Store session variables after the first request.

    $this->last_response = array(
      'body' => $body,
      'meta' => $meta
    );
   
    $this->checkLastResponseForError();
    
    return $body;
  }
  
  private function checkLastResponseForError() {
    $meta = $this->last_response['meta'];
    $body = $this->last_response['body'];
    
    if (!$meta)
      return;
    
    $err = null;
    switch ($meta['http_code']) {
      case 400:
        throw new Pest_BadRequest($this->processError($body));
        break;
      case 401:
        throw new Pest_Unauthorized($this->processError($body));
        break;
      case 403:
        throw new Pest_Forbidden($this->processError($body));
        break;
      case 404:
        throw new Pest_NotFound($this->processError($body));
        break;
      case 405:
        throw new Pest_MethodNotAllowed($this->processError($body));
        break;
      case 409:
        throw new Pest_Conflict($this->processError($body));
        break;
      case 410:
        throw new Pest_Gone($this->processError($body));
        break;
      case 422:
        // Unprocessable Entity -- see http://www.iana.org/assignments/http-status-codes
        // This is now commonly used (in Rails, at least) to indicate
        // a response to a request that is syntactically correct,
        // but semantically invalid (for example, when trying to 
        // create a resource with some required fields missing)
        throw new Pest_InvalidRecord($this->processError($body));
        break;
      default:
        if ($meta['http_code'] >= 400 && $meta['http_code'] <= 499)
          throw new Pest_ClientError($this->processError($body));
        elseif ($meta['http_code'] >= 500 && $meta['http_code'] <= 599)
          throw new Pest_ServerError($this->processError($body));
        elseif (!$meta['http_code'] || $meta['http_code'] >= 600) {
          throw new Pest_UnknownResponse($this->processError($body));
        }
    }
  }
}


class Pest_Exception extends Exception { }
class Pest_UnknownResponse extends Pest_Exception { }

/* 401-499 */ class Pest_ClientError extends Pest_Exception {}
/* 400 */ class Pest_BadRequest extends Pest_ClientError {}
/* 401 */ class Pest_Unauthorized extends Pest_ClientError {}
/* 403 */ class Pest_Forbidden extends Pest_ClientError {}
/* 404 */ class Pest_NotFound extends Pest_ClientError {}
/* 405 */ class Pest_MethodNotAllowed extends Pest_ClientError {}
/* 409 */ class Pest_Conflict extends Pest_ClientError {}
/* 410 */ class Pest_Gone extends Pest_ClientError {}
/* 422 */ class Pest_InvalidRecord extends Pest_ClientError {}

/* 500-599 */ class Pest_ServerError extends Pest_Exception {}

?>
