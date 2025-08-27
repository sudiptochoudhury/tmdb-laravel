<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
return [
    /*
     * Api key
     */
    'api_key' => '',
    // 'cache' => true,
    'cache' => [
        'defaultTtl' => null,
        'enabled' => true,
    ],
    'client' => [
        'secure' => true,
    ],
];
