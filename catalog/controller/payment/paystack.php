<?php

namespace Opencart\Catalog\Controller\Extension\Paystack\Payment;

use Opencart\Catalog\Model\Extension\Paystack\Payment\Paystack as PaystackModel;

/**
 * Class Paystack
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
 * @property \Opencart\System\Engine\Proxy&\Opencart\Catalog\Model\Checkout\Order $model_checkout_order
 * @property \Opencart\System\Library\Cart\Currency $currency
 * @property \Opencart\System\Library\Log $log
 * 
 * @package Opencart\Catalog\Controller\Extension\Paystack\Payment
 */
class Paystack extends \Opencart\System\Engine\Controller 
{
	protected const ROUTE_PATH = 'extension/paystack/payment/paystack';

	public function index(): string 
	{
		// load checkout model from — /catalog/model/{checkout/order}.php
		$this->load->model('checkout/order');
		$this->load->language(self::ROUTE_PATH);

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_testmode'] = $this->language->get('text_testmode');
		$data['livemode'] = $this->config->get('payment_paystack_live');

		$data['key'] = $this->config->get('payment_paystack_live') ?
			$this->config->get('payment_paystack_live_public') :
			$this->config->get('payment_paystack_test_public');

		// access the loaded order model (checkout/order) via Proxy property
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$data = array_merge($data, $this->get_payment_currency_rate($order_info));

		$data['ref']      = uniqid('' . $this->session->data['order_id'] . '-');
		$data['email']    = $order_info['email'];
		$data['callback'] = $this->url->link(self::ROUTE_PATH . '.confirm', [
			'trxref' => rawurlencode($data['ref']),
		], true);

		return $this->load->view(self::ROUTE_PATH, $data);
	}

	public function confirm(): void 
	{
		$this->load->language(self::ROUTE_PATH);
		$responseContext = $this->get_confirmation_response();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($responseContext));
	}

	private function get_confirmation_response(): array
	{
		$redirect_params = [
			'language' => $this->config->get('config_language'),
		];

		if (empty($this->request->get['trxref'])) {
			return [
				'error' =>  $this->language->get('error_payment_reference')
			];
		}

		/** @var string Transaction reference ID*/
		$trxref = $this->request->get['trxref'];

		/** @var string $order_id The ID that comes before the first dash in $trxref */
		$order_id = explode('-', $trxref, 2)[0] ?? 0;

		// if no dash were in transation reference, we will have an empty order_id
		if (!$order_id) {
			return [
				'error' => $this->language->get('error_order_id')
			];
		}

		// load checkout model
		$this->load->model('checkout/order');
		
		// access the model
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return [
				'redirect' => $this->url->link('checkout/failure', $redirect_params, true)
			];
		}
		
		if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'paystack.paystack') {
			return [
				'error' => $this->language->get('error_payment_method')
			];
		}

		if ($this->config->get('payment_paystack_debug')) {
			$this->log->write('PAYSTACK :: CALLBACK DATA: ' . print_r($this->request->get, true));
		}
		
		// Callback paystack to get real transaction status
		$api_response = $this->query_api_transaction_verify($trxref);

		switch($api_response['data']['status'] ?? null) {
			case 'success':
				//PSTK Logger
				$paystack_key = $this->config->get('payment_paystack_live') ?
					$this->config->get('payment_paystack_live_public') :
					$this->config->get('payment_paystack_test_public');
				
				$this->log_transaction_success('opencart-4.x', $paystack_key, $trxref);

				$order_status_id = $this->config->get('payment_paystack_order_status_id');
				$comment = $this->language->get('comment_payment_success');
				$redirect_url = $this->url->link('checkout/success', $redirect_params, true);
				
				break;

			case 'failed':
				$order_status_id = $this->config->get('payment_paystack_declined_status_id');
				$comment = $this->language->get('comment_payment_failed');
				$redirect_url = $this->url->link('checkout/checkout', $redirect_params, true);

				break;

			default:
				$order_status_id = $this->config->get('payment_paystack_canceled_status_id');
				$comment = $this->language->get('comment_payment_canceled');
				$redirect_url = $this->url->link('checkout/checkout', $redirect_params, true);	
		} 
		
		$this->model_checkout_order->addHistory($order_id, $order_status_id, $comment);
		
		return ['redirect' => $redirect_url];
	}

	private function get_payment_currency_rate(array $order_info): array
	{
		// Currency selected by customer in the store
		$selected_currency = strtoupper($this->session->data['currency']) ?? $this->config->get('config_currency');
		
		$data['currency'] = $order_info['currency_code'];
		$data['amount']   = $order_info['total'];

		// If customer selected an allowed currency e.g (NGN, GHS), s
		// but the items rate were calculated in a different currency e.g USD

		if (in_array($selected_currency, PaystackModel::SUPPORTED_CURRENCIES) && $data['currency'] !== $selected_currency) {
			// Convert the total cost (rate) to the currency selected by the user
			$data['amount'] = $this->currency->convert($data['amount'], $data['currency'], $selected_currency);
			$data['currency'] = $selected_currency;
		}

		$data['amount'] = (int) ($data['amount'] * 100); // convert it to the smallest unit e.g kobo

		return $data;
	}

	private function query_api_transaction_verify($reference): array 
	{
		$secret_key = $this->config->get('payment_paystack_live') ?
			$this->config->get('payment_paystack_live_secret') :
			$this->config->get('payment_paystack_test_secret');

		$context = stream_context_create(
			[
				'http' => [
					'method'     => "GET",
					'header'     => "Authorization: Bearer " . $secret_key,
					'user-agent' => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
				]
			]
		);

		$url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);

		$request = file_get_contents($url, false, $context);

		return json_decode($request, true);
	}

	private function log_transaction_success(string $plugin_name, string $public_key, string $trxref): void 
	{
		//send reference to logger along with plugin name and public key
		$url = "https://plugin-tracker.paystackintegrations.com/log/charge_success";

		$fields = [
			'plugin_name'           => $plugin_name,
			'transaction_reference' => $trxref,
			'public_key'            => $public_key
		];

		$fields_string = http_build_query($fields);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//execute post
		$result = curl_exec($ch);
		//  echo $result;
	}
}
