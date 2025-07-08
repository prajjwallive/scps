document.addEventListener('DOMContentLoaded', function() {
    const confirmationMessageBox = document.getElementById('confirmationMessageBox');

    function showConfirmation(message, type) {
        confirmationMessageBox.textContent = message;
        confirmationMessageBox.className = `fixed top-5 right-5 p-4 rounded-lg text-white z-50 shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        confirmationMessageBox.classList.remove('hidden');
        setTimeout(() => {
            confirmationMessageBox.classList.add('hidden');
        }, 3000);
    }

    function renderMenuItem(item, menuType) {
        // Image path for items added dynamically by JS
        // This path is relative to the `index.php` (or `time-menu.php`) which loads this JS.
        // Assuming images are in scps1/uploads/food_images/
        // From admin/time-menu.php (which loads this JS), the path would be ../uploads/...
        const imageSrc = item.image_path ? `../${item.image_path}` : '../uploads/no-image.png';
        return `
            <div class="menu-item" data-food-id="${item.food_id}">
                <div class="flex items-center">
                    <img src="${imageSrc}" alt="${item.name}" class="w-10 h-10 rounded-full mr-3 object-cover">
                    <span class="font-semibold text-gray-700">${item.name}</span>
                </div>
                <button class="remove-item-btn text-red-500 hover:text-red-700" data-menu-type="${menuType}" data-food-id="${item.food_id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    }

    function loadInitialMenus() {
        attachRemoveListeners();
    }
    
    // Event listener for adding items
    document.querySelectorAll('.add-item-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const select = this.querySelector('select');
            const food_id = select.value;
            const menuType = this.closest('.menu-card').querySelector('[data-menu-type]').dataset.menuType;

            if (!food_id) {
                showConfirmation('Please select a food item.', 'error');
                return;
            }

            try {
                // Path from admin/time-menu.php (which loads time-menu.js) to admin/api/manage_time_menu.php is api/
                const response = await fetch('api/manage_time_menu.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', menu_type: menuType, food_id: food_id })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('Add Item API Response:', result);

                if (result.success) {
                    const menuContainer = this.closest('.menu-card').querySelector('[data-menu-type]');
                    menuContainer.insertAdjacentHTML('beforeend', renderMenuItem(result.item, menuType));
                    showConfirmation('Item added successfully!', 'success');
                    select.value = '';
                    attachRemoveListeners();
                } else {
                    showConfirmation(result.message, 'error');
                }
            } catch (error) {
                console.error('Error adding item:', error);
                showConfirmation('An error occurred while adding the item.', 'error');
            }
        });
    });

    // Function to attach/re-attach remove listeners
    function attachRemoveListeners() {
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            if (button.dataset.listenerAttached) return;
            button.dataset.listenerAttached = true;

            button.addEventListener('click', async function() {
                const food_id = this.dataset.foodId;
                const menuType = this.dataset.menuType;

                if (!confirm(`Are you sure you want to remove this item from the ${menuType} menu?`)) {
                    return;
                }
                
                try {
                    // Path from admin/time-menu.php (which loads time-menu.js) to admin/api/manage_time_menu.php is api/
                    const response = await fetch('api/manage_time_menu.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'remove', menu_type: menuType, food_id: food_id })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('Remove Item API Response:', result);

                    if (result.success) {
                        this.closest('.menu-item').remove();
                        showConfirmation('Item removed successfully.', 'success');
                    } else {
                        showConfirmation(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error removing item:', error);
                    showConfirmation('An error occurred while removing the item.', 'error');
                }
            });
        });
    }

    // --- Time Settings Form Submission ---
    const timeSettingsForm = document.getElementById('timeSettingsForm');
    if (timeSettingsForm) {
        timeSettingsForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const settings = {};
            // Iterate over formData to collect values directly (type="time" inputs will give HH:MM strings)
            for (let [key, value] of formData.entries()) {
                settings[key] = value; // Do not parseInt here, send as HH:MM string
            }

            try {
                const response = await fetch('api/manage_menu_time_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(settings)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('Save Time Settings API Response:', result);

                if (result.success) {
                    showConfirmation('Time settings saved successfully!', 'success');
                    // No need to reload, the new values are reflected in the form inputs
                } else {
                    showConfirmation(result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving time settings:', error);
                showConfirmation('An error occurred while saving time settings.', 'error');
            }
        });
    }


    // Initial load and attach listeners to any items rendered by PHP
    loadInitialMenus();
});
