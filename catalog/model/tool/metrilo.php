<?php

class ModelToolMetrilo extends Model {

	private $metrilo_is_enabled;
	private $metrilo_api_key;
	private $events_queue = [];

	private function init() {

		$this->load->model('setting/setting');

		$this->model_setting_setting->getSetting('metrilo');

		$this->metrilo_is_enabled = $this->config->get('metrilo_is_enabled');

		$this->metrilo_api_key = $this->config->get('metrilo_api_key');

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('account/order');
		$this->load->model('tool/image');

	}

	public function hasEventsInQueue(){
		if(count($this->events_queue) > 0){
			return true;
		}
		return false;
	}

	public function addProductViewEvent(){
		$this->init();

		$product_id = $this->request->get['product_id'];
		if(isset($product_id) && $product_id){
			$product = $this->model_catalog_product->getProduct($product_id);
			$productData = array(
				'id'		=> $product['product_id'],
				'sku'		=> $product['sku'],
				'name'		=> $product['name'],
				'price'		=> $product['price']
			);
			if(isset($product['image']) && !empty($product['image'])){
				$image_url = $this->model_tool_image->resize($product['image'], 500, 500);
				$productData['image'] = $image_url;
			}

			$this->addEventInQueue('event', 'view_product', $productData);
		}
	}

	public function addEventInQueue($method, $event_type, $params = false, $in_session = false){

		$tracking_event = array(
			'method' 			=> $method, 
			'event_type'		=> $event_type, 
			'params'			=> $params
			);

		if($in_session){
			$this->addEventInSession($tracking_event);
		}else{
			array_push($this->events_queue, $tracking_event);
		}

		return true;
	}

	public function addEventInSession($tracking_event){
		$metrilo_queue = [];
		if(isset($this->session->data['metrilo_queue'])){
			$metrilo_queue = json_decode($this->session->data['metrilo_queue']);
		}
		array_push($metrilo_queue, $tracking_event);
		$this->session->data['metrilo_queue'] = json_encode($metrilo_queue, true);
	}

	public function flushEventsFromSession(){
		if(isset($this->session->data['metrilo_queue'])){
			$metrilo_queue = json_decode($this->session->data['metrilo_queue'], true);
			if(count($metrilo_queue) > 0){
				foreach($metrilo_queue as $k => $tracking_event){
					array_push($this->events_queue, $tracking_event);
				}
			}
			$this->session->data['metrilo_queue'] = json_encode([], true);
		}
	}

	public function renderTrackingScript(){
		$rendered_script = '';
		if($this->hasEventsInQueue()){
			foreach($this->events_queue as $event){
				$event_script = '';
				if($event['method'] == 'event'){
					$event_script = 'metrilo.event("'.$event['event_type'].'", '.json_encode($event['params']).'); ';
				}
				if($event['method'] == 'identify'){
					$event_script = 'metrilo.identify("'.$event['event_type'].'", '.json_encode($event['params']).'); ';
				}
				$rendered_script .= $event_script;
			}
		}
		return $rendered_script;
	}

	public function addToCartEvent($product_info = false, $quantity = 1){
		if($product_info){
			$productData = array(
				'id'		=> $product_info['product_id'],
				'sku'		=> $product_info['sku'],
				'name'		=> $product_info['name'],
				'price'		=> $product_info['price'], 
				'quantity'	=> $quantity
			);
			$this->addEventInQueue('event', 'add_to_cart', $productData, true);
		}
	}

	public function addCategoryViewEvent($category_info){
		if($category_info){
			$categoryData = array(
				'id'		=> $category_info['category_id'],
				'name'		=> $category_info['name']
			);
			$this->addEventInQueue('event', 'view_category', $categoryData);
		}
	}

	public function orderPlaced($order_id){

		$this->init();

		$order_info = $this->model_account_order->getOrder($order_id);
		$order_products = $this->model_account_order->getOrderProducts($order_id);

		// prepare order tracking event

		$tracking_event = array(
			'order_id'				=> $order_id, 
			'amount' 				=> $order_info['total'], 
			'items' 				=> array(),
			'shipping_method'		=> $order_info['shipping_method'], 
			'payment_method'		=> $order_info['payment_method']			
		);

		// prepare products for order event

		foreach ($order_products as $product) {

			$product_hash = array(
				'id' 		=> $product['product_id'],
				'quantity' 	=> $product['quantity'],
				'name' 		=> $product['name']
			);
			array_push($tracking_event['items'], $product_hash);

		}

		// add order event in queue

		$this->addEventInQueue('event', 'order', $tracking_event);


		// prepare identify event

		$identify_params = array(
			'email'			=> $order_info['email'], 
			'first_name'	=> $order_info['payment_firstname'], 
			'last_name'		=> $order_info['payment_lastname'], 
			'name'			=> $order_info['payment_firstname'] .' '.$order_info['payment_lastname']
		);

		// add identify event in queue

		$this->addEventInQueue('identify', $identify_params['email'], $identify_params);

	}


	// Ensure logged in user is identified

	public function ensureCustomerIdentify($customer_object){

		if($customer_object->getEmail() && !isset($this->session->data['metrilo_identify'])){

			$identify_params = array(
				'email'			=> $customer_object->getEmail(), 
				'first_name'	=> $customer_object->getFirstName(), 
				'last_name'		=> $customer_object->getLastName(), 
				'name'			=> $customer_object->getFirstName().' '.$customer_object->getLastName()
			);

			$this->addEventInQueue('identify', $identify_params['email'], $identify_params);
			$this->session->data['metrilo_identify'] = true;
		}

	}


	// Fetch Metrilo API key for JavaScript tracking librari

	public function getMetriloApiKey() {

		$this->init();

		if (isset($this->metrilo_is_enabled) && $this->metrilo_is_enabled && isset($this->metrilo_api_key) && ($this->metrilo_api_key != '')) {
			return $this->metrilo_api_key;
		} else {
			return null;
		}

	}

}
?>
