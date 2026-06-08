<?php
    require_once "../../configs.php";
    require_once "../../functions/subsidiary_functions.php";
    require_once "../../functions/payroll_fuctions.php";
    print_r($_POST);
    $conn = openConn();
    $staffDetails = selectSubsidiaryById($conn, (int) $conn->real_escape_string($_POST['staff_id']));
    if($staffDetails && is_array($staffDetails)){
        $salary = 0;
        $salary += (float) $_POST['bs'];
?>

<div class=' container my-2'>
                      <h4>Salary Receipt Preview</h4>
                      <div class=" border-1 border-black ">
                          <div class=" row">
                            <div class=' col-2'>
                              <img src="../../resources/logo/logo_1_125x125.jpg" alt="" srcset="">
                            </div>
                            <div class=' col-8'><h4>Company Name</h4></div>
                          </div>
                          <div class=" my-2">
                            <h4>Salary receipt</h4>
                          </div>
                          <form action="./processes/printings/print_salary_receipt.php" method="post">
                            <div class=" my-2">
                            
                            
                            </div>
                          <div class=' my-2'>
                            <h6>Name: <?= $staffDetails['name']?> </h6>
                            <h6>Position/title: <?= $staffDetails['title']?> </h6>
                            <h6>Phone: <?= $staffDetails['phone']?> </h6>
                            <h6>Basic Salary: <?=  number_format( $_POST['bs'])?> </h6>
                            <?php
                            $allowanceAmount =0;
                              $allowance = selectAlowanceByStaffId($conn, (int) $conn->real_escape_string($_POST['staff_id']));
                              if($allowance && is_array($allowance)){
                                  $allowanceAmount = $allowance['amount'];
                              }      
                              $salary + $allowanceAmount;
                          ?>
                            <h6>Allowance: <?=  number_format( $allowanceAmount,2) ?> </h6>
                            <h6>Taxable Amount: <?=  number_format( $salary)?> </h6>
                            <h6>NSSF: <?= number_format($_POST['bs']*0.10) ?> </h6>
                            <h6>Payee: <?= number_format(calculatePayee((float) $salary)) ?> </h6>
                            <h6>Net Pay: <?= number_format($salary - calculatePayee((float) $salary) - ($_POST['bs']*0.10) ,2 ) ?> </h6>
                          </div>
                          <button type="submit" class=" btn btn-sm btn-info">Print</button>
                          </form>
                      </div>
                  </div>

                  <?php
    }
                  ?>