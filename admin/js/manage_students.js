document.addEventListener('DOMContentLoaded', function() {
    // --- STATE & CACHE ---
    let state = {
        nfcId: null,
        studentId: null,
        studentData: null,
        cardExists: null
    };

    // --- DOM ELEMENTS ---
    const nfcScannerInput = document.getElementById('main-nfc-scanner');
    const statusMessage = document.getElementById('scanner-status-message');
    const actionArea = document.getElementById('action-area');
    const studentInfoCard = document.getElementById('student-info-card');
    
    const addBtn = document.getElementById('action-btn-add');
    const balanceBtn = document.getElementById('action-btn-balance');
    const editBtn = document.getElementById('action-btn-edit');
    const actionButtons = [addBtn, balanceBtn, editBtn];

    const addView = document.getElementById('add-student-view');
    const balanceView = document.getElementById('balance-view');
    const editView = document.getElementById('edit-student-view');
    const formViews = [addView, balanceView, editView];

    // Forms
    const addStudentForm = document.getElementById('addStudentForm');
    const updateBalanceForm = document.getElementById('updateBalanceForm');
    const editStudentForm = document.getElementById('editStudentForm');

    // Messages
    const addMessageDiv = document.getElementById('add-student-message');
    const balanceMessageDiv = document.getElementById('balance-message');
    const editMessageDiv = document.getElementById('edit-student-message');

    // Delete functionality
    const deleteBtn = document.getElementById('deleteStudentBtn');
    const deleteModal = document.getElementById('delete-confirm-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const deleteStudentNameSpan = document.getElementById('delete-student-name');

    // --- UI UPDATE FUNCTIONS ---
    function resetUI() {
        actionArea.classList.add('hidden-visually');
        formViews.forEach(v => v.classList.add('hidden-visually'));
        actionButtons.forEach(b => {
            b.disabled = true;
            b.classList.remove('available', 'active');
        });
        statusMessage.textContent = '';
        [addMessageDiv, balanceMessageDiv, editMessageDiv].forEach(el => el.classList.add('hidden-visually'));
        addStudentForm.reset();
        updateBalanceForm.reset();
        editStudentForm.reset();
    }

    function renderUI() {
        resetUI();
        if (state.nfcId === null) return;

        if (state.cardExists) {
            // CARD FOUND
            statusMessage.textContent = 'Existing Card Detected';
            statusMessage.className = 'status-success';

            // Show student info
            studentInfoCard.innerHTML = `
                <div>
                    <h2>${state.studentData.full_name}</h2>
                    <p>Student ID: ${state.studentData.student_id}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-90">Current Balance</p>
                    <span class="balance">Rs. ${parseFloat(state.studentData.current_balance).toFixed(2)}</span>
                </div>`;
            
            // Enable relevant buttons
            balanceBtn.disabled = false;
            editBtn.disabled = false;
            balanceBtn.classList.add('available');
            editBtn.classList.add('available');
            actionArea.classList.remove('hidden-visually');
            
        } else {
            // CARD NOT FOUND
            statusMessage.textContent = 'New Card Detected. Ready to Register.';
            statusMessage.className = 'status-error';
            studentInfoCard.innerHTML = '';
            
            addBtn.disabled = false;
            addBtn.classList.add('available');
            actionArea.classList.remove('hidden-visually');
        }
    }

    function switchFormView(viewToShow) {
        actionButtons.forEach(b => b.classList.remove('active'));
        formViews.forEach(v => v.classList.add('hidden-visually'));

        if(viewToShow) {
            viewToShow.button.classList.add('active');
            viewToShow.view.classList.remove('hidden-visually');
        }
    }

    // --- API & DATA HANDLING ---
    let debounceTimeout;
    nfcScannerInput.addEventListener('input', () => {
        clearTimeout(debounceTimeout);
        const nfcIdValue = nfcScannerInput.value.trim();

        if (nfcIdValue.length < 3) {
            state.nfcId = null;
            resetUI();
            return;
        }

        statusMessage.textContent = 'Checking...';
        statusMessage.className = '';

        debounceTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`./api/get_nfc_status.php?nfc_id=${encodeURIComponent(nfcIdValue)}`);
                const data = await response.json();
                
                state.nfcId = nfcIdValue;
                state.cardExists = data.success && data.found;
                state.studentData = data.data || null;
                state.studentId = data.data ? data.data.student_id : null;
                renderUI();

            } catch (error) {
                statusMessage.textContent = 'Network error. Please try again.';
                statusMessage.className = 'status-error';
            }
        }, 500);
    });

    // --- EVENT LISTENERS FOR ACTION BUTTONS ---
    addBtn.addEventListener('click', () => {
        if (addBtn.disabled) return;
        switchFormView({ button: addBtn, view: addView });
        document.getElementById('add_nfc_id').value = state.nfcId;
    });

    balanceBtn.addEventListener('click', () => {
        if (balanceBtn.disabled) return;
        switchFormView({ button: balanceBtn, view: balanceView });
        document.getElementById('balance_nfc_id').value = state.nfcId;
    });

    editBtn.addEventListener('click', () => {
        if (editBtn.disabled) return;
        switchFormView({ button: editBtn, view: editView });
        // Populate edit form
        document.getElementById('edit_student_id').value = state.studentId;
        document.getElementById('edit_full_name').value = state.studentData.full_name;
        document.getElementById('edit_username').value = state.studentData.username;
        document.getElementById('edit_contact_number').value = state.studentData.contact_number;
        document.getElementById('edit_student_email').value = state.studentData.student_email;
        document.getElementById('edit_parent_email').value = state.studentData.parent_email;
        document.getElementById('edit_pin').value = '';
    });

    // --- FORM SUBMISSION LOGIC ---
    async function handleFormSubmit(form, url, messageDiv) {
        try {
            const response = await fetch(url, { method: 'POST', body: new FormData(form) });
            const data = await response.json();
            messageDiv.textContent = data.message;
            messageDiv.classList.toggle('success', data.success);
            messageDiv.classList.toggle('error', !data.success);
            messageDiv.classList.remove('hidden-visually');

            if(data.success) {
                nfcScannerInput.value = ''; // Reset main scanner on success
                resetUI();
                return true;
            }
        } catch (error) {
            messageDiv.textContent = 'An unexpected network error occurred.';
            messageDiv.className = 'message-feedback error';
            messageDiv.classList.remove('hidden-visually');
        }
        return false;
    }
    
    addStudentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(addStudentForm, './api/add_student.php', addMessageDiv);
    });

    updateBalanceForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(updateBalanceForm, './api/update_student_balance.php', balanceMessageDiv);
    });

    editStudentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(editStudentForm, './api/update_student_details.php', editMessageDiv);
    });

    // --- DELETE LOGIC ---
    deleteBtn.addEventListener('click', () => {
        deleteStudentNameSpan.textContent = state.studentData.full_name;
        deleteModal.classList.remove('hidden-visually');
    });

    cancelDeleteBtn.addEventListener('click', () => {
        deleteModal.classList.add('hidden-visually');
    });

    confirmDeleteBtn.addEventListener('click', async () => {
        try {
            const response = await fetch('./api/delete_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ student_id: state.studentId })
            });
            const data = await response.json();
            if(data.success) {
                deleteModal.classList.add('hidden-visually');
                alert('Student deleted successfully.'); // Using alert for critical feedback
                nfcScannerInput.value = '';
                resetUI();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('A network error occurred during deletion.');
        }
    });

});
