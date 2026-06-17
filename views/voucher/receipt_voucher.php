
<div class=' container'>
                <div class=" card card-info " >


                                        <div class=" card-header ">
                                                <h4 class=" card-title">Receipt Voucher Form</h4>


                                        </div>
                                        <div class=" card-body">
                                            <form action="./controllers/voucher_controller.php" method="post" class=' was-validated'>
                                                    <div class=" container">
                                                        <div class=" row">
                                                            <div class=" col-sm-10 col-md-3">
                                                                <Label class=" form-label">Payement Mode</Label>
                                                                <select name="cat_type"  id="type1" class=" type form-control" onchange="selectSub(this.value)" required>
                                                                     <option value="">Select below</option>
                                                                      <option value="22">Cash Payment</option>
                                                                     <option value="23">Bank Payment</option>

                                                                </select>

                                                            </div>
                                                            <div class=" col-sm-10 col-md-3">
                                                                <label for="" class=' form-label'>Account used</label>
                                                                <div id='sub'></div>
                                                            </div>
                                                            <div class=" col-sm-10 col-md-3">
                                                                  <label for="" class=" form-label"> Date</label>
                                                                    <input type="date" name="voucherdate"  max="<?php echo date("Y-m-d") ?>" value="<?php echo date("Y-m-d") ?>"  class=" form-control" ></div>
                                                            <div class=" col-sm-10 col-md-3">
                                                               <label for="" class="form-label">Voucher Reference</label>
                                                                                            <?php $v = "RV/"; ?>
                                                                        <?php
                                                                            $sql ="SELECT COUNT(transaction_voucher.id) id FROM `transaction_voucher` WHERE 1";
                                                                            $result = $conn->query($sql);
                                                                            $row = $result->fetch_assoc();
                                                                            $ref =  $row['id'] +1;
                                                                        ?>
                                                                        <input type="text" name="voucher_ref" id="" value="<?php echo $v. $ref ?>" class=" form-control" readonly>


                                                            </div>
                                                            <div class=" col-sm-10 col-md-3 my-2">
                                                                <label for="" class=' form-label'>Currency Used</label>
                                                                <select name="currency" id="" class="form-control select2-form select2bs4-form" required onchange="selectCurrency(this.value)">
                                                                    <option value="">Select below</option>
                                                                    <?php
                                                                        // Check if currencies table exists before querying
                                                                        $tableCheck = $conn->query("SHOW TABLES LIKE 'currencies'");
                                                                        if ($tableCheck && $tableCheck->num_rows > 0) {
                                                                            $sql3 = "SELECT * FROM `currencies`";
                                                                            $result3 = $conn->query($sql3);
                                                                            if ($result3 && $result3->num_rows > 0):
                                                                                while ($rows3 = $result3->fetch_assoc()):
                                                                                    echo "<option value='" . $rows3['id'] . "'>" . htmlspecialchars($rows3['name']) . "</option>";
                                                                                endwhile;
                                                                            endif;
                                                                        } else {
                                                                            // Default fallback currencies when table doesn't exist
                                                                            echo "<option value='1'>TZS - Tanzanian Shilling</option>";
                                                                            echo "<option value='2'>USD - US Dollar</option>";
                                                                            echo "<option value='3'>EUR - Euro</option>";
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class='col-sm-10 col-md-3 my-2' >
                                                                <label for="">Currency value</label>
                                                                <div class="" id="currencyx"></div>
                                                            </div>
                                                            <div class='col-sm-10 col-md-3 my-2' >
                                                                <label for="">Branch</label>
                                                                <select name="branch" class="form-control select2-form select2bs4-form" required id="">
                                                                     <?php
                                                                        $branchId = null;
                                                                        if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                                            $branchId = $_SESSION['branchid'];  
                                                                        }
                                                            
                                                                        $branches = selectAllBranches($conn, $branchId);
                                                            
                                                                        if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                                             
                                                                            echo '<option value="">--Select Below--</option>';
                                                                             echo '<option value="0">All Branch</option>';
                                                                        }
                                                            
                                                                        if ($branches && is_array($branches)) {
                                                                            foreach ($branches as $result) {
                                                                                $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                                                echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div class=" my-2">
                                                        <button type="button" class=" btn btn-sm btn-info" onclick="addReceiptRow()">ADD NEW ROW</button>
                                                        <button type="button" class=" btn btn-sm btn-danger" onclick="removeReceiptRow()">REMOVE LAST ROW</button>
                                                    </div>
                                                    <table class=" table table-bordered" id="recepttable-1">
                                                        <tr>
                                                            <th>Item (Credit Account)</th>
                                                            <th>Amount</th>
                                                            <th>Equivalent</th>
                                                            <th>Description</th>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                             <select name="cr_account[]" id="" class="form-control select2-form select2bs4-form" required>
                                                                               <option value="">Choose below</option>
                                                                               <?php
                                                                                 $sql = " SELECT * FROM `subsidiaries` WHERE deleted_at IS NULL";
                                                                                  $query = mysqli_query($conn, $sql);
                                                                                   while($row = mysqli_fetch_assoc($query)){
                                                                                        ?>
                                                                                   <option value="<?php echo $row['id']?>"><?php echo $row['name']?></option>
                                                                                    <?php
                                                                                   }
                                                                                      ?>
                                                                                 </select>
                                                            </td>
                                                            <td>
                                                                <input type="number"  class=" form-control amounts" step="any" name="amount[]" id="" oninput="calculateEqv1()" required >
                                                            </td>
                                                            <td>
                                                                <input type="text" name="eqv[]" id="" class=" form-control eqvs" value="0" readonly>
                                                            </td>
                                                            <td><input type="text" name="desc[]" id="" class=" form-control" ></td>
                                                        </tr>
                                                    </table>
                                                    <div class=" card-footer">
                                                       <button type="submit" name="addreciptvoucher" class=" btn btn-info btn-sm btn-block">POST</button>

                                                    </div>
                                            </form>
                                        </div>



                        </div>
                </div>
         
