<?php
// ... existing login code ...

if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: admin/index.php");
    } elseif ($user['role'] === 'dispatcher') {
        header("Location: dispatcher/index.php");
    } else {
        header("Location: index.php"); // for other roles
    }
    exit();
} 