<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; padding: 30px; line-height: 1.4; color: #333; }

    .invoice-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; margin-bottom: 20px; }
    .invoice-header-logo { width: 250px; height: 250px; flex-shrink: 0; }
    .invoice-header-logo img { width: 100%; height: 100%; object-fit: contain; }
    .invoice-main-title { font-size: 44px; font-weight: bold; color: #d97c2b; text-align: right; flex: 1; }

    .info-row { display: flex; justify-content: space-between; align-items: flex-start; margin: 20px 0; padding: 15px 0; border-bottom: 1px solid #ddd; }
    .bill-to-section { flex: 1; }
    .bill-to-label { font-weight: bold; font-size: 13px; color: #000; margin-bottom: 5px; }
    .bill-to-value { font-size: 13px; color: #333; font-weight: bold; margin-bottom: 2px; }
    .meta-vertical-list { flex: 1; text-align: right; }
    .meta-item { margin-bottom: 8px; font-size: 13px; }
    .meta-label { font-weight: bold; color: #000; }
    .meta-value { color: #333; margin-left: 5px; }

    .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .invoice-table thead tr { background: #d97c2b; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .invoice-table th { padding: 11px 8px; text-align: left; font-size: 12px; font-weight: bold; border: 1px solid #c06a1a; color: #fff; }
    .invoice-table td { padding: 9px 8px; border: 1px solid #ddd; font-size: 12px; }
    .invoice-table tbody tr:nth-child(even) { background: #fdf3e7; }
    .text-right { text-align: right; }

    .vat-badge { display: inline-block; background: #d97c2b; color: #fff; font-size: 9px; font-weight: bold; padding: 1px 4px; border-radius: 3px; margin-left: 5px; vertical-align: middle; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .summary-section { margin-top: 20px; width: 100%; }
    .summary-row { display: flex; justify-content: flex-end; margin-bottom: 8px; }
    .summary-label { width: 220px; font-weight: bold; text-align: right; padding-right: 20px; color: #000; font-size: 13px; }
    .summary-value { width: 160px; text-align: right; font-size: 13px; }
    .grand-total { font-size: 15px; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
    .grand-total .summary-label, .grand-total .summary-value { font-weight: bold; }

    .sig-rows { margin-top: 40px; width: 100%; }
    .sig-row { display: flex; align-items: center; margin-bottom: 25px; gap: 20px; }
    .sig-row-label { width: 110px; font-weight: bold; font-size: 12px; flex-shrink: 0; }
    .sig-field { display: flex; align-items: center; gap: 6px; flex: 1; font-size: 12px; border-bottom: 1px solid #000; padding-bottom: 4px; }
    .sig-field span { font-weight: bold; white-space: nowrap; }
    .sig-line { flex: 1; min-width: 60px; }

    .signature-section { margin-top: 50px; display: flex; flex-direction: column; align-items: flex-start; width: 280px; }
    .signature-line { width: 100%; border-top: 2px solid #000; margin-bottom: 8px; }
    .signature-name { font-size: 13px; font-weight: bold; color: #000; margin-bottom: 3px; }
    .signature-position { font-size: 12px; color: #555; }

    .invoice-footer { margin-top: 40px; font-size: 11px; color: #fff; text-align: center; background: #d97c2b; padding: 15px 20px; line-height: 1.7; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .invoice-footer strong { color: #fff; font-size: 13px; }

    @media print {
        body { padding: 15px; }
    }
</style>

<?php
require_once "../../../configs.php";
require_once "../../../functions/voucher_functions.php";
require_once "../../../functions/subsidiary_functions.php";
$conn = openConn();
$invoiceId = (int) $_GET['id'];
$signature_name     = isset($_GET['signature_name'])     ? htmlspecialchars($_GET['signature_name'])     : '';
$signature_position = isset($_GET['signature_position']) ? htmlspecialchars($_GET['signature_position']) : '';

$voucher = selectPurchaseVoucherById($conn, $invoiceId);
$items = selectPurchaseVoucherItems($conn, $invoiceId);
$supplier = selectSubsidiaryById($conn, $voucher['supplier_id']);
$currencyName = $voucher['currency_name'];
$currencyRate = floatval($voucher['currency_rate']);
$voucherDate = date('d-M-Y', strtotime($voucher['date_']));
$isTSH = strtoupper($currencyName) === 'TSH';
$status = $voucher['status'];

$createdByName  = !empty($voucher['created_by_name'])  ? htmlspecialchars($voucher['created_by_name'])  : '-';
$reviewedByName = !empty($voucher['reviewed_by_name']) ? htmlspecialchars($voucher['reviewed_by_name']) : '-';
$approvedByName = !empty($voucher['approved_by_name']) ? htmlspecialchars($voucher['approved_by_name']) : '-';
$createdAt  = !empty($voucher['created_at'])  ? date('d-M-Y', strtotime($voucher['created_at']))  : '-';
$reviewedAt = !empty($voucher['reviewed_at']) ? date('d-M-Y', strtotime($voucher['reviewed_at'])) : '-';
$approvedAt = !empty($voucher['approved_at']) ? date('d-M-Y', strtotime($voucher['approved_at'])) : '-';

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

<div class="invoice-header">
    <div class="invoice-header-logo">
        <img src="../../../dist/img/logo.png" alt="Company Logo">
    </div>
    <div class="invoice-main-title">Purchase Voucher</div>
</div>

<div class="info-row">
    <div class="bill-to-section">
        <div class="bill-to-label">Supplier:</div>
        <div class="bill-to-value"><?= htmlspecialchars($voucher['supplier_name']) ?></div>
        <?php if(!empty($supplier['email'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['email']) ?></div>
        <?php endif; ?>
        <?php if(!empty($supplier['tin'])): ?>
        <div class="bill-to-value">TIN: <?= htmlspecialchars($supplier['tin']) ?></div>
        <?php endif; ?>
        <?php if(!empty($supplier['vrn'])): ?>
        <div class="bill-to-value">VRN: <?= htmlspecialchars($supplier['vrn']) ?></div>
        <?php endif; ?>
        <?php if(!empty($supplier['phone'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['phone']) ?></div>
        <?php endif; ?>
        <?php if(!empty($supplier['address'])): ?>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['address']) ?></div>
        <?php endif; ?>
    </div>
    <div class="meta-vertical-list">
        <div class="meta-item">
            <span class="meta-label">Voucher Date:</span>
            <span class="meta-value"><?= $voucherDate ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Reference:</span>
            <span class="meta-value"><?= htmlspecialchars($voucher['reference']) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Currency:</span>
            <span class="meta-value"><?= htmlspecialchars($currencyName) ?></span>
        </div>
    </div>
</div>

<table class="invoice-table">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:38%;">Description</th>
            <th style="width:8%;">Qty</th>
            <th style="width:10%;">Unit</th>
            <th style="width:14%;">Unit Price</th>
            <th style="width:14%;" class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if($items && is_array($items)){
            $counter = 1;
            foreach($items as $item){
                $itemAmt      = floatval($item['amt']);
                $itemPrice    = floatval($item['price']);
                $itemQty      = floatval($item['quantity']);
                $itemVatRate  = floatval($item['vat_rate']);
                $itemHasVat   = $itemVatRate > 0;
        ?>
        <tr>
            <td><?= $counter ?></td>
            <td>
                <?= htmlspecialchars($item['item_name']) ?>
                <?php if($itemHasVat): ?>
                <span class="vat-badge">VAT <?= number_format($itemVatRate, 0) ?>%</span>
                <?php endif; ?>
            </td>
            <td><?= number_format($itemQty, 0) ?></td>
            <td><?= htmlspecialchars($item['unit_name'] ?? '-') ?></td>
            <td><?= number_format($itemPrice, 2) ?></td>
            <td class="text-right"><?= number_format($itemAmt, 2) ?></td>
        </tr>
        <?php
                $counter++;
            }
        }
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
        <div class="summary-label">VAT:</div>
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