<?php
// includes/admin_header.php - Admin Dashboard Header

// Ensure session is started before accessing session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get admin username for display
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
?>
<header class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
    <div class="flex items-center space-x-6">
      <h1 class="text-2xl font-bold text-gray-900"><a href="dashboard.php" class="text-gray-900 hover:text-blue-600 transition-colors">Admin Dashboard</a></h1>

      <nav class="hidden md:flex items-center">
        <a href="../admin/sales.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Sales</a>
        <a href="../admin/transaction.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Transactions</a>
        <a href="../admin/product.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Products</a>
        <a href="../admin/manage_students.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Manage Student</a>
        <a href="../admin/activity.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Activity Log</a>
        <a href="../admin/staff.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Staff</a>
        <a href="../admin/time-menu.php" class="text-gray-600 hover:text-blue-600 transition-colors font-semibold px-4 py-2">Time-Menu</a>
      </nav>
    </div>

    <div class="flex items-center">
      <div class="relative ml-3 dropdown">
        <div>
          <button type="button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
            <span class="sr-only">Open user menu</span>
            <span class="h-8 w-8 rounded-full bg-blue-200 flex items-center justify-center text-blue-800 font-semibold text-sm">
                <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
            </span>
            <span class="hidden md:block ml-2 text-gray-700 font-medium"><?php echo htmlspecialchars($admin_username); ?></span>
            <svg class="w-5 h-5 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </button>
        </div>

        <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none dropdown-menu hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
          <a href="../admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-0">Your Profile</a>
          <a href="../admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-1">Settings</a>
          <a href="../admin/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-2">Sign out</a>
        </div>
      </div>

      <div class="-mr-2 flex items-center md:hidden">
        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
          <span class="sr-only">Open main menu</span>
          <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      <a href="../admin/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>
      <a href="../admin/sales.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Sales</a>
      <a href="../admin/transaction.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Transactions</a>
      <a href="../admin/product.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Products</a>
      <a href="../admin/manage_students.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Manage Students</a>
      <a href="../admin/activity.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Activity Log</a>
      <a href="../admin/staff.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Staff</a>
      <a href="../admin/time-menu.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Time-Menu</a>
      <a href="../admin/profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Profile</a>
      <a href="../admin/settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Settings</a>
      <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-gray-100">Sign Out</a>
    </div>
  </div>
</header>

<script>
  // Toggle mobile menu
  document.getElementById('mobile-menu-button').addEventListener('click', function () {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function (event) {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
      if (!dropdown.contains(event.target)) {
        dropdown.querySelector('.dropdown-menu').classList.add('hidden');
      }
    });
  });

  // Toggle dropdown menu
  document.getElementById('user-menu-button').addEventListener('click', function (event) {
    event.stopPropagation(); // Prevent document click from immediately closing it
    const dropdownMenu = this.closest('.dropdown').querySelector('.dropdown-menu');
    dropdownMenu.classList.toggle('hidden');
  });
</script>
