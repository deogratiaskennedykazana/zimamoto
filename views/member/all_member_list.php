<div class=" card card-dark">
    <div class=" card-header"> <h4 class=" card-title">List Of Members</h4> </div>
    <div class=" card-body  table-responsive">
            <!--<table class="table table-hover table-bordered data-table-basic data-table">-->
        <table class=" table table-sm table-striped table-bordered table-seah data-table">
            <thead>
                <tr class=" table-primary">
                    <th>#</th>
                    <th>Reg No</th>
                    <th>Name</th>
                    <th>Branch Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Check No</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
                    
                    $branchId = ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') 
                        ? $_SESSION['branchid'] 
                        : null;
         
                    if ($branchId !== null) {
                        $members = getAllMembersByBranch($conn, $branchId);
                    } else {
                        $members = getAllMembers($conn);
                    }
                    
                   
                    if ($members && is_array($members)) {
                        $counter = 1;
                        foreach ($members as $member) {
                            echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td>$member[reg_no]</td>";
                                echo "<td>$member[name]</td>";
                                echo "<td>$member[branch]</td>";
                                echo "<td>$member[phone]</td>";
                                echo "<td>$member[email]</td>";
                                echo "<td>$member[address]</td>";
                                echo "<td>$member[check_no]</td>";
                                echo "<td> </td>"; // placeholder for actions
                            echo "</tr>";
                            $counter++;
                        }
                    }
            ?>
        </tbody>
        </table>
    </div>
</div>

<script src="./dist/datatable2/jquery-3.7.1.js"></script>
 <script src="./dist/datatable2/datatables.js"></script>
 
 <script>
     $(document).ready(function(){
                 new DataTable('.data-table', {
                     responsive: true,
                     ordering:false,
                     pageLength: 240,
                     layout: {
                 topStart: {
                     buttons: [
                         'copy', 'excel', 'pdf','print'
                     ]
                 }
             }
         });
     });
 </script>