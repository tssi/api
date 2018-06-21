<?php
class UsersController extends ApiAppController  {
	
	var $name = 'Users';
	var $uses = array('User','UserType');
	function beforeFilter() {
		parent::beforeFilter();
        $this->Auth->autoRedirect = false;
		$this->Auth->allow(array('login','add'));
		
    }
	function login(){
		$user = array('User'=>null);
		if($user = $this->Auth->user()){
			if(!$this->isAPIRequest())
				$this->redirect('/');
		}
		if(isset($this->data['User'])){
			//$this->data['User']['password'] =  $this->Auth->password($this->data['User']['password']);
			if($this->Auth->login($this->data['User'])){
				$user = $this->Auth->user();
				if(!$this->RequestHandler->isAjax()){
					$this->redirect('/');
				}
			}else{
				$this->Session->setFlash(__('Invalid username/password', true));
			}
		}
		if(isset($user['User']['id'])){
			unset($user['User']['created']);
			unset($user['User']['modified']);
		}
		if($this->isAPIRequest()){
			$userObj =  $user['User'];
			$user = array('User'=>array('user'=>$userObj));
		}
		$this->set('user', $user);
	}
	function logout(){
		$this->Auth->logout();
		if(!$this->RequestHandler->isAjax()){
			$this->redirect('login');
		}
		$this->set('user', array(array('User'=>array())));
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
			$this->User->create();
			if ($this->User->save($this->data)) {
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
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for user', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->User->delete($id)) {
			$this->Session->setFlash(__('User deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('User was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
}	
	
?>