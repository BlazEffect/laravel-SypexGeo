<?php

namespace Scriptixru\SypexGeo;

use Illuminate\Config\Repository;

class SypexGeo
{
    /**
     * @var \SxGeo instance.
     */
    private $_sypex = null;

    /**
     * Illuminate config repository instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var string $ip remote client IP-address
     */
    public $ip = '';
    /**
     * @var int $ipAsLong remote client IP-address as integer value
     */
    public $ipAsLong = 0;
    /**
     * @var array $city geo information about city
     */
    public $city = [];
    /**
     * @var array $region geo information about region
     */
    public $region = [];
    /**
     * @var array $country geo information about country
     */
    public $country = [];

    /**
     * Get full geo info by remote IP-address
     * @param string $ip source ip, if empty then determine
     * @return array geo info|false if error
     * result array example:
     *  ```php
     *    [
     *       'city' => [
     *           'id' => 709717,
     *           'lat' => 48.023000000000003,
     *           'lon' => 37.802239999999998,
     *           'name_ru' => 'Донецк',
     *           'name_en' => 'Donets\'k',
     *           'okato' => '14101',
     *       ],
     *       'region' => [
     *           'id' => 709716,
     *           'lat' => 48,
     *           'lon' => 37.5,
     *           'name_ru' => 'Донецкая область',
     *           'name_en' => 'Donets\'ka Oblast\'',
     *           'iso' => 'UA-14',
     *           'timezone' => 'Europe/Zaporozhye',
     *           'okato' => '14',
     *       ],
     *       'country' => [
     *           'id' => 222,
     *           'iso' => 'UA',
     *           'continent' => 'EU',
     *           'lat' => 49,
     *           'lon' => 32,
     *           'name_ru' => 'Украина',
     *           'name_en' => 'Ukraine',
     *           'timezone' => 'Europe/Kiev',
     *       ],
     *   ]
     *  ```
     */
    public function __construct($object, Repository $config)
    {
        // $this->config  = $config;
        $this->_sypex = $object;
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments[0]))
            $arguments[0] = $this->getIP();
        else if (!filter_var($arguments[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        } else {
            $this->ip = $arguments[0];
            $this->ipAsLong = sprintf('%u', ip2long($arguments[0]));
        }

        if (method_exists($this->_sypex, $name)) {
            $data = $this->_sypex->$name(...$arguments);
        } else {
            throw new \Exception("Вызван несуществующий метод: $name");
        }

        if (isset($data['city']))
            $this->city = $data['city'];
        if (isset($data['region']))
            $this->region = $data['region'];
        if (isset($data['country']))
            $this->country = $data['country'];

        // return empty($data) ? $this->config->get('sxgeo.default_location', array()) : $data;
        return empty($data) ? null : $data;
    }
    /**
     * Detect client IP address
     * @return string IP
     */
    public function getIP()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ip = getenv('HTTP_CLIENT_IP');
        elseif (getenv('HTTP_X_FORWARDED_FOR'))
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        elseif (getenv('HTTP_X_FORWARDED'))
            $ip = getenv('HTTP_X_FORWARDED');
        elseif (getenv('HTTP_FORWARDED_FOR'))
            $ip = getenv('HTTP_FORWARDED_FOR');
        elseif (getenv('HTTP_FORWARDED'))
            $ip = getenv('HTTP_FORWARDED');
        else
            $ip = getenv('REMOTE_ADDR');

        $this->ip = $ip;
        $this->ipAsLong = sprintf('%u', ip2long($ip));
        return $ip;
    }
}
