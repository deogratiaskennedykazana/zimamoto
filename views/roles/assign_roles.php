<div class="card card-primary">
    <div class="card-header"><h4 class="card-title">Assign Roles to Users</h4></div>
    <div class="card-body">
        <form action="./controllers/role_controller.php" method="post" class="was-validated">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Select User</label>
                        <select name="user_id" class="form-control select2-form select2bs4-form" required>
                            <option value="">Select User</option>
                            <?php
                            $users = selectAllUsers($conn);
                            if($users && is_array($users)):
                                foreach($users as $u):
                            ?>
                                <option value="<?= $u['id'] ?>"><?= $u['name'] ?> (<?= $u['branch_name'] ?? 'N/A' ?>)</option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Select Role</label>
                        <select name="role_id" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php
                            $roles = selectAllRoles($conn);
                            if($roles && is_array($roles)):
                                foreach($roles as $r):
                            ?>
                                <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="assign_role" class="btn btn-primary btn-block"><i class="fas fa-check"></i> Assign</button>
                    </div>
                </div>
            </div>
        </form>

        <hr>
        <h5>Current User Role Assignments</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-search">
                <thead>
                    <tr><th>User</th><th>Branch</th><th>Current Role</th><th>Assigned Roles</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $allUsers = selectAllUsersWithRoles($conn);
                    if($allUsers && is_array($allUsers)):
                        foreach($allUsers as $u):
                    ?>
                    <tr>
                        <td><?= $u['name'] ?></td>
                        <td><?= $u['branch_name'] ?? 'N/A' ?></td>
                        <td><?= ucfirst($u['role']) ?></td>
                        <td><?= $u['assigned_roles'] ?? '<span class="text-muted">None</span>' ?></td>
                        <td>
                            <?php
                            $userRoles = getUserRoles($conn, $u['id']);
                            if($userRoles && is_array($userRoles)):
                                foreach($userRoles as $ur):
                            ?>
                                <form action="./controllers/role_controller.php" method="post" style="display:inline" onsubmit="return confirm('Revoke role?')">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="role_id" value="<?= $ur['id'] ?>">
                                    <button type="submit" name="revoke_role" class="btn btn-danger btn-sm" title="Revoke <?= $ur['name'] ?>"><i class="fas fa-ban"></i> <?= $ur['name'] ?></button>
                                </form>
                            <?php endforeach; endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
