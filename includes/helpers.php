<?php
// scps1/includes/helpers.php - Common helper functions

/**
 * A placeholder for any future helper functions.
 * For example, you might add functions for:
 * - Sanitizing input data
 * - Logging specific events
 * - More complex transaction processing logic
 */
function sanitize_input($data) {
    global $link; // Access the database connection if needed for real_escape_string
    if ($link) {
        return mysqli_real_escape_string($link, htmlspecialchars(strip_tags(trim($data))));
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

// Note: No closing PHP tag is intentional
