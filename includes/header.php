<?php
// includes/header.php - Generic Header for Student Pages

// This file is intended to be included at the top of other PHP pages.
// It assumes Tailwind CSS is already linked in the including page's <head>.

// You can add session checks or dynamic content here if needed for the header.
// For example, to display student name if logged in:
// session_start(); // Only if not already started in the including file
// $student_name = $_SESSION['current_student_info']['student_name'] ?? 'Guest';

?>
<header class="bg-white text-blue-600 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-3xl font-bold">Smart Canteen</h1>
        <nav>
            <a href="../index.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Home</a>
            <a href="order_history.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Order History</a>
            <a href="profile.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Profile</a>
            <?php if (isset($_SESSION['current_student_info'])): ?>
                <a href="../api/logout.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-200 font-semibold">Logout</a>
            <?php else: ?>
                <?php endif; ?>
        </nav>
    </div>
</header>
