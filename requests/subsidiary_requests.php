<?php
    require_once "../configs.php";
    require_once "../functions/transaction_functions.php";
    require_once "../functions/subsidiary_functions.php";
    require_once "../functions/opening_balance_functions.php";
    $conn = openConn();
    
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        if(isset($_GET['select_subsidiaries_by_type'])){
            $type = $conn->real_escape_string($_GET['type']);
            $subsidiaries = [];
            
            if($type === 'deleted'){
                $subsidiaries = selectDeletedSubsidiaries($conn);
            } elseif($type === 'all'){
                $subsidiaries = selectAllSubsidiaries($conn);
            } else {
                $subsidiaries = selectSubsidiaryByType($conn, $type);
            }
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-search">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Ledger</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($subsidiaries && is_array($subsidiaries)){
                            $counter = 1;
                            foreach($subsidiaries as $sub){
                                echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>{$sub['name']}</td>";
                                    echo "<td>{$sub['type']}</td>";
                                    echo "<td>" . (isset($sub['ledger_name']) ? $sub['ledger_name'] : '-') . "</td>";
                                    
                                    if($type !== 'deleted'){
                                        echo "<td>
                                            <a href='./?page=edit_subsidiary&id={$sub['id']}' class='btn btn-sm btn-primary my-1'>Edit</a>
                                            <a href='./?page=view_subsidiary_report&id={$sub['id']}' class='btn btn-sm btn-secondary my-1'>View Report</a>
                                            <a href='./?page=delete_subsidiary&id={$sub['id']}' class='btn btn-sm btn-danger my-1' onclick=\"return confirm('Are you sure you want to delete this subsidiary?');\">Delete</a>
                                        </td>";
                                    } else {
                                        echo "<td>
                                            <a href='./?page=restore_subsidiary&id={$sub['id']}' class='btn btn-sm btn-primary my-1'>Restore</a>
                                        </td>";
                                    }
                                echo "</tr>";
                                $counter++;
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No subsidiaries found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
        }


    }
    ?>