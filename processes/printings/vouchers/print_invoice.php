<?php
session_start();
if(!isset($_SESSION['userid'])){
    echo "<script>alert('Unauthorized'); window.close();</script>";
    exit();
}
require_once "../../../configs.php";
require_once "../../../functions/voucher_functions.php";
require_once "../../../functions/subsidiary_functions.php";
$conn = openConn();
$invoiceId = (int) $_GET['id'];
$signature_name     = isset($_GET['signature_name'])     ? htmlspecialchars($_GET['signature_name'])     : '';
$signature_position = isset($_GET['signature_position']) ? htmlspecialchars($_GET['signature_position']) : '';
$invoice = selectSalesVoucherById($conn, $invoiceId);
$items = selectSalesVoucherItems($conn, $invoiceId);
$customer = selectSubsidiaryById($conn, $invoice['customer_id']);
$currencyName = $invoice['currency_name'];
$currencyRate = floatval($invoice['currency_rate']);
$invoiceDate = date('d-M-Y', strtotime($invoice['date_']));
$dueDate = isset($invoice['due_date']) ? date('d-M-Y', strtotime($invoice['due_date'])) : 'N/A';
$invoiceHeading = isset($invoice['invoice_heading']) ? htmlspecialchars($invoice['invoice_heading']) : '';
$isTSH = strtoupper($currencyName) === 'TSH';
$status = $invoice['status'];

$createdByName  = !empty($invoice['created_by_name'])  ? htmlspecialchars($invoice['created_by_name'])  : '-';
$reviewedByName = !empty($invoice['reviewed_by_name']) ? htmlspecialchars($invoice['reviewed_by_name']) : '-';
$approvedByName = !empty($invoice['approved_by_name']) ? htmlspecialchars($invoice['approved_by_name']) : '-';
$createdAt  = !empty($invoice['created_at'])  ? date('d-M-Y', strtotime($invoice['created_at']))  : '-';
$reviewedAt = !empty($invoice['reviewed_at']) ? date('d-M-Y', strtotime($invoice['reviewed_at'])) : '-';
$approvedAt = !empty($invoice['approved_at']) ? date('d-M-Y', strtotime($invoice['approved_at'])) : '-';

$subtotal = 0;
$totalVat = 0;
$hasVat = false;
if($items && is_array($items)){
    foreach($items as $item){
        $subtotal += floatval($item['amt']);
        $totalVat += floatval($item['vat_amount']);
        if(floatval($item['vat_rate']) > 0) $hasVat = true;
    }
}
$grandTotal = $subtotal + $totalVat;
$grandTotalTSH = $grandTotal * $currencyRate;
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.4; color: #333; }
    .invoice-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; margin-bottom: 20px; }
    .invoice-header-logo { width: 250px; height: 250px; flex-shrink: 0; }
    .invoice-header-logo img { width: 100%; height: 100%; object-fit: contain; }
    .invoice-main-title { font-size: 48px; font-weight: bold; color: #d97c2b; text-align: right; flex: 1; }
    .info-row { display: flex; justify-content: space-between; align-items: flex-start; margin: 20px 0; padding: 15px 0; border-bottom: 1px solid #ddd; }
    .bill-to-section { flex: 1; }
    .bill-to-label { font-weight: bold; font-size: 13px; color: #000; margin-bottom: 5px; }
    .bill-to-value { font-size: 13px; color: #333; font-weight: bold; margin-bottom: 2px; }
    .meta-vertical-list { flex: 1; text-align: right; }
    .meta-item { margin-bottom: 8px; font-size: 13px; }
    .meta-label { font-weight: bold; color: #000; display: inline; }
    .meta-value { color: #333; display: inline; margin-left: 5px; }
    .invoice-description { margin: 20px 0; font-size: 13px; line-height: 1.5; color: #333; }
    .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .invoice-table thead tr { background: #d97c2b; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .invoice-table th { background: #d97c2b; color: #fff; padding: 12px 8px; text-align: left; font-size: 13px; font-weight: bold; border: 1px solid #c06a1a; }
    .invoice-table td { padding: 10px 8px; border: 1px solid #ddd; font-size: 12px; }
    .invoice-table tbody tr:nth-child(even) { background: #fdf3e7; }
    .text-right { text-align: right; }
    .vat-badge { display: inline-block; background: #d97c2b; color: #fff; font-size: 9px; font-weight: bold; padding: 1px 4px; border-radius: 3px; margin-left: 5px; vertical-align: middle; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .summary-section { margin-top: 20px; width: 100%; }
    .summary-row { display: flex; justify-content: flex-end; margin-bottom: 8px; }
    .summary-label { width: 220px; font-weight: bold; text-align: right; padding-right: 20px; color: #000; font-size: 13px; }
    .summary-value { width: 160px; text-align: right; font-size: 13px; }
    .grand-total { font-size: 16px; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
    .grand-total .summary-label, .grand-total .summary-value { font-weight: bold; }
    .bank-info { margin-top: 30px; font-size: 12px; line-height: 1.8; }
    .bank-title { font-weight: bold; margin-bottom: 5px; color: #000; font-size: 14px; }
    .sig-rows { margin-top: 40px; width: 100%; }
    .sig-row { display: flex; align-items: center; margin-bottom: 25px; gap: 20px; }
    .sig-row-label { width: 110px; font-weight: bold; font-size: 12px; flex-shrink: 0; }
    .sig-field { display: flex; align-items: center; gap: 6px; flex: 1; font-size: 12px; border-bottom: 1px solid #000; padding-bottom: 4px; }
    .sig-field span { font-weight: bold; white-space: nowrap; }
    .sig-line { flex: 1; min-width: 60px; }
    .signature-section { margin-top: 50px; display: flex; flex-direction: column; align-items: flex-start; width: 300px; }
    .signature-line { width: 100%; border-top: 2px solid #000; margin-bottom: 10px; }
    .signature-name { font-size: 14px; font-weight: bold; color: #000; margin-bottom: 3px; }
    .signature-position { font-size: 12px; color: #555; }
    .invoice-footer { background: #d97c2b; color: #fff; padding: 15px 20px; text-align: center; font-size: 11px; line-height: 1.7; margin-top: 40px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .invoice-footer strong { color: #fff; font-size: 13px; }
    @media print { body { padding: 15px; } }
</style>

<div class="invoice-header">
    <div class="invoice-header-logo">
        <img src="../../../dist/img/logo.png" alt="Company Logo">
    </div>
    <div class="invoice-main-title">Tax Invoice</div>
</div>

<div class="info-row">
    <div class="bill-to-section">
        <div class="bill-to-label">Bill To:</div>
        <div class="bill-to-value"><?= htmlspecialchars($invoice['customer_name']) ?></div>
        <?php if(!empty($customer['email'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($customer['email']) ?></div>
        <?php endif; ?>
        <?php if(!empty($customer['tin'])): ?>
        <div class="bill-to-value">TIN: <?= htmlspecialchars($customer['tin']) ?></div>
        <?php endif; ?>
        <?php if(!empty($customer['vrn'])): ?>
        <div class="bill-to-value">VRN: <?= htmlspecialchars($customer['vrn']) ?></div>
        <?php endif; ?>
        <?php if(!empty($customer['phone'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($customer['phone']) ?></div>
        <?php endif; ?>
        <?php if(!empty($customer['address'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($customer['address']) ?></div>
        <?php endif; ?>
    </div>
    <div class="meta-vertical-list">
        <div class="meta-item">
            <span class="meta-label">Invoice Date:</span>
            <span class="meta-value"><?= $invoiceDate ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Due Date:</span>
            <span class="meta-value"><?= $dueDate ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Invoice Number:</span>
            <span class="meta-value"><?= htmlspecialchars($invoice['reference']) ?></span>
        </div>
    </div>
</div>

<?php if(!empty($invoiceHeading)): ?>
<div class="invoice-description"><?= $invoiceHeading ?></div>
<?php endif; ?>

<table class="invoice-table">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:33%;">Description</th>
            <th style="width:8%;">Qty</th>
            <th style="width:8%;">Unit</th>
            <th style="width:12%;">Unit Price</th>
            <th style="width:12%;" class="text-right">Amount</th>
            <th style="width:8%;">VAT</th>
            <th style="width:12%;" class="text-right">VAT Amt</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $subtotal = 0;
        $totalVat = 0;
        $hasVat = false;
        if($items && is_array($items)){
            $counter = 1;
            foreach($items as $item){
                $itemAmt       = floatval($item['amt']);
                $itemPrice     = floatval($item['price']);
                $itemQty       = floatval($item['quantity']);
                $itemVatRate   = floatval($item['vat_rate']);
                $itemVatAmount = floatval($item['vat_amount']);
                $subtotal += $itemAmt;
                $totalVat += $itemVatAmount;
                if($itemVatRate > 0) $hasVat = true;
        ?>
        <tr>
            <td><?= $counter ?></td>
            <td>
                <?= htmlspecialchars($item['item_name']) ?>
                <?php if($itemVatRate > 0): ?>
                <span class="vat-badge">VAT <?= number_format($itemVatRate, 0) ?>%</span>
                <?php endif; ?>
            </td>
            <td><?= number_format($itemQty, 0) ?></td>
            <td><?= htmlspecialchars($item['unit_name'] ?? '-') ?></td>
            <td><?= number_format($itemPrice, 2) ?></td>
            <td class="text-right"><?= number_format($itemAmt, 2) ?></td>
            <td><?= $itemVatRate > 0 ? number_format($itemVatRate, 0).'%' : '-' ?></td>
            <td class="text-right"><?= $itemVatAmount > 0 ? number_format($itemVatAmount, 2) : '-' ?></td>
        </tr>
        <?php
                $counter++;
            }
        }
        $grandTotal = $subtotal + $totalVat;
        $grandTotalTSH = $grandTotal * $currencyRate;
        ?>
    </tbody>
</table>

<div class="summary-section">
    <div class="summary-row">
        <div class="summary-label">Sub Total:</div>
        <div class="summary-value"><?= number_format($subtotal, 2) ?> <?= htmlspecialchars($currencyName) ?></div>
    </div>
    <?php if($hasVat): ?>
    <div class="summary-row">
        <div class="summary-label">Total VAT:</div>
        <div class="summary-value"><?= number_format($totalVat, 2) ?> <?= htmlspecialchars($currencyName) ?></div>
    </div>
    <?php endif; ?>
    <div class="summary-row grand-total">
        <div class="summary-label">Grand Total:</div>
        <div class="summary-value"><?= number_format($grandTotal, 2) ?> <?= htmlspecialchars($currencyName) ?></div>
    </div>
    <?php if(!$isTSH): ?>
    <div class="summary-row">
        <div class="summary-label">Grand Total (TSH):</div>
        <div class="summary-value"><?= number_format($grandTotalTSH, 2) ?> TSH</div>
    </div>
    <?php endif; ?>
</div>

<div class="bank-info">
    <div class="bank-title">PAYMENT DETAILS</div>
    <strong>Account name:</strong> JADON LTD<br>
    <strong>Account number:</strong> 005000079806<br>
    <strong>Bank name:</strong> AZANIA BANK<br>
    <strong>Branch name:</strong> TEGETA BRANCH<br>
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



<div class="invoice-footer">
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