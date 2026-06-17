<div class=" card card-info">
        <div class=" card-header">
                <h4>Vehicle Report</h4>
        </div>
        <div class=" card-body">
                <div class=" table-primary  p-2">
                      <h4>Vehicle Details</h4>
                </div>
               
            <?php
                // get Vehicle details
                $vehicle = selectVehicleById($conn, $vehicleId);
                if($vehicle && is_array($vehicle)){
                    ?>
                    <div class=" row p-3" >
                        <div class=" col-2 border-right border-dark">
                            <h5>Manufacturer : <?= $vehicle['manufacturer'] ?></h5>
                        </div>
                        <div class=" col-3 border-right border-dark">
                            <h5>Vehicle Plate : <?= $vehicle['plate_no'] ?></h5>
                        </div>
                        <div class=" col-3 border-right border-dark">
                            <h5>Purchase Date : <?= $vehicle['purchase_date'] ?></h5>
                        </div>
                        <div class=" col-3 border-right border-dark">
                            <h5>Insurance Date : <?= $vehicle['insurance_date'] ?></h5>
                        </div>
                    </div>
                    <!-- root Involved -->
                     <div class="table-secondary p-1 mt-4">
                        <h4>Route Involved</h4>
                     </div>
                     <div class=" table-responsive">
                        <table class=" table table-sm table-bordered">
                                <tr>
                                    <td>#</td>
                                    <td>Date</td>
                                    <td>Driver</td>
                                    <td>Route</td>
                                </tr>
                                <?php 
                                   $routes = getVehicleConsignment($conn, $vehicle['sub_id']);
                                   if($routes && is_array($routes)){
                                        $counter =1;
                                        foreach($routes as $route){
                                            echo "<tr>";
                                                echo "<td>$counter</td>";
                                                echo "<td>$route[date_]</td>";
                                                echo "<td>$route[driver]</td>";
                                                echo "<td>$route[route]</td>";
                                            echo "</tr>";
                                            $counter++;
                                        }
                                   } else{
                                    echo "<tr><td colspan='4' class=' table-danger'>No route </td></tr>";
                                   }
                                ?>
                        </table>
                    </table>
                        <div class=" table-warning p-1 mt-2">
                            <h4>Cost Involved</h4>
                        </div>
                        <div class=" table-responsive">
                                    <table class=" table table-sm table-bordered">
                                        <tr>
                                            <td>#</td>
                                            <td>Date</td>
                                        
                                            <td>Item</td>
                                            <td>Amount</td>
                                        </tr>
                                        <?php
                                            $costs = selectVehiclePayment($conn, $vehicleId, "payment");
                                            $totalAmount =0;
                                            if($costs && is_array($costs)){
                                                $counter = 1;
                                                foreach($costs as $cost){
                                                    echo "<tr>";
                                                        echo "<td>$counter</td>";
                                                        echo "<td>$cost[date_]</td>";
                                                        echo "<td>$cost[paid_to]</td>";
                                                        echo "<td>$cost[cr_ammount]</td>";
                                                    echo "</tr>";
                                                    $counter++;
                                                    $totalAmount += $cost['cr_ammount'];
                                                }
                                                echo "<tr class=' table-primary'><td colspan='3' >Total</td><td>" . number_format( $totalAmount) ."</td></tr>";
                                            } else{
                                                echo "<tr><td colspan='4' class=' table-danger'>No cost </td></tr>";
                                            }
                                        ?>
                                    </table>
                        </div>
                        <div class=" table-success p-1 mt-2">
                            <h4>Sales Involved</h4>
                        </div>
                        <div class=" table-responsive mb-2">
                                <table class=" table table-sm table-bordered">
                                    <tr>
                                        <td>#</td>
                                        <td>Date</td>
                                        <td>Item</td>
                                        <td>Amount</td>
                                    </tr>
                                    <?php
                                        $sales = getVehicleSales($conn, $vehicle['sub_id']);
                                        $totalAmount =0;
                                        if($sales && is_array($sales)){
                                            $counter = 1;
                                            foreach($sales as $sale){
                                                echo "<tr>";
                                                    echo "<td>$counter</td>";
                                                    echo "<td>$sale[date_]</td>";
                                                    echo "<td>$sale[route]</td>";
                                                    echo "<td>$sale[cr_ammount]</td>";
                                                echo "</tr>";
                                                $counter++;
                                                $totalAmount += $sale['cr_ammount'];
                                            }
                                            echo "<tr class=' table-primary'><td colspan='3' >Total</td><td>" . number_format( $totalAmount) ."</td></tr>";
                                        } else{
                                            echo "<tr><td colspan='4' class=' table-danger'>No sales </td></tr>";
                                        }
                                    ?>

                                </table>
                        </div>
                     <?php
                }
            ?>
        </div>
        <div class=" card-footer"> -- END OF REPORT --</div>

</div>