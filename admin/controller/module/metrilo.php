<?php
class ControllerModuleMetrilo extends Controller {

	private $error = array();

	public function index() {


		$this->load->language('module/metrilo');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('metrilo', $this->request->post);

			if($this->request->post['metrilo_is_enabled'] == '1') {
				if(strlen($this->request->post['metrilo_api_key']) > 0) {
					$this->session->data['success'] = $this->language->get('message_enabled');
					$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
				} else {
					$this->error['warning'] = $this->language->get('message_warning');
				}
			} else {
				$this->error['warning'] = $this->language->get('message_disabled');
			}

		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_metrilo_api_key'] = $this->language->get('text_metrilo_api_key');

		$this->data['text_enabled'] = $this->language->get('text_enabled');

		$this->data['option_enable'] = $this->language->get('option_enable');
		$this->data['option_disable'] = $this->language->get('option_disable');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs'] = array();

 		$this->data['breadcrumbs'][] = array(
     		'text'      => $this->language->get('text_home'),
		'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
    		'separator' => false
 		);

 		$this->data['breadcrumbs'][] = array(
     		'text'      => $this->language->get('text_module'),
		'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
    		'separator' => ' :: '
 		);

 		$this->data['breadcrumbs'][] = array(
     		'text'      => $this->language->get('heading_title'),
		'href'      => $this->url->link('module/metrilo', 'token=' . $this->session->data['token'], 'SSL'),
    		'separator' => ' :: '
 		);

		$this->data['action'] = $this->url->link('module/metrilo', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');


		if (isset($this->request->post['metrilo_api_key'])) {
			$this->data['metrilo_api_key'] = $this->request->post['metrilo_api_key'];
		} else {
			$this->data['metrilo_api_key'] = $this->config->get('metrilo_api_key');
		}

		if (isset($this->request->post['metrilo_is_enabled'])) {
			$this->data['metrilo_is_enabled'] = $this->request->post['metrilo_is_enabled'];
		} else {
			$this->data['metrilo_is_enabled'] = $this->config->get('metrilo_is_enabled');
		}

		$this->load->model('design/layout');

		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		$this->template = 'module/metrilo.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());

	}

}
?>
