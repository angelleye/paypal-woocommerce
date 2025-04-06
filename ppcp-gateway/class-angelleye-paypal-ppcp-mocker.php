<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AngellEYE_PayPal_PPCP_Mock {

	protected bool $enabled = false;
	protected mixed $mock_scenario = '';
	protected string $restricted_action = '';


	public function __construct() {
		$settings = get_option('woocommerce_angelleye_ppcp_settings', array());

		$this->enabled = isset($settings['enable_negative_testing']) && $settings['enable_negative_testing'] === 'yes';
		$this->mock_scenario = $settings['negative_testing_mock_error'] ?? '';
		$this->restricted_action = isset($settings['mock_restriction']) ? strtolower($settings['mock_restriction']) : '';
	}

	public function is_enabled(): bool {
		return $this->enabled;
	}

	public function get_mock_scenario() {
		return $this->mock_scenario;
	}

	public function get_mock_response( $endpoint, $action ): ?array {

		// Check if mocker is disabled.
		if ( ! $this->is_enabled() ) {
			return null;
		}

		// Check for mock restriction.
		if ( ! empty($this->restricted_action) && strtolower($action) !== $this->restricted_action ) {
			return null;
		}

		return match ( strtolower( $this->mock_scenario ) ) {
			'payer_action_required' => [
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
			],
			default => null,
		};
	}
}
