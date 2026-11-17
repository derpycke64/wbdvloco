<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['username'])) {
    header('Location: products.php');
    exit;
}
if (empty($_SESSION['loco_auth_csrf'])) {
    $_SESSION['loco_auth_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['loco_auth_csrf'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $year = filter_var($_POST['dob-year'] ?? null, FILTER_VALIDATE_INT);
    $month = filter_var($_POST['dob-month'] ?? null, FILTER_VALIDATE_INT);
    $day = filter_var($_POST['dob-day'] ?? null, FILTER_VALIDATE_INT);

    if (!hash_equals($csrf, $postedCsrf)) {
        http_response_code(403);
        $message = 'This form expired. Refresh the page and try again.';
    } elseif ($username === '' || $email === '' || $password === '' || $confirmPassword === '' || !$year || !$month || !$day) {
        $message = 'All fields are required.';
    } elseif (strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $message = 'Enter a valid username and email address.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = 'Use a password between 8 and 72 characters.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Your passwords do not match.';
    } elseif ($year < 1900 || $year > (int)gmdate('Y') || !checkdate($month, $day, $year)) {
        $message = 'Enter a valid date of birth.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $birthday = sprintf('%04d-%02d-%02d', $year, $month, $day);
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password, birthday, wallet_balance)
                 VALUES (:username, :email, :password, :birthday, 0.00)'
            );
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':birthday' => $birthday,
            ]);
            $newUserId = $pdo->lastInsertId();
            $profile = $pdo->prepare(
                "INSERT INTO user_profiles (user_id, display_name, avatar_url)
                 VALUES (:user_id, :display_name, 'images/default_avatar.png')"
            );
            $profile->execute([':user_id' => $newUserId, ':display_name' => $username]);
            $pdo->commit();
            header('Location: login.php?registered=' . urlencode($username));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ((string)$e->getCode() === '23000') {
                $message = 'Username or email already exists.';
            } else {
                error_log('LOCO registration database error: ' . $e->getMessage());
                $message = 'Registration is temporarily unavailable. Please try again later.';
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
                <img src="/Loco/images/loco-logo.png" class="loco-logo-img" alt="Logo">
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
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="loco-group">
                <label class="loco-label">CREATE A PROFILE USERNAME</label>
                <input type="text" name="username" class="loco-input" placeholder="Your Username" required autocomplete="off">
            </div>

            <div class="loco-group">
                <label class="loco-label">EMAIL ADDRESS</label>
                <input type="email" name="email" autocomplete="email" class="loco-input" placeholder="you@example.com" required autocomplete="off">
            </div>

            <div class="loco-group">
                <label class="loco-label">PASSWORD</label>
                <input type="password" name="password" autocomplete="new-password" class="loco-input" placeholder="••••••••" required>
            </div>
            <div class="loco-group">
                <label class="loco-label-password">CONFIRM YOUR PASSWORD</label>
                <input type="password" name="confirm_password" id="reg-confirm" autocomplete="new-password" class="loco-input" required>
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
                    <a href="login.php" class="loco-brand-gloss-btn">Already have an account?</a>
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
                        <a href="work_in_progress.php?feature=YouTube" class="loco-social-link">YT</a>
                        <a href="work_in_progress.php?feature=Twitch" class="loco-social-link">TW</a>
                        <a href="work_in_progress.php?feature=Facebook" class="loco-social-link">FB</a>
                        <a href="work_in_progress.php?feature=X%20Social" class="loco-social-link">X</a>
                    </div>
                </div>

                
                <div class="loco-footer-links-grid">
                    
                    <div class="loco-links-col">
                        <h4>LOCO</h4>
                        <a href="work_in_progress.php?feature=About%20Loco">About Loco</a>
                        <a href="work_in_progress.php?feature=System%20Status">System Status</a>
                        <a href="work_in_progress.php?feature=Track%20Routes">Track Routes</a>
                        <a href="work_in_progress.php?feature=Dev%20Blog">Dev Blog</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>SERVICES</h4>
                        <a href="work_in_progress.php?feature=API%20Portal">API Portal</a>
                        <a href="work_in_progress.php?feature=Careers">Careers</a>
                        <a href="work_in_progress.php?feature=Hardware%20Specs">Hardware Specs</a>
                        <a href="work_in_progress.php?feature=Enterprise">Enterprise</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>LEGAL</h4>
                        <a href="work_in_progress.php?feature=Privacy">Privacy</a>
                        <a href="work_in_progress.php?feature=Accessibility">Accessibility</a>
                        <a href="work_in_progress.php?feature=Notices%20%26%20Policies">Notices & Policies</a>
                        <a href="work_in_progress.php?feature=Cookies">Cookies</a>
                    </div>

                    <div class="loco-links-col">
                        <h4>SUPPORT</h4>
                        <a href="work_in_progress.php?feature=Get%20Support">Get Support</a>
                        <a href="work_in_progress.php?feature=My%20Account">My Account</a>
                        <a href="work_in_progress.php?feature=Documentation">Documentation</a>
                        <a href="work_in_progress.php?feature=Contact%20Us">Contact Us</a>
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