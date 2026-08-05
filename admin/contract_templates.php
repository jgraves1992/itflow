<?php

$sort = "contract_template_name";
$order = "ASC";

require_once "includes/inc_all_admin.php";

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS ct.*,
        sla_low.sla_name    AS sla_low_name,
        sla_med.sla_name    AS sla_medium_name,
        sla_high.sla_name   AS sla_high_name,
        sla_urg.sla_name    AS sla_urgent_name
    FROM contract_templates ct
    LEFT JOIN slas sla_low  ON sla_low.sla_id  = ct.contract_template_sla_low_id
    LEFT JOIN slas sla_med  ON sla_med.sla_id  = ct.contract_template_sla_medium_id
    LEFT JOIN slas sla_high ON sla_high.sla_id = ct.contract_template_sla_high_id
    LEFT JOIN slas sla_urg  ON sla_urg.sla_id  = ct.contract_template_sla_urgent_id
    WHERE ct.contract_template_archived_at IS NULL
      AND (ct.contract_template_name LIKE '%$q%' OR ct.contract_template_type LIKE '%$q%')
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
                <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(escapeHtml($q)); } ?>" placeholder="Search templates">
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
                        <th>SLA — Low</th>
                        <th>SLA — Medium</th>
                        <th>SLA — High</th>
                        <th>SLA — Urgent</th>
                        <th>Std Rate</th>
                        <th>AH Rate</th>
                        <th>Support Hours</th>
                        <th>Net Terms</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($sql)) {
                        $id          = intval($row['contract_template_id']);
                        $name        = escapeHtml($row['contract_template_name']);
                        $type        = escapeHtml($row['contract_template_type']);
                        $renewal     = escapeHtml($row['contract_template_renewal_frequency']);
                        $rate_std    = floatval($row['contract_template_rate_standard']);
                        $rate_ah     = floatval($row['contract_template_rate_after_hours']);
                        $support_hrs = escapeHtml($row['contract_template_support_hours']);
                        $net_terms   = escapeHtml($row['contract_template_net_terms']);
                        $sla_low     = escapeHtml($row['sla_low_name']    ?? '');
                        $sla_medium  = escapeHtml($row['sla_medium_name'] ?? '');
                        $sla_high    = escapeHtml($row['sla_high_name']   ?? '');
                        $sla_urgent  = escapeHtml($row['sla_urgent_name'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <a class="text-bold ajax-modal" href="#"
                                data-modal-size="lg"
                                data-modal-url="modals/contract_template/contract_template_edit.php?id=<?= $id ?>">
                                <i class="fas fa-fw fa-file-contract text-dark"></i> <?= $name ?>
                            </a>
                            <div class="mt-1 text-secondary small"><?= escapeHtml($row['contract_template_description']) ?></div>
                        </td>
                        <td><?= $type ?: '-' ?></td>
                        <td><?= $renewal ?: '-' ?></td>
                        <td><?= $sla_low ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= $sla_medium ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= $sla_high ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= $sla_urgent ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= $rate_std ? '$' . number_format($rate_std, 2) : '-' ?></td>
                        <td><?= $rate_ah  ? '$' . number_format($rate_ah, 2)  : '-' ?></td>
                        <td><?= $support_hrs ?: '-' ?></td>
                        <td><?= $net_terms ?: '-' ?></td>
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
                                    <a class="dropdown-item text-danger text-bold confirm-link" href="post.php?delete_contract_template=<?= $id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                                        <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php require_once "../includes/filter_footer.php"; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
