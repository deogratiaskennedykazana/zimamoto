<?php
require_once __DIR__ . '/../../functions/audit_functions.php';

$limit  = 50;
$page   = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$filters = [
    'user_id'   => !empty($_GET['user_id'])   ? (int)$_GET['user_id']          : null,
    'module'    => !empty($_GET['module'])     ? $_GET['module']                : null,
    'action'    => !empty($_GET['action'])     ? $_GET['action']                : null,
    'date_from' => !empty($_GET['date_from'])  ? $_GET['date_from']             : null,
    'date_to'   => !empty($_GET['date_to'])    ? $_GET['date_to']               : null,
];
$filters = array_filter($filters);

$logs  = getAuditTrail($conn, $filters, $limit, $offset);
$total = countAuditTrail($conn, $filters);
$pages = ceil($total / $limit);

// Get distinct modules for filter
$modules = [];
$mRes = $conn->query("SELECT DISTINCT module FROM audit_trail ORDER BY module");
if ($mRes) { while ($r = $mRes->fetch_assoc()) $modules[] = $r['module']; }

$users = selectAllUsers($conn);
?>

<div class="card card-secondary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-history mr-2"></i>Audit Trail</h4>
        <div class="card-tools">
            <span class="badge badge-info"><?= number_format($total) ?> records</span>
        </div>
    </div>
    <div class="card-body">

        <!-- Filters -->
        <form method="get" action="./">
            <input type="hidden" name="page" value="audit_trail">
            <div class="row mb-3">
                <div class="col-md-2">
                    <select name="user_id" class="form-control form-control-sm">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="module" class="form-control form-control-sm">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>>
                                <?= ucfirst($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="action" class="form-control form-control-sm">
                        <option value="">All Actions</option>
                        <?php foreach (['create','update','delete','approve','reject','login','logout','export'] as $a): ?>
                            <option value="<?= $a ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>>
                                <?= ucfirst($a) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="<?= $filters['date_from'] ?? '' ?>" placeholder="From date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="<?= $filters['date_to'] ?? '' ?>" placeholder="To date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="./?page=audit_trail" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Date &amp; Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Record</th>
                        <th>Detail</th>
                        <th>IP Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($logs && count($logs) > 0): ?>
                    <?php foreach ($logs as $i => $log): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><small><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                        <td>
                            <strong><?= htmlspecialchars($log['user_name'] ?? 'System') ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '') ?></small>
                        </td>
                        <td>
                            <span class="badge <?= auditActionBadge($log['action']) ?>">
                                <?= strtoupper($log['action']) ?>
                            </span>
                        </td>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($log['module']) ?></span></td>
                        <td><?= $log['record_id'] ? '#' . $log['record_id'] : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <small><?= htmlspecialchars(mb_strimwidth($log['detail'] ?? '', 0, 80, '…')) ?></small>
                        </td>
                        <td><small><?= htmlspecialchars($log['ip_address'] ?? '') ?></small></td>
                        <td>
                            <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                            <button class="btn btn-xs btn-outline-info" onclick="showDiff(<?= $log['id'] ?>)" data-old='<?= htmlspecialchars($log['old_values'] ?? '{}') ?>' data-new='<?= htmlspecialchars($log['new_values'] ?? '{}') ?>'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No audit records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <nav>
            <ul class="pagination pagination-sm">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="./?page=audit_trail&p=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="fas fa-minus-circle"></i> Before</h6>
                        <pre id="oldVals" class="bg-light p-2 rounded" style="font-size:12px;max-height:300px;overflow:auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="fas fa-plus-circle"></i> After</h6>
                        <pre id="newVals" class="bg-light p-2 rounded" style="font-size:12px;max-height:300px;overflow:auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDiff(id) {
    var btn = document.querySelector('[onclick="showDiff(' + id + ')"]');
    var old = btn.getAttribute('data-old');
    var nw  = btn.getAttribute('data-new');
    try { old = JSON.stringify(JSON.parse(old), null, 2); } catch(e) {}
    try { nw  = JSON.stringify(JSON.parse(nw),  null, 2); } catch(e) {}
    document.getElementById('oldVals').textContent = old || '(none)';
    document.getElementById('newVals').textContent = nw  || '(none)';
    $('#diffModal').modal('show');
}
</script>
