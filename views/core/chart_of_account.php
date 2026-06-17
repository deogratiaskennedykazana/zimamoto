
                <div class=" card card-info">
                    <div class=" card-header"> <h4> Chart Of Account </h4></div>
                    <div class=" card-body">
                    <table class=" table table-bordered" id='coa'>
                            <thead class=" table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Master</th>
                                    <th>Sub master</th>
                                    <th>Ledger</th>
                                    <th>subsidiary</th>
                                </tr>

                            </thead>
                            <tbody>
                                <?php
                                        $select  ="SELECT 
                                                  master.id, 
                                                  master.name AS master, 
                                                  submain.id, 
                                                  submain.name AS submain, 
                                                  ledgers.id, 
                                                  ledgers.name AS ledger, 
                                                  subsidiaries.id, 
                                                  subsidiaries.name AS subs, 
                                                  subsidiaries.ledger_id
                                              FROM 
                                                  master
                                              JOIN 
                                                  submain 
                                                  ON submain.master_id = master.id
                                              JOIN 
                                                  ledgers 
                                                  ON ledgers.submain_id = submain.id
                                              JOIN 
                                                  subsidiaries 
                                                  ON subsidiaries.ledger_id = ledgers.id;";
                                        $result_1 = $conn->query($select);
                                        $counter =1;
                                        if($result_1->num_rows>0){
                                            while($rows_1 =$result_1->fetch_assoc() ){

                                                ?>
                                                <tr class='item'>
                                                    <td ><?php echo $counter; ?></td>
                                                    <td ><?php echo $rows_1['master']; ?></td>
                                                    <td><?php echo $rows_1['submain']; ?></td>
                                                    <td><?php echo $rows_1['ledger']; ?></td>
                                                    <td><?php echo $rows_1['subs']; ?></td>

                                                </tr>
                                                <?php
                                                $counter++;
                                            }
                                        }
                                ?>
                            </tbody>
                            <tfoot class=" table-primary">
                            <tr>
                                    <th>#</th>
                                    <th>Master</th>
                                    <th>Sub master</th>
                                    <th>Ledger</th>
                                    <th>subsidiary</th>
                                </tr>
                                </tfoot>


                        </table>
                    </div>
                    <div class=" card-footer bg-info"> -- End -- </div>
                </div>
              <?php
        