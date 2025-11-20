<?php
session_start();
require_once 'db.php';  // connectDB() 사용

$errors = [];
$email = '';

$mysqli = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = '이메일과 비밀번호를 모두 입력해주세요.';
    }

    if (empty($errors)) {
        // 이메일로 사용자 찾기
        $stmt = $mysqli->prepare("SELECT user_id, name, email, password_hash FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // 사용자 존재 여부 + 비밀번호 검증
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = '이메일 또는 비밀번호가 올바르지 않습니다.';
        } else {
            // 세션에 저장
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            header('Location: main.php');
            exit;
        }

        $stmt->close();
    }
}

$just_registered = isset($_GET['registered']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>login</title>
    <style>
        body { background: #E7ECFF; font-family: sans-serif; margin:0; padding:0; }

        /* 상단 서비스 이름 */
        .brand-header {
            width: 100%;
            text-align: center;
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #4f46e5;
            padding-top: 40px;
        }

        .page-wrapper { display:flex; justify-content:center; padding-top:50px; }
        .card { width:380px; }
        .card-title { font-size:28px; font-weight:700; margin-bottom:30px; text-align:center; }

        .input-text {
            width:100%; padding:14px; margin-bottom:15px;
            border-radius:6px; border:1px solid #c7d2fe;
        }
        .btn {
            width:100%; padding:14px;
            background:#4f8ffb; color:white; border:none; border-radius:8px;
            cursor:pointer;
        }
        .btn-secondary {
            width:100%; padding:14px;
            background:#64748b; color:white; border:none; border-radius:8px;
            margin-top:10px; cursor:pointer;
        }

        .error-message {
            background:#fee2e2; padding:10px; margin-bottom:15px;
            border-radius:6px; color:#b91c1c;
        }
        .success-message {
            background:#d1fae5; padding:10px; margin-bottom:15px;
            border-radius:6px; color:#065f46;
        }
    </style>
</head>
<body>

<!-- 서비스 이름 -->
<div class="brand-header">뀨asty</div>

<div class="page-wrapper">
    <div class="card">
        <h1 class="card-title">login</h1>

        <?php if ($just_registered): ?>
            <div class="success-message">회원가입이 완료되었습니다. 로그인해주세요.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="email" name="email" class="input-text" placeholder="email"
                   value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>

            <input type="password" name="password" class="input-text" placeholder="Password" required>

            <button type="submit" class="btn">Log in</button>
        </form>

        <!-- 회원가입 버튼 -->
        <a href="register.php">
            <button class="btn-secondary">회원가입</button>
        </a>
    </div>
</div>

</body>
</html>
