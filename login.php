<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: pos.php");
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <!-- Bootstrap v5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-dark d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow-lg p-4 text-center" style="max-width: 350px; background-color:#4e342e; color:#f5f5dc;">
        <!-- Logo -->
        <img src="images/Logo.png" alt="Company Logo" class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;background:#f5f5dc;">
        
        <h2 class="fw-bold mb-4">Welcome Back</h2>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3 position-relative">
                <i class="fa fa-user position-absolute top-50 start-0 translate-middle-y ms-3 text-light"></i>
                <input type="text" name="username" class="form-control ps-5 bg-dark text-light border-secondary" placeholder="Username" required>
                <div class="invalid-feedback">Please enter your username.</div>
            </div>
            
            <div class="mb-3 position-relative">
                <i class="fa fa-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-light"></i>
                <input type="password" name="password" id="password" class="form-control ps-5 bg-dark text-light border-secondary" placeholder="Password" required>
                <i class="fa fa-eye toggle-password position-absolute top-50 end-0 translate-middle-y me-3 text-light" onclick="togglePassword()"></i>
                <div class="invalid-feedback">Please enter your password.</div>
            </div>
            
            <button type="submit" class="btn btn-success w-100 fw-bold">
                <i class="fa fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger mt-3'>$error</div>"; ?>
    </div>

    <script>
        // Bootstrap validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })();

        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const toggleIcon = document.querySelector(".toggle-password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
