<h1>Install this App in your Store</h1>
<?php 
	echo $this->Form->create(false, array('action' => 'go')); 
	echo $this->Form->input('shop_domain', array( 'label' => 'Your Shop Domain (i.e. jewels.myshopify.com' ));
	echo $this->Form->end('Go');

?>
