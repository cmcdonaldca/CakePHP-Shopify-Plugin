<?php 
	echo $this->Form->create(false, array('url'=>'/shopify/install/go'));//array('plugin'=>'shopify', 'controller'=>'install','action' => 'go')); 
	echo $this->Form->input('shop_domain', array( 'placeholder'=>'your-jewel-store.myshopify.com', 'label' => 'Enter your Shop Domain:' ));
	echo $this->Form->end('Go');
?>
