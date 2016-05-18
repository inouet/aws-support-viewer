#!/usr/bin/env php
<?php
/**
 * import_cases.php
 *
 * Import support cases, communications and attachments.
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/common.php';

ORM::configure('sqlite:' . DATA_DIR . '/db/support_cases.db');
ORM::configure(
    'id_column_overrides',
    [
        'cases'          => 'case_id',
        'attachments'    => 'attachment_id',
        'communications' => 'communication_id'
    ]
);

/*
|-----------------------------------------------------------------------------------------------------------------------
| main
|-----------------------------------------------------------------------------------------------------------------------
*/

function main()
{
    global $argv;

    $logger = get_logger();
    $logger->info("START", $argv);

    $case_dir = DATA_DIR . "/cases";

    //
    // case
    //
    $case_files = glob("{$case_dir}/case-*/case-*.json");

    foreach ($case_files as $file) {
        import_case($file);
    }

    //
    // communications
    //
    $comm_files = glob("{$case_dir}/case-*/comm-*.json");

    foreach ($comm_files as $file) {
        import_communication($file);
    }
    $logger->info("FINISH");
}

/**
 * Import case
 *
 * @param string $file
 */
function import_case($file)
{
    $done_file = $file . '.done';

    $logger = get_logger();

    if (file_exists($done_file)) {
        return;
    }

    $data  = file_get_contents($file);
    $array = json_decode($data, true);

    // account_id (case-310000000000-xxxx-2016-xxxxxxxxxxxxxxxx)
    $parts      = explode('-', $array['caseId']);
    $account_id = $parts[1];

    $table = ORM::for_table('cases');
    $row   = $table->find_one($array['caseId']);

    if (!$row) {
        $row = $table->create();
    }

    $cc_email_addresses = join(',', $array['ccEmailAddresses']);

    $row->case_id            = $array['caseId'];
    $row->status             = $array['status'];
    $row->cc_email_addresses = $cc_email_addresses;
    $row->time_created       = $array['timeCreated'];
    $row->severity_code      = $array['severityCode'];
    $row->language           = $array['language'];
    $row->category_code      = $array['categoryCode'];
    $row->service_code       = $array['serviceCode'];
    $row->submitted_by       = $array['submittedBy'];
    $row->display_id         = $array['displayId'];
    $row->subject            = $array['subject'];
    $row->account_id         = $account_id;

    try {
        $row->save();
        $logger->info("import_case", [$row->case_id]);
        touch($done_file);
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $logger->error("import_case", [$message]);
    }
}

/**
 * Import communication
 *
 * @param string $file
 */
function import_communication($file)
{
    $done_file = $file . '.done';
    $logger    = get_logger();

    if (file_exists($done_file)) {
        return;
    }

    $data  = file_get_contents($file);
    $array = json_decode($data, true);

    $table = ORM::for_table('communications');

    $communication_id = generate_communication_id($array);

    $has_attachment = 0;
    if (is_array($array['attachmentSet']) && sizeof($array['attachmentSet']) > 0) {
        $has_attachment = 1;
    }

    $row = $table->find_one($communication_id);

    if (!$row) {
        $row = $table->create();
    }
    $row->case_id          = $array['caseId'];
    $row->communication_id = $communication_id;
    $row->body             = $array['body'];
    $row->time_created     = $array['timeCreated'];
    $row->submitted_by     = $array['submittedBy'];
    $row->has_attachment   = $has_attachment;

    try {
        $row->save();
        $logger->info("import_communication", [$row->communication_id]);
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $logger->error("import_case", [$message]);
    }

    if ($has_attachment) {
        foreach ($array['attachmentSet'] as $row) {
            import_attachment($row, $array['caseId'], $communication_id);
        }
    }
    touch($done_file);
}

/**
 * Import attachment
 *
 * @param array  $data
 * @param string $case_id
 * @param string $communication_id
 */
function import_attachment($data, $case_id, $communication_id)
{
    $logger = get_logger();
    $table  = ORM::for_table('attachments');

    $row = $table->find_one($data['attachmentId']);

    if (!$row) {
        $row = $table->create();
    }

    $row->attachment_id    = $data['attachmentId'];
    $row->file_name        = $data['fileName'];
    $row->case_id          = $case_id;
    $row->communication_id = $communication_id;

    try {
        $row->save();
        $logger->info("import_attachment", [$row->attachment_id]);
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $logger->error("import_case", [$message]);
    }
}

main();
