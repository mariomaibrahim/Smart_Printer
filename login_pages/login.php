
<?php
// Include auth system
require_once '../BackEnd/PHP-pages/session_auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

// Include register.php for login functionality
require_once '../BackEnd/PHP-pages/register.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Login Page</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Montserrat", sans-serif;
        }

        body {
            background-color: #ffffff;
            background: linear-gradient(to right, #ffffff, #ffffff);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            min-height: 100%;
        }

        .container p {
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0.3px;
            margin: 20px 0;
        }

        .container span {
            font-size: 12px;
        }

        .container a {
            color: #333;
            font-size: 13px;
            text-decoration: none;
            margin: 15px 0 10px;
        }

        .container button {
            background-color: #512da8;
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
        }

        .container button.hidden {
            background-color: transparent;
            border-color: #fff;
        }

        .container form {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
        }

        .container input {
            background-color: #eee;
            border: none;
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            width: 90%;
            outline: none;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.active .sign-in {
            transform: translateX(100%);
        }

        .forgot-password {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.active .forgot-password {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: move 0.6s;
        }

        @keyframes move {

            0%,
            49.99% {
                opacity: 0;
                z-index: 1;
            }

            100%,
            100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .social-icons {
            margin: 20px 0;
        }

        .social-icons a {
            border: 1px solid #ccc;
            border-radius: 20%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 3px;
            width: 40px;
            height: 40px;
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .toggle {
            background-color: #512da8;
            height: 100%;
            background: linear-gradient(to right, #5c6bc0, #512da8);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 30px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .toggle-left {
            transform: translateX(-200%);
        }

        .container.active .toggle-left {
            transform: translateX(0);
        }

        .toggle-right {
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right {
            transform: translateX(200%);
        }

        .error {
            color: #ff3333;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
            width: 100%;
        }

        /* New styling for the no account message */
        .account-message {
            margin-top: 15px;
            font-size: 12px;
            text-align: center;
            width: 100%;
        }

        .account-message a {
            color: #512da8;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        /* Form validation styles */
        .error-message {
            color: #ff3333;
            font-size: 11px;
            margin-top: 2px;
            text-align: left;
            width: 100%;
            display: none;
        }

        input.error {
            border: 1px solid #ff3333;
        }

        /* Mobile header icons */
        .mobile-header {
            display: none;
            justify-content: center;
            padding: 15px 0;
            background: linear-gradient(to right, #5c6bc0, #512da8);
            border-radius: 30px 30px 0 0;
        }

        .mobile-header .icon {
            color: white;
            font-size: 24px;
            margin: 0 15px;
            cursor: pointer;
        }

        /* Success message styling */
        .success-message {
            color: #4CAF50;
            font-size: 13px;
            margin: 10px 0;
            display: none;
            text-align: center;
            width: 100%;
        }

        /* Forgot password description */
        .forgot-description {
            text-align: center;
            font-size: 13px;
            color: #666;
            margin: 10px 0 20px;
            width: 80%;
        }

        /* Server message styling */
        .server-message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            width: 90%;
            text-align: center;
        }

        .server-error {
            background-color: #ffebee;
            color: #d32f2f;
            border: 1px solid #f5c6cb;
        }

        .server-success {
            background-color: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                width: 90%;
                min-height: 80vh;
            }
        }

        @media (max-width: 650px) {
            .container {
                width: 90%;
            }

            .form-container {
                width: 100%;
            }

            .sign-in {
                width: 100%;
            }

            .forgot-password {
                width: 100%;
            }

            .toggle-container {
                display: none;
            }

            .container.active .sign-in {
                transform: translateX(-100%);
            }

            .container.active .forgot-password {
                transform: translateX(0);
            }

            .container button {
                padding: 10px 30px;
            }

            .container form {
                padding: 0 20px;
                margin-top: 15px;
            }

            /* Show mobile header on small screens */
            .mobile-header {
                display: flex;
            }

            /* Mobile navigation for smaller screens */
            .mobile-nav {
                display: flex;
                width: 100%;
                padding: 10px;
                justify-content: center;
                background-color: #f9f9f9;
                margin-top: 10px;
            }

            .mobile-nav-text {
                margin: 10px 0;
                text-align: center;
                width: 100%;
            }

            .mobile-nav button {
                margin: 5px;
                background-color: #512da8;
                color: #fff;
                padding: 8px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
            }
        }

        form img {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container" id="container">
        <!-- Forgot Password section -->
        <section id="forgot-password">
            <div class="form-container forgot-password" id="forgot-password">
                <form novalidate method="POST" action="login.php">
                    <input type="hidden" name="action" value="reset">
                    <img src="undraw_push-notifications_5z1s.svg" alt="">
                    <h1>Forgot Password</h1>
                    <p class="forgot-description">Enter your email address below and we'll send you instructions to reset your password.</p>
                    
                    <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] == 'reset'): ?>
                    <div class="server-message server-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    <div class="server-message server-success">
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <input type="email" placeholder="Email" id="reset-email" name="email" required>
                    <div class="error-message" id="reset-email-error">Please enter a valid email address</div>
                    
                    <div class="success-message" id="reset-success">Password reset instructions have been sent to your email.</div>
                    
                    <button type="submit" id="reset-button">Reset Password</button>
                    
                    <!-- Message for users who remember their password -->
                    <div class="account-message">
                        Remember your password? <a href="#" id="go-to-signin">Sign in now</a>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Sign In section -->
        <section id="sign-in">
            <div class="form-container sign-in">
                <form novalidate method="POST" action="login.php">
                    <input type="hidden" name="action" value="signin">
                    <img src="undraw_login_weas.svg" alt="">
                    <h1>Sign In</h1>
                    
                    <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] == 'signin'): ?>
                    <div class="server-message server-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="social-icons">
                        <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                        <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                        <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                    <span>or use your email password</span>
                    <input type="email" placeholder="Email" id="signin-email" name="email" required>
                    <div class="error-message" id="signin-email-error">Please enter a valid email address</div>

                    <input type="password" placeholder="Password" id="signin-password" name="password" required>
                    <div class="error-message" id="signin-password-error">Password is required</div>
                    <a href="#" id="forgot-password-link">Forgot Your Password?</a>
                    <button type="submit">Sign In</button>
                </form>
            </div>
            
            <!-- Toggle Container section -->
            <div class="toggle-container">
                <div class="toggle">
                    <div class="toggle-panel toggle-left">
                        <h1>Welcome Back!</h1>
                        <p>Enter your personal details to use all of site features</p>
                        <button class="hidden" id="login">Sign In</button>
                    </div>
                    <div class="toggle-panel toggle-right">
                        <h1>Forgot Password?</h1>
                        <p>Enter your email to receive password reset instructions</p>
                        <button class="hidden" id="forgot">Reset Password</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        const container = document.getElementById('container');
        const forgotBtn = document.getElementById('forgot');
        const loginBtn = document.getElementById('login');
        const goToSignin = document.getElementById('go-to-signin');
        const forgotPasswordLink = document.getElementById('forgot-password-link');

        // Form elements
        const forgotPasswordForm = document.querySelector('.forgot-password form');
        const signinForm = document.querySelector('.sign-in form');

        // Input fields
        const resetEmail = document.getElementById('reset-email');
        const signinEmail = document.getElementById('signin-email');
        const signinPassword = document.getElementById('signin-password');

        // Error messages
        const resetEmailError = document.getElementById('reset-email-error');
        const signinEmailError = document.getElementById('signin-email-error');
        const signinPasswordError = document.getElementById('signin-password-error');
        
        // Success message
        const resetSuccess = document.getElementById('reset-success');
        const resetButton = document.getElementById('reset-button');

        // Email validation function
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Form validation functions
        function validateForgotPasswordForm(e) {
            let isValid = true;

            // Validate Email
            if (!resetEmail.value.trim() || !isValidEmail(resetEmail.value)) {
                resetEmailError.style.display = 'block';
                resetEmail.classList.add('error');
                isValid = false;
            } else {
                resetEmailError.style.display = 'none';
                resetEmail.classList.remove('error');
            }

            if (!isValid) {
                e.preventDefault();
            }
        }

        function validateSigninForm(e) {
            let isValid = true;

            // Validate Email
            if (!signinEmail.value.trim() || !isValidEmail(signinEmail.value)) {
                signinEmailError.style.display = 'block';
                signinEmail.classList.add('error');
                isValid = false;
            } else {
                signinEmailError.style.display = 'none';
                signinEmail.classList.remove('error');
            }

            // Validate Password
            if (!signinPassword.value.trim()) {
                signinPasswordError.style.display = 'block';
                signinPassword.classList.add('error');
                isValid = false;
            } else {
                signinPasswordError.style.display = 'none';
                signinPassword.classList.remove('error');
            }

            if (!isValid) {
                e.preventDefault();
            }
        }

        // Toggle buttons
        forgotBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });

        // Navigation links
        goToSignin.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove("active");
        });

        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add("active");
        });

        // Form validation
        forgotPasswordForm.addEventListener('submit', validateForgotPasswordForm);
        signinForm.addEventListener('submit', validateSigninForm);

        // Real-time validation on input fields
        resetEmail.addEventListener('input', () => {
            if (resetEmail.value.trim() && isValidEmail(resetEmail.value)) {
                resetEmailError.style.display = 'none';
                resetEmail.classList.remove('error');
            }
        });

        signinEmail.addEventListener('input', () => {
            if (signinEmail.value.trim() && isValidEmail(signinEmail.value)) {
                signinEmailError.style.display = 'none';
                signinEmail.classList.remove('error');
            }
        });

        signinPassword.addEventListener('input', () => {
            if (signinPassword.value.trim()) {
                signinPasswordError.style.display = 'none';
                signinPassword.classList.remove('error');
            }
        });

        // Check for PHP errors and activate appropriate form
        document.addEventListener("DOMContentLoaded", function() {
            <?php if(isset($_POST['action']) && $_POST['action'] == 'reset'): ?>
            container.classList.add("active");
            <?php endif; ?>
        });
    </script>
</body>

</html>