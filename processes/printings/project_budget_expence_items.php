<?php
ob_start();
require_once "../../functions/subsidiary_functions.php";
require_once "../../functions/master_functions.php";
require_once "../../functions/submain_functions.php";
require_once "../../functions/ledger_functions.php";
require_once "../../configs.php";
require_once "../../vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
ob_end_clean();
$conn = openConn();
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(60);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->setCellValue('A1', 'S/N');
$sheet->setCellValue('B1', 'Budget Item');
$sheet->setCellValue('C1', 'Amount');
$sheet->getStyle('A1:C1')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true, 'size' => 12]
]);
$row = 2;
$index = 1;
$expensesMaster = selectMasterByMasterName($conn, "Expenses");
if ($expensesMaster && is_array($expensesMaster)) {
    $submasters = selectAllSubmasterByMasterId($conn, $expensesMaster['id']);
    if ($submasters && is_array($submasters)) {
        foreach ($submasters as $submain) {
            $ledgers = selectLedgerBySubMainId($conn, $submain['id']);
            if ($ledgers && is_array($ledgers)) {
                foreach ($ledgers as $ledger) {
                    $subsidiaries = selectSubsidiariesBYledgerId($conn, $ledger['id']);
                    if ($subsidiaries && is_array($subsidiaries)) {
                        foreach ($subsidiaries as $sub) {
                            $sheet->setCellValue('A' . $row, $index);
                            $sheet->setCellValue('B' . $row, $sub['name']);
                            $sheet->setCellValue('C' . $row, '');
                            $row++;
                            $index++;
                        }
                    }
                }
            }
        }
    }
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="project_budget_upload_template.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;