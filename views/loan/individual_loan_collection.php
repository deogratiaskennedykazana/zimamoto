<?php
// Branch-level accountants only see/collect from members in their own branch —
// consistent with the branch-restriction pattern used across the rest of the app.
$branchId = null;
if (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch') {
    $branchId = (int) $_SESSION['branchid'];
}
$loanCustomers = selectUsersWithApprovedLoans($conn, $branchId);
?>
<div id="spinnerModal" class="modal" tabindex="-1" role="dialog" style="display: none;">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content text-center">
      <div class="modal-body">
        <div class="spinner-border text-success mb-2" role="status"></div>
        <div>Loading...</div>
      </div>
    </div>
  </div>
</div>

<div class="card card-primary">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Loan Collection</h5>
    <button class="btn btn-sm btn-outline-light" onclick="window.print()"><i class="fas fa-print mr-1"></i>Print</button>
  </div>
  <div class="card-body">
    <h5 class="card-text table-primary p-2">
      <div class="row align-items-center">
        <div class="col-md-3">Select Loan Customer</div>
        <div class="col-md-9">
          <div class="row">
            <div class="col-md-8">
              <select class="form-control select2-form select2bs4-form" id="user-id-select">
                <option value="">-- Select Loan Customer --</option>
                <?php
                if ($loanCustomers && is_array($loanCustomers)) {
                    foreach ($loanCustomers as $cust) {
                        echo "<option value='" . (int) $cust['user_id'] . "'>" . htmlspecialchars($cust['name']) . "</option>";
                    }
                }
                ?>
              </select>
            </div>
            <div class="col-md-4">
              <button class="btn btn-sm btn-success w-100" onclick="getLoanRepaymentSchedule()"><i class="fas fa-search mr-1"></i>Load Schedule</button>
            </div>
          </div>
          <?php if (empty($loanCustomers)): ?>
            <small class="text-muted">No members with approved loans were found<?= $branchId ? ' for your branch' : '' ?>.</small>
          <?php endif; ?>
        </div>
      </div>
    </h5>
    <div id="schedule-content" class="mt-3"></div>
  </div>
</div>

<script>
function showSpinner() { $('#spinnerModal').css('display', 'block'); }
function hideSpinner() { $('#spinnerModal').css('display', 'none'); }

function getLoanRepaymentSchedule() {
  const userId = document.getElementById("user-id-select").value;
  if (!userId) {
    alert("Please select a loan customer.");
    return;
  }

  localStorage.setItem('last_collection_user_id', userId);

  showSpinner();
  $.get("./requests/loan_collection_requests.php", {
    get_loan_repayment_schedule: 1,
    user_id: userId
  }, function (response) {
    document.getElementById("schedule-content").innerHTML = response;
    if (typeof formatNumber === 'function') { formatNumber(); }
    if ($('.select2-form').length) { $('.select2-form').select2({ theme: 'bootstrap4' }); }
    initScheduleEvents();
  }).fail(function () {
    document.getElementById("schedule-content").innerHTML =
      "<div class='alert alert-danger'>Failed to load repayment schedule. Please try again.</div>";
  }).always(hideSpinner);
}

function initScheduleEvents() {

    $(document).off('change', '.selectAll').on('change', '.selectAll', function () {
        var loan = $(this).data('loan');
        var checked = $(this).prop('checked');
        $('.schCb[data-loan="' + loan + '"]').prop('checked', checked);
        updateCollectionBtn(loan);
    });

    $(document).off('click', '.schRow').on('click', '.schRow', function (e) {
        if ($(e.target).is('input[type="checkbox"]')) return;
        var row = $(this).data('row');
        var loan = $(this).data('loan');
        var cb = $('.schCb[data-row="' + row + '"]');
        if (cb.length) {
            cb.prop('checked', !cb.prop('checked'));
            updateCollectionBtn(loan);
        }
    });

    $(document).off('change', '.schCb').on('change', '.schCb', function () {
        var loan = $(this).data('loan');
        updateCollectionBtn(loan);
    });
}

function updateCollectionBtn(loan) {
    var cbs = $('.schCb[data-loan="' + loan + '"]:checked');
    var total = 0;
    var ids = [];

    $('.schCb[data-loan="' + loan + '"]').each(function () {
        var row = $(this).data('row');
        $('.schRow[data-row="' + row + '"]').css('background-color', '');
    });

    cbs.each(function () {
        total += parseFloat($(this).data('rem'));
        ids.push($(this).data('row'));
        var row = $(this).data('row');
        $('.schRow[data-row="' + row + '"]').css('background-color', '#fff3cd');
    });

    if (cbs.length > 0) {
        $('#payBtn_' + loan).show();
        $('#count_' + loan).text(cbs.length);
        $('#amt_' + loan).val(total.toFixed(2));
        $('#col_' + loan).val(total.toFixed(2));
        $('#sel_' + loan).val(JSON.stringify(ids));
    } else {
        $('#payBtn_' + loan).hide();
    }
}

window.addEventListener('DOMContentLoaded', function () {
  const savedUserId = localStorage.getItem('last_collection_user_id');
  if (savedUserId && document.querySelector("#user-id-select option[value='" + savedUserId + "']")) {
    const select = document.getElementById("user-id-select");
    select.value = savedUserId;
    if ($(select).hasClass('select2-hidden-accessible')) { $(select).trigger('change'); }
    getLoanRepaymentSchedule();
  }
});
</script>
