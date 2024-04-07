<?php

namespace Scriptixru\SypexGeo;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getCountry(...$arguments) get iso country code by ip
 */
class SypexGeoFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'sxgeo';
    }
}
