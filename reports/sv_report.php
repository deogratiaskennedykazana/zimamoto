<?php 
    include("../configs.php");
    include("../links.php");
    $id = $_GET['id'];
    $sql ="SELECT subsidiaries.name, voucher.name as voucher, sales_voucher.* FROM subsidiaries, sales_voucher, voucher WHERE subsidiaries.id= sales_voucher.dr_account AND voucher.id= sales_voucher.voucher_type AND sales_voucher.id='$id'";

$result = $conn->query($sql);
$rows = $result->fetch_assoc();
$customer_id = $rows['dr_account'];

//customer details
$sqlc = "SELECT * FROM subsidiaries WHERE id= '$customer_id'";
$resultc = $conn->query($sqlc);
$rowc = $resultc->fetch_assoc();

$sqld = "SELECT * FROM company_details";
$resultd = $conn->query($sqld);
$rowd = $resultd->fetch_assoc();
?>
<div>
    <?php include("../header.php")?>
</div>
<div class=" w3-margin-bottom w3-margin-top w3-padding w3-row w3-border-bottom ">
    <div class=' w3-third w3-text-white' >.</div>
    <div class=' w3-third ' > <h5>Tax Invoice</h5></div>
    <div class=' w3-third ' > <a href="../pdfs/sv_pdf.php?id=<?php echo $id?>" class=" w3-btn bg-danger">Generate PDF</a> </div>
</div>
<div class=" w3-row">


        <div class=" w3-half w3-margin">
                <h5>Customer Details</h5>
                <table>
                    <tr>
                        <td>Name: <?php echo $rowc['name'];?> </td>
                    </tr>
                    <tr>
                        <td>TIN: <?php echo $rowc['tin'];?> </td>
                    </tr>
                    <tr>
                        <td>Address: <?php echo $rowc['address'];?> </td>
                    </tr>
                    <tr>
                        <td>Phone: <?php echo $rowc['phone'];?> </td>
                    </tr>
                    <tr>
                        <td>Email: <?php echo $rowc['email'];?> </td>
                    </tr>
                </table>
            </div>
            <div>
                <h4>Company Details</h4>
                <div class=" w3-third w3-margin">
                
                <?php echo $rowd['title'];?> <br>
                <?php echo $rowd['address'];?> <br>
                Phone <?php echo $rowd['phone'];?> <br>
                Email: <?php echo $rowd['email'];?> <br>
                TIN: <?php echo $rowd['tin'];?>

</div>

            </div>
    </div>


    <div class="  w3-border">
       
   
        <div class="  w3-margin w3-threequarter">
                <h4>Tax  Details</h4>
                    <table class=" w3-table">
                        <tr>
                            <td>REF No: <?php echo $rows['reference'] ?> </td>
                            <td>Date: <?php echo $rows['date_']?></td>
                        </tr>
                        <tr>
                            <td>Customer: <?php echo $rows['name'] ?> </td>
                            <td>Voucher: <?php echo $rows['voucher']?></td>
                        </tr>
                    </table>

        


            </div> <br>
        <div class="w3-threequarter w3-margin ">

    
    <h4>Tax Details</h4>
     <table class=" table w3-striped table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Item (description)</th>
                <th>Quanity</th>
                <th>Price</th>
                <th>Amount</th>

            </tr>
        </thead>
        <tbody>
        <?php
                $sql = "SELECT subsidiaries.name, sales_voucher_details.* FROM subsidiaries,sales_voucher_details WHERE subsidiaries.id = sales_voucher_details.cr_account AND sales_voucher_details.sv_id='$id'";
              //  $sql = "SELECT subsidiaries.name, purchase_voucher_details.* FROM subsidiaries, purchase_voucher_details WHERE subsidiaries.id = purchase_voucher_details.dr_account_id AND purchase_voucher_details.pv_id='$id'";
               
                $result = $conn->query($sql);
                if($result->num_rows>0){
                    $total = 0;
                    $num =0;
                    while($rows = $result->fetch_assoc()){
                        $num++;
                        $total += $rows['amount'];
                        ?>
                        <tr>
                            <td><?php echo $num;?></td>
                            <td><?php echo $rows['name'];?></td>
                            <td><?php echo $rows['quantity'];?></td>
                            <td><?php echo $rows['price'];?></td>
                            <td><?php echo $rows['amount'];?></td>


                            
                        </tr>
                        <?php
                    }
                    echo "<tr class=' w3-red'>";
                    echo "<td>Grand Total</td>";
                    echo "<td></td><td></td><td></td>";
                    echo "<td>" .$total ."</td>";
                } else{
                    echo "no data";
                }
            ?>
        </tbody>
    </table>
    </div>
</div>