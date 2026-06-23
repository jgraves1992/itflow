<?php

require_once "includes/inc_all_custom.php";

enforceUserPermission('module_client');

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$where  = "WHERE sequence_archived_at IS NULL";

// sanitizeInput() already escapes for SQL — do not re-escape, or quotes/backslashes break the match
if ($search) {
    $where .= " AND (sequence_name LIKE '%$search%' OR sequence_description LIKE '%$search%')";
}

$sql = mysqli_query($mysqli,
    "SELECT s.*,
            (SELECT COUNT(*) FROM marketing_sequence_steps WHERE step_sequence_id = s.sequence_id) AS step_count,
            (SELECT COUNT(*) FROM marketing_enrollments WHERE enrollment_sequence_id = s.sequence_id AND enrollment_status = 'active') AS active_leads,
            (SELECT COUNT(*) FROM marketing_enrollments WHERE enrollment_sequence_id = s.sequence_id) AS total_leads
     FROM marketing_sequences s
     $where
     ORDER BY s.sequence_created_at DESC");

if ($sql === false) {
    echo '<div class="alert alert-danger m-3"><strong>Database error:</strong> ' . htmlspecialchars(mysqli_error($mysqli)) . '<br>Did you run <code>db_marketing.sql</code>?</div>';
    require_once "../../includes/footer.php";
    exit;
}

?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-envelope-open-text mr-2"></i>Email Sequences</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSequenceModal">
                <i class="fas fa-plus"></i> New Sequence
            </button>
        </div>
    </div>

    <?php if ($search): ?>
    <div class="card-body pb-0">
        <form method="GET" class="row">
            <div class="col-md-5">
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Search sequences...">
                    <div class="input-group-append">
                        <button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <a href="marketing_sequences.php" class="btn btn-default btn-sm">Clear</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-body p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Steps</th>
                    <th>Active Leads</th>
                    <th>Total Enrolled</th>
                    <th>From</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($sql) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No sequences yet. Click <strong>New Sequence</strong> to create your first drip campaign.
                    </td>
                </tr>
            <?php endif; ?>
            <?php while ($seq = mysqli_fetch_assoc($sql)):
                $seq_id = intval($seq['sequence_id']);
            ?>
                <tr>
                    <td>
                        <a href="marketing_sequence_details.php?id=<?= $seq_id ?>">
                            <?= nullable_htmlentities($seq['sequence_name']) ?>
                        </a>
                        <?php if ($seq['sequence_description']): ?>
                        <small class="text-muted d-block"><?= nullable_htmlentities(mb_strimwidth($seq['sequence_description'], 0, 80, '…')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= $seq['step_count'] ?></td>
                    <td>
                        <?php if ($seq['active_leads'] > 0): ?>
                            <span class="badge badge-info"><?= $seq['active_leads'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $seq['total_leads'] ?></td>
                    <td>
                        <?php if ($seq['sequence_from_email']): ?>
                            <small><?= nullable_htmlentities($seq['sequence_from_name'] ?: $seq['sequence_from_email']) ?></small>
                        <?php else: ?>
                            <small class="text-muted">System default</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $seq['sequence_active'] ? 'success' : 'secondary' ?>">
                            <?= $seq['sequence_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($seq['sequence_created_at'])) ?></td>
                    <td class="text-right">
                        <a href="marketing_sequence_details.php?id=<?= $seq_id ?>" class="btn btn-xs btn-default" title="Edit"><i class="fas fa-edit"></i></a>
                        <?php if (lookupUserPermission('module_client') >= 2): ?>
                        <form action="post.php" method="POST" class="d-inline"
                              onsubmit="return confirm('Archive this sequence? Active enrollments will be paused.')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="archive_marketing_sequence" value="1">
                            <input type="hidden" name="sequence_id" value="<?= $seq_id ?>">
                            <button type="submit" class="btn btn-xs btn-default text-danger" title="Archive"><i class="fas fa-archive"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Sequence Modal -->
<div class="modal fade" id="addSequenceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="add_marketing_sequence" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>New Email Sequence</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Sequence Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sequence_name" required
                               placeholder="e.g. New Lead Nurture, MSP Intro, Quarterly Check-in">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="sequence_description" rows="2"
                                  placeholder="What is this sequence for?"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Daily Send Time</label>
                        <input type="time" class="form-control" name="sequence_send_time" value="09:00">
                        <small class="text-muted">All emails in this sequence will send at this time of day</small>
                    </div>
                    <hr>
                    <p class="text-muted small">Override the system sender for this sequence (optional).</p>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" class="form-control" name="sequence_from_name"
                                       placeholder="Your Name">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>From Email</label>
                                <input type="email" class="form-control" name="sequence_from_email"
                                       placeholder="you@company.com">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Create Sequence</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
