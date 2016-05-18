<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/view_helper.php';

use JasonGrimes\Paginator;
use Slim\Views\PhpRenderer;

ORM::configure('sqlite:' . DATA_DIR . '/db/support_cases.db');
ORM::configure(
    'id_column_overrides',
    [
        'cases'          => 'case_id',
        'attachments'    => 'attachment_id',
        'communications' => 'communication_id'
    ]
);

function get_pager_offset($page, $per_page)
{
    if ($page <= 0) {
        $page = 1;
    }
    return $per_page * ($page - 1);
}

$app = new Slim\App();

$container             = $app->getContainer();
$container['renderer'] = new PhpRenderer(VIEW_DIR);

$app->get(
    '/',
    function (Slim\Http\Request $request, Slim\Http\Response $response, $args) {

        $per_page = 30;

        $query = $request->getParam('query');
        $page  = $request->getParam('page', 1);

        $offset = get_pager_offset($page, $per_page);

        $sql_select = 'SELECT * FROM cases';
        $sql_count  = 'SELECT COUNT(case_id) AS count FROM cases';

        $args  = [];
        $where = [];

        if ($query) {
            $where[]      = "(subject LIKE :body OR case_id IN (SELECT case_id FROM communications WHERE body LIKE :body))";
            $args["body"] = "%{$query}%";
        }

        $sql_where = '';
        if ($where) {
            $sql_where = " WHERE " . join(" AND ", $where);
        }

        $sql_limit = " LIMIT {$offset}, {$per_page}";
        $sql_order = ' ORDER BY time_created DESC';

        $sql_select = $sql_select . $sql_where . $sql_order . $sql_limit;
        $sql_count  = $sql_count . $sql_where;

        // Count
        $table       = ORM::for_table('cases');
        $rows        = $table->raw_query($sql_count, $args)->find_array();
        $total_count = $rows[0]['count'];

        // Select
        $table = ORM::for_table('cases');
        //$table->order_by_desc('time_created');
        $table->raw_query($sql_select, $args);
        $case_list = $table->find_array();

        // Paginator
        $url       = '/?page=(:num)&query=' . urlencode($query);
        $paginator = new Paginator($total_count, $per_page, $page, $url);

        $view = [
            'case_list'   => $case_list,
            'paginator'   => $paginator,
            'total_count' => $total_count,
        ];

        return $this->renderer->render($response, "/index.php", $view);
    }
);

$app->get(
    '/case/{case_id}',
    function (Slim\Http\Request $request, Slim\Http\Response $response, $args) {
        $case_id = $args['case_id'];

        //
        // case
        //
        $_case = ORM::for_table('cases')->find_one($case_id);
        $case  = $_case->as_array();

        //
        // communications
        //
        $communications = ORM::for_table('communications')
            ->where('case_id', $case_id)
            ->order_by_asc('time_created')
            ->find_array();

        //
        // attachments
        //
        $attachments = ORM::for_table('attachments')
            ->where('case_id', $case_id)
            ->find_array();

        $_attachments = [];
        foreach ($attachments as $row) {
            $_attachments[$row['communication_id']][] = $row;
        }

        $view = [
            'case'           => $case,
            'communications' => $communications,
            'attachments'    => $_attachments,
        ];
        return $this->renderer->render($response, "/case.php", $view);
    }
);

$app->get(
    '/attachment/{attachment_id}',
    function (Slim\Http\Request $request, Slim\Http\Response $response, $args) {
        $attachment_id = $args['attachment_id'];

        $dir  = DATA_DIR . '/attachments';
        $file = $dir . '/' . $attachment_id;


        $attachment = ORM::for_table('attachments')
            ->find_one($attachment_id);

        if (!$attachment) {
            die("Not found");
        }

        $file_name = $attachment->get('file_name');

        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $file_name . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));

        readfile($file);
        return $response;
    }
);

$app->run();
