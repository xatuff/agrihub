<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up page</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="login">
        <h1>Sign Up</h1>
        <form method="post" action="signup_action.php">
            <label for="name">
                <i class="fas fa-user"></i>
            </label>
            <input type="text" name="name" placeholder="Full Name" id="name" required>
            
            <label for="email">
                <i class="fas fa-envelope"></i>
            </label>
            <input type="text" name="email" placeholder="Email" id="email" required>
            
            <label for="password">
                <i class="fas fa-key"></i>
            </label>
            <input type="password" name="password" placeholder="Password" id="password" required>
            
            <label for="message">
                <i class="fas fa-comment"></i>
            </label>
            <input type="text" name="message" placeholder="Message" id="message">

            <input type="submit" value="SIGN UP">
        </form>
        <p style="text-align: center;">
            Already have an account? <a href="login.php">Login</a>
            <br></br>
        </p>
    </div>
</body>
</html>
