<div class=" card card-info">
    <div class=" card-header"> <h5 class=" card-title">Income Statement by Ledger</h5> </div> 
    <div class=" card-body">
    <?php 
            print_r($_POST);
            $branchId = (int) $_POST['branch'];
              $branch = SelectBranchById($conn,$branchId);
                        if($branch && is_array($branch)){
                            ?>
                                <h5 class=" card-text">Branch Name: <?= $branch['name'] ?></h5>
                                <h5 class=" card-text">Branch address: <?= $branch['address'] ?></h5>
                            <?php
                        }
    ?>
    </div>
</div>
