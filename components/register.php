<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../sql/db.php'; // connectDB() 불러옴

$errors = [];
$name = '';
$email = '';

$mysqli = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $password_confirm === '') {
        $errors[] = '모든 정보를 입력해주세요.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '이메일 형식이 올바르지 않습니다.';
    }

    if ($password !== $password_confirm) {
        $errors[] = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
    }

    if (empty($errors)) {

        // 이메일 중복 확인
        $stmt = $mysqli->prepare("SELECT user_id FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = '이미 가입된 이메일입니다.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("INSERT INTO User (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hash);
            $stmt->execute();

            header('Location: login.php?registered=1');
            exit;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>register</title>
    <style>
        body { background: #E7ECFF; font-family: sans-serif; }
        .page-wrapper { display:flex; justify-content:center; padding-top:120px; }
        .card { width:380px; }
        .card-title { font-size:28px; font-weight:700; margin-bottom:30px; }
        .input-text { width:100%; padding:14px; margin-bottom:15px; border-radius:6px; border:1px solid #c7d2fe; }
        .btn { width:100%; padding:14px; background:#4f8ffb; color:white; border:none; border-radius:8px; }
        .error-message { background:#fee2e2; padding:10px; margin-bottom:15px; border-radius:6px; color:#b91c1c; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="card">
        <h1 class="card-title">register</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="name" class="input-text" placeholder="name" required>
            <input type="email" name="email" class="input-text" placeholder="email" required>
            <input type="password" name="password" class="input-text" placeholder="Password" required>
            <input type="password" name="password_confirm" class="input-text" placeholder="check password" required>
            <button type="submit" class="btn">회원가입</button>
        </form>
    </div>
</div>
</body>
</html>