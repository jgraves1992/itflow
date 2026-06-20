<?php

require_once "includes/inc_all_custom.php";

enforceUserPermission('module_client');

$lead_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$lead = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_leads WHERE lead_id = $lead_id AND lead_archived_at IS NULL"));

if (!$lead) {
    flash_alert('Lead not found.', 'error');
    header('Location: marketing_leads.php');
    exit;
}

$sequences_available = mysqli_query($mysqli,
    "SELECT s.* FROM marketing_sequences s
     WHERE s.sequence_active = 1 AND s.sequence_archived_at IS NULL
     AND s.sequence_id NOT IN (
         SELECT enrollment_sequence_id FROM marketing_enrollments
         WHERE enrollment_lead_id = $lead_id
     )
     ORDER BY s.sequence_name");

$enrollments = mysqli_query($mysqli,
    "SELECT e.*, s.sequence_name,
            (SELECT COUNT(*) FROM marketing_sequence_steps WHERE step_sequence_id = s.sequence_id) AS total_steps,
            (SELECT COUNT(*) FROM marketing_email_log WHERE log_enrollment_id = e.enrollment_id) AS emails_sent
     FROM marketing_enrollments e
     JOIN marketing_sequences s ON s.sequence_id = e.enrollment_sequence_id
     WHERE e.enrollment_lead_id = $lead_id
     ORDER BY e.enrollment_enrolled_at DESC");

$email_log = mysqli_query($mysqli,
    "SELECT l.*, s.sequence_name, st.step_order
     FROM marketing_email_log l
     JOIN marketing_sequences s ON s.sequence_id = l.log_sequence_id
     JOIN marketing_sequence_steps st ON st.step_id = l.log_step_id
     WHERE l.log_lead_id = $lead_id
     ORDER BY l.log_sent_at DESC
     LIMIT 50");

$status_colors = [
    'new' => 'secondary', 'contacted' => 'info', 'qualified' => 'primary',
    'converted' => 'success', 'lost' => 'danger',
];

$enrollment_colors = [
    'active' => 'info', 'completed' => 'success', 'paused' => 'warning', 'unsubscribed' => 'danger',
];

?>

<div class="row">

    <!-- Lead Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i><?= nullable_htmlentities($lead['lead_name']) ?></h3>
                <div class="card-tools">
                    <?php if ($lead['lead_client_id']): ?>
                    <a href="/agent/client_overview.php?client_id=<?= intval($lead['lead_client_id']) ?>"
                       class="btn btn-default btn-sm" title="View in ITFlow">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#editLeadModal">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Email</dt>
                    <dd class="col-7">
                        <a href="mailto:<?= nullable_htmlentities($lead['lead_email']) ?>">
                            <?= nullable_htmlentities($lead['lead_email']) ?>
                        </a>
                    </dd>

                    <?php if ($lead['lead_company']): ?>
                    <dt class="col-5">Company</dt>
                    <dd class="col-7"><?= nullable_htmlentities($lead['lead_company']) ?></dd>
                    <?php endif; ?>

                    <?php if ($lead['lead_phone']): ?>
                    <dt class="col-5">Phone</dt>
                    <dd class="col-7"><?= nullable_htmlentities($lead['lead_phone']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5">Status</dt>
                    <dd class="col-7">
                        <span class="badge badge-<?= $status_colors[$lead['lead_status']] ?? 'secondary' ?>">
                            <?= ucfirst($lead['lead_status']) ?>
                        </span>
                    </dd>

                    <?php if ($lead['lead_source']): ?>
                    <dt class="col-5">Source</dt>
                    <dd class="col-7"><?= nullable_htmlentities($lead['lead_source']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5">Added</dt>
                    <dd class="col-7"><?= date('M j, Y', strtotime($lead['lead_created_at'])) ?></dd>

                    <?php if ($lead['lead_unsubscribed']): ?>
                    <dt class="col-5">Unsubscribed</dt>
                    <dd class="col-7">
                        <span class="badge badge-warning">Yes</span>
                        <small class="text-muted d-block"><?= date('M j, Y', strtotime($lead['lead_unsubscribed_at'])) ?></small>
                    </dd>
                    <?php endif; ?>
                </dl>

                <?php if ($lead['lead_notes']): ?>
                <hr>
                <p class="text-muted small mb-0"><?= nl2br(nullable_htmlentities($lead['lead_notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sequences Column -->
    <div class="col-md-8">

        <!-- Active Enrollments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope-open-text mr-2"></i>Sequence Enrollments</h3>
                <div class="card-tools">
                    <?php if (mysqli_num_rows($sequences_available) > 0): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#enrollModal">
                        <i class="fas fa-plus"></i> Enroll in Sequence
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Sequence</th>
                            <th>Status</th>
                            <th>Emails Sent</th>
                            <th>Next Send</th>
                            <th>Enrolled</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($enrollments) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Not enrolled in any sequences.</td></tr>
                    <?php endif; ?>
                    <?php while ($enroll = mysqli_fetch_assoc($enrollments)):
                        $e_id = intval($enroll['enrollment_id']);
                        $e_color = $enrollment_colors[$enroll['enrollment_status']] ?? 'secondary';
                    ?>
                        <tr>
                            <td>
                                <a href="marketing_sequence_details.php?id=<?= $enroll['enrollment_sequence_id'] ?>">
                                    <?= nullable_htmlentities($enroll['sequence_name']) ?>
                                </a>
                            </td>
                            <td><span class="badge badge-<?= $e_color ?>"><?= ucfirst($enroll['enrollment_status']) ?></span></td>
                            <td><?= $enroll['emails_sent'] ?> / <?= $enroll['total_steps'] ?></td>
                            <td>
                                <?php if ($enroll['enrollment_status'] === 'active' && $enroll['enrollment_next_send_at']): ?>
                                    <?= date('M j, Y', strtotime($enroll['enrollment_next_send_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($enroll['enrollment_enrolled_at'])) ?></td>
                            <td class="text-right">
                                <?php if ($enroll['enrollment_status'] === 'active'): ?>
                                <form action="post.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="pause_marketing_enrollment" value="1">
                                    <input type="hidden" name="enrollment_id" value="<?= $e_id ?>">
                                    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                                    <button type="submit" class="btn btn-xs btn-default" title="Pause"><i class="fas fa-pause"></i></button>
                                </form>
                                <?php elseif ($enroll['enrollment_status'] === 'paused'): ?>
                                <form action="post.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="resume_marketing_enrollment" value="1">
                                    <input type="hidden" name="enrollment_id" value="<?= $e_id ?>">
                                    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                                    <button type="submit" class="btn btn-xs btn-default" title="Resume"><i class="fas fa-play"></i></button>
                                </form>
                                <?php endif; ?>
                                <form action="post.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this lead from the sequence?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="unenroll_marketing_lead" value="1">
                                    <input type="hidden" name="enrollment_id" value="<?= $e_id ?>">
                                    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                                    <button type="submit" class="btn btn-xs btn-default text-danger" title="Remove"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Email History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Email History</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Subject</th><th>Sequence</th><th>Step</th><th>Sent</th></tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($email_log) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No emails sent yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($log = mysqli_fetch_assoc($email_log)): ?>
                        <tr>
                            <td><?= nullable_htmlentities($log['log_subject']) ?></td>
                            <td><?= nullable_htmlentities($log['sequence_name']) ?></td>
                            <td>#<?= $log['step_order'] ?></td>
                            <td><?= date('M j, Y g:ia', strtotime($log['log_sent_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Edit Lead Modal -->
<div class="modal fade" id="editLeadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="edit_marketing_lead" value="1">
                <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Lead</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lead_name" required
                               value="<?= nullable_htmlentities($lead['lead_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="lead_email" required
                               value="<?= nullable_htmlentities($lead['lead_email']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" class="form-control" name="lead_company"
                               value="<?= nullable_htmlentities($lead['lead_company']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" class="form-control" name="lead_phone"
                               value="<?= nullable_htmlentities($lead['lead_phone']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="lead_status">
                                    <?php foreach (['new', 'contacted', 'qualified', 'converted', 'lost'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $lead['lead_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Source</label>
                                <input type="text" class="form-control" name="lead_source"
                                       value="<?= nullable_htmlentities($lead['lead_source']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="lead_notes" rows="3"><?= nullable_htmlentities($lead['lead_notes']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enroll in Sequence Modal -->
<?php if (mysqli_num_rows($sequences_available) > 0):
    // Reset result pointer after earlier num_rows check
    mysqli_data_seek($sequences_available, 0);
?>
<div class="modal fade" id="enrollModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="enroll_marketing_lead" value="1">
                <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll in Sequence</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Sequence <span class="text-danger">*</span></label>
                        <select class="form-control" name="sequence_id" required>
                            <option value="">— Choose a sequence —</option>
                            <?php while ($seq = mysqli_fetch_assoc($sequences_available)): ?>
                            <option value="<?= $seq['sequence_id'] ?>">
                                <?= nullable_htmlentities($seq['sequence_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <p class="text-muted small mb-0">
                        The first email will be queued on the next cron run. Subsequent emails
                        follow the sequence's scheduled delays.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once "../../includes/footer.php"; ?>
