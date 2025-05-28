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
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <title>Print Page</title>
</head>

<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="logo">
            <div class="logo-icon">AITP</div>
            <span>Smart Printer</span>
        </div>

        <div class="nav-links">
            <a href="home_page.php" class="active">Home</a>
            <a href="home_page.php">Features</a>
            <a href="home_page.php">How It Works</a>
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
            <a href="home_page.php #hero">Home</a>
            <a href="home_page.php #features">Features</a>
            <a href="home_page.php #how-it-works">How It Works</a>
            <a href="home_page.php #contact">Contact Us</a>
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
                <a class="btn btn-signup" href="../login_pages/login.php">
                    <i class="fa-solid fa-user-plus"></i> Sign in
                </a>
            <?php endif; ?>
        </div>
    </div>
    <br><br>
    <div class="overlay"></div>
    <!-- Print option-->
    <div class="app-container">
        <header class="app-header">
            <h1>Print Job</h1>
            <p>Configure your print settings and upload your documents.</p>
        </header>

        <main class="app-main">
            <section class="options-panel">
                <h2>Print Options</h2>

                <!-- Color Options -->
                <div class="option-group" id="color-options-section">
                    <label class="option-label">Color</label>
                    <div class="radio-button-group">
                        <label class="radio-button">
                            <input type="radio" name="color" value="bw" checked>
                            <span class="radio-label-text">Black & White</span>
                        </label>
                        <label class="radio-button">
                            <input type="radio" name="color" value="color">
                            <span class="radio-label-text">Color</span>
                        </label>
                    </div>
                </div>
                <!-- Layout Options -->
                <div class="option-group" id="layout-options-section">
                    <label class="option-label">Layout</label>
                    <div class="radio-button-group">
                        <label class="radio-button">
                            <input type="radio" name="layout" value="portrait" checked>
                            <span class="radio-label-text">Portrait</span>
                        </label>
                        <label class="radio-button">
                            <input type="radio" name="layout" value="landscape">
                            <span class="radio-label-text">Landscape</span>
                        </label>
                    </div>
                </div>
                <!-- Sides Options -->
                <div class="option-group" id="sides-options-section">
                    <label class="option-label">Sides</label>
                    <div class="radio-button-group">
                        <label class="radio-button">
                            <input type="radio" name="sides" value="one-sided" checked>
                            <span class="radio-label-text">One-sided</span>
                        </label>
                        <label class="radio-button">
                            <input type="radio" name="sides" value="two-sided">
                            <span class="radio-label-text">Two-sided (Booklet)</span>
                            <!-- Or Long-edge/Short-edge if needed -->
                        </label>
                    </div>
                </div>
                <!-- Pages Options -->
                <div class="option-group" id="pages-options-section">
                    <label class="option-label">Pages</label>
                    <div class="radio-options-vertical">
                        <label class="radio-button">
                            <input type="radio" name="pages" value="all" checked>
                            <span class="radio-label-text">All</span>
                        </label>
                        <label class="radio-button radio-button-input">
                            <input type="radio" name="pages" value="custom">
                            <input type="text" id="customPageRange" class="text-input" placeholder="e.g., 1-3, 5, 7-9"
                                disabled>
                        </label>
                    </div>
                </div>
                <!-- Copies Options -->
                <div class="option-group" id="copies-options-section">
                    <label class="option-label" for="copiesInput">Copies</label>
                    <div class="copies-control">
                        <button class="icon-btn" id="decreaseCopies" aria-label="Decrease copies"><i
                                class="fas fa-minus"></i></button>
                        <input type="number" id="copiesInput" class="number-input" value="1" min="1" max="999"
                            aria-label="Number of copies">
                        <button class="icon-btn" id="increaseCopies" aria-label="Increase copies"><i
                                class="fas fa-plus"></i></button>
                    </div>
                </div>

            </section>

            <section class="file-panel">
                <!-- File Upload Section -->
                <div class="upload-section" id="upload-section">
                    <h2>Upload Document</h2>
                    <div class="upload-area" id="dropArea">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <p>Drag & Drop your file here</p>
                        <p class="upload-separator">or</p>
                        <button class="button button-primary" id="browseBtn">Browse File</button>
                        <input type="file" id="fileInput" hidden accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <p class="upload-hint">Supported formats: PDF, DOC, DOCX, JPG, PNG. Max size: 10MB.</p>
                    </div>
                    <div class="progress-area" id="progressArea">
                        <!-- Progress bars will appear here -->
                    </div>
                    <div class="uploaded-area" id="uploadedArea">
                        <!-- Uploaded file list will appear here -->
                    </div>
                </div>
                <!-- Order Summary Section -->
                <div class="summary-section" id="summary-section">
                    <h2>Order Summary</h2>
                    <div class="summary-details">
                        <div class="summary-item">
                            <span class="summary-label">Color:</span>
                            <span class="summary-value" id="summaryColor">Black & White</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Layout:</span>
                            <span class="summary-value" id="summaryLayout">Portrait</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Sides:</span>
                            <span class="summary-value" id="summarySides">One-sided</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Pages:</span>
                            <span class="summary-value" id="summaryPages">All</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Copies:</span>
                            <span class="summary-value" id="summaryCopies">1</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Files:</span>
                            <span class="summary-value" id="summaryFiles">0 files</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Page Count:</span>
                            <span class="summary-value" id="summaryPageCount">0</span>
                        </div>
                    </div>
                    <button class="button button-primary button-full-width" id="printBtn" disabled>Submit Print
                        Job</button>
                </div>
            </section>
        </main> <br>
        <!-- Connect with Our Team Section -->
        <section class="contact-section" id="contact_us">
            <h2>Connect with Our Team</h2>
            <p class="subtitle">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut sollicitudin tellus luctus
                neullamcorper mattis, purus leo dotu.</p>
            <br>
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
            </div>
        </section>
    </div>
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

   
    <script src="options.js"></script>
    <script src="home.js"></script>
    
    <!-- Payment Confirmation Modal -->
    <div id="printConfirmModal" class="payment-popup">
        <div class="payment-box">
            <svg class="modal-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h2>Service Price</h2>
            <div class="discount-badge">The cost: <span id="cost"> </span> AITP</div>
            <p>Are you sure you want to proceed with payment? <br> This action will be processed immediately.</p>
            <div><button id="okBtn" class="button button-primary"> Print now</button>
                <button id="cancelBtn" class="button close-btn">Cancel</button>

            </div>
        </div>

</body>

</html>