<?php
require_once '../../includes/modal_header.php';

$contract_template_id = intval($_GET['id']);

$contract_types_array = ['Fully Managed', 'Partialy Managed', 'Break/Fix'];
$renewal_frequency_array = ['Manual', 'Annually', '2 Year', '3 Year', '5 Year', '7 Year'];

// Fetch existing template
$sql = mysqli_query($mysqli, "SELECT contract_template_description, contract_template_details, contract_template_name,
    contract_template_net_terms, contract_template_rate_after_hours,
    contract_template_rate_standard, contract_template_renewal_frequency,
    contract_template_sla_low_id, contract_template_sla_medium_id,
    contract_template_sla_high_id, contract_template_sla_urgent_id,
    contract_template_support_hours, contract_template_type FROM contract_templates WHERE contract_template_id = $contract_template_id LIMIT 1");
$row = mysqli_fetch_assoc($sql);

// Assign locals
$name          = escapeHtml($row['contract_template_name']);
$description   = escapeHtml($row['contract_template_description']);
$type          = escapeHtml($row['contract_template_type']);
$renewal_frequency = escapeHtml($row['contract_template_renewal_frequency']);
$current_template_sla_ids = [
    'low'    => intval($row['contract_template_sla_low_id']),
    'medium' => intval($row['contract_template_sla_medium_id']),
    'high'   => intval($row['contract_template_sla_high_id']),
    'urgent' => intval($row['contract_template_sla_urgent_id']),
];
$hourly_rate   = intval($row['contract_template_rate_standard']);
$after_hours   = intval($row['contract_template_rate_after_hours']);
$support_hours = escapeHtml($row['contract_template_support_hours']);
$net_terms     = intval($row['contract_template_net_terms']);
$details       = escapeHtml($row['contract_template_details']);

ob_start();
?>

<div class="modal-header bg-dark">
    <h5 class="modal-title"><i class="fa fa-fw fa-file-contract mr-2"></i>Edit Contract Template</h5>
    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
</div>

<!-- Tabs Navigation -->
<ul class="modal-header nav nav-pills nav-justified">
    <li class="nav-item">
        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">General Info</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="sla-tab" data-toggle="tab" href="#sla" role="tab">SLA</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="rates-tab" data-toggle="tab" href="#rates" role="tab">Rates & Support</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab">Details</a>
    </li>
</ul>

<form action="post.php" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="contract_template_id" value="<?= $contract_template_id ?>">

    <div class="modal-body">
        <div class="tab-content" id="contractTemplateTabContent">

            <!-- General Info Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="form-group">
                    <label>Template Name <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-contract"></i></span>
                        </div>
                        <input type="text" class="form-control" name="name"
                            placeholder="Contract Template Name" maxlength="200" required autofocus
                            value="<?= $name ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Template Description <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-align-left"></i></span>
                        </div>
                        <input type="text" class="form-control" name="description"
                            placeholder="Contract Template Description" maxlength="200" required
                            value="<?= $description ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Contract Type <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                        </div>
                        <select class="form-control select2" name="type" required>
                            <option value="">- Select Type -</option>
                            <?php foreach ($contract_types_array as $type_select) { ?>
                                <option <?php if ($type == $type_select) { echo "selected"; } ?>><?= $type_select ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Renewal Frequency</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-sync-alt"></i></span>
                        </div>
                        <select class="form-control select2" name="renewal_frequency">
                            <option value="">- Select Frequency -</option>
                            <?php foreach ($renewal_frequency_array as $renewal_frequency_select) { ?>
                                <option <?php if ($renewal_frequency == $renewal_frequency_select) { echo "selected"; } ?>><?= $renewal_frequency_select ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SLA Tab -->
            <div class="tab-pane fade" id="sla" role="tabpanel">
                <?php
                $sql_slas_te = mysqli_query($mysqli, "SELECT sla_id, sla_name, sla_description FROM slas WHERE sla_archived_at IS NULL ORDER BY sla_name ASC");
                $slas_list_te = [];
                while ($s = mysqli_fetch_assoc($sql_slas_te)) { $slas_list_te[] = $s; }
                ?>
                <p class="text-muted small mb-3">Link a native SLA plan per priority. When a contract using this template is Active these override standard SLA assignments for the client's tickets.</p>
                <?php
                foreach ([
                    ['low',    'Low',    'badge-success'],
                    ['medium', 'Medium', 'badge-warning text-dark'],
                    ['high',   'High',   'badge-danger'],
                    ['urgent', 'Urgent', 'badge-dark'],
                ] as [$key, $label, $badge]) {
                    $current = $current_template_sla_ids[$key];
                ?>
                <div class="form-group">
                    <label><span class="badge <?= $badge ?>"><?= $label ?></span> Priority SLA Plan</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-stopwatch"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_template_sla_<?= $key ?>_id">
                            <option value="0">- None -</option>
                            <?php foreach ($slas_list_te as $sla) { ?>
                                <option value="<?= intval($sla['sla_id']) ?>" <?= $current === intval($sla['sla_id']) ? 'selected' : '' ?>>
                                    <?= escapeHtml($sla['sla_name']) ?>
                                    <?= $sla['sla_description'] ? ' — ' . escapeHtml($sla['sla_description']) : '' ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($slas_list_te)) { ?>
                    <p class="text-muted small mt-2"><i class="fas fa-info-circle mr-1"></i>No SLA plans configured. <a href="/admin/sla.php">Create one in Admin → SLA</a>.</p>
                <?php } ?>
            </div>

            <!-- Rates & Support Tab -->
            <div class="tab-pane fade" id="rates" role="tabpanel">
                <div class="form-group">
                    <label>Standard Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                        </div>
                        <input type="text" class="form-control" name="rate_standard" placeholder="e.g., 100"
                            value="<?= $rate_standard ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>After Hours Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-moon"></i></span>
                        </div>
                        <input type="text" class="form-control" name="rate_after_hours" placeholder="e.g., 150"
                            value="<?= $rate_after_hours ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Support Hours</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                        </div>
                        <input type="text" class="form-control" name="support_hours" placeholder="e.g., Mon-Fri 9am-5pm" maxlength="100"
                            value="<?= $support_hours ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Net Terms</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-invoice-dollar"></i></span>
                        </div>
                        <input type="text" class="form-control" name="net_terms" placeholder="e.g., Net 30" maxlength="50"
                            value="<?= $net_terms ?>">
                    </div>
                </div>
            </div>

            <!-- Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="form-group">
                    <label>Contract Details</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-align-left"></i></span>
                        </div>
                        <textarea class="form-control tinymce" rows="6" name="details"
                            placeholder="Enter Contract Details"><?= $details ?></textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" name="edit_contract_template" class="btn btn-primary text-bold">
            <i class="fa fa-check mr-2"></i>Save Changes
        </button>
        <button type="button" class="btn btn-light" data-dismiss="modal">
            <i class="fa fa-times mr-2"></i>Cancel
        </button>
    </div>
</form>

<?php
require_once '../../../includes/modal_footer.php';
?>
