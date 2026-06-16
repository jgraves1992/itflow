<?php

// Default Column Sortby/Order Filter
$sort = "contract_start_date";
$order = "DESC";

// If client_id is in URI then show client Side Bar and client header
if (isset($_GET['client_id'])) {
    require_once "includes/inc_all_client.php";
    $client_query = "AND contract_client_id = $client_id";
    $client_url = "client_id=$client_id&";
} else {
    require_once "includes/inc_all.php";
    $client_query = '';
    $client_url = '';
}

// Perms
enforceUserPermission('module_sales');

// Status filter
$status_filter = '';
if (!empty($_GET['status'])) {
    $status = sanitizeInput($_GET['status']);
    $status_filter = "AND contract_status = '$status'";
}

// Status counts
$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS num FROM contracts WHERE contract_status = 'Active' AND contract_archived_at IS NULL $client_query"));
$num_active = intval($row['num']);

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS num FROM contracts WHERE contract_status = 'Pending' AND contract_archived_at IS NULL $client_query"));
$num_pending = intval($row['num']);

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS num FROM contracts WHERE contract_status = 'Expired' AND contract_archived_at IS NULL $client_query"));
$num_expired = intval($row['num']);

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS num FROM contracts WHERE contract_status = 'Terminated' AND contract_archived_at IS NULL $client_query"));
$num_terminated = intval($row['num']);

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM contracts
    LEFT JOIN clients ON contract_client_id = client_id
    WHERE (contract_name LIKE '%$q%' OR contract_type LIKE '%$q%' OR contract_status LIKE '%$q%' OR client_name LIKE '%$q%')
    AND contract_archived_at IS NULL
    $status_filter
    $access_permission_query
    $client_query
    ORDER BY $sort $order LIMIT $record_from, $record_to"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

<div class="card card-dark">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-file-contract mr-2"></i>Contracts</h3>
        <div class="card-tools">
            <?php if (lookupUserPermission("module_sales") >= 2) { ?>
                <button type="button" class="btn btn-primary ajax-modal"
                    data-modal-url="modals/contract/contract_add.php?<?= $client_url ?>"
                    data-modal-size="lg">
                    <i class="fas fa-plus mr-2"></i>New Contract
                </button>
            <?php } ?>
        </div>
    </div>

    <div class="card-body">

        <!-- Status summary cards -->
        <div class="row mb-3">
            <div class="col-sm-3">
                <a href="?<?= $client_url ?>status=Active" class="small-box bg-success text-decoration-none">
                    <div class="inner">
                        <h4><?= $num_active ?></h4>
                        <p>Active</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </a>
            </div>
            <div class="col-sm-3">
                <a href="?<?= $client_url ?>status=Pending" class="small-box bg-warning text-decoration-none">
                    <div class="inner">
                        <h4><?= $num_pending ?></h4>
                        <p>Pending</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </a>
            </div>
            <div class="col-sm-3">
                <a href="?<?= $client_url ?>status=Expired" class="small-box bg-secondary text-decoration-none">
                    <div class="inner">
                        <h4><?= $num_expired ?></h4>
                        <p>Expired</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-times"></i></div>
                </a>
            </div>
            <div class="col-sm-3">
                <a href="?<?= $client_url ?>status=Terminated" class="small-box bg-danger text-decoration-none">
                    <div class="inner">
                        <h4><?= $num_terminated ?></h4>
                        <p>Terminated</p>
                    </div>
                    <div class="icon"><i class="fas fa-ban"></i></div>
                </a>
            </div>
        </div>

        <form class="mb-4" autocomplete="off">
            <?php if ($client_url) { ?>
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <?php } ?>
            <?php if (!empty($_GET['status'])) { ?>
                <input type="hidden" name="status" value="<?= nullable_htmlentities($_GET['status']) ?>">
            <?php } ?>
            <div class="row">
                <div class="col-sm-4">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q"
                            value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>"
                            placeholder="Search Contracts">
                        <div class="input-group-append">
                            <button class="btn btn-primary"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <?php if (!empty($_GET['status'])) { ?>
                    <div class="col-sm-4 pt-2">
                        <a href="?<?= $client_url ?>" class="text-secondary">
                            <i class="fas fa-times mr-1"></i>Clear filter: <?= nullable_htmlentities($_GET['status']) ?>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </form>
        <hr>

        <div class="table-responsive">
            <table class="table table-striped table-borderless table-hover">
                <thead class="text-dark <?php if ($num_rows[0] == 0) { echo "d-none"; } ?> text-nowrap">
                    <tr>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_name&order=<?= $disp ?>">
                                Name <?php if ($sort == 'contract_name') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <?php if (!$client_url) { ?>
                            <th>
                                <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=client_name&order=<?= $disp ?>">
                                    Client <?php if ($sort == 'client_name') { echo $order_icon; } ?>
                                </a>
                            </th>
                        <?php } ?>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_type&order=<?= $disp ?>">
                                Type <?php if ($sort == 'contract_type') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_start_date&order=<?= $disp ?>">
                                Start <?php if ($sort == 'contract_start_date') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_end_date&order=<?= $disp ?>">
                                End <?php if ($sort == 'contract_end_date') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <th>SLA (L/M/H Response hrs)</th>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_renewal_frequency&order=<?= $disp ?>">
                                Renewal <?php if ($sort == 'contract_renewal_frequency') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <th>
                            <a class="text-dark" href="?<?= $url_query_strings_sort ?>&sort=contract_status&order=<?= $disp ?>">
                                Status <?php if ($sort == 'contract_status') { echo $order_icon; } ?>
                            </a>
                        </th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                while ($row = mysqli_fetch_assoc($sql)) {
                    $contract_id       = intval($row['contract_id']);
                    $contract_name     = nullable_htmlentities($row['contract_name']);
                    $contract_status   = nullable_htmlentities($row['contract_status']);
                    $contract_type     = nullable_htmlentities($row['contract_type']);
                    $contract_start    = nullable_htmlentities($row['contract_start_date']);
                    $contract_end      = nullable_htmlentities($row['contract_end_date']);
                    $contract_renewal  = nullable_htmlentities($row['contract_renewal_frequency']);
                    $sla_low_resp      = nullable_htmlentities($row['contract_sla_low_response_time']);
                    $sla_med_resp      = nullable_htmlentities($row['contract_sla_medium_response_time']);
                    $sla_high_resp     = nullable_htmlentities($row['contract_sla_high_response_time']);
                    $row_client_id     = intval($row['client_id']);
                    $row_client_name   = nullable_htmlentities($row['client_name']);

                    switch ($contract_status) {
                        case 'Active':      $badge = 'success'; break;
                        case 'Pending':     $badge = 'warning text-dark'; break;
                        case 'Expired':     $badge = 'secondary'; break;
                        case 'Terminated':  $badge = 'danger'; break;
                        default:            $badge = 'secondary';
                    }

                    $sla_display = ($sla_low_resp || $sla_med_resp || $sla_high_resp)
                        ? "$sla_low_resp / $sla_med_resp / $sla_high_resp"
                        : "-";

                ?>
                    <tr>
                        <td class="text-bold">
                            <a href="contract.php?<?= $client_url ?>contract_id=<?= $contract_id ?>">
                                <i class="fas fa-fw fa-file-contract text-dark mr-1"></i><?= $contract_name ?>
                            </a>
                        </td>
                        <?php if (!$client_url) { ?>
                            <td class="text-bold">
                                <a href="contracts.php?client_id=<?= $row_client_id ?>"><?= $row_client_name ?></a>
                            </td>
                        <?php } ?>
                        <td><?= $contract_type ?></td>
                        <td><?= $contract_start ?: '-' ?></td>
                        <td><?= $contract_end ?: '-' ?></td>
                        <td><?= $sla_display ?></td>
                        <td><?= $contract_renewal ?: '-' ?></td>
                        <td>
                            <span class="badge badge-<?= $badge ?> p-2"><?= $contract_status ?></span>
                        </td>
                        <td>
                            <div class="dropdown dropleft text-center">
                                <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="contract.php?<?= $client_url ?>contract_id=<?= $contract_id ?>">
                                        <i class="fas fa-fw fa-eye mr-2"></i>View
                                    </a>
                                    <?php if (lookupUserPermission("module_sales") >= 2) { ?>
                                        <a class="dropdown-item ajax-modal" href="#"
                                            data-modal-size="lg"
                                            data-modal-url="modals/contract/contract_edit.php?id=<?= $contract_id ?>">
                                            <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <?php if (lookupUserPermission("module_sales") >= 3) { ?>
                                            <a class="dropdown-item text-danger text-bold confirm-link"
                                                href="post.php?archive_contract=<?= $contract_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                                                <i class="fas fa-fw fa-archive mr-2"></i>Archive
                                            </a>
                                        <?php } ?>
                                    <?php } ?>
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
