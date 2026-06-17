<div class=" card card-primary">
    <div class="card-header">
        <h3 class="card-title">Branches List</h3>
    </div>
    <div class="card-body">
        <table  class="table table-bordered table-striped table-search">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th> Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Region</th>
                    <th>Action</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php
                    $branches = selectAllBranches($conn);
                    $sn = 1;
                    foreach($branches as $branch){
                        echo "<tr>
                        <td>$sn</td>
                        <td>{$branch['name']}</td>
                        <td>{$branch['phone']}</td>
                        <td>{$branch['address']}</td>
                        <td>{$branch['email']}</td>
                        <td>{$branch['mkoa']}</td>
                        <td>
                            <a href='./?page=view_branch_details&id=$branch[id]' class=' btn btn-primary  btn-sm m-1'>View</a>
                            <a href='./?edit_branch&id=$branch[id]' class='m-1 btn btn-info btn-sm'>Edit</a>
                            <a href='./controllers/branch_controller.php?delete_branch$branch[id]' class=' m-1 btn btn-sm btn-danger '>Delete</a>
                        </td>
                    </tr>";
                        $sn++;
                    }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>S/N</th>
                    <th> Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Region</th>
                    <th>Action</th>
                </tr>
            </tfoot>
        </table>
</div>