<?php
Configure::write('Api.MASTER_ROUTES','');
class SlugRoute extends CakeRoute {
    function parse($url) {
		$params = parent::parse($url);
		if (empty($params)) {
            return false;
        }

		$__INPUT =file_get_contents('php://input');
		
		if($__INPUT):
			$_REQUEST['__INPUT'] = $__INPUT;
		elseif(isset($_REQUEST['__INPUT'])):
			$__INPUT = $_REQUEST['__INPUT'];
		endif;

		$input = json_decode($__INPUT,true);
		
		
		if(isset($input['id'])){
			$params['pass']['id']=$input['id'];
		}
		
		switch($_SERVER['REQUEST_METHOD']){
			case 'DELETE':
				$params['action']='delete';
			break;
			case 'PUT':
				
				if(is_array($input)):
					foreach($input as $key=>$value)
						$_POST[$key] = $value;
				endif;
				
				$params['action']='edit';
			break;
		}
        if ($input) {
            return $params;
        }
        return false;
    }
 
}