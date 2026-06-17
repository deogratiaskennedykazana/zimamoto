<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">List of Master</h4>
    </div>
    <div class="card-body">
        <table class="table table-sm table-search table-striped" id="submains">
            <thead class="bg-primary">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $num = 0;
                $masters = selectAllMasters($conn);
                if($masters && is_array($masters)){
                    foreach($masters as $master){
                        $num++;
                ?>
                <tr>
                    <td><?php echo $num; ?></td>
                    <td><?php echo htmlspecialchars($master['name']); ?></td>
                    <td><?php echo htmlspecialchars($master['status']); ?></td>
                     
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="4" class="text-center">No Master found</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
 