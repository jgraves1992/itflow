<?php

require_once "includes/inc_all_custom.php";

enforceUserPermission('module_client');

$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search        = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = "WHERE lead_archived_at IS NULL";

if ($status_filter) {
    $s = mysqli_real_escape_string($mysqli, $status_filter);
    $where .= " AND lead_status = '$s'";
}

if ($search) {
    $q = mysqli_real_escape_string($mysqli, $search);
    $where .= " AND (lead_name LIKE '%$q%' OR lead_email LIKE '%$q%' OR lead_company LIKE '%$q%')";
}

$records_per_page = 25;
$page             = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$record_from      = ($page - 1) * $records_per_page;

$count_query   = mysqli_query($mysqli, "SELECT COUNT(*) AS cnt FROM marketing_leads $where");
if ($count_query === false) {
    echo '<div class="alert alert-danger m-3"><strong>Database error:</strong> ' . htmlspecialchars(mysqli_error($mysqli)) . '<br>Did you run <code>db_marketing.sql</code>?</div>';
    require_once "../../includes/footer.php";
    exit;
}
$count_result  = mysqli_fetch_assoc($count_query);
$record_count  = intval($count_result['cnt']);

$sql = mysqli_query($mysqli, "SELECT * FROM marketing_leads $where ORDER BY lead_created_at DESC LIMIT $record_from, $records_per_page");

$status_colors = [
    'new'       => 'secondary',
    'contacted' => 'info',
    'qualified' => 'primary',
    'converted' => 'success',
    'lost'      => 'danger',
];

?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-tag mr-2"></i>Marketing Leads</h3>
        <div class="card-tools">
            <form action="post.php" method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="sync_marketing_leads" value="1">
                <button type="submit" class="btn btn-default btn-sm" title="Import clients from ITFlow that aren't in marketing yet">
                    <i class="fas fa-sync-alt"></i> Sync from ITFlow
                </button>
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addLeadModal">
                <i class="fas fa-plus"></i> New Lead
            </button>
        </div>
    </div>

    <div class="card-body pb-0">
        <form method="GET" class="row">
            <div class="col-md-5">
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" name="search"
                           placeholder="Search name, email, company..."
                           value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
                    <div class="input-group-append">
                        <button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach (['new', 'contacted', 'qualified', 'converted', 'lost'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($search || $status_filter): ?>
            <div class="col-md-2">
                <a href="marketing_leads.php" class="btn btn-default btn-sm">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Sequences</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($sql) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No leads found. Click <strong>New Lead</strong> to add your first prospect.
                    </td>
                </tr>
            <?php endif; ?>
            <?php while ($row = mysqli_fetch_assoc($sql)):
                $lead_id      = intval($row['lead_id']);
                $lead_name    = nullable_htmlentities($row['lead_name']);
                $lead_email   = nullable_htmlentities($row['lead_email']);
                $lead_company = nullable_htmlentities($row['lead_company']);
                $lead_status  = $row['lead_status'];
                $lead_source  = nullable_htmlentities($row['lead_source']);
                $badge_color  = $status_colors[$lead_status] ?? 'secondary';

                $active_seq = intval(mysqli_fetch_assoc(mysqli_query($mysqli,
                    "SELECT COUNT(*) AS cnt FROM marketing_enrollments
                     WHERE enrollment_lead_id = $lead_id AND enrollment_status = 'active'"))['cnt']);
            ?>
                <tr>
                    <td>
                        <a href="marketing_lead_details.php?id=<?= $lead_id ?>"><?= $lead_name ?></a>
                        <?php if ($row['lead_unsubscribed']): ?>
                            <span class="badge badge-warning ml-1" title="Unsubscribed from emails">Unsub</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $lead_company ?></td>
                    <td><a href="mailto:<?= $lead_email ?>"><?= $lead_email ?></a></td>
                    <td><span class="badge badge-<?= $badge_color ?>"><?= ucfirst($lead_status) ?></span></td>
                    <td><?= $lead_source ?></td>
                    <td>
                        <?php if ($active_seq > 0): ?>
                            <span class="badge badge-info"><?= $active_seq ?> active</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($row['lead_created_at'])) ?></td>
                    <td class="text-right">
                        <a href="marketing_lead_details.php?id=<?= $lead_id ?>" class="btn btn-xs btn-default" title="View"><i class="fas fa-eye"></i></a>
                        <?php if (lookupUserPermission('module_client') >= 2): ?>
                        <a href="post.php"
                           onclick="if(!confirm('Archive this lead?')) return false; this.href='post.php?archive_marketing_lead=1&id=<?= $lead_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>';"
                           class="btn btn-xs btn-default text-danger" title="Archive">
                            <i class="fas fa-archive"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($record_count > $records_per_page): ?>
    <div class="card-footer clearfix">
        <?php
        $total_pages = ceil($record_count / $records_per_page);
        for ($i = 1; $i <= $total_pages; $i++):
            $active_class = ($i === $page) ? 'btn-primary' : 'btn-default';
        ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
           class="btn btn-sm <?= $active_class ?>"><?= $i ?></a>
        <?php endfor; ?>
        <span class="text-muted ml-2"><?= $record_count ?> total</span>
    </div>
    <?php endif; ?>
</div>

<!-- Add Lead Modal -->
<div class="modal fade" id="addLeadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="add_marketing_lead" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Add Lead</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lead_name" required placeholder="Jane Smith">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="lead_email" required placeholder="jane@example.com">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" class="form-control" name="lead_company" placeholder="Acme Corp">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" class="form-control" name="lead_phone" placeholder="555-555-5555">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="lead_status">
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="converted">Converted</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Source</label>
                                <input type="text" class="form-control" name="lead_source" placeholder="Website, Referral...">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="lead_notes" rows="3" placeholder="Any additional context..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Add Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
