<?php
 require_once "../../functions/purchase_order_functions.php";
  require_once "../../functions/subsidiary_functions.php";
require_once "../../configs.php";
   $conn = openConn();
$purchaseOrderId = (int) $_GET['id'];
$signature_name = $_GET['signature_name'] ?? '';
$signature_position = $_GET['signature_position'] ?? '';
$purchaseOrder = selectPurchaseOrderById($conn, $purchaseOrderId);
$items = selectPurchaseOrderItemsByPurchaseOrderId($conn, $purchaseOrderId);
$supplier = selectSubsidiaryById($conn, (int)$purchaseOrder['supplier_id']);
$isTSH = strtoupper($purchaseOrder['currency_name']) === 'TSH';
$rate = floatval($purchaseOrder['currency_rate']) ?: 1;
$invoiceHeading = !empty($purchaseOrder['invoice_heading']) ? htmlspecialchars($purchaseOrder['invoice_heading']) : '';
$paymentTerms = !empty($purchaseOrder['payment_terms']) ? htmlspecialchars($purchaseOrder['payment_terms']) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?= htmlspecialchars($purchaseOrder['ref_no']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.4; }
        .invoice-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; margin-bottom: 20px; }
        .invoice-header-logo { width: 150px; height: 150px; flex-shrink: 0; }
        .invoice-header-logo img { width: 100%; height: 100%; object-fit: contain; }
        .invoice-main-title { font-size: 48px; font-weight: bold; color: #d97c2b; text-align: right; letter-spacing: 1px; flex: 1; }
        .info-row { display: flex; justify-content: space-between; align-items: flex-start; margin: 20px 0; padding: 15px 0; border-bottom: 1px solid #ddd; }
        .bill-to-section { flex: 1; }
        .bill-to-label { font-weight: bold; font-size: 13px; color: #000; margin-bottom: 5px; }
        .bill-to-value { font-size: 13px; color: #333; font-weight: bold; }
        .meta-vertical-list { flex: 1; text-align: right; }
        .meta-item { margin-bottom: 8px; font-size: 13px; }
        .meta-label { font-weight: bold; color: #000; }
        .meta-value { color: #333; margin-left: 5px; }
        .invoice-description { margin: 20px 0; font-size: 13px; line-height: 1.5; color: #333; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .invoice-table th { background: #d97c2b; color: #fff; padding: 12px 8px; text-align: left; font-size: 13px; font-weight: bold; border: 1px solid #ddd; }
        .invoice-table td { padding: 10px 8px; border: 1px solid #ddd; font-size: 12px; }
        .invoice-table tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .summary-section { margin-top: 20px; width: 100%; }
        .summary-row { display: flex; justify-content: flex-end; margin-bottom: 8px; }
        .summary-label { width: 200px; font-weight: bold; text-align: right; padding-right: 20px; color: #000; font-size: 13px; }
        .summary-value { width: 150px; text-align: right; font-size: 13px; }
        .grand-total { font-size: 16px; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
        .grand-total .summary-label, .grand-total .summary-value { font-weight: bold; }
        .payment-terms-section { margin-top: 30px; font-size: 12px; line-height: 1.6; }
        .payment-terms-title { font-weight: bold; margin-bottom: 5px; color: #000; font-size: 14px; }
        .signature-section { margin-top: 50px; display: flex; flex-direction: column; align-items: flex-start; width: 300px; }
        .signature-line { width: 100%; border-top: 2px solid #000; margin-bottom: 10px; }
        .signature-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .signature-position { font-size: 12px; color: #333; }
        .invoice-footer { margin-top: 40px; font-size: 11px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 15px; line-height: 1.5; }
        @media print { body { padding: 10px; } }
    </style>
</head>
<body>

<div class="invoice-header">
    <div class="invoice-header-logo">
        <img src="../../dist/img/logo.png" alt="Company Logo">
    </div>
    <div class="invoice-main-title">Purchase Order</div>
</div>

<div class="info-row">
    <div class="bill-to-section">
        <div class="bill-to-label">Supplier:</div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['name'] ?? 'N/A') ?></div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['email'] ?? '') ?></div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['tin'] ?? '') ?></div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['vrn'] ?? '') ?></div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['phone'] ?? '') ?></div>
        <div class="bill-to-value"><?= htmlspecialchars($supplier['address'] ?? '') ?></div>
    </div>
    <div class="meta-vertical-list">
        <div class="meta-item">
            <span class="meta-label">Date:</span>
            <span class="meta-value"><?= date('d-M-Y', strtotime($purchaseOrder['date_'])) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Expiry Date:</span>
            <span class="meta-value"><?= date('d-M-Y', strtotime($purchaseOrder['expiry_date'])) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">PO #:</span>
            <span class="meta-value"><?= htmlspecialchars($purchaseOrder['ref_no']) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Status:</span>
            <span class="meta-value"><?= ucfirst($purchaseOrder['status']) ?></span>
        </div>
        
        <?php if(!$isTSH): ?>
        <div class="meta-item">
            <span class="meta-label">Exchange Rate:</span>
            <span class="meta-value">1 <?= htmlspecialchars($purchaseOrder['currency_name']) ?> = <?= number_format($rate, 2) ?> TSH</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($invoiceHeading)): ?>
<div class="invoice-description"><?= $invoiceHeading ?></div>
<?php endif; ?>

<table class="invoice-table">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:45%;">Description</th>
            <th style="width:10%;">Qty</th>
            <th style="width:10%;">Unit</th>
            <th style="width:15%;">Unit Price (<?= htmlspecialchars($purchaseOrder['currency_name']) ?>)</th>
            <th style="width:15%;" class="text-right">Amount (<?= htmlspecialchars($purchaseOrder['currency_name']) ?>)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if(is_array($items) && count($items) > 0):
            $counter = 1;
            foreach($items as $item):
                $itemPrice = $isTSH ? floatval($item['price']) : floatval($item['price']) / $rate;
                $itemAmount = $isTSH ? floatval($item['amount']) : floatval($item['amount']) / $rate;
        ?>
        <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td><?= number_format(floatval($item['quantity']), 2) ?></td>
            <td><?= htmlspecialchars($item['unit_name'] ?? '-') ?></td>
            <td><?= number_format($itemPrice, 2) ?></td>
            <td class="text-right"><?= number_format($itemAmount, 2) ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center; color:#999; padding:30px;">NO ITEMS FOUND.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
$subtotal = floatval($purchaseOrder['subtotal']);
$taxAmount = floatval($purchaseOrder['tax_amount']);
$grandTotal = floatval($purchaseOrder['total_amount']);
$displaySubtotal = $isTSH ? $subtotal : $subtotal / $rate;
$displayTax = $isTSH ? $taxAmount : $taxAmount / $rate;
$displayGrand = $isTSH ? $grandTotal : $grandTotal / $rate;
?>

<div class="summary-section">
    <div class="summary-row">
        <div class="summary-label">Sub-total:</div>
        <div class="summary-value"><?= number_format($displaySubtotal, 2) ?> <?= htmlspecialchars($purchaseOrder['currency_name']) ?></div>
    </div>
    <?php if(floatval($purchaseOrder['tax_rate']) > 0): ?>
    <div class="summary-row">
        <div class="summary-label">VAT (<?= number_format(floatval($purchaseOrder['tax_rate']), 0) ?>%):</div>
        <div class="summary-value"><?= number_format($displayTax, 2) ?> <?= htmlspecialchars($purchaseOrder['currency_name']) ?></div>
    </div>
    <?php endif; ?>
    <div class="summary-row grand-total">
        <div class="summary-label">Total:</div>
        <div class="summary-value"><?= number_format($displayGrand, 2) ?> <?= htmlspecialchars($purchaseOrder['currency_name']) ?></div>
    </div>
    <?php if(!$isTSH): ?>
    <div class="summary-row">
        <div class="summary-label">Total (TSH):</div>
        <div class="summary-value"><?= number_format($grandTotal, 2) ?> TSH</div>
    </div>
    <?php endif; ?>
</div>

<?php if(!empty($paymentTerms)): ?>
<div class="payment-terms-section">
    <div class="payment-terms-title">TERMS AND CONDITIONS</div>
    <?= nl2br($paymentTerms) ?>
</div>
<?php endif; ?>

<div class="signature-section">
    <div class="signature-line"></div>
    <div class="signature-name"><?= htmlspecialchars($signature_name) ?></div>
    <div class="signature-position"><?= htmlspecialchars($signature_position) ?></div>
</div>

<div class="invoice-footer">
    <strong>JADON LIMITED</strong><br>
    2nd Floor, Tevi Commercial Park, Bagamoyo Rd, Plot No.576, Mbezi Beach, P.O. Box 60163, Dar es Salaam, Tanzania.<br>
    Email: info@jadon.co.tz | Tel: +255621009936 / +255 737 829 077 | TIN: 138-847-209 | VRN: 40-034505-T
</div>

<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>