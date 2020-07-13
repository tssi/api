<?php

class ApiComponent extends Object {
	function initialize(&$controller, $settings = array()) {
        // saving the controller reference for later use
        $this->controller =& $controller;
    }
   function startup(&$controller) {
	  $params = $this->controller->params;
	
	  if (	$params['url']['ext'] === 'json' ||
			( 
				$this->controller->RequestHandler->isAjax() && 
				$params['controller']=='users' &&
				in_array($params['action'],array('register','login','logout'))
			)
		){
		
		  switch($_SERVER['REQUEST_METHOD']){
			case 'GET':
				$this->apiGet($this->controller);
			break;
			case 'POST':
				$this->apiPost($this->controller);
			break;
			case 'DELETE':
				$this->apiDelete($this->controller);
			break;
			case 'PUT':
				$this->apiPut($this->controller);
			break;
		  }
	  }
   }
	function beforeRender(&$controller) {
		$meta = $this->controller->Session->read('meta');
		$this->controller->set(compact('meta'));
	}
   protected function apiGet(&$controller){
		$endpoint = $this->controller->params['controller'];
		$__Class = Inflector::classify($endpoint);
		$Endpoint = &ClassRegistry::init($__Class);
		$conf = array();
		//Pagination config
		$page = (int)(isset($_GET['page'])?$_GET['page']:1);
		$limit = $conf['limit'] = isset($_GET['limit'])?$_GET['limit']:10;
		$recursive = isset($Endpoint->recursive)?$Endpoint->recursive:-1;
		$customPaginate = isset($Endpoint->customPaginate)?$Endpoint->customPaginate:null;
		$contain = isset($Endpoint->contain)?$Endpoint->contain:null;
		$consumableFields = isset($Endpoint->consumableFields)?$Endpoint->consumableFields:null;
		$disposableFields = isset($Endpoint->disposableFields)?$Endpoint->disposableFields:null;
		$offset = $conf['offset'] = $page?($page-1)*$limit:null;
		//Sorting
		$sort = isset($_GET['sort'])?$_GET['sort']:null;
		$direction = null;
		if($sort){
			switch($sort){
				case 'latest':
					$sort = 'modified';
					$direction = 'desc';
				break;
				case 'oldest':
					$sort = 'modified';
					$direction = 'asc';
				break;
				default:
					$sortParam = explode('-',$sort);
					if(count($sortParam)==2):
						$sort =  $sortParam[0];
						$direction =  $sortParam[1];
					endif;
				break;
			}
			
		}
		//Filter
		$conditions = array();
		$blacklist = array('url','page','limit','offset','sort','order','created','modified','fields','keyword');
		foreach($_GET as $field=>$value){
			if(!in_array($field,$blacklist)){
				$values =  explode(',', $value);
				if(count($values)>1)
					$value=$values;
				array_push($conditions,array($__Class.'.'.$field=>$value));
			}
		}
		//Search
		$keyword = null;
		$fields = null;
		if(isset($_GET['keyword'])) $keyword = '%'.$_GET['keyword'].'%';
		if($keyword&&isset($_GET['fields'])) $fields = explode(',',$_GET['fields']);
		if($keyword && $fields){
			$cond = array();
			foreach($fields as $fld){
				$cond[$__Class.'.'.$fld.' LIKE'] = $keyword;
			}
			$conditions = array_merge($conditions,array('OR'=>$cond));
		}
		$conf['conditions']=$conditions;
		//Meta Data
		$meta = array();
		$page_url = '';
		$meta['message'] = Inflector::humanize($endpoint);
		if($keyword)
			$meta['keyword'] = $_GET['keyword'];
		if($Endpoint->useTable)
		switch($this->controller->action){
			case 'index':
				//Pagination count
				$count_conf = $conf;
				$meta['limit'] = $limit;
				unset($count_conf['limit']);
				unset($count_conf['offset']);
				$count = $Endpoint->find('count',$count_conf);
				$last = $limit&&$limit!='less'?ceil($count/$limit):1;
				$next = $page < $last ? $page + 1:null;
				$prev = $page>1?$page - 1:null;
				$meta['message'] = 'List of '. $meta['message'];
				$meta['next'] = $next? $next:null;
				$meta['prev'] = $prev? $prev:null;
				$meta['last'] = $last;
				$meta['count'] = $count;
				$meta['page'] = $page;
				$meta['pages'] = $last;
				//Set up paginate
				$paginate = array();
				$paginate['page'] = $page;
				$paginate['cache'] = 'default';
				if($recursive) $paginate['recursive']=$recursive;
				if($contain) $paginate['contain']=$contain;
				if($consumableFields):
					$paginate['fields']=$consumableFields;
				else:
					$schema = $Endpoint->schema();
					$fields = array();
					foreach($schema as $sk=>$sv){
						array_push($fields,$sk);
					}
					$paginate['fields']=$fields;
				endif;
				if($disposableFields):
					$fields = $paginate['fields'];
					$paginate['fields'] = array_diff($fields,$disposableFields);
				endif;
				if($conditions) $paginate['conditions']=$conditions;
				if($sort&&$direction) $paginate['order']=array($__Class.'.'.$sort=>$direction);
				$paginate['limit']=$limit?$limit:$count;
				$paginateVars = array($__Class => $paginate);
				if($customPaginate){
					foreach($customPaginate as $cPK=>$cPD){
						$paginateVars[$cPK] = $cPD;
					}
				}
				$this->controller->paginate = $paginateVars;
				$paginate['limit']=$count;
			break;
			case 'view':
				$meta['message'] ='View '.Inflector::singularize($meta['message']).' '.$this->controller->params['id'];
			break;
		}
		$this->controller->conditions = $conditions;
		$meta['epoch'] = time();
		$this->controller->Session->write('meta',$meta);
   }
   protected function apiPost(&$controller){
	   $endpoint = $this->controller->params['controller'];
	   $__Class = Inflector::classify($endpoint);
	   $input = file_get_contents('php://input');
	   $data = array($__Class=>json_decode($input,true));
	   if($this->controller->params['action']!='logout')
	   		if(is_array($data[$__Class]))
		   foreach($data[$__Class] as $field=>$value){
			  
			   if(is_array($value)){
				   $__SubClass = Inflector::classify($field);
				   $data[$__SubClass] = $value;
			   }
		   }
	   if(isset($data[$__Class]['reorder'])){
		   $data =  $this->apiReorder($data[$__Class]['reorder'],$__Class);
		   unset($data[$__Class]['reorder']);
	   }
	   $this->controller->data = $data;
	   $meta = array();
	   $page_url = null;
	   $meta['message'] = $__Class;
	   $meta['epoch'] = time();
	   $this->controller->Session->write('meta',$meta);
   }
    protected function apiDelete(&$controller){
	   $endpoint = $this->controller->params['controller'];
	   $__Class = Inflector::classify($endpoint);
	   $input = file_get_contents('php://input');
	   $data = array($__Class=>json_decode($input,true));
	   $this->controller->data = $data;
	   $meta = array();
	   $page_url = null;
	   $meta['message'] = $__Class;
	   $meta['epoch'] = time();
	   $this->controller->Session->write('meta',$meta);
   }
    protected function apiPut(&$controller){
	   $endpoint = $this->controller->params['controller'];
	   $__Class = Inflector::classify($endpoint);
	   $data = array($__Class=> $_POST);
	   $this->controller->data = $data;
	   $meta = array();
	   $page_url = null;
	   $meta['message'] = $__Class;
	   $meta['epoch'] = time();
	   $this->controller->Session->write('meta',$meta);
   }
	protected function apiReorder($data,$class){
		$stack = array();
		foreach($data as $index=>$value){
			$item =  array($class=>array('id'=>$value, 'order'=>$index+1, 'sequence'=>$index+1));
			array_push($stack,$item);
		}
		return $stack;
	}
 }

?>