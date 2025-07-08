<?php
// admin/Workspace_products.php - Handles product data operations (Fetch, Add, Update, Toggle Availability)

session_start();
header('Content-Type: application/json');

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

require_once '../includes/db_connection.php';

if ($link === false) {
    error_log('DB Error (Workspace_products.php): Could not connect to database: ' . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check server logs.']);
    exit();
}

$response = ['success' => false, 'message' => 'An error occurred.'];

// Determine the action based on the request method and 'action' parameter
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to get from GET or POST

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // --- FETCH ALL PRODUCTS (with pagination) ---
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]]);
        $offset = ($page - 1) * $limit;

        // Count total records first
        $sqlTotal = "SELECT COUNT(*) AS total FROM food";
        $stmtTotal = mysqli_prepare($link, $sqlTotal);
        mysqli_stmt_execute($stmtTotal);
        $resultTotal = mysqli_stmt_get_result($stmtTotal);
        $total_records = mysqli_fetch_assoc($resultTotal)['total'];
        mysqli_stmt_close($stmtTotal);

        // Fetch products with pagination
        $sql = "SELECT food_id, name, description, price, category, image_path, is_available FROM food ORDER BY food_id DESC LIMIT ? OFFSET ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $products = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $products[] = $row;
                }
                $response['success'] = true;
                $response['message'] = 'Products fetched successfully.';
                $response['products'] = $products;
                $response['total_records'] = $total_records;
                $response['current_page'] = $page;
                $response['total_pages'] = ceil($total_records / $limit);
                mysqli_free_result($result);
            } else {
                $response['message'] = 'Database error executing product fetch.';
                error_log('DB Error (Workspace_products.php): execute fetch: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error preparing product fetch.';
            error_log('DB Error (Workspace_products.php): prepare fetch: ' . mysqli_error($link));
        }
        break;

    case 'POST':
        // Handle different POST actions
        switch ($action) {
            case 'add':
                // --- ADD NEW PRODUCT ---
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $category = trim($_POST['category'] ?? '');
                $image_path = trim($_POST['image_path'] ?? '');
                $is_available = filter_var($_POST['is_available'] ?? 1, FILTER_VALIDATE_INT);

                if (empty($name) || $price === false || empty($category)) {
                    $response['message'] = 'Missing required fields for adding product.';
                    break;
                }

                $sql = "INSERT INTO food (name, description, price, category, image_path, is_available) VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssdssi", $name, $description, $price, $category, $image_path, $is_available);
                    if (mysqli_stmt_execute($stmt)) {
                        $response['success'] = true;
                        $response['message'] = 'Product added successfully!';
                    } else {
                        $response['message'] = 'Failed to add product: ' . mysqli_stmt_error($stmt);
                        error_log('DB Error (Workspace_products.php - add): ' . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $response['message'] = 'Database error preparing add product query.';
                    error_log('DB Error (Workspace_products.php - add): ' . mysqli_error($link));
                }
                break;

            case 'update':
                // --- UPDATE EXISTING PRODUCT ---
                $food_id = filter_var($_POST['food_id'] ?? 0, FILTER_VALIDATE_INT);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $category = trim($_POST['category'] ?? '');
                $image_path = trim($_POST['image_path'] ?? '');
                $is_available = filter_var($_POST['is_available'] ?? 1, FILTER_VALIDATE_INT);

                if ($food_id === false || empty($name) || $price === false || empty($category)) {
                    $response['message'] = 'Missing required fields for updating product.';
                    break;
                }

                $sql = "UPDATE food SET name = ?, description = ?, price = ?, category = ?, image_path = ?, is_available = ? WHERE food_id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ssdssii", $name, $description, $price, $category, $image_path, $is_available, $food_id);
                    if (mysqli_stmt_execute($stmt)) {
                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            $response['success'] = true;
                            $response['message'] = 'Product updated successfully!';
                        } else {
                            $response['message'] = 'Product not found or no changes made.';
                        }
                    } else {
                        $response['message'] = 'Failed to update product: ' . mysqli_stmt_error($stmt);
                        error_log('DB Error (Workspace_products.php - update): ' . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $response['message'] = 'Database error preparing update product query.';
                    error_log('DB Error (Workspace_products.php - update): ' . mysqli_error($link));
                }
                break;

            case 'activate':
            case 'deactivate':
                // --- TOGGLE PRODUCT AVAILABILITY ---
                // Data comes as JSON for toggle from frontend
                $input = json_decode(file_get_contents('php://input'), true);
                $food_id = filter_var($input['food_id'] ?? 0, FILTER_VALIDATE_INT);
                $new_status = ($action === 'activate') ? 1 : 0;

                if ($food_id === false) {
                    $response['message'] = 'Invalid product ID for status toggle.';
                    break;
                }

                $sql = "UPDATE food SET is_available = ? WHERE food_id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $new_status, $food_id);
                    if (mysqli_stmt_execute($stmt)) {
                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            $response['success'] = true;
                            $response['message'] = 'Product availability updated successfully!';
                        } else {
                            $response['message'] = 'Product not found or status already set.';
                        }
                    } else {
                        $response['message'] = 'Failed to update product availability: ' . mysqli_stmt_error($stmt);
                        error_log('DB Error (Workspace_products.php - toggle): ' . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $response['message'] = 'Database error preparing toggle availability query.';
                    error_log('DB Error (Workspace_products.php - toggle): ' . mysqli_error($link));
                }
                break;

            default:
                $response['message'] = 'Unknown POST action.';
                break;
        }
        break;

    default:
        $response['message'] = 'Unsupported request method.';
        break;
}

mysqli_close($link);
echo json_encode($response);
?>
