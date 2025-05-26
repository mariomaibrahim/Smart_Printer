
<?php
// Include auth system
require_once '../BackEnd/PHP-pages/session_auth.php';

// Check if user is logged in (but don't redirect)
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Printer</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>

<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="logo">
            <div class="logo-icon">AITP</div>
            <span>Smart Printer</span>
        </div>

        <div class="nav-links">
            <a href="#hero" class="active">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#contact_us">Contact Us</a>
        </div>

        <div class="right-section">
            <?php if ($isLoggedIn): ?>
                <!-- Show these buttons when logged in -->
                <a class="btn btn-profile" href="../User_page/user.php">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a class="btn btn-logout" href="../BackEnd/PHP-pages/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </a>
            <?php else: ?>
                <!-- Show this button when not logged in -->
                <a class="btn btn-signup" href="../login_pages/login.php">
                    <i class="fa-solid fa-user-plus"></i> Sign in
                </a>
            <?php endif; ?>
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
            <a href="#hero" class="active">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#contact-section">Contact Us</a>
        </div>

        <div class="sidebar-footer">
            <?php if ($isLoggedIn): ?>
                <a class="btn btn-profile" href="../User_page/user.php">
                    <i class="fas fa-user"></i> My Profile
                </a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a class="btn btn-logout" href="../BackEnd/PHP-pages/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </a>
            <?php else: ?>
                <a class="btn btn-login" href="../login_pages/login.php">
                    <i class="fa-solid fa-print"></i> Sign in
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="overlay"></div>

    <!-- Hero Section -->
    <section id="hero" class="hero">
        <div class="hero-container">
            <div class="hero-text">
                <h1>Welcome to the AITP</h1>
                <p>Your printer... smarter than you think! Control it remotely, monitor printing status, and do
                    everything with the push of a button.</p>
                <div class="hero-buttons">
                    <a href="#features"><button class="info">More Info</button>
                    <?php if ($isLoggedIn): ?>
                        <a href="./Options_page.php"><button class="contact">Print now</button></a>
                    <?php else: ?>
                        <a href="../login_pages/login.php"><button class="contact">Print now</button></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <div class="printer-container">
                    <dotlottie-player src="https://lottie.host/c14f3f3c-7f32-4aa5-9bc6-db87eefa122b/JkEsOhfTmr.lottie"
                        background="transparent" speed="1" loop autoplay width="400px">
                    </dotlottie-player>
                    <div class="paper"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <h2 class="section-title"> Printer Features</h2>
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-print"></i></div>
                <h3>High-Quality Printing</h3>
                <p>Enjoy high-resolution printing up to 4800×1200 dpi with vibrant colors and sharp text for all your
                    documents.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-wifi"></i></div>
                <h3>Wireless Connectivity</h3>
                <p>Print easily from any device using built-in Wi-Fi and Bluetooth, with a dedicated mobile app for
                    remote printing.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Ink Saving</h3>
                <p>Innovative economical ink system saves up to 50% of traditional printing costs with high-capacity
                    cartridges that last longer.</p>
            </div>
        </div>
    </section>


    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="how-container">
            <h2 class="section-title">How It Works</h2>

            <div class="timeline">
                <!-- Item 1 -->
                <div class="timeline-item left">
                    <div class="timeline-number">01</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="timeline-text">
                            <h3 class="timeline-title">Select Print Options</h3>
                            <p class="timeline-description">
                                Choose print type, paper size, and binding type, along with the delivery method.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="timeline-item right">
                    <div class="timeline-number">02</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="timeline-text">
                            <h3 class="timeline-title">Upload the Document</h3>
                            <p class="timeline-description">
                                After logging into your account, securely upload the file in PDF format with high
                                privacy.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="timeline-item left">
                    <div class="timeline-number">03</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="timeline-text">
                            <h3 class="timeline-title">Choose Payment Method</h3>
                            <p class="timeline-description">
                                Pay using a Mada debit card, credit card, or your wallet balance.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="timeline-item right">
                    <div class="timeline-number">04</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="timeline-text">
                            <h3 class="timeline-title">Receive Your Prints</h3>
                            <p class="timeline-description">
                                Easily receive them through pick-up points, express delivery, or shipping to your area.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> <br>
    <!-- Connect with Our Team Section -->
    <section class="contact-section" id="contact_us">
        <h2>Connect with Our Team</h2>
        <p class="subtitle">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut sollicitudin tellus luctus
            neullamcorper mattis, purus leo dotu.</p>

        <div class="contact-container">
            <!-- Contact Form -->
            <div class="contact-form">
                <h3>Get in Touch with Us</h3>
                <form action="#" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" placeholder="Input your name">
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Input your ID">
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" placeholder="Email">
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Submit your message request"></textarea>
                    </div>
                    <button type="submit" class="send-button">Send message</button>
                </form>
            </div>
            <br>
            <!-- Contact Details -->
            <div class="contact-details">
                <h3>Contact Details</h3>
                <p class="text">Lorem ipsum dolor sit amet, consecte tuam olle aliquip acing eliqtia aute sit dolor
                    aed
                    tana nisia datat tane tal nascaiper del matario denta low bico.</p>

                <ul class="detail-list">
                    <li class="detail-item">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                            </svg>
                        </div>
                        <div class="detail-content">
                            <h4>Address</h4>
                            <p>Jl. Raya Kuta No. 121</p>
                        </div>
                    </li>
                    <li class="detail-item">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z" />
                            </svg>
                        </div>
                        <div class="detail-content">
                            <h4>Mobile</h4>
                            <p>(+021) 789 345</p>
                        </div>
                    </li>
                    <li class="detail-item">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M8.5 5.5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5z" />
                                <path
                                    d="M6.5 0a.5.5 0 0 0 0 1H7v1.07a7.001 7.001 0 0 0-3.273 12.474l-.602.602a.5.5 0 0 0 .707.708l.746-.746A6.97 6.97 0 0 0 8 16a6.97 6.97 0 0 0 3.422-.892l.746.746a.5.5 0 0 0 .707-.708l-.601-.602A7.001 7.001 0 0 0 9 2.07V1h.5a.5.5 0 0 0 0-1h-3zm1.038 3.018a6.093 6.093 0 0 1 .924 0 6 6 0 1 1-.924 0zM10 7a.5.5 0 0 0-.5.5v1h-1a.5.5 0 0 0 0 1H10v1a.5.5 0 0 0 1 0v-1h1a.5.5 0 0 0 0-1h-1v-1A.5.5 0 0 0 10 7z" />
                            </svg>
                        </div>
                        <div class="detail-content">
                            <h4>Availability</h4>
                            <p>Daily 09 am - 05 pm</p>
                        </div>
                    </li>
                    <li class="detail-item">
                        <div class="detail-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z" />
                            </svg>
                        </div>
                        <div class="detail-content">
                            <h4>Email</h4>
                            <p>admin@support.com</p>
                        </div>
                    </li>
                </ul>

                <div class="social-media">
                    <h4>Social Media:</h4>
                    <div class="social-icons">
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z" />
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z" />
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </section>

    <!--footer section-->
    <footer class="footer-container__complexArchitecturalSystemIntegrationDynamicComponentWrapper">
        <!-- Logo and Information Section -->
        <div class="footer-logo-section__dynamicResponsiveSystemEnhancedVisualRepresentationUnit">
            <div class="footer-logo__brandIdentityEnhancedVisualRepresentationDynamicScalableComponent">
                <span></span> Smart Printer
            </div>
            <p class="footer-description__contentOptimizedSearchEngineEnhancedTextualRepresentation">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                labore
                et
                dolore magna.
            </p>
            <div class="footer-contact-info__userInteractionDataPointCollectionSystemElement">
                <div class="footer-contact-item__compositeDataStructureUserAccessibleInformationUnit">
                    <i class="fas fa-map-marker-alt"></i> Jl. Nih Mandala, Renon, Bali 80225
                </div>
                <div class="footer-contact-item__compositeDataStructureUserAccessibleInformationUnit">
                    <i class="fas fa-phone-alt"></i> +6221 2002 2012
                </div>
                <div class="footer-contact-item__compositeDataStructureUserAccessibleInformationUnit">
                    <i class="fas fa-envelope"></i> support@yourdomain.ltd
                </div>
            </div>
        </div>

        <!-- Quick Links Section -->
        <div class="footer-links-section__navigationalHierarchyEnhancedUserInterfaceComponent">
            <h3 class="footer-heading__hierarchicalStructuralNavigationSystemComponentIdentifier">Quick Links
            </h3>
            <ul class="footer-link-list__interfaceNavigationCollectionDynamicContentAccessibilityEnhancer">
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">About
                        Us</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Our
                        Service</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Our
                        Project</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Faq</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Our
                        Pricing</a>
                </li>
            </ul>
        </div>

        <!-- Information Section -->
        <div class="footer-information-section__contentManagementSystemIntegratedDisplayElement">
            <h3 class="footer-heading__hierarchicalStructuralNavigationSystemComponentIdentifier">Information
            </h3>
            <ul class="footer-link-list__interfaceNavigationCollectionDynamicContentAccessibilityEnhancer">
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Contact
                        Us</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Privacy
                        Policy</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Terms
                        of
                        Service</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Disclaimer</a>
                </li>
                <li class="footer-link-item__userInteractionEventListenerEnabledNavigationalNode">
                    <a href="#" class="footer-link__systemNavigationUserInteractionEventHandlerComponent">Credit</a>
                </li>
            </ul>
        </div>

        <!-- Newsletter Section -->
        <div class="footer-newsletter-section__dataCollectionUserEngagementOptimizedModule">
            <h3 class="footer-heading__hierarchicalStructuralNavigationSystemComponentIdentifier">Newsletter
            </h3>
            <p class="footer-description__contentOptimizedSearchEngineEnhancedTextualRepresentation">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                labore
                et
                dolore magna.
            </p>
            <form class="footer-newsletter-form__dataCollectionUserSubmissionProcessingSystem">
                <input type="email" placeholder="Your Email Address"
                    class="footer-newsletter-input__userDataEntrySystemValidationEnabledComponent">
                <button type="submit"
                    class="footer-newsletter-button__formSubmissionProcessEventTriggerUserInterfaceElement">Send</button>
            </form>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-divider__visualSeparationEnhancementStructuralIntegrityComponent"></div>
        <div class="footer-bottom__legalInformationDisplaySystemGeneratedContentContainer">
            <div class="footer-copyright__legalProtectionInformationSystemGeneratedTextualComponent">
                Copyright © 2023 Internal, All rights reserved. Powered by: Box Creatives
            </div>
            <div class="footer-social-icons__externalSystemIntegrationUserEngagementEnhancementModule">
                <a href="#" class="footer-social-icon__externalPlatformLinkUserInteractionTriggerComponent">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="footer-social-icon__externalPlatformLinkUserInteractionTriggerComponent">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="footer-social-icon__externalPlatformLinkUserInteractionTriggerComponent">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="footer-social-icon__externalPlatformLinkUserInteractionTriggerComponent">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>
    </footer>

    <script src="home.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Print now button 
            const printButtons = document.querySelectorAll('.contact');
            printButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    <?php if (!$isLoggedIn): ?>
                    // If not logged in, handle redirection in JS
                    e.preventDefault();
                    window.location.href = '../login_pages/login.php';
                    <?php endif; ?>
                });
            });
        });
    </script>

</body>

</html>