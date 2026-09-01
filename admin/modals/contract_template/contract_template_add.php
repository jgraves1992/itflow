<?php
require_once '../../includes/modal_header.php';


$contract_types_array = ['Fully Managed', 'Partialy Managed', 'Break/Fix'];
$renewal_frequency_array = ['Manual', 'Annually', '2 Year', '3 Year', '5 Year', '7 Year'];

ob_start();
?>

<div class="modal-header bg-dark">
    <h5 class="modal-title"><i class="fa fa-fw fa-file-contract me-2"></i>New Contract Template</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<!-- Tabs Navigation -->
<ul class="modal-header nav nav-pills nav-justified">
    <li class="nav-item">
        <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">General Info</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="sla-tab" data-bs-toggle="tab" href="#sla" role="tab">SLA</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="rates-tab" data-bs-toggle="tab" href="#rates" role="tab">Rates & Support</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">Details</a>
    </li>
</ul>

<form action="post.php" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="modal-body">
        <div class="tab-content" id="contractTemplateTabContent">

            <!-- General Info Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="mb-3">
                    <label>Template Name <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-contract"></i></span>
                        <input type="text" class="form-control" name="name" placeholder="Contract Template Name" maxlength="200" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Template Description <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-align-left"></i></span>
                        <input type="text" class="form-control" name="description"
                        placeholder="Contract Template Description" maxlength="200" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Contract Type <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                        <select class="form-select select2" name="type" required>
                            <option value="">- Select Type -</option>
                            <?php foreach ($contract_types_array as $type) { ?>
                                <option><?= $type ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Renewal Frequency</label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-sync-alt"></i></span>
                        <select class="form-select select2" name="renewal_frequency">
                            <option value="">- Select Frequency -</option>
                            <?php foreach ($renewal_frequency_array as $renewal_frequency) { ?>
                                <option><?= $renewal_frequency ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SLA Tab -->
            <div class="tab-pane fade" id="sla" role="tabpanel">
                <?php
                $sql_slas_ta = mysqli_query($mysqli, "SELECT sla_id, sla_name, sla_description FROM slas WHERE sla_archived_at IS NULL ORDER BY sla_name ASC");
                $slas_list_ta = [];
                while ($s = mysqli_fetch_assoc($sql_slas_ta)) { $slas_list_ta[] = $s; }
                ?>
                <p class="text-muted small mb-3">Link a native SLA plan per priority. When a contract using this template is Active these override standard SLA assignments for the client's tickets.</p>
                <?php
                foreach ([
                    ['low',    'Low',    'badge-success'],
                    ['medium', 'Medium', 'badge-warning text-dark'],
                    ['high',   'High',   'badge-danger'],
                    ['urgent', 'Urgent', 'badge-dark'],
                ] as [$key, $label, $badge]) {
                ?>
                <div class="mb-3">
                    <label><span class="badge <?= $badge ?>"><?= $label ?></span> Priority SLA Plan</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-fw fa-stopwatch"></i></span>
                        <select class="form-select select2" name="contract_template_sla_<?= $key ?>_id">
                            <option value="0">- None -</option>
                            <?php foreach ($slas_list_ta as $sla) { ?>
                                <option value="<?= intval($sla['sla_id']) ?>">
                                    <?= escapeHtml($sla['sla_name']) ?>
                                    <?= $sla['sla_description'] ? ' — ' . escapeHtml($sla['sla_description']) : '' ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($slas_list_ta)) { ?>
                    <p class="text-muted small mt-2"><i class="fas fa-info-circle mr-1"></i>No SLA plans configured. <a href="/admin/sla.php">Create one in Admin → SLA</a>.</p>
                <?php } ?>
            </div>

            <!-- Rates & Support Tab -->
            <div class="tab-pane fade" id="rates" role="tabpanel">
                <div class="mb-3">
                    <label>Standard Hourly Rate</label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                        <input type="text" class="form-control" name="rate_standard" placeholder="e.g., 100">
                    </div>
                </div>

                <div class="mb-3">
                    <label>After Hours Hourly Rate</label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-moon"></i></span>
                        <input type="text" class="form-control" name="rate_after_hours" placeholder="e.g., 150">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Support Hours</label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                        <input type="text" class="form-control" name="support_hours" placeholder="e.g., Mon-Fri 9am-5pm" maxlength="100">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Net Terms</label>
                    <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-invoice-dollar"></i></span>
                        <input type="text" class="form-control" name="net_terms" placeholder="e.g., Net 30" maxlength="50">
                    </div>
                </div>
            </div>

            <!-- Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="mb-3">
                    <textarea class="form-control tinymce" rows="6" name="details" placeholder="Enter Contract Details"></textarea>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" name="add_contract_template" class="btn btn-primary text-bold">
            <i class="fa fa-check me-2"></i>Create Template
        </button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            <i class="fa fa-times me-2"></i>Cancel
        </button>
    </div>
</form>

<?php
require_once '../../../includes/modal_footer.php';
?>
