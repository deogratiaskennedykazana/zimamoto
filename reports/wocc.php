<?php
    include("../configs.php");
    include("../links.php");
   // print_r($_POST);
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];

    $start_date = $date1;
    $end_date = $date2;
    $amana_saving_balance =0;
    
?>

<div class="w3-margin w3-padding w3-boder w3-border-blue  ">
    <h4 class=" w3-text-blue w3-center">Tathimini Ya mali ya chama.</h4>

    <table class=" table table-bordered">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Maelezo</th>
                
                <th><?php $year = date("Y"); echo  ( $year); ?></th>
                <th><?php $year = date("Y"); echo  ( $year -1); ?></th>
                <th>Uwiano</th>
                
            </tr>
           
        </thead>
        <tr>
            <td>a.</td>
            <td>(akiba na amana/mali za chama)*100%</td>
            <td>
                <?php
                    include ("../api2/amana_api.php");

                   // echo $amana_balance;
                   $amana_saving_balance += ($amana_balance*(-1));
                    include("../api2/saving_api.php");
                    $amana_saving_balance += ($saving_balance*(-1));
                    
                    include("../api2/assets.php");
                    $sales_balance = $sales_balance*(-1);
                    echo $amana_saving_balance . "/" . $sales_balance . "<hr>";

                   echo ( number_format((($amana_saving_balance/$sales_balance)*(-1))). "%");
                
                ?>
            </td>
            <td></td>
            <td>70-80</td>
        </tr>
        <tr>
            <td>b.</td>
            <td>(Mikopo ya wanachama/ Mali za wanachama)*100%</td>
            <td>

                    <?php
                        include("../api2/loan_api.php");
                       
                        include("../api2/assets.php");
                        $sales_balance = $sales_balance* (-1);

                        echo  number_format( $loan_balance,2) ."/" . number_format( $sales_balance,2) . "<hr>";

                        echo ( number_format(($loan_balance/$sales_balance),2)*(-1). "%")

                    ?>
            </td>
            <td></td>
            <td>70-80</td>
        </tr>
        <tr>
            <td>c.</td>
            <td>(Hisa ya wanachama/ Mali za wanachama)*100%</td>
            <td>
                <?php
                    include("../api2/hisa_api.php");
                    
                    echo  number_format( $hisa_balance,2) . "/" . number_format( $sales_balance,2) . "<hr>";
                    echo number_format(($hisa_balance/$sales_balance)*(-1),2) . "%";
                ?>
            </td>
            <td></td>
            <td>10-20</td>
        </tr>
    </table>
            <h4 class=" w3-center">TATHMINI YA MTAJI WA CHAMA</h4>
            <table class=" w3-table-all">
                <tr>
                    <th>Maelezo</th>
                    <th>2020</th>
                    <th>2019</th>
                    <th></th>
                </tr>
                <tr>
                    <th>Mtaji Tete</th>
                    <td>00000</td>
                    <td>0000</td>
                    <td>0%</td>
                </tr>
                <tr>
                    <td>Mali ya chama</td>
                    <td>0000</td>
                    <td>0000</td>
                    <td>0%</td>

                </tr>
                <tr>
                    <td>Mtaji Halisi</td>
                    <td>0000</td>
                    <td>0000</td>
                    <td>0%</td>
                </tr>
                <tr>
                    <td>Mali ya chama</td>
                    <td>0000</td>
                    <td>0000</td>
                    <td>0%</td>
                </tr>
            </table>
</div>