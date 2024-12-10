<?php
include("config.php");
include("firebaseRDB.php");
$error = ""; // Variable to hold error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $message = $_POST['message'];

    if (empty($name)) {
        $error = "Name is required";
    } else if (empty($email)) {
        $error = "Email is required";
    } else if (empty($password)) {
        $error = "Password is required";
    } else {
        $frdb = new firebaseRDB($databaseURL);
        $retrieve = $frdb->retrieve("/user", "email", "EQUAL", $email);
        $data = json_decode($retrieve, true);

        if (count($data) > 0) {
            $error = "Email already used";
        } else {
            // Insert new user data into Firebase
            $insert = $frdb->insert("/user", [
                "name" => $name,
                "email" => $email,
                "password" => $password,
                "message" => $message
            ]);

            $result = json_decode($insert, true);

            if ($result) {
                // Redirect to login after successful registration
                header("Location: login.php");
                exit();
            } else {
                $error = "Failed to register. Please try again.";
            }
        }
    }
}
?>

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

        <!-- Error message display -->

        <form method="post" action="signup.php">
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
        <?php if (!empty($error)): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <p style="text-align: center;">
            Already have an account? <a href="login.php">Login</a>
            <br></br>
        </p>
    </div>
</body>
</html>
