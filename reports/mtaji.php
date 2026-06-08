<?php
    include("../configs.php");
    include("../links.php");
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    $balance1 = 0;
    $main_balance = 0;
    $malimbikizo_balance =0;
    $elimu_balance =0;
    $kinga_madeni_balance =0;
    $kukombolea_hisa_balance =0;
    $akiba_lazima_balance = 0;
    $upungufu_balance = 0;
    //row balance
    $ziada_balance = 0;
?>

<div class=" w3-margin w3-padding " >
    <h4 class=" w3-center">MABADILIKO YA MTAJI</h4>
    <table class=" table table-bordered w3-card">
        <thead>
            <tr>
                <th>MAELEZO</th>
               
                <th>AKIBA YA LAZIMA</th>
                <th>AKIBA YA KUKOMBOLEA HISA</th>
                <th>KINGA YA MADENI MABAYA</th>
                <th>MFUKO WA ELIMU</th>
                <th>MALIMBIKIZO YA ZIADA (UPUNGUFU)</th>
                <th>JUMLA</th>

            </tr>
            <tr>
                
                <td></td>
               
                <td>TSH</td>
                <td>TSH</td>
                <td>TSH</td>
                <td>TSH</td>
                <td>TSH</td>
            </tr>
            <tr>
                
                <td></td>
                <td>20%</td>
                <td>15%</td>
                <td>15%</td>
                <td>10%</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td> <b> Bakaa Anzia 01.01.2023 </b></td>
                
                <td>
                    <?php
                        $sql = "SELECT * FROM `starting_balance` WHERE account_id ='992'";// AND `date_` BETWEEN 'Y-01-01' AND 'Y-12-31'";
                        $result = $conn->query($sql);
                        if($result->num_rows>0){
                            $row = $result->fetch_assoc();
                            echo number_format( $row['ammount'],2);
                            $balance1 +=$row['ammount'];
                            $akiba_lazima_balance += $row['ammount'];
                        }
                    ?>

                </td>
                <td>
                <?php
                        $sql1 = "SELECT * FROM `starting_balance` WHERE account_id ='993'";// AND `date_` BETWEEN 'Y-01-01' AND 'Y-12-31'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows>0){
                            $row1 = $result1->fetch_assoc();
                            echo number_format( $row1['ammount'],2);
                            $balance1 +=$row1['ammount'];
                            $kukombolea_hisa_balance += $row1['ammount'];
                        }
                    ?>

                </td>
                <td>
                <?php
                        $sql2 = "SELECT * FROM `starting_balance` WHERE account_id ='995'";// AND `date_` BETWEEN 'Y-01-01' AND 'Y-12-31'";
                        $result2 = $conn->query($sql2);
                        if($result2->num_rows>0){
                            $row2 = $result2->fetch_assoc();
                            echo number_format( $row2['ammount'],2);
                            $balance1 +=$row2['ammount'];
                            $kinga_madeni_balance += $row2['ammount'];
                        }
                    ?>
                </td>
                <td>
                <?php
                        $sql3 = "SELECT * FROM `starting_balance` WHERE account_id ='994'";// AND `date_` BETWEEN 'Y-01-01' AND 'Y-12-31'";
                        $result3 = $conn->query($sql3);
                        if($result3->num_rows>0){
                            $row3 = $result3->fetch_assoc();
                            echo number_format( $row3['ammount'],2);
                            $balance1 +=$row3['ammount'];
                            $elimu_balance += $row3['ammount'];
                        }
                    ?>
                </td>
                <td>
                <?php
                        $sql4 = "SELECT * FROM `starting_balance` WHERE account_id ='996'";// AND `date_` BETWEEN 'Y-01-01' AND 'Y-12-31'";
                        $result4 = $conn->query($sql4);
                        if($result4->num_rows>0){
                            $row4 = $result4->fetch_assoc();
                            echo number_format( $row4['ammount'],2);
                            $balance1 +=$row4['ammount'];
                            $malimbikizo_balance += $balance1;
                            $upungufu_balance += $row4['ammount'];
                        }
                    ?>

                </td>
                <td><?php echo  number_format( $balance1,2) ?></td>
            </tr>
            <?php
                $sql5 = "SELECT mabadiliko_mtaji_item.name,mabadiliko_mtaji_values.*
                         FROM mabadiliko_mtaji_item,mabadiliko_mtaji_values 
                          WHERE mabadiliko_mtaji_item.id =mabadiliko_mtaji_values.item";
                $result5 = $conn->query($sql5);
                if($result5->num_rows>0){
                    while($rows5 = $result5->fetch_assoc()){
                        $ziada_balance += $rows5['akiba_ya_lazima'];
                        $ziada_balance += $rows5['akiba_ya_kukomboa_hisa'];
                        $ziada_balance += $rows5['kinga_ya_madeni_mabaya'];
                        $ziada_balance += $rows5['mfuko_wa_elimu'];
                        $ziada_balance += $rows5['upungufu'];
                        $kinga_madeni_balance +=$rows5['kinga_ya_madeni_mabaya'];
                        $elimu_balance += $rows5['mfuko_wa_elimu'];
                        $akiba_lazima_balance +=  $rows5['akiba_ya_lazima'];
                        $kukombolea_hisa_balance += $rows5['akiba_ya_kukomboa_hisa'];
                        $upungufu_balance +=$rows5['upungufu'];
                        // $malimbikizo_balance += $rows5['upungufu'];
                         echo "<tr>";
                         echo "<td>".$rows5['name']."</td>";
                         echo "<td>". number_format( $rows5['akiba_ya_lazima'],2)."</td>";
                         echo "<td>". number_format( $rows5['akiba_ya_kukomboa_hisa'],2)."</td>";
                         echo "<td>". number_format( $rows5['kinga_ya_madeni_mabaya'],2)."</td>";
                         echo "<td>". number_format( $rows5['mfuko_wa_elimu'],2)."</td>";
                         echo "<td>". number_format( $rows5['upungufu'],2)."</td>";
                         echo "<td>". number_format( $ziada_balance,2)."</td>";
                         echo "</tr>";
                         $balance1 += $ziada_balance;
                    }
                }
            ?>
            
            <tr class=" w3-red">
                <td>Bakaa <?php echo date("d-m-Y", strtotime($date2)) ?></td>
                <td>
                <?php
                        echo number_format(  $akiba_lazima_balance,2);
                    ?>
                </td>
                <td>
                <?php
                        echo number_format( $kukombolea_hisa_balance,2);
                    ?>
                </td>
                <td>
                <?php
                        echo number_format( $kinga_madeni_balance,2);
                    ?>
                </td>
                <td>
                <?php
                        echo number_format( $elimu_balance,2);
                    ?>
                </td>
                <td>
                    <?php
                        echo number_format( $upungufu_balance,2);
                    ?>
                </td>
                <td>
                    <?php
                        $main_balance += $balance1;
                        echo number_format( $main_balance,2);
                    ?>
                </td>
            </tr>
           
        </tbody>
    </table>
</div>