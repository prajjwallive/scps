<?php
// admin/api/export_transactions_excel.php - Exports transaction data as Excel (XLSX)

session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Redirect to login or show error if not logged in
    header('Location: ../login.php');
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

require_once '../../includes/db_connection.php';

if ($link === false) {
    die('Database connection failed.');
}

// --- Include PhpSpreadsheet library ---
// Make sure you have installed PhpSpreadsheet via Composer: composer require phpoffice/phpspreadsheet
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get filter parameters from GET request
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $conditions[] = "(s.full_name LIKE ? OR t.nfc_id LIKE ? OR t.txn_id LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}
if (!empty($status) && in_array($status, ['success', 'pending'])) {
    $conditions[] = "t.status = ?";
    $params[] = $status;
    $types .= 's';
}
if (!empty($startDate)) {
    $conditions[] = "DATE(t.transaction_time) >= ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($endDate)) {
    $conditions[] = "DATE(t.transaction_time) <= ?";
    $params[] = $endDate;
    $types .= 's';
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

// Fetch all matching transactions (no pagination for export)
$sql = "SELECT
            t.txn_id,
            s.full_name AS student_name,
            t.nfc_id,
            t.total_amount,
            t.transaction_time,
            t.status
        FROM `transaction` t
        LEFT JOIN `student` s ON t.student_id = s.student_id
        $whereClause
        ORDER BY t.transaction_time DESC";

$stmt = mysqli_prepare($link, $sql);

if ($stmt === false) {
    die('Failed to prepare statement: ' . mysqli_error($link));
}

if (!empty($params)) {
    // Use call_user_func_array for dynamic binding
    $bind_names = [$types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format data for Excel if needed (e.g., amounts, dates)
    $row['total_amount'] = (float)$row['total_amount']; // Ensure numeric
    $row['transaction_time'] = date('Y-m-d H:i:s', strtotime($row['transaction_time'])); // Standardize date format
    $transactions[] = $row;
}

mysqli_stmt_close($stmt); // Correctly close the prepared statement
mysqli_close($link);     // Correctly close the database connection

// --- Prepare data for Spreadsheet ---
$spreadsheetData = [];
// Add header row
$spreadsheetData[] = ['Transaction ID', 'Student Name', 'NFC ID', 'Total Amount', 'Timestamp', 'Status'];

// Add data rows
foreach ($transactions as $txn) {
    $spreadsheetData[] = [
        $txn['txn_id'],
        $txn['student_name'],
        $txn['nfc_id'],
        $txn['total_amount'],
        $txn['transaction_time'],
        ucfirst($txn['status'])
    ];
}

// --- Generate Excel using PhpSpreadsheet ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray($spreadsheetData, NULL, 'A1'); // Populate sheet from array

// Set column widths for better readability (optional)
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('E')->setAutoSize(true);
$sheet->getColumnDimension('F')->setAutoSize(true);

// Set headers for download
$filename = 'transactions_report_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write the spreadsheet to the output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
