<div class="card card-primary">
  <div class='card-header'>Purchases Voucher</div>
  <div class='card-body'>
    <form action="./controllers/voucher_controller.php" class="was-validated" method="post">
      
      <!-- Row 1: Date, Reference -->
      <div class="row mb-3">
        <div class="col-md-6 col-sm-12">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-md-6 col-sm-12">
          <label class="form-label">Reference</label>
          <?php
            $v = "SV/";
            $sql = "SELECT COUNT(id) id FROM sales_voucher";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $ref = $row['id'] + 1;
          ?>
          <input type="text" name="voucher_ref" readonly value="<?php echo $v . $ref ?>" class="form-control">
        </div>
      </div>

      <!-- Row 2: Currency, Exchange Rate -->
      <div class="row mb-3">
        <div class="col-md-6 col-sm-12">
          <label class="form-label">Currency Used</label>
          <select name="currency" class=" form-control select2 select2bs4 " onchange="selectCurrency(this.value)" required>
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
        <div class="col-md-6 col-sm-12">
          <label class="form-label">Exchange Rate</label>
          <div id="currency1"></div>
        </div>
      </div>

      <!-- Row 3: Supplier, VAT -->
      <div class="row mb-3">
         <!-- Row 3: Customer & VAT -->
       
        <div class="col-md-6 col-sm-12">
          <label class="form-label">Customer's Name</label>
          <select name="dr_account" class="form-control select2 select2bs4" required>
            <option value="">Choose below</option>
            <?php
              $sql = "SELECT * FROM subsidiaries WHERE type='customer'";
              $query = mysqli_query($conn, $sql);
              while ($row = mysqli_fetch_assoc($query)) {
                echo "<option value='{$row['id']}'>{$row['name']}</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-6 col-sm-12">
          <label class="form-label">VAT Tax Rate</label>
          <select name="rates" id="rates" class=" form-control select2 select2bs4 " required>
            <option value="">Select below</option>
            <option value="18">18%</option>
            <option value="0">0%</option>
          </select>
        </div>
      </div>

      <!-- Row 4: Branch -->
      <div class="row mb-3">
        <div class="col-md-4 col-sm-12">
          <label class="form-label">Branch</label>
          <select name="branchId" id="branchId" class=" form-control select2 select2bs4 " required>
            <?php
              $branchId = null;
              if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                  $branchId = $_SESSION['branchid'];
              }

              $branches = selectAllBranches($conn, $branchId);

              if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                  echo '<option value="">--Select Below--</option>';
                   echo '<option value="0">All Branches</option>';
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
      </div>

      <!-- Buttons -->
      <div class="w3-container mb-3">
        <button type="button" class="btn btn-info" onclick="addPurchaseRow()">Add Row</button>
        <button type="button" class="btn btn-danger" onclick="removePurchaseRow()">Remove Row</button>
      </div>

      <!-- Table of Items -->
      <table class="table table-sm table-bordered" id="pvtable">
        <thead class="table-primary">
          <tr>
            <th>Item (Description)</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Price</th>
            <th>Description</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <select name="item[]" class=" form-control select2 select2bs4 " required>
                <option value="">Select below</option>
                <?php
                  $sql = "SELECT * FROM subsidiaries WHERE deleted_at IS NULL";
                  $query = mysqli_query($conn, $sql);
                  while ($row = mysqli_fetch_assoc($query)) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                  }
                ?>
              </select>
            </td>
            <td><input type="number" name="quantity[]" min="1" class="form-control" required></td>
            <td>
              <select name="unit[]" class=" form-control select2 select2bs4 " required>
                <option value="">Select below</option>
                <?php
                  $units = selectAllUnit($conn);
                  if ($units && is_array($units)) {
                    foreach ($units as $unit) {
                      echo "<option value='{$unit['id']}'>{$unit['name']}</option>";
                    }
                  }
                ?>
              </select>
            </td>
            <td><input type="number" name="price[]" class="form-control" oninput="calculateSaleTotal()" required></td>
            <td><input type="text" name="description[]" class="form-control"></td>
            <td><input type="number" name="amount[]" class="form-control total" readonly></td>
          </tr>
        </tbody>
      </table>

      <!-- Totals -->
      <table class="table table-sm">
        <tr>
          <td>Sub total</td>
          <td></td>
          <td><input type="number" name="subtotal" id="subtotal" readonly class="form-control" required></td>
        </tr>
        <tr>
          <td>VAT</td>
          <td></td>
          <td><input type="number" name="tax" id="tax" readonly class="form-control" required></td>
        </tr>
        <tr>
          <td>Grand total</td>
          <td></td>
          <td><input type="number" name="total" id="total" readonly class="form-control" required></td>
        </tr>
      </table>
    </div>

    <!-- Footer -->
    <div class="card-footer">
      <button type="submit" class="btn btn-info" name="addslvocher">Add</button>
    </div>
  </form>
</div>


 <script>
                          $(document).ready(function(){
                                $(function () {
                                     $(".select2").select2({
                                                   placeholder: "Select an option",
                                                         });
                                        $('.select2bs4').select2({
                                  theme: 'bootstrap4'
                                })
                                    });


                            });
                        </script>
 
