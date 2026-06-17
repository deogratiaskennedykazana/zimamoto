<?php
session_start();
if(!isset($_SESSION['userid'])){
    echo "<script>alert('Unauthorized'); window.close();</script>";
    exit();
}
require_once "../../../configs.php";
require_once "../../../functions/transaction_functions.php";
require_once "../../../functions/subsidiary_functions.php";
require_once "../../../functions/project_functions.php";
require_once "../../../functions/opening_balance_functions.php";

$conn = openConn();

$id = (int) $_GET['id'];
$projectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
$signatureName     = $_GET['signature_name']     ?? '';
$signaturePosition = $_GET['signature_position'] ?? '';

$subsidiary = selectSubsidiaryById($conn, $id);
$name = $subsidiary['name'] ?? 'Unknown';

$balance = 0;
$counter = 1;
$total_debit = 0;
$total_credit = 0;

$openingBalance = selectOpeningBalance($conn, $id);
$opening_debit = 0;
$opening_credit = 0;
$opening_date = date("d-M-Y");

if ($openingBalance && is_array($openingBalance)) {
    $opening_date = date("d-M-Y", strtotime($openingBalance['date_']));
    if ($openingBalance['type'] === 'credit') {
        $balance -= $openingBalance['ammount'];
        $opening_credit = $openingBalance['ammount'];
        $total_credit += $openingBalance['ammount'];
    } elseif ($openingBalance['type'] === 'debit') {
        $balance += $openingBalance['ammount'];
        $opening_debit = $openingBalance['ammount'];
        $total_debit += $openingBalance['ammount'];
    }
}
$opening_balance = $balance;

$transactions = selectAllTransactionsBySubId($conn, $id, $projectId);

$project = null;
if ($projectId !== null) {
    $project = listProjectById($conn, $projectId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement - <?php echo htmlspecialchars($name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; padding: 30px; }

        /* Row 1: logo left, title right */
        .top-row { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 2px solid #d97c2b; margin-bottom: 16px; }
        .top-row img { height: 65px; object-fit: contain; }
        .top-title { font-size: 28px; font-weight: bold; color: #d97c2b; text-align: right; }

        /* Row 2: project left, account right */
        .info-row { display: flex; justify-content: space-between; gap: 30px; padding-bottom: 14px; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .info-box { flex: 1; }
        .info-box.right { text-align: right; }
        .info-label { font-weight: bold; font-size: 12px; color: #000; margin-bottom: 4px; }
        .info-value { font-size: 12px; color: #444; margin-bottom: 2px; }
        .info-generated { font-size: 11px; color: #aaa; margin-top: 6px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #d97c2b; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        thead th { padding: 9px 10px; font-size: 11px; font-weight: bold; color: #fff; border: 1px solid #c06a1a; }
        thead th.num { text-align: right; }
        tbody td { padding: 7px 10px; font-size: 12px; border: 1px solid #ddd; }
        tbody td.num { text-align: right; }
        .tr-opening td { background: #d1e7dd; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tr-closing td { background: #f8d7da; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* Signature */
        .sig-section { margin-top: 40px; display: flex; justify-content: flex-end; }
        .sig-box { width: 280px; }
        .sig-line { border-bottom: 1px dotted #999; height: 30px; margin-bottom: 6px; }
        .sig-label { font-size: 11px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px; }
        .sig-name { font-size: 13px; font-weight: bold; color: #000; margin-bottom: 2px; }
        .sig-position { font-size: 12px; color: #555; }

        /* Footer */
        .v-footer { margin-top: 30px; font-size: 11px; color: #fff; text-align: center; background: #d97c2b; padding: 12px 20px; line-height: 1.8; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .v-footer strong { color: #fff; font-size: 12px; }
    </style>
</head>
<body>

    <div class="top-row">
        <img src="../../../dist/img/logo.png" alt="Logo">
        <div class="top-title">Account Statement</div>
    </div>

    <div class="info-row">
        <div class="info-box">
            <?php if ($project): ?>
            <div class="info-label">Project</div>
            <div class="info-value"><strong><?php echo htmlspecialchars($project['name']); ?></strong></div>
            <?php if (!empty($project['customer_name'])): ?>
            <div class="info-value"><?php echo htmlspecialchars($project['customer_name']); ?></div>
            <?php endif; ?>
            <?php if (!empty($project['location'])): ?>
            <div class="info-value"><?php echo htmlspecialchars($project['location']); ?></div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="info-box right">
            <div class="info-label">Account</div>
            <div class="info-value"><strong><?php echo htmlspecialchars($name); ?></strong></div>
            <?php if (!empty($subsidiary['email'])): ?>
            <div class="info-value"><?php echo htmlspecialchars($subsidiary['email']); ?></div>
            <?php endif; ?>
            <?php if (!empty($subsidiary['tin'])): ?>
            <div class="info-value">TIN: <?php echo htmlspecialchars($subsidiary['tin']); ?></div>
            <?php endif; ?>
            <?php if (!empty($subsidiary['phone'])): ?>
            <div class="info-value"><?php echo htmlspecialchars($subsidiary['phone']); ?></div>
            <?php endif; ?>
            <?php if (!empty($subsidiary['address'])): ?>
            <div class="info-value"><?php echo htmlspecialchars($subsidiary['address']); ?></div>
            <?php endif; ?>
            <div class="info-generated">Generated: <?php echo date('d-M-Y'); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Details</th>
                <th>Narration</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="tr-opening">
                <td><?php echo $counter++; ?></td>
                <td><?php echo $opening_date; ?></td>
                <td><strong>Opening Balance</strong></td>
                <td></td>
                <td class="num"><?php echo $opening_debit ? number_format($opening_debit, 2) : '—'; ?></td>
                <td class="num"><?php echo $opening_credit ? number_format($opening_credit, 2) : '—'; ?></td>
                <td class="num"><strong><?php echo number_format($opening_balance, 2); ?></strong></td>
            </tr>
            <?php
            if ($transactions && count($transactions) > 0) {
                foreach ($transactions as $transaction) {
                    $trans_debit = 0;
                    $trans_credit = 0;
                    $details = '';
                    if ($transaction['dr_account'] == $id) {
                        $corresponding = selectSubsidiaryById($conn, (int)$transaction['cr_account']);
                        $details = htmlspecialchars($corresponding['name'] ?? 'Unknown');
                        $trans_debit = $transaction['amount'];
                        $balance += $trans_debit;
                        $total_debit += $trans_debit;
                    } elseif ($transaction['cr_account'] == $id) {
                        $corresponding = selectSubsidiaryById($conn, (int)$transaction['dr_account']);
                        $details = htmlspecialchars($corresponding['name'] ?? 'Unknown');
                        $trans_credit = $transaction['amount'];
                        $balance -= $trans_credit;
                        $total_credit += $trans_credit;
                    }
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo date("d-M-Y", strtotime($transaction['date_'])); ?></td>
                        <td><?php echo $details; ?></td>
                        <td><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></td>
                        <td class="num"><?php echo $trans_debit ? number_format($trans_debit, 2) : '—'; ?></td>
                        <td class="num"><?php echo $trans_credit ? number_format($trans_credit, 2) : '—'; ?></td>
                        <td class="num"><?php echo number_format($balance, 2); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr class="tr-closing">
                <td colspan="4">Closing Balance</td>
                <td class="num"><?php echo number_format($total_debit, 2); ?></td>
                <td class="num"><?php echo number_format($total_credit, 2); ?></td>
                <td class="num"><?php echo number_format($balance, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="sig-section">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Prepared by</div>
            <?php if (!empty($signatureName)): ?>
            <div class="sig-name"><?php echo htmlspecialchars($signatureName); ?></div>
            <?php endif; ?>
            <?php if (!empty($signaturePosition)): ?>
            <div class="sig-position"><?php echo htmlspecialchars($signaturePosition); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="v-footer">
        <strong>JADON LIMITED</strong><br>
        2nd Floor, Tevi Commercial Park, Bagamoyo Rd, Plot No.576, Mbezi Beach, P.O. Box 60163, Dar es Salaam, Tanzania.<br>
        Email: info@jadon.co.tz &nbsp;|&nbsp; Tel: +255621009936 / +255 737 829 077 &nbsp;|&nbsp; TIN: 138-847-209 &nbsp;|&nbsp; VRN: 40-034505-T
    </div>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>

</body>
</html>