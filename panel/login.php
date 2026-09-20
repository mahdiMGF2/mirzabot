<?php
session_start();

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';

if (!empty($_SESSION['admin_user'])) {
  header('Location: index.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

  if (!csrf_check_value($_POST['_csrf'] ?? '')) {
    $error = $textbotlang['panel']['loginWrongCredentials'];
  } elseif ($username === '' || $password === '') {
    $error = $textbotlang['panel']['loginEnterCredentials'];
  } elseif (!check_login_rate($ip)) {

    $error = $textbotlang['panel']['loginTooManyAttempts'];
    error_log("Login rate limit hit for IP: $ip username: $username");
  } else {

    $admin = select("admin", "*", "username", $username, "select");

    $dummyHash = '$2y$10$dummy.hash.for.timing.attack.prevention.xxxxxxxxxxxxxxxx';
    $storedHash = $admin ? (string) $admin['password'] : $dummyHash;

    $isCorrect = false;
    $storedIsHash = str_starts_with($storedHash, '$2') || str_starts_with($storedHash, '$argon2');
    if ($storedIsHash) {
      $isCorrect = password_verify($password, $storedHash);
    } elseif ($admin) {
      $isCorrect = hash_equals($storedHash, $password);
    }

    if ($isCorrect && $admin) {

      if (!str_starts_with($admin['password'], '$2')) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        update("admin", "password", $hash, "username", $username);
      }
      clear_login_rate($ip);
      session_regenerate_id(true);
      $_SESSION['admin_user'] = $admin['username'];
      $_SESSION['login_time'] = time();
      flash('success', $textbotlang['panel']['loginWelcomeBack'] . $admin['username']);
      header('Location: index.php');
      exit;
    } else {
      $error = $textbotlang['panel']['loginWrongCredentials'];
      error_log("Failed login for username: $username from IP: $ip");
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <meta name="theme-color" content="#0F172A" id="mtc">
  <title><?= $textbotlang['panel']['loginPanelTitle'] ?></title>
  <link rel="stylesheet" href="css/style.css">
  <script>(function () { var t = localStorage.getItem('panel-theme') || 'navy'; document.documentElement.setAttribute('data-theme', t); var c = { navy: '#0F172A', purple: '#180D2E', emerald: '#0A1F1C', sunset: '#1A0D0D', slate: '#080808', light: '#F1F5F9', linen: '#FAF7F2', mint: '#F0FDF4', lavender: '#FAF5FF' }; var m = document.getElementById('mtc'); if (m && c[t]) m.content = c[t]; })();</script>
</head>

<body>
  <div class="auth">
    <aside class="auth-aside">
      <div class="auth-mark">
        <div class="dot">M</div>
        <span><?= $textbotlang['panel']['loginHeading'] ?></span>
      </div>
      <div class="auth-quote">
        <h2><?= $textbotlang['panel']['loginSubtitle'] ?> <a style="color:#a8dafd !important  "
            href="https://github.com/mahdiMGF2/mirzabot"><?= $textbotlang['panel']['loginUsernameLabel'] ?></a>
          <?= $textbotlang['panel']['loginUsernamePlaceholder'] ?></h2>
        <cite><?= $textbotlang['panel']['loginPasswordLabel'] ?></cite>
      </div>
      <div class="auth-foot">© <?= date('Y') ?> <?= $textbotlang['panel']['loginPasswordPlaceholder'] ?></div>
    </aside>
    <main class="auth-main">
      <div class="auth-box" style="animation:fadeUp .5s ease-out">
        <h1><?= $textbotlang['panel']['loginButton'] ?></h1>
        <p class="lede"><?= $textbotlang['panel']['loginRememberMe'] ?></p>
        <?php if ($error): ?>
          <div class="notice notice-no" style="margin-bottom:20px"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form class="auth-form" method="POST" autocomplete="on">
          <?php ?>
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <div class="field">
            <label for="username"><?= $textbotlang['panel']['loginFooter'] ?></label>
            <input type="text" id="username" name="username" class="input" placeholder="admin"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required autofocus
              maxlength="100">
          </div>
          <div class="field">
            <label for="password"><?= $textbotlang['panel']['loginErrorTitle'] ?></label>
            <input type="password" id="password" name="password" class="input" placeholder="••••••••"
              autocomplete="current-password" required maxlength="200">
          </div>
          <button type="submit" class="btn btn-primary" id="loginBtn">
            <span id="loginText"><?= $textbotlang['panel']['loginShowPassword'] ?></span>
            <span id="loginSpin"
              style="display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite"></span>
          </button>
        </form>
        <div class="auth-bottom"><?= $textbotlang['panel']['loginHidePassword'] ?></div>
      </div>
    </main>
  </div>
  <script src="js/login.js"></script>
</body>

</html>