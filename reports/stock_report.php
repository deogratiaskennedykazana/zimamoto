<?php
    include("../links.php");
    include("../configs.php");
    $date1 =$_POST['date1'];
    $date2 = $_POST['date2'];
    $item = $_POST['item'];
?>
<div class=" w3-container w3-border">
    <h3 class=" w3-text-blue">Stock Report</h3>

    <table class=" w3-table w3-striped">

    <thead>
        <tr class=" w3-blue">
            <td>#</td>
            <td>Date</td>
            <td>Reference NO:</td>
            <td>Stock item</td>
            <td>QTY</td>
            <td>Unit</td>
            <td>Price</td>

            <td>Balance (qty)</td>
            <td>Balance (Ammount)</td>
        </tr>
    </thead>
    <tbody>
    <?php
    $num =0;
    $quantity_total =0;
        $sql = "SELECT subsidiaries.name, stocks.* FROM subsidiaries, stocks WHERE subsidiaries.id = stocks.subsidiary_id AND stocks.subsidiary_id='$item' AND date_ BETWEEN '$date1' AND '$date2' ORDER BY date_ ";
        $result = $conn->query($sql);
        while($rows = $result->fetch_assoc()){
            $qties = $rows['quantity'];
            $price = $rows['price'];
            
            $type = $rows['type'];
            if($type =='r'){
                $quantity_total += $qties;
            } else{
                $quantity_total -= $qties;
            }
        $num++;
        ?>
        <tr>
            <td><?php echo $num?></td>
            <td><?php echo $rows['date_']?></td>
            <td><?php echo $rows['referene']?></td>
            <td><?php echo $rows['name'] ?></td>
            <td><?php echo $rows['quantity'] ?></td>
            <td><?php  ?></td>
            <td><?php echo $rows['price']?></td>
            <td><?php echo $quantity_total  ?></td>
            <td><?php echo $rows['amount'] ?></td>
        </tr>
        <?php
        }
    ?>
    </tbody>
    </table>
</div>