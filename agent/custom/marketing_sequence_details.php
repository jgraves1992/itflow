<?php

require_once "includes/inc_all_custom.php";

enforceUserPermission('module_client');

$sequence_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sequence = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_sequences WHERE sequence_id = $sequence_id AND sequence_archived_at IS NULL"));

if (!$sequence) {
    flash_alert('Sequence not found.', 'error');
    header('Location: marketing_sequences.php');
    exit;
}

$steps = mysqli_query($mysqli,
    "SELECT * FROM marketing_sequence_steps
     WHERE step_sequence_id = $sequence_id
     ORDER BY step_order ASC");

$active_enrollments = intval(mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT COUNT(*) AS cnt FROM marketing_enrollments
     WHERE enrollment_sequence_id = $sequence_id AND enrollment_status = 'active'"))['cnt']);

$total_enrollments = intval(mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT COUNT(*) AS cnt FROM marketing_enrollments
     WHERE enrollment_sequence_id = $sequence_id"))['cnt']);

$step_count = intval(mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT COUNT(*) AS cnt FROM marketing_sequence_steps
     WHERE step_sequence_id = $sequence_id"))['cnt']);

// Determine next step order
$next_order = $step_count + 1;
$next_delay = 0;
if ($step_count > 0) {
    $last_step = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT step_delay_days FROM marketing_sequence_steps
         WHERE step_sequence_id = $sequence_id ORDER BY step_order DESC LIMIT 1"));
    $next_delay = intval($last_step['step_delay_days']) + 3;
}

?>

<div class="row">
    <!-- Sequence Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope-open-text mr-2"></i>Sequence Settings</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#editSequenceModal">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <h5 class="mb-1"><?= nullable_htmlentities($sequence['sequence_name']) ?></h5>
                <?php if ($sequence['sequence_description']): ?>
                <p class="text-muted small"><?= nl2br(nullable_htmlentities($sequence['sequence_description'])) ?></p>
                <?php endif; ?>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-6">Status</dt>
                    <dd class="col-6">
                        <span class="badge badge-<?= $sequence['sequence_active'] ? 'success' : 'secondary' ?>">
                            <?= $sequence['sequence_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </dd>
                    <dt class="col-6">Steps</dt>
                    <dd class="col-6"><?= $step_count ?></dd>
                    <dt class="col-6">Active Leads</dt>
                    <dd class="col-6"><?= $active_enrollments ?></dd>
                    <dt class="col-6">Total Enrolled</dt>
                    <dd class="col-6"><?= $total_enrollments ?></dd>
                    <dt class="col-6">Send Time</dt>
                    <dd class="col-6"><small><?= date('g:i A', strtotime($sequence['sequence_send_time'])) ?></small></dd>
                    <dt class="col-6">From</dt>
                    <dd class="col-6">
                        <?php if ($sequence['sequence_from_email']): ?>
                            <small><?= nullable_htmlentities($sequence['sequence_from_name'] ?: $sequence['sequence_from_email']) ?></small>
                        <?php else: ?>
                            <small class="text-muted">System default</small>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Variable Reference Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-code mr-2"></i>Template Variables</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td><code>{{name}}</code></td><td class="text-muted">Full name</td></tr>
                        <tr><td><code>{{first_name}}</code></td><td class="text-muted">First name</td></tr>
                        <tr><td><code>{{company}}</code></td><td class="text-muted">Company</td></tr>
                        <tr><td><code>{{email}}</code></td><td class="text-muted">Email address</td></tr>
                        <tr><td><code>{{phone}}</code></td><td class="text-muted">Phone number</td></tr>
                        <tr><td><code>{{unsubscribe_link}}</code></td><td class="text-muted">1-click unsub URL</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Steps Column -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Email Steps</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addStepModal">
                        <i class="fas fa-plus"></i> Add Step
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($steps) === 0): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-envelope fa-2x mb-2 d-block"></i>
                    No steps yet. Add the first email in this sequence.
                </div>
                <?php endif; ?>

                <?php
                $step_num = 0;
                while ($step = mysqli_fetch_assoc($steps)):
                    $step_num++;
                    $step_id = intval($step['step_id']);
                ?>
                <div class="p-3 <?= $step_num > 1 ? 'border-top' : '' ?>">
                    <div class="d-flex align-items-start">
                        <div class="mr-3 text-center" style="min-width:40px">
                            <span class="badge badge-secondary badge-pill" style="font-size:1rem;padding:6px 10px"><?= $step['step_order'] ?></span>
                            <small class="d-block text-muted mt-1">Day <?= $step['step_delay_days'] ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <strong><?= nullable_htmlentities($step['step_subject']) ?></strong>
                            <div class="text-muted small mt-1" style="max-height:60px;overflow:hidden">
                                <?= nl2br(nullable_htmlentities(mb_strimwidth(strip_tags($step['step_body']), 0, 200, '…'))) ?>
                            </div>
                        </div>
                        <div class="ml-3 text-nowrap">
                            <button type="button" class="btn btn-xs btn-default"
                                    data-toggle="modal"
                                    data-target="#editStepModal<?= $step_id ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="post.php" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this step?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="delete_marketing_step" value="1">
                                <input type="hidden" name="step_id" value="<?= $step_id ?>">
                                <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                                <button type="submit" class="btn btn-xs btn-default text-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Step Modal -->
                <div class="modal fade" id="editStepModal<?= $step_id ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <form action="post.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="edit_marketing_step" value="1">
                                <input type="hidden" name="step_id" value="<?= $step_id ?>">
                                <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Step <?= $step['step_order'] ?></h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="form-group">
                                                <label>Subject <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="step_subject" required
                                                       value="<?= nullable_htmlentities($step['step_subject']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Send on Day # <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="step_delay_days"
                                                       min="0" required value="<?= $step['step_delay_days'] ?>">
                                                <small class="text-muted">Days after enrollment</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Email Body <span class="text-danger">*</span></label>
                                        <textarea class="form-control tinymce" name="step_body" rows="12"
                                                  placeholder="Use {{name}}, {{company}}, {{unsubscribe_link}} etc."><?= nullable_htmlentities($step['step_body']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sequence Modal -->
<div class="modal fade" id="editSequenceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="edit_marketing_sequence" value="1">
                <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sequence</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Sequence Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sequence_name" required
                               value="<?= nullable_htmlentities($sequence['sequence_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="sequence_description" rows="2"><?= nullable_htmlentities($sequence['sequence_description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Daily Send Time</label>
                        <input type="time" class="form-control" name="sequence_send_time"
                               value="<?= substr($sequence['sequence_send_time'], 0, 5) ?>">
                        <small class="text-muted">All emails in this sequence send at this time of day</small>
                    </div>
                    <div class="form-group">
                        <label>Active</label>
                        <select class="form-control" name="sequence_active">
                            <option value="1" <?= $sequence['sequence_active'] ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= !$sequence['sequence_active'] ? 'selected' : '' ?>>Inactive (pauses new sends)</option>
                        </select>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" class="form-control" name="sequence_from_name"
                                       value="<?= nullable_htmlentities($sequence['sequence_from_name']) ?>"
                                       placeholder="System default">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>From Email</label>
                                <input type="email" class="form-control" name="sequence_from_email"
                                       value="<?= nullable_htmlentities($sequence['sequence_from_email']) ?>"
                                       placeholder="System default">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Step Modal -->
<div class="modal fade" id="addStepModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="add_marketing_step" value="1">
                <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Add Email Step</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="step_subject" required
                                       placeholder="e.g. Quick question about your IT needs">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label>Send on Day # <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="step_delay_days"
                                       min="0" required value="<?= $next_delay ?>">
                                <small class="text-muted">Days after enrollment (0 = immediately)</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Body <span class="text-danger">*</span></label>
                        <textarea class="form-control tinymce" name="step_body" rows="14"
                                  placeholder="Hi {{first_name}},&#10;&#10;...&#10;&#10;To unsubscribe: {{unsubscribe_link}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Add Step</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
