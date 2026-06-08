<div class=" card card-secondary">
    <div class=" card-header"> <h4 class=" card-title">Preview Voucher</h4> </div>
    <div class=" card-body">
        <?php
            $transaction = selectTransactionById($conn, $id);
            if($transaction && is_array($transaction)){
               // print_r($transaction);
                ?>
                    <table class=" table table-striped table-sm">
                        <tr>
                            <td>Ref NO:<?= $transaction['reference_no'] ?></td>
                            
                        </tr>
                        <tr>
                            <td>Date:<?= $transaction['date_'] ?></td>
                            
                        </tr>
                        <tr>
                            <td>Debit Account:<?= $transaction['debit_acc'] ?>  </td>
                        </tr>
                        <tr>
                            <td>Amount :<?= number_format( $transaction['dr_ammount'],2) ?>  </td>
                        </tr>
                        <tr>
                            <td>Currency Value :<?= number_format( $transaction['currency'],2) ?>  </td>
                        </tr>
                        <tr>
                            <td>Credit Account :<?= $transaction['credit_ac'] ?>  </td>
                        </tr>
                        <tr>
                            <td>Description :<?= $transaction['description'] ?>  </td>
                        </tr>
                        <tr>
                            <td>Branch :<?= $transaction['branch'] ?>  </td>
                        </tr>
                    </table>
                <?php
            } else{
                echo "<p>Something went wrong</p>";
            }
        ?>
    </div>
    <div class=" card-footer"></div>
</div>