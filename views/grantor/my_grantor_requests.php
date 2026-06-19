<div class="card card-warning">
    <div class="card-header"><h4 class="card-title">My Guarantor Requests</h4></div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>What does being a guarantor mean?</strong> By accepting, you agree to be responsible for repaying this loan if the borrower defaults. Please review the loan details carefully before responding.
        </div>
        <?php
        $pendingRequests = selectGrantorPendingRequests($conn, $_SESSION['userid']);
        // Also fetch all (including responded) for history
        $allRequests = selectAllGrantorNotificationsForGrantor($conn, (int)$_SESSION['userid']);
        if($pendingRequests && is_array($pendingRequests) && count($pendingRequests) > 0):
        ?>
        <h5 class="mb-3 text-warning"><i class="fas fa-clock mr-1"></i>Pending Requests (<?= count($pendingRequests) ?>)</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-search">
                <thead>
                    <tr><th>Applicant</th><th>Loan Amount</th><th>Loan Type</th><th>Period</th><th>Requested On</th><th>Expires</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($pendingRequests as $req): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($req['applicant_name']) ?></strong></td>
                        <td class="text-right">TZS <?= number_format($req['principle'], 2) ?></td>
                        <td><?= htmlspecialchars($req['loan_type'] ?? 'N/A') ?></td>
                        <td><?= (int)$req['period'] ?> months</td>
                        <td><?= date('d/m/Y H:i', strtotime($req['sent_at'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($req['expires_at'])) ?></td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="respondToRequest('<?= $req['token'] ?>', 'accepted')">
                                <i class="fas fa-check"></i> Accept
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="respondToRequest('<?= $req['token'] ?>', 'rejected')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i>No pending guarantor requests.</div>
        <?php endif; ?>

        <?php
        // Show history of responded requests
        $respondedRequests = array_filter($allRequests ?? [], fn($r) => $r['status'] !== 'pending');
        if (!empty($respondedRequests)):
        ?>
        <h5 class="mb-3 mt-4"><i class="fas fa-history mr-1"></i>Response History</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead>
                    <tr><th>Applicant</th><th>Loan Amount</th><th>Period</th><th>Status</th><th>Responded On</th><th>Comment</th></tr>
                </thead>
                <tbody>
                    <?php foreach($respondedRequests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['applicant_name']) ?></td>
                        <td class="text-right">TZS <?= number_format($req['principle'], 2) ?></td>
                        <td><?= (int)$req['period'] ?> months</td>
                        <td>
                            <?php
                            $badge = ['accepted'=>'badge-success','rejected'=>'badge-danger','expired'=>'badge-secondary'];
                            $cls = $badge[$req['status']] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?= $cls ?>"><?= ucfirst($req['status']) ?></span>
                        </td>
                        <td><?= $req['responded_at'] ? date('d/m/Y', strtotime($req['responded_at'])) : '—' ?></td>
                        <td><?= htmlspecialchars($req['comment'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="./controllers/grantor_controller.php">
                <div class="modal-header"><h5 class="modal-title">Respond to Guarantor Request</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="token" id="responseToken">
                    <input type="hidden" name="status" id="responseStatus">
                    <div class="form-group">
                        <label>Comment (optional)</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                    <p id="responseMessage" class="font-weight-bold"></p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="respond_grantor" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function respondToRequest(token, status) {
    document.getElementById('responseToken').value = token;
    document.getElementById('responseStatus').value = status;
    document.getElementById('responseMessage').innerText = status === 'accepted'
        ? 'You are about to ACCEPT this guarantor request.'
        : 'You are about to REJECT this guarantor request.';
    $('#responseModal').modal('show');
}
</script>
