<?php

namespace Opencart\Admin\Controller\Extension\Opencart\Payment;

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
 * @property \Opencart\System\Engine\Proxy&\Opencart\Admin\Model\Setting\Setting $model_setting_setting
 * 
 * @package Opencart\Admin\Controller\Extension\Opencart\Payment;
 */
class Paystack extends \Opencart\System\Engine\Controller 
{
	protected const ROUTE_PATH = 'extension/opencart/payment/paystack';

	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * Handles the module settings page display.
	 *
	 * This method is called when the administrator clicks the "Edit" button 
	 * for the Paystack payment extension in the admin panel.
	 *
	 * @return void
	 */
	public function index(): void 
	{
		$this->load->language(self::ROUTE_PATH);
		
		$this->document->setTitle($this->language->get('heading_title'));

		// load setting model from — /admin/model/{setting/setting}.php
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {

			// access the loaded model (setting/setting) via Proxy property
			$this->model_setting_setting->editSetting('payment_paystack', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->getAdminUrl(self::ROUTE_PATH));

			return;
		}

		$view = $this->load->view(self::ROUTE_PATH, $this->createViewData());

		$this->response->setOutput($view);
	}

	private function createViewData(): array
	{
		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_keys'] = $this->error['keys'] ?? '';

		$data['action'] = $this->getAdminUrl(self::ROUTE_PATH);
		$data['cancel'] = $this->getAdminUrl('marketplace/extension', ['type' => 'payment']);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),	
				'href' => $this->getAdminUrl('common/dashboard'),
			],
			[
				'text' => $this->language->get('text_payment'),
				'href' => $this->getAdminUrl('marketplace/extension', ['type' => 'payment']),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->getAdminUrl(self::ROUTE_PATH),
			]
		];

		$data = array_merge($data, $this->setConfigurationData([
			'payment_paystack_live_secret',
			'payment_paystack_live_public',
			'payment_paystack_test_secret',
			'payment_paystack_test_public',
			'payment_paystack_live',
			'payment_paystack_debug',
			'payment_paystack_total',
			'payment_paystack_order_status_id',
			'payment_paystack_pending_status_id',
			'payment_paystack_canceled_status_id',
			'payment_paystack_failed_status_id',
		]));

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data = array_merge($data, $this->setConfigurationData([
			'payment_paystack_geo_zone_id',
		]));

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data = array_merge($data, $this->setConfigurationData([
			'payment_paystack_status',
			'payment_paystack_sort_order',
		]));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		return $data;
	}

	private function setConfigurationData(array $configKeys): array
	{
		foreach ($configKeys as $key) {
			$data[$key] = $this->request->post[$key] ?? $this->config->get($key);
		}

		return $data;
	}

	private function getAdminUrl(string $route, array $parameters = []): string
	{
		return $this->url->link($route, array_merge($parameters, [
			'user_token' => $this->session->data['user_token']
		]), true);
	}

	/**
	 * @param string $value The API key
	 * @param string $mode live or test
	 * @param string $access secret or private
	 * @return bool
	 */
	private function isValidKey(string $value, string $mode, string $access): bool
	{
		return substr_compare($value, (substr($access, 0, 1)) . 'k_' . $mode . '_', 0, 8, true) === 0;
	}

	private function validate(): bool
	{
		if (!$this->user->hasPermission('modify', self::ROUTE_PATH)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$live_secret = $this->request->post['payment_paystack_live_secret'];
		$live_public = $this->request->post['payment_paystack_live_public'];
		$test_secret = $this->request->post['payment_paystack_test_secret'];
		$test_public = $this->request->post['payment_paystack_test_public'];

		if ($this->request->post['payment_paystack_live'] && (!$this->isValidKey($live_secret, 'live', 'secret') || !$this->isValidKey($live_public, 'live', 'public'))) {
			$this->error['keys'] = $this->language->get('error_live_keys');
		}

		if (!$this->request->post['payment_paystack_live'] && (!$this->isValidKey($test_secret, 'test', 'secret') || !$this->isValidKey($test_public, 'test', 'public'))) {
			$this->error['keys'] = $this->language->get('error_test_keys');
		}

		return !$this->error;
	}
}
