<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; padding: 30px; line-height: 1.4; color: #333; }
    .v-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; margin-bottom: 20px; }
    .v-logo { width: 250px; height: 200px; flex-shrink: 0; }
    .v-logo img { width: 100%; height: 100%; object-fit: contain; }
    .v-title { font-size: 40px; font-weight: bold; color: #d97c2b; text-align: right; flex: 1; }
    .v-info-row { display: flex; justify-content: space-between; align-items: flex-start; margin: 20px 0; padding: 15px 0; border-bottom: 1px solid #ddd; }
    .v-party { flex: 1; }
    .v-party-label { font-weight: bold; font-size: 13px; color: #000; margin-bottom: 6px; margin-top: 10px; }
    .v-party-label:first-child { margin-top: 0; }
    .v-party-value { font-size: 13px; color: #333; font-weight: bold; margin-bottom: 3px; }
    .v-meta { flex: 1; text-align: right; }
    .v-meta-item { margin-bottom: 8px; font-size: 13px; }
    .v-meta-label { font-weight: bold; color: #000; }
    .v-meta-value { color: #333; margin-left: 5px; }
    .v-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .v-table thead tr { background: #d97c2b; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .v-table th { padding: 11px 8px; text-align: left; font-size: 12px; font-weight: bold; border: 1px solid #c06a1a; color: #fff; }
    .v-table td { padding: 9px 8px; border: 1px solid #ddd; font-size: 12px; }
    .v-table tbody tr:nth-child(even) { background: #fdf3e7; }
    .text-right { text-align: right; }
    .v-summary { margin-top: 15px; width: 100%; }
    .v-summary-row { display: flex; justify-content: flex-end; margin-bottom: 8px; }
    .v-summary-label { width: 200px; font-weight: bold; text-align: right; padding-right: 20px; font-size: 13px; color: #000; }
    .v-summary-value { width: 160px; text-align: right; font-size: 13px; }
    .v-grand { border-top: 2px solid #000; padding-top: 10px; margin-top: 5px; }
    .v-grand .v-summary-label, .v-grand .v-summary-value { font-weight: bold; font-size: 15px; }
    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-active { background: #d1e7dd; color: #0a3622; }
    .status-draft { background: #e2e3e5; color: #41464b; }
    .sig-rows { margin-top: 50px; width: 100%; }
    .sig-row { display: flex; align-items: center; margin-bottom: 30px; gap: 15px; }
    .sig-row-label { font-size: 12px; font-weight: bold; color: #000; width: 100px; flex-shrink: 0; }
    .sig-field { display: flex; align-items: center; gap: 6px; flex: 1; font-size: 12px; border-bottom: 1px solid #000; padding-bottom: 4px; }
    .sig-field span { font-size: 11px; color: #333; white-space: nowrap; font-weight: bold; }
    .sig-line { flex: 1; min-width: 60px; }
    .signature-section { margin-top: 50px; display: flex; flex-direction: column; align-items: flex-start; width: 300px; }
    .signature-line { width: 100%; border-top: 2px solid #000; margin-bottom: 10px; }
    .signature-name { font-size: 14px; font-weight: bold; color: #000; margin-bottom: 3px; }
    .signature-position { font-size: 12px; color: #555; }
    .v-footer { margin-top: 40px; font-size: 11px; color: #fff; text-align: center; background: #d97c2b; padding: 15px 20px; line-height: 1.7; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .v-footer strong { color: #fff; font-size: 13px; }
    @media print { body { padding: 15px; } }
</style>

<?php
session_start();
if(!isset($_SESSION['userid'])){
    echo "<script>alert('Unauthorized'); window.close();</script>";
    exit();
}
require_once "../../../configs.php";
require_once "../../../functions/transaction_functions.php";
require_once "../../../functions/subsidiary_functions.php";
$conn = openConn();
$id = (int) $_GET['id'];
$signature_name     = isset($_GET['signature_name'])     ? htmlspecialchars($_GET['signature_name'])     : '';
$signature_position = isset($_GET['signature_position']) ? htmlspecialchars($_GET['signature_position']) : '';
$transaction = selectTransactionById($conn, $id);
if(!$transaction){ echo "Voucher not found."; exit(); }

$voucherType     = $transaction['voucher_type'] ?? '';
$subsidiary      = selectSubsidiaryById($conn, $transaction['dr_account']);
$subsidiaryLabel = $voucherType === 'payment' ? 'Paid To' : ($voucherType === 'receipt' ? 'Received From' : 'Debited Account');
$voucherDate     = date('d-M-Y', strtotime($transaction['date_']));
$typeLabel       = ucfirst($voucherType) . ' Voucher';
$status          = $transaction['status'];

$createdByName  = !empty($transaction['created_by_name'])  ? htmlspecialchars($transaction['created_by_name'])  : '-';
$reviewedByName = !empty($transaction['reviewed_by_name']) ? htmlspecialchars($transaction['reviewed_by_name']) : '-';
$approvedByName = !empty($transaction['approved_by_name']) ? htmlspecialchars($transaction['approved_by_name']) : '-';
$createdAt  = !empty($transaction['created_at'])  ? date('d-M-Y', strtotime($transaction['created_at']))  : '-';
$reviewedAt = !empty($transaction['reviewed_at']) ? date('d-M-Y', strtotime($transaction['reviewed_at'])) : '-';
$approvedAt = !empty($transaction['approved_at']) ? date('d-M-Y', strtotime($transaction['approved_at'])) : '-';
?>

<div class="v-header">
    <div class="v-logo"><img src="../../../dist/img/logo.png" alt="Logo"></div>
    <div class="v-title"><?= htmlspecialchars($typeLabel) ?></div>
</div>

<div class="v-info-row">
    <div class="v-party">
        <div class="v-party-label"><?= $subsidiaryLabel ?>:</div>
        <div class="v-party-value"><?= htmlspecialchars($subsidiary['name'] ?? '') ?></div>
        <?php if(!empty($subsidiary['email'])): ?>
        <div class="v-party-value"><?= htmlspecialchars($subsidiary['email']) ?></div>
        <?php endif; ?>
        <?php if(!empty($subsidiary['tin'])): ?>
        <div class="v-party-value">TIN: <?= htmlspecialchars($subsidiary['tin']) ?></div>
        <?php endif; ?>
        <?php if(!empty($subsidiary['phone'])): ?>
        <div class="v-party-value"><?= htmlspecialchars($subsidiary['phone']) ?></div>
        <?php endif; ?>
        <?php if(!empty($subsidiary['address'])): ?>
        <div class="v-party-value"><?= htmlspecialchars($subsidiary['address']) ?></div>
        <?php endif; ?>
        <div class="v-party-label">From:</div>
        <div class="v-party-value"><?= htmlspecialchars($transaction['credit_acc'] ?? '') ?></div>
    </div>
    <div class="v-meta">
        <div class="v-meta-item">
            <span class="v-meta-label">Reference:</span>
            <span class="v-meta-value"><?= htmlspecialchars($transaction['reference_no']) ?></span>
        </div>
        <div class="v-meta-item">
            <span class="v-meta-label">Date:</span>
            <span class="v-meta-value"><?= $voucherDate ?></span>
        </div>
        <div class="v-meta-item">
            <span class="v-meta-label">Status:</span>
            <span class="v-meta-value">
                <span class="status-badge status-<?= $status ?>">
                    <?= ucfirst($status) ?>
                </span>
            </span>
        </div>
    </div>
</div>

<table class="v-table">
    <thead>
        <tr>
            <th style="width:80%;">Narration</th>
            <th style="width:20%;" class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= htmlspecialchars($transaction['description']) ?></td>
            <td class="text-right"><?= number_format(floatval($transaction['amount']), 2) ?></td>
        </tr>
    </tbody>
</table>

<div class="v-summary">
    <div class="v-summary-row v-grand">
        <div class="v-summary-label">Total Amount:</div>
        <div class="v-summary-value"><?= number_format(floatval($transaction['amount']), 2) ?></div>
    </div>
</div>

<div class="sig-rows">
    <?php if(in_array($status, ['draft', 'pending', 'active'])): ?>
    <div class="sig-row">
        <div class="sig-row-label">Prepared by:</div>
        <div class="sig-field"><span>Name:</span> <?= $createdByName ?></div>
        <div class="sig-field"><span>Signature:</span><div class="sig-line"></div></div>
        <div class="sig-field"><span>Date:</span> <?= $createdAt ?></div>
    </div>
    <?php endif; ?>

    <?php if(in_array($status, ['pending', 'active'])): ?>
    <div class="sig-row">
        <div class="sig-row-label">Reviewed by:</div>
        <div class="sig-field"><span>Name:</span> <?= $reviewedByName ?></div>
        <div class="sig-field"><span>Signature:</span><div class="sig-line"></div></div>
        <div class="sig-field"><span>Date:</span> <?= $reviewedAt ?></div>
    </div>
    <?php endif; ?>

    <?php if($status === 'active'): ?>
    <div class="sig-row">
        <div class="sig-row-label">Approved by:</div>
        <div class="sig-field"><span>Name:</span> <?= $approvedByName ?></div>
        <div class="sig-field"><span>Signature:</span><div class="sig-line"></div></div>
        <div class="sig-field"><span>Date:</span> <?= $approvedAt ?></div>
    </div>
    <?php endif; ?>
</div>

<?php if(!empty($signature_name) || !empty($signature_position)): ?>
<div class="signature-section">
    <div class="signature-line"></div>
    <?php if(!empty($signature_name)): ?>
    <div class="signature-name"><?= $signature_name ?></div>
    <?php endif; ?>
    <?php if(!empty($signature_position)): ?>
    <div class="signature-position"><?= $signature_position ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="v-footer">
    <strong>JADON LIMITED</strong><br>
    2nd Floor, Tevi Commercial Park, Bagamoyo Rd, Plot No.576, Mbezi Beach, P.O. Box 60163, Dar es Salaam, Tanzania.<br>
    Email: info@jadon.co.tz | Tel: +255621009936 / +255 737 829 077 | TIN: 138-847-209 | VRN: 40-034505-T
</div>

<script>
window.onload = function() {
    window.print();
    window.onafterprint = function() {
        window.close();
    };
};
</script>