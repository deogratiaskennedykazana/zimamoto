<?php
include("../configs.php");
include("../links.php");
?>
 <div class=" w3-container w3-margin-top">
                                <h4>List of Vouchers</h4>
                                <table class=" w3-table">
                                    <thead>
                                        <tr class=" w3-blue">
                                            <td>Date:</td>
                                            <td>Voucher Type</td>
                                            <td>Reference No:</td>
                                            <td>Category</td>
                                            <td>Debit A/c</td>
                                            <td>Dr Amount</td>
                                            <td>Credit A/c</td>
                                            <td>Cr Amount</td>
                                            <td>Currency</td>
                                            <td>Actions</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $sql = "SELECT voucher.name AS name, transaction_voucher.* FROM voucher, transaction_voucher WHERE voucher.id = transaction_voucher.voucher_type";
                                            $query = mysqli_query($conn, $sql);
                                            while($rows = mysqli_fetch_assoc($query)){
                                                $cat_id = $rows['category_type'];
                                                $dr_account = $rows['dr_account'];
                                                $cr_account = $rows['cr_account'];
                                                $currency = $rows['currency']
                                                ?>
                                                <tr>
                                                    <td><?php echo $rows['date_']?></td>
                                                    <td><?php echo $rows['name']?></td>
                                                    <td><?php echo $rows['reference_no']?></td>
                                                    
                                                    <td>
                                                        <?php  
                                                            $sql_cat = "SELECT * FROM category WHERE id='$cat_id'";
                                                            $quer_cat = mysqli_query($conn, $sql_cat);
                                                            while($result_cat = mysqli_fetch_assoc($quer_cat)){
                                                                echo $result_cat['name'];
                                                            }

                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?Php
                                                                  $sql_cat = "SELECT * FROM `subsidiaries` WHERE id ='$dr_account'";
                                                                  $quer_cat = mysqli_query($conn, $sql_cat);
                                                                  while($result_cat = mysqli_fetch_assoc($quer_cat)){
                                                                      echo $result_cat['name'];
                                                                  }
                                                         ?>
                                                    </td>
                                                    <td><?php echo $rows['dr_ammount']?></td>
                                                    <td>
                                                        <?Php
                                                                  $sql_cat = "SELECT * FROM `subsidiaries` WHERE id ='$cr_account'";
                                                                  $quer_cat = mysqli_query($conn, $sql_cat);
                                                                  while($result_cat = mysqli_fetch_assoc($quer_cat)){
                                                                      echo $result_cat['name'];
                                                                  }
                                                         ?>
                                                    </td>
                                                    <td><?php echo $rows['dr_ammount']?></td>
                                                    <td>
                                                    <?Php
                                                                  $sql_cat = "SELECT * FROM currency WHERE id='$currency'";
                                                                  $quer_cat = mysqli_query($conn, $sql_cat);
                                                                  while($result_cat = mysqli_fetch_assoc($quer_cat)){
                                                                      echo $result_cat['name'];
                                                                  }
                                                         ?>
                                                    </td>
                                                    <td>edit</td>
                                                </tr>
                                                <?php
                                            }
                                        ?>
                                    </tbody>
                                </table>

                            </div>
                           