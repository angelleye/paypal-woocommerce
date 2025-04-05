<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AngellEYE_PayPal_PPCP_Mock {

	protected $enabled = false;
	protected $mock_scenario = '';

	public function __construct() {
		// Load any stored config from options or session if desired
		$this->enabled = 'yes' === get_option('angelleye_pfw_ppcp_enable_negative_testing', 'no');
		$this->mock_scenario = get_option('angelleye_pfw_ppcp_mock_error_code', '');
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
