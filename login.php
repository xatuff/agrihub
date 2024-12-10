<?php
session_start();
$error = "";
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error after displaying it
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
    <link href="style2.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="login">
        <h1>AgriHub Login Page</h1>
        <form method="post" action="login_action.php">
            <label for="email">
                <i class="fas fa-envelope"></i>
            </label>
            <input type="text" name="email" placeholder="Email" id="email" required>
            
            <label for="password">
                <i class="fas fa-key"></i>
            </label>
            <input type="password" name="password" placeholder="Password" id="password" required>
            
            <input type="submit" value="LOGIN">
        </form>

        <!-- Display error message if present -->
        <?php if ($error): ?>
    <div class="error-box">
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

        <p style="text-align: center;">
            Don't have an account yet? <a href="signup.php">Sign up</a>
            <br></br>
        </p>
    </div>
</body>
</html>