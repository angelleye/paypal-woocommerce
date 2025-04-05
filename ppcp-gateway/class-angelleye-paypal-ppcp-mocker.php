<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AngellEYE_PayPal_PPCP_Mock {

	protected $enabled = false;
	protected $mock_scenario = '';

	public function __construct() {
		$settings = get_option('woocommerce_angelleye_ppcp_settings', array());

		$this->enabled = isset($settings['enable_negative_testing']) && $settings['enable_negative_testing'] === 'yes';
		$this->mock_scenario = isset($settings['negative_testing_mock_error']) ? $settings['negative_testing_mock_error'] : '';

		if (class_exists('WC_Logger')) {
			$logger = wc_get_logger();
			$logger->info('Mocker constructed. Enabled: ' . var_export($this->enabled, true) . ' | Scenario: ' . $this->mock_scenario, array('source' => 'angelleye_ppcp'));
		}
	}

	public function is_enabled() {
		return (bool) $this->enabled;
	}

	public function get_mock_scenario() {
		return $this->mock_scenario;
	}

	public function get_mock_response( $endpoint, $action ) {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		switch ( $this->mock_scenario ) {
			case 'payer_action_required':
				return [
					'name'     => 'UNPROCESSABLE_ENTITY',
					'details'  => [
						[ 'issue' => 'PAYER_ACTION_REQUIRED' ]
					],
					'message'  => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
					'debug_id' => 'mock-debug-id-123456',
					'links'    => [
						[
							'href'   => 'https://www.sandbox.paypal.com/checkoutnow?token=MOCK123',
							'rel'    => 'payer-action',
							'method' => 'GET'
						]
					]
				];
			// Add more mock scenarios here
			default:
				return null;
		}
	}
}
