<?php
// Get member ID from URL
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

// Get member data
$member = null;
if($member_id > 0) {
    $member = selectMemberById($conn, $member_id);
}

 


if($member && is_array($member)) {
?>
<div class="card card-warning">
    <div class="card-header">Change Member Branch</div>
   <form action="./controllers/member_controllers.php" method="post" class="was-validated">
        <input type="hidden" name="member_id" value="<?= $member_id ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Member Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($member['name']) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Current Branch</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($member['branch']) ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-3">
                        <label>Select New Branch</label>
                        <select class="form-control" name="new_branch_id" required>
                            <option value="">-- Select New Branch --</option>
                            <?php
                            $branches = selectAllBranches($conn);
                            if($branches && is_array($branches)){
                                foreach($branches as $branch){
                                    if($branch['id'] != $member['branch_id']) { // Don't show current branch
                                        ?>
                                        <option value="<?= $branch['id'] ?>"><?= $branch['name'] ?></option>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <input type="hidden" name="member_id" value="<?= $member_id ?>">
            <button type="submit" name="change_branch" class="btn btn-warning">Change Branch</button>
            <a href="./?page=members" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
} else {
    echo "<div class='alert alert-danger'>Member not found!</div>";
}
?>