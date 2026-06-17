
<div class=' container'>
              <div class=" card card-primary" id="modal2">
                       
                                
                                    <div class=" card-header w3-text-white bg-primary">
                                        <h4>Payment Voucher</h4>
                                       
                                    </div>
                                    <div class=" modal-body">
                                    <form enctype="multipart/form-data" class=' was-validated' action="./controllers/voucher_controller.php" method="post">
                        
                                <div class=" row m-1">
                                    <div class=" col-md-3 col-sm-10 form-group">
                                        <label for="" class=" form-label">Voucher Type</label>
                                             <select name="cat_type" id="type2" class="form-control" required onchange="selectSub2(this.value)">
                                                        <option value="">Select below</option>
                                                        <option value="22">Cash payment Voucher </option>
                                                        <option value="23">Bank payment Voucher</option>    
                                                    
                                                    </select> 
                                    </div>
                                    <div class=" col-md-3 col-sm-10 form-group">
                                        <label for="" class=" form-label"> Date</label>
                                        <input type="date" name="voucherdate" id=""  max='<?php echo date("Y-m-d") ?>' value="<?php echo date("Y-m-d") ?>" class=" form-control" >
                                    </div>
                                    <div class=" col-md-3 col-sm-10 form-group">
                                        <label for="" class="  form-label">Voucher Reference</label>
                                        <?php $v = "PV/"; ?>
                                        <?php 
                                            $sql ="SELECT COUNT(transaction_voucher.id) id FROM `transaction_voucher` WHERE reference_no LIKE 'PV/%';";
                                            $result = $conn->query($sql);
                                            $row = $result->fetch_assoc();
                                            $ref =  $row['id'] +1;
                                        ?>
                                        <input type="text" name="voucher_ref" id="" value="<?php echo $v. $ref ?>" class=" form-control" readonly>
                                </div>
                                    <div class=" col-md-3 col-sm-10 form-group">
                                      <label for="" class=' form-label'>Branch</label>
                                      <select name="branchId" required id="branchId" class="form-control select2-form select2bs4-form">
                                           <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                     echo '<option value="0">All Branches</option>';
                                                    
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

                                    <div class=" col-md-3 col-sm-10 form-group">
                                    <label for="" class=" form-label">Credit Account</label>
                                    <div class="" id='sub2'>
                                   
                                    </div>
                                    </div>
                                    <div class=" form-group col-md-3 col-sm-10 ">
                                                <label for="" class=" form-label">Currency Used</label>
                                                <select name="currency" required id="" class="form-control select2-form select2bs4-form" onchange="selectCurrency(this.value)">                                            
                                                <option value="">Select below</option>
                                               <?php     
                                                        $sql = "SELECT * FROM currencies";
                                                        $query = mysqli_query($conn,$sql);
                                                        while($rows = mysqli_fetch_assoc($query)){
                                                        ?>
                                                        <option value="<?php echo $rows['id']?>"><?php echo $rows['name'] ?></option>
                                                 <?php       
                                                   }
                                                   ?>
                                    
                                                   
                                    </select>

                                                </div>
                                    
                                    <div class=" col-md-3 col-sm-10 form-group">
                                    <label for="" class=" form-label">Exchange Rate</label>
                                  <!-- <input  name="curr" id="curr_value" class=" form-control"  readonly value="1.0"> -->

                                        <div id="currencyp">

                                        </div>
                                    </td>
                                    
                              </div>
                                
                               </div>
                                <div class=" my-2">
                                   <button type="button"  id="add-row-btn" class="btn btn-sm btn-info" onclick="addpaymentVoucherRow()">Add Row</button>
                                   <button type="button" class=' btn btn-danger btn-sm'  onclick="removeTableRow('receipt-voucher-table')">Remove row</button>
                                    
                                </div>
                                <table class=' table table-sm' id='receipt-voucher-table'>
                                    <tr>
                                        <td  class=" form-label">Account To Debit</td>
                                        <td> <label for="" class=" form-label"> Amount</label></td>
                                        <td> <label for="" class=" form-label"> Equvalent Amount</label></td>
                                        
                                  
                                        <td>  <label for="" class=" form-label">Description</label></td>
                                    </tr>
                                <tr>
                                        <td >
                                                
                                                <select name="dr_account[]" id="" step='any' required class="form-control select2-form select2bs4-form">
                                                    <option value="">Choose below</option>
                                                    <?php
                                                        $sql = " select * from subsidiaries ";
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
                                        <input type="number"  class=" form-control amounts" step="any" name="amount[]" id="" oninput="calculateEqv1()" required>
                                        </td>
                                        
                                            <td>
                                            <input type="text" name="eqv[]" id="" class=" form-control eqvs" readonly>

                                            </td>        
                                        
                                       
                                       
                                          <td>
                                            <input type="text" name="desc[]" class=" form-control">
                                        </td>
                                    </tr>
                                </table>
                             

                         </div>
                         <hr>
                               

                               
                                    <br>

                                    
                                    </div>
                                    <div class=" card-footer">
                                    <p class=" " id="vch"> 
                                         <!--<button type="submit" class=" btn btn-success" name="addvoucher">POST </button>-->
                                         <button type="submit" name="addpaymentVoucher"  class=" btn btn-success  btn-block"  >POST</button>
                                         

                                    </p>
                                  
                                

                        </div>
                            </form>
                                    
                                     <!-- end of modal2 -->
              </div>
            
   