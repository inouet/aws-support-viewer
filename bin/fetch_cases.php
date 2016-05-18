#!/usr/bin/env php
<?php
/**
 * fetch_cases.php
 *
 * Fetch support cases from AWS API
 *
 * Usage:
 *
 *  $ fetch_cases.php --profile default1,default2 --status open
 *  $ fetch_cases.php --profile default1,default2 --status all --days 100
 *
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/common.php';

use Ulrichsg\Getopt\Argument;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

/*
|-----------------------------------------------------------------------------------------------------------------------
| options
|-----------------------------------------------------------------------------------------------------------------------
*/

function get_options()
{
    $options   = [];
    $options[] = (new Option(null, 'status', Getopt::REQUIRED_ARGUMENT))->setArgument(
        new Argument('open', 'is_string')
    );
    $options[] = (new Option(null, 'profile', Getopt::REQUIRED_ARGUMENT));
    $options[] = (new Option(null, 'days', Getopt::OPTIONAL_ARGUMENT))->setArgument(new Argument(365, 'is_numeric'));
    $getopt    = new Getopt($options);

    try {
        $getopt->parse();
        $options = $getopt->getOptions();

        if (!isset($options['profile']) || !isset($options['status'])) {
            echo 'Error: Option profile and status is required';
            echo $getopt->getHelpText();
            exit(1);
        }
        return $options;
    } catch (UnexpectedValueException $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
        echo $getopt->getHelpText();
        exit(1);
    }
}

/*
|-----------------------------------------------------------------------------------------------------------------------
| cases
|-----------------------------------------------------------------------------------------------------------------------
*/

/**
 * Retrieve Support Cases
 *
 * @param  string $profile_name
 * @param  string $lang   [ ja | en ]
 * @param  string $status [ all | open ]
 * @param int     $days
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-support-2013-04-15.html#describecases
 */
function fetch_cases($profile_name, $lang, $status = 'all', $days = 90)
{
    $logger = get_logger();
    $logger->info("START fetch_cases", [$profile_name, $lang, $status, $days]);

    $client = get_client($profile_name);

    $params = [
        'includeCommunications' => false,
        'includeResolvedCases'  => true,
        'language'              => $lang
    ];

    if ($status == 'all') {
        $after_time                     = date(DATE_ISO8601, time() - 60 * 60 * 24 * $days);
        $params['includeResolvedCases'] = true;
        $params['afterTime']            = $after_time;
    } else {
        $params['includeResolvedCases'] = false;
    }

    $results = $client->getPaginator('DescribeCases', $params);

    foreach ($results as $result) {

        foreach ($result['cases'] as $case) {
            $logger->info("fetch_case", [$case['caseId'], $case['timeCreated'], $case['subject']]);
            save_case($case);
            fetch_communications($profile_name, $case['caseId']);
        }
    }
    $logger->info("FINISH fetch_cases");
}

/**
 * Save Support Case
 *
 * @param array $case
 *
 * @return bool
 */
function save_case($case)
{
    $logger = get_logger();

    $case_id = $case['caseId'];
    $dir     = sprintf(DATA_DIR . '/cases/%s', $case_id);

    $file = $dir . '/' . $case_id . '.json';
    $data = json_encode($case);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (file_exists($file) && md5_file($file) === md5($data)) {
        // Already exists and no differ
        return true;
    }

    $logger->info("save case: {$file}");
    file_put_contents($file, $data);

    // remove done file
    $done_file = $file . '.done';
    if (file_exists($done_file)) {
        unlink($done_file);
    }
    return true;
}

/*
|-----------------------------------------------------------------------------------------------------------------------
| communications
|-----------------------------------------------------------------------------------------------------------------------
*/

/**
 * Retrieve Communications
 *
 * @param string $profile_name
 * @param string $case_id
 */
function fetch_communications($profile_name, $case_id)
{
    $client = get_client($profile_name);

    $params = [
        'caseId' => $case_id,
    ];

    $results = $client->getPaginator('DescribeCommunications', $params);
    foreach ($results as $result) {
        foreach ($result['communications'] as $communication) {
            save_communication($communication);

            // 添付ファイル取得
            if ($communication['attachmentSet']) {
                fetch_attachments($profile_name, $communication['attachmentSet']);
            }
        }
    }
}

/**
 * @param array $communication
 *
 * @return bool
 */
function save_communication($communication)
{
    $logger  = get_logger();
    $case_id = $communication['caseId'];

    $communication_id = generate_communication_id($communication);

    $dir  = sprintf(DATA_DIR . '/cases/%s', $case_id);
    $file = $dir . '/comm-' . $communication_id . '.json';
    $data = json_encode($communication);

    if (file_exists($file) && md5_file($file) === md5($data)) {
        return true;
    }

    file_put_contents($file, $data);
    $logger->info("save communication: {$file}");

    $done_file = $file . '.done';
    if (file_exists($done_file)) {
        unlink($done_file);
    }

    return true;
}

/*
|-----------------------------------------------------------------------------------------------------------------------
| attachments
|-----------------------------------------------------------------------------------------------------------------------
*/

/**
 * Retrieve Attachments
 *
 * @param string $aws_profile_name
 * @param array  $attachment_set
 */
function fetch_attachments($aws_profile_name, $attachment_set)
{
    $logger = get_logger();
    $client = get_client($aws_profile_name);
    foreach ($attachment_set as $i => $row) {


        $dir  = DATA_DIR . '/attachments';
        $file = $dir . '/' . $row['attachmentId'];

        if (file_exists($file)) {
            continue;
        }

        $params = [
            'attachmentId' => $row['attachmentId'],
        ];
        $result = $client->describeAttachment($params);

        // var_dump($result['attachment']['fileName']);
        file_put_contents($file, $result['attachment']['data']);
        $logger->info("save attachment", $file);
    }
}

/*
|-----------------------------------------------------------------------------------------------------------------------
| miscellaneous
|-----------------------------------------------------------------------------------------------------------------------
*/


/*
|-----------------------------------------------------------------------------------------------------------------------
| main
|-----------------------------------------------------------------------------------------------------------------------
*/

function main()
{
    global $argv;

    $lang_list = ['en', 'ja'];

    $start_time = time();
    $logger     = get_logger();
    $logger->info('START', $argv);

    $options = get_options();
    $days    = isset($options['days']) ? $options['days'] : 365;
    $status  = $options['status'];

    $profile_list = explode(',', $options['profile']);

    $exit_code = 0;
    foreach ($profile_list as $profile_name) {
        $profile_name = trim($profile_name);
        foreach ($lang_list as $lang) {
            try {
                fetch_cases($profile_name, $lang, $status, $days);
            } catch (\Exception $e) {
                $logger->error("Profile: {$profile_name} Message: " . $e->getMessage());
                $exit_code = 1;
            }
        }
    }

    $finish_time    = time();
    $execution_time = $finish_time - $start_time;

    $logger->info('FINISH', ['execution time' => $execution_time . ' sec']);
    exit($exit_code);
}

main();
