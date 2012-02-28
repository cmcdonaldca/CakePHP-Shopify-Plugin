<?php

class ShopifyAuthComponent extends Component {
	var $name = "ShopifyAuth";
	var $components = array('Session');	
	private $_excludeCheckOnList = array(array('controller'=>'Install', 'action'=>'index'), array('controller'=>'Install', 'action'=>'go'));
	public $isAuthorized = false;
	public $shop_domain;
	public $token;

	public function __construct(&$controller, $settings=array()) {
		parent::__construct($controller, $settings);
		if (!empty($settings['exclude_check_on'])) {
			
			foreach ($settings['exclude_check_on'] as $exclude_check_on)
				$this->_excludeCheckOnList[] = $exclude_check_on;
		}
	}

	function initialize(&$controller) {

		if ($this->Session->check('shopify.shop_domain')) {
 			$shop_domain = $this->Session->read('shopify.shop_domain');
			$token = $this->Session->read('shopify.token');
			$signature = $this->Session->read('shopify.signature');
			$timestamp = $this->Session->read('shopify.timestamp');
			$this->isAuthorized = $this->_isAuthorized($shop_domain, $token, $signature, $timestamp);
			if ($this->isAuthorized) {
				$this->shop_domain = $shop_domain;
				$this->token = $token;
			} else {
				$this->logout();
			}
		}
		if (!$this->isAuthorized) {
			if (isset($_GET['shop']) && isset($_GET['t']) && isset($_GET['signature']) && isset($_GET['timestamp'])) {
	 			$shop_domain = $_GET['shop'];
				$token = $_GET['t'];
				$signature = $_GET['signature'];
				$timestamp = $_GET['timestamp'];
				$this->isAuthorized = $this->_isAuthorized($shop_domain, $token, $signature, $timestamp);
				if ($this->isAuthorized) {
					$this->logout();
					$this->Session->write('shopify.shop_domain', $shop_domain);
					$this->Session->write('shopify.token', $token);
					$this->Session->write('shopify.signature', $signature);
					$this->Session->write('shopify.timestamp', $timestamp);
					$this->shop_domain = $shop_domain;
					$this->token = $token;
				}
			}
		}

		// if we are not authorized AND this current controller/view is not excluded from an Auth Check: redirect to install screen
		if (!$this->isAuthorized && !$this->_excludeCheckOn(&$controller))
			$controller->redirect(array('controller'=>'install', 'plugin'=>'shopify'));
	}

	public function getAppInstallUrl($shop_domain) {
		return "http://" . $shop_domain . "/admin/api/auth?api_key=" . Configure::read('api_key');
	}

	public function logout() {
		$this->Session->delete('shopify');
	}
	
	private function _isAuthorized($shop_domain, $token, $signature, $timestamp) {
		$secret = Configure::read('shared_secret');
		return (md5($secret . "shop=" . $shop_domain . "t=" . $token . "timestamp=" . $timestamp) === $signature);
	}

	private function _excludeCheckOn(&$controller) {
		foreach ($this->_excludeCheckOnList as $exclude_check_on) {
			if ($controller->name == $exclude_check_on['controller'] && array_key_exists('action', $exclude_check_on) && $controller->view == $exclude_check_on['action'])
				return true;
			if ($controller->name == $exclude_check_on['controller'] && $controller->name == 'Pages')
			{
				if (is_array($controller->passedArgs) && count($controller->passedArgs) > 0 && $controller->passedArgs[0] == $exclude_check_on['page'])
					return true;
			}
		}
		return false;
	}
}

?>
