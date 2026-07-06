<?php

require_once "includes/inc_all_custom.php";

enforceUserPermission('module_client');

$show_archived = !empty($_GET['archived']);
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search        = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = $show_archived ? "WHERE lead_archived_at IS NOT NULL" : "WHERE lead_archived_at IS NULL";

// sanitizeInput() already escapes for SQL — do not re-escape, or quotes/backslashes break the match
if ($status_filter) {
    $where .= " AND lead_status = '$status_filter'";
}

if ($search) {
    $where .= " AND (lead_name LIKE '%$search%' OR lead_email LIKE '%$search%' OR lead_company LIKE '%$search%')";
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

$can_enroll = lookupUserPermission('module_client') >= 2;

$sequences_active_sql = mysqli_query($mysqli,
    "SELECT sequence_id, sequence_name FROM marketing_sequences
     WHERE sequence_active = 1 AND sequence_archived_at IS NULL
     ORDER BY sequence_name");

?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-tag mr-2"></i>Marketing Leads
            <?php if ($show_archived): ?>
            <span class="badge badge-secondary ml-1">Archived</span>
            <?php endif; ?>
        </h3>
        <div class="card-tools">
            <form action="post.php" method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="sync_marketing_leads" value="1">
                <button type="submit" class="btn btn-default btn-sm" title="Import clients from ITFlow that aren't in marketing yet">
                    <i class="fas fa-sync-alt"></i> Sync from ITFlow
                </button>
            </form>
            <?php if (!$show_archived): ?>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addLeadModal">
                <i class="fas fa-plus"></i> New Lead
            </button>
            <?php if ($can_enroll): ?>
            <button type="button" id="bulkEnrollBtn" class="btn btn-default btn-sm" disabled data-toggle="modal" data-target="#bulkEnrollModal">
                <i class="fas fa-stream"></i> Enroll Selected (<span class="selectedCount">0</span>)
            </button>
            <?php endif; ?>
            <a href="marketing_leads.php?archived=1" class="btn btn-default btn-sm" title="View archived leads">
                <i class="fas fa-archive"></i> Archived
            </a>
            <?php else: ?>
            <a href="marketing_leads.php" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Active Leads
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body pb-0">
        <form method="GET" class="row">
            <?php if ($show_archived): ?>
            <input type="hidden" name="archived" value="1">
            <?php endif; ?>
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
            <?php if (!$show_archived): ?>
            <div class="col-md-3">
                <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach (['new', 'contacted', 'qualified', 'converted', 'lost'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($search || $status_filter): ?>
            <div class="col-md-2">
                <a href="marketing_leads.php<?= $show_archived ? '?archived=1' : '' ?>" class="btn btn-default btn-sm">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <?php if ($can_enroll && !$show_archived): ?>
                    <th><input type="checkbox" id="selectAllLeads"></th>
                    <?php endif; ?>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Source</th>
                    <?php if (!$show_archived): ?><th>Sequences</th><?php endif; ?>
                    <th><?= $show_archived ? 'Archived' : 'Added' ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($sql) === 0): ?>
                <tr>
                    <td colspan="<?= ($can_enroll && !$show_archived) ? 9 : 8 ?>" class="text-center text-muted py-4">
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
                    <?php if ($can_enroll && !$show_archived): ?>
                    <td>
                        <?php if (!$row['lead_unsubscribed']): ?>
                        <input type="checkbox" class="lead-checkbox" value="<?= $lead_id ?>">
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
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
                    <?php if (!$show_archived): ?>
                    <td>
                        <?php if ($active_seq > 0): ?>
                            <span class="badge badge-info"><?= $active_seq ?> active</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td><?= $show_archived
                        ? date('M j, Y', strtotime($row['lead_archived_at']))
                        : date('M j, Y', strtotime($row['lead_created_at'])) ?></td>
                    <td class="text-right">
                        <a href="marketing_lead_details.php?id=<?= $lead_id ?>" class="btn btn-xs btn-default" title="View"><i class="fas fa-eye"></i></a>
                        <?php if (lookupUserPermission('module_client') >= 2): ?>
                        <?php if ($show_archived): ?>
                        <a href="post.php"
                           onclick="if(!confirm('Restore this lead?')) return false; this.href='post.php?unarchive_marketing_lead=1&id=<?= $lead_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>';"
                           class="btn btn-xs btn-default text-success" title="Restore">
                            <i class="fas fa-undo"></i>
                        </a>
                        <?php else: ?>
                        <a href="post.php"
                           onclick="if(!confirm('Archive this lead?')) return false; this.href='post.php?archive_marketing_lead=1&id=<?= $lead_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>';"
                           class="btn btn-xs btn-default text-danger" title="Archive">
                            <i class="fas fa-archive"></i>
                        </a>
                        <?php endif; ?>
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
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?><?= $show_archived ? '&archived=1' : '' ?>"
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

<?php if ($can_enroll && !$show_archived): ?>
<!-- Bulk Enroll Modal -->
<div class="modal fade" id="bulkEnrollModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="bulkEnrollForm" action="post.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="bulk_enroll_marketing_leads" value="1">
                <div id="bulkEnrollLeadIds"></div>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-stream mr-2"></i>Enroll Selected Leads</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p><strong class="selectedCount">0</strong> lead(s) selected.</p>
                    <div class="form-group">
                        <label>Sequence <span class="text-danger">*</span></label>
                        <select class="form-control" name="sequence_id" required>
                            <option value="">- Select Sequence -</option>
                            <?php while ($seq = mysqli_fetch_assoc($sequences_active_sql)): ?>
                            <option value="<?= intval($seq['sequence_id']) ?>"><?= nullable_htmlentities($seq['sequence_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <small class="text-muted">Leads already enrolled in the chosen sequence will be skipped automatically.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll  = document.getElementById('selectAllLeads');
    var checkboxes = document.querySelectorAll('.lead-checkbox');
    var bulkBtn    = document.getElementById('bulkEnrollBtn');
    var countSpans = document.querySelectorAll('.selectedCount');

    function updateBulkUI() {
        var checkedCount = document.querySelectorAll('.lead-checkbox:checked').length;
        bulkBtn.disabled = checkedCount === 0;
        countSpans.forEach(function (span) { span.textContent = checkedCount; });
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBulkUI();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateBulkUI);
    });

    document.getElementById('bulkEnrollForm').addEventListener('submit', function () {
        var container = document.getElementById('bulkEnrollLeadIds');
        container.innerHTML = '';
        document.querySelectorAll('.lead-checkbox:checked').forEach(function (cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'lead_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
    });
});
</script>
<?php endif; ?>

<?php require_once "../../includes/footer.php"; ?>
