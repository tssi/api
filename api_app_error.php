<?php
class ApiAppError extends ErrorHandler {
	var $CODES = array(
			202=>'No results found for %s',
			401=>'Invalid Login',
			403=>'Invalid Endpoint',
			402=>'Data not set',
			404=>'Empty Record',
	);
	function invalidLogin($params){
		$code = 401;
		$message = $this->CODES[$code];
		$this->fetchError($code,$message);
	}
	function invalidEndpoint($params){
		$code = 403;
		$message = $this->CODES[$code];
		$this->fetchError($code,$message);
	}
	function dataNotSet($params){
		$code = 402;
		$message = $this->CODES[$code];
		$this->fetchError($code,$message);
	}
	function emptyRecord($params){
		$code = 404;
		$message = $this->CODES[$code];
		$this->fetchError($code,$message);
	}
	function noResults($params){
		$code =202;
		$message = sprintf($this->CODES[$code],$params['keyword']);
		$this->fetchError($code,$message);
	}
	function errorJSON($params){
		$code = 500;
		$message = 'JSON Error';
		  switch ($params['code']) {
	        case JSON_ERROR_NONE:
	            $message .= ' - No errors';
	        break;
	        case JSON_ERROR_DEPTH:
	            $message .=  ' - Maximum stack depth exceeded';
	        break;
	        case JSON_ERROR_STATE_MISMATCH:
	            $message .=  ' - Underflow or the modes mismatch';
	        break;
	        case JSON_ERROR_CTRL_CHAR:
	            $message .=  ' - Unexpected control character found';
	        break;
	        case JSON_ERROR_SYNTAX:
	            $message .=  ' - Syntax error, malformed JSON';
	        break;
	        case JSON_ERROR_UTF8:
	            $message .=  ' - Malformed UTF-8 characters, possibly incorrectly encoded';
	        break;
	        default:
	            $message .= ' - Unknown error';
	        break;
    	}
		$this->fetchError($code,$message);
	}
	protected function fetchError($code,$message){
		$this->controller->header('HTTP/1.1  '.$code.' '.$message);
		$response = compact('code','message');
		echo json_encode($response,JSON_NUMERIC_CHECK );
	}
	
}
?>