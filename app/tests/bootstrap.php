<?php

/**
 * PHPUnit's <php><env force="true"> only writes to $_ENV/putenv(), never
 * $_SERVER — but Laravel's Env repository checks $_SERVER first. Inside the
 * app container, APP_ENV=local (and the dev DB_DATABASE) already come from
 * app/.env via docker-compose's env_file, landing in $_SERVER before PHPUnit
 * even runs, so force="true" silently loses. Setting $_SERVER here, before
 * autoload, is what actually wins.
 *
 * DB_HOST/DB_PORT are deliberately NOT forced here: inside the container they
 * must stay postgres:5432 (from the real env), on the host they fall back to
 * phpunit.xml's 127.0.0.1:5433 (docker-compose.override.yml port mapping).
 */
$forced = [
    'APP_ENV'                 => 'testing',
    'APP_MAINTENANCE_DRIVER'  => 'file',
    'BCRYPT_ROUNDS'           => '4',
    'BROADCAST_CONNECTION'    => 'null',
    'CACHE_STORE'             => 'array',
    'DB_CONNECTION'           => 'pgsql',
    'DB_DATABASE'             => 'carregamento_test',
    'DB_USERNAME'             => 'postgres',
    'DB_PASSWORD'             => '123456',
    'MAIL_MAILER'             => 'array',
    'QUEUE_CONNECTION'        => 'sync',
    'SESSION_DRIVER'          => 'array',
    'GUARDIAN_MOCK'           => 'true',
    'PROTHEUS_MOCK'           => 'true',
    'PULSE_ENABLED'           => 'false',
    'TELESCOPE_ENABLED'       => 'false',
    'NIGHTWATCH_ENABLED'      => 'false',
];

foreach ($forced as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key]    = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
