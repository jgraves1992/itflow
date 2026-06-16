<?php

// If client_id is in URI then show client Side Bar and client header
if (isset($_GET['client_id'])) {
    require_once "includes/inc_all_client.php";
} else {
    require_once "includes/inc_all.php";
}

// Perms
enforceUserPermission('module_sales');

if (!isset($_GET['contract_id'])) {
    redirect("contracts.php");
}

$contract_id = intval($_GET['contract_id']);

$sql = mysqli_query(
    $mysqli,
    "SELECT * FROM contracts
    LEFT JOIN clients ON contract_client_id = client_id
    WHERE contract_id = $contract_id
    AND contract_archived_at IS NULL
    $access_permission_query
    LIMIT 1"
);

if (mysqli_num_rows($sql) == 0) {
    $backlink = isset($_GET['client_id']) ? "contracts.php?client_id=$client_id" : "contracts.php";
    echo "<h1 class='text-secondary pt-5 text-center'>Contract not found<br><small><a href='$backlink'><i class='fas fa-arrow-left mr-2'></i>Back to Contracts</a></small></h1>";
    require_once "../includes/footer.php";
    exit();
}

$row = mysqli_fetch_assoc($sql);

$contract_id          = intval($row['contract_id']);
$contract_name        = nullable_htmlentities($row['contract_name']);
$contract_status      = nullable_htmlentities($row['contract_status']);
$contract_type        = nullable_htmlentities($row['contract_type']);
$contract_details     = $row['contract_details'];
$contract_start       = nullable_htmlentities($row['contract_start_date']);
$contract_end         = nullable_htmlentities($row['contract_end_date']);
$contract_renewal     = nullable_htmlentities($row['contract_renewal_frequency']);
$contract_net_terms   = nullable_htmlentities($row['contract_net_terms']);
$contract_support_hrs = nullable_htmlentities($row['contract_support_hours']);
$contract_rate_std    = floatval($row['contract_rate_standard']);
$contract_rate_ah     = floatval($row['contract_rate_after_hours']);
$sla_low_resp         = nullable_htmlentities($row['contract_sla_low_response_time']);
$sla_low_res          = nullable_htmlentities($row['contract_sla_low_resolution_time']);
$sla_med_resp         = nullable_htmlentities($row['contract_sla_medium_response_time']);
$sla_med_res          = nullable_htmlentities($row['contract_sla_medium_resolution_time']);
$sla_high_resp        = nullable_htmlentities($row['contract_sla_high_response_time']);
$sla_high_res         = nullable_htmlentities($row['contract_sla_high_resolution_time']);
$snap_client_name     = nullable_htmlentities($row['contract_client_name']);
$snap_client_addr     = nullable_htmlentities($row['contract_client_address']);
$snap_client_email    = nullable_htmlentities($row['contract_client_email']);
$snap_client_phone    = nullable_htmlentities($row['contract_client_phone']);
$snap_contact_name    = nullable_htmlentities($row['contract_contact_name']);
$snap_agent_name      = nullable_htmlentities($row['contract_agent_name']);
$contract_created_at  = nullable_htmlentities($row['contract_created_at']);
$contract_updated_at  = nullable_htmlentities($row['contract_updated_at']);
$row_client_id        = intval($row['client_id']);
$row_client_name      = nullable_htmlentities($row['client_name']);

$client_url = isset($_GET['client_id']) ? "client_id=$client_id&" : "";

switch ($contract_status) {
    case 'Active':      $badge = 'success'; break;
    case 'Pending':     $badge = 'warning text-dark'; break;
    case 'Expired':     $badge = 'secondary'; break;
    case 'Terminated':  $badge = 'danger'; break;
    default:            $badge = 'secondary';
}

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="contracts.php?<?= $client_url ?>">Contracts</a>
        </li>
        <li class="breadcrumb-item active"><?= $contract_name ?></li>
    </ol>
</nav>

<!-- Header Card -->
<div class="card card-dark mb-3">
    <div class="card-header py-2">
        <h3 class="card-title mt-2">
            <i class="fa fa-fw fa-file-contract mr-2"></i><?= $contract_name ?>
            <span class="badge badge-<?= $badge ?> ml-2"><?= $contract_status ?></span>
        </h3>
        <div class="card-tools">
            <?php if (lookupUserPermission("module_sales") >= 2) { ?>
                <button type="button" class="btn btn-secondary ajax-modal"
                    data-modal-size="lg"
                    data-modal-url="modals/contract/contract_edit.php?id=<?= $contract_id ?>">
                    <i class="fas fa-edit mr-2"></i>Edit
                </button>

                <!-- Status Change -->
                <div class="btn-group ml-1">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-exchange-alt mr-2"></i>Set Status
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="post.php?set_contract_status=<?= $contract_id ?>&status=Active&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                            <i class="fas fa-fw fa-check-circle text-success mr-2"></i>Active
                        </a>
                        <a class="dropdown-item" href="post.php?set_contract_status=<?= $contract_id ?>&status=Pending&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                            <i class="fas fa-fw fa-clock text-warning mr-2"></i>Pending
                        </a>
                        <a class="dropdown-item" href="post.php?set_contract_status=<?= $contract_id ?>&status=Expired&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                            <i class="fas fa-fw fa-calendar-times text-secondary mr-2"></i>Expired
                        </a>
                        <a class="dropdown-item" href="post.php?set_contract_status=<?= $contract_id ?>&status=Terminated&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                            <i class="fas fa-fw fa-ban text-danger mr-2"></i>Terminated
                        </a>
                    </div>
                </div>

                <?php if (lookupUserPermission("module_sales") >= 3) { ?>
                    <a href="post.php?archive_contract=<?= $contract_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                        class="btn btn-danger ml-1 confirm-link"
                        title="Archive Contract">
                        <i class="fas fa-archive mr-2"></i>Archive
                    </a>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>

<div class="row">

    <!-- Contract Info -->
    <div class="col-lg-6">
        <div class="card card-outline card-dark">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-fw fa-info-circle mr-2"></i>Contract Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th width="40%">Type</th>
                        <td><?= $contract_type ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td><?= $contract_start ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>End Date</th>
                        <td><?= $contract_end ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Renewal</th>
                        <td><?= $contract_renewal ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Net Terms</th>
                        <td><?= $contract_net_terms ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Support Hours</th>
                        <td><?= $contract_support_hrs ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Standard Rate</th>
                        <td><?= $contract_rate_std ? '$' . number_format($contract_rate_std, 2) . '/hr' : '-' ?></td>
                    </tr>
                    <tr>
                        <th>After-Hours Rate</th>
                        <td><?= $contract_rate_ah ? '$' . number_format($contract_rate_ah, 2) . '/hr' : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td><?= $contract_created_at ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>Updated</th>
                        <td><?= $contract_updated_at ?: '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- SLA -->
    <div class="col-lg-6">
        <div class="card card-outline card-dark">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-fw fa-stopwatch mr-2"></i>SLA Times (hours)</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <thead class="text-dark">
                        <tr>
                            <th>Priority</th>
                            <th>Response</th>
                            <th>Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-success">Low</span></td>
                            <td><?= $sla_low_resp ?: '-' ?> hrs</td>
                            <td><?= $sla_low_res ?: '-' ?> hrs</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning text-dark">Medium</span></td>
                            <td><?= $sla_med_resp ?: '-' ?> hrs</td>
                            <td><?= $sla_med_res ?: '-' ?> hrs</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">High</span></td>
                            <td><?= $sla_high_resp ?: '-' ?> hrs</td>
                            <td><?= $sla_high_res ?: '-' ?> hrs</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Client Snapshot -->
<div class="card card-outline card-dark">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-fw fa-camera mr-2"></i>Client Snapshot (at time of signing)</h3>
        <?php if ($row_client_id) { ?>
            <div class="card-tools">
                <a href="client_overview.php?client_id=<?= $row_client_id ?>" class="btn btn-sm btn-secondary">
                    <i class="fas fa-user mr-1"></i>View Live Client
                </a>
            </div>
        <?php } ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Client Name</strong><br><?= $snap_client_name ?: '-' ?></div>
            <div class="col-md-3"><strong>Address</strong><br><?= nl2br($snap_client_addr) ?: '-' ?></div>
            <div class="col-md-3"><strong>Email</strong><br><?= $snap_client_email ?: '-' ?></div>
            <div class="col-md-3"><strong>Phone</strong><br><?= $snap_client_phone ?: '-' ?></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3"><strong>Contact</strong><br><?= $snap_contact_name ?: '-' ?></div>
            <div class="col-md-3"><strong>Agent</strong><br><?= $snap_agent_name ?: '-' ?></div>
        </div>
    </div>
</div>

<!-- Contract Details / Notes -->
<?php if (!empty($contract_details)) { ?>
    <div class="card card-outline card-dark">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-fw fa-align-left mr-2"></i>Contract Terms & Details</h3>
        </div>
        <div class="card-body">
            <?= $contract_details ?>
        </div>
    </div>
<?php } ?>

<?php require_once "../includes/footer.php"; ?>
