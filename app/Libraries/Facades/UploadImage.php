<?php

namespace App\Libraries\Facades;

use Illuminate\Support\Facades\Facade;

class UploadImage extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'UploadImage';
    }
}
