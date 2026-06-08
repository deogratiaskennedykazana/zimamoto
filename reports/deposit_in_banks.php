<?php
    include("../configs.php");
    include("../links.php");
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $subsector_id =0;
?>
<div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>DEPOSITS AND LOANS IN BANKS AND FINANCIAL INSTITUTIONS FOR THE MONTH ENDED:		 </h4>
        <h4>Amount reported as TZS 0.00	</h4>
        <h5> Between <?php echo date("m-d-Y", strtotime($start_date))  ?> And <?php echo date("d-m-Y", strtotime($end_date)) ?> </h5>
    		

    </div>

    <table class=" table table-bordered">
        <thead>
            <tr>
                <th>S/No</th>
                <th>Name of Bank or Financial Institution</th>
                <th>Deposit Amounts</th>
                <th>Loan Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                   <b>  TOTAL BALANCES WITH BANKS AND OTHER FINANCIAL INSTITUTIONS </b>
                </td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>NMB</td>
                <td>0</td>
                <td>0</td>

            </tr>
            <tr>
                <td>CRDB</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td><b> AGENT BANKING ACCOUNT BALANCES (WAKALA) </b> </td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>NMB</td>
                <td>0</td>
                <td>0</td>

            </tr>
            <tr>
                <td>CRDB</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>
                       <b>TOTAL BALANCES WITH MOBILE MONEY NETWORKS</b>
                       <td>0</td>
                       <td>0</td>

                </td>

            </tr>
            <tr>
                <td>MPesa</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>TIGO PESA</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>Airetel Money</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td><b>LENDING TO SACCOS AND SECOND TIER ORGANIZATION</b></td>
                <td>-</td>
                <td>-</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <b>BORROWING FROM SACCOS AND SECOND TIER ORGANIZATION</b>

                </td>
                <td>-</td>
                <td>-</td>
            </tr>
            <tr>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
            <tr>
                <td>
                    <b>Total Net exposures*</b>

                </td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>