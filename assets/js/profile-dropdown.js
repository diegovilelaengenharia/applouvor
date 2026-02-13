// Toggle Profile Dropdown
function toggleProfileDropdown(event, dropdownId = 'headerProfileDropdown') {
    if (event) event.stopPropagation();

    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) {
        console.error('Profile dropdown not found:', dropdownId);
        return;
    }

    // Close notification dropdown if open
    const notificationDropdown = document.getElementById('notificationDropdownDesktop');
    if (notificationDropdown && notificationDropdown.classList.contains('active')) {
        notificationDropdown.classList.remove('active');
    }

    // Toggle profile dropdown
    dropdown.classList.toggle('active');

    // Close on outside click
    if (dropdown.classList.contains('active')) {
        setTimeout(() => {
            document.addEventListener('click', function closeProfileDropdown(e) {
                if (!dropdown.contains(e.target) && !e.target.closest('.profile-avatar-btn')) {
                    dropdown.classList.remove('active');
                    document.removeEventListener('click', closeProfileDropdown);
                }
            });
        }, 10);
    }
}

// Make function globally available
window.toggleProfileDropdown = toggleProfileDropdown;
