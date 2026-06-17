<div class="card card-info">
  <div class="card-header">
    <h3>Journal Voucher</h3>
  </div>

  <form action="./controllers/voucher_controller.php" method="post" class="was-validated">
    <div class="card-body">
      <!-- Row 1: Voucher Date, Branch, Reference -->
      <div class="row mb-3">
        <div class="col-md-4 col-sm-12">
          <label class="w3-text-blue">Select Voucher Date</label>
          <input type="date" name="voucherdate" class="form-control" required>
        </div>
        <div class="col-md-4 col-sm-12">
          <label class="form-label">Branch</label>
          <select name="branchId" id="branchId" class="form-control select2-form select2bs4-form" required>
            <?php
              $branchId = null;
              if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                  $branchId = $_SESSION['branchid'];
              }

              $branches = selectAllBranches($conn, $branchId);

              if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                  echo '<option value="">--Select Below--</option>';
              }

              if ($branches && is_array($branches)) {
                  foreach ($branches as $result) {
                      $selected = ($branchId == $result['id']) ? 'selected' : '';
                      echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                  }
              }
            ?>
          </select>
        </div>
        <div class="col-md-4 col-sm-12">
          <label>Voucher Reference</label>
          <?php
            $v = "JV/";
            $sql = "SELECT COUNT(transaction_voucher.id) id FROM `transaction_voucher` WHERE 1";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $ref =  $row['id'] + 1;
          ?>
          <input type="text" name="voucher_ref" value="<?php echo $v . $ref ?>" class="form-control" readonly>
        </div>
      </div>

      <!-- Row 2: Currency, Exchange Rate, Cost Center -->
      <div class="row mb-3">
        <div class="col-md-4 col-sm-12">
          <label>Currency Used</label>
          <select name="currency" onchange="selectCurrency(this.value)" class="form-control select2-form select2bs4-form" required>
            <option value="">Select below</option>
            <?php
              $sql = "SELECT * FROM currencies";
              $query = mysqli_query($conn, $sql);
              while ($rows = mysqli_fetch_assoc($query)) {
                echo "<option value='{$rows['id']}'>{$rows['name']}</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-4 col-sm-12">
          <label class="w3-text-blue">Exchange Rate</label>
          <div id="currency3"></div>
        </div>
         
      </div>

      <!-- Row 3: Table -->
      <div class="mt-3 d-none">
        <button type="button" id="add-row-btn" class="w3-button w3-blue" onclick="addJvRow1()">Add Row</button>
        <button type="button" disabled onclick="removeTvRow2()">Remove row</button>
      </div>

      <div class="table-responsive">
        <table id="tv3-table" class="table table-bordered">
          <thead>
            <tr>
              <th>Account To Debt</th>
              <th>Amount</th>
              <th class="w3-hide">Equiv Amount</th>
              <th>Account to Credit</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="deb_acc ">
                                                            <select name="min_item[]" id="min_item" required class="form-control select2-form select2bs4-form min_item">
                                                             <option value="">select Below</option>
                                                                
                                                            </select>
              <td>
                <input type="number" name="debt_amount[]" step="any" class="form-control debt_amount" required oninput="calculateEqv(this.value)">
              </td>
              <td>
                <input type="number" name="equiv_dr[]" readonly class="form-control equiv_dr w3-hide">
              </td>
              <td>
                <select name="cr_account[]" class="form-control select2-form select2bs4-form min_item" required>
                  <option value="">Choose below</option>
                 
                </select>
              </td>
              <td>
                <input type="text" name="desc[]" class="form-control">
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer">
      <p class="w3-center" id="vch">
        <button type="submit" class="btn btn-success" name="addminjv">POST</button>
      </p>
    </div>
  </form>
</div>
 <script>
                        $(document).ready(function(){
                           fetchSub();
                            $(document).on('change', 'select[name="branchId"]', function(){
                               // console.log('chenged');
                                var branch = $(this).val();
                                console.log(branch)
                                $.ajax({
                                    type:"GET",
                                    url:"./requests/form_requests.php",
                                    dataType: 'json',
                                    data:{"get_min_sub_by_branch_id":"","branchId":branch},
                                    success: function(data){
                                        $('.min_item').find('option:not(:first)').remove();
                                      $.each(data, function(index, item) {
                                          $('.min_item').append(
                                            $('<option>', {
                                              value: item.id,
                                              text: item.name
                                            })
                                          )
                                      })
                                        
                                     
                                    }
                                })
                            });
                            fetchSub();
                        });
                       

                        
                        function fetchSub(){
                            
                            //console.log("am running");
                            var branch = $('select[name="branchId"]').val();
                            console.log(branch);
                            $.ajax({
                                    type:"GET",
                                    url:"./requests/form_requests.php",
                                    dataType: 'json',
                                    data:{"get_min_sub_by_branch_id":"","branchId":branch},
                                    success: function(data){
                                        $('.min_item').find('option:not(:first)').remove();
                                      $.each(data, function(index, item) {
                                          $('.min_item').append(
                                            $('<option>', {
                                              value: item.id,
                                              text: item.name
                                            })
                                          )
                                      })
                                        
                                     
                                    }
                                });
                            
                        }
                </script>
