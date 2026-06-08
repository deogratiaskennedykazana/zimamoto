<?php
    include("../configs.php");
$conn = openConn();

       $month = $_POST['month'];
    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

?>
<div class=" w3-margin table-responsive table-responsive-xl table-responsive-lg  ">
                        <h4>Payroll Voucher between <?php echo date("d-m-Y", strtotime($start_date)) ?> AND <?php echo date("d-m-Y", strtotime($end_date)) ?> </h4>
                        <button class=" btn btn-danger" data-bs-target="#voucher" data-bs-toggle="modal">GENERATE JOURNAL VOUCHER</button>
                        <table class=" table table-bordered table-striped">
                            <thead class=" table-success">
                                <tr class=" w3-center">
                                    <td rowspan="2">#</td>
                                    <td rowspan="2">Employee`s name</td>
                                    <td rowspan="2">Titles</td>
                                    <td rowspan="2">ID</td>
                                    <td rowspan="2">TIN</td>
                                    <td rowspan="2">Basic Pay</td>
                                    <td rowspan="2">Allowances</td>
                                    <td rowspan="2">Gros pay</td>
                                    <td rowspan="2">NSSF (10%)</td>
                                    <td rowspan="2">Taxable Amount</td>
                                    <td colspan="4">Deducations</td>
                                    <td rowspan="2" class=''>NET PAY</td>
                                    <td colspan="4">Employment Costs</td>
                                </tr>
                                <tr>
                                    
                                    <td>PAYE</td>
                                    <td>Advance Cash</td>
                                    <td>loans</td>
                                    <td>Total</td>
                                    
                                    <td>Employer NSSF</td>
                                    <td>SDL</td>
                                    <td>WCF</td>
                                    <td>Total</td>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $total_basic_pay =0;
                                    $total_gross_pay = 0;
                                    $total_allowance =0;
                                    $total_nssf =0;
                                    $total_taxable_amount =0;
                                    $total_payee =0;
                                    $total_advance_cash = 0;
                                    $total_loan =0;
                                    $total_deducation =0;
                                    $total_net_pay =0;
                                    $total_sdl =0;
                                    $total_wcf =0;
                                    $total_employer_cost =0;
                                    
                                    
                                    $sql1 = "SELECT * FROM subsidiaries WHERE type='staff' ORDER BY name";
                                    $result1 =$conn->query($sql1);
                                    if($result1->num_rows>0){
                                        $counter =1;
                                        while($rows1 = $result1->fetch_assoc()){
                                            $emp_id = $rows1['id'];
                                            
                                            $basic_pay =0;
                                            $gross_pay = 0;
                                            $allowance =0;
                                            $nssf =0;
                                            $taxable_amount =0;
                                            $payee =0;
                                            $advance_cash = 0;
                                            $loan =0;
                                            $deducation =0;
                                            $net_pay =0;
                                            $sdl =0;
                                            $wcf =0;
                                            $employer_cost =0;

                                            echo "<tr class=' w3-hover-blue w3-hover-text-white'>";
                                                echo "<td>$counter</td>";
                                                echo "<td>".$rows1['name']."</td>";
                                                echo "<td></td>";
                                                echo "<td></td>";
                                                echo "<td>".$rows1['tin']."</td>";
                                                $sql2 = "SELECT * FROM `salary` WHERE subsidiary_id='$emp_id'";
                                                $result2 = $conn->query($sql2);
                                                if($result2->num_rows>0){
                                                    $rows2 = $result2->fetch_assoc();
                                                    $basic_pay = $rows2['amount'];
                                                    $gross_pay += $basic_pay;
                                                    $total_basic_pay += $basic_pay;
                                                    echo "<td>".number_format($basic_pay,2)."</td>";
                                                } else{
                                                    echo "<td>".number_format($basic_pay,2)."</td>";
                                                    

                                                }
                                                // get allowance
                                                $sql3 = "SELECT amount as allowance from allowance_staffs WHERE staff_id='$emp_id' AND `date_` BETWEEN '$start_date' AND '$end_date' ";
                                                $result3 = $conn->query($sql3);
                                                if($result3->num_rows>0){
                                                    while($rows3 = $result3->fetch_assoc()):
                                                           $allowance += $rows3['allowance'];
                                                            $total_allowance += $allowance;
                                                    $gross_pay += $allowance;
                                                    endwhile;
                                                 
                                                   
                                                   
                                                }
                                                 echo "<td>".number_format($allowance). "</td>";
                                                $total_gross_pay +=$gross_pay;
                                                $nssf += ($gross_pay*0.1);
                                                $total_nssf +=$nssf;
                                                $taxable_amount +=($gross_pay-$nssf);
                                                $total_taxable_amount += $taxable_amount;
                                                if($taxable_amount<= 270000){
                                                    $payee = 0;

                                                } elseif($taxable_amount<=520000){
                                                  $remain =  ($taxable_amount - 270000)* 0.08;
                                                  $payee = $remain;

                                                } elseif($taxable_amount<=760000){
                                                    $remain = (($taxable_amount -520000)*0.2)+ 20000;
                                                    $payee = $remain;
                                                } elseif($taxable_amount<=1000000){
                                                    $remain = (($taxable_amount -760000)*0.25)+68000;
                                                    $payee = $remain;
                                                } elseif($taxable_amount> 1000000){
                                                    $remain = (($taxable_amount -1000000)*0.3)+ 128000;
                                                    $payee = $remain;

                                                }
                                                $deducation += $payee;
                                            $total_payee += $payee;
                                           echo "<td>".number_format($gross_pay,2)."</td>";
                                           echo "<td>".number_format($nssf,2)."</td>";
                                           echo "<td>".number_format($taxable_amount,2)."</td>";
                                           echo "<td>".number_format($payee,2)."</td>";
                                        //    get advance
                                                $sql4 = "SELECT * FROM `adv_cashes` WHERE `subsidiary_id`= '$emp_id'";
                                                $result4 =$conn->query($sql4);
                                                if($result4->num_rows>0){
                                                    while($rows4 = $result4->fetch_assoc()):
                                                        $date = date("Y-m-t", strtotime($rows4['date_']));
                                                        if($date >= $start_date && $date <= $end_date):
                                                            $advance_cash += $rows4['amount'];
                                                          
                                                        endif;
                                                    endwhile;
                                                    
                                                }
                                                echo "<td>".number_format($advance_cash)."</td>";
                                                    $total_advance_cash += $advance_cash;
                                                    $deducation += $advance_cash;
                                           
                                                //get loan
                                                $sql5 = "SELECT amount as mkopo FROM `loans` WHERE `subsidiary_id`= '$emp_id' AND `date_` BETWEEN '$start_date' AND '$end_date'";
                                                $result5 = $conn->query($sql5);
                                                if($result5->num_rows>0){
                                                    
                                                    while($rows5 = $result5->fetch_assoc()):
                                                        $loan += $rows5['mkopo'];
                                                          $deducation += $loan;
                                                    endwhile;
                                                    
                                                 
                                                  

                                                }
                                                   echo "<td>".number_format($loan)."</td>";

                                                $net_pay =($taxable_amount- $deducation);
                                                $sdl = $basic_pay* 0.035;
                                                $wcf = $basic_pay * 0.005; // wcf
                                                $employer_cost = $nssf + $wcf + $sdl;
                                                
                                                echo "<td>".number_format($deducation,2)."</td>";
                                                echo "<td>".number_format($net_pay,2)."</td>";
                                                echo "<td>".number_format($nssf,2)."</td>";
                                                echo "<td>".number_format($sdl,2)."</td>";
                                                echo "<td>".number_format($wcf,2)."</td>";
                                                echo "<td>".number_format($employer_cost,2)."</td>";
                                                $total_wcf += $wcf;
                                                $total_sdl += $sdl; 
                                                $total_deducation += $deducation;
                                                $total_net_pay += $net_pay;
                                                $total_employer_cost += $employer_cost;
                                            echo "</tr>";
                                            $counter++;
                                        }
                                        echo "<tr class=' w3-red'>";
                                          echo "<td></td>";
                                          echo "<td>TOTAL</td>";
                                          echo "<td></td>";
                                          echo "<td></td>";
                                          echo "<td></td>";
                                          echo "<td>".number_format($total_basic_pay,2)."</td>";
                                          echo "<td>".number_format($total_allowance,2)."</td>";
                                          echo "<td>".number_format($total_gross_pay,2)."</td>";
                                          echo "<td>".number_format($total_nssf,2)."</td>";
                                          echo "<td>".number_format($total_taxable_amount,2)."</td>";
                                          echo "<td>".number_format($total_payee,2)."</td>";
                                          echo "<td>".number_format($total_advance_cash,2)."</td>";
                                          echo "<td>".number_format(000,2)." </td>";
                                          echo "<td>".number_format($total_deducation,2)."</td>";
                                          echo "<td>".number_format($total_net_pay,2)."</td>";
                                          echo "<td>".number_format($total_nssf,2)."</td>";
                                          echo "<td>".number_format($total_sdl,2)."</td>";
                                          echo "<td>".number_format($total_wcf,2)."</td>";
                                          echo "<td>".number_format($total_employer_cost,2)."</td>";

                                        echo "</tr>";
                                    }
                                ?>
                            </tbody>
                        </table>


                        <!-- voucher -->
                        <div class=" modal fade" id="voucher">
                            <div class=" modal-dialog modal-lg">
                                    <div class=" modal-content">
                                        <div class=" modal-header bg-info w3-text-white">
                                             <h4> JOURNAL VOUCHER </h4>
                                             <button class=" btn btn-danger" data-bs-dismiss="modal">&times;</button>
                                       
                                      </div>

                                        <div class=" modal-body w3-margin">
                                            <form action="../process/payroll_journal_voucher.php" method="post">
                                                    <label for="" class=" w3-text-blue">Date</label>
                                                    <input type="date" name="date" max="<?php echo date("Y-m-d") ?>" class=" w3-input w3-border" required id="">
                                                <table class=" table table-bordered ">
                                                        <tr class=" w3-red">
                                                            <td>A/c Debit</td>
                                                            <td>Amount</td>
                                                            <td>A/c Credit</td>
                                                            <td>Description</td>
                                                        </tr>
                                                        <tr>
                                                                    <td>
                                                                        <input type="text" value="Salaries Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="273" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo round( $total_net_pay,2) ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="Salary Payable" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="483" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="Desc" id="" readonly>
                                                                    </td>    
                                                             </tr>
                                                              <tr>
                                                                    <td>
                                                                        <input type="text" value="Salaries Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="273" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo round( $total_nssf,2) ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="NSSF Payable" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="484" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="NSSF payable" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    <tr>
                                                                    <td>
                                                                        <input type="text" value="Salaries Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="273" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo $total_payee ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="Paye Payable" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="485" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="Paye Payable" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    <tr>
                                                                    <td>
                                                                        <input type="text" value="SDL Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="486" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo round( $total_sdl,2) ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="SDL payable" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="487" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="SDL Payable" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    <tr>
                                                                    <td>
                                                                        <input type="text" value="NSSF Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="274" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo round($total_nssf,2) ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="NSSF Payables" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="484" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="NSSF Payable" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    <tr>
                                                                    <td>
                                                                        <input type="text" value="WCF Expences" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="488" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo  round( $total_wcf,2) ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="WCF Payable" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="489" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="WCF Paybale" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    <tr>
                                                                    <td>
                                                                        <input type="text" value="Salaries Expenses" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="273" name="dr_account[]" required >
                                                                    </td>
                                                                    <td><input type="text" name="amount[]" value="<?php echo $total_advance_cash ?>" class=" w3-input w3-border" id=""></td>
                                                                    <td> 
                                                                        <input type="text" value="Salary Advance" readonly class=" w3-border w3-input" id="">
                                                                        <input type="hidden" value="388" name="cr_account[]" required >
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="dec[]" class=" w3-border w3-input" value="Salary advance" id="" readonly>
                                                                    </td>    
                                                     </tr>
                                                    
                                                   
                                                    </table>

                                                    <button type="submit" class=" btn btn-danger" name="submit_voucher">SUBMIT VOUCHER</button>
                                            </form>
                                        </div>
                                        <div class=" modal-footer bg-info w3-text-white">
                                            <i>Powered by <a href="http://www.tellicerp.co.tz" target="_blank" rel="noopener noreferrer">tellicerp</a></i>
                                        </div>
                                    </div>
                            </div>
                        </div>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
         <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
         <script src="https://www.w3schools.com/lib/w3.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
         <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

                            
                                