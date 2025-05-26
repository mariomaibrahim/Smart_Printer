document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const closeSidebar = document.querySelector('.close-sidebar');
    const shippingBtn = document.getElementById('shippingBtn');
    const paymentPopup = document.getElementById('paymentPopup');
    const cancelBtn = document.getElementById('cancelBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    // Toggle mobile sidebar
    menuToggle.addEventListener('click', function() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when sidebar is open
    });
    
    // Close sidebar function
    function closeSidebarMenu() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Close sidebar when clicking the X button
    closeSidebar.addEventListener('click', closeSidebarMenu);
    
    // Close sidebar when clicking the overlay
    overlay.addEventListener('click', closeSidebarMenu);
    
    // Open payment popup
    shippingBtn.addEventListener('click', function() {
        paymentPopup.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when popup is open
    });
    
    // Close payment popup
    function closePopup() {
        paymentPopup.style.display = 'none';
        document.body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Close popup with cancel button
    cancelBtn.addEventListener('click', closePopup);
    
    // Close popup when clicking outside
    paymentPopup.addEventListener('click', function(e) {
        if (e.target === paymentPopup) {
            closePopup();
        }
    });
    
    // Escape key closes popups and sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (paymentPopup.style.display === 'flex') {
                closePopup();
            }
            if (sidebar.classList.contains('active')) {
                closeSidebarMenu();
            }
        }
    });
    
    // Logout functionality
    logoutBtn.addEventListener('click', function() {
        // For demonstration purposes, redirect to login page
        // In a real application, you would perform logout actions here
        alert('Logging out...');
        window.location.href = '../Home_page/home_page.php'; // Redirect to homepage/login page
    });
    
    // Add active class to current navigation link based on URL
    function setActiveNavLink() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-links a, .sidebar-nav a');
        
        navLinks.forEach(link => {
            const linkPage = link.getAttribute('href').split('#')[0];
            if (linkPage === currentPage || (currentPage === 'user.html' && linkPage === '')) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    // Call function on page load
    setActiveNavLink();
    
    // Animation for fields on hover
    const fieldValues = document.querySelectorAll('.field-value');
    fieldValues.forEach(field => {
        field.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 51, 0.1)';
        });
        
        field.addEventListener('mouseout', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
});