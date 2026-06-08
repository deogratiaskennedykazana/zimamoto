<?php
 
$branchId = null;
if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
    $branchId = $_SESSION['branchid'];
}

if ($branchId !== null) {
     
    $members = getAllMembersByBranch($conn, $branchId);
    ?>
    <div class="card card-dark">
                    <div class="card-header"> 
                        <h4 class="card-title">List Of Members</h4> 
                    </div>
                    <div class="card-body table-responsive">
                       <table class=" table table-bordered table-sm data-table">
                            <tr class="table-primary">
                                <td>#</td>
                                <td>Reg No</td>
                                <td>Name</td>
                                <td>Branch Name</td>
                                <td>Phone</td>
                                <td>Email</td>
                                <td>Address</td>
                                <td>Check No</td>
                                <td>Action</td>
                            </tr>
                            <?php
                            if($members && is_array($members)){
                               $counter = 1;
                               foreach($members as $member){
                                   echo "<tr>";
                                            echo "<td>$counter</td>";
                                            echo "<td>$member[reg_no]</td>";
                                            echo "<td>$member[name]</td>";
                                            echo "<td>$member[branch]</td>";
                                            echo "<td>$member[phone]</td>";
                                            echo "<td>$member[email]</td>";
                                            echo "<td>$member[address]</td>";
                                            echo "<td>$member[check_no]</td>";
                                            echo "<td>
                                                    <a href='./?page=edit_member&member_id=$member[id]&branch_id=$branchId' class='btn btn-sm btn-primary me-1'>
                                                        Edit
                                                    </a>
                                                    <a href='./?page=change_branch_member&member_id=$member[id]&branch_id=$branchId' class='btn btn-sm btn-warning'>
                                                        Change Branch
                                                    </a>
                                                  </td>";
                                   echo "</tr>";
                                   $counter++;
                               }
                            }else{
                                echo "<tr><td colspan='9'>No data</td></tr>";
                            }
                            ?>
                        </table>
                </div>
<?php
} else {
    // Show the dropdown and AJAX search form for non-branch-level users
    ?>
    <div class="card card-primary">
        <div class="card-header"><h4 class="card-title">View Branch Members</h4></div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-10 col-md-4">
                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <select class="form-control" id="branch" name="branch">
                            <option value="">--Select Below--</option>
                            <option value="0">All Branches</option>
                            <?php
                            $branches = selectAllBranches($conn);
                            if ($branches && is_array($branches)) {
                                foreach ($branches as $result) {
                                    echo "<option value='{$result['id']}'>{$result['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-10 col-md-3">
                    <div class="form-group mt-4 p-1">
                        <button class="fetch-data btn btn-sm btn-info" onclick="fetchAllMembers()">Search Data</button>
                    </div>
                </div>
            </div>
        </div>
        <div class='data'></div>
    </div>

    <!-- Spinner Modal -->
    <div class="modal fade" id="spinnerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white text-center p-4">
                <div class="row">
                    <div class="col-3 mt-3">
                        <div class="spinner-border text-light mb-3" role="status"></div>
                    </div>
                    <div class="col-6">
                        <div>System Status</div>
                        <div><strong>Fetching data…</strong></div>  
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>


<script>
//  const modal = new bootstrap.Modal(document.getElementById('spinnerModal'), {
//   backdrop: 'static',
//   keyboard: false
// });
 
$(function(){

})
</script>



  