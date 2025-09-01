<?php
class ApiAppError extends ErrorHandler {
	var $CODES = array(
			202=>'No results found for %s',
			401=>'Invalid Login',
			403=>'Invalid Endpoint',
			402=>'Data not set',
			404=>'Empty Record',
			405=>'Invalid Token',
			406=>'You are not authorized to add admin users'
	);
	function invalidLogin($params){
		$code = 401;
		$message = $this->CODES[$code];
		if(isset($params['message']))
			$message = $params['message'];
		$this->fetchError($code,$message);
	}
	function invalidAdminUser($params){
		$code = 406;
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
	function invalidToken($params){
		$code = 405;
		$message = $this->CODES[$code];
		if(isset($params['token']))
			$message .= ' - Token: ' . $params['token'];
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
		header("Pragma: no-cache");
		header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
		header('Content-Type: application/json');
		header('HTTP/1.1 '.$code.' '.$message);
		$response = compact('code','message');
		echo json_encode($response,JSON_NUMERIC_CHECK );
	}
	
}
?>