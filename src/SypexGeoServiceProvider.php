<?php

namespace Scriptixru\SypexGeo;

use Illuminate\Support\ServiceProvider;

class SypexGeoServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/sxgeo.php' => config_path('sxgeo.php'),
		], 'sxgeo-config');
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register providers.
		$this->app->singleton('sxgeo', function ($app) {
			$sypexConfig = $app['config'];
			$sypexConfigPath = $sypexConfig->get('sxgeo.sxgeo.path', []);
            $defaultIsoCountryCode = $sypexConfig->get('sxgeo.default_iso_country_code', []);

            $sypexConfigFile = $sypexConfig->get('sxgeo.sxgeo.file', []);
            $sxgeo = new SxGeo(base_path() . $sypexConfigPath . $sypexConfigFile);
            $sxgeo->setDefaultIsoCountryCode($defaultIsoCountryCode);
            $sxgeo->setIgnoredIp($sypexConfig->get('sxgeo.ignored_ip', []));

			return new SypexGeo($sxgeo, $app['config']);
		});
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['sxgeo'];
	}
}
