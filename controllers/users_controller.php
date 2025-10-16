<?php
class UsersController extends ApiAppController  {
	
	var $name = 'Users';
	var $uses = array('User','UserType','MasterConfig');
	var $__DEFAULT_SYS_FLDS = array('MasterConfig.sys_key','MasterConfig.sys_value');
	var $__DEFAULT_SYS_KEYS = array('MasterConfig.sys_key'=>array('DEFAULT_PASS','SCHOOL_ALIAS','ACTIVE_SY'));
	function beforeFilter() {
		parent::beforeFilter();
        $this->Auth->autoRedirect = false;
		$this->Auth->allow(array('login','add'));
		$this->Auth->userScope = array('User.status' => 'ACTIV');
		
    }
	function login(){
		$user = array('User'=>null);
		if($user = $this->Auth->user()){
			if(!$this->isAPIRequest())
				$this->redirect('/');
		}
		$allowLogin = true;

		$isInMarqaDomain = strpos($_SERVER['HTTP_HOST'], 'marqa.one') !== false;
		$skipChecking = true;
		if($isInMarqaDomain):
			if(!isset($this->data['User']['turnstile_token']) && !$skipChecking):
				$params = array('message'=>'Turnstile token is required');
				$user = array("User"=>array('user'=>"Access denied"));
				$this->cakeError('invalidLogin',$params);
				return $this->set('user', $user);
			endif;
			if(isset($this->data['User']['turnstile_token'])):
				$turnstileToken = $this->data['User']['turnstile_token'];
				$verificationResult = $this->verifyTurnstileToken($turnstileToken);
				
				if(!$verificationResult['success']):
					$params = array('message'=>'Invalid turnstile token');
					$params['error-codes'] = $verificationResult['error-codes'];
					if(isset($verificationResult['result'])):
						$params['result'] = $verificationResult['result'];
					endif;
					$user = array("User"=>array('user'=>"Access denied"));
					$this->cakeError('invalidLogin',$params);
					return $this->set('user', $user);
				endif;
			endif;
		endif;
		if(basename(ROOT)=='sap'):
			$_ENB_CONF = $this->MasterConfig->getVars(array('SAP_DISABLE_ON','SAP_ENABLE_ON'));
			$_ENB_CONF['SAP_DISABLE_ON']=strtotime($_ENB_CONF['SAP_DISABLE_ON']);
			$_ENB_CONF['SAP_ENABLE_ON']=strtotime($_ENB_CONF['SAP_ENABLE_ON']);
			
			// Converting time strings into timestamps
			$disableOn = ($_ENB_CONF['SAP_DISABLE_ON']);
			$enableOn = ($_ENB_CONF['SAP_ENABLE_ON']);
			$sysTime = time(); // Current server time

			// Initialize the login permission variable
			$allowLogin = true;
			// Logic to determine if login is allowed
			if ($sysTime > $disableOn) {
			    $allowLogin = false; // Default to not allowing login
			    if ($sysTime > $enableOn && $enableOn > $disableOn) {
			        $allowLogin = true; // Allow login if current time is past enable time and enable time is after disable time
			    }
			}

			$bypassValidated = $this->Session->read('BypassValidate');
			if($bypassValidated){
				$allowLogin = true;
			}
			

		endif;
		$user = array('User'=>null);
		if(isset($this->data['User'])){
			$isAdmin =  $this->data['User']['username']=='admin';

			if(!$allowLogin && !$isAdmin):
				$params = array('message'=>'Portal not yet ready');
				$this->Session->setFlash(__($params['message'], true));
				$user = array("User"=>array('user'=>"Access denied"));
				$this->cakeError('invalidLogin',$params);
				return $this->set('user', $user);
			endif;

			$password  = $this->data['User']['password'];
			if($this->isAPIRequest())
				$this->data['User']['password'] =  $this->Auth->password($password);
			if($this->Auth->login($this->data['User'])){
				$user = $this->Auth->user();
				if($user['User']['user_type_id']=='stdnt'):
					$this->User->Student->recursive = -1;
					$stud = $this->User->Student->findById($user['User']['username']);
					$user['User']['student_name'] = $stud['Student']['short_name'];
					
					// Check if password_changed is null, the include is response password_change_required
					if($user['User']['password_changed']==null):
						$user['User']['password_change_required'] = true;
					endif; 
					unset($user['User']['plain_password']);
					unset($user['User']['id']);
				endif;

				$this->Session->setFlash(__('Login successful', true));
				if(!$this->RequestHandler->isAjax()){
					$this->redirect('/');
				}
			}else{
				$verifiedHash = isset($user['User']['erb_hash']);
				$username = $this->data['User']['username'];
				$user = $this->User->findByUsername($username);

				if($user):
					// Verify erb_hash if no password set yet
					if($user['User']['password']=='' && isset($user['User']['erb_hash'])):
						$verifiedHash = false;
						$hash = md5($password);
						$erbHash = $user['User']['erb_hash'];
						$verifiedHash =  $hash==$erbHash;
						if($verifiedHash):
							$user['User']['password'] = $this->Auth->password($password);
							$user['User']['erb_hash'] =  null;
							$this->User->save($user);

							if($this->Auth->login($this->data['User'])):
								$user = $this->Auth->user();
								if(!$this->RequestHandler->isAjax()):
									$this->redirect('/');
								endif;
							else:
								$verifiedHash = false;
							endif;
						endif;
					endif;

					if(!$verifiedHash):
						$this->Session->setFlash(__('Invalid username/password', true));
						if($user){
							$user['User']['login_failed']=$user['User']['login_failed']+1;
							$user['User']['ip_failed']=$this->getIPAddr();
							$this->User->save($user);
							if($user['User']['status']!='ACTIV'){
								$this->Session->setFlash(__('Account not active', true));
							}
						}
						$user = array('User'=>null);
					endif;
				else:
						$user = array('User'=>null);
						$this->Session->setFlash(__('Invalid username/password', true));
				endif;
			}
		}
		if(isset($user['User']['id'])){
			$userType = $user['User']['user_type']=$user['User']['user_type_id'];
			$user['User']['login_success']=$user['User']['login_success']+1;
			$user['User']['ip_success']=$this->getIPAddr();
			$this->User->save($user);
			
			unset($user['User']['created']);
			unset($user['User']['modified']);
			unset($user['User']['user_type_id']);
			unset($user['User']['login_failed']);
			unset($user['User']['login_success']);
			unset($user['User']['ip_failed']);
			unset($user['User']['ip_success']);
			
			
			if (strtotime($user['User']['password_changed']) > strtotime('-30 days')){
				unset($user['User']['password_changed']);
			}
			$conditions = array('UserGrant.user_type_id'=>$userType);
			$fields = array('id','master_module_id');
			$grants = $this->UserType->UserGrant->find('list',compact('conditions','fields'));
			$access =  array_values($grants);
			$user['User']['access']=$access;
		}
		if($this->isAPIRequest()){
			$userObj =  $user['User'];
			$user = array('User'=>array('user'=>$userObj));
		}
		$this->set('user', $user);
	}
	function logout(){
		$this->Session->delete('BypassValidate');
		$this->set('user', array('User'=>array('logout'=>1)));
		$this->Session->delete('TableRegistry');
		$this->Auth->logout();
		if(!$this->isAPIRequest()){
			$this->redirect('login');
		}
	}
	function index() {
		$this->User->recursive = 0;
		$this->set('users', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('user', $this->User->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$data = $this->data;
			$isAdmin =  $this->Auth->user('user_type_id') == 'admin';
			if($data['User']['user_type_id'] == 'admin' && !$isAdmin){
				$this->Session->setFlash(__('You are not authorized to add admin users', true));
				$this->cakeError('invalidAdminUser');
				$this->redirect(array('action' => 'index'));
			}
			$this->User->create();
			if($this->isApiRequest()){
				if(isset($this->data['User']['password']))
				$this->data['User']['password']=$this->Auth->password($this->data['User']['password']);
			}
			if ($this->User->save($this->data)) {
				unset($this->data['User']['password']);
				$this->Session->setFlash(__('The user has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.', true));
			}
		}
		$userTypes = $this->User->UserType->find('list');
		$this->set(compact('userTypes'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->User->save($this->data)) {
				$this->Session->setFlash(__('The user has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->User->read(null, $id);
		}
		$userTypes = $this->User->UserType->find('list');
		$this->set(compact('userTypes'));
	}

	function delete($id = null) {
		if(isset($this->data['User']))
			$id = $this->data['User']['id'];
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for user', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->data['User']['status']='ARCHV';
		if ($this->User->save($this->data['User'])) {
			$this->Session->setFlash(__('User deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('User was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
	
	function reset_pass(){
		if(isset($this->data['User']))
			$id = $this->data['User']['id'];
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for user', true));
			$this->redirect(array('action'=>'index'));
		}
		
		$fields = $this->__DEFAULT_SYS_FLDS;
		$conditions = $this->__DEFAULT_SYS_KEYS;
		
		$config = $this->MasterConfig->find('list',compact('fields','conditions'));
		if(!isset($config['DEFAULT_PASS'])){
			$defaultPass = $config['SCHOOL_ALIAS'].''.$config['ACTIVE_SY'];
		}else{
			$defaultPass = $config['DEFAULT_PASS'];
		}
		$this->data['User']['password']=$this->Auth->password($defaultPass);
		$this->data['User']['erb_hash']=null;
		if ($this->User->save($this->data['User'])) {
			$this->Session->setFlash(__('Password has been reset', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('Password was not reset', true));
		$this->redirect(array('action' => 'index'));
	}

	protected function getIPAddr(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    $ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		    $ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	protected function verifyTurnstileToken($token){
		$isVerified = array('success'=>false,'error-codes'=>array());
		$_T_CONF = $this->MasterConfig->getVars(array('SCHOOL_T_KEY','SCHOOL_T_SECRET'));
		if(!isset($_T_CONF['SCHOOL_T_SECRET'])):
			$isVerified['error-codes'][] = 'secret_not_set';
			return $isVerified;
		endif;
		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$data = array(
			'secret' => $_T_CONF['SCHOOL_T_SECRET'],
			'response' => $token,
			'remoteip' => $this->getIPAddr()
		);
		$options = array(
		'http' => array(
			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($data)
			)
		);

		$context = stream_context_create($options);
		$response = file_get_contents($url, false, $context);

		if ($response === FALSE) :
			$isVerified['error-codes'][] = 'internal_error';
			return $isVerified;
		endif;

		$result = json_decode($response, true);
				
		if(!empty($result)):
			$isVerified['success'] = isset($result['success']) && $result['success'];
			$isVerified['result'] = $result;
			if(!$isVerified['success']):
				$isVerified['error-codes'] = array_merge($isVerified['error-codes'], isset($result['error-codes']) ? $result['error-codes'] : array());
			endif;
		endif;
		return $isVerified;
	}
}	
	
?>
