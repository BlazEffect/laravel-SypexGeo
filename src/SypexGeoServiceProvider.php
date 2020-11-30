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
			$sypexConfigType = $sypexConfig->get('sxgeo.sxgeo.type', []);
			$sypexConfigPath = $sypexConfig->get('sxgeo.sxgeo.path', []);
            $defaultIsoCountryCode = $sypexConfig->get('sxgeo.default_iso_country_code', []);

			switch ($sypexConfigType) {
				case 'database':
				default:
					$sypexConfigFile = $sypexConfig->get('sxgeo.sxgeo.file', []);
                    $sxgeo = new SxGeo(base_path() . $sypexConfigPath . $sypexConfigFile);
                    $sxgeo->setDefaultIsoCountryCode($defaultIsoCountryCode);
					break;

				case 'web_service':
					$license_key = $sypexConfig->get('sxgeo.sxgeo.license_key', []);
					$sxgeo = new SxGeoHttp($license_key);
					break;
			}
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
