<?php

class ShopifyAuthComponent extends Component {

  var $name = "ShopifyAuth";
  var $components = array('Session', 'Shopify.Curl');	

  private $_excludeCheckOnList = array(array('controller'=>'Install', 'action'=>'index'), array('controller'=>'Install', 'action'=>'go'));

  private $skipAuthCheck = false;

  public $isAuthorized = false;
  public $shop_domain;
  public $token;

  public function __construct(&$controller, $settings=array()) {
    parent::__construct($controller, $settings);
    if (isset($settings['skipAuthCheck'])) {
      $this->skipAuthCheck = $settings['skipAuthCheck'];
    }
    if (!empty($settings['exclude_check_on'])) {
      foreach ($settings['exclude_check_on'] as $exclude_check_on)
        $this->_excludeCheckOnList[] = $exclude_check_on;
    }
    $this->secret = Configure::read('shared_secret');
    $this->api_key = Configure::read('api_key');
    $this->scope = Configure::read('scope');
  }

  function initialize(&$controller) {

    $this->isAuthorized = false;
    if ($this->Session->check('shopify.shop_domain')) {
      $shop_domain = $this->Session->read('shopify.shop_domain');
      $token = $this->Session->read('shopify.token');
      if ($token != '') {
        $this->shop_domain = $shop_domain;
        $this->token = $token;
        $this->isAuthorized = true;
      } else {
        $this->logout();
      }
    }
    if (!$this->isAuthorized) {
      if (isset($controller->request->query['shop'])) {
        $shop_domain = $controller->request->query['shop'];

        if (isset($controller->request->query['signature']) && isset($controller->request->query['timestamp'])) {
          $hasCode = false;
          $signature = $controller->request->query['signature'];
          $timestamp = $controller->request->query['timestamp'];
          $code = '';
          if (isset($controller->request->query['code'])) {
            $code = $controller->request->query['code'];
            $hasCode = true;
          }
          if ($this->_isAuthorized($shop_domain, $code, $signature, $timestamp)) {
            $shop = $controller->Shop->findByShopDomain($shop_domain);
            $token = '';
            if ($hasCode) {
              $token = $this->getAccessToken($shop_domain, $code, $signature, $timestamp);
            } else {
              if (isset($shop["Shop"]) && isset($shop["Shop"]["token"])) {
                $token = $shop["Shop"]["token"];
              }
            }
            if ($token != '') {
              $this->logout();

              if ($hasCode) {
                // save the shop
                $shop = $controller->Shop->findByShopDomain($shop_domain);
                if (!isset($shop["Shop"])) {
                  $shop["Shop"] = array();
                  $shop["Shop"]["shop_domain"] = $shop_domain;
                }else {
                  unset($shop["Shop"]["modified"]);
                  unset($shop["Shop"]["created"]);
                }
                $shop["Shop"]["token"] = $token;
                $controller->Shop->save($shop);
              }

              $this->isAuthorized = true;
              $this->Session->write('shopify.shop_domain', $shop_domain);
              $this->Session->write('shopify.token', $token);
              $this->shop_domain = $shop_domain;
              $this->token = $token;
              $controller->redirect('/');
            }
          }

          // else we have a shop parameter, but no session
          // so redirect them
        } else {
          $return_url = Router::url(null, true);
          if (isset($controller->request->query['id'])) {
            $return_url .= "?id=" . urlencode($controller->request->query['id']);
          }
          $auth_url = $this->getAuthorizeUrl($shop_domain, $return_url);
          $controller->redirect($auth_url);
        }
      }
    }
    if ($this->skipAuthCheck)
      return;
    else if($controller->name == 'CakeError')
      return;
    else if(!$this->isAuthorized && !$this->_excludeCheckOn(&$controller))
      $controller->redirect(array('controller'=>'install', 'plugin'=>'shopify'));
  }

  public function setAuth($apiAuth) {
    $this->shop_domain = $apiAuth['shop_domain'];
    $this->token = $apiAuth['token'];
  }

  // Get the URL required to request authorization
  public function getAuthorizeUrl($shop_domain, $redirect_url='') {
    $url = "http://$shop_domain/admin/oauth/authorize?client_id={$this->api_key}&scope=" . urlencode($this->scope);
    if ($redirect_url != '') {
      $url .= "&redirect_uri=" . urlencode($redirect_url);
    }
    return $url;
  }

  // Once the User has authorized the app, call this with the code to get the access token
  public function getAccessToken($shop_domain, $code, $signature, $timestamp) {
    // POST to  POST https://SHOP_NAME.myshopify.com/admin/oauth/access_token
    $url = "https://$shop_domain/admin/oauth/access_token";
    $payload = "client_id={$this->api_key}&client_secret={$this->secret}&code=$code";
    list($response,) = $this->Curl->HttpRequest('POST', $url, '', $payload, array());
    $response = json_decode($response, true);
    if (isset($response['access_token']))
      return $response['access_token'];
    return '';
  }

  public function logout() {
    $this->Session->delete('shopify');
    $this->isAuthorized = false;
  }

  private function _isAuthorized($shop_domain, $code, $signature, $timestamp) {
    $part1 = $this->secret;
    if ($code != '')
      $part1 .= "code=" . $code;
    $part1 .= "shop=" . $shop_domain . "timestamp=" . $timestamp;

    return (md5($part1) === $signature);
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
