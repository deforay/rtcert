<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
    'db' => array(
        'driver'         => 'Pdo',
        'dsn'            => 'mysql:dbname=rtcert;host=localhost',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
        'platform_options' => array('quote_identifiers' => false),
    ),
    'module_layouts' => array(
        'Application' => 'layout/layout',
        'Application' => 'layout/modal'
    ),
    'service_manager' => array(
        'factories' => array(
            'Laminas\Db\Adapter\Adapter'
            => 'Laminas\Db\Adapter\AdapterServiceFactory',
        ),
        // to allow other adapter to be called by
        // $sm->get('db1') or $sm->get('db2') based on the adapters config.
        'abstract_factories' => array(
            'Laminas\Db\Adapter\AdapterAbstractServiceFactory',
        ),
    ),
);
