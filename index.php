<?php
// scps1/index.php - Main student menu page for NFC-based ordering

// Start the session to manage the cart
session_start();

// --- PHPMailer Setup ---
// You must install PHPMailer via Composer. From your scps1/ directory, run:
// composer require phpmailer/phpmailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Path to Composer's autoload.php
$autoloadPath = __DIR__ . '/vendor/autoload.php';

// Check if Composer autoload.php exists and load it
if (!file_exists($autoloadPath)) {
    error_log("[FATAL ERROR] Composer autoload.php NOT FOUND at: " . $autoloadPath . ". Please run 'composer install' in the scps1/ directory.");
    // If we're in an AJAX context, return an error. Otherwise, display a message.
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'System error: PHPMailer dependencies not found. Please contact support. (Code 101)']);
        exit();
    } else {
        die("<h1>System Error</h1><p>PHPMailer dependencies not found. Please run <code>composer install</code> in the <code>scps1/</code> directory.</p>");
    }
}
require $autoloadPath;

// Verify if PHPMailer class is available after autoload
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    error_log("[FATAL ERROR] PHPMailer class 'PHPMailer\\PHPMailer\\PHPMailer' not found after autoload. This might indicate a corrupted Composer installation or a problem within autoload.php itself.");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'System error: PHPMailer class not loaded. Please contact support. (Code 102)']);
        exit();
    } else {
        die("<h1>System Error</h1><p>PHPMailer class not loaded. Please contact support. (Code 102)</p>");
    }
} else {
    error_log("[DEBUG] PHPMailer class 'PHPMailer\\PHPMailer\\PHPMailer' is available.");
}
// --- END PHPMailer Setup ---


// Include database connection
require_once __DIR__ . '/includes/db_connection.php';

// Check database connection
if (!$link || mysqli_connect_errno()) {
    error_log("index.php: Database connection failed: " . mysqli_connect_error());
    $db_connection_error = true;
} else {
    $db_connection_error = false;
}

// Initialize cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to safely execute shell commands and capture output
// This function will execute the Python script to read the NFC tag
function executePythonScript($scriptPath) {
    error_log("executePythonScript: Attempting to execute Python script: " . $scriptPath);
    // Escape the script path to prevent shell injection issues
    $escapedScriptPath = escapeshellarg($scriptPath);

    // Command to execute the Python script
    // Use 'python' or 'python3' based on your system's configuration
    // Ensure that 'python' is in your system's PATH, or provide the full path to python.exe
    $command = "python " . $escapedScriptPath;
    error_log("executePythonScript: Full command: " . $command);

    // Execute the command using proc_open for better error handling and output capture
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout (where the NFC ID will be printed)
        2 => array("pipe", "w")   // stderr (where Python errors will be printed)
    );
    $process = proc_open($command, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        error_log("executePythonScript: Failed to open process for command: " . $command);
        return ['success' => false, 'error' => 'Failed to execute NFC reader script. Check server logs.'];
    }

    // Close stdin as we don't need to send input to the Python script
    fclose($pipes[0]);

    // Read stdout (the NFC ID) and stderr (any Python errors)
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // It is important to close the process before getting the return value
    $returnValue = proc_close($process);
    error_log("executePythonScript: Python script exited with code: " . $returnValue);
    error_log("executePythonScript: Python STDOUT: '" . trim($stdout) . "'");
    error_log("executePythonScript: Python STDERR: '" . trim($stderr) . "'");

    // Check if there was any error output from Python (stderr indicates a script error)
    if (!empty($stderr)) {
        // Log the error for server-side debugging, but provide a generic message to the user
        error_log("executePythonScript: Python script STDERR detected: " . $stderr);
        return ['success' => false, 'error' => 'NFC reader encountered a critical script error: ' . trim($stderr)];
    }

    // If stdout contains an "ERROR:" prefix, it's an error message from our Python script logic
    if (strpos(trim($stdout), "ERROR:") === 0) {
        error_log("executePythonScript: Python script returned application error: " . $stdout);
        return ['success' => false, 'error' => 'NFC reader error: ' . trim(str_replace('ERROR:', '', $stdout))];
    }

    // If successful, return the trimmed data from stdout (which should be the NFC ID)
    if (!empty(trim($stdout))) {
        return ['success' => true, 'data' => trim($stdout)];
    } else {
        return ['success' => false, 'error' => 'No NFC data received within timeout. Please tap your card.'];
    }
}

// --- AJAX Request Handling ---
// Unified approach to get action from either JSON body or $_POST
$input_data = []; // Initialize to empty array
$action = null;

// Attempt to read JSON raw input first
$input_json = file_get_contents('php://input');
if ($input_json) {
    $decoded_json = json_decode($input_json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $input_data = $decoded_json;
        if (isset($input_data['action'])) {
            $action = $input_data['action'];
        }
    } else {
        error_log("index.php: JSON decode error for raw input: " . json_last_error_msg() . ". Raw input: " . $input_json);
    }
}

// If action not found in JSON, check $_POST (for x-www-form-urlencoded requests)
if ($action === null && isset($_POST['action'])) {
    $action = $_POST['action'];
    // For actions like scan_nfc_card_auto, $_POST will contain the necessary data.
}

if ($action) { // Only proceed if an action is identified
    header('Content-Type: application/json'); // Always respond with JSON for AJAX actions

    // Action: Scan NFC card and retrieve student details
    if ($action === 'scan_nfc_card_auto') {
        error_log("--- scan_nfc_card_auto action called ---");
        if ($db_connection_error) {
            error_log("scan_nfc_card_auto: Database connection error detected.");
            echo json_encode(['status' => 'error', 'message' => 'Database connection error. Cannot scan NFC.']);
            exit;
        }

        // Define the path to your Python script relative to index.php
        $pythonScriptPath = __DIR__ . '/read_nfc_once.py';
        $scan_result = executePythonScript($pythonScriptPath);

        if ($scan_result['success']) {
            $nfc_id = $scan_result['data']; // The NFC ID captured from Python
            error_log("scan_nfc_card_auto: Successfully received NFC ID from Python: '" . $nfc_id . "' (Length: " . strlen($nfc_id) . ")");

            // Query to get student details and balance using NFC ID
            $stmt = $link->prepare("SELECT s.student_id, s.full_name, s.parent_email, nc.current_balance, nc.nfc_id
                                    FROM nfc_card nc
                                    JOIN student s ON nc.student_id = s.student_id
                                    WHERE nc.nfc_id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $nfc_id);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res->num_rows > 0) {
                    $student_data = $res->fetch_assoc();
                    error_log("scan_nfc_card_auto: Student found for NFC ID " . $nfc_id . ": " . $student_data['full_name']);
                    
                    // Store student info in session for later use in payment
                    $_SESSION['current_student_info'] = [
                        'student_id' => $student_data['student_id'],
                        'full_name' => $student_data['full_name'],
                        'parent_email' => $student_data['parent_email'], // Store parent email
                        'nfc_id' => $student_data['nfc_id'],
                        'current_balance' => $student_data['current_balance']
                    ];

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'NFC card read successfully!',
                        'nfc_id' => $student_data['nfc_id'],
                        'student_id' => $student_data['student_id'],
                        'student_name' => $student_data['full_name'],
                        'current_balance' => $student_data['current_balance']
                    ]);
                } else {
                    error_log("scan_nfc_card_auto: No student found for NFC ID: '" . $nfc_id . "'");
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'NFC ID "' . htmlspecialchars($nfc_id) . '" not found or not linked to any student.',
                        'nfc_id' => $nfc_id
                    ]);
                }
                $stmt->close();
            } else {
                error_log("scan_nfc_card_auto: Failed to prepare student query: " . $link->error);
                echo json_encode(['status' => 'error', 'message' => 'Database query error during NFC scan. ' . $link->error]);
            }
        } else {
            error_log("scan_nfc_card_auto: Python script failed: " . $scan_result['error']);
            echo json_encode(['status' => 'error', 'message' => $scan_result['error']]);
        }
    }
    // Action: Process the final payment
    else if ($action === 'process_final_payment') { 
        error_log("--- process_final_payment action called ---");
        if ($db_connection_error) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection error. Cannot process payment.']);
            exit;
        }

        // Data received from JavaScript (now consistently from $input_data)
        $nfc_id_from_js = $input_data['nfc_id'] ?? '';
        $pin = trim($input_data['pin'] ?? ''); 
        $cart_items = $input_data['cart_items'] ?? [];

        // Retrieve student info from session, set during scan_nfc_card_auto
        if (!isset($_SESSION['current_student_info']) || $_SESSION['current_student_info']['nfc_id'] !== $nfc_id_from_js) {
            error_log("process_final_payment: Session student info missing or NFC ID mismatch. Session NFC: " . ($_SESSION['current_student_info']['nfc_id'] ?? 'N/A') . ", JS NFC: " . $nfc_id_from_js);
            echo json_encode(['status' => 'error', 'message' => 'Session expired or NFC card mismatch. Please restart order.']);
            exit;
        }

        $student_id = $_SESSION['current_student_info']['student_id'];
        $nfc_id = $_SESSION['current_student_info']['nfc_id']; // Use the NFC ID from session as primary
        $student_name = $_SESSION['current_student_info']['full_name'];
        $parent_email = $_SESSION['current_student_info']['parent_email'];

        // Calculate total amount from cart items
        $total_amount = 0;
        $food_prices_map = []; // To store actual prices from DB
        $food_ids_in_cart = [];
        foreach ($cart_items as $item) {
            $food_ids_in_cart[] = $item['food_id'];
        }

        if (!empty($food_ids_in_cart)) {
            $placeholders = implode(',', array_fill(0, count($food_ids_in_cart), '?'));
            $stmt_food_prices = $link->prepare("SELECT food_id, price FROM food WHERE food_id IN ($placeholders)");
            if (!$stmt_food_prices) { throw new Exception("Failed to prepare food prices query: " . $link->error); }
            $types = str_repeat('i', count($food_ids_in_cart));
            $stmt_food_prices->bind_param($types, ...$food_ids_in_cart);
            $stmt_food_prices->execute();
            $res_food_prices = $stmt_food_prices->get_result();
            while ($row = $res_food_prices->fetch_assoc()) {
                $food_prices_map[$row['food_id']] = $row['price'];
            }
            $stmt_food_prices->close();
        }

        foreach ($cart_items as $item) {
            if (!isset($food_prices_map[$item['food_id']])) {
                throw new Exception("Food item ID " . $item['food_id'] . " not found or invalid.");
            }
            $total_amount += $food_prices_map[$item['food_id']] * intval($item['quantity']);
        }

        // Basic validation
        if (empty($pin) || $total_amount <= 0 || !is_array($cart_items) || empty($cart_items)) {
            error_log("process_final_payment: Invalid input data. PIN length: " . strlen($pin) . ", Cart empty: " . (empty($cart_items) ? 'Yes' : 'No') . ", Total amount: " . $total_amount);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payment data provided (missing PIN or empty cart).']);
            exit;
        }

        // Start a database transaction for atomicity
        $link->begin_transaction();
        error_log("process_final_payment: Database transaction started.");

        try {
            // 1. Fetch NFC card data (again, but for locking and current balance)
            $stmt_nfc = $link->prepare("SELECT current_balance, pin_hash, status FROM nfc_card WHERE nfc_id = ? AND student_id = ? FOR UPDATE"); // FOR UPDATE locks the row
            if (!$stmt_nfc) { throw new Exception("Failed to prepare NFC card query: " . $link->error); }
            $stmt_nfc->bind_param("si", $nfc_id, $student_id);
            $stmt_nfc->execute();
            $res_nfc = $stmt_nfc->get_result();

            if ($res_nfc->num_rows === 0) {
                throw new Exception("NFC ID not found or not linked to student in database.");
            }
            $nfc_db_data = $res_nfc->fetch_assoc();
            $current_balance = $nfc_db_data['current_balance'];
            $pin_hash = $nfc_db_data['pin_hash'];
            $card_status = $nfc_db_data['status'];
            $stmt_nfc->close();
            error_log("process_final_payment: NFC data fetched from DB. Current Balance: " . $current_balance . ", Card Status: " . $card_status);

            if ($card_status !== 'Active') {
                throw new Exception("Your NFC card is " . htmlspecialchars($card_status) . ". Cannot process order.");
            }

            // Verify the provided PIN against the hashed PIN
            if (!password_verify($pin, $pin_hash)) {
                error_log("DEBUG: PIN verification FAILED for NFC ID: " . $nfc_id . ". Received PIN (trimmed): '" . $pin . "' (Length: " . strlen($pin) . "), Stored PIN Hash: '" . $pin_hash . "' (Length: " . strlen($pin_hash) . ")");
                throw new Exception("Incorrect PIN provided. Please try again.");
            }
            error_log("process_final_payment: PIN verification SUCCESS for NFC ID: " . $nfc_id);

            // 2. Check if balance is sufficient
            error_log("process_final_payment: Checking balance. Current: " . $current_balance . ", Total Bill: " . $total_amount);
            if ($current_balance < $total_amount) {
                throw new Exception("Insufficient balance. Your current balance is Rs. " . number_format($current_balance, 2) . ". Required: Rs. " . number_format($total_amount, 2) . ".");
            }
            error_log("process_final_payment: Balance sufficient.");

            // 3. Record transaction
            $stmt_transaction = $link->prepare("INSERT INTO `transaction` (nfc_id, student_id, total_amount, status, transaction_time) VALUES (?, ?, ?, 'success', NOW())");
            if (!$stmt_transaction) { throw new Exception("Failed to prepare transaction insertion: " . $link->error); }
            $stmt_transaction->bind_param("sid", $nfc_id, $student_id, $total_amount);
            $stmt_transaction->execute();
            $txn_id = $link->insert_id; // Get the ID of the newly inserted transaction
            $stmt_transaction->close();
            error_log("process_final_payment: Transaction recorded with ID: " . $txn_id);

            // 4. Record transaction items
            $stmt_item = $link->prepare("INSERT INTO `transaction_item` (txn_id, food_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            if (!$stmt_item) { throw new Exception("Failed to prepare transaction item insertion: " . $link->error); }
            foreach ($cart_items as $item) {
                $food_id = intval($item['food_id']);
                $quantity = intval($item['quantity']);
                $unit_price = $food_prices_map[$food_id]; // Use price fetched from DB

                $stmt_item->bind_param("iiid", $txn_id, $food_id, $quantity, $unit_price);
                $stmt_item->execute();
                error_log("process_final_payment: Inserted transaction item: Txn ID " . $txn_id . ", Food ID " . $food_id . ", Qty " . $quantity);
            }
            $stmt_item->close();
            error_log("process_final_payment: All transaction items recorded.");

            // 5. Update NFC card balance
            $new_balance = $current_balance - $total_amount;
            $stmt_update_balance = $link->prepare("UPDATE nfc_card SET current_balance = ?, last_used = NOW() WHERE nfc_id = ?");
            if (!$stmt_update_balance) { throw new Exception("Failed to prepare balance update: " . $link->error); }
            $stmt_update_balance->bind_param("ds", $new_balance, $nfc_id);
            $stmt_update_balance->execute();
            $stmt_update_balance->close();
            error_log("process_final_payment: NFC card balance updated. New balance: " . $new_balance);

            // 6. Record activity log
            $stmt_log = $link->prepare("INSERT INTO activity_log (timestamp, activity_type, description, user_id, related_id) VALUES (NOW(), 'Order', ?, ?, ?)");
            if (!$stmt_log) { throw new Exception("Failed to prepare activity log insertion: " . $link->error); }
            $log_description = "Student " . $student_name . " (ID: " . $student_id . ") purchased items via NFC. Total: Rs. " . number_format($total_amount, 2);
            $stmt_log->bind_param("sii", $log_description, $student_id, $txn_id);
            $stmt_log->execute();
            $stmt_log->close();
            error_log("process_final_payment: Activity log recorded.");

            // Commit the transaction
            $link->commit();
            error_log("process_final_payment: Transaction committed successfully.");

            // Clear the session cart after successful order
            $_SESSION['cart'] = [];
            error_log("process_final_payment: Session cart cleared.");

            // --- Send Email Receipt ---
            if ($parent_email) {
                error_log("[DEBUG] Attempting to send email to parent: " . $parent_email);
                $mail = new PHPMailer(true);
                try {
                    // PHPMailer Debug Output - CRITICAL for debugging email issues
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
                    $mail->Debugoutput = 'error_log';      // Send debug output to the PHP error log

                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'poudelaman4@gmail.com'; // Your Gmail address
                    $mail->Password   = 'bqgguivdsdycqyzp';     // Your Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
                    $mail->Port       = 587; // Port for STARTTLS

                    // Recipients
                    $mail->setFrom('poudelaman4@gmail.com', 'United Technical Khaja Ghar'); // Sender email and name
                    $mail->addAddress($parent_email); // Recipient email (parent_email from DB)
                    $mail->addReplyTo('poudelaman4@gmail.com', 'Canteen Support'); // Reply-To header

                    // Content
                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Subject = 'Canteen Purchase Receipt for ' . htmlspecialchars($student_name);
                    
                    // Construct email body
                    $items_html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                    <thead style="background-color: #f2f2f2;"><tr><th>Item</th><th>Quantity</th><th>Price</th></tr></thead><tbody>';
                    foreach ($cart_items as $item) {
                        $item_name = htmlspecialchars($item['food_name'] ?? 'Unknown Item');
                        $item_total = $food_prices_map[$item['food_id']] * $item['quantity'];
                        $items_html .= "<tr><td>{$item_name}</td><td style='text-align:center;'>{$item['quantity']}</td><td style='text-align:right;'>Rs. " . number_format($item_total, 2) . "</td></tr>";
                    }
                    $items_html .= '</tbody></table>';

                    $email_body = "
                        <h1 style='color: #0056b3;'>Canteen Receipt</h1>
                        <p>Dear Parent,</p>
                        <p>This is a notification for a purchase made by your child, <strong>" . htmlspecialchars($student_name) . "</strong>, at the United Technical Khaja Ghar.</p>
                        <h3>Order Details (Transaction ID: {$txn_id})</h3>
                        {$items_html}
                        <p style='font-size: 1.2em; font-weight: bold; text-align: right;'>Total: Rs. " . number_format($total_amount, 2) . "</p>
                        <p style='font-size: 1.2em; font-weight: bold; text-align: right;'>New Card Balance: Rs. " . number_format($new_balance, 2) . "</p>
                        <hr><p style='font-size: 0.9em; color: #6c757d;'>This is an automated message. Please do not reply.</p>";

                    $mail->Body = $email_body;
                    $mail->AltBody = 'This is the plain text version for non-HTML email clients. Your child, ' . htmlspecialchars($student_name) . ', made a purchase at United Technical Khaja Ghar. Total: Rs. ' . number_format($total_amount, 2) . '. New Card Balance: Rs. ' . number_format($new_balance, 2) . '.';

                    $mail->send();
                    error_log("[DEBUG] Email sent successfully to " . $parent_email); 
                } catch (Exception $e) {
                    error_log("[ERROR] Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                    error_log("[ERROR] PHPMailer Exception Details: " . $e->getMessage());
                }
            } else {
                error_log("[DEBUG] Parent email not available for student ID: " . $student_id . ". Skipping email sending.");
            }
            // --- End Send Email Receipt ---

            // Update session balance after successful transaction
            $_SESSION['current_student_info']['current_balance'] = $new_balance;

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment successful! New balance: NPR ' . number_format($new_balance, 2),
                'new_balance' => $new_balance,
                'transaction_id' => $txn_id, // Return transaction ID for coupon
                'student_name' => $student_name // Return student name for coupon
            ]);

        } catch (Exception $e) {
            // Rollback on error
            $link->rollback();
            error_log("Payment processing error: Transaction rolled back. Error: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment failed: ' . $e->getMessage()
            ]);
        }
    }
    // Action to fetch all available food items (for the "All Available Food Items Section")
    else if ($action === 'get_all_food_items') { 
        error_log("--- get_all_food_items action called ---");
        if ($db_connection_error) {
            error_log("get_all_food_items: Database connection error detected.");
            echo json_encode(['status' => 'error', 'message' => 'Database connection error. Cannot fetch food items.']);
            exit;
        }

        $all_food_items = [];
        // Data consistently from $input_data
        $excluded_ids = $input_data['excluded_food_ids'] ?? [];

        error_log("get_all_food_items: Raw JSON Input: " . ($input_json ? $input_json : 'Empty'));
        error_log("get_all_food_items: Decoded Input: " . json_encode($input_data));
        error_log("get_all_food_items: Excluded IDs received: " . json_encode($excluded_ids));


        $sql_all_food = "SELECT food_id, name, price, image_path, category, description FROM food WHERE is_available = 1";
        if (!empty($excluded_ids)) {
            $placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
            $sql_all_food .= " AND food_id NOT IN ($placeholders)";
            error_log("get_all_food_items: SQL query with exclusions: " . $sql_all_food);
        } else {
            error_log("get_all_food_items: SQL query (no exclusions): " . $sql_all_food);
        }
        $sql_all_food .= " ORDER BY name";


        $stmt_all_food = $link->prepare($sql_all_food);
        if ($stmt_all_food) {
            if (!empty($excluded_ids)) {
                $types = str_repeat('i', count($excluded_ids));
                $bind_params = array();
                $bind_params[] = $types;
                foreach ($excluded_ids as &$id) { 
                    $bind_params[] = &$id;
                }
                call_user_func_array(array($stmt_all_food, 'bind_param'), $bind_params);
                error_log("get_all_food_items: Bound " . count($excluded_ids) . " excluded IDs.");
            }
            $stmt_all_food->execute();
            error_log("get_all_food_items: Statement executed. Error: " . $stmt_all_food->error); 
            $result_all_food = $stmt_all_food->get_result();
            if ($result_all_food) {
                while($row = $result_all_food->fetch_assoc()) {
                    $all_food_items[] = $row;
                }
                error_log("get_all_food_items: Fetched " . count($all_food_items) . " food items.");
            } else {
                error_log("get_all_food_items: get_result() failed: " . $stmt_all_food->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve results from food query: ' . $stmt_all_food->error]);
                $stmt_all_food->close();
                exit;
            }
            
            $stmt_all_food->close();
            echo json_encode(['status' => 'success', 'food_items' => $all_food_items]);
        } else {
            error_log("get_all_food_items: Failed to prepare all food query: " . $link->error);
            echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed for food items: ' . $link->error]);
        }
    }

    $link->close(); // Close database connection for AJAX requests
    exit; // Terminate script after handling AJAX request
}

// Helper function for call_user_func_array bind_param for older PHP versions (PHP < 5.6)
// This is needed because bind_param requires arguments to be passed by reference.
function ref_values($arr){
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;
}


// --- Initial Page Load Logic (Time-Based Menu) ---
$time_based_menu_items = [];
$current_menu_title = '';
$current_menu_type = null; // Will be 'Breakfast', 'Lunch', 'Dinner', or null
$display_time_based_section = true; // Control visibility of the entire section

// Fetch configured time ranges from the database
$menu_hours_db = [];
if (!$db_connection_error) {
    $sql_fetch_times = "SELECT menu_type, start_hour, start_minute, end_hour, end_minute FROM menu_time_settings";
    $result_fetch_times = $link->query($sql_fetch_times);
    if ($result_fetch_times) {
        while ($row = $result_fetch_times->fetch_assoc()) {
            $menu_hours_db[$row['menu_type']] = [
                'start_hour' => $row['start_hour'],
                'start_minute' => $row['start_minute'],
                'end_hour' => $row['end_hour'], 
                'end_minute' => $row['end_minute']
            ];
        }
    } else {
        error_log("index.php: Failed to fetch menu time settings: " . $link->error);
    }
}

// Default values if not found in DB
$default_hours = [
    'Breakfast' => ['start_hour' => 6, 'start_minute' => 0, 'end_hour' => 11, 'end_minute' => 0],
    'Lunch'     => ['start_hour' => 11, 'start_minute' => 0, 'end_hour' => 16, 'end_minute' => 0],
    'Dinner'    => ['start_hour' => 16, 'start_minute' => 0, 'end_hour' => 22, 'end_minute' => 0]
];
$actual_menu_hours = array_merge($default_hours, $menu_hours_db); // Use fetched settings, fallback to defaults


if (!$db_connection_error) {
    date_default_timezone_set('Asia/Kathmandu');
    $current_time_in_minutes = (int)date('G') * 60 + (int)date('i'); // Convert current time to minutes for comparison

    // Determine current active menu type based on saved/default settings
    foreach ($actual_menu_hours as $type => $hours) {
        $start_minutes = $hours['start_hour'] * 60 + $hours['start_minute'];
        $end_minutes = $hours['end_hour'] * 60 + $hours['end_minute'];

        if ($current_time_in_minutes >= $start_minutes && $current_time_in_minutes < $end_minutes) {
            $current_menu_type = $type;
            break;
        }
    }

    if ($current_menu_type) {
        // Set specific title for the current active menu
        switch ($current_menu_type) {
            case 'Breakfast': $current_menu_title = 'Good Morning! Today\'s Breakfast Specials'; break;
            case 'Lunch':     $current_menu_title = 'It\'s Lunch Time! Check Out These Options'; break;
            case 'Dinner':    $current_menu_title = 'Dinner is Served! What Will You Have?'; break;
            default: $current_menu_title = 'Currently no special menu active.'; break; // Fallback title
        }

        // Fetch items for the current active time-based menu
        $sql = "SELECT f.food_id, f.name, f.price, f.image_path, f.category, f.description
                FROM time_based_menu tbm
                JOIN food f ON tbm.food_id = f.food_id
                WHERE tbm.menu_type = ? AND f.is_available = 1 ORDER BY f.name";
        $stmt = $link->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $current_menu_type);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $time_based_menu_items[] = $row;
            }
            $stmt->close();
        } else {
            error_log("index.php: Failed to prepare current menu SQL: " . $link->error);
            $current_menu_title = "Error loading menu. Please try again later.";
        }
    } else {
        // No specific time-based menu is active (between defined ranges)
        $current_menu_title = 'Currently no special menu active.';
    }

    // --- Fetch ALL Food IDs that are part of ANY time-based menu ---
    // These items will be excluded from the "All Available Food Items" section
    $all_time_based_food_ids = [];
    $sql_all_time_menus = "SELECT DISTINCT food_id FROM time_based_menu";
    $result_all_time_menus = $link->query($sql_all_time_menus);
    if ($result_all_time_menus) {
        while ($row = $result_all_time_menus->fetch_assoc()) {
            $all_time_based_food_ids[] = $row['food_id'];
        }
    } else {
        error_log("index.php: Failed to fetch all time-based food IDs: " . $link->error);
    }

    // Determine if the time-based menu section should be displayed at all
    // It should ONLY display if there are actual items fetched for the current time-based menu.
    $display_time_based_section = !empty($time_based_menu_items);


} else {
    // Database connection error handling
    $current_menu_title = "Database connection error. Unable to load menus.";
    $all_time_based_food_ids = []; // Ensure this is empty on DB error
    $display_time_based_section = false; // Hide section on critical error
}
// --- End Time-Based Menu Logic & Configuration ---

// Close the main database connection after initial page load data is fetched
// Only close if connection was successful and it's not an AJAX request that already closed it
if (!$db_connection_error && $link && $link->ping() && !$action) { 
    $link->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Canteen - Order Food</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #007BFF; --accent-hover: #0056b3;
            --success-color: #28A745; --success-hover: #1e7e34;
            --danger-color: #DC3545; --danger-hover: #c82333;
            --text-primary: #212529; --text-secondary: #6C757D;
            --bg-main: #F8F9FA; --bg-card: #FFFFFF;
            --border-color: #DEE2E6; --light-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: var(--text-primary); }
        .header-title { color: var(--accent-color); font-weight: 800; }
        .section-title { color: var(--text-primary); font-weight: 700; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 8px; font-weight: 600; transition: all 0.2s; cursor: pointer; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-success-custom { background-color: var(--success-color); color: white; }
        .btn-success-custom:hover:not(:disabled) { background-color: var(--success-hover); transform: translateY(-1px); }
        .btn-danger-custom { background-color: var(--danger-color); color: white; }
        .btn-danger-custom:hover:not(:disabled) { background-color: var(--danger-hover); transform: translateY(-1px); }
        .btn-accent { background-color: var(--accent-color); color: white; }
        .btn-accent:hover:not(:disabled) { background-color: var(--accent-hover); transform: translateY(-1px); }
        .food-card { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; transition: all 0.3s; box-shadow: var(--light-shadow); border: 1px solid var(--border-color); }
        .food-card:hover { transform: translateY(-4px); border-color: var(--accent-color); }
        .food-card img { width: 100%; height: 180px; object-fit: cover; }
        .category-label { position: absolute; top: 10px; left: 10px; padding: 0.4rem 0.8rem; border-radius: 16px; font-size: 0.7rem; font-weight: 600; z-index: 10; text-transform: uppercase; color: white; }
        .cart-panel { background-color: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); }
        #cartTotal { color: var(--accent-color); font-weight: 700; }
        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; } /* Added flex for centering */
        .modal-content { background-color: var(--bg-card); margin: auto; padding: 2rem; border-radius: 12px; max-width: 95%; width: 500px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); } /* Added box-shadow */
        .modal-title { color: var(--accent-color); font-weight: 700; }
        .close-button { color: #aaa; position: absolute; top: 0.8rem; right: 1rem; font-size: 1.5rem; font-weight: bold; cursor: pointer; }
        input[type="text"], input[type="password"] { border: 1px solid #ced4da; border-radius: 8px; padding: 0.75rem 1rem; width: 100%; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: var(--accent-color); box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); outline: none; }
        #confirmationMessageBox { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 500; color: white; z-index: 1001; }
        #timeBasedMenuSection { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 16px; }
        
        /* Spinner for NFC scan */
        .nfc-loader {
            border: 4px solid #f3f3f3; /* Light grey */
            border-top: 4px solid #007BFF; /* Blue */
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <div id="confirmationMessageBox" class="hidden"></div>

    <header class="site-header p-5 shadow-md bg-white sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold flex items-center space-x-3 header-title">
                <i class="fas fa-utensils text-2xl"></i>
                <span>United Technical Khaja Ghar</span>
            </h1>
        </div>
    </header>

    <main class="flex-grow container mx-auto p-4 md:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <div class="lg:col-span-2">
                <!-- Time-Based Menu Section -->
                <?php if ($display_time_based_section): ?>
                <section id="timeBasedMenuSection" class="mb-8 p-6">
                    <h2 class="text-2xl section-title mb-5 text-gray-800">
                        <?= htmlspecialchars($current_menu_title) ?>
                    </h2>
                    <?php if (!empty($time_based_menu_items)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php foreach($time_based_menu_items as $item): ?>
                                <?php
                                    $categoryClass = $item['category'] === 'Veg' ? 'bg-green-500' : 'bg-red-500';
                                    // Image path relative to index.php (scps1/): ./uploads/food_images/image.jpg
                                    $imageSrc = !empty($item['image_path']) ? './' . htmlspecialchars($item['image_path']) : 'https://placehold.co/300x200/E0E0E0/4A4A4A?text=No+Image';
                                ?>
                                <div class="food-card flex flex-col">
                                    <div class="relative">
                                        <span class="category-label <?= $categoryClass ?>"><?= htmlspecialchars($item['category']) ?></span>
                                        <img src="<?= $imageSrc ?>" onerror="this.src='https://placehold.co/300x200/E0E0E0/4A4A4A?text=Error'" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="p-4 flex flex-col flex-grow">
                                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="text-gray-600 text-sm mb-4 flex-grow"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                                        <p class="text-2xl font-bold text-blue-600 mb-4">Rs. <?= number_format($item['price'], 2) ?></p>
                                        <button data-food-id="<?= $item['food_id'] ?>"
                                                data-food-name="<?= htmlspecialchars($item['name']) ?>"
                                                data-food-price="<?= $item['price'] ?>"
                                                data-image-path="<?= $imageSrc ?>"
                                                class="add-to-cart-btn btn btn-accent w-full mt-auto">
                                            <i class="fas fa-plus-circle mr-2"></i>Add to Cart
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-gray-500 py-10">
                            <i class="fas fa-box-open text-5xl mb-3"></i>
                            <p class="text-lg">No items currently assigned to the <?= htmlspecialchars($current_menu_type) ?> menu.</p>
                            <p class="text-sm mt-2">Please check back later or contact admin.</p>
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; // End $display_time_based_section ?>

                <!-- All Available Food Items Section -->
                <?php if ($db_connection_error): ?>
                    <div class="col-span-full bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        <strong class="font-bold">Connection Error!</strong> Could not connect to the database.
                    </div>
                <?php else: ?>
                    <div>
                        <h2 class="text-2xl section-title mb-5 flex items-center">
                            <i class="fas fa-hamburger text-2xl mr-3 text-accent-color"></i>All Available Food Items
                        </h2>
                        <div id="foodItemsContainer" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            <!-- Items will be loaded here by JavaScript -->
                            <div class="col-span-full text-center text-gray-500 py-12"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Loading All Items...</p></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-1 cart-panel p-6 h-fit sticky top-24">
                <h2 class="text-xl section-title mb-5 flex items-center"><i class="fas fa-shopping-cart text-xl mr-3 text-accent-color"></i>Your Cart</h2>
                <div id="cartItemsList" class="overflow-y-auto max-h-[calc(100vh-400px)] pr-1 space-y-2.5 mb-5">
                    <div class="text-center py-10"><i class="fas fa-shopping-bag text-5xl text-gray-300 mb-3"></i><p class="text-gray-500">Your cart is empty.</p></div>
                </div>
                <div class="border-t border-gray-200 pt-5">
                    <div class="flex justify-between items-center text-xl font-bold mb-5">
                        <span class="section-title">Total:</span>
                        <span id="cartTotal" class="text-2xl">Rs. 0.00</span>
                    </div>
                    <div class="space-y-3">
                        <button id="payOrderBtn" class="w-full btn btn-success-custom py-3 text-base" disabled><i class="fas fa-credit-card mr-2"></i>Pay Now</button>
                        <button id="clearCartBtn" class="w-full btn btn-danger-custom py-3 text-base" disabled><i class="fas fa-trash-alt mr-2"></i>Clear Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Multi-Step Payment Modal -->
    <div id="paymentFlowModal" class="modal hidden">
        <div class="modal-content">
            <span class="close-button" id="closePaymentModalBtn">&times;</span>
            <!-- Step 1: Scan NFC Card -->
            <div id="step1_scanNfc">
                <div class="text-center mb-5">
                    <i class="fas fa-id-card text-4xl text-accent-color mb-3"></i>
                    <h2 class="text-2xl modal-title">Scan Card</h2>
                    <p class="text-gray-600">Please tap the NFC card on the reader to proceed.</p>
                </div>
                <!-- Manual input removed, replaced by scan button and loader -->
                <button id="nfcScanProceedBtn" class="w-full btn btn-accent py-3 flex items-center justify-center space-x-2">
                    <i class="fas fa-wifi"></i> <span>Scan NFC Card</span>
                </button>
                <div id="nfcScanLoading" class="hidden text-center mt-3 flex items-center justify-center text-blue-600">
                    <div class="nfc-loader"></div> <span>Scanning...</span>
                </div>
                <p id="step1_message" class="text-center mt-3 text-sm font-medium text-red-600"></p>
            </div>
            <!-- Step 2: Confirm Details (existing) -->
            <div id="step2_confirmDetails" class="hidden">
                <div class="text-center mb-5">
                    <i class="fas fa-user-check text-4xl text-green-500 mb-3"></i>
                    <h2 class="text-2xl modal-title">Confirm Order</h2>
                    <p class="text-gray-600">Review the order details below.</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border mb-4">
                    <div class="flex justify-between font-semibold mb-3"><span>Student Name:</span><span id="paymentStudentName"></span></div>
                    <div class="flex justify-between font-semibold text-green-600"><span>Card Balance:</span><span id="paymentCurrentBalance"></span></div>
                    <div class="flex justify-between font-semibold text-gray-700 text-sm mt-2"><span>NFC ID:</span><span id="paymentNfcId"></span></div>
                </div>
                <div id="paymentBillDetails" class="mb-4"></div>
                <div class="flex justify-between text-xl font-bold border-t pt-4"><span>Total Bill:</span><span id="paymentTotalBill" class="text-red-600"></span></div>
                <div class="flex space-x-3 mt-6">
                    <button id="confirmCancelBtn" class="w-full btn btn-danger-custom py-3"><i class="fas fa-times mr-2"></i>Cancel</button>
                    <button id="confirmProceedBtn" class="w-full btn btn-success-custom py-3"><i class="fas fa-arrow-right mr-2"></i>Proceed to PIN</button>
                </div>
                <p id="step2_message" class="text-center mt-3 text-sm font-medium text-red-600"></p>
            </div>
            <!-- Step 3: Enter PIN (existing) -->
            <div id="step3_enterPin" class="hidden">
                <div class="text-center mb-5">
                    <i class="fas fa-keypad text-4xl text-accent-color mb-3"></i>
                    <h2 class="text-2xl modal-title">Enter PIN</h2>
                    <p class="text-gray-600">Enter your 4-digit PIN to finalize payment.</p>
                </div>
                <input type="password" id="paymentPinInput" readonly class="w-full bg-gray-100 text-center text-3xl tracking-[1em] border-2 rounded-lg py-2" maxlength="4" placeholder="">
                <div id="numpad" class="grid grid-cols-3 gap-3 my-4">
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">1</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">2</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">3</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">4</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">5</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">6</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">7</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">8</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">9</button>
                    <button id="numpad-clear" class="numpad-btn btn btn-outline-danger border-gray-300 hover:bg-red-50 text-sm font-bold rounded-lg">CLEAR</button>
                    <button class="numpad-btn btn btn-outline border-gray-300 hover:bg-gray-100 py-3 text-xl rounded-lg">0</button>
                    <button id="numpad-backspace" class="numpad-btn btn btn-outline-danger border-gray-300 hover:bg-red-50 rounded-lg"><i class="fas fa-backspace text-xl"></i></button>
                </div>
                <button id="finalPayBtn" class="w-full btn btn-success-custom py-3"><i class="fas fa-check-circle mr-2"></i>Confirm & Pay</button>
                <p id="step3_message" class="text-center mt-3 text-sm font-medium text-red-600"></p>
            </div>
        </div>
    </div>

    <footer class="site-footer p-5 text-center mt-auto bg-white border-t">
        <div class="container mx-auto text-sm">&copy; <?= date('Y') ?> Smart Canteen. All rights reserved.</div>
    </footer>

    <!-- Pass excluded food IDs to JavaScript -->
    <script>
        const excludedFoodIds = <?= json_encode($all_time_based_food_ids); ?>;
        const currentMenuType = <?= json_encode($current_menu_type); ?>;
        const hardcodedMenuHours = <?= json_encode($actual_menu_hours); ?>; // Now uses actual fetched/default settings
    </script>
    <script src="./js/script.js"></script>
</body>
</html>
