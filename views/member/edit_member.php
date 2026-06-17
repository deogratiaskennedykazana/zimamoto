<?php
// Get data from URL
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Fetch member data
$member = null;
if($member_id > 0) {
    $member = selectMemberById($conn, $member_id);
}

// /print_r($member);

if($member && is_array($member)) {
?>
<div class="card card-primary">
    <div class="card-header">Edit Member</div>
    <!--<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?member_id=<?= $member_id ?>&branch_id=<?= $branch_id ?>" method="post" class="was-validated">-->
    <form action="./controllers/member_controllers.php" method="post" class="was-validated">
        <input type="hidden" name="member_id" value="<?= $member_id ?>">
        <!--<input type="hidden" name="branch_id" value="<?= $branch_id ?>">-->
        <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
    
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($member['name']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($member['email']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($member['phone']) ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="address">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($member['address']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" class="form-control" id="reg_no" name="reg_no" value="<?= htmlspecialchars($member['reg_no']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="birthdate">Birth Date</label>
                        <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?= $member['birthdate'] ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="branch">Current Branch</label>
                        <input type="text" class="form-control" id="branch" value="<?= htmlspecialchars($member['branch']) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="male" <?= $member['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $member['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="nida">NIDA</label>
                        <input type="text" class="form-control" id="nida" name="nida" value="<?= htmlspecialchars($member['nida']) ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="check_no">Check Number</label>
                        <input type="text" class="form-control" id="check_no" name="check_no" value="<?= htmlspecialchars($member['check_no']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending" <?= $member['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $member['status'] == 'approved' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $member['status'] == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" name="update_member" class="btn btn-primary">Update Member</button>
            <a href="./?page=members&branchId=<?= $branch_id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
} else {
    echo "<div class='alert alert-danger'>Member not found!</div>";
}
?>