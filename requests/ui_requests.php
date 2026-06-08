<?php
        require_once "../configs.php";
        require_once "../functions/member_functions.php";

        $conn = openConn();

        if($_SERVER['REQUEST_METHOD'] === "GET"){
          if(isset($_GET['get_members_by_branch_id'])){
    $branchId = (int) $_GET['branchId'];
    $members = [];
    if($branchId == 0){
        $members = getAllMembers($conn);
    } else{
        $members = getAllMembersByBranch($conn, $branchId);
    }
    ?>
    <div class="card card-dark">
        <div class="card-header"> 
            <h4 class="card-title">List Of Members</h4> 
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm data-table">
                <thead>
                    <tr class="table-primary">
                        <th>#</th>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Branch Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Check No</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if($members && is_array($members) && count($members) > 0){
                   $counter = 1;
                   foreach($members as $member){
                       // Ensure all fields exist
                       $reg_no = isset($member['reg_no']) ? (empty($member['reg_no']) ? '-' : htmlspecialchars($member['reg_no'])) : '-';
                       $name = isset($member['name']) ? (empty($member['name']) ? '-' : htmlspecialchars($member['name'])) : '-';
                       $branch = isset($member['branch']) ? (empty($member['branch']) ? '-' : htmlspecialchars($member['branch'])) : '-';
                       $phone = isset($member['phone']) ? (empty($member['phone']) ? '-' : htmlspecialchars($member['phone'])) : '-';
                       $email = isset($member['email']) ? (empty($member['email']) ? '-' : htmlspecialchars($member['email'])) : '-';
                       $address = isset($member['address']) ? (empty($member['address']) ? '-' : htmlspecialchars($member['address'])) : '-';
                       $check_no = isset($member['check_no']) ? (empty($member['check_no']) ? '-' : htmlspecialchars($member['check_no'])) : '-';
                       
                       echo "<tr>";
                       echo "<td>{$counter}</td>";
                       echo "<td>{$reg_no}</td>";
                       echo "<td>{$name}</td>";
                       echo "<td>{$branch}</td>";
                       echo "<td>{$phone}</td>";
                       echo "<td>{$email}</td>";
                       echo "<td>{$address}</td>";
                       echo "<td>{$check_no}</td>";
                       echo "</tr>";
                       $counter++;
                   }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>No data available</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
            
           
         
if(isset($_GET['update_members_by_branch_id'])){
    $branchId = (int) $_GET['branchId'];
    $members = [];
    if($branchId == 0){
        $members = getAllMembers($conn);
    } else{
        $members = getAllMembersByBranch($conn, $branchId);
    }
    ?>
    <div class="card card-info">
        <div class="card-header"> 
            <h4 class="card-title">List Of Members</h4> 
        </div>
        <div class="card-body table-responsive">
           <table class="table table-bordered table-sm data-table">
                <thead>
                    <tr class="table-primary">
                        <th>#</th>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Branch Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Check No</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
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
                                            <a href='./?page=change_branch_member&member_id=$member[id]&branch_id=$branchId' class='btn btn-sm btn-warning my-1 mx-1'>
                                                Change Branch
                                            </a>
                                            <a onclick='return confirm(\"Are you sure?\")' href='./controllers/member_controllers.php?delete_member&member_id=$member[id]' class='btn btn-sm btn-danger ' >Delete Member</a>
                                          </td>";
                           echo "</tr>";
                           $counter++;
                       }
                    }else{
                        echo "<tr><td colspan='9'>No data</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
}

}
?>