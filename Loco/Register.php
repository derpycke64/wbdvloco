<?php
session_start();
require_once 'db.php';

if(isset($_SESSION['username'])){
    header("Location: products.php");
    exit();
}

$message = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_passwrd = $_POST['confirm_password'];
    $birth_year  = $_POST['dob-year'] ?? null;
    $birth_month = $_POST['dob-month'] ?? null;
    $birth_day   = $_POST['dob-day'] ?? null;

    if(empty($username) || empty($email) || empty($password) || empty($confirm_passwrd) || empty($birth_year) || empty($birth_month) || empty($birth_day)){
        $message = "All fields are required.";
    }
    elseif($password !== $confirm_passwrd){
        $message = "Your Passwords do not match.";
    }
    else{
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $birthday = sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day);
        try{
            $pdo -> beginTransaction();
            $sql_user = "INSERT INTO users (username, email, password, birthday, wallet_balance) VALUES (:username, :email, :password, :birthday, 0.00)";
            $statement_user = $pdo->prepare($sql_user);
            $statement_user->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':birthday' => $birthday
            ]);

            $new_user_id = $pdo ->lastinsertid();
            $sql_profile = "INSERT INTO user_profiles (user_id, display_name, avatar_url) VALUES (:user_id, :display_name, 'images/default_avatar.png')";
            $stmt_profile = $pdo-> prepare($sql_profile);
            $stmt_profile->execute([
                ':user_id'      => $new_user_id,
                ':display_name' => $username
            ]);
            $pdo -> commit();
            header("Location: Login.php?registered=" . urlencode($username));
            exit();
        }catch(\PDOexception $e){
            $pdo->rollBack();
            if($e->getCode() == 23000){
                $message = "Username or Email already exists.";
            } else {
                 $message = "Database Error: " . $e->getMessage(); 
            }
        }
    }
} 
?>
<!DOCTYPE HTML>
<html lang="en">
    <head>
         <meta charset="UTF-8">
         <link rel="stylesheet" href="style.css">
         <title>Loco - Create Account</title>
    <body>
    <header class="loco-global-nav">
        <div class="loco-nav-inner">
            <a href="index.php" class="loco-nav-logo">
                <img src="images/logo.png" class="loco-logo-img" alt="Logo">
                LOCO
            </a>
            <nav class="loco-nav-links">
                <a href="#store" class="loco-nav-link">STORE</a>
                <a href="#community" class="loco-nav-link">COMMUNITY</a>
                <a href="#about" class="loco-nav-link">ABOUT</a>
                <a href="#support" class="loco-nav-link">SUPPORT</a>
            </nav>
        </div>
    </header>
    <div class="loco-header-outside">
        <h1 class="loco-logo-text">LOCO</h1>
    </div>

    <div class="loco-panel">
        <?php if(!empty($message)) echo "<p class='loco-alert-danger'>$message</p>"; ?>
        
        <form method="POST" action="register.php">
            <div class="loco-group">
                <label class="loco-label">CREATE A PROFILE USERNAME</label>
                <input type="text" name="username" class="loco-input" placeholder="Your Username" required autocomplete="off">
            </div>

            <div class="loco-group">
                <label class="loco-label">EMAIL ADDRESS</label>
                <input type="email" name="email" class="loco-input" placeholder="you@example.com" required autocomplete="off">
            </div>

            <div class="loco-group">
                <label class="loco-label">PASSWORD</label>
                <input type="password" name="password" class="loco-input" placeholder="••••••••" required>
            </div>
            <div class="loco-group">
                <label class="loco-label-password">CONFIRM YOUR PASSWORD</label>
                <input type="password" name="confirm_password" id="reg-confirm" class="loco-input" required>
            </div>

            
            <div class="loco-group">
                <label class="loco-label">DATE OF BIRTH</label>
                <div class="loco-dob-container">
                    <select name="dob-month" id="dob-month" class="loco-dob-select" required>
                        <option value="" disabled selected hidden>Month</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>

                    <select name="dob-day" id="dob-day" class="loco-dob-select" required>
                        <option value="" disabled selected hidden>Day</option>
                    </select>

                    <select name="dob-year" id="dob-year" class="loco-dob-select" required>
                        <option value="" disabled selected hidden>Year</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="loco-button">CONTINUE</button>
            <div class="loco-redirect-action-box" style="padding-top: 10px;">
                    <a href="Login.php" class="loco-brand-gloss-btn">Already have an account?</a>
                </div>
        </form>
    </div>
    <footer class="loco-footer-bar">
        <div class="loco-sub-footer">
            <div class="loco-sub-footer-inner">
                
                <div class="loco-footer-brand-col">
                    <div class="loco-footer-logos">
                        <span class="loco-footer-logo-text">LOCO</span>
                        <span class="loco-footer-logo-sub">Stoker Studios</span>
                    </div>
                    <p class="loco-copyright-text">
                        © 2026 Stoker Studios. All rights reserved. All trademarks are property of their respective owners in the PH and other countries.
                    </p>
                    <div class="loco-social-row">
                        <a href="#youtube" class="loco-social-link">YT</a>
                        <a href="#twitch" class="loco-social-link">TW</a>
                        <a href="#facebook" class="loco-social-link">FB</a>
                        <a href="#x" class="loco-social-link">X</a>
                    </div>
                </div>

                
                <div class="loco-footer-links-grid">
                    
                    <div class="loco-links-col">
                        <h4>LOCO</h4>
                        <a href="#about">About Loco</a>
                        <a href="#status">System Status</a>
                        <a href="#routes">Track Routes</a>
                        <a href="#blog">Dev Blog</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>SERVICES</h4>
                        <a href="#api">API Portal</a>
                        <a href="#jobs">Careers</a>
                        <a href="#hardware">Hardware Specs</a>
                        <a href="#enterprise">Enterprise</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>LEGAL</h4>
                        <a href="#privacy">Privacy</a>
                        <a href="#terms">Accessibility</a>
                        <a href="#policies">Notices & Policies</a>
                        <a href="#cookies">Cookies</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>SUPPORT</h4>
                        <a href="#help">Get Support</a>
                        <a href="#account">My Account</a>
                        <a href="#docs">Documentation</a>
                        <a href="#contact">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>


<script>
  const monthSelect = document.getElementById('dob-month');
  const daySelect = document.getElementById('dob-day');
  const yearSelect = document.getElementById('dob-year');

  
  const currentYear = new Date().getFullYear();
  const startYear = 1920;

  for (let y = currentYear; y >= startYear; y--) {
    const option = document.createElement('option');
    option.value = y;
    option.textContent = y;
    yearSelect.appendChild(option);
  }

  function updateDays() {
    const selectedMonth = parseInt(monthSelect.value);
    const selectedYear = parseInt(yearSelect.value);
    
    
    const previouslySelectedDay = daySelect.value;

    
    daySelect.innerHTML = '<option value="" disabled selected>Day</option>';

    
    if (!selectedMonth) return;

    
    const yearToCalculate = selectedYear || 2024; 
    const daysInMonth = new Date(yearToCalculate, selectedMonth, 0).getDate();

    for (let d = 1; d <= daysInMonth; d++) {
      const option = document.createElement('option');
      const value = d.toString().padStart(2, '0');
      option.value = value;
      option.textContent = d;
      daySelect.appendChild(option);
    }

    if (parseInt(previouslySelectedDay) <= daysInMonth) {
      daySelect.value = previouslySelectedDay;
    }
  }
    function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                confirmPassword.setCustomValidity('');
            } else if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        monthSelect.addEventListener('change', updateDays);
        yearSelect.addEventListener('change', updateDays);
        password.addEventListener('change', checkPasswordMatch);
        confirmPassword.addEventListener('keyup', checkPasswordMatch);
</script>