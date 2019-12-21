<?php

namespace App\Libraries;

class CreditLib
{
    /**
     * UploadImage constructor.
     */
    public function __construct()
    {
        $this->storageImage = \Storage::disk('public');
        $this->imageConfig = config('image_config');
    }
}
