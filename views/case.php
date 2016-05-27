<?php include_once('_header.php') ?>

<div class="blank">
   <div class="blank-page">

<h2>AWS Support Case Details</h2>

<table border="0" width="90%">
    <tr>
        <th>Subject</th>
        <td><?php e($case['subject']) ?></td>
    </tr>
    <tr>
        <th>Case ID</th>
        <td><?php e($case['display_id']) ?></td>
        <th>Status</th>
        <td><?php ucw($case['status']) ?></td>
    </tr>
    <tr>
        <th>Created</th>
        <td><?php e($case['time_created']) ?></td>

        <th>Severity</th>
        <td><?php ucw($case['severity_code']) ?></td>
    </tr>
    <tr>

        <th>Service</th>
        <td>
            <?php ucw($case['service_code']) ?>
        </td>

        <th>Category</th>
        <td>
            <?php ucw($case['category_code']) ?>
        </td>
    </tr>
    <tr>
        <th>By</th>
        <td><?php e($case['submitted_by']) ?></td>
        <th>CCd emails</th>
        <td><?php e($case['cc_email_addresses']) ?></td>
    </tr>
</table>

<h2>Correspondence</h2>
<?php foreach ($communications as $row): ?>
    <table border="1" width="90%">
        <tr>
            <th width="200">Submitted:</th>
            <td><?php echo $row['submitted_by'] ?></td>
        </tr>
        <tr>
            <th>Date:</th>
            <td><?php echo $row['time_created'] ?></td>
        </tr>

        <?php if (isset($attachments[$row['communication_id']])): ?>
            <tr>
                <th>Attachment</th>
                <td>
                    <?php foreach ($attachments[$row['communication_id']] as $att): ?>
                        <?php  e($att['file_name']) ?> <a href="/attachment/<?php e($att['attachment_id']) ?>">Download</a>
                        <br/>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td colspan="2">
                <pre><?php e($row['body']) ?></pre>
            </td>
        </tr>
    </table>
    <br/>
<?php endforeach; ?>

</div>
</div>

<?php include_once('_footer.php') ?>

