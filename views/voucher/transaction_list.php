<div class=" card card-secondary">
    <div class=" card-header"> <h4 class=" card-title">Pending Voucher List</h4> </div>
    <div class=" card-body">
        <table class=" table table-sm table-border table-striped ">
            <tr>
                <td>#</td>
                <td>Ref No</td>
                <td>Debit Account </td>
                <td>Amount</td>
                <td>Currency Value</td>
                <td>Credit Account</td>
                <td>Description</td>
                <td>action</td>
            </tr>
          <?php
                $branchId = ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') 
                            ? $_SESSION['branchid'] 
                            : null;
            
                $vouchers = selectTransactionByStatus($conn, "active", $branchId);
                if ($vouchers && is_array($vouchers)) {
                    $counter = 1;
                    foreach ($vouchers as $voucher) {
                        echo "<tr>";
                        echo "<td>$counter</td>";
                        echo "<td>{$voucher['reference_no']}</td>";
                        echo "<td>{$voucher['debit_acc']}</td>";
                        echo "<td>" . number_format($voucher['dr_ammount'], 2) . "</td>";
                        echo "<td>" . number_format($voucher['currency'], 2) . "</td>";
                        echo "<td>{$voucher['credit_ac']}</td>";
                        echo "<td>{$voucher['description']}</td>";
                        echo "<td>
                                <a class='btn btn-info btn-sm' href='./?page=preview_voucher&voucher_id={$voucher['id']}'>Preview</a>
                              </td>";
                        echo "</tr>";
                        $counter++;
                    }
                }
            ?>

        </table>
    </div>
</div>