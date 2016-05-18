<?php include_once('_header.php') ?>

<h1>Cases</h1>

<form method="get" action="/">
    Query: <input type="text" name="query" value="<?php e(@$_GET['query'])?>">
    <input type="submit" value="Search">
</form>

Results: <?php e(number_format($total_count)) ?>

<table border="1">
    <tr>
        <th>Created</th>
        <th>Subject</th>
        <th>Case ID	</th>
        <th>Service</th>
        <th>Severity</th>
        <th>Status</th>
    </tr>
<?php foreach ($case_list as $row): ?>
    <tr>
        <td nowrap><?php e($row['time_created']) ?></td>

        <td><?php e($row['subject']) ?></td>
        <td>
            <a href="/case/<?php e($row['case_id']) ?>"><?php e($row['display_id']) ?></a>
        </td>

        <td nowrap><?php ucw($row['service_code']) ?></td>
        <td nowrap><?php ucw($row['severity_code']) ?></td>

        <td nowrap><?php e($row['status']) ?></td>
    </tr>
<?php endforeach; ?>
</table>

<?php echo $paginator; ?>

<?php include_once('_footer.php') ?>
