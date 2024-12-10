<?php
include("config.php");
include("firebaseRDB.php");
session_start();

$email = $_POST['email'];
$password = $_POST['password'];

if (empty($email)) {
    $_SESSION['login_error'] = "Email is required";
    header("Location: login2.php");
    exit();
} else if (empty($password)) {
    $_SESSION['login_error'] = "Password is required";
    header("Location: login2.php");
    exit();
} else {
    $frdb = new firebaseRDB($databaseURL);

    // Retrieve the user data
    $retrieve = $frdb->retrieve("/user");
    $data = json_decode($retrieve, true);

    // Debugging: Check what is returned
    if (!is_array($data)) {
        $_SESSION['login2_error'] = "Error: Unable to retrieve user data.";
        header("Location: login.php");
        exit();
    }

    // Check if any user data matches the provided email
    $userFound = false;
    foreach ($data as $key => $user) {
        if (isset($user['email']) && $user['email'] == $email) {
            $userFound = true;
            // Check the password
            if (isset($user['password']) && $user['password'] == $password) {
                $_SESSION['user'] = $user;
                header("location: newdashboard.php");
                exit();
            } else {
                $_SESSION['login_error'] = "Incorrect password";
                header("Location: login2.php");
                exit();
            }
        }
    }

    if (!$userFound) {
        $_SESSION['login_error'] = "Email not registered";
        header("Location: login2.php");
        exit();
    }
}
