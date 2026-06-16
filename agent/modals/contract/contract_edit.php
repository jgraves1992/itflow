<?php

require_once '../../../includes/modal_header.php';

$contract_id = intval($_GET['id'] ?? 0);

$sql = mysqli_query($mysqli, "SELECT * FROM contracts WHERE contract_id = $contract_id AND contract_archived_at IS NULL LIMIT 1");

if (mysqli_num_rows($sql) == 0) {
    echo '<div class="modal-body"><p class="text-danger">Contract not found.</p></div>';
    require_once '../../../includes/modal_footer.php';
    exit();
}

$row = mysqli_fetch_assoc($sql);

$contract_types_array    = ['Fully Managed', 'Partially Managed', 'Break/Fix'];
$contract_status_array   = ['Pending', 'Active', 'Expired', 'Terminated'];
$renewal_frequency_array = ['Manual', 'Annually', '2 Year', '3 Year', '5 Year', '7 Year'];

ob_start();

?>

<div class="modal-header bg-dark">
    <h5 class="modal-title"><i class="fa fa-fw fa-edit mr-2"></i>Edit Contract</h5>
    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
</div>

<ul class="nav nav-pills nav-justified modal-header">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ce-general">General</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ce-client">Client Info</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ce-sla">SLA</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ce-rates">Rates & Support</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ce-details">Details</a></li>
</ul>

<form action="post.php" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="contract_id" value="<?= $contract_id ?>">

    <div class="modal-body">
        <div class="tab-content">

            <!-- General Tab -->
            <div class="tab-pane fade show active" id="ce-general">

                <div class="form-group">
                    <label>Contract Name <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-contract"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_name"
                            value="<?= nullable_htmlentities($row['contract_name']) ?>" maxlength="255" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Contract Type <strong class="text-danger">*</strong></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_type" required>
                            <option value="">- Select Type -</option>
                            <?php foreach ($contract_types_array as $type) { ?>
                                <option <?= $row['contract_type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
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
                                <option <?= $row['contract_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
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
                            <input type="date" class="form-control" name="contract_start_date"
                                max="2999-12-31" value="<?= nullable_htmlentities($row['contract_start_date']) ?>">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-calendar-times"></i></span>
                            </div>
                            <input type="date" class="form-control" name="contract_end_date"
                                max="2999-12-31" value="<?= nullable_htmlentities($row['contract_end_date']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Renewal Frequency</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-sync-alt"></i></span>
                        </div>
                        <select class="form-control select2" name="contract_renewal_frequency">
                            <option value="">- None -</option>
                            <?php foreach ($renewal_frequency_array as $freq) { ?>
                                <option <?= $row['contract_renewal_frequency'] === $freq ? 'selected' : '' ?>><?= $freq ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Client Info Tab -->
            <div class="tab-pane fade" id="ce-client">
                <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Updating this snapshot does not change the live client record.</p>

                <div class="form-group">
                    <label>Client Name (Snapshot)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_client_name"
                            value="<?= nullable_htmlentities($row['contract_client_name']) ?>" maxlength="255">
                    </div>
                </div>

                <div class="form-group">
                    <label>Client Address (Snapshot)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-map-marker-alt"></i></span>
                        </div>
                        <textarea class="form-control" name="contract_client_address" rows="3"><?= nullable_htmlentities($row['contract_client_address']) ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Client Email (Snapshot)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-envelope"></i></span>
                            </div>
                            <input type="email" class="form-control" name="contract_client_email"
                                value="<?= nullable_htmlentities($row['contract_client_email']) ?>" maxlength="255">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Client Phone (Snapshot)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-phone"></i></span>
                            </div>
                            <input type="text" class="form-control" name="contract_client_phone"
                                value="<?= nullable_htmlentities($row['contract_client_phone']) ?>" maxlength="100">
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
                            <input type="text" class="form-control" name="contract_contact_name"
                                value="<?= nullable_htmlentities($row['contract_contact_name']) ?>" maxlength="255">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Agent Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-user-tie"></i></span>
                            </div>
                            <input type="text" class="form-control" name="contract_agent_name"
                                value="<?= nullable_htmlentities($row['contract_agent_name']) ?>" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLA Tab -->
            <div class="tab-pane fade" id="ce-sla">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Low Priority Response (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-clock"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_low_response_time"
                                value="<?= intval($row['contract_sla_low_response_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Low Priority Resolution (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-hourglass-half"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_low_resolution_time"
                                value="<?= intval($row['contract_sla_low_resolution_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Medium Priority Response (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-clock"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_medium_response_time"
                                value="<?= intval($row['contract_sla_medium_response_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Medium Priority Resolution (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-hourglass-half"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_medium_resolution_time"
                                value="<?= intval($row['contract_sla_medium_resolution_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>High Priority Response (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-bolt"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_high_response_time"
                                value="<?= intval($row['contract_sla_high_response_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>High Priority Resolution (hrs)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-stopwatch"></i></span>
                            </div>
                            <input type="number" class="form-control" name="sla_high_resolution_time"
                                value="<?= intval($row['contract_sla_high_resolution_time']) ?: '' ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rates & Support Tab -->
            <div class="tab-pane fade" id="ce-rates">
                <div class="form-group">
                    <label>Standard Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                        </div>
                        <input type="number" step="0.01" class="form-control" name="contract_rate_standard"
                            value="<?= floatval($row['contract_rate_standard']) ?: '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>After-Hours Hourly Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-moon"></i></span>
                        </div>
                        <input type="number" step="0.01" class="form-control" name="contract_rate_after_hours"
                            value="<?= floatval($row['contract_rate_after_hours']) ?: '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Net Terms</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-file-invoice-dollar"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_net_terms"
                            value="<?= nullable_htmlentities($row['contract_net_terms']) ?>" maxlength="50">
                    </div>
                </div>

                <div class="form-group">
                    <label>Support Hours</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-calendar-check"></i></span>
                        </div>
                        <input type="text" class="form-control" name="contract_support_hours"
                            value="<?= nullable_htmlentities($row['contract_support_hours']) ?>" maxlength="100">
                    </div>
                </div>
            </div>

            <!-- Details Tab -->
            <div class="tab-pane fade" id="ce-details">
                <div class="form-group">
                    <label>Contract Terms & Details</label>
                    <textarea class="form-control tinymce" rows="8" name="contract_details"><?= $row['contract_details'] ?></textarea>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" name="edit_contract" class="btn btn-primary text-bold">
            <i class="fa fa-check mr-2"></i>Save Changes
        </button>
        <button type="button" class="btn btn-light" data-dismiss="modal">
            <i class="fa fa-times mr-2"></i>Cancel
        </button>
    </div>
</form>

<?php require_once '../../../includes/modal_footer.php'; ?>
