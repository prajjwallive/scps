<?php
// student/profile.php - Student Profile and Order History

date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE STUDENT LOGIN ---
if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    $_SESSION['login_error_message'] = "You need to log in to view your profile.";
    header('Location: ../login.php'); // Adjust path if needed
    exit();
}

$current_student_id = $_SESSION['student_id'];

// Include database connection & packages
require_once '../includes/db_connection.php';
include '../includes/packages.php'; // For styling

// --- Initialize Variables ---
// Student Details
$student_data = null;
$nfc_data = null;
$profile_error_message = "";
// Order History
$orders = [];
$orders_error_message = "";
// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$itemsPerPage = 5; // Show fewer orders directly on profile page
$offset = ($page - 1) * $itemsPerPage;
$totalOrderRecords = 0;


// --- FETCH STUDENT PROFILE DATA ---
$sqlFetchStudent = "SELECT s.full_name, s.contact_number, s.student_email, s.username, s.nfc_id,
                           nc.current_balance, nc.status as nfc_status, nc.last_used as nfc_last_used
                    FROM student s
                    LEFT JOIN nfc_card nc ON s.student_id = nc.student_id
                    WHERE s.student_id = ?";

if ($stmtFetch = mysqli_prepare($link, $sqlFetchStudent)) {
    mysqli_stmt_bind_param($stmtFetch, "i", $current_student_id);
    if (mysqli_stmt_execute($stmtFetch)) {
        $result = mysqli_stmt_get_result($stmtFetch);
        if ($data = mysqli_fetch_assoc($result)) {
            $student_data = $data; // Store fetched data
        } else {
            $profile_error_message = "Could not retrieve your profile information.";
        }
        mysqli_free_result($result);
    } else {
        $profile_error_message = "Error fetching profile: " . mysqli_stmt_error($stmtFetch);
        error_log("DB Execute Error (student/profile.php - fetch student): " . mysqli_stmt_error($stmtFetch));
    }
    mysqli_stmt_close($stmtFetch);
} else {
    $profile_error_message = "Database error preparing to fetch profile: " . mysqli_error($link);
    error_log("DB Prepare Error (student/profile.php - fetch student): " . mysqli_error($link));
}


// --- FETCH ORDER HISTORY DATA (Similar to order_history.php) ---

// Count total orders first for pagination
$sqlCountOrders = "SELECT COUNT(*) AS total FROM transaction WHERE student_id = ?";
if ($stmtCount = mysqli_prepare($link, $sqlCountOrders)) {
    mysqli_stmt_bind_param($stmtCount, "i", $current_student_id);
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    if ($rowCount = mysqli_fetch_assoc($resultCount)) {
        $totalOrderRecords = (int)$rowCount['total'];
    } else {
         error_log("DB Error (student/profile.php - count orders): Failed to fetch count.");
         $orders_error_message = "Could not count total orders.";
    }
    mysqli_stmt_close($stmtCount);
} else {
    error_log("DB Error (student/profile.php - count orders prepare): " . mysqli_error($link));
     $orders_error_message = "Error preparing to count orders.";
}

// Fetch paginated orders if count > 0 and no error counting
if ($totalOrderRecords > 0 && empty($orders_error_message)) {
    $sqlFetchOrders = "SELECT txn_id, formatted_id, total_amount, status, transaction_time
                       FROM transaction
                       WHERE student_id = ?
                       ORDER BY transaction_time DESC
                       LIMIT ?, ?";
    if ($stmtOrders = mysqli_prepare($link, $sqlFetchOrders)) {
        mysqli_stmt_bind_param($stmtOrders, "iii", $current_student_id, $offset, $itemsPerPage);
        if (mysqli_stmt_execute($stmtOrders)) {
             $resultOrders = mysqli_stmt_get_result($stmtOrders);
             while ($row = mysqli_fetch_assoc($resultOrders)) {
                 $orders[] = $row;
             }
             mysqli_free_result($resultOrders);
        } else {
             $orders_error_message = "Error fetching order history details.";
             error_log("DB Error (student/profile.php - fetch orders execute): " . mysqli_stmt_error($stmtOrders));
        }
        mysqli_stmt_close($stmtOrders);
    } else {
        $orders_error_message = "Error preparing order history query.";
        error_log("DB Error (student/profile.php - fetch orders prepare): " . mysqli_error($link));
    }
}

$totalPages = ceil($totalOrderRecords / $itemsPerPage);

// No longer including header.php here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile & Orders - <?php echo $student_data ? htmlspecialchars($student_data['full_name']) : 'Student'; ?></title>
    <style>
        /* Combine styles from admin/profile.php (view) and student/order_history.php */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background for the body */
            color: #1f2937; /* Default text color */
        }
        .profile-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .profile-section, .orders-section { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .profile-detail { margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .profile-detail:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .profile-detail label { font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem; }
        .profile-detail span { color: #1f2937; font-size: 1.05rem; /* Slightly smaller than admin profile */ }
        .nfc-status-Active { color: #059669; } /* green-600 */
        .nfc-status-Inactive, .nfc-status-Lost, .nfc-status-Blocked { color: #dc2626; } /* red-600 */
        .order-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .order-table th, .order-table td { border: 1px solid #e2e8f0; padding: 0.75rem 1rem; text-align: left; font-size: 0.9rem; color: #1f2937; }
        .order-table th { background-color: #f7fafc; font-weight: 600; color: #4b5563; }
        .order-table tr:nth-child(even) { background-color: #f9fafb; }
        .order-table a { color: #4299e1; text-decoration: none; }
        .order-table a:hover { text-decoration: underline; }
        .status-success { color: #38a169; font-weight: bold; }
        .status-failed { color: #e53e3e; font-weight: bold; }
        .status-refunded { color: #dd6b20; font-weight: bold; }
        .pagination { margin-top: 1.5rem; text-align: center; }
        .pagination a, .pagination span { display: inline-block; padding: 0.5rem 0.75rem; margin: 0 0.25rem; border: 1px solid #cbd5e0; border-radius: 0.25rem; text-decoration: none; color: #4a5568; }
        .pagination a:hover { background-color: #e2e8f0; }
        .pagination .current-page { background-color: #4299e1; color: white; border-color: #4299e1; }
        .pagination .disabled { color: #a0aec0; pointer-events: none; }
        .no-orders { text-align: center; padding: 1.5rem; margin-top: 1.5rem; color: #6b7280; }
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .error { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

    <header class="bg-white text-blue-600 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold">Smart Canteen</h1>
            <nav>
                <a href="../index.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Home</a>
                <a href="order_history.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Order History</a>
                <a href="../api/logout.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-200 font-semibold">Logout</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow">
        <div class="profile-container">
            <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">My Profile & Order History</h1>

            <section class="profile-section">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Profile Information</h2>
                <?php if (!empty($profile_error_message)): ?>
                    <div class="message error"><?php echo $profile_error_message; ?></div>
                <?php elseif ($student_data): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <div class="profile-detail">
                            <label>Full Name:</label>
                            <span><?php echo htmlspecialchars($student_data['full_name']); ?></span>
                        </div>
                        <div class="profile-detail">
                            <label>Username:</label>
                            <span><?php echo htmlspecialchars($student_data['username']); ?></span>
                        </div>
                        <div class="profile-detail">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($student_data['student_email']); ?></span>
                        </div>
                        <div class="profile-detail">
                            <label>Contact Number:</label>
                            <span><?php echo htmlspecialchars($student_data['contact_number']); ?></span>
                        </div>
                        <?php if ($student_data['nfc_id']): // Only show NFC details if linked ?>
                            <div class="profile-detail">
                                <label>NFC Card ID:</label>
                                <span><?php echo htmlspecialchars($student_data['nfc_id']); ?></span>
                            </div>
                            <div class="profile-detail">
                                <label>Card Status:</label>
                                <span class="font-semibold nfc-status-<?php echo htmlspecialchars($student_data['nfc_status']); ?>">
                                    <?php echo htmlspecialchars($student_data['nfc_status']); ?>
                                </span>
                            </div>
                             <div class="profile-detail">
                                <label>Current Balance:</label>
                                <span class="font-bold text-green-600">
                                    ₹<?php echo number_format($student_data['current_balance'] ?? 0, 2); ?>
                                </span>
                            </div>
                            <div class="profile-detail">
                                <label>NFC Card Last Used:</label>
                                 <span>
                                    <?php
                                    if ($student_data['nfc_last_used']) {
                                         try {
                                             $date = new DateTime($student_data['nfc_last_used'], new DateTimeZone('UTC')); // Adjust if DB timezone is different
                                             $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                             echo $date->format('M j, Y, g:i A');
                                         } catch (Exception $e) {
                                             echo 'N/A';
                                         }
                                     } else {
                                         echo 'Never';
                                     }
                                    ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="profile-detail md:col-span-2">
                                <label>NFC Card:</label>
                                <span>Not Linked</span>
                            </div>
                        <?php endif; ?>
                    </div>
                     <?php endif; ?>
            </section>

            <section class="orders-section">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Recent Orders</h2>

                <?php if (!empty($orders_error_message)): ?>
                    <div class="message error"><?php echo $orders_error_message; ?></div>
                <?php elseif (empty($orders)): ?>
                    <div class="no-orders">
                        <p>You haven't placed any orders recently.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full order-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo htmlspecialchars($order['formatted_id']); ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                            <?php
                                            try {
                                                $date = new DateTime($order['transaction_time'], new DateTimeZone('UTC')); // Adjust if DB timezone is different
                                                $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                                echo $date->format('M j, Y, g:i A');
                                            } catch (Exception $e) { echo 'N/A'; }
                                            ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-700">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-center">
                                            <span class="status-<?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-center">
                                            <a href="order_details.php?txn_id=<?php echo $order['txn_id']; ?>" title="View Details" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Order History Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="hover:bg-gray-200">&laquo; Previous</a>
                        <?php else: ?>
                            <span class="disabled">&laquo; Previous</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="hover:bg-gray-200"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="hover:bg-gray-200">Next &raquo;</a>
                        <?php else: ?>
                            <span class="disabled">Next &raquo;</span>
                        <?php endif; ?>
                    </nav>
                     <?php // Optional: Link to full order history page if you keep that separate too
                     if($totalOrderRecords > $itemsPerPage) echo '<div class="text-center mt-4"><a href="order_history.php" class="text-indigo-600 hover:text-indigo-800">View All Orders</a></div>';
                     ?>
                    <?php endif; ?>

                <?php endif; ?>
            </section>

        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 text-center mt-auto">
        <div class="container mx-auto">
            &copy; <?= date('Y') ?> Smart Canteen. All rights reserved.
        </div>
    </footer>

    <?php
    // Close DB connection
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>
