<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AngellEYE_PayPal_PPCP_Mock {

	protected bool $enabled = false;
	protected string $mock_scenario = '';
	protected string $restricted_action = '';


	public function __construct() {
		$settings = get_option('woocommerce_angelleye_ppcp_settings', array());

		$this->enabled           = isset($settings['enable_negative_testing']) && $settings['enable_negative_testing'] === 'yes';
		$this->mock_scenario     = isset($settings['negative_testing_mock_error']) ? $settings['negative_testing_mock_error'] : '';
		$this->restricted_action = isset($settings['mock_restriction']) ? strtolower($settings['mock_restriction']) : '';

		$testmode_enabled = isset($settings['testmode']) && $settings['testmode'] === 'yes';
		if ( ! $testmode_enabled ) {
			$this->enabled = false;
		}
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

		switch ( strtolower( $this->mock_scenario ) ) {
			case 'payer_action_required':
				return [
					'name'     => 'UNPROCESSABLE_ENTITY',
					'details'  => [[
						'issue' => 'PAYER_ACTION_REQUIRED',
						'description' => 'Transaction cannot complete successfully, instruct the buyer to return to PayPal.'
					]],
					'message'  => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
					'debug_id' => 'mock-10398537340c8',
					'links'    => [[
						'href'   => 'https://developer.paypal.com/api/orders/v2/#error-PAYER_ACTION_REQUIRED',
						'rel'    => 'information_link',
						'method' => 'GET'
					],[
						'href'   => 'https://www.paypal.com/checkoutnow?token=MOCK5O190127TN364715T',
						'rel'    => 'payer-action',
						'method' => 'GET'
					]]
				];
			case 'invalid_array_min_items':
				return [
					'name'     => 'INVALID_REQUEST',
					'message'  => 'Request is not well-formed, syntactically incorrect, or violates schema.',
					'debug_id' => 'mock-10398537340c8',
					'details'  => [
						[
							'field'       => '/purchase_units',
							'value'       => '[]',
							'location'    => 'body',
							'issue'       => 'INVALID_ARRAY_MIN_ITEMS',
							'description' => 'The number of items in an array parameter is too small.'
						]
					],
					'links'    => [
						[
							'href' => 'https://developer.paypal.com/api/orders/v2/#error-INVALID_ARRAY_MIN_ITEMS',
							'rel'  => 'information_link'
						]
					]
				];
			case 'missing_shipping_address':
				return [
					'name'     => 'UNPROCESSABLE_ENTITY',
					'details'  => [
						[
							'field'       => '/purchase_units/@reference_id==\'PUHF\'/shipping/address',
							'issue'       => 'MISSING_SHIPPING_ADDRESS',
							'description' => 'The shipping address is required when `shipping_preference=SET_PROVIDED_ADDRESS`.'
						]
					],
					'message'  => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
					'debug_id' => 'mock-f200264a4e02a',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/rest/reference/orders/v2/errors/#MISSING_SHIPPING_ADDRESS',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'invalid_parameter_syntax':
				return [
					'name'     => 'INVALID_REQUEST',
					'details'  => [
						[
							'field'       => '/purchase_units/@reference_id==\'Reference ID 2\'/shipping/address',
							'value'       => 'x',
							'location'    => 'body',
							'issue'       => 'INVALID_PARAMETER_SYNTAX',
							'description' => 'The value of a field does not conform to the expected format.'
						]
					],
					'message'  => 'Request is not well-formed, syntactically incorrect, or violates schema.',
					'debug_id' => 'mock-f087ef02ffdb6',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/orders/v2/#error-INVALID_PARAMETER_SYNTAX',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'missing_payment_source':
				return [
					'name'     => 'INVALID_REQUEST',
					'details'  => [
						[
							'field'       => '/payment_source',
							'issue'       => 'MISSING_REQUIRED_PARAMETER',
							'description' => 'A required field or parameter is missing.',
							'location'    => 'body'
						]
					],
					'message'  => 'Request is not well-formed, syntactically incorrect, or violates schema.',
					'debug_id' => 'mock-90957fca61718',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/orders/v2/#error-MISSING_REQUIRED_PARAMETER',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'not_authorized':
				return [
					'name'            => 'NOT_AUTHORIZED',
					'message'         => 'Authorization failed due to insufficient permissions.',
					'debug_id'        => '970e6a10938c5',
					'informationLink' => 'https://developer.paypal.com/docs/api/orders#errors'
				];
			case 'internal_server_error':
				return [
					'name'     => 'INTERNAL_SERVER_ERROR',
					'message'  => 'An internal server error has occurred.',
					'debug_id' => 'mock-360ee42996992',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/orders/v2/#error-INTERNAL_SERVER_ERROR',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'ineligible_for_donations':
				return [
					'name'     => 'NOT_AUTHORIZED',
					'details'  => [
						[
							'issue'       => 'INELIGIBLE_FOR_DONATIONS',
							'description' => 'In order to process \'items.category\' as \'DONATION\', please ensure that Charity confirmation process is completed here - https://www.paypal.com/charities.'
						]
					],
					'message'  => 'Authorization failed due to insufficient permissions.',
					'debug_id' => 'mock-cf9b626b1e1e1',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/docs/api/orders/v2/#error-INELIGIBLE_FOR_DONATIONS',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'permission_denied':
				return [
					'name'     => 'NOT_AUTHORIZED',
					'details'  => [
						[
							'issue'       => 'PERMISSION_DENIED',
							'description' => 'You do not have permission to access or perform operations on this resource.'
						]
					],
					'message'  => 'Authorization failed due to insufficient permissions.',
					'debug_id' => 'mock-f713577394f18',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/rest/reference/orders/v2/errors/#PERMISSION_DENIED',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'resource_not_found':
				return [
					'name'     => 'RESOURCE_NOT_FOUND',
					'message'  => 'The specified resource does not exist.',
					'debug_id' => 'mock-90957fca61718',
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/api/orders/v2/#error-RESOURCE_NOT_FOUND',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			case 'capture_status_not_valid':
				return [
					'name'     => 'UNPROCESSABLE_ENTITY',
					'message'  => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
					'debug_id' => 'mock-360ee42996992',
					'details'  => [
						[
							'issue'       => 'CAPTURE_STATUS_NOT_VALID',
							'description' => 'Invalid capture status. Tracker information can only be added to captures in `COMPLETED` state.'
						]
					],
					'links'    => [
						[
							'href'   => 'https://developer.paypal.com/docs/api/orders/v2/#error-CAPTURE_STATUS_NOT_VALID',
							'rel'    => 'information_link',
							'method' => 'GET'
						]
					]
				];
			default:
				return null;
		}
	}
}
