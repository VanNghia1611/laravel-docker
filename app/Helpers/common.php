<?php

use Ramsey\Uuid\Uuid;

if (!function_exists('generateToken')) {

    /**
     * Generate token
     *
     * @return string
     */
    function generateToken()
    {
        return hash_hmac('sha256', str_random(40), config('app.key'));
    }
}

//Generate uuid
if (!function_exists('generateUuid')) {

    /**
     * Generate string unique
     *
     * @param int $level the uuid level
     *
     * @return string the uuid string
     */
    function generateUuid($level = 4)
    {
        switch ($level) {
            case 1:
                $uuid = Uuid::uuid1();
                break;
            case 3:
                $uuid = Uuid::uuid3(Uuid::NAMESPACE_DNS, 'php.net');
                break;
            case 5:
                $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');
                break;
            default:
                $uuid = Uuid::uuid4();
                break;
        }

        return $uuid->toString();
    }
}

if (!function_exists('validateKeyWords')) {

    /**
     * This method to validate the keywords when user input
     *
     * @param string $keyWords Key words
     *
     * @return bool|mixed
     */
    function validateKeyWords($keyWords)
    {
        $key = preg_replace('/[;,"\[\].:{}]/', '', $keyWords);
        if (empty($key)) {
            return false;
        }
        return $key;
    }
}