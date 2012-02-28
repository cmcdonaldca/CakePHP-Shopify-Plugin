<?php 
	echo $this->Form->create(false, array('url'=>'/shopify/install/go'));//array('plugin'=>'shopify', 'controller'=>'install','action' => 'go')); 
	echo $this->Form->input('shop_domain', array( 'label' => 'Enter your Shop Domain (i.e. jewels.myshopify.com)' ));
	echo $this->Form->end('Go');
?>
