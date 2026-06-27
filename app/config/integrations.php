<?php

return [
    'guardian' => [
        'wsdl'    => env('GUARDIAN_WSDL', 'http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl'),
        'mock'    => env('GUARDIAN_MOCK', true),
        'timeout' => env('GUARDIAN_TIMEOUT', 10),
    ],

    'protheus' => [
        'base_url' => env('PROTHEUS_BASE_URL', ''),
        'username' => env('PROTHEUS_USERNAME', ''),
        'password' => env('PROTHEUS_PASSWORD', ''),
        'mock'     => env('PROTHEUS_MOCK', true),
        'timeout'  => env('PROTHEUS_TIMEOUT', 15),
    ],
];
