<?php

// Default Column Sort by Filter
$sort = "contract_template_name";
$order = "ASC";

require_once "includes/inc_all_admin.php";

// Search query
$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM contract_templates
    WHERE contract_template_archived_at IS NULL
    AND (contract_template_name LIKE '%$q%' OR contract_template_type LIKE '%$q%')
    ORDER BY $sort $order LIMIT $record_from, $record_to"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

<div class="card card-dark">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-file-contract mr-2"></i>Contract Templates</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary ajax-modal" data-modal-url="modals/contract_template/contract_template_add.php" data-modal-size="lg">
                <i class="fas fa-plus mr-2"></i>New Template
            </button>
        </div>
    </div>
    <div class="card-body">

        <form autocomplete="off">
            <div class="input-group">
                <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search templates">
                <div class="input-group-append">
                    <button class="btn btn-secondary"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        <hr>

        <div class="table-responsive-sm">
            <table class="table table-striped table-borderless table-hover">
                <thead class="text-dark <?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                    <tr>
                        <th>Template Name</th>
                        <th>Type</th>
                        <th>Renewal</th>
                        <th>SLA Response (L/M/H hrs)</th>
                        <th>SLA Resolution (L/M/H hrs)</th>
                        <th>Standard Rate</th>
                        <th>After-Hours Rate</th>
                        <th>Net Terms</th>
                        <th>Support Hours</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($sql)) {
                        $id           = intval($row['contract_template_id']);
                        $name         = nullable_htmlentities($row['contract_template_name']);
                        $description  = nullable_htmlentities($row['contract_template_description']);
                        $type         = nullable_htmlentities($row['contract_template_type']);
                        $renewal      = nullable_htmlentities($row['contract_template_renewal_frequency']);
                        $sla_low_resp = nullable_htmlentities($row['contract_template_sla_low_response_time']);
                        $sla_med_resp = nullable_htmlentities($row['contract_template_sla_medium_response_time']);
                        $sla_high_resp= nullable_htmlentities($row['contract_template_sla_high_response_time']);
                        $sla_low_res  = nullable_htmlentities($row['contract_template_sla_low_resolution_time']);
                        $sla_med_res  = nullable_htmlentities($row['contract_template_sla_medium_resolution_time']);
                        $sla_high_res = nullable_htmlentities($row['contract_template_sla_high_resolution_time']);
                        $rate_std     = nullable_htmlentities($row['contract_template_rate_standard']);
                        $rate_ah      = nullable_htmlentities($row['contract_template_rate_after_hours']);
                        $net_terms    = nullable_htmlentities($row['contract_template_net_terms']);
                        $support_hrs  = nullable_htmlentities($row['contract_template_support_hours']);
                        $created      = nullable_htmlentities($row['contract_template_created_at']);
                        $updated      = nullable_htmlentities($row['contract_template_updated_at']);
                    ?>
                    <tr>
                        <td>
                            <span class="text-bold"><i class="fas fa-fw fa-file-contract text-dark mr-1"></i><?= $name ?></span>
                            <?php if ($description) { ?>
                                <div class="mt-1 text-secondary small"><?= $description ?></div>
                            <?php } ?>
                        </td>
                        <td><?= $type ?></td>
                        <td><?= $renewal ?: '-' ?></td>
                        <td><?= "$sla_low_resp / $sla_med_resp / $sla_high_resp" ?></td>
                        <td><?= "$sla_low_res / $sla_med_res / $sla_high_res" ?></td>
                        <td><?= $rate_std ? '$' . number_format($rate_std, 2) . '/hr' : '-' ?></td>
                        <td><?= $rate_ah  ? '$' . number_format($rate_ah, 2)  . '/hr' : '-' ?></td>
                        <td><?= $net_terms ?: '-' ?></td>
                        <td><?= $support_hrs ?: '-' ?></td>
                        <td><?= $created ?></td>
                        <td><?= $updated ?: '-' ?></td>
                        <td>
                            <div class="dropdown dropleft text-center">
                                <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item ajax-modal" href="#"
                                        data-modal-size="lg"
                                        data-modal-url="modals/contract_template/contract_template_edit.php?id=<?= $id ?>">
                                        <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger text-bold confirm-link"
                                        href="post.php?delete_contract_template=<?= $id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                                        <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <br>
        </div>
        <?php require_once "../includes/filter_footer.php"; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
