<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GAMESHOP Stock System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="brand" style="justify-content: center; margin-bottom: 1rem;">
                <i class="fas fa-microchip"></i>
                <span>GAME</span>SHOP
            </div>
            <h2>Create Account</h2>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" action="auth.php" method="POST">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Choose a username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Choose a password">
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm your password">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
