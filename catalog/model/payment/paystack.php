<?php
namespace Opencart\Catalog\Model\Extension\Paystack\Payment;
/**
 * Class Paystack
 *
 * Can be called from $this->load->model('extension/opencart/payment/paystack');
 *
 * @property \Opencart\System\Engine\Loader $load
 * @property \Opencart\System\Library\Document $document
 * @property \Opencart\System\Library\Language $language
 * @property \Opencart\System\Library\Cart\User $user
 * @property \Opencart\System\Library\Request $request
 * @property \Opencart\System\Library\Session $session
 * @property \Opencart\System\Engine\Config $config
 * @property \Opencart\System\Library\Url $url
 * @property \Opencart\System\Library\Response $response
 * @property \Opencart\System\Library\Cart\Cart $cart
 * @property \Opencart\System\Library\DB $db
 * @property \Opencart\Catalog\Model\Localisation\GeoZone $model_localisation_geo_zone
 * @property \Opencart\System\Library\Cart\Currency $currency
 * 
 * @package Opencart\Catalog\Model\Extension\Paystack\Payment
 */
class Paystack extends \Opencart\System\Engine\Model 
{
	public const SUPPORTED_CURRENCIES = [
		'NGN',
		'GHS',
		'USD',
		'ZAR',
		'KES',
	];

	protected const ROUTE_PATH = 'extension/paystack/payment/paystack';

	/**
	 * @param array $address The customer's billing address, if collected by the store. 
	 * 						 Address may be empty if billing address collection is disabled or not yet provided.
	 * @return array An array containing the payment method details if available
	 */
	public function getMethods(array $address = []): array
	{
		$this->load->language(self::ROUTE_PATH);

		$method_data = [];

		/** @var string */
		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		$isAllowedCurrency = in_array(strtoupper($currency), self::SUPPORTED_CURRENCIES);

		$status = $isAllowedCurrency && $this->isConditionValid($address);

		if ($status) {
			$option_data['paystack'] = [
				'code' => 'paystack.paystack',
				'name' => $this->language->get('heading_title')
			];

			$method_data = [
				'code'       => 'paystack',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_paystack_sort_order')
			];
		}

		return $method_data;
	}

	public function isConditionValid(array $address): bool
	{
		// If paystack is disabled, well - it is what is it
		if (empty($this->config->get('payment_paystack_status'))) {
			return false;
		};

		// Determine if paystack is on live or test mode
		$requiredApiKey = empty($this->config->get('payment_paystack_live')) ? 'payment_paystack_test_%s' : 'payment_paystack_live_%s';

		// Obtain the public & secret key
		$publicKey = $this->config->get(sprintf($requiredApiKey, 'public'));
		$secretKey = $this->config->get(sprintf($requiredApiKey, 'secret'));

		// If any of the required key is not set, disable the payment
		if (empty($publicKey) || empty($secretKey)) {
			return false;
		}

		// Paystack does not support opencart-style recursive payment. Therefore:
		// If the customer’s cart contains a subscription-based product, disable the payment
		if ($this->cart->hasSubscription()) {
			return false;
		}

		// Get the minimum amount an order must have for payment to be enabled
		$minTotal = $this->config->get('payment_paystack_total');

		// If the total amount in cart is below the minimum amount an order must have, disable the payment
		if (!empty($minTotal) && (float)$minTotal > 0 && $this->cart->getTotal() < (float)$minTotal) {
			return false;
		}

		// If the store is not requiring a payment address during checkout, enable the payment
		// if (!$this->config->get('config_checkout_payment_address')) {
		// 	return true;
		// } 
		
		// If specific Geo Zone is configured for the payment method, verify the zone
		if ($this->config->get('payment_paystack_geo_zone_id')) {
			// Geo Zone
			$this->load->model('localisation/geo_zone');

			$results = $this->model_localisation_geo_zone->getGeoZone(
				(int)$this->config->get('payment_paystack_geo_zone_id'), 
				(int)$address['country_id'], 
				(int)$address['zone_id']
			);

			return !!$results;
		} 

		return true;
	}
}
