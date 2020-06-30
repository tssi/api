<?php

class ApiAppModel extends Model {
	var $disposableFields = array('created','modified','password');
	var $cacheExpires = '+1 day';
	var $cacheDirectory = 'system01';
	var $usePaginationCache = false;
	function paginate ($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
 
		if ((Configure::read('Cache.disable') === false) && (Configure::read('Cache.check') === true) && $this->usePaginationCache) {
				//$key = $options['cache'];
				$expires = $this->cacheExpires;
				$conf = $this->cacheDirectory;
		}
		$conf = strtolower($this->alias);
		$this->_set_cache_config($conf);
		$args = func_get_args();
		if ($this->usePaginationCache) {
	 
			$args = func_get_args();
			$uniqueCacheId = '';
			
			$prefix = array();
			if(isset($args[0])){
				$conds = $args[0];
				foreach($conds as $cv){
					$field =  array_keys($cv)[0];
					$value =  array_values($cv)[0];
					$field = explode('.',$field)[1];
					if(is_array($value))
						$value = implode(',', $value);
					array_push($prefix,$field.'-'.$value) ;
				}
			}
			if(isset($args[3])) array_push($prefix,'page-'.$args[3]);
			if(isset($args[4])) array_push($prefix,'of-'.$args[4]);
			
			foreach ($args as $arg) {
				$uniqueCacheId .= serialize($arg);
			}
			$uniqueCacheId = md5($uniqueCacheId);
			array_push($prefix,$uniqueCacheId);
			
			$prefix = implode('-',$prefix);
			$pagination = Cache::read($prefix, $conf);
	 
		}
		if (!empty($extra['contain'])) {
				$contain = $extra['contain'];
			}
	 
		if (!empty($extra['joins'])) {
				$joins = $extra['joins'];
			}
		if (!empty($extra['group'])) {
				$group = $extra['group'];
		}
		if (empty($pagination)) {
			$pagination = $this->find('all', compact('conditions', 'fields', 'order', 'limit', 'page',  'group', 'contain','joins','group','recursive')); //'recursive'
			if ($this->usePaginationCache)
			Cache::write( $prefix , $pagination, $conf);
	 
		}
	 
		return $pagination;
	 
	}
		
	protected function _set_cache_config ($conf) {
 
		$expires = $this->_set_expiry();
		$path = CACHE . $this->cacheDirectory.DS. $conf;
	 
		///// create folder if not exists
		App::import('Folder');
		$Folder = new Folder($path, true); //, '755');
		unset ($Folder);
	 
		Cache::config($conf, array(
					'engine' => 'File', // DA SPECIFICARE, non defult 'FIle' in cake 1.3
					'path' => $path,
					'prefix'    => '_',
					'duration'  => $expires
					)
			);
 
    }
	protected function _set_expiry() {
		$expires = $this->cacheExpires;
		return $expires;
    }

 	public function afterSave($created){
 		parent::afterSave($created);
 		$this->clearCacheFolder();
 	}
 	public function afterDelete(){
 		parent::afterDelete();
 		$this->clearCacheFolder();
 	}
 	protected function clearCacheFolder($force=false,$path=null){
 		if($this->usePaginationCache || $force):
	 		$folder = strtolower($this->name);
	 		if($path ==null)
	 			$path = CACHE . $this->cacheDirectory.DS.$folder;
	 		App::import('Folder');
	 		$cacheFolder = new Folder($path);
	 		$cacheFolder->delete();
	 		$cacheFolder = new Folder($path, true);
 		endif;
 	}
    protected function delete_cache_data($name = null, $conf = 'system01') {
		if($conf == 'system01')
			$conf = strtolower($this->alias);
		
	   $this->_set_cache_config($conf);
 
		if ($name) {
			Cache::delete($name, $conf);
		} else {
			Cache::clear(false, $conf);
		}
    }
}

?>