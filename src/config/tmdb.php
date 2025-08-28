<?php

return [
    /*
     * Api key
     */
    'api_key' => '',

    /*
     * Enable Cache
     */
    // 'cache' => true,
    'cache' => [
        'enabled' => true,
        'defaultTtl' => null,
    ],
    /**
     * Client options
     */
    'client' => [
        /**
         * Use https
         */
        'secure' => true,
    ],
];
