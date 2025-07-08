<?php
// --- TEMPORARY PIN HASHER ---
// Use this file to get a hash for a test PIN.
// DELETE THIS FILE IMMEDIATELY AFTER USE FOR SECURITY.

// <-- *** CHANGE THIS to the 4-digit PIN you want to hash! ***
$test_pin = "1234"; 

// Validate that the PIN is a 4-digit string
if (!preg_match('/^\d{4}$/', $test_pin)) {
    die("Error: Please provide a valid 4-digit PIN.");
}

$hashed_pin = password_hash($test_pin, PASSWORD_DEFAULT);

echo "The PIN hash for '" . htmlspecialchars($test_pin) . "' is:<br>";
echo "<strong style='font-family: monospace;'>" . htmlspecialchars($hashed_pin) . "</strong><br><br>";
echo "Copy the hash and paste it into the 'pin_hash' field in your 'nfc_card' table in phpMyAdmin.";
echo "<br><br><strong style='color:red;'>REMEMBER TO DELETE THIS FILE AFTER YOU HAVE THE HASH!</strong>";
?>
