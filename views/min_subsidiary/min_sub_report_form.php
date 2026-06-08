<div class=" card card-info ">
    <div class=" card-header"> <h4>Min Subsidiary list</h4> </div>
    <div class=" card-body">
        <table class=" table table-bordered table-sm  table-search">
            <tr class=" bg-primary">
                <td>#</td>
                <td>Name</td>
                <td>Category</td>
                <td>Action</td>
            </tr>
            <?php
                 $branchId = null;
                    if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                        $branchId = $_SESSION['branchid']; // Only their branch
                    }
                    $minsubs = selectAllMinSubs($conn, $branchId);
                    
                    if($minsubs && is_array($minsubs)){
                        $counter = 1;
                        foreach($minsubs as $minsub){
                            echo "<tr>";
                            echo "<td>$counter</td>";
                            echo "<td>$minsub[name]</td>";
                            echo "<td>$minsub[category]</td>";
                            echo "<td><a href='./?page=min_sub_report&id=$minsub[id]' class=' btn btn-sm btn-info'>View Report</a></td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
            ?>
        </table>
    </div>
</div>