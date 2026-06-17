<div class=" card card-primary">
    <div class=" card-header"> <h4 class=" card-title" >View Branch Members</h4> </div>
    <div class=" card-body">
        <div class=" row">
            <div class=" col-sm-10 col-md-4">
                <div class=" form-group">
                    <label for="branch">Branch</label>
                    <select class=" form-control" id="branch" name="branch">
                        <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                     echo '<option value="0">All Branches</option>';
                                                    
                                                }
                                    
                                                if ($branches && is_array($branches)) {
                                                    foreach ($branches as $result) {
                                                        $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                        echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                    }
                                                }
                                            ?>
                    </select>
                </div>
            </div>
            <div class=" col-sm-10 col-md-3">
                <div class=" form-group mt-4 p-1">
                    <button class=" fetch-data btn btn-sm btn-info" onclick="fetchAllMembersByBranch()">Search Data</button>
                </div>
            </div>
        </div>
    </div>
    <div class='data'></div>
</div>
<!-- Modal -->
<div class="modal fade" id="spinnerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white text-center p-4">
        <div class=" row">
            <div class=" col-3 mt-3">
                <div class="spinner-border text-light mb-3" role="status"></div>
            </div>
            <div class=" col-6">
                    <div>System Status</div>
                    <div><strong>Fetching data…</strong></div>
            </div>
        </div>
   
      
    </div>
  </div>
</div>

<script>
//  const modal = new bootstrap.Modal(document.getElementById('spinnerModal'), {
//   backdrop: 'static',
//   keyboard: false
// });
 
$(function(){

})
</script>