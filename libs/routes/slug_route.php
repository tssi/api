<?php
class SlugRoute extends CakeRoute {
    function parse($url) {
		$params = parent::parse($url);
		
		$input = json_decode(file_get_contents('php://input'),true);
		if (empty($params)) {
            return false;
        }
		if(isset($input['id'])){
			$params['pass']['id']=$input['id'];
		}
		switch($_SERVER['REQUEST_METHOD']){
			case 'DELETE':
				$params['action']='delete';
			break;
			case 'PUT':
				foreach($input as $key=>$value)
					$_POST[$key] = $value;
				$params['action']='edit';
			break;
		}
        if ($input) {
            return $params;
        }
        return false;
    }
 
}