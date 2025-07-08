<?php
date_default_timezone_set('Asia/Kathmandu');
session_start();

if (!isset($_SESSION['admin_id'])){
    header('Location: login.php');
    exit();
}

include '../includes/packages.php';
include '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --danger-color: #dc3545;
            --danger-hover: #c82333;
            --success-color: #28a745;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }

        /* --- Scanner Section --- */
        .scanner-container {
            text-align: center;
            padding: 2.5rem;
            background-color: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .scanner-container h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .scanner-container p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        #main-nfc-scanner {
            width: 100%;
            max-width: 400px;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
            transition: all 0.2s;
        }
        #main-nfc-scanner:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
            outline: none;
        }
        #scanner-status-message {
            margin-top: 1rem;
            font-weight: 500;
            height: 24px;
        }
        .status-success { color: var(--success-color); }
        .status-error { color: var(--danger-color); }
        
        /* --- Action Area --- */
        .action-area { transition: all 0.4s ease-in-out; }
        .action-area.hidden-visually { opacity: 0; transform: translateY(20px); max-height: 0; overflow: hidden; }
        
        .student-info-card {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 16px rgba(0, 86, 179, 0.2);
        }
        .student-info-card h2 { font-size: 1.5rem; font-weight: 600; margin: 0; }
        .student-info-card p { opacity: 0.9; margin: 0.25rem 0 0; }
        .student-info-card .balance { font-size: 1.75rem; font-weight: 700; }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
        }
        .action-btn {
            flex: 1;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            background-color: var(--bg-white);
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-btn:disabled {
            background-color: var(--bg-light);
            color: #adb5bd;
            cursor: not-allowed;
        }
        .action-btn.available {
            border-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        .action-btn.available:not(:disabled):hover {
            background-color: var(--primary-color);
            color: white;
        }
        .action-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* --- Forms --- */
        .form-container {
            padding: 2rem;
            background-color: var(--bg-white);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .form-view.hidden-visually { display: none; }
        .form-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem; color: #343a40; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .grid-col-span-2 { grid-column: span 2 / span 2; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #495057; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; }
        .form-input:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); outline: none; }
        .btn-submit { background-color: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; width: 100%; display: flex; align-items: center; justify-content: center; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .btn-danger { background-color: var(--danger-color); }
        .btn-danger:hover { background-color: var(--danger-hover); }
        .message-feedback { padding: 0.75rem 1rem; margin-bottom: 1.5rem; border-radius: 6px; text-align: center; }
        .message-feedback.hidden-visually { display: none; }
        .message-feedback.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message-feedback.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* --- Modal --- */
        .modal {
            position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
        }
        .modal.hidden-visually { display: none; }
        .modal-content {
            background-color: var(--bg-white); padding: 2rem; border-radius: 8px;
            width: 90%; max-width: 450px; text-align: center;
        }
        .modal-content h2 { font-size: 1.5rem; margin-bottom: 1rem; }
        .modal-content p { color: var(--text-muted); margin-bottom: 2rem; }
        .modal-actions { display: flex; gap: 1rem; }
        .modal-actions button { flex: 1; padding: 0.7rem; border-radius: 6px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- 1. Scanner Section -->
        <div class="scanner-container">
            <h1>Manage Students</h1>
            <p>Scan or enter an NFC card ID to begin</p>
            <input type="text" id="main-nfc-scanner" placeholder="NFC-XXXX" autofocus>
            <div id="scanner-status-message"></div>
        </div>

        <!-- 2. Main Action Area (Initially Hidden) -->
        <div id="action-area" class="action-area hidden-visually">
            <!-- Student Info Display -->
            <div id="student-info-card" class="student-info-card">
                <!-- Content generated by JS -->
            </div>

            <!-- Action Buttons -->
            <div id="action-buttons" class="action-buttons">
                <button id="action-btn-add" class="action-btn" disabled>
                    <i class="fas fa-user-plus mr-2"></i>Add Student
                </button>
                <button id="action-btn-balance" class="action-btn" disabled>
                    <i class="fas fa-wallet mr-2"></i>Manage Balance
                </button>
                <button id="action-btn-edit" class="action-btn" disabled>
                    <i class="fas fa-user-edit mr-2"></i>Edit / Delete
                </button>
            </div>

            <!-- Forms Container -->
            <div id="forms-container">
                <!-- Add Student Form -->
                <div id="add-student-view" class="form-view hidden-visually">
                    <div class="form-container">
                        <h2 class="form-title">Register New Student</h2>
                        <div id="add-student-message" class="message-feedback hidden-visually"></div>
                        <form id="addStudentForm">
                            <input type="hidden" id="add_nfc_id" name="nfc_id">
                            <div class="form-grid">
                                <div class="form-group"><label for="add_full_name">Full Name:</label><input type="text" id="add_full_name" name="full_name" class="form-input" required></div>
                                <div class="form-group"><label for="add_username">Username:</label><input type="text" id="add_username" name="username" class="form-input" required></div>
                                <div class="form-group"><label for="add_contact_number">Contact Number:</label><input type="text" id="add_contact_number" name="contact_number" class="form-input" required></div>
                                <div class="form-group"><label for="add_student_email">Student Email:</label><input type="email" id="add_student_email" name="student_email" class="form-input" required></div>
                                <div class="form-group"><label for="add_parent_email">Parent's Email :</label><input type="email" id="add_parent_email" name="parent_email" class="form-input"></div>
                                <div class="form-group"><label for="add_pin">4-Digit PIN:</label><input type="password" id="add_pin" name="pin" class="form-input" required maxlength="4" pattern="\d{4}"></div>
                            </div>
                            <button type="submit" class="btn-submit mt-4" id="submitBtnAddStudent"><span class="btn-text">Add Student</span></button>
                        </form>
                    </div>
                </div>
                
                <!-- Manage Balance Form -->
                <div id="balance-view" class="form-view hidden-visually">
                    <div class="form-container">
                        <h2 class="form-title">Update Card Balance</h2>
                        <div id="balance-message" class="message-feedback hidden-visually"></div>
                        <form id="updateBalanceForm">
                            <input type="hidden" id="balance_nfc_id" name="nfc_id">
                            <div class="form-group">
                                <label for="amount">Amount to Add/Deduct (NPR):</label>
                                <input type="number" id="amount" name="amount" class="form-input" step="100" required placeholder="e.g., 500 or -50">
                            </div>
                            <button type="submit" class="btn-submit mt-4" id="submitBtnBalance"><span class="btn-text">Update Balance</span></button>
                        </form>
                    </div>
                </div>

                <!-- Edit Student Form -->
                <div id="edit-student-view" class="form-view hidden-visually">
                    <div class="form-container">
                         <h2 class="form-title">Edit Student Information</h2>
                        <div id="edit-student-message" class="message-feedback hidden-visually"></div>
                        <form id="editStudentForm">
                            <input type="hidden" id="edit_student_id" name="student_id">
                            <div class="form-grid">
                                <div class="form-group"><label for="edit_full_name">Full Name:</label><input type="text" id="edit_full_name" name="full_name" class="form-input" required></div>
                                <div class="form-group"><label for="edit_username">Username:</label><input type="text" id="edit_username" name="username" class="form-input" required></div>
                                <div class="form-group"><label for="edit_contact_number">Contact Number:</label><input type="text" id="edit_contact_number" name="contact_number" class="form-input" required></div>
                                <div class="form-group"><label for="edit_student_email">Student Email:</label><input type="email" id="edit_student_email" name="student_email" class="form-input" required></div>
                                <div class="form-group"><label for="edit_parent_email">Parent's Email:</label><input type="email" id="edit_parent_email" name="parent_email" class="form-input"></div>
                                <div class="form-group"><label for="edit_pin">Set New 4-Digit PIN (Optional):</label><input type="password" id="edit_pin" name="pin" class="form-input" maxlength="4" placeholder="Leave blank to keep current PIN"></div>
                            </div>
                            <button type="submit" class="btn-submit mt-4" id="submitBtnEditStudent"><span class="btn-text">Update Information</span></button>
                        </form>
                        <hr class="my-8">
                        <div>
                            <h3 class="text-xl font-semibold text-red-600">Danger Zone</h3>
                            <p class="text-gray-600 my-2">Deleting a student is permanent and cannot be undone. This will remove the student, their card, and all transaction history.</p>
                            <button id="deleteStudentBtn" class="btn-submit btn-danger w-auto"><i class="fas fa-trash-alt mr-2"></i>Delete This Student</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="modal hidden-visually">
        <div class="modal-content">
            <h2 class="text-red-600">Are you sure?</h2>
            <p>This action is irreversible. You are about to permanently delete <strong id="delete-student-name"></strong> and all associated data.</p>
            <div class="modal-actions">
                <button id="cancel-delete-btn" class="bg-gray-200">Cancel</button>
                <button id="confirm-delete-btn" class="bg-red-600 text-white">Yes, Delete Student</button>
            </div>
        </div>
    </div>

    <script src="./js/manage_students.js"></script>
</body>
</html>
