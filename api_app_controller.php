<?php
class ApiAppController extends Controller {
	var $name = 'ApiApp';
	var $components = array('RequestHandler','Auth','Session','Api.Api');
	
	var $helpers = array('Html','Form','Session');
	
	function beforeFilter() {
		parent::beforeFilter();
		$this->paginate = array('limit'=>10);
		$this->Auth->allow('display');
	}
	function redirect($config){
		if($this->RequestHandler->isAjax()){
			$this->beforeRender();
		}else{
			return parent::redirect($config);
		}
	}
	function beforeRender(){
		if($this->isAPIRequest()){
			$message = $this->Session->read('Message.flash.message');
			if($message) $this->Session->write('meta.message',$message);
			$this->sanitizeApiRequest();
			
		}else{
			return parent::beforeRender();
		}
	}
	protected function isAPIRequest(){
		return $this->RequestHandler->isAjax()||$this->RequestHandler->ext=='json';
	}
	protected function sanitizeApiRequest(){
		if($this->name=='CakeError'){
			if(isset($this->viewVars['title'])){
				if($this->viewVars['title'] == 'Missing Controller'){
					return $this->cakeError('invalidEndpoint');
				}
			}
			return;
		} 
		header('Content-Type: application/json');
		$meta = $this->Session->read('meta');
		$meta['code'] = '200';
		$response = array('meta'=>$meta);
		if(in_array($this->params['action'],array('index','view','register','login','logout'))){
			$endpoint = $this->params['controller'];
			if($this->params['action']!='index'){
				$endpoint =  Inflector::singularize($endpoint);
			}
			$dataField = Inflector::variable($endpoint);
			if(isset($this->viewVars[$dataField])){
				$response['data'] = $this->viewVars[$dataField];
			}else if($this->params['action']=='logout'){
				$response['data']['User'] = '0';
				$response['meta']['message'] = 'Logout';
			}else{
				return $this->cakeError('dataNotSet');
			}
		}else if($this->params['action']=='add'||$this->params['action']=='edit'){
			$modelClass = $this->modelClass;
			$this->data[$modelClass]['id'] = $this->$modelClass->id;
			$response['data'] = $this->data;
		}
		echo $this->encodeData($response);
		$this->_stop();
	}
	protected function encodeData($response) {
	  if(isset($response['data'])){
		  $endpoint = $this->params['controller'];
		  $__Class = Inflector::classify($endpoint);
		  $__data = array();
		  if($this->action=='index'){
			  foreach($response['data'] as $key=>$value){
				  array_push($__data,$value[$__Class]);
			  }
		  }else{
			  $__data = $response['data'][$__Class];
		  }
		  if($this->params['controller']=='master_configs'){
			  if(count($__data)==1){
				  $__data = $__data[0];
			  }
		  }
		  $response['data']=$__data;
		  if($response['data']==null){
			if(isset($response['meta']['keyword'])){
				$keyword = $response['meta']['keyword'];
				return $this->cakeError('noResults',compact('keyword'));  
			}else if($this->params['action']=='login'){
				return $this->cakeError('invalidLogin');  
			}
			else 
				return $this->cakeError('emptyRecord');  
		  }
			
	 }
	 $json_encode =  json_encode($response,JSON_NUMERIC_CHECK );
	 if($code=json_last_error()){
	 	return $this->cakeError('errorJSON',compact('code')); 
	 }
	 return $json_encode;
  }
	
}

?>