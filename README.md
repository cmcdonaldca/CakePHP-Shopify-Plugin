# CakePHP Shopify Plugin

This is a plugin that allows you to authenticate and make API calls with Shopify.  It's perfect for building custom and public Shopify Apps.  You can quickly and easily get a Shopify up and running and use CakePHP at the same time.

## Requirements

-CakePHP (built and tested on 2.0.5 Stable)

## Installation

After you have downloaded and installed CakePHP:

* create a folder called "Shopify" in your app/Plugin folder.
* extract (or clone) the contents of this repository to that "Shopify" folder.

you should now have these folder:

* app/Plugin/Shopify/Config
* app/Plugin/Shopify/Controller
* app/Plugin/Shopify/View

now you need edit the config file with your Shopify App API info

* open app/Plugin/Shopify/Config/shopify.php
* fill in the api_key and shared_secret

load the plugin and configuration into your CakePHP App

* open app/Config/bootstrap.php and add this at the bottom:

```php
CakePlugin::load('Shopify');
Configure::load('Shopify.shopify');
```

## Usage

### Sample 1 - In your App Controller
Simply add this line to your AppController and it can be used in all Controllers

```php
	public $components = array('Shopify.ShopifyAuth', 'Shopify.ShopifyAPI');
```

### Sample 2 - In a specific Controller
```php
<?php
App::uses('AppController', 'Controller');
class SimpleController extends AppController {

	public $name = 'Simple';
	public $components = array('RequestHandler', 'Shopify.ShopifyAuth', 'Shopify.ShopifyAPI');
	public function beforeRender() {
    		parent::beforeRender();
		// before rendering the views, check how many API calls we made
		$this->set('shopifyCallsMade', $this->ShopifyAPI->callsMade());
		$this->set('shopifyCallLimit', $this->ShopifyAPI->callLimit());
    	}

	public function index() {
		try {
			// I only want the id and title of the collections
			$fields = "fields=id,title";
			// get list of collections
			$custom_collections = $this->ShopifyAPI->call('GET', "/admin/custom_collections.json", $fields);
			$this->set('collections', $custom_collections);

		} catch (Exception $e) {
			
			// nothing fancy here
			echo "<pre>|";
			print_r($e);
			echo "</pre>";
		}
		
	}
}
```

Now, this plugin will automatically handle the shopify Authentication and redirect to an install form.  Did you get that?  You don't have to worry about authentication.  It's all handled.  All you have to do is start making API calls.


## Coming Soon

Ability to skip Shopify Authentication on certain controllers and views.  Currently it checks all including the front page. 

```php
	public $components = array(
				'Shopify.ShopifyAuth' => array('allow', array('controller/view1', 'controller2/view2')), 
				'Shopify.ShopifyAPI');

```

## Special Thanks

Special thanks to Sandeep for his initial release of the lightweight Shopify API client (https://github.com/sandeepshetty/shopify.php) that is used and slightly modified in this repository.
