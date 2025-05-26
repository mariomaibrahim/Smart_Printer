// Sidebar functionality
const menuToggle = document.querySelector('.menu-toggle');
const closeSidebar = document.querySelector('.close-sidebar');
const sidebar = document.querySelector('.sidebar');
const overlay = document.querySelector('.overlay');
const sidebarLinks = document.querySelectorAll('.sidebar-nav a');

function toggleSidebar() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
}

function closeSidebarFunc() {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 300);
    document.body.style.overflow = 'auto';
}

menuToggle.addEventListener('click', toggleSidebar);
closeSidebar.addEventListener('click', closeSidebarFunc);
overlay.addEventListener('click', closeSidebarFunc);

// Close sidebar when a link is clicked
sidebarLinks.forEach(link => {
    link.addEventListener('click', closeSidebarFunc);
});

// Touch support for sidebar
let touchStartX = 0;
let touchEndX = 0;

sidebar.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].screenX;
});

sidebar.addEventListener('touchend', e => {
    touchEndX = e.changedTouches[0].screenX;
    if (touchStartX - touchEndX > 50) {
        closeSidebarFunc();
    }
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Timeline scroll animations
$(document).ready(function () {
    function isElementInViewport(el) {
        var rect = el[0].getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.8
        );
    }

    function handleScroll() {
        $('.timeline-item').each(function (index) {
            if (isElementInViewport($(this)) && !$(this).hasClass('visible')) {
                setTimeout(() => {
                    $(this).addClass('visible');
                }, index * 200); // Staggered animation
            }
        });
    }

    handleScroll();
    $(window).on('scroll', handleScroll);
});

// Print Now button redirect logic
document.addEventListener('DOMContentLoaded', function() {
    // Get the Print Now button
    const printButton = document.querySelector('.hero-buttons .contact');
    
    if (printButton) {
        // Override the default link behavior
        printButton.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent the default navigation
            
            // Redirect to login page
            window.location.href = '../login_pages/login.php';
            
            // Note: In a real implementation with authentication, you would check if user is logged in:
            /*
            if (userIsLoggedIn()) {
                window.location.href = 'Options_page.php';
            } else {
                window.location.href = '../login_pages/login.php';
            }
            */
        });
    }
});