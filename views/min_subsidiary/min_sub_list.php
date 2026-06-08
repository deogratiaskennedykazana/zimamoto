<div class=" card card-dark">
    <div class=" card-header"> <h4 class=" card-title">Min Subsiary List</h4> </div>
    <div class=" card-body table-responsive">
        <table class=" table table-sm table-striped table-bordered table-search">
            <tr>
                <td>#</td>
                <td>Name</td>
                <td>Category</td>
                <td>Type</td>
                <td>Subsidiary</td>
                <td>Action</td>
            </tr>
           <?php
                $branchId = null;
                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                    $branchId = $_SESSION['branchid']; // Only their branch
                }
                $minSubs = selectAllMinSubs($conn, $branchId);
                if ($minSubs && is_array($minSubs)) {
                    $counter = 1;
                    foreach ($minSubs as $minSub) {
                        echo "<tr>";
                        echo "<td>{$counter}</td>";
                        echo "<td>{$minSub['name']}</td>";
                        echo "<td>{$minSub['category']}</td>";
                        echo "<td>{$minSub['type']}</td>";
                        echo "<td>{$minSub['sub']}</td>";
                        echo "<td>
                                <a class='btn btn-info btn-sm'>Edit</a>
                                <a class='btn btn-danger btn-sm my-1'>Delete</a>
                              </td>";
                        echo "</tr>";
                        $counter++;
                    }
                }
            ?>

        </table>

    </div>
</div>