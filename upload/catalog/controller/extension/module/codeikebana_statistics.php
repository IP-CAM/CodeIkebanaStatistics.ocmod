<?php
class ControllerExtensionModuleCodeikebanaStatistics extends Controller {
    public function addOrderHistory(&$route, &$args) {
            $this->load->model('checkout/order');

            $order_info = $this->model_checkout_order->getOrder($args[0]);
            
            if ($order_info) {
//            var_dump($args[1]);
//            var_dump($order_info['order_status_id']);
//            var_dump($this->config->get('config_complete_status'));
//            var_dump($this->config->get('config_processing_status'));
                    $this->load->model('report/statistics');

                    // If order status in complete or proccessing add value to sale total
                    if (in_array($args[1], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                        $this->model_report_statistics->addValue('order_sale', $order_info['total']);	
                    }

                    // If order status not in complete or proccessing remove value to sale total
                    if (!in_array($args[1], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                            $this->model_report_statistics->removeValue('order_sale', $order_info['total']);
                    }

                    // Remove from processing status if new status is not array
                    if (in_array($order_info['order_status_id'], $this->config->get('config_processing_status')) && !in_array($args[1], $this->config->get('config_processing_status'))) {
                            $this->model_report_statistics->removeValue('order_processing', 1);
                    }

                    // Add to processing status if new status is not array		
                    if (!in_array($order_info['order_status_id'], $this->config->get('config_processing_status')) && in_array($args[1], $this->config->get('config_processing_status'))) {
                            $this->model_report_statistics->addValue('order_processing', 1);
                    }

                    // Remove from complete status if new status is not array
                    if (in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && !in_array($args[1], $this->config->get('config_complete_status'))) {
                            $this->model_report_statistics->removeValue('order_complete', 1);
                    }

                    // Add to complete status if new status is not array		
                    if (!in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && in_array($args[1], $this->config->get('config_complete_status'))) {
                            $this->model_report_statistics->addValue('order_complete', 1);
                    }			
            }
    }
}
