<?php

namespace extras\plugins\paystack;

use App\Helpers\Number;
use App\Models\Post;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Helpers\Payment;
use App\Models\Package;
use Unicodeveloper\Paystack\Facades\Paystack as PaystackFacade;

class Paystack extends Payment
{
	/**
	 * Send Payment
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Post $post
	 * @param array $resData
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 * @throws \Exception
	 */
	public static function sendPayment(Request $request, Post $post, $resData = [])
	{
		// Set the right URLs
		parent::setRightUrls($resData);
		
		// Get the Package
		$package = Package::find($request->input('package_id'));
		
		// Don't make a payment if 'price' = 0 or null
		if (empty($package) || $package->price <= 0) {
			return redirect(parent::$uri['previousUrl'] . '?error=package')->withInput();
		}
		
		// Apply actions when Payment failed
		$supportedCurrencies = preg_split('#[:,;\s]+#ui', config('payment.paystack.currencies'));
		$supportedCurrencies = array_filter(array_map('trim', $supportedCurrencies));
		if (!in_array(strtoupper($package->currency_code), $supportedCurrencies)) {
			$errorMessage = trans('paystack::messages.currency_support_error', ['currencies' => implode(', ', $supportedCurrencies)]);
			
			return parent::paymentFailureActions($post, $errorMessage);
		}
		
		// Get the amount
		$amount = Number::toFloat($package->price);
		$amount = self::getAmount($amount, $package->currency_code);
		
		// Get first name & last name
		$firstName = $lastName = '';
		if (isset($post->contact_name)) {
			$tmp = splitName($post->contact_name);
			$firstName = $tmp['firstName'];
			$lastName = $tmp['lastName'];
		}
		
		// Generate the Transaction Reference
		$transRef = '';
		try {
			$transRef = PaystackFacade::genTranxRef();
		} catch (\Throwable $e) {
		}
		
		// API Parameters
		$providerParams = [
			'key'          => config('paystack.secretKey'),            // required
			"email"        => isset($post->email) ? $post->email : '', // required
			'amount'       => $amount,                                 // required in kobo (1 NGN = 100 kobos)
			'reference'    => $transRef,           					   // required - 'ref' is Not required
			'currency'     => $package->currency_code,
			'callback_url' => parent::$uri['paymentReturnUrl'],
			"plan"         => '',
			"first_name"   => $firstName,
			"last_name"    => $lastName,
			'metadata'     => [],
		];
		
		// Local Parameters
		$localParams = [
			'payment_method_id' => $request->input('payment_method_id'),
			'post_id'           => $post->id,
			'package_id'        => $package->id,
		];
		$localParams = array_merge($localParams, $providerParams);
		
		// Try to make the Payment
		try {
			
			// Save local parameters into session
			session()->put('params', $localParams);
			session()->save(); // If redirection to an external URL will be done using PHP header() function
			
			// Update the request values
			$request->merge($providerParams);
			request()->replace($providerParams);
			
			// Make the payment
			// Redirect the client to the Paystack payment summary page
			redirectUrl(PaystackFacade::getAuthorizationUrl()->url);
			
		} catch (\Throwable $e) {
			
			// Apply actions when API failed
			return parent::paymentApiErrorActions($post, $e);
			
		}
	}
	
	/**
	 * @param $params
	 * @param $post
	 * @return $this|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 * @throws \Exception
	 */
	public static function paymentConfirmation($params, $post)
	{
		// Set form page URL
		parent::$uri['previousUrl'] = str_replace(['#entryToken', '#entryId'], [$post->tmp_token, $post->id], parent::$uri['previousUrl']);
		parent::$uri['nextUrl'] = str_replace(['#entryToken', '#entryId', '#entrySlug'], [$post->tmp_token, $post->id, $post->slug], parent::$uri['nextUrl']);
		
		// Get the Payment information
		try {
			$paymentDetails = PaystackFacade::getPaymentData();
			
			// Check the Payment
			if (
				isset($paymentDetails['status'], $paymentDetails['data'], $paymentDetails['data']['status'])
				&& $paymentDetails['status'] == true
				&& $paymentDetails['data']['status'] === 'success'
			) {
				
				// Save the Transaction ID at the Provider
				if (isset($paymentDetails['data']['id'])) {
					$params['transaction_id'] = $paymentDetails['data']['id'];
				}
				
				// Apply actions after successful Payment
				return parent::paymentConfirmationActions($params, $post);
				
			} else {
				
				// Apply actions when Payment failed
				return parent::paymentFailureActions($post);
				
			}
		} catch (\Throwable $e) {
			
			// Apply actions when API failed
			return parent::paymentApiErrorActions($post, $e);
			
		}
	}
	
	/**
	 * Amount's specificity for Paystack
	 *
	 * NOTE: Always send a kobo value for amount
	 * e.g. to accept 120 Naira 50 Kobo, send 12050 as the amount.
	 *
	 * More information: https://developers.paystack.co/docs/transaction-parameters
	 *
	 * @param $amount
	 * @param $currencyCode
	 * @return int
	 */
	private static function getAmount($amount, $currencyCode)
	{
		$exceptCurrencies = ['HUF'];
		
		if (!in_array($currencyCode, $exceptCurrencies)) {
			$amount = intval((float)$amount * 100);
		}
		
		return $amount;
	}
	
	/**
	 * @return array
	 */
	public static function getOptions()
	{
		$options = [];
		
		$paymentMethod = PaymentMethod::active()->where('name', 'paystack')->first();
		if (!empty($paymentMethod)) {
			$options[] = (object)[
				'name'     => mb_ucfirst(trans('admin.settings')),
				'url'      => admin_url('payment_methods/' . $paymentMethod->id . '/edit'),
				'btnClass' => 'btn-info',
			];
		}
		
		return $options;
	}
	
	/**
	 * @return bool
	 */
	public static function installed()
	{
		$paymentMethod = PaymentMethod::active()->where('name', 'paystack')->first();
		if (empty($paymentMethod)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @return bool
	 */
	public static function install()
	{
		// Remove the plugin entry
		self::uninstall();
		
		// Plugin data
		$data = [
			'id'                => 6,
			'name'              => 'paystack',
			'display_name'      => 'Paystack',
			'description'       => 'Payment with Paystack',
			'has_ccbox'         => 0,
			'is_compatible_api' => 0,
			'lft'               => 0,
			'rgt'               => 0,
			'depth'             => 1,
			'active'            => 1,
		];
		
		try {
			// Create plugin data
			$paymentMethod = PaymentMethod::create($data);
			if (empty($paymentMethod)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @return bool
	 */
	public static function uninstall()
	{
		$paymentMethod = PaymentMethod::where('name', 'paystack')->first();
		if (!empty($paymentMethod)) {
			$deleted = $paymentMethod->delete();
			if ($deleted > 0) {
				return true;
			}
		}
		
		return false;
	}
}
