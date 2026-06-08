<div class="card card-warning">
    <div class="card-header"><h4 class="card-title">My Guarantor Requests</h4></div>
    <div class="card-body">
        <?php
        $requests = selectGrantorPendingRequests($conn, $_SESSION['userid']);
        if($requests && is_array($requests) && count($requests) > 0):
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-search">
                <thead>
                    <tr><th>Applicant</th><th>Loan Amount</th><th>Loan Type</th><th>Period</th><th>Requested On</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td><?= $req['applicant_name'] ?></td>
                        <td class="text-right"><?= number_format($req['principle'], 2) ?></td>
                        <td><?= $req['loan_type'] ?? 'N/A' ?></td>
                        <td><?= $req['period'] ?> months</td>
                        <td><?= date('d/m/Y H:i', strtotime($req['sent_at'])) ?></td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="respondToRequest('<?= $req['token'] ?>', 'accepted')"><i class="fas fa-check"></i> Accept</button>
                            <button class="btn btn-danger btn-sm" onclick="respondToRequest('<?= $req['token'] ?>', 'rejected')"><i class="fas fa-times"></i> Reject</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">No pending guarantor requests.</div>
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
