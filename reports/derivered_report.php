<?php
    #####################################################
    #       this is file for good derivered report      #
    #                                                   #
    #                                                   #
    #####################################################
    
    include("../configs.php");
    include("../links.php");
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    $id = $_POST['id'];

?>
<div class=" w3-container w3-border">
        <h3>Good Derivered note report </h3>

</div>
<table class=" w3-table w3-border w3-container">
    <th>
        <tr class=" w3-blue">
            <td>#</td>
            <td>Reference</td>
            <td>Item</td>
            <td>Quantity</td>
            <!-- <td>Unit</td> -->
            <td>Amount</td>

        </tr>     
    </th>
    <tbody>
        <?php
            $sql = "SELECT subsidiaries.name, stocks.* FROM subsidiaries, stocks WHERE subsidiaries.id = stocks.subsidiary_id AND stocks.type='d' AND stocks.subsidiary_id = '$id' AND date_ BETWEEN '$date1' AND '$date2'";
            $result = $conn->query($sql);
            $num =0;
            while($rows = $result->fetch_assoc()){
                $unit = $rows['units'];
                $sql2 = "SELECT * FROM units WHERE id='$unit'";
                
                $num++;
                ?>
                <tr>
                    <td><?php echo  $num ?></td>
                    <td><?php echo  $rows['referene'] ?></td>
                    <td><?php echo  $rows['name'] ?></td>
                    <td><?php echo  $rows['quantity'] ?></td>
                    <td><?php echo $rows['amount'] ?></td>
                </tr>
                <?php
            }
        ?>
    </tbody>
</table>