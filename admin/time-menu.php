<?php
// admin/time-menu.php - Page for managing time-based menus

session_start();
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) { // Changed check to admin_id for consistency
    header('Location: ../admin/login.php'); // Redirect to login if not authenticated
    exit();
}

// Path to db_connection.php:
// time-menu.php is in C:\xampp\htdocs\scps1\admin\
// db_connection.php is in C:\xampp\htdocs\scps1\includes\
// So, from admin/, go up one level (..) to scps1/, then into includes/.
require_once '../includes/db_connection.php';

// Fetch all food items to populate the selection dropdowns
$food_items_result = $link->query("SELECT food_id, name FROM food WHERE is_available = 1 ORDER BY name");
$all_food_items = [];
while ($row = $food_items_result->fetch_assoc()) {
    $all_food_items[] = $row;
}

// Function to fetch current menu items for a specific menu type (Breakfast, Lunch, Dinner)
function get_menu_items($link, $menu_type) {
    $sql = "SELECT f.food_id, f.name, f.image_path FROM time_based_menu tbm
            JOIN food f ON tbm.food_id = f.food_id
            WHERE tbm.menu_type = ?
            ORDER BY f.name";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $menu_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

// Fetch items for each menu type
$breakfast_menu = get_menu_items($link, 'Breakfast');
$lunch_menu = get_menu_items($link, 'Lunch');
$dinner_menu = get_menu_items($link, 'Dinner');

// Fetch time settings
$time_settings = [];
$sql_time_settings = "SELECT menu_type, start_hour, start_minute, end_hour, end_minute FROM menu_time_settings";
$result_time_settings = $link->query($sql_time_settings);
if ($result_time_settings) {
    while ($row = $result_time_settings->fetch_assoc()) {
        $time_settings[$row['menu_type']] = [
            'start_hour' => $row['start_hour'],
            'start_minute' => $row['start_minute'],
            'end_hour' => $row['end_hour'],
            'end_minute' => $row['end_minute']
        ];
    }
} else {
    error_log("time-menu.php: Failed to fetch time settings: " . $link->error);
}

// Default values if not found in DB or for initial display
$default_hours = [
    'Breakfast' => ['start_hour' => 6, 'start_minute' => 0, 'end_hour' => 11, 'end_minute' => 0],
    'Lunch'     => ['start_hour' => 11, 'start_minute' => 0, 'end_hour' => 16, 'end_minute' => 0],
    'Dinner'    => ['start_hour' => 16, 'start_minute' => 0, 'end_hour' => 22, 'end_minute' => 0]
];
$actual_menu_hours = array_merge($default_hours, $time_settings);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time-Based Menus</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .menu-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        /* Adjusted width for time inputs for better appearance */
        input[type="time"] {
            width: 120px; /* Adjust as needed */
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php
    // Include the admin header.
    // time-menu.php is in C:\xampp\htdocs\scps1\admin\
    // admin_header.php is in C:\xampp\htdocs\scps1\includes\
    // So, from admin/, go up one level (..) to scps1/, then into includes/.
    include '../includes/admin_header.php';
    ?>

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Time-Based Menus</h1>
        </div>

        <!-- Confirmation Message Box (initially hidden) -->
        <div id="confirmationMessageBox" class="hidden fixed top-5 right-5 p-4 rounded-lg text-white z-50"></div>

        <div class="grid grid-cols-1 gap-8 mb-8">
            <div class="menu-card p-6">
                <h2 class="text-2xl font-bold text-gray-700 mb-4">Configure Menu Time Ranges</h2>
                <form id="timeSettingsForm" class="space-y-4">
                    <?php foreach (['Breakfast', 'Lunch', 'Dinner'] as $menu_type_setting): ?>
                        <?php
                            $start_hour = $actual_menu_hours[$menu_type_setting]['start_hour'];
                            $start_minute = $actual_menu_hours[$menu_type_setting]['start_minute'];
                            $end_hour = $actual_menu_hours[$menu_type_setting]['end_hour'];
                            $end_minute = $actual_menu_hours[$menu_type_setting]['end_minute'];

                            // Format hours and minutes as HH:MM for time input value
                            $start_time_formatted = sprintf('%02d:%02d', $start_hour, $start_minute);
                            $end_time_formatted = sprintf('%02d:%02d', $end_hour, $end_minute);
                        ?>
                        <div class="flex items-center gap-4">
                            <label class="w-24 text-lg font-medium text-gray-700"><?= htmlspecialchars($menu_type_setting) ?>:</label>
                            <input type="time" name="<?= strtolower($menu_type_setting) ?>_start" 
                                class="border-gray-300 rounded-md shadow-sm text-center" 
                                value="<?= $start_time_formatted ?>" required>
                            <span class="text-gray-600">to</span>
                            <input type="time" name="<?= strtolower($menu_type_setting) ?>_end" 
                                class="border-gray-300 rounded-md shadow-sm text-center" 
                                value="<?= $end_time_formatted ?>" required>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn bg-blue-600 text-white hover:bg-blue-700 px-6 py-2 rounded-md">
                        <i class="fas fa-save mr-2"></i>Save Time Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Breakfast Menu Card -->
            <div class="menu-card" id="breakfast-menu">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-700 flex items-center"><i class="fas fa-coffee mr-3 text-yellow-600"></i>Breakfast</h2>
                </div>
                <div class="p-4 space-y-3" data-menu-type="Breakfast">
                    <?php foreach ($breakfast_menu as $item): ?>
                        <div class="menu-item" data-food-id="<?= $item['food_id'] ?>">
                            <div class="flex items-center">
                                <!-- CORRECTED IMAGE PATH: From admin/ to scps1/ then uploads/ -->
                                <img src="../<?= htmlspecialchars($item['image_path'] ?? 'uploads/no-image.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-10 h-10 rounded-full mr-3 object-cover">
                                <span class="text-gray-800 font-medium"><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                            <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 focus:outline-none" 
                                    data-menu-type="Breakfast" data-food-id="<?= $item['food_id'] ?>"> <!-- ADDED data- attributes -->
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-4 bg-gray-50 border-t rounded-b-lg">
                    <form class="add-item-form flex gap-2" data-menu-type="Breakfast">
                        <select class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Select an item --</option>
                            <?php foreach ($all_food_items as $item): ?>
                                <option value="<?= $item['food_id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md"><i class="fas fa-plus"></i></button>
                    </form>
                </div>
            </div>

            <!-- Lunch Menu Card -->
            <div class="menu-card" id="lunch-menu">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-700 flex items-center"><i class="fas fa-sun mr-3 text-orange-500"></i>Lunch</h2>
                </div>
                <div class="p-4 space-y-3" data-menu-type="Lunch">
                    <?php foreach ($lunch_menu as $item): ?>
                        <div class="menu-item" data-food-id="<?= $item['food_id'] ?>">
                            <div class="flex items-center">
                                <!-- CORRECTED IMAGE PATH -->
                                <img src="../<?= htmlspecialchars($item['image_path'] ?? 'uploads/no-image.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-10 h-10 rounded-full mr-3 object-cover">
                                <span class="text-gray-800 font-medium"><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                            <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 focus:outline-none"
                                    data-menu-type="Lunch" data-food-id="<?= $item['food_id'] ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-4 bg-gray-50 border-t rounded-b-lg">
                    <form class="add-item-form flex gap-2" data-menu-type="Lunch">
                        <select class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Select an item --</option>
                            <?php foreach ($all_food_items as $item): ?>
                                <option value="<?= $item['food_id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md"><i class="fas fa-plus"></i></button>
                    </form>
                </div>
            </div>

            <!-- Dinner Menu Card -->
            <div class="menu-card" id="dinner-menu">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-700 flex items-center"><i class="fas fa-moon mr-3 text-indigo-600"></i>Dinner</h2>
                </div>
                <div class="p-4 space-y-3" data-menu-type="Dinner">
                    <?php foreach ($dinner_menu as $item): ?>
                        <div class="menu-item" data-food-id="<?= $item['food_id'] ?>">
                            <div class="flex items-center">
                                <!-- CORRECTED IMAGE PATH -->
                                <img src="../<?= htmlspecialchars($item['image_path'] ?? 'uploads/no-image.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-10 h-10 rounded-full mr-3 object-cover">
                                <span class="text-gray-800 font-medium"><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                            <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 focus:outline-none"
                                    data-menu-type="Dinner" data-food-id="<?= $item['food_id'] ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-4 bg-gray-50 border-t rounded-b-lg">
                    <form class="add-item-form flex gap-2" data-menu-type="Dinner">
                        <select class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Select an item --</option>
                            <?php foreach ($all_food_items as $item): ?>
                                <option value="<?= $item['food_id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md"><i class="fas fa-plus"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- External JavaScript for time-menu functionality (e.g., adding/removing items via AJAX) -->
    <script src="js/time-menu.js"></script>
</body>
</html>
