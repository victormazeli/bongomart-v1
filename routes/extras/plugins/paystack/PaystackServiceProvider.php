<?php

namespace extras\plugins\paystack;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Route;

class PaystackServiceProvider extends ServiceProvider
{
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('paystack', function ($app) {
			return new Paystack($app);
		});
	}
	
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Load plugin views
        $this->loadViewsFrom(realpath(__DIR__ . '/resources/views'), 'payment');

        // Load plugin languages files
		$this->loadTranslationsFrom(realpath(__DIR__ . '/resources/lang'), 'paystack');

        // Merge plugin config
        $this->mergeConfigFrom(realpath(__DIR__ . '/config.php'), 'payment');
        
        $this->setConfigVars();
    }
	
	/**
	 * Update the config vars
	 */
	private function setConfigVars()
	{
		config()->set('paystack', config('payment.paystack'));
	}
}
