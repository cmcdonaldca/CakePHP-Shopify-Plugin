<?php
class ShopifyAPIComponent extends Component {
	var $components = array('ShopifyAuth', 'Shopify.Curl');
	private $api_key;
	private $secret;
	private $is_private_app;
	private $last_response_headers = null;

	public function __construct(&$controller, $settings=array()) {
		parent::__construct($controller, $settings);
		$this->name = "ShopifyAPI";
		$this->api_key = Configure::read('api_key');
		$this->secret = Configure::read('shared_secret');
    $this->is_private_app = Configure::read('is_private_app');
	}

  public function getShopDomain() {
    return $this->ShopifyAuth->shop_domain;
  }

	public function isAuthorized() {
		return strlen($this->ShopifyAuth->shop_domain) > 0 && strlen($this->ShopifyAuth->token) > 0;
	}
	
	public function callsMade()
	{
		return $this->shopApiCallLimitParam(0);
	}

	public function callLimit()
	{
		return $this->shopApiCallLimitParam(1);
	}

	public function callsLeft($response_headers)
	{
		return $this->callLimit() - $this->callsMade();
	}

	public function call($method, $path, $params=array())
	{
		if (!$this->isAuthorized())
			return;
		$password = $this->is_private_app ? $this->secret : md5($this->secret.$this->ShopifyAuth->token);
		$baseurl = "https://{$this->api_key}:$password@{$this->ShopifyAuth->shop_domain}/";
	
		$url = $baseurl.ltrim($path, '/');
		$query = in_array($method, array('GET','DELETE')) ? $params : array();
		$payload = in_array($method, array('POST','PUT')) ? stripslashes(json_encode($params)) : array();
    $request_headers = in_array($method, array('POST','PUT')) ? array("Content-Type: application/json; charset=utf-8", 'Expect:') : array();
    $request_headers[] = 'X-Shopify-Access-Token: ' . $this->ShopifyAuth->token;
		list($response_body, $response_headers) = $this->Curl->HttpRequest($method, $url, $query, $payload, $request_headers);
    $this->last_response_headers = $response_headers;
    $response = json_decode($response_body, true);

		if (isset($response['errors']) or ($this->last_response_headers['http_status_code'] >= 400))
			throw new ShopifyApiException($method, $path, $params, $this->last_response_headers, $response);

		return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
	}
	
	private function shopApiCallLimitParam($index)
	{
		if ($this->last_response_headers == null)
		{
			return 0;
		}
		$params = explode('/', $this->last_response_headers['http_x_shopify_shop_api_call_limit']);
		return (int) $params[$index];
	}	
}

class ShopifyApiException extends Exception
{
	protected $method;
	protected $path;
	protected $params;
	protected $response_headers;
	protected $response;
	
	function __construct($method, $path, $params, $response_headers, $response)
	{
		$this->method = $method;
		$this->path = $path;
		$this->params = $params;
		$this->response_headers = $response_headers;
		$this->response = $response;
		
		parent::__construct($response_headers['http_status_message'], $response_headers['http_status_code']);
	}

	function getMethod() { return $this->method; }
	function getPath() { return $this->path; }
	function getParams() { return $this->params; }
	function getResponseHeaders() { return $this->response_headers; }
	function getResponse() { return $this->response; }
}

?>
