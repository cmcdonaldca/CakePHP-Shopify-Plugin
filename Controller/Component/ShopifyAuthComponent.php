<?php

class ShopifyAuthComponent extends Component {
	var $name = "ShopifyAuthComponent";
	var $components = array('Session');	

	public $shop_domain;
	public $token;

	function initialize(&$controller, $settings = array()) {
		$authorized = false;
		if ($this->Session->check('shopify.shop_domain')) {
 			$shop_domain = $this->Session->read('shopify.shop_domain');
			$token = $this->Session->read('shopify.token');
			$signature = $this->Session->read('shopify.signature');
			$timestamp = $this->Session->read('shopify.timestamp');
			$authorized = $this->_isAuthorized($shop_domain, $token, $signature, $timestamp);
			if ($authorized) {
				$this->shop_domain = $shop_domain;
				$this->token = $token;
			} else {
				logout();
			}
		}
		if (!$authorized) {
			if (isset($_GET['shop']) && isset($_GET['t']) && isset($_GET['signature']) && isset($_GET['timestamp'])) {
	 			$shop_domain = $_GET['shop_domain'];
				$token = $_GET['token'];
				$signature = $_GET['signature'];
				$timestamp = $_GET['timestamp'];
				$authorized = $this->_isAuthorized($shop_domain, $token, $signature, $timestamp);
				if ($authorized) {
					$this->Session->write('shopify.shopify_domain', $shop_domain);
					$this->Session->write('shopify.token', $token);
					$this->Session->write('shopify.signature', $signature);
					$this->Session->write('shopify.timestamp', $timestamp);
					$this->shop_domain = $shop_domain;
					$this->token = $token;
				}
			}
		}

		if (!$authorized && $controller->name != "Install")
			$controller->redirect(array('controller'=>'install', 'plugin'=>'shopify'));
	}
	
	public function getAppInstallUrl($shop_domain) {
		return "http://" . $shop_domain . "/admin/api/auth?api_key=" . Configure::read('api_key');
	}
	
	private function _isAuthorized($shop_domain, $token, $timestamp, $signature) {
		$secret = Configure::read('shared_secret');
		return (md5($secret . "shop=" . $shop_domain . "t=" . $token . "timestamp=" . $timestamp) === $signature);
	}

	public function logout() {
		$this->Session->delete('shopify');
	}
}

?>
