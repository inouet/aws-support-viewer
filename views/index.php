
<?php include_once('_header.php') ?>

<div class="blank">
  <div class="blank-page">

<h1>AWS Support Cases</h1>

<div>
    <form method="get" action="/">
        Query: <input type="text" name="query" value="<?php e(@$_GET['query'])?>">
        <input type="submit" value="Search">
    </form>

    Results: <?php e(number_format($total_count)) ?>
</div>

<table class="table table-striped table-bordered table-condensed table-hover">
    <thead class="thead-inverse">
    <tr>
        <th>Created</th>
        <th>Subject</th>
        <th>Case ID	</th>
        <th>Service</th>
        <th>Severity</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($case_list as $row): ?>
    <tr>
        <td nowrap><?php e($row['time_created']) ?></td>

        <td><?php e($row['subject']) ?></td>
        <td>
            <a href="/case/<?php e($row['case_id']) ?>"><?php e($row['display_id']) ?></a>
        </td>

        <td nowrap><?php ucw($row['service_code']) ?></td>
        <td nowrap><?php ucw($row['severity_code']) ?></td>

        <td nowrap><?php ucw($row['status']) ?></td>
    </tr>
    <?php endforeach; ?>
</tbody>
</table>

<?php echo $paginator; ?>

  </div><!-- //blank-page -->
</div><!-- //div blank -->>

<?php include_once('_footer.php') ?>

