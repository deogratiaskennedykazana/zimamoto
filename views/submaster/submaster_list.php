<?php
$submains = selectAllSubmains($conn);
$masters = selectAllMasters($conn);
?>
<div class="text-end mb-3">
    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addSubmainModal">
        <i class="fas fa-plus"></i> Add Submaster
    </button>
</div>
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">List of Submaster</h4>
    </div>
    <div class="card-body">
        <table class="table table-sm table-search table-striped" id="submains">
            <thead class="bg-primary">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Master</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $num = 0;
                if($submains && is_array($submains)){
                    foreach($submains as $submain){
                        $num++;
                ?>
                <tr>
                    <td><?php echo $num; ?></td>
                    <td><?php echo htmlspecialchars($submain['name']); ?></td>
                    <td><?php echo htmlspecialchars($submain['master_name']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSubmainModal<?php echo $submain['id']; ?>">
                            Edit
                        </button>
                        <form action="controllers/submain_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this submain?');">
                            <input type="hidden" name="id" value="<?php echo $submain['id']; ?>">
                            <button type="submit" name="delete_submain" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="4" class="text-center">No submains found</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
if($submains && is_array($submains)){
    foreach($submains as $submain){
?>
<div class="modal fade" id="editSubmainModal<?php echo $submain['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Edit Submaster</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="controllers/submain_controller.php" method="POST" class="was-validated">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $submain['id']; ?>">
                    <div class="mb-3">
                        <label for="edit_submain_name<?php echo $submain['id']; ?>" class="form-label">Submaster Name</label>
                        <input type="text" class="form-control" id="edit_submain_name<?php echo $submain['id']; ?>" name="name" value="<?php echo htmlspecialchars($submain['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_master_id<?php echo $submain['id']; ?>" class="form-label">Select Master</label>
                        <select class="form-control select2-form select2bs4-form" id="edit_master_id<?php echo $submain['id']; ?>" name="master_id" required>
                            <option value="">-- Select Master --</option>
                            <?php 
                            if($masters && is_array($masters)){
                                foreach($masters as $master){
                            ?>
                            <option value="<?php echo $master['id']; ?>" <?php echo ($master['id'] == $submain['master_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($master['name']); ?>
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
                    <button type="submit" name="update_submain" class="btn btn-warning">Update Submain</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
    }
}
?>
<div class="modal fade" id="addSubmainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Add New Submaster</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="controllers/submain_controller.php" method="POST" class="was-validated">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="submain_name" class="form-label">Submaster Name</label>
                        <input type="text" class="form-control" id="submain_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="master_id" class="form-label">Select Master</label>
                        <select class="form-control select2-form select2bs4-form" id="master_id" name="master_id" required>
                            <option value="">-- Select Master --</option>
                            <?php 
                            if($masters && is_array($masters)){
                                foreach($masters as $master){
                            ?>
                            <option value="<?php echo $master['id']; ?>">
                                <?php echo htmlspecialchars($master['name']); ?>
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
                    <button type="submit" name="create_submain" class="btn btn-primary">Add Submaster</button>
                </div>
            </form>
        </div>
    </div>
</div>