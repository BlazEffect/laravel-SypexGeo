# SypexGeo for Laravel 12

----------

The data comes from a database


## Installation

- [SypexGeo for Laravel 5 on Packagist](https://packagist.org/packages/scriptixru/sypexgeo)
- [SypexGeo for Laravel 5 on GitHub](https://github.com/scriptixru/sypexgeo)
- [SypexGeo for Laravel 7 on GitHub](https://github.com/default-089/sxgeo)
- [SypexGeo for Laravel 7 on Packagist](https://packagist.org/packages/default-089/sxgeo)
- [SypexGeo for Laravel 12 on GitHub](https://github.com/BlazEffect/laravel-SypexGeo)

To get the latest version of SypexGeo simply require it in your `composer.json` file.

~~~
"blazeffect/laravel-sypexgeo": "1.0.0"
~~~

You'll then need to run `composer install` to download it and have the autoloader updated.

The package will automatically register a service provider.

### Publish the configurations

Run this on the command line from the root of your project:

~~~
$ php artisan vendor:publish --tag=sxgeo-config
~~~

A configuration file will be publish to `config/sxgeo.php`


## Usage


Getting the location data for a given IP:

```php
$sxgeo = app('sxgeo');
$location = $sxgeo->get('232.223.11.11');
```

### Example Data

If data is received from the database - config/sxgeo.php
```php
        [
            'city' => [
                'id' => 524901,
                'lat' => 55.75222,
                'lon' => 37.61556,
                'name_ru' => 'Москва',
                'name_en' => 'Moscow',
                'okato' => '45',
            ],
            'region' => [
                'id' => 524894,
                'lat' => 55.76,
                'lon' => 37.61,
                'name_ru' => 'Москва',
                'name_en' => 'Moskva',
                'iso' => 'RU-MOW',
                'timezone' => 'Europe/Moscow',
                'okato' => '45',
            ],
            'country' => [
                'id' => 185,
                'iso' => 'RU',
                'continent' => 'EU',
                'lat' => 60,
                'lon' => 100,
                'name_ru' => 'Россия',
                'name_en' => 'Russia',
                'timezone' => 'Europe/Moscow',
            ],
        ];
```

#### Default Location

In the case that a location is not found the fallback location will be returned with the `default` parameter set to `true`. To set your own default change it in the configurations `config/geoip.php`





