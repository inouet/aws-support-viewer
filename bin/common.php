<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Get AWS SupportClient
 *
 * @param $profile_name
 *
 * @return \Aws\Support\SupportClient
 */
function get_client($profile_name)
{
    static $client;
    if (!$client) {
        $client = new Aws\Support\SupportClient(
            [
                'version' => 'latest',
                'region'  => 'us-east-1',
                'profile' => $profile_name,
            ]
        );
    }
    return $client;
}

/**
 * Generate Communication Id from communication data
 *
 * @param array $communication
 *
 * @return string
 */
function generate_communication_id($communication)
{
    $source = $communication['caseId'] . $communication['timeCreated'] . $communication['submittedBy'];
    $id     = sha1($source);
    return $id;
}


/**
 * Get Logger interface
 *
 * @return Logger
 */
function get_logger()
{
    global $argv;

    static $logger;
    if (!$logger) {
        $file   = basename($argv[0], '.php') . '.log';
        $logger = new Logger('support');
        $logger->pushHandler(new StreamHandler(LOG_DIR . '/' . $file, Logger::DEBUG));
    }
    return $logger;
}
