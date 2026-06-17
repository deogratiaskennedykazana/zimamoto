 
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <?php
                            $customers = countSubsByType($conn, "customer", "active");
                            if($customers && is_array($customers)){
                                echo "<h3>$customers[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Customers</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="./?page=register_customer" class="small-box-footer">Register New Customer <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <?php
                            $suppliers = countSubsByType($conn, "supplier", "active");
                            if($suppliers && is_array($suppliers)){
                                echo "<h3>$suppliers[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Supplier</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-stats-bars"></i>
                    </div>
                    <a href="./?page=register_supplier" class="small-box-footer">Register New Supplier <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <?php
                            $staff = countSubsByType($conn, "staff", "active");
                            if($staff && is_array($staff)){
                                echo "<h3>$staff[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Staffs</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person-add"></i>
                    </div>
                    <a href="./?page=register_staff" class="small-box-footer">Register New Staffs <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                            $assets = countSubsByType($conn, "asset", "active");
                            if($assets && is_array($assets)){
                                echo "<h3>$assets[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Assets</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-pie-graph"></i>
                    </div>
                    <a href="./?page=register_asset" class="small-box-footer">Add New Assets <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <?php
                            $stocks = countSubsByType($conn, "stocks", "active");
                            if($stocks && is_array($stocks)){
                                echo "<h3>$stocks[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Stocks</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-pie-graph"></i>
                    </div>
                    <a href="./?page=register_stock" class="small-box-footer">Add New Stock <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <?php
                            $others = countSubsByType($conn, "others", "active");
                            if($others && is_array($others)){
                                echo "<h3>$others[jumla]</h3>";
                            } else{
                                echo "<h3>0</h3>";
                            }
                        ?>
                        <p>Other Subsidiaries</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-pie-graph"></i>
                    </div>
                    <a href="./?page=add_other_subsidiary" class="small-box-footer">Add New Subsidiary <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
   