<?php

    require_once "../functions/subsidiary_functions.php";
    require_once "../functions/utilities_functions.php";
    require_once "../functions/min_sub_functions.php";
      require_once "../functions/user_function.php";
      require_once "../functions/min_transaction_functions.php";
      require_once "../functions/role_functions.php";
    require_once "../configs.php";
    $conn = openConn();

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

        if(isset($_GET['select_debitSubByLedgerId'])){
            $ledgerId = (int) $_GET['ledgerId'];
            ?>
                 <select name="deb_account" id="" class="form-control" required>
                <option value="">Choose Below</option>
                <?php
                $subs = selectSubsidiaryByLedgerId($conn,$ledgerId);
                if($subs && is_array($subs)){
                    foreach($subs as $sub){
                        echo "<option value='$sub[id]'>$sub[name]</option>";
                    }
                }
               
                ?>
            </select>
            <?php
        }
        if(isset($_GET['select_creditSubByLedgerId'])){
            $ledgerId = (int) $_GET['ledgerId'];
            
            ?>
                 <select name="cr_account" id="" class="form-control select2-form select2bs4-form" required>
                <option value="">Choose Below</option>
                <?php
                $subs = selectSubsidiaryByLedgerId($conn,$ledgerId);
                if($subs && is_array($subs)){
                    foreach($subs as $sub){
                        echo "<option value='$sub[id]'>$sub[name]</option>";
                    }
                }
               
                ?>
            </select>
            <?php
        }
        
        
        if(isset($_GET['get_form_unit_select'])){
                ?>
                    <select name="unit[]" id="" class=" form-control select2 select2bs4 " required>
                                                        <option value="">Select below</option>
                                                                <?php
                                                                    $units = selectAllUnit($conn);
                                                                    if($units && is_array($units)){
                                                                        foreach($units as $unit){
                                                                            echo "<option value='$unit[id]'>$unit[name]</option>";
                                                                        }
                                                                    }
                                                                ?>      

                                                    </select>
                <?php
              }
              
              
        if(isset($_GET['get_currency_value'])){
            $currencyId = (int) $_GET['currencyId'];
            $currency = selectCurrencyById($conn, $currencyId);

            ?>
                 <input  name="curr" id="curr_value" class=" form-control"   value="<?= $currency['value'] ?>">
            <?php

        }
        if(isset($_GET['select_credit_sub'])){
            ?>

            <select name="cr_account[]" id="" class="form-control select2-form select2bs4-form" required>
                <option value="">Choose below</option>
                <?php
                    $subs = getAllSubsidiaries($conn);
                    if($subs && is_array($subs)){
                        foreach($subs as $sub){
                            echo "<option value='$sub[id]'>$sub[name]</option>";
                        }
                    }
                ?>
            </select>
            <?php
        }
        if(isset($_GET['select_debit_sub'])){
            ?>

            <select name="dr_account[]" id="" class=" form-control select2-form select2bs4-form" required>
                <option value="">Choose below</option>
                <?php
                    $subs = getAllSubsidiaries($conn);
                    if($subs && is_array($subs)){
                        foreach($subs as $sub){
                            echo "<option value='$sub[id]'>$sub[name]</option>";
                        }
                    }
                ?>
            </select>
            <?php
        }
        if(isset($_GET['get_voucher_get_subs_items'])){
            ?>
                  <select name="item[]" id="" class=" select2 select2bs4 select2-form select2bs4-form form-control w3-border" required>
                                                                <option value="">Select below</option>
                                                                    <?php
                                                                        $items  = getAllSubsidiaries($conn);
                                                                        if($items && is_array($items)){
                                                                          foreach($items as $item){
                                                                            echo "<option value='$item[id]'> $item[name] </option>";
                                                                          }
                                                                        }
                                                                        ?>
                                                                </select>
            <?php
          }

if(isset($_GET['get_min_sub_by_branch_id'])){
    $branchId = (int) $_GET['branchId'];
    $data = array();
    $minsubs = [];
    
    if($branchId == 0){
        $minsubs = selectAllMinSubs($conn,null);
    } else{
        $minsubs = selectMinSubByBranchId($conn, $branchId);
    }
    
    if($minsubs && is_array($minsubs)){
        foreach($minsubs as $minsub){
            // CLEAN THE NAME - Remove invalid UTF-8 characters
            $cleanName = mb_convert_encoding($minsub['name'], 'UTF-8', 'UTF-8');
            $cleanName = preg_replace('/[^\x20-\x7E\x80-\xFF]/', '', $cleanName); // Remove weird chars
            
            $data[] = [
                'id' => $minsub['id'],
                'name' => $cleanName,
            ];
        }
    }
    
    
    echo json_encode($data);
 
}
                  if(isset($_GET['get_members_by_branch_id_json'])){
            $userId   = isset($_GET['userId'])  ? (int) $_GET['userId']  : 0;
            $branchId = isset($_GET['branchId']) ? (int) $_GET['branchId'] : 0;

            // Always resolve branch from members table (authoritative source)
            if ($userId > 0) {
                $branchStmt = $conn->prepare(
                    "SELECT branch_id FROM members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1"
                );
                if ($branchStmt) {
                    $branchStmt->bind_param("i", $userId);
                    $branchStmt->execute();
                    $branchRow = $branchStmt->get_result()->fetch_assoc();
                    $branchStmt->close();
                    if ($branchRow && !empty($branchRow['branch_id'])) {
                        $branchId = (int) $branchRow['branch_id'];
                    }
                }
            }

            // DEBUG: expose raw diagnostics when ?debug=1 is added to the URL
            if (isset($_GET['debug'])) {
                $diagSql = "SELECT u.id, u.name, u.status, u.deleted_at AS u_deleted,
                                   m.branch_id AS m_branch, m.deleted_at AS m_deleted
                            FROM users u
                            LEFT JOIN members m ON m.user_id = u.id
                            WHERE m.branch_id = $branchId
                            ORDER BY u.name";
                $diagResult = $conn->query($diagSql);
                $diagData   = $diagResult ? $diagResult->fetch_all(MYSQLI_ASSOC) : [];
                echo json_encode([
                    'resolved_branch_id' => $branchId,
                    'requesting_user_id' => $userId,
                    'raw_rows'           => $diagData,
                    'last_error'         => $conn->error,
                ]);
                exit;
            }

            $data    = [];
            $members = selectUsersByBranchId($conn, $branchId);
            if ($members && is_array($members)) {
                foreach ($members as $member) {
                    if ($userId > 0 && (int)$member['id'] === $userId) continue;
                    $data[] = [
                        'id'   => $member['id'],
                        'name' => $member['name'],
                    ];
                }
            }
            echo json_encode($data);
          }
           if(isset($_GET['get_loan_capacity_by_user_id'])){
            $userId = (int) $_GET['userId'];
            $subId = selectMinSubByUserIDAndCategory($conn, $userId,'saving');
            $amanaTransaction  = [];
            $balance = 0;
            if($subId && is_array($subId)){
                $amanaTransaction = getMinTransactionByMinSubId($conn, $subId['id']);
                  if($amanaTransaction && is_array($amanaTransaction)){
                foreach($amanaTransaction as $transaction){
                        if($transaction['dr_account'] == $subId['id']){
                            $balance += $transaction['amount'];
                        } elseif($transaction['cr_account'] == $subId['id']){
                            $balance -= $transaction['amount'];
                        }
                }
            }
            }
           echo $balance*-3;
           }

        if(isset($_GET['get_role_permissions'])){
            $roleId = (int) $_GET['role_id'];
            $existingPerms = getRolePermissions($conn, $roleId);
            $existingMap = [];
            foreach($existingPerms as $p) $existingMap[$p['module']] = $p;

            $modules = ['Dashboard', 'Members', 'Loans', 'Budget', 'Meetings', 'Reports', 'Settings', 'Users', 'Roles'];
            ?>
            <table class="table table-bordered table-sm">
                <thead><tr><th>Module</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th><th>Approve</th></tr></thead>
                <tbody>
                    <?php foreach($modules as $idx => $m): 
                        $e = $existingMap[$m] ?? [];
                    ?>
                    <tr>
                        <td>
                            <input type="hidden" name="module[<?= $idx ?>]" value="<?= $m ?>">
                            <strong><?= $m ?></strong>
                        </td>
                        <td class="text-center"><input type="checkbox" name="can_view[<?= $idx ?>]" value="1" <?= ($e['can_view'] ?? 0) ? 'checked' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" name="can_create[<?= $idx ?>]" value="1" <?= ($e['can_create'] ?? 0) ? 'checked' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" name="can_edit[<?= $idx ?>]" value="1" <?= ($e['can_edit'] ?? 0) ? 'checked' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" name="can_delete[<?= $idx ?>]" value="1" <?= ($e['can_delete'] ?? 0) ? 'checked' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" name="can_approve[<?= $idx ?>]" value="1" <?= ($e['can_approve'] ?? 0) ? 'checked' : '' ?>></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }

?>


 