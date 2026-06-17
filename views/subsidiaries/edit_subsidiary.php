<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Edit Subsidiary</h4>
    </div>
    <form action="./controllers/subsidiary_controller.php" class="was-validated" method="post">
        <div class="card-body">
            <?php
                $sub = selectSubsidiaryById($conn, $id);
                if ($sub && is_array($sub)) {
            ?>
                <div class="form-group">
                    <label for="subsidiary_name">Subsidiary Name</label>
                    <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                    <input type="text" class="form-control" required name="name" id="subsidiary_name" value="<?= $sub['name'] ?>">
                </div>
                <div class="form-group">
                    <label for="subsidiary_type">Subsidiary Type</label>
                    <select name="type" required class="form-control select2-form select2bs4-form">
                        <option value="">Select Below</option>
                        <?php
                            $types = ['supplier','customer','staff','stock','asset','others'];
                            foreach ($types as $type) {
                                $selected = ($sub['type'] === $type) ? 'selected' : '';
                                echo "<option value='$type' $selected>" . ucfirst($type) . "</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ledger_id">Subsidiary Ledger</label>
                    <select name="ledger_id" required class="form-control select2-form select2bs4-form" id="ledger_id">
                        <?php
                            $currentLedger = selectLedgerById($conn, $sub['ledger_id']);
                            if ($currentLedger && is_array($currentLedger)) {
                                echo "<option value='{$currentLedger['id']}'> {$currentLedger['name']} </option>";
                            }
                            $ledgers = selectAllActiveLedgers($conn);
                            if ($ledgers && is_array($ledgers)) {
                                foreach ($ledgers as $ledger) {
                                    if ($ledger['id'] != $sub['ledger_id']) {
                                        echo "<option value='{$ledger['id']}'> {$ledger['name']} </option>";
                                    }
                                }
                            }
                        ?>
                    </select>
                </div>
            <?php } ?>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-info btn-block" name="edit_subsidiary">
                Edit Subsidiary
            </button>
        </div>
    </form>
</div>