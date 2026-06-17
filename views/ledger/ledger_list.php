<?php
$ledgers = selectAllActiveLedgers($conn);
$submains = selectAllSubmains($conn);
?>
<div class="text-end mb-3">
    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addLedgerModal">
        <i class="fas fa-plus"></i> Add Ledger
    </button>
</div>
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">List of ledger</h4>
    </div>
    <div class="card-body">
        <table class="table table-sm table-search table-striped" id="ledgers">
            <thead class="bg-primary">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Submain</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $num = 0;
                if($ledgers && is_array($ledgers)){
                    foreach($ledgers as $ledger){
                        $num++;
                ?>
                <tr>
                    <td><?php echo $num; ?></td>
                    <td><?php echo htmlspecialchars($ledger['name']); ?></td>
                    <td><?php echo htmlspecialchars($ledger['submain_name']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editLedgerModal<?php echo $ledger['id']; ?>">
                            Edit
                        </button>
                        <form action="controllers/ledger_controllers.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this ledger?');">
                            <input type="hidden" name="id" value="<?php echo $ledger['id']; ?>">
                            <button type="submit" name="delete_ledger" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="4" class="text-center">No ledgers found</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
if($ledgers && is_array($ledgers)){
    foreach($ledgers as $ledger){
?>
<div class="modal fade" id="editLedgerModal<?php echo $ledger['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Edit Ledger</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="controllers/ledger_controllers.php" method="POST" class="was-validated">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $ledger['id']; ?>">
                    <div class="mb-3">
                        <label for="edit_ledger_name<?php echo $ledger['id']; ?>" class="form-label">Ledger Name</label>
                        <input type="text" class="form-control" id="edit_ledger_name<?php echo $ledger['id']; ?>" name="name" value="<?php echo htmlspecialchars($ledger['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_submain_id<?php echo $ledger['id']; ?>" class="form-label">Select Submain</label>
                        <select class="form-control select2-form select2bs4-form" id="edit_submain_id<?php echo $ledger['id']; ?>" name="submain_id" required>
                            <option value="">-- Select Submain --</option>
                            <?php 
                            if($submains && is_array($submains)){
                                foreach($submains as $submain){
                            ?>
                            <option value="<?php echo $submain['id']; ?>" <?php echo ($submain['id'] == $ledger['submain_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($submain['name']); ?>
                            </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_ledger" class="btn btn-warning">Update Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
    }
}
?>
<div class="modal fade" id="addLedgerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Add New Ledger</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="controllers/ledger_controllers.php" method="POST" class="was-validated">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ledger_name" class="form-label">Ledger Name</label>
                        <input type="text" class="form-control" id="ledger_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="submain_id" class="form-label">Select Submain</label>
                        <select class="form-control select2-form select2bs4-form" id="submain_id" name="submain_id" required>
                            <option value="">-- Select Submain --</option>
                            <?php 
                            if($submains && is_array($submains)){
                                foreach($submains as $submain){
                            ?>
                            <option value="<?php echo $submain['id']; ?>">
                                <?php echo htmlspecialchars($submain['name']); ?>
                            </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_ledger" class="btn btn-primary">Add Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>