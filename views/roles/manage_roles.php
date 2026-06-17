<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title">Manage Roles & Permissions</h4>
        <div class="card-tools">
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addRoleModal"><i class="fas fa-plus"></i> New Role</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-search">
                <thead>
                    <tr><th>Role Name</th><th>Description</th><th>Created By</th><th>Created At</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $roles = selectAllRoles($conn);
                    if($roles && is_array($roles)):
                        foreach($roles as $r):
                    ?>
                    <tr>
                        <td><strong><?= $r['name'] ?></strong></td>
                        <td><?= $r['description'] ?></td>
                        <td><?= $r['created_by_name'] ?? 'N/A' ?></td>
                        <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="editRole(<?= $r['id'] ?>, '<?= $r['name'] ?>', '<?= $r['description'] ?>')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-warning btn-sm" onclick="managePermissions(<?= $r['id'] ?>)"><i class="fas fa-shield-alt"></i> Permissions</button>
                            <a href="./controllers/role_controller.php?delete_role=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete role?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal">
    <div class="modal-dialog"><div class="modal-content">
        <form action="./controllers/role_controller.php" method="post">
            <div class="modal-header"><h5>New Role</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>Role Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="create_role" class="btn btn-primary">Create</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal">
    <div class="modal-dialog"><div class="modal-content">
        <form action="./controllers/role_controller.php" method="post">
            <input type="hidden" name="role_id" id="editRoleId">
            <div class="modal-header"><h5>Edit Role</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>Role Name</label><input type="text" name="name" id="editRoleName" class="form-control" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="editRoleDesc" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_role" class="btn btn-warning">Update</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permsModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form action="./controllers/role_controller.php" method="post">
            <input type="hidden" name="role_id" id="permsRoleId">
            <div class="modal-header"><h5>Role Permissions</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body" id="permsContent">
                <div class="text-center">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="save_permissions" class="btn btn-primary">Save Permissions</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function editRole(id, name, desc) {
    document.getElementById('editRoleId').value = id;
    document.getElementById('editRoleName').value = name;
    document.getElementById('editRoleDesc').value = desc;
    $('#editRoleModal').modal('show');
}
function managePermissions(roleId) {
    document.getElementById('permsRoleId').value = roleId;
    document.getElementById('permsContent').innerHTML = '<div class="text-center">Loading...</div>';
    $('#permsModal').modal('show');

    $.ajax({
        url: './requests/form_requests.php',
        type: 'GET',
        data: { get_role_permissions: '', role_id: roleId },
        success: function(html) {
            document.getElementById('permsContent').innerHTML = html;
        }
    });
}
</script>
