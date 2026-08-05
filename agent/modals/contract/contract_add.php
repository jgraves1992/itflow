<?php

require_once '../../../includes/modal_header.php';

$client_id = intval($_GET['client_id'] ?? 0);

$contract_types_array    = ['Fully Managed', 'Partially Managed', 'Break/Fix'];
$contract_status_array   = ['Pending', 'Active', 'Expired', 'Terminated'];
$renewal_frequency_array = ['Manual', 'Annually', '2 Year', '3 Year', '5 Year', '7 Year'];

// Load available templates and encode as JSON for client-side pre-fill
$sql_templates = mysqli_query($mysqli, "SELECT * FROM contract_templates
    WHERE contract_template_archived_at IS NULL
    ORDER BY contract_template_name ASC");
$templates_data = [];
$templates_list = [];
while ($t = mysqli_fetch_assoc($sql_templates)) {
    $templates_data[$t['contract_template_id']] = $t;
    $templates_list[] = $t;
}
$templates_json = json_encode($templates_data);

// Load clients for dropdown (when not in client context)
if (!$client_id) {
    $sql_clients = mysqli_query($mysqli, "SELECT client_id, client_name FROM clients
        WHERE client_archived_at IS NULL ORDER BY client_name ASC");
}

// Load contacts for primary contact dropdown
$sql_contacts = mysqli_query($mysqli, "SELECT contact_id, contact_name FROM contacts
    WHERE contact_archived_at IS NULL
    " . ($client_id ? "AND contact_client_id = $client_id" : "") . "
    ORDER BY contact_name ASC");

ob_start();

?>

<div class="modal-header bg-dark">
    <h5 class="modal-title"><i class="fa fa-fw fa-file-contract mr-2"></i>New Contract</h5>
    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
</div>

<!-- Tab navigation -->
<ul class="nav nav-pills nav-justified modal-header">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#ct-general">General</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#ct-client">Client Info</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#ct-sla">SLA</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#ct-rates">Rates & Support</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#ct-details">Details</a>
    </li>
</ul>

<form action="post.php" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <?php if ($client_id) { ?>
        <input type="hidden" name="client_id" value="<?= $client_id ?>">
    <?php } ?>

    <div class="modal-body">
        <div class="tab-content">

            <!-- General Tab -->
            <div class="tab-pane fade show active" id="ct-general">

                <?php if (!empty($templates_list)) { ?>
                    <div class="form-group">
                        <label>Load from Template <small class="text-muted">(optional — pre-fills type, SLA & rates)</small></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-copy"></i></span>
                            </div>
                            <select class="form-control select2" name="template_id" id="contractTemplateSelect">
                                <option value="">- None -</option>
                                <?php foreach ($templates_list as $t) { ?>
                                    <option value="<?= intval($t['contract_template_id']) ?>">
                                        <?= escapeHtml($t['contract_template_name']) ?>
                                        (<?= escapeHtml($t['contract_template_type']) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <hr>
                <?php } ?>

                <div class="form-group">
                    <label>Contract Name <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-contract"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_name" placeholder="e.g. Acme Corp - Managed Services 2026" maxlength="255" required autofocus>
                    </div>
                </div>

                <?php if (!$client_id) { ?>
                    <div class="form-group">
                        <label>Client <strong class="text-danger">*</strong></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-user"></i></span>
                            </div>
                            <select class="form-control select2" name="client_id" required>
                                <option value="">- Select Client -</option>
                                <?php while ($c = mysqli_fetch_assoc($sql_clients)) { ?>
                                    <option value="<?= intval($c['client_id']) ?>"><?= escapeHtml($c['client_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                <?php } ?>

                <div class="form-group">
                    <label>Contract Type <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_type" id="contractTypeField" required>
                            <option value="">- Select Type -</option>
                            <?php foreach ($contract_types_array as $type) { ?>
                                <option><?= $type ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-toggle-on"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_status" required>
                            <?php foreach ($contract_status_array as $st) { ?>
                                <option <?= $st === 'Pending' ? 'selected' : '' ?>><?= $st ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                            </div>
                            <input type="date" class="form-control" name="contract_start_date" max="2999-12-31" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-calendar-times"></i></span>
                            </div>
                            <input type="date" class="form-control" name="contract_end_date" max="2999-12-31" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Renewal Frequency</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-sync-alt"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_renewal_frequency" id="contractRenewalField">
                            <option value="">- None -</option>
                            <?php foreach ($renewal_frequency_array as $freq) { ?>
                                <option><?= $freq ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Client Info Tab -->
            <div class="tab-pane fade" id="ct-client">
                <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>This snapshot is preserved on the contract at the time of creation.</p>

                <div class="form-group">
                    <label>Client Name (Snapshot)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_client_name" placeholder="Client legal name" maxlength="255">
                    </div>
                </div>

                <div class="form-group">
                    <label>Client Address (Snapshot)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-map-marker-alt"></i></span>
                        </div>
                        <textarea class="form-control" name="contract_client_address" rows="3" placeholder="Street, City, State, ZIP"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Client Email (Snapshot)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-envelope"></i></span>
                            </div>
                            <input type="email" class="form-control" name="contract_client_email" placeholder="billing@client.com" maxlength="255">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Client Phone (Snapshot)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-phone"></i></span>
                            </div>
                            <input type="text" class="form-control" name="contract_client_phone" placeholder="(555) 000-0000" maxlength="100">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Contact Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control" name="contract_contact_name" placeholder="Primary contact name" maxlength="255">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Agent Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-user-tie"></i></span>
                            </div>
                            <input type="text" class="form-control" name="contract_agent_name" placeholder="Your name / company rep" maxlength="255" value="<?= escapeHtml($session_name ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLA Tab -->
            <div class="tab-pane fade" id="ct-sla">
                <?php
                $sql_slas = mysqli_query($mysqli, "SELECT sla_id, sla_name, sla_description FROM slas WHERE sla_archived_at IS NULL ORDER BY sla_name ASC");
                $slas_list = [];
                while ($s = mysqli_fetch_assoc($sql_slas)) { $slas_list[] = $s; }
                ?>
                <p class="text-muted small mb-3">Link a native SLA plan per priority. While this contract is Active these override standard SLA assignments for the client's tickets.</p>
                <?php
                $sla_priorities = [
                    ['low',    'Low',    'badge-success',           'contractSlaLowId'],
                    ['medium', 'Medium', 'badge-warning text-dark', 'contractSlaMediumId'],
                    ['high',   'High',   'badge-danger',            'contractSlaHighId'],
                ];
                foreach ($sla_priorities as [$key, $label, $badge, $elId]) {
                ?>
                <div class="form-group">
                    <label><span class="badge <?= $badge ?>"><?= $label ?></span> Priority SLA Plan</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-stopwatch"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_sla_<?= $key ?>_id" id="<?= $elId ?>">
                            <option value="0">- None -</option>
                            <?php foreach ($slas_list as $sla) { ?>
                                <option value="<?= intval($sla['sla_id']) ?>">
                                    <?= escapeHtml($sla['sla_name']) ?>
                                    <?= $sla['sla_description'] ? ' — ' . escapeHtml($sla['sla_description']) : '' ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($slas_list)) { ?>
                    <p class="text-muted small mt-2"><i class="fas fa-info-circle mr-1"></i>No SLA plans configured. <a href="/admin/sla.php" target="_blank">Create one in Admin → SLA</a>.</p>
                <?php } ?>
            </div>

            <!-- Rates & Support Tab -->
            <div class="tab-pane fade" id="ct-rates">
                <div class="form-group">
                    <label>Standard Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                        </div>
                        <input type="number" step="0.01" class="form-control" name="contract_rate_standard" id="rateStd" placeholder="e.g. 100.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>After-Hours Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-moon"></i></span>
                        </div>
                        <input type="number" step="0.01" class="form-control" name="contract_rate_after_hours" id="rateAH" placeholder="e.g. 150.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Net Terms</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-invoice-dollar"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_net_terms" id="netTerms" placeholder="e.g. Net 30" maxlength="50">
                    </div>
                </div>

                <div class="form-group">
                    <label>Support Hours</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-calendar-check"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_support_hours" id="supportHours" placeholder="e.g. Mon-Fri 8am-6pm ET" maxlength="100">
                    </div>
                </div>
            </div>

            <!-- Details Tab -->
            <div class="tab-pane fade" id="ct-details">
                <div class="form-group">
                    <label>Contract Terms & Details</label>
                    <textarea class="form-control tinymce" rows="8" name="contract_details" placeholder="Enter full contract terms, scope of work, exclusions, etc."></textarea>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" name="add_contract" class="btn btn-primary text-bold">
            <i class="fa fa-check mr-2"></i>Create Contract
        </button>
        <button type="button" class="btn btn-light" data-dismiss="modal">
            <i class="fa fa-times mr-2"></i>Cancel
        </button>
    </div>
</form>

<script>
var contractTemplates = <?= $templates_json ?>;

$(document).on('change', '#contractTemplateSelect', function() {
    var id = $(this).val();
    if (!id || !contractTemplates[id]) return;
    var d = contractTemplates[id];

    if (d.contract_template_type) {
        $('#contractTypeField').val(d.contract_template_type).trigger('change');
    }
    if (d.contract_template_renewal_frequency) {
        $('#contractRenewalField').val(d.contract_template_renewal_frequency).trigger('change');
    }

    var slaMap = {
        contractSlaLowId:    d.contract_template_sla_low_id,
        contractSlaMediumId: d.contract_template_sla_medium_id,
        contractSlaHighId:   d.contract_template_sla_high_id,
    };
    $.each(slaMap, function(elId, val) {
        if (val) { $('#' + elId).val(val).trigger('change'); }
    });

    var fields = {
        rateStd:      d.contract_template_rate_standard,
        rateAH:       d.contract_template_rate_after_hours,
        netTerms:     d.contract_template_net_terms,
        supportHours: d.contract_template_support_hours
    };
    $.each(fields, function(elId, val) {
        if (val !== null && val !== undefined && val !== '') {
            $('#' + elId).val(val);
        }
    });
});
</script>

<?php require_once '../../../includes/modal_footer.php'; ?>
