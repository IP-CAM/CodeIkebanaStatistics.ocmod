<?php
class ControllerExtensionModuleCodeikebanaStatistics extends Controller {

    public function index() {
        
		$this->load->language('extension/module/codeikebana_statistics');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('module_codeikebana_statistics', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/codeikebana_statistics', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/codeikebana_statistics', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

                $setting_info = $this->model_setting_setting->getSetting('module_codeikebana_statistics');

		if (isset($this->request->post['module_codeikebana_statistics_status'])) {
			$data['module_codeikebana_statistics_status'] = $this->request->post['module_codeikebana_statistics_status'];
		} elseif (isset($setting_info['module_codeikebana_statistics_status'])) {
			$data['module_codeikebana_statistics_status'] = $setting_info['module_codeikebana_statistics_status'];
		} else {
		}
               
                $data['config_processing_status'] = $this->config->get('config_processing_status');
                $data['config_complete_status'] = $this->config->get('config_complete_status');
                
                $this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
                
                $this->load->model('report/statistics');
                $this->load->model('sale/order');
                $order_total = $this->model_sale_order->getTotalOrders();
                $order_compleated = $this->model_sale_order->getTotalOrdersByCompleteStatus();
                $order_processing = $this->model_sale_order->getTotalOrdersByProcessingStatus();
                        
                $data['statistics'] = $this->model_report_statistics->getStatistics();
                
                foreach ($data['statistics'] as $key => $stat) {
                    
                    switch ($stat['code']){
                        case 'order_sale': 
                            $order_total_statuses_ids = implode(',', array_merge($data['config_processing_status'], $data['config_complete_status']));
                            $data['statistics'][$key]['current'] = $this->model_sale_order->getTotalSales(['filter_order_status' => $order_total_statuses_ids]);
                            break;
                        case 'order_complete': 
                            $data['statistics'][$key]['current'] = $order_compleated;
                            break;
                        case 'order_processing': 
                            $data['statistics'][$key]['current'] = $order_processing;
                            break;
                        case 'order_other': 
                            $data['statistics'][$key]['current'] = $order_total - $order_processing - $order_compleated;
                            break;
                    }
                }
                
                $data['user_token'] = $this->session->data['user_token'];
                
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/codeikebana_statistics', $data));
    }

    public function refresh() {
        
        $code = $this->request->get['code'];
        $status = false;
        if ($code) {
            $this->load->model('sale/order');
            $this->load->model('report/statistics');
            switch ($code){
                case 'order_sale': 
                    $order_total_statuses_ids = implode(',', array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')));
                    $newTotal = $this->model_sale_order->getTotalSales(['filter_order_status' => $order_total_statuses_ids]);
                    $this->model_report_statistics->editValue('order_sale', $newTotal);
                    $status = true;
                    break;
                case 'order_complete': 
                    $order_compleated = $this->model_sale_order->getTotalOrdersByCompleteStatus();
                    $this->model_report_statistics->editValue('order_complete', $order_compleated);
                    $status = true;
                    break;
                case 'order_processing': 
                    $order_processing = $this->model_sale_order->getTotalOrdersByProcessingStatus();
                    $this->model_report_statistics->editValue('order_processing', $order_processing);
                    $status = true;
                    break;
                case 'order_other': 
                    $order_total = $this->model_sale_order->getTotalOrders();
                    $order_compleated = $this->model_sale_order->getTotalOrdersByCompleteStatus();
                    $order_processing = $this->model_sale_order->getTotalOrdersByProcessingStatus();
                    $this->model_report_statistics->editValue('order_other', $order_total - $order_processing - $order_compleated);
                    $status = true;
                    break;
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($status));
    }
    
    public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('codeikebana_statistics', 'catalog/model/checkout/order/addOrderHistory/before', 'extension/module/codeikebana_statistics/addOrderHistory');
        
        // disable default event
        $event = $this->model_setting_event->getEventByCode('statistics_order_history'); 
        if ($event && $event['status']){
            $this->model_setting_event->disableEvent($event['event_id']);
        }
        
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_codeikebana_statistics',  ['module_codeikebana_statistics_status' => "1"]);
    }
    
    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('codeikebana_statistics');
        
        // enable default event
        $event = $this->model_setting_event->getEventByCode('statistics_order_history'); 
        if ($event && !$event['status']){
            $this->model_setting_event->enableEvent($event['event_id']);
        }
        
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_codeikebana_statistics');
    }
}