// scps1/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements ---
    const foodItemsContainer = document.getElementById('foodItemsContainer');
    const cartItemsList = document.getElementById('cartItemsList');
    const cartTotalSpan = document.getElementById('cartTotal');
    const payOrderBtn = document.getElementById('payOrderBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const confirmationMessageBox = document.getElementById('confirmationMessageBox');

    // --- Payment Modal Elements ---
    const paymentFlowModal = document.getElementById('paymentFlowModal');
    const closePaymentModalBtn = document.getElementById('closePaymentModalBtn');

    // Step 1: Scan NFC Card
    const step1Div = document.getElementById('step1_scanNfc');
    // Removed nfcIdInput as it's no longer manual
    const nfcScanProceedBtn = document.getElementById('nfcScanProceedBtn'); // This now triggers the scan
    const nfcScanLoading = document.getElementById('nfcScanLoading'); // Loading spinner/message
    const step1Message = document.getElementById('step1_message');

    // Step 2: Confirm Details
    const step2Div = document.getElementById('step2_confirmDetails');
    const studentNameSpan = document.getElementById('paymentStudentName');
    const currentBalanceSpan = document.getElementById('paymentCurrentBalance');
    const paymentNfcIdSpan = document.getElementById('paymentNfcId'); // To display scanned NFC ID
    const paymentBillDetails = document.getElementById('paymentBillDetails');
    const totalBillSpan = document.getElementById('paymentTotalBill');
    const confirmProceedBtn = document.getElementById('confirmProceedBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const step2Message = document.getElementById('step2_message'); // Added for balance check message

    // Step 3 (PIN)
    const step3Div = document.getElementById('step3_enterPin');
    const pinInput = document.getElementById('paymentPinInput');
    const finalPayBtn = document.getElementById('finalPayBtn');
    const step3Message = document.getElementById('step3_message');

    // Numpad Elements
    const numpadButtons = document.querySelectorAll('#numpad .numpad-btn:not(#numpad-clear):not(#numpad-backspace)');
    const numpadClearBtn = document.getElementById('numpad-clear');
    const numpadBackspaceBtn = document.getElementById('numpad-backspace');

    // --- Global State ---
    let cart = []; // This will be kept in sync with the server session
    let paymentData = {}; // Stores nfcId, studentName, balance, bill, etc.

    // --- Utility Functions ---
    function showConfirmation(message, type) {
        confirmationMessageBox.classList.remove('bg-green-500', 'bg-red-500', 'bg-blue-500', 'hidden');
        confirmationMessageBox.textContent = message;

        if (type === 'success') {
            confirmationMessageBox.classList.add('bg-green-500');
        } else if (type === 'error') {
            confirmationMessageBox.classList.add('bg-red-500');
        } else { // Default or info
            confirmationMessageBox.classList.add('bg-blue-500');
        }

        confirmationMessageBox.classList.add('fixed', 'bottom-5', 'left-1/2', '-translate-x-1/2', 'p-3', 'rounded-lg', 'font-semibold', 'text-white', 'z-50', 'shadow-lg');

        // Add a small animation for better UX
        confirmationMessageBox.style.opacity = '0';
        confirmationMessageBox.style.transform = 'translate(-50%, 20px)';
        setTimeout(() => {
            confirmationMessageBox.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            confirmationMessageBox.style.opacity = '1';
            confirmationMessageBox.style.transform = 'translate(-50%, 0)';
        }, 10); // Small delay to ensure transition applies

        setTimeout(() => {
            confirmationMessageBox.style.opacity = '0';
            confirmationMessageBox.style.transform = 'translate(-50%, 20px)';
            setTimeout(() => {
                confirmationMessageBox.classList.add('hidden');
                confirmationMessageBox.style.transition = ''; // Reset transition
            }, 300); // Match CSS transition duration
        }, 3000); // Display duration
    }

    // --- Core Application Logic ---

    // Fetches and displays all food items from the server (excluding time-based ones)
    async function fetchAndDisplayFoodItems() {
        foodItemsContainer.innerHTML = '<div class="col-span-full text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p>Loading All Items...</p></div>';
        try {
            // `excludedFoodIds` is a global JS variable populated by PHP
            const idsToExclude = typeof excludedFoodIds !== 'undefined' ? excludedFoodIds : [];
            
            const response = await fetch('./index.php', { // Changed endpoint to index.php
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all_food_items', excluded_food_ids: idsToExclude }) // Added action and changed body format
            });

            const data = await response.json();
            if (data.status === 'success') { // Check for status key
                foodItemsContainer.innerHTML = ''; // Clear loading message
                if (data.food_items.length === 0) {
                    foodItemsContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">No other items available at this time.</p>';
                    return;
                }
                data.food_items.forEach(item => {
                    const categoryClass = item.category === 'Veg' ? 'bg-green-500' : 'bg-red-500';
                    // Image path needs to be relative to index.php (scps1/)
                    const imageSrc = item.image_path ? `./${item.image_path}` : 'https://placehold.co/300x200/E0E0E0/4A4A4A?text=No+Image';
                    const foodCard = `
                        <div class="food-card flex flex-col">
                            <div class="relative">
                                <span class="category-label ${categoryClass}">${item.category}</span>
                                <img src="${imageSrc}" onerror="this.src='https://placehold.co/300x200/E0E0E0/4A4A4A?text=Error'" alt="${item.name}">
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-xl font-semibold mb-2">${item.name}</h3>
                                <p class="text-gray-600 text-sm mb-4 flex-grow">${item.description || ''}</p>
                                <p class="text-2xl font-bold text-blue-600 mb-4">Rs. ${parseFloat(item.price).toFixed(2)}</p>
                                <button data-food-id="${item.food_id}"
                                        data-food-name="${item.name}"
                                        data-food-price="${item.price}"
                                        data-image-path="${imageSrc}"
                                        class="add-to-cart-btn btn btn-accent w-full mt-auto">
                                    <i class="fas fa-plus-circle mr-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>`;
                    foodItemsContainer.insertAdjacentHTML('beforeend', foodCard);
                });
            } else {
                foodItemsContainer.innerHTML = `<p class="col-span-full text-red-500 text-center">${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error fetching all food items:', error);
            foodItemsContainer.innerHTML = '<p class="col-span-full text-red-500 text-center">Failed to load all items.</p>';
        }
    }

    // This function handles adding items to the cart (delegated from document.body)
    document.body.addEventListener('click', async (event) => {
        const button = event.target.closest('.add-to-cart-btn');
        if (button) {
            const foodId = button.dataset.foodId;
            const foodName = button.dataset.foodName;
            const foodPrice = button.dataset.foodPrice;
            const imagePath = button.dataset.imagePath; // Capture image path

            try {
                const response = await fetch('./api/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ food_id: foodId, food_name: foodName, price: foodPrice, quantity: 1, image_path: imagePath }) // Pass image_path
                });
                const result = await response.json();
                if (result.success) {
                    showConfirmation(`${foodName} added to cart!`, 'success');
                    fetchAndDisplayCartItems();
                } else {
                    showConfirmation(result.message, 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showConfirmation('Failed to add item.', 'error');
            }
        }
    });

    // Fetches and displays cart items from session
    async function fetchAndDisplayCartItems() {
        try {
            const response = await fetch('./api/get_cart_items.php');
            const data = await response.json();
            if (data.success) {
                cart = data.cart;
                cartItemsList.innerHTML = '';
                let total = 0;

                if (cart.length === 0) {
                    cartItemsList.innerHTML = '<div class="text-center py-10"><i class="fas fa-shopping-bag text-5xl text-gray-300 mb-3"></i><p class="text-gray-500">Your cart is empty.</p></div>';
                    payOrderBtn.disabled = true;
                    clearCartBtn.disabled = true;
                } else {
                    cart.forEach(item => {
                        total += parseFloat(item.price) * parseInt(item.quantity);
                        cartItemsList.innerHTML += `
                            <div class="flex items-center p-2 rounded-lg bg-gray-50">
                                <div class="flex-grow">
                                    <h4 class="font-semibold">${item.food_name}</h4>
                                    <p class="text-sm text-gray-600">Rs. ${parseFloat(item.price).toFixed(2)}</p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <button class="update-quantity-btn text-blue-500 font-bold" data-food-id="${item.food_id}" data-action="decrease">-</button>
                                    <span>${item.quantity}</span>
                                    <button class="update-quantity-btn text-blue-500 font-bold" data-food-id="${item.food_id}" data-action="increase">+</button>
                                    <button class="remove-from-cart-btn text-red-500" data-food-id="${item.food_id}"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>`;
                    });
                    payOrderBtn.disabled = false;
                    clearCartBtn.disabled = false;
                }
                cartTotalSpan.textContent = `Rs. ${total.toFixed(2)}`;
                attachCartItemListeners(); // Re-attach listeners after updating DOM
            } else {
                console.error('Failed to fetch cart items:', data.message);
                cartItemsList.innerHTML = '<p class="text-red-500 text-center">Error loading cart.</p>';
            }
        } catch (error) {
            console.error('Error fetching cart items:', error);
            cartItemsList.innerHTML = '<p class="text-red-500 text-center">Error loading cart.</p>';
        }
    }

    // Handles updates and removals from cart
    function attachCartItemListeners() {
        document.querySelectorAll('.update-quantity-btn, .remove-from-cart-btn').forEach(button => {
            button.onclick = async function() {
                const foodId = this.dataset.foodId;
                const isUpdate = this.classList.contains('update-quantity-btn');
                const url = isUpdate ? './api/update_cart_quantity.php' : './api/remove_from_cart.php';
                const body = isUpdate ? { food_id: foodId, action: this.dataset.action } : { food_id: foodId };

                try {
                    const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                    const result = await response.json();
                    if (result.success) {
                        fetchAndDisplayCartItems();
                        showConfirmation('Cart updated.', 'info');
                    } else {
                        showConfirmation(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error updating cart:', error);
                    showConfirmation('Failed to update cart.', 'error');
                }
            };
        });
    }

    // Clears the entire cart
    clearCartBtn.addEventListener('click', async () => {
        try {
            await fetch('./api/clear_cart.php', { method: 'POST' });
            showConfirmation('Cart cleared!', 'info');
            fetchAndDisplayCartItems();
        } catch (error) {
            console.error('Error clearing cart:', error);
            showConfirmation('Failed to clear cart.', 'error');
        }
    });

    // --- Payment Flow Logic ---

    // Resets modal to initial state (Step 1)
    function resetPaymentModal() {
        step1Div.classList.remove('hidden');
        step2Div.classList.add('hidden');
        step3Div.classList.add('hidden');

        // Reset elements within Step 1
        nfcScanProceedBtn.disabled = false;
        nfcScanLoading.classList.add('hidden');
        step1Message.textContent = '';

        // Reset elements within Step 3
        pinInput.value = '';
        finalPayBtn.disabled = false; // Enable for next attempt
        step3Message.textContent = '';

        paymentData = {}; // Clear previous payment data
    }

    // Opens the payment modal
    payOrderBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            showConfirmation('Cart is empty. Please add items before paying.', 'info');
            return;
        }
        resetPaymentModal();
        paymentFlowModal.classList.remove('hidden');
        // No focus on nfcIdInput as it's now auto-scanned
    });

    // Closes the payment modal
    function closePaymentModal() {
        paymentFlowModal.classList.add('hidden');
    }
    closePaymentModalBtn.addEventListener('click', closePaymentModal);
    confirmCancelBtn.addEventListener('click', closePaymentModal);

    // --- Step 1: Scan NFC Card Logic ---
    nfcScanProceedBtn.addEventListener('click', async () => {
        nfcScanProceedBtn.disabled = true;
        nfcScanLoading.classList.remove('hidden'); // Show loading spinner
        step1Message.textContent = ''; // Clear previous messages

        try {
            // Send AJAX request to index.php for NFC scan
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Use form-urlencoded for simple action
                body: 'action=scan_nfc_card_auto' // Request the new action in PHP
            });
            const result = await response.json();

            if (result.status === 'success') {
                // Store fetched data globally
                paymentData = {
                    nfcId: result.nfc_id,
                    studentName: result.student_name,
                    balance: parseFloat(result.current_balance),
                    bill: parseFloat(cartTotalSpan.textContent.replace('Rs. ', '')) // Get current total from cart
                };

                // Populate Step 2 details
                studentNameSpan.textContent = paymentData.studentName;
                currentBalanceSpan.textContent = `Rs. ${paymentData.balance.toFixed(2)}`;
                paymentNfcIdSpan.textContent = paymentData.nfcId; // Display the scanned NFC ID
                totalBillSpan.textContent = `Rs. ${paymentData.bill.toFixed(2)}`;

                // Populate payment bill details with cart items
                paymentBillDetails.innerHTML = '';
                cart.forEach(item => {
                    paymentBillDetails.innerHTML += `
                        <div class="flex justify-between text-gray-700 text-sm mb-1">
                            <span>${item.food_name} (x${item.quantity})</span>
                            <span>Rs. ${(item.quantity * item.price).toFixed(2)}</span>
                        </div>`;
                });

                // Check for insufficient balance immediately
                const totalBillAmount = parseFloat(cartTotalSpan.textContent.replace('Rs. ', '')); // Recalculate based on current cart
                if (parseFloat(result.current_balance) < totalBillAmount) { // Use result.current_balance directly from server
                    confirmProceedBtn.disabled = true;
                    step2Message.textContent = `Insufficient balance: Rs. ${parseFloat(result.current_balance).toFixed(2)}. Needed: Rs. ${totalBillAmount.toFixed(2)}`;
                    step2Message.classList.add('text-red-600');
                    showConfirmation('Insufficient balance!', 'error');
                } else {
                    confirmProceedBtn.disabled = false;
                    step2Message.textContent = ''; // Clear any previous insufficient balance message
                    step2Message.classList.remove('text-red-600');
                }


                // Move to Step 2: Confirm Details
                step1Div.classList.add('hidden');
                step2Div.classList.remove('hidden');
                showConfirmation('NFC card scanned successfully!', 'success');

            } else {
                step1Message.textContent = result.message || 'NFC scan failed.';
                showConfirmation(result.message || 'NFC scan failed.', 'error');
            }
        } catch (e) {
            console.error('Error in NFC scan proceed:', e);
            step1Message.textContent = 'An error occurred during scan. Please check the reader.';
            showConfirmation('Critical Scan Error!', 'error');
        } finally {
            nfcScanProceedBtn.disabled = false;
            nfcScanLoading.classList.add('hidden'); // Hide loading spinner
        }
    });

    // --- Step 2: Confirm Details Logic ---
    confirmProceedBtn.addEventListener('click', () => {
        // This check is already done after NFC scan, but good to double-check
        if (paymentData.balance < paymentData.bill) {
            showConfirmation('Insufficient balance! Please top up your card.', 'error');
            // Maybe keep modal open at step 2 or close
            return;
        }
        step2Div.classList.add('hidden');
        step3Div.classList.remove('hidden');
        pinInput.focus();
    });

    // --- Numpad Logic ---
    numpadButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (pinInput.value.length < 4) {
                pinInput.value += button.textContent;
            }
        });
    });

    numpadClearBtn.addEventListener('click', () => {
        pinInput.value = '';
        finalPayBtn.disabled = true; // Disable until 4 digits are entered again
    });

    numpadBackspaceBtn.addEventListener('click', () => {
        pinInput.value = pinInput.value.slice(0, -1);
        finalPayBtn.disabled = pinInput.value.length !== 4; // Re-evaluate disabled state
    });

    // Enable/disable final pay button based on PIN length
    pinInput.addEventListener('input', () => {
        finalPayBtn.disabled = pinInput.value.length !== 4;
    });

    // --- Final Pay Logic ---
    finalPayBtn.addEventListener('click', async () => {
        console.log('--- finalPayBtn clicked ---'); // Debug log
        const pin = pinInput.value;
        console.log('PIN entered (length):', pin.length, 'PIN:', pin); // Debug log

        if (pin.length !== 4) {
            step3Message.textContent = 'PIN must be 4 digits.';
            console.error('PIN length invalid:', pin.length); // Debug log
            return;
        }
        if (!paymentData.nfcId) {
            step3Message.textContent = 'Error: NFC card data missing. Please restart payment.';
            console.error('NFC ID missing from paymentData.'); // Debug log
            return;
        }

        finalPayBtn.disabled = true;
        finalPayBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        step3Message.textContent = 'Processing payment...';
        step3Message.classList.remove('text-red-600');
        step3Message.classList.add('text-blue-600');

        console.log('Attempting to fetch process_final_payment...'); // Debug log
        console.log('Sending data:', {
            action: 'process_final_payment',
            nfc_id: paymentData.nfcId,
            pin: pin,
            cart_items: cart
        }); // Debug log

        try {
            const response = await fetch('index.php', { // Changed endpoint to index.php
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'process_final_payment',
                    nfc_id: paymentData.nfcId,
                    pin: pin,
                    cart_items: cart
                })
            });

            console.log('Fetch response received.'); // Debug log
            const result = await response.json();
            console.log('Response JSON:', result); // Debug log

            if (result.status === 'success') {
                showConfirmation(result.message, 'success');
                closePaymentModal();
                if (typeof generatePrintableCoupon === 'function') {
                    generatePrintableCoupon(result.student_name, result.transaction_id, cart);
                } else {
                    console.warn('generatePrintableCoupon function not found. Coupon will not be printed.');
                }
                // Clear the cart *after* successful payment and potential coupon generation
                await fetch('./api/clear_cart.php', { method: 'POST' }); // Ensure server-side cart is cleared
                fetchAndDisplayCartItems(); // Update UI cart
            } else {
                step3Message.textContent = result.message || 'Payment failed.'; // Default message if none provided
                step3Message.classList.add('text-red-600');
                showConfirmation(`Payment Failed: ${result.message}`, 'error');
                pinInput.value = ''; // Clear PIN on failure
                // Re-enable button if it was disabled and not a success (allows retry)
                finalPayBtn.disabled = false;
            }
        } catch (e) {
            console.error('Error processing final payment (JavaScript catch block):', e); // Debug log
            step3Message.textContent = 'A critical error occurred during payment. Check console.';
            step3Message.classList.add('text-red-600');
            showConfirmation('Payment Critical Error!', 'error');
        } finally {
            finalPayBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Confirm & Pay';
        }
    });

    // Your existing generatePrintableCoupon function
    function generatePrintableCoupon(studentName, orderId, orderedItems) {
        let itemsHtml = orderedItems.map(item => `
            <tr>
                <td style="padding: 2px 3px; text-align: left; border-bottom: 1px dashed #ccc;">${item.food_name}</td>
                <td style="padding: 2px 3px; text-align: center; border-bottom: 1px dashed #ccc;">${item.quantity}</td>
                <td style="padding: 2px 3px; text-align: right; border-bottom: 1px dashed #ccc;">Rs. ${(item.price * item.quantity).toFixed(2)}</td>
            </tr>
        `).join('');

        const total = orderedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);

        const receiptHtml = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Order Receipt #${orderId}</title>
            <style>
                @page { size: 80mm auto; margin: 3mm; }
                body { font-family: 'monospace', sans-serif; width: 100%; color: #000; font-size: 8.5pt; line-height: 1.2; }
                .header, .footer { text-align: center; }
                .header { border-bottom: 1px dashed #000; padding-bottom: 3px; margin-bottom: 8px; }
                .header h2 { margin: 0; font-size: 12pt; }
                .header p, .info p { margin: 1px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 5px; }
                th, td { padding: 2px 3px; border-bottom: 1px dashed #000; white-space: nowrap; }
                th { text-align: right; font-size: 9pt; }
                th:first-child { text-align: left; }
                table thead tr th:nth-child(1), table tbody tr td:nth-child(1) { width: 55%; }
                table thead tr th:nth-child(2), table tbody tr td:nth-child(2) { width: 15%; text-align: center; }
                table thead tr th:nth-child(3), table tbody tr td:nth-child(3) { width: 30%; text-align: right; }
                .total { font-weight: bold; text-align: right; margin-top: 8px; font-size: 10.5pt; padding-right: 3px; }
                .footer { margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>United Technical Khaja Ghar</h2>
                <p>Order Receipt</p>
            </div>
            <div class="info">
                <p><strong>Order #:</strong> ${orderId}</p>
                <p><strong>Student:</strong> ${studentName}</p>
                <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            <div class="total">Total: Rs. ${total}</div>
            <div class="footer"><p>Thank you for your order!</p></div>
        </body>
        </html>`;

        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        iframe.style.left = '-9999px';
        document.body.appendChild(iframe);

        iframe.contentDocument.open();
        iframe.contentDocument.write(receiptHtml);
        iframe.contentDocument.close();

        iframe.onload = function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => { document.body.removeChild(iframe); }, 1000);
        };
    }

    // --- Initial Load ---
    fetchAndDisplayFoodItems();
    fetchAndDisplayCartItems();
});
