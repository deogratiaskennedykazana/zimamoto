<div class=" card card-dark">
    <div class=" card-header">
        <h4 class=" card-title">Subsidiary List</h4>
    </div>
    <div class=" card-body table-responsive">
        <table class=" table table-bordered table-striped table-sm table-search">
            <thead>
                <tr>
                    <th>SN</th>
                    <th> Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Ledger</th>
                    <td>Action</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $subs = getAllSubsidiaries($conn);
                    if($subs && is_array($subs)){
                        $counter = 1;
                        foreach($subs as $sub){
                            echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td>$sub[name]</td>";
                                echo "<td>$sub[type]</td>";
                                echo "<td>$sub[category]</td>";
                                echo "<td>$sub[ledger]</td>";
                                echo "<td>
                                            <a href='./edit_subsidiary.php?sub=$sub[id]' class='btn btn-sm btn-primary my-1'>Edit</a>
                                            <a href='./?page=delete_sub&sub=$sub[id]' class='btn btn-sm btn-danger'>Delete</a>
                                </td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
                ?>
            </tbody>
        </table>
</div>
</div>