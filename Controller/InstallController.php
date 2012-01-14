<?php

class InstallController extends AppController {
	var $name = "Install";

	public function index() {

	}

	public function go() {
		if (empty($this->data['shop_domain'])) {
			$this->render('index');
		} else {
			$auth_url = $this->ShopifyAuth->getAppInstallUrl($this->data['shop_domain']);
			$this->redirect($auth_url);
		}
	}
}

?>
