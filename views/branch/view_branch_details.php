<div class=" card card-primary">
    <div class="card-header"> <h5>Branch Details</h5> </div>
    <div class=" card-body">
        <?php 
                // print_r($_POST);
                $branchId = (int) $_GET['id'];
                $branch = SelectBranchById($conn,$branchId);
                            if($branch && is_array($branch)){
                                ?>
                                    <h5 class=" card-text">Branch Name: <?= $branch['name'] ?></h5>
                                    <h5 class=" card-text">Branch address: <?= $branch['address'] ?></h5>
                                <?php
                            }
        ?>
    </div>
    <div class=" card-body">
        <div class=" row">
            <div class=" col-sm-10 col-md-5">
                <h5 class=' text-center'>Branch Members</h5>
                <?php
                    $member = countBranchMember($conn, $branchId);
                    if($member && is_array($member)){
                        echo "<h4 class=' text-center'>$member[member]</h4>";
                    }
                ?>
            </div>
        </div>
    </div>
    <div class=" card-footer"> <h5 class=" card-text"> Branch Staff </h5> </div>
    <div class=" card-body">
                    <div class=" card-footer">
                        <button class=" btn btn-info btn-sm" data-target="#addstaff" data-toggle="modal" type="button">Add new Staff</button>
                    </div>
        <table class=" table table-bordered table-striped">
            <tr>
                <td>#</td>
                <td>Name</td>
                <td>Position</td>
                <td>Status</td>
                <td>Action</td>
            </tr>
            <?php
                    $staffs = selectBranchStaff($conn, $branchId);
                    if($staffs && is_array($staffs)){
                        $counter = 1;
                        foreach($staffs as $staff){
                            echo "<tr>";
                            echo "<td>$counter</td>";
                            echo "<td>$staff[name]</td>";
                            echo "<td>$staff[role]</td>";
                            echo "<td>$staff[status]</td>";
                            echo "<td>";
                                            echo "<button class=' btn btn-info btn-sm lunch-user-lore-modal' data-id='$staff[id]' data-name='$staff[name]' data-role='$staff[role]'  data-toggle='modal' data-target='#change-role-modal'>Change role</button>";
                             echo "</td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
            ?>
        </table>
    </div>
</div>

 <div class=" modal fade" id="addstaff">
                    <div class=" modal-dialog">
                        <div class=" modal-content">
                            <div class=" modal-header bg-info">
                                <h4 class=' text-white '>Add New Staff</h4>
                                <button type="button" class=" btn btn-danger" data-dismiss="modal"> &times;</button>
                            </div>
                            <div class=" modal-body" id="super-viser-details">
                                <form action="./controllers/branch_controller.php" method="POST">
                                    <input type="hidden" name="branch_id" value="<?= $branchId ?>">
                                    <div class=" form-goup">
                                        <label for="" class=" form-label"> Select Member</label>
                                        <select name="user_id" class=" form-control select2-form select2bs4-form" required id="">
                                            <option value=""> Select Staff</option>
                                            <?php
                                                    $users =selectsByBranchIdAndUserRole($conn, $branchId, 'member');
                                                    if($users && is_array($users)){
                                                        foreach($users as $user){
                                                            echo "<option value='$user[id]'>$user[name]</option>";
                                                        }
                                                    }
                                            ?>
                                        </select>
                                    </div>
                                    <div class=" form-group">
                                        <label for="">Role</label>
                                        <select name="role" class=" form-control" required id="">
                                            <option value="">Select Below</option>
                                            <option value="staff">Staff</option>
                                            <option value="manager">Branch Manager (katibu)</option>
                                            <option value="chairmana">Branch Chairrman</option>
                                            <option value="accountant"> Branch Accountant</option>
                                        </select>
                                    </div>
                                    <div class=" modal-footer">
                                        <button type="button" class=" btn btn-sm btn-danger" data-dismiss='modal' >Cancel</button>
                                        <button type="submit" class=" btn btn-sm btn-success" name="addstaff">Add Role</button>
                                    </div>
                                </form>
                    </div>
 </div>
                    </div>
 </div>

 <!-- modal to change user role -->
  <div class=" modal fade" id="change-role-modal">
        <div class=" modal-dialog">
            <div class=" modal-content">
                <div class=" modal-header"> <h5>Change Role</h5>
                 <button type="button" class=" btn btn-danger" data-dismiss="modal"> &times;</button>
             </div>
             <form action="./controllers/branch_controller.php" method="post">
                <div class=" card-body">
                    <div class=" form-label">
                        <label for="">User Name</label>
                        <input type="text" name="name" required class=" form-control" id="user_name">
                        <input type="hidden" name="user_id" id="user_id">

                    </div>
                    <div class=" form-label">
                    <label for="">Role</label>
                    <select name="role" required  class=" form-control" id="">
                        <option value="">Select Below</option>
                        <option value="staff">Staff</option>
                        <option value="manager">Branch Manager(manager)</option>
                        <option value="accountant">Branch Accountant</option>
                        <option value="chairman"> Branch Chairman</option>
                        <option value="member"> Remove role</option>
                    </select>
                    </div>
                    
                </div>
                <div class=" card-footer">
                    <button type="button" class=" btn btn-sm btn-danger" data-dismiss="modal">Cancel</button>
                    <button type="submit" class=" btn btn-sm btn-info" name="addstaff">Change Role</button>
                </div>
             </form>
            </div>
        </div>                                            
 
  </div>

  <script>
     document.querySelectorAll(".lunch-user-lore-modal").forEach(button => {
        button.addEventListener("click", () => {
            let id = button.getAttribute("data-id");
            let name = button.getAttribute("data-name");
            let role = button.getAttribute("data-role");
            document.getElementById("user_name").value = name;
            document.getElementById("user_id").value = id;
            //document.getElementById("role").value = role;
        });
     });
  </script>