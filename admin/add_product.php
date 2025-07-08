<?php
// admin/add_product.php - Handles adding NEW or UPDATING existing products with Activity Logging

// Start the session
session_start();

// --- ADD LOGGING HERE ---
// These logs will help us see what data is received
error_log("--- add_product.php received request ---");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true)); // Log all POST data
error_log("FILES Data: " . print_r($_FILES, true)); // Log all FILES data (for image upload)
error_log("--- END RECEIVED DATA LOGS ---");
// --- END LOGGING ---


// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct and $link variable is created

// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Default response, will be updated based on logic outcome
$response = ['success' => false, 'message' => 'An error occurred during processing.'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Determine if Adding or Editing ---
    // Check if a product ID was sent (indicating an edit operation)
    $product_id = filter_input(INPUT_POST, 'productId', FILTER_VALIDATE_INT);
    $is_editing = ($product_id !== false && $product_id !== null); // True if productId is a valid integer

    // --- Log the result of the edit check ---
    error_log("Is Editing: " . ($is_editing ? "true" : "false"));
    error_log("Product ID detected: " . ($product_id ?? 'NULL'));
    error_log("--- END EDIT CHECK LOGS ---");
    // --- END LOGGING ---


    // --- Retrieve and Sanitize Input Data ---
    // These names must match the 'name' attributes in your HTML form
    // Using direct $_POST access and casting to string for string types.
    // Using filter_input for price/id validation.
    $name = isset($_POST['productName']) ? (string)$_POST['productName'] : '';
    $price = filter_input(INPUT_POST, 'productPrice', FILTER_VALIDATE_FLOAT); // VALIDATE_FLOAT returns float or false/null
    $category = isset($_POST['productCategory']) ? (string)$_POST['productCategory'] : ''; // Ensure it's treated as string
    $description = isset($_POST['productDescription']) ? (string)$_POST['productDescription'] : '';

    // For the checkbox, $_POST['productAvailable'] will be 'on' if checked, or not set if unchecked.
    // Convert to 1 or 0.
    $is_available = isset($_POST['productAvailable']) ? 1 : 0;


    // --- Log the raw and sanitized input values ---
     error_log("Input Values - Name: '" . $name . "'");
     error_log("Input Values - Price: '" . ($price ?? 'NULL') . "'"); // Log NULL for false/null
     error_log("Input Values - Category: '" . $category . "'"); // Use quotes to see if empty string
     error_log("Input Values - Description: '" . $description . "'");
     error_log("Input Values - Is Available: '" . $is_available . "'");
    error_log("--- END INPUT VALUES LOGS ---");
    // --- END LOGGING ---


    // Initialize image path variable. We will only update the image_path
    // if a new file is uploaded during an edit or if adding a new product.
    $image_path = null; // Use null initially, meaning don't change the image path by default


    // --- Handle Image File Upload ---
    // Check if a file was uploaded and there were no upload errors
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['productImage']['tmp_name'];
        $file_name = basename($_FILES['productImage']['name']);
        $upload_dir = '../images/'; // Directory to upload images (relative to admin/)

        // Ensure the upload directory exists and is writable (basic check)
         if (!is_dir($upload_dir)) {
             mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
         }
         if (!is_writable($upload_dir)) {
             $response['message'] = 'Image upload directory is not writable.';
             error_log('File Upload Error: Upload directory not writable: ' . $upload_dir);
              // Stop execution if directory is not writable, as image upload will fail
              echo json_encode($response);
              if (isset($link)) mysqli_close($link);
              exit();
         }


        // Generate a unique filename to avoid overwriting
        $new_file_name = uniqid('img_', true) . '.' . strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $dest_path_full = $upload_dir . $new_file_name; // Full server path to save the file
        $image_path_db = 'images/' . $new_file_name; // Path to store in database (relative to project root)


        // Move the file from temporary location to the destination
        if (move_uploaded_file($file_tmp_path, $dest_path_full)) {
            $image_path = $image_path_db; // Set image_path to the new path if upload is successful
             error_log("Image uploaded successfully to: " . $dest_path_full);
            // TODO: Optional - Implement logic to delete the old image file
            //       if editing and a new image was uploaded.
            //       This requires fetching the old image_path before updating.

        } else {
            // File move failed
            $response['message'] = 'Error moving uploaded image file.';
            error_log('File Upload Error: Could not move uploaded file from ' . $file_tmp_path . ' to ' . $dest_path_full . '. Error: ' . (error_get_last()['message'] ?? 'Unknown'));
             // If image move fails, proceed without updating the image path in DB.
             $image_path = null; // Ensure image_path is null so it's not updated in DB
        }
    } elseif (isset($_FILES['productImage']) && $_FILES['productImage']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., file size, partial upload)
        $php_upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        ];
        $error_message = $php_upload_errors[$_FILES['productImage']['error']] ?? 'Unknown file upload error.';
        $response['message'] = 'File upload error: ' . $error_message;
        error_log('File Upload Error (admin/add_product.php): Code ' . $_FILES['productImage']['error'] . ' - ' . $error_message);
         // If upload has an error (but not NO_FILE), proceed without updating the image path.
         $image_path = null; // Ensure image_path is null
    }
     // If UPLOAD_ERR_NO_FILE (no file selected) or no $_FILES['productImage'] at all,
     // $image_path remains null, and we won't update the image_path in the database.


    // --- Basic Input Validation ---
    // Adjusted validation slightly based on potential VARCHAR behavior
    if ($name === '') { // Check for strictly empty string
        $response['message'] = 'Product name is required.';
         error_log('Validation Error: Name is empty string.');
    } elseif ($price === false || $price === null || $price < 0) {
         $response['message'] = 'Valid price is required.';
         error_log('Validation Error: Price is invalid (' . ($price ?? 'NULL') . ').');
    } elseif ($is_editing && ($product_id === false || $product_id === null)) {
         // Should not happen with filter_input check above, but defensive
         $response['message'] = 'Invalid product ID provided for editing.';
          error_log('admin/add_product.php failed validation: Invalid product ID for editing.');
    }
     else {
        // Validation passed, proceed to database operation (Insert or Update)

        if ($is_editing) {
            // --- Perform Update Operation ---
            error_log("--- Performing UPDATE operation for product ID: " . $product_id . " ---");

            // Start building the UPDATE query.
            $sql_update = "UPDATE food SET name = ?, description = ?, price = ?, category = ?, is_available = ?";
            // Initial types and parameters. Use references for parameters required by mysqli_stmt_bind_param
            // Types: s:name, s:description, d:price, s:category, i:is_available
            // Corrected types string for the initial 5 parameters
            $types = "ssdsi";

            // Pass parameters by reference
            $params = [&$name, &$description, &$price, &$category, &$is_available];

            // If a new image was uploaded, add image_path to the query and parameters
            if ($image_path !== null) { // Only update image_path if a new file was successfully uploaded
                $sql_update .= ", image_path = ?";
                $types .= "s"; // Add type 's' for image_path
                $params[] = &$image_path; // Add the new image path to parameters
                 error_log("UPDATE includes image_path update. Current types: " . $types); // Log current types string
            } else {
                 error_log("UPDATE does NOT include image_path update (image_path is null). Current types: " . $types); // Log current types string
            }

            // Add the WHERE clause to target the specific product
            $sql_update .= " WHERE food_id = ?";
            $types .= "i"; // Add type 'i' for food_id
            $params[] = &$product_id; // Add the product ID to parameters for the WHERE clause
            error_log("UPDATE includes food_id WHERE clause. Final types: " . $types); // Log final types string


            // --- Detailed Logging Before Binding ---
            error_log("DEBUG: Final UPDATE SQL: " . $sql_update);
            error_log("DEBUG: Final UPDATE Types: " . $types);
            error_log("DEBUG: Final UPDATE Params (pre-bind check): " . print_r($params, true)); // Detailed check
            error_log("DEBUG: Types string length: " . strlen($types));
            error_log("DEBUG: Params array count: " . count($params));
            // --- END Logging Before Binding ---


            if ($stmt_update = mysqli_prepare($link, $sql_update)) {

                error_log("DEBUG: After Prepare - Statement Ready.");

                // Bind parameters dynamically using call_user_func_array
                // The first argument to bind_param is the statement object itself
                // Then comes the string of types, followed by the parameters
                // Note: call_user_func_array requires parameters to be passed by reference
                // The $params array elements are already references due to '&'
                $bind_params = array_merge([$stmt_update, $types], $params);

                error_log("DEBUG: Before call_user_func_array - Bind Params Array structure: " . print_r($bind_params, true)); // Very detailed check

                // Corrected check: Ensure type string length matches parameter count in the $params array
                if (strlen($types) !== count($params)) { // Corrected this check
                     $response['message'] = 'Internal Error: Parameter count mismatch during binding preparation.';
                     error_log("FATAL ERROR: Type string length (" . strlen($types) . ") does not match parameter count (" . count($params) . ") for binding in UPDATE (Check logic constructing \$types and \$params).");
                     // Exit gracefully with error response
                     echo json_encode($response);
                     if (isset($link)) mysqli_close($link);
                     exit();
                }


                // Perform the binding
                // call_user_func_array('mysqli_stmt_bind_param', $bind_params) requires the first element to be the mysqli_stmt object by reference in some PHP versions.
                // Since $bind_params[0] is the object directly, and it's the first element, this often works.
                if (call_user_func_array('mysqli_stmt_bind_param', $bind_params)) {
                     error_log("DEBUG: Parameters bound successfully.");

                     if (mysqli_stmt_execute($stmt_update)) {
                         error_log("DEBUG: UPDATE statement executed successfully.");
                         // Check if any row was affected (updated)
                         if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                              $response['success'] = true;
                              $response['message'] = 'Product updated successfully!';
                              error_log("Product ID " . $product_id . " updated successfully. Affected Rows: " . mysqli_stmt_affected_rows($stmt_update));

                              // --- Log Product Update Activity ---
                              if (isset($link) && $link !== false) {
                                  $activity_type = 'product_updated';
                                  $description = "Admin '" . ($_SESSION['admin_username'] ?? 'N/A') . "' updated product ID " . $product_id . " ('" . mysqli_real_escape_string($link, $name) . "').";
                                  $admin_id = $_SESSION['admin_id'] ?? null;
                                  $user_id = null; // Not a user action
                                  $related_id = $product_id; // Relate to the product ID

                                  $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, user_id, related_id) VALUES (NOW(), ?, ?, ?, ?, ?)";
                                  if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                                      // Assuming admin_id, user_id, related_id are INT NULLABLE in DB
                                      mysqli_stmt_bind_param($stmt_log, "ssiii", $activity_type, $description, $admin_id, $user_id, $related_id);
                                      mysqli_stmt_execute($stmt_log); // Execute without strict error checking here to not block product update response
                                      mysqli_stmt_close($stmt_log);
                                  } else {
                                      error_log("Error preparing activity log query for product update: " . mysqli_error($link));
                                  }
                              }
                              // --- End Log Product Update Activity ---

                         } else {
                              // No row updated (could be no changes made, or ID not found)
                              // If the ID was valid and present, it means no fields were different.
                              // We can consider this a successful "save" from the user's perspective.
                              // If the ID wasn't found, mysqli_stmt_affected_rows would also be 0.
                              // To distinguish, we'd need a preceding SELECT query, but for simplicity,
                              // if $is_editing is true, and affected_rows is 0, we'll assume no changes were needed.
                              // Log a warning if affected rows is 0 but product ID seemed valid.
                              error_log("WARNING: UPDATE statement executed for product ID " . $product_id . ", but 0 rows affected. (No changes made or ID not found).");
                              $response['success'] = true;
                              $response['message'] = 'Product updated (no changes made or product not found).';

                         }

                     } else {
                         $response['message'] = 'Database error executing product update.';
                         error_log('DB Error (admin/add_product.php): execute update: ' . mysqli_stmt_error($stmt_update));
                     }

                } else {
                     $response['message'] = 'Database error binding parameters for update.';
                      error_log('DB Error (admin/add_product.php): bind param update failed: ' . mysqli_stmt_error($stmt_update)); // Use stmt_error here as it's after prepare
                }

                mysqli_stmt_close($stmt_update);

            } else {
                $response['message'] = 'Database error preparing product update.';
                error_log('DB Error (admin/add_product.php): prepare update: ' . mysqli_error($link)); // Use general error here
            }

        } else {
            // --- Perform Insert Operation (Existing Logic) ---
            error_log("--- Performing INSERT operation ---");

            // For insert, if no image uploaded, $image_path is null.
            // The DB column `image_path` might be NULLABLE or have a default like ''.
            // We'll explicitly set it to '' if null, assuming the DB expects a string.
            $image_path_insert = ($image_path !== null) ? $image_path : '';


            $sql_insert = "INSERT INTO food (name, description, price, category, image_path, is_available) VALUES (?, ?, ?, ?, ?, ?)";

            error_log("INSERT SQL: " . $sql_insert);
            error_log("INSERT Params: name='" . $name . "', desc='" . $description . "', price='" . ($price ?? 'NULL') . "', cat='" . $category . "', img='" . $image_path_insert . "', avail='" . $is_available . "'");


            if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
                // Corrected types string for INSERT:
                // s:name, s:description, d:price, s:category, s:image_path, i:is_available
                 $types_insert = "ssdsis";

                mysqli_stmt_bind_param($stmt_insert, $types_insert, $name, $description, $price, $category, $image_path_insert, $is_available);

                if (mysqli_stmt_execute($stmt_insert)) {
                    $response['success'] = true;
                    $response['message'] = 'Product added successfully!';
                    $response['new_product_id'] = mysqli_stmt_insert_id($stmt_insert);
                    error_log("Product added successfully. New ID: " . $response['new_product_id']);

                    // --- Log Product Added Activity ---
                    if (isset($link) && $link !== false) {
                        $activity_type = 'product_added';
                        $description = "Admin '" . ($_SESSION['admin_username'] ?? 'N/A') . "' added new product '" . mysqli_real_escape_string($link, $name) . "' (ID: " . $response['new_product_id'] . ").";
                        $admin_id = $_SESSION['admin_id'] ?? null;
                        $user_id = null; // Not a user action
                        $related_id = $response['new_product_id']; // Relate to the new product ID

                        $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, user_id, related_id) VALUES (NOW(), ?, ?, ?, ?, ?)";
                        if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                            // Assuming admin_id, user_id, related_id are INT NULLABLE in DB
                            mysqli_stmt_bind_param($stmt_log, "ssiii", $activity_type, $description, $admin_id, $user_id, $related_id);
                            mysqli_stmt_execute($stmt_log); // Execute without strict error checking here
                            mysqli_stmt_close($stmt_log);
                        } else {
                            error_log("Error preparing activity log query for product added: " . mysqli_error($link));
                        }
                    }
                    // --- End Log Product Added Activity ---

                } else {
                    $response['message'] = 'Database error adding product.';
                    error_log('DB Error (admin/add_product.php): execute insert: ' . mysqli_stmt_error($stmt_insert));
                }

                mysqli_stmt_close($stmt_insert);

            } else {
                $response['message'] = 'Database error preparing product insert.';
                error_log('DB Error (admin/add_product.php): prepare insert: ' . mysqli_error($link));
            }
        }
    }

} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
    error_log('admin/add_product.php received non-POST request.');
}

// Close the database connection
if (isset($link)) {
    mysqli_close($link);
}

// Send the JSON response
error_log("--- Sending Final Response: " . json_encode($response) . " ---");
echo json_encode($response);

// Note: No closing PHP tag is intentional
?>
