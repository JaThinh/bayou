document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleBtn = document.getElementById('toggle-sidebar');
    const menuTexts = document.querySelectorAll('.menu-text');
    const sidebarLogo = document.querySelector('.sidebar-logo h2');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        if (sidebar.classList.contains('collapsed')) {
            menuTexts.forEach(text => text.style.display = 'none');
            sidebarLogo.innerHTML = 'B<span>A</span>';
            sidebarLogo.style.fontSize = '24px';
        } else {
            // Wait for transition before showing text
            setTimeout(() => {
                menuTexts.forEach(text => text.style.display = 'inline-block');
                sidebarLogo.innerHTML = 'Bayou<span>Admin</span>';
            }, 200);
        }
    });
    
    // Add active state functionality for menu items
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault(); // For demo purposes
            menuItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
