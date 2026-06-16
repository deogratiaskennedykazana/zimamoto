<?php
/**
 * Admin Dashboard — with Chart.js graphs
 */

// ---- Stat counts ----
$totalMembers  = 0;
$totalLoans    = 0;
$pendingLoans  = 0;
$approvedLoans = 0;
$totalUsers    = 0;
$totalBranches = 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM members WHERE deleted_at IS NULL"); if($r){ $totalMembers = $r->fetch_assoc()['c']; }
$r = $conn->query("SELECT COUNT(*) AS c FROM loans"); if($r){ $totalLoans = $r->fetch_assoc()['c']; }
$r = $conn->query("SELECT COUNT(*) AS c FROM loans WHERE status='pending'"); if($r){ $pendingLoans = $r->fetch_assoc()['c']; }
$r = $conn->query("SELECT COUNT(*) AS c FROM loans WHERE status='approved'"); if($r){ $approvedLoans = $r->fetch_assoc()['c']; }
$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='approved'"); if($r){ $totalUsers = $r->fetch_assoc()['c']; }
$r = $conn->query("SELECT COUNT(*) AS c FROM branches"); if($r){ $totalBranches = $r->fetch_assoc()['c']; }

// ---- Loans by month (last 12 months) ----
$loanMonths = []; $loanCounts = []; $loanAmounts = [];
$sql = "SELECT DATE_FORMAT(created_at,'%b %Y') AS mon, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
        FROM loans
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY MIN(created_at) ASC";
$r = $conn->query($sql);
if($r){ while($row=$r->fetch_assoc()){ $loanMonths[]=$row['mon']; $loanCounts[]=$row['cnt']; $loanAmounts[]=$row['total']; } }

// ---- Loans by status (pie) ----
$loanStatusLabels=[]; $loanStatusData=[];
$r = $conn->query("SELECT status, COUNT(*) AS cnt FROM loans GROUP BY status");
if($r){ while($row=$r->fetch_assoc()){ $loanStatusLabels[]= ucfirst($row['status']); $loanStatusData[]=$row['cnt']; } }

// ---- Member registrations by month (last 12 months) ----
$memMonths=[]; $memCounts=[];
$sql = "SELECT DATE_FORMAT(created_at,'%b %Y') AS mon, COUNT(*) AS cnt
        FROM members
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND deleted_at IS NULL
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY MIN(created_at) ASC";
$r = $conn->query($sql);
if($r){ while($row=$r->fetch_assoc()){ $memMonths[]=$row['mon']; $memCounts[]=$row['cnt']; } }

// ---- Budget: approved vs pending vs rejected ----
$budgetLabels=[]; $budgetData=[];
$r = $conn->query("SELECT status, COUNT(*) AS cnt FROM budget GROUP BY status");
if($r){ while($row=$r->fetch_assoc()){ $budgetLabels[]=ucfirst($row['status']); $budgetData[]=$row['cnt']; } }

// ---- Top 5 branches by member count ----
$branchNames=[]; $branchMembers=[];
$sql = "SELECT b.branch_name, COUNT(m.id) AS cnt FROM branches b LEFT JOIN members m ON m.branch_id=b.id AND m.deleted_at IS NULL GROUP BY b.id ORDER BY cnt DESC LIMIT 5";
$r = $conn->query($sql);
if($r){ while($row=$r->fetch_assoc()){ $branchNames[]=$row['branch_name']; $branchMembers[]=$row['cnt']; } }

// ---- Recent Loans ----
$recentLoans = [];
$r = $conn->query("SELECT l.id, m.name AS member_name, l.amount, l.status, l.created_at FROM loans l LEFT JOIN members m ON l.member_id=m.id ORDER BY l.created_at DESC LIMIT 8");
if($r){ while($row=$r->fetch_assoc()) $recentLoans[]=$row; }
?>

<!-- Stat Row -->
<div class="row">
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-info">
      <div class="inner"><h3><?= number_format($totalMembers) ?></h3><p>Members</p></div>
      <div class="icon"><i class="fas fa-users"></i></div>
      <a href="./?page=all_member_list" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-success">
      <div class="inner"><h3><?= number_format($totalLoans) ?></h3><p>Total Loans</p></div>
      <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
      <a href="./?page=approved_loan_list_form" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-warning">
      <div class="inner"><h3><?= number_format($pendingLoans) ?></h3><p>Pending Loans</p></div>
      <div class="icon"><i class="fas fa-hourglass-half"></i></div>
      <a href="./?page=Pending_loan_list_form" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-danger">
      <div class="inner"><h3><?= number_format($approvedLoans) ?></h3><p>Approved Loans</p></div>
      <div class="icon"><i class="fas fa-check-circle"></i></div>
      <a href="./?page=approved_loan_list_form" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-primary">
      <div class="inner"><h3><?= number_format($totalUsers) ?></h3><p>Active Users</p></div>
      <div class="icon"><i class="fas fa-user-shield"></i></div>
      <a href="#" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-sm-4 col-6">
    <div class="small-box bg-secondary">
      <div class="inner"><h3><?= number_format($totalBranches) ?></h3><p>Branches</p></div>
      <div class="icon"><i class="fas fa-code-branch"></i></div>
      <a href="./?page=branch_list" class="small-box-footer">More <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
</div>

<!-- Row 1: Loans over time + Loan status pie -->
<div class="row">
  <div class="col-md-8">
    <div class="card card-primary card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Loans Per Month (Last 12 Months)</h3></div>
      <div class="card-body">
        <canvas id="loanMonthChart" height="100"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-success card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Loans by Status</h3></div>
      <div class="card-body">
        <canvas id="loanStatusChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: Member registrations + Branch members bar -->
<div class="row">
  <div class="col-md-7">
    <div class="card card-info card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>New Member Registrations (Last 12 Months)</h3></div>
      <div class="card-body">
        <canvas id="memberChart" height="110"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card card-warning card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-building mr-2"></i>Top 5 Branches by Members</h3></div>
      <div class="card-body">
        <canvas id="branchChart" height="160"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Row 3: Budget doughnut + Recent loans table -->
<div class="row">
  <div class="col-md-4">
    <div class="card card-danger card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Budget Status</h3></div>
      <div class="card-body">
        <canvas id="budgetChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card card-secondary card-outline">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-2"></i>Recent Loan Applications</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped table-hover mb-0">
          <thead class="thead-dark">
            <tr><th>#</th><th>Member</th><th>Amount (TZS)</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
          <?php if($recentLoans): foreach($recentLoans as $i=>$l): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($l['member_name'] ?? 'N/A') ?></td>
            <td><?= number_format($l['amount'],2) ?></td>
            <td>
              <?php
                $badge=['approved'=>'badge-success','pending'=>'badge-warning','rejected'=>'badge-danger'];
                $cls=$badge[$l['status']]??'badge-secondary';
              ?>
              <span class="badge <?= $cls ?>"><?= ucfirst($l['status']) ?></span>
            </td>
            <td><small><?= date('d/m/Y',strtotime($l['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center text-muted py-3">No loans found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var loanMonthLabels = <?= json_encode($loanMonths ?: ['No data']) ?>;
  var loanCountData   = <?= json_encode($loanCounts ?: [0]) ?>;
  var loanAmountData  = <?= json_encode($loanAmounts ?: [0]) ?>;

  // Loans per month — dual axis line + bar
  new Chart(document.getElementById('loanMonthChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: loanMonthLabels,
      datasets: [
        {
          label: 'No. of Loans',
          data: loanCountData,
          backgroundColor: 'rgba(60,141,188,0.7)',
          borderColor: 'rgba(60,141,188,1)',
          borderWidth: 1,
          yAxisID: 'y'
        },
        {
          label: 'Total Amount (TZS)',
          data: loanAmountData,
          type: 'line',
          fill: false,
          borderColor: 'rgba(0,166,90,1)',
          backgroundColor: 'rgba(0,166,90,0.1)',
          pointRadius: 4,
          tension: 0.3,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Count' } },
        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Amount (TZS)' } }
      }
    }
  });

  // Loan status pie
  var statusColors = ['#28a745','#ffc107','#dc3545','#17a2b8','#6c757d','#007bff'];
  new Chart(document.getElementById('loanStatusChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: <?= json_encode($loanStatusLabels ?: ['No data']) ?>,
      datasets: [{ data: <?= json_encode($loanStatusData ?: [1]) ?>, backgroundColor: statusColors }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });

  // Member registrations — line chart
  new Chart(document.getElementById('memberChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: <?= json_encode($memMonths ?: ['No data']) ?>,
      datasets: [{
        label: 'New Members',
        data: <?= json_encode($memCounts ?: [0]) ?>,
        borderColor: '#17a2b8',
        backgroundColor: 'rgba(23,162,184,0.15)',
        pointRadius: 5,
        tension: 0.3,
        fill: true
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  // Top branches bar
  new Chart(document.getElementById('branchChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($branchNames ?: ['No data']) ?>,
      datasets: [{
        label: 'Members',
        data: <?= json_encode($branchMembers ?: [0]) ?>,
        backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8']
      }]
    },
    options: { responsive: true, indexAxis: 'y', scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
  });

  // Budget doughnut
  var budgetColors = ['#28a745','#ffc107','#dc3545','#6c757d'];
  new Chart(document.getElementById('budgetChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($budgetLabels ?: ['No data']) ?>,
      datasets: [{ data: <?= json_encode($budgetData ?: [1]) ?>, backgroundColor: budgetColors }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
})();
</script>
