<?php 
// Include auth system
require_once '../BackEnd/PHP-pages/session_auth.php';

// Require login for this page
requireLogin();

// Include user data
require_once '../BackEnd/PHP-pages/register.php';

// Database connection for notifications
try {
    $dbname = new PDO('mysql:host=localhost;dbname=aitp', 'root', '');
    $dbname->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get user notifications
$notifications = [];
$unread_count = 0;

try {
    // Get recent notifications (last 10) - only unread notifications
    $stmt = $dbname->prepare("SELECT * FROM notifications WHERE user_id = :user_id AND seen = 0 ORDER BY created_at DESC LIMIT 10");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $unread_count = count($notifications);
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Admin users list
$allowedAdminUsers = [2320603, 2320598, 2320241];

// Check if current user has admin privileges
$isAdmin = isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], $allowedAdminUsers);
?>
<!DOCTYPE html>  
<html lang="en">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Smart Printer - User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="user.css">
    <link rel="stylesheet" href="../Home_page/style.css">
</head>  
<body>  
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="logo">
            <div class="logo-icon">AITP</div>
            <span>Smart Printer</span>
        </div>

        <div class="nav-links">
            <a href="../Home_page/home_page.php">Home</a>
            <a href="../Home_page/home_page.php#features">Features</a>
            <a href="../Home_page/home_page.php#how-it-works">How It Works</a>
            <a href="../Home_page/home_page.php#contact_us">Contact Us</a>
        </div>

        <div class="right-section">
            <a class="btn btn-print" href="../Home_page/Options_page.php">
                <i class="fa-solid fa-print"></i> Print Now
            </a>
            <?php if($isAdmin): ?>
            <a class="btn btn-admin" href="../BackEnd/Admin/index.php">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
            <?php endif; ?>
            <a class="btn btn-logout" href="../BackEnd/PHP-pages/logout.php">
                <i class="fas fa-sign-out-alt"></i> Log out
            </a>
           <div class="menu-toggle" role="button" aria-label="Toggle menu">☰</div>
        </div>
    </div>

    <!-- Mobile Sidebar -->
    <div class="sidebar" role="navigation" aria-label="Mobile navigation">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">AITP</div>
                <span>Smart Printer</span>
            </div>
            <div class="close-sidebar" role="button" aria-label="Close menu">×</div>
        </div>

        <div class="sidebar-nav">
            <a href="../Home_page/home_page.php">Home</a>
            <a href="../Home_page/home_page.php#features">Features</a>
            <a href="../Home_page/home_page.php#how-it-works">How It Works</a>
            <a href="../Home_page/home_page.php#contact_us">Contact Us</a>
        </div>

        <div class="sidebar-footer">
             <a class="btn btn-print" href="../Home_page/Options_page.php">
                <i class="fa-solid fa-print"></i> Print Now
            </a>
            <?php if($isAdmin): ?>
            <a class="btn btn-admin" href="../BackEnd/Admin/index.php">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
            <?php endif; ?>
            <a class="btn btn-logout" href="../BackEnd/PHP-pages/logout.php">
                <i class="fas fa-sign-out-alt"></i> Log out
            </a>
        </div>
    </div>

    <div class="overlay"></div>

    <!-- Main Content -->
    <div class="profile-container">  
        <div class="user-info">  
            <div class="profile-header">  
                <div class="profile-picture-placeholder">  
                    <i class="fas fa-user user-icon"></i>  
                </div>
                <h2>User Profile</h2>  
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="fa fa-bell"></i>
                        <?php if($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="notification-dropdown-content" id="notificationDropdown">
                        <div class="notification-header">
                            You have <?php echo $unread_count; ?> new notifications
                        </div>
                        
                        <div class="notification-list">
                            <?php if(empty($notifications)): ?>
                                <div class="no-notifications">
                                    No new notifications
                                </div>
                            <?php else: ?>
                                <?php foreach($notifications as $notification): ?>
                                    <div class="notification-item unread" 
                                         data-notification-id="<?php echo $notification['id']; ?>">
                                        <div style="display: flex; align-items: flex-start;">
                                            <div class="notification-icon <?php echo $notification['type'] == 'success' ? 'success' : 'info'; ?>">
                                                <i class="fa <?php echo $notification['type'] == 'success' ? 'fa-check' : 'fa-info'; ?>"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <div class="notification-content">
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </div>
                                                <div class="notification-time">
                                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($unread_count > 0): ?>
                            <div class="notification-actions">
                                <button class="mark-all-read-btn" id="markAllReadBtn">
                                    Mark all as read
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>  

            <div class="field-container">  
                <label class="field-label">Name</label>  
                <div class="field-value"><?php echo $users['name'] ?? $_SESSION['user_name']; ?></div>  
            </div>   

            <div class="field-container">  
                <label class="field-label">Email</label>  
                <div class="field-value"><?php echo $users['email'] ?? $_SESSION['user_email']; ?></div>  
            </div>  

            <div class="field-container">  
                <label class="field-label">ID</label>  
                <div class="field-value"><?php echo $users['id'] ?? $_SESSION['user_id']; ?></div>  
            </div>  
        </div>  

        <div class="balance-section">  
            <div class="balance-content">  
                <h2 class="balance-title">Your Balance</h2>  
                <div class="balance-amount">  
                    <img src="coin aitp.png" alt="Coin" class="coin-img">
                    <span><?php echo $users['balance'] ?? $_SESSION['user_balance']; ?></span>  
                </div>    
            </div>  
        </div>  
    </div>  

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menu toggle for mobile sidebar
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');
        const closeSidebar = document.querySelector('.close-sidebar');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Notification functionality
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadBtn');

        // Toggle notification dropdown
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });

        // Mark individual notification as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-notification-id');
                if (this.classList.contains('unread')) {
                    markNotificationAsRead([notificationId]);
                    
                    // Remove the notification from DOM with animation
                    this.style.opacity = '0.5';
                    this.style.transition = 'opacity 0.3s ease';
                    
                    setTimeout(() => {
                        this.remove();
                        updateNotificationBadge();
                        
                        // Check if no notifications left
                        const remainingNotifications = document.querySelectorAll('.notification-item').length;
                        if (remainingNotifications === 0) {
                            document.querySelector('.notification-list').innerHTML = '<div class="no-notifications">No new notifications</div>';
                            const actionsDiv = document.querySelector('.notification-actions');
                            if (actionsDiv) {
                                actionsDiv.remove();
                            }
                        }
                    }, 300);
                }
            });
        });

        // Mark all notifications as read
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                markNotificationAsRead([]);
                
                // Remove all notifications from DOM with animation
                const allNotifications = document.querySelectorAll('.notification-item.unread');
                allNotifications.forEach((item, index) => {
                    setTimeout(() => {
                        item.style.opacity = '0.5';
                        item.style.transition = 'opacity 0.3s ease';
                        
                        setTimeout(() => {
                            item.remove();
                        }, 300);
                    }, index * 100);
                });
                
                // Update UI after all animations
                setTimeout(() => {
                    document.querySelector('.notification-list').innerHTML = '<div class="no-notifications">No new notifications</div>';
                    this.parentElement.remove();
                    updateNotificationBadge();
                }, (allNotifications.length * 100) + 400);
            });
        }

        // Function to mark notifications as read
        function markNotificationAsRead(notificationIds) {
            fetch('../BackEnd/PHP-pages/upload.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_notifications_read&notification_ids=' + JSON.stringify(notificationIds)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Notifications marked as read');
                } else {
                    console.error('Error marking notifications as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Update notification badge
        function updateNotificationBadge() {
            const badge = document.querySelector('.notification-badge');
            const unreadItems = document.querySelectorAll('.notification-item.unread').length;
            
            if (unreadItems === 0 && badge) {
                badge.remove();
            } else if (badge) {
                badge.textContent = unreadItems;
            }
            
            // Update header text
            const header = document.querySelector('.notification-header');
            if (header) {
                header.textContent = `You have ${unreadItems} new notifications`;
            }
        }

        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            fetch('../BackEnd/PHP-pages/upload.php?action=get_unread_notifications_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > <?php echo $unread_count; ?>) {
                        // Show notification alert for new notifications
                        showNewNotificationAlert(data.unread_count - <?php echo $unread_count; ?>);
                        
                        // Reload notifications
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error checking for new notifications:', error);
                });
        }, 30000);

        // Function to show new notification alert
        function showNewNotificationAlert(newCount) {
            // Create alert element
            const alert = document.createElement('div');
            alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4CAF50;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-family: Arial, sans-serif;
                animation: slideIn 0.3s ease;
            `;
            
            alert.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa fa-bell" style="font-size: 18px;"></i>
                    <span>You have ${newCount} new notification${newCount > 1 ? 's' : ''}!</span>
                </div>
            `;
            
            // Add CSS animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(alert);
            
            // Remove alert after 3 seconds
            setTimeout(() => {
                alert.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    alert.remove();
                    style.remove();
                }, 300);
            }, 3000);
        }

        // Payment popup functionality (if exists)
        const shippingBtn = document.getElementById('shippingBtn');
        const paymentPopup = document.getElementById('paymentPopup');
        const confirmPayBtn = document.getElementById('confirmPayBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        if(shippingBtn) {
            shippingBtn.addEventListener('click', function() {
                paymentPopup.style.display = 'flex';
            });
        }
        
        if(cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                paymentPopup.style.display = 'none';
            });
        }
        
        if(confirmPayBtn) {
            confirmPayBtn.addEventListener('click', function() {
                alert('Payment processed successfully!');
                paymentPopup.style.display = 'none';
            });
        }
    });
    </script>
</body>  
</html>