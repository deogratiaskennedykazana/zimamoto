 
                        <div class=' card card-primary'>
                            <div class=' card-header'>Add Other Subsidiary Form</div>
                            <div class=' card-body'>
                               <form class="was-validated" action="./controllers/subsidiary_controller.php" method="post">
                                    <label for="" class=" form-label">Ledger</label>
                                    <select name="ledger" id="" class=" form-control select2-form select2bs4-form" required>
                                        <option value="">Select Ledger</option>
                                        <?php
                                            $ledgers = selectAllActiveLedgers($conn);
                                            if($ledgers && is_array($ledgers)){
                                                foreach($ledgers as $ledger){
                                                    echo "<option value='$ledger[id]'>$ledger[name]</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                    <br>
                                    <label for="" class=" form-label">Name:</label>
                                    <input type="text" name="name" id="" class=" form-control" required>
                                    <br>
                            </div>
                            <div class=' card-footer'>
                                <button class="btn btn-primary w-100 " name="addothers" type="submit">Add Account</button>
                            </div>
                            </form>
                        </div>
                    