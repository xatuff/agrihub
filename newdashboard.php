<?php
include("config.php");
include("firebaseRDB.php");

// Set the default time zone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit();
}

$email = $_SESSION['user']['email']; // Email to query user data
$frdb = new firebaseRDB($databaseURL);

// Retrieve user data
$retrieve = $frdb->retrieve("/user", "email", "EQUAL", $email);
$data = json_decode($retrieve, true);

// Find the user key
$userKey = null;
foreach ($data as $key => $user) {
    if (isset($user['email']) && $user['email'] === $email) {
        $userKey = $key;
        break;
    }
}

$statusMessage = ""; // Variable to hold status messages
$userMessages = [];
$retrieveMessages = $frdb->retrieve("/user/$userKey");

if ($userKey && $retrieveMessages) {
    $channel = isset($user['channel']) ? $user['channel'] : "@agrihub4";

    $message = isset($user['message']) ? date('H:i:s', strtotime($user['message'])) : '';
    $message2 = isset($user['message2']) ? date('H:i:s', strtotime($user['message2'])) : '';
    $message3 = isset($user['message3']) ? date('H:i:s', strtotime($user['message3'])) : '';
    $userData = json_decode($retrieveMessages, true);

    // Check for keys that match the 'message[number]' pattern
    foreach ($userData as $key => $value) {
        if (preg_match('/^message\d+$/', $key)) { // Regex to match 'message1', 'message2', etc.
            // Ensure $value is a string and extract the correct time value if it's an array
            if (is_array($value)) {
                // Handle the case where the value is an array
                $userMessages[$key] = isset($value['time']) ? date('H:i:s', strtotime($value['time'])) : '';
            } else {
                // If it's a string, directly apply strtotime
                $userMessages[$key] = date('H:i:s', strtotime($value));
            }
        }
    }
}




// Handling deletion logic
if (isset($_POST['deleteEntry'])) {
    $randomID1 = $_POST['randomID1'];
    $randomID2 = $_POST['randomID2'];

    // Delete the specific (randomID**) entry under (randomID*)
    $deleteResult = $frdb->delete("user/$userKey/$randomID1", $randomID2);

    if ($deleteResult) {
        $statusMessage = "Entry with IDs $randomID1 -> $randomID2 deleted successfully.<br>";
    } else {
        $statusMessage = "Failed to delete entry.<br>";
    }
}

    

    // Retrieve all entries for the current user
    $retrieveUserEntries = $frdb->retrieve("/user/$userKey");
    $userEntries = json_decode($retrieveUserEntries, true);

    // Check if userEntries is an array and contains the nested random IDs
    $nestedEntries = [];
    if (is_array($userEntries)) {
        foreach ($userEntries as $randomID1 => $entry) {
            if (is_array($entry)) {
                foreach ($entry as $randomID2 => $nestedEntry) {
                    if (is_array($nestedEntry)) {
                        $nestedEntries[$randomID1][$randomID2] = $nestedEntry;
                    }
                }
            }
        }
    }
 else {
    echo "User not found.<br>";
}
$name = isset($user['name']) ? $user['name'] : 'Unknown';
if (isset($_POST['fetchData2']) || isset($_POST['fetchDataManual'])) {
    header("Refresh:0"); // Refresh the page to reflect changes
    // Count the current number of rows in the table
    $rowCount = 0;
    foreach ($nestedEntries as $entries) {
        $rowCount += count($entries);
    }

    // Check if row count is 30 or more
    if ($rowCount >= 30) {
        $statusMessage .= "Unable to add new data. Delete a row first.<br>";
    } else {

        // Proceed with fetching and inserting new data if row count is less than 7
        // Retrieve data from the /data path
$retrieveData = $frdb->retrieve("/data");
$data = json_decode($retrieveData, true);

// Retrieve the camera value from the image path
$retrieveImageData = $frdb->retrieve("/image");
$imageData = json_decode($retrieveImageData, true);
$cameraValue = isset($imageData['camera']) ? $imageData['camera'] : "N/A"; // Default to "N/A" if not found

$retrieveImageData2 = $frdb->retrieve("/servo2");
$imageData2 = json_decode($retrieveImageData2, true);
$cameraValue2 = isset($imageData2['ipservo']) ? $imageData2['ipservo'] : "N/A"; // Default to "N/A" if not found

// Retrieve the soil moisture value etc from the soil path
$retrieveData2 = $frdb->retrieve("/soil");
$data2 = json_decode($retrieveData2, true);

$retrieveDataGPS = $frdb->retrieve("/gps");
$datagps = json_decode($retrieveDataGPS, true);

//Servo

$retrieveservo = $frdb->retrieve("/servo");
$servoData = json_decode($retrieveservo, true);
$servoip = isset($servoData['servo']) ? $servoData['servo'] : "N/A";  // Get the servo IP


// Generate a new random ID
$newID = uniqid();

// Prepare the data to insert
$currentTime = date('Y-m-d H:i:s');
$userData = [
    "time" => $currentTime,
    "temperature" => isset($data['temperature']) ? $data['temperature'] : "N/A",
    "humidity" => isset($data['humidity']) ? $data['humidity'] : "N/A",
    "pressure" => isset($data['pressure']) ? $data['pressure'] : "N/A",
    "camera" => $cameraValue ,// Add the camera value here
    "soilmois" => isset($data2['soilmoisture']) ? $data2['soilmoisture'] : "N/A",
    "soilhumidity" => isset($data2['soilhumidity']) ? $data2['soilhumidity'] : "N/A",
    "soiltemperature" => isset($data2['soiltemperature']) ? $data2['soiltemperature'] : "N/A",
    "gpstrackedlongititude" => isset($datagps['Longitude']) ? $datagps['Longitude'] : "N/A",
    "gpstrackedlatitude" => isset($datagps['Latitude']) ? $datagps['Latitude'] : "N/A",

];

// Insert the new data under the user ID
$insert = $frdb->insert("user/$userKey/$newID", $userData);


if ($insert) {
    $statusMessage .= "Data inserted successfully under new ID: $newID<br>";
    
    // Send Telegram notification
    $userName = $name;  // Use the user's name from your session or database
    $time = $currentTime;  // Time of data insertion
    $temperature = isset($data['temperature']) ? $data['temperature'] : "N/A";  // Retrieved temperature
    $humidity = isset($data['humidity']) ? $data['humidity'] : "N/A";  // Retrieved humidity
    $pressure = isset($data['pressure']) ? $data['pressure'] : "N/A";  // Retrieved pressure
    $soilmois = isset($data2['soilmoisture']) ? $data2['soilmoisture'] : "N/A";
    $soilhumidity= isset($data2['soilhumidity']) ? $data2['soilhumidity'] : "N/A";
    $soiltemperature= isset($data2['soiltemperature']) ? $data2['soiltemperature'] : "N/A";
    // Create the message content
    $content = "Hello, $name,\n";
    $content .= "Time: $time\n";
    $content .= "Temperature: $temperature\n";
    $content .= "Humidity: $humidity\n";
    $content .= "Pressure: $pressure \n\n";
    $content .= "Soil Content Information: \n";
    $content .= "Soil Moisture: $soilmois\n";
    $content .= "Soil Humidity: $soilhumidity\n";
    $content .= "Soil Temeprature: $soiltemperature\n";
    

    // Your bot's token that you got from @BotFather
    $apiToken = "7543181789:AAF2P1v1dVbADU32P8j41_e-GsPyCD9cr48";  // Replace with your actual bot token
    
    // Send the text message to Telegram first
    $dataMessage = [
        'chat_id' => $channel,  // Replace with your Telegram chat ID or channel
        'text' => $content,
        'parse_mode' => 'HTML'
    ];
    
    // Send the text message using file_get_contents
    $responseMessage = file_get_contents("https://api.telegram.org/bot$apiToken/sendMessage?" . http_build_query($dataMessage));

    // Now, retrieve the base64 image data from Firebase
    $retrieveImageData = $frdb->retrieve("/image");
    $imageData = json_decode($retrieveImageData, true);
    $cameraValue = isset($imageData['camera']) ? $imageData['camera'] : "N/A";  // Get the base64 image string

    // Check if base64 image exists
    if ($cameraValue != "N/A") {
        // Decode the base64 image
        $imageData = base64_decode($cameraValue);

        // Check if decoding is successful
        if ($imageData === false) {
            $statusMessage .= "Failed to decode the base64 image.<br>";
            exit;
        }

        // Save the image to a temporary file
        $imagePath = 'temp_image.jpg';  // You can change the extension based on the image format
        file_put_contents($imagePath, $imageData);

        // Check if the file was saved successfully
        if (!file_exists($imagePath)) {
            $statusMessage .= "Failed to save the image.<br>";
            exit;
        }

        // Prepare the data for Telegram API (sending the image)
        $data = [
            'chat_id' => $channel,  // Replace with your Telegram chat ID or channel
            'photo' => new CURLFile(realpath($imagePath)),
            'caption' => "Here's the latest image captured!",
            'parse_mode' => 'HTML'
        ];

        // Send the image to Telegram using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$apiToken/sendPhoto");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // Check for errors with the cURL request
        if ($response === false) {
            $statusMessage .= "cURL Error: " . curl_error($ch) . "<br>";
        } else {
            $statusMessage .= "Image sent successfully to Telegram!<br>";
        }

        // Close cURL
        curl_close($ch);

        // Delete the temporary image file after sending
        unlink($imagePath);
    } else {
        $statusMessage .= "No image found to send.<br>";
    }
    
} else {
    $statusMessage .= "Failed to insert data.<br>";
}


           

        } 
    }


// Ensure the user array is set


// Get the current date and time for initial load
//to BE WORKED!!

$initialTime = date('Y-m-d H:i:s');

if (isset($_POST['downloadTodayImages'])) {
    // Initialize variables
    $zip = new ZipArchive();
    $zipFile = 'today_images.zip';
    $todayDate = date('Y-m-d'); // Format today's date as YYYY-MM-DD
    $images = []; // Array to store image data and file names

    // Loop through entries and filter by today's date
    foreach ($nestedEntries as $randomID1 => $entries) {
        foreach ($entries as $randomID2 => $entry) {
            $time = isset($entry['time']) ? $entry['time'] : '';
            $cameraValue = isset($entry['camera']) ? $entry['camera'] : '';

            // Check if the time matches today's date
            if (strpos($time, $todayDate) === 0 && $cameraValue !== 'N/A') {
                // Decode base64 and create a temporary JPEG file
                $imageData = base64_decode($cameraValue);
                $imageName = "image_" . $randomID1 . "_" . $randomID2 . ".jpg";
                
                // Save image data and file name for later zip creation
                $images[$imageName] = $imageData;
            }
        }
    }

    // Create a zip file and add images
    if (!empty($images) && $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        foreach ($images as $imageName => $imageData) {
            $zip->addFromString($imageName, $imageData);
        }
        $zip->close();

        // Set headers to download the ZIP file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . basename($zipFile));
        header('Content-Length: ' . filesize($zipFile));
        
        // Output the ZIP file for download
        readfile($zipFile);

        // Delete the zip file from the server after download
        unlink($zipFile);
        exit;
    } 
}

if (isset($_POST['update_gps'])) {
    // Retrieve GPS data from Firebase
    $retrieveDataGPS = $frdb->retrieve("/gps");
    $datagps = json_decode($retrieveDataGPS, true);

    // Extract latitude and longitude
    $latitude = isset($datagps['Latitude']) ? $datagps['Latitude'] : null;
    $longitude = isset($datagps['Longitude']) ? $datagps['Longitude'] : null;
    $currentTime = date('Y-m-d H:i:s');

    if ($latitude && $longitude) {
        // Update session variables and Firebase database
        $_SESSION['user']['currentlat'] = $latitude;
        $_SESSION['user']['currentlng'] = $longitude;
        $_SESSION['user']['lastgpstracked'] = $currentTime;

        $updateGPSData = [
            "currentlat" => $latitude,
            "currentlng" => $longitude,
            "lastgpstracked" => $currentTime
        ];

        $frdb->update("user/$userKey", $updateGPSData);
        header("Refresh:0"); // Refresh the page to update map with new location
        exit();
    } else {
        echo "GPS data not found.";
    }
}
$currentDate = new DateTime();

// Initialize variables for the 5 months
$month1avgtmp = $month2avgtmp = $month3avgtmp = $month4avgtmp = $month5avgtmp = 0;
$month1avghum = $month2avghum = $month3avghum = $month4avghum = $month5avghum = 0;
$month1avgprs = $month2avgprs = $month3avgprs = $month4avgprs = $month5avgprs = 0;
$month1avgsoi = $month2avgsoi = $month3avgsoi = $month4avgsoi = $month5avgsoi = 0;

// Loop through the last 5 months (starting from the current month)
for ($i = 0; $i < 5; $i++) {
    // Get the date range for the current month
    $monthStart = (clone $currentDate)->modify("-$i months")->modify('first day of this month')->setTime(0, 0, 0);
    $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
    
    // Initialize variables for temperature, humidity, and pressure totals and counts
    $totalTemperature = $totalHumidity = $totalPressure = $totalMos = 0;
    $temperatureCount = $humidityCount = $pressureCount = $mosCount = 0;
    
    // Loop through all entries
    foreach ($nestedEntries as $randomID1 => $entry) {
        foreach ($entry as $randomID2 => $nestedEntry) {
            if (isset($nestedEntry['time'])) {
                $entryDate = new DateTime($nestedEntry['time']);
                
                // Check if the entry's date is within the current month
                if ($entryDate >= $monthStart && $entryDate <= $monthEnd) {
                    // Temperature
                    if (isset($nestedEntry['temperature'])) {
                        $totalTemperature += $nestedEntry['temperature'];
                        $temperatureCount++;
                    }
                    // Humidity
                    if (isset($nestedEntry['humidity'])) {
                        $totalHumidity += $nestedEntry['humidity'];
                        $humidityCount++;
                    }
                    // Pressure
                    if (isset($nestedEntry['pressure'])) {
                        $totalPressure += $nestedEntry['pressure'];
                        $pressureCount++;
                    }

                    if (isset($nestedEntry['soilmois'])) {
                        $totalMos += $nestedEntry['soilmois'];
                        $mosCount++;
                    }
                }
            }
        }
    }
    
    // Calculate averages for the current month
    $avgTemperature = ($temperatureCount > 0) ? $totalTemperature / $temperatureCount : 0;
    $avgHumidity = ($humidityCount > 0) ? $totalHumidity / $humidityCount : 0;
    $avgPressure = ($pressureCount > 0) ? $totalPressure / $pressureCount : 0;
    $avgMos = ($mosCount > 0) ? $totalMos / $mosCount : 0;

    // Assign averages to the respective variables for the correct month
    switch ($i) {
        case 0:
            $month1avgtmp = $avgTemperature;
            $month1avghum = $avgHumidity;
            $month1avgprs = $avgPressure;
            $month1avgsoi = $avgMos;
            break;
        case 1:
            $month2avgtmp = $avgTemperature;
            $month2avghum = $avgHumidity;
            $month2avgprs = $avgPressure;
            $month2avgsoi = $avgMos;
            break;
        case 2:
            $month3avgtmp = $avgTemperature;
            $month3avghum = $avgHumidity;
            $month3avgprs = $avgPressure;
            $month3avgsoi = $avgMos;
            break;
        case 3:
            $month4avgtmp = $avgTemperature;
            $month4avghum = $avgHumidity;
            $month4avgprs = $avgPressure;
            $month4avgsoi = $avgMos;
            break;
        case 4:
            $month5avgtmp = $avgTemperature;
            $month5avghum = $avgHumidity;
            $month5avgprs = $avgPressure;
            $month5avgsoi = $avgMos;
            break;
    }
}
$locationset  = isset($user['locationset']) ? $user['locationset'] : "Kluang";
$apiKey2 = "c26b2ca27b0257c9999ba8f46fc56676";
$apiUrl2 = "https://api.openweathermap.org/data/2.5/forecast?q={$locationset}&appid={$apiKey2}&units=metric";

// Get the weather data
$response2 = file_get_contents($apiUrl2);
$weatherData = json_decode($response2, true);

// Handle any errors with fetching the data
if ($weatherData && $weatherData['cod'] !== "200") {
    $errorMessage = "Unable to fetch weather data.";
}

$plant="chilli";

//User Setting ( Still Testing in 1 DECEMBER 2024 ) 

$locationset  = isset($user['locationset']) ? $user['locationset'] : "Kluang";
$plantset = isset($user['plant']) ? $user['plant'] : "Red Chilli";

if (isset($_POST['save_settings'])) {
    // Save Plant
    if (!empty($_POST['plantset'])) {
        $plantset = $_POST['plantset'];
        $frdb->update("user/$userKey", ["plant" => $plantset]);
        $statusMessage = "Plant saved successfully!";
    }

    // Save Location
    if (!empty($_POST['locationset'])) {
        $locationset = $_POST['locationset'];
        $frdb->update("user/$userKey", ["locationset" => $locationset]);
        $statusMessage = isset($statusMessage) 
            ? $statusMessage . " Location saved successfully!" 
            : "Location saved successfully!";
    }

    // Save Channel
    if (!empty($_POST['channel'])) {
        $channel = $_POST['channel'];
        $frdb->update("user/$userKey", ["channel" => $channel]);
        $statusMessage = isset($statusMessage) 
            ? $statusMessage . " Channel updated successfully!" 
            : "Channel updated successfully!";
    }

    // Save Messages
    if (!empty($_POST['messages'])) {
        foreach ($_POST['messages'] as $key => $msg) {
            $formattedTime = date('H:i:s', strtotime($msg));
            $frdb->update("user/$userKey", [$key => $formattedTime]);
        }
        $statusMessage = isset($statusMessage) 
            ? $statusMessage . " Messages updated successfully!" 
            : "Messages updated successfully!";
    }

    // Redirect to refresh the page and prevent form resubmission
    header("Refresh:0");
    exit();

    //DELETE MESSAGE KEY

    
}

if (isset($_POST['delete_message'])) {
    $messageKey = $_POST['messageKey']; // Get the message key to be deleted

    // Delete the specific message from Firebase
    $deleteResult = $frdb->delete("user/$userKey/$messageKey", null);  // Pass `null` or the required second argument
    header("Refresh:0"); // Refresh the page to reflect changes

    foreach ($userData as $key => $value) {
        if (preg_match('/^message\d+$/', $key)) {
            // Debugging: Output value to inspect its structure
            var_dump($value);  // Check the structure of $value
            
            if (is_array($value)) {
                // If $value is an array, look for the time field inside it
                if (isset($value['time'])) {
                    $userMessages[$key] = date('H:i:s', strtotime($value['time']));
                } else {
                    // If there's no time field, assign an empty string or handle accordingly
                    $userMessages[$key] = '';
                }
            } else {
                // If $value is a string, apply strtotime directly
                $userMessages[$key] = date('H:i:s', strtotime($value));
            }
        }
    }
}


// ADD MESSAGE KEY
if (isset($_POST['add_message']) && $_POST['newMessageTime'] !== "") {
    // Get the new message input from the form
    $newMessage = $_POST['newMessageTime'];

    // Get the next message number (using count to determine where to place the new message)
    $nextMessageNumber = count($userMessages) + 1;  // Automatically calculate the next message number

    // Directly update the Firebase database with the new message
    $updateResult = $frdb->update("/user/$userKey", ["message$nextMessageNumber" => $newMessage]);

    // Check if the update was successful
    if ($updateResult) {
        // Update session or provide feedback to the user
        $_SESSION['user']['message'] = $newMessage;
        $statusMessage = "Message message$nextMessageNumber added successfully with value: '$newMessage'!";
        header("Refresh:0"); // Refresh the page to reflect the changes
        exit();
    } else {
        // If the update failed
        $statusMessage = "Failed to add new message.";
    }
}





?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="newdashboard11.css"> <!-- Link to your external CSS file -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  
   <!-- Sidebar -->
   <div id="mySidebar" class="sidebar">
    <button class="closebtn" onclick="closeNav()">&#9776; Close Menu</button>
    <br><br><br><br>
    <a href="#home" onclick="showSection('home')"><i class="fas fa-home"></i> Home Overview</a>
    <a href="#data" onclick="showSection('data')"><i class="fas fa-chart-line"></i> Data Entries</a>
    <a href="#map" onclick="showSection('map')"><i class="fas fa-map-marker-alt"></i> Google Map</a>
    <a href="#ai" onclick="showSection('ai')"><i class="fas fa-robot"></i> Artificial Intelligence</a>
    <a href="#live" onclick="showSection('live')"><i class="fas fa-video"></i> Live Streaming </a>
    <a href="#setting" onclick="showSection('setting')"><i class="fas fa-gear"></i> Settings </a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
<div id="main">
    <button class="openbtn" onclick="openNav()">&#9776; Menu</button>
<!-- Main Content -->
<section id="home" class="content-section">
    <h2>Welcome to your dashboard!</h2>
    <p>Hello <b><?php echo $name; ?></b>, your current message is: <b><?php echo $message; ?></b>.</p>
    <p>Current time: <b id="currentTime"><?php echo $initialTime; ?></b></p>
    <div class="button-container">
        <form method="POST" action="">
            <input type="submit" name="fetchDataManual" value="Fetch Data" class="button-style">
        </form>
        

        <form method="POST" action="">
            <input type="hidden" name="downloadTodayImages" value="1">
            <input type="submit" value="Download Today's Images" class="button-style">
        </form>
    </div>
    <div id="notification-box" class="notification-box hidden"></div>


    <div id="weather-forecast" class="weather-section">
            <h3>Weather Forecast for <?php echo htmlspecialchars($locationset); ?></h3>
            <div id="forecast" class="forecast">
                <?php
                if (isset($weatherData) && $weatherData['cod'] === "200") {
                    // Iterate through the weather forecast for the next 5 days (3-hour intervals)
                    $forecastCount = 0;
                    foreach ($weatherData['list'] as $item) {
                        if ($forecastCount == 5) break; // Show only 5 entries (for the next 5 days)
                        
                        // Check if it's the right time to display
                        $date = new DateTime($item['dt_txt']);
                        $time = $date->format('H:i');
                        if ($time === "12:00") {
                            $forecastCount++;
                            $icon = $item['weather'][0]['icon'];
                            $description = $item['weather'][0]['description'];
                            $temp = $item['main']['temp'];
                            $humidity = $item['main']['humidity'];
                            $windSpeed = $item['wind']['speed'];
                            echo "
                            <div class='forecast-item'>
                                <p><strong>{$date->format('l, d M Y')}</strong></p>
                                <img src='http://openweathermap.org/img/wn/{$icon}.png' alt='{$description}'>
                                <p>{$description}</p>
                                <p>Temp: {$temp}°C</p>
                                <p>Humidity: {$humidity}%</p>
                                <p>Wind: {$windSpeed} m/s</p>
                            </div>";
                        }
                    }
                } else {
                    echo "<p>{$errorMessage}</p>";
                }
                ?>
            </div>
            </div>


    <div class="content">
    <div id="graph-container">
        <div id="charts-grid">
            <!-- Temperature Chart -->
            <div class="chart-container">
                <div class="input-container">
                    <input type="number" id="value1" placeholder="Week 1 Temp (°C)" class="temp-input" value="<?php echo $month1avgtmp; ?>" style="display:none;">
                    <input type="number" id="value2" placeholder="Week 2 Temp (°C)" class="temp-input" value="<?php echo $month2avgtmp; ?>" style="display:none;">
                    <input type="number" id="value3" placeholder="Week 3 Temp (°C)" class="temp-input" value="<?php echo $month3avgtmp; ?>" style="display:none;">
                    <input type="number" id="value4" placeholder="Week 4 Temp (°C)" class="temp-input" value="<?php echo $month4avgtmp; ?>" style="display:none;">
                    <input type="number" id="value5" placeholder="Week 5 Temp (°C)" class="temp-input" value="<?php echo $month5avgtmp; ?>" style="display:none;">
                </div>
                <canvas id="weatherChart"></canvas>
            </div>
            
            <!-- Humidity Chart -->
            <div class="chart-container">
                <div class="input-container">
                    <input type="number" id="humidityValue1" value="<?php echo $month1avghum; ?>" style="display:none;">
                    <input type="number" id="humidityValue2" value="<?php echo $month2avghum; ?>" style="display:none;">
                    <input type="number" id="humidityValue3" value="<?php echo $month3avghum; ?>" style="display:none;">
                    <input type="number" id="humidityValue4" value="<?php echo $month4avghum; ?>" style="display:none;">
                    <input type="number" id="humidityValue5" value="<?php echo $month5avghum; ?>" style="display:none;">
                </div>
                <canvas id="humidityChart"></canvas>
            </div>
            
            <!-- Pressure Chart -->
            <div class="chart-container">
                <div class="input-container">
                    <input type="number" id="pressure1" placeholder="Week 1 Pressure (hPa)" class="pressure-input" value="<?php echo $month1avgprs; ?>" style="display:none;">
                    <input type="number" id="pressure2" placeholder="Week 2 Pressure (hPa)" class="pressure-input" value="<?php echo $month2avgprs; ?>" style="display:none;">
                    <input type="number" id="pressure3" placeholder="Week 3 Pressure (hPa)" class="pressure-input" value="<?php echo $month3avgprs; ?>" style="display:none;">
                    <input type="number" id="pressure4" placeholder="Week 4 Pressure (hPa)" class="pressure-input" value="<?php echo $month4avgprs; ?>" style="display:none;">
                    <input type="number" id="pressure5" placeholder="Week 5 Pressure (hPa)" class="pressure-input" value="<?php echo $month5avgprs; ?>" style="display:none;">
                </div>
                <canvas id="pressureChart"></canvas>
            </div>
            
            <!-- Soil Moisture Chart -->
            <div class="chart-container">
                <div class="input-container">
                    <input type="number" id="soil1" placeholder="Week 1 Soil" class="soil-input" value="<?php echo $month1avgsoi; ?>" style="display:none;">
                    <input type="number" id="soil2" placeholder="Week 2 Soil" class="soil-input" value="<?php echo $month2avgsoi; ?>" style="display:none;">
                    <input type="number" id="soil3" placeholder="Week 3 Soil" class="soil-input" value="<?php echo $month3avgsoi; ?>" style="display:none;">
                    <input type="number" id="soil4" placeholder="Week 4 Soil" class="soil-input" value="<?php echo $month4avgsoi; ?>" style="display:none;">
                    <input type="number" id="soil5" placeholder="Week 5 Soil" class="soil-input" value="<?php echo $month5avgsoi; ?>" style="display:none;">
                </div>
                <canvas id="soilmoistureChart"></canvas>
            </div>
        </div>
    </div>

    <p class="footer">Weather Data Visualization for Malaysia</p>
    </div>

    <h2>Agrihub AI Data Analysis
    <img src="icon.png" alt="Icon" style="width: 20px; height: 20px; margin-left: 5px; vertical-align: middle;">
</h2>
    <div id="response" style="margin-top: 20px;"></div>

</h2>


</section>


<script>
    //THIS IS FOR TEMEPERATURE 
    const currentDate = new Date();

    // Function to get the name of a month from the current date
    function getMonthName(monthOffset) {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const monthIndex = (currentDate.getMonth() - monthOffset + 12) % 12;
        return months[monthIndex];
    }

    // Initialize the line chart
    const ctx = document.getElementById('weatherChart').getContext('2d');
    const weatherChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                getMonthName(0),  // Month 5 (current month - 4)
                getMonthName(1),  // Month 4 (current month - 3)
                getMonthName(2),  // Month 3 (current month - 2)
                getMonthName(3),  // Month 2 (current month - 1)
                getMonthName(4)   // Month 1 (current month - 0)
            ],
            datasets: [{
                label: 'Average Monthly Temperature (°C)',
                data: [], // Placeholder for dynamic data
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderWidth: 3,
                fill: true,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#3b82f6',
                pointRadius: 6,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `${tooltipItem.raw}°C`;
                        }
                    },
                    backgroundColor: '#3b82f6',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 10
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#fff',
                        font: { size: 14 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 50,
                    ticks: {
                        color: '#fff',
                        font: { size: 14 }
                    },
                    title: {
                        display: true,
                        text: 'Temperature (°C)',
                        color: '#fff',
                        font: { size: 16, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff',
                        font: { size: 14 }
                    },
                    title: {
                        display: true,
                        text: 'Month',
                        color: '#fff',
                        font: { size: 16, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    }
                }
            }
        }
    });

    // Function to update the chart with input values
    function updateGraph() {
        const values = [
            parseFloat(document.getElementById('value1').value) || 0,
            parseFloat(document.getElementById('value2').value) || 0,
            parseFloat(document.getElementById('value3').value) || 0,
            parseFloat(document.getElementById('value4').value) || 0,
            parseFloat(document.getElementById('value5').value) || 0
        ];

        // Update chart data
        weatherChart.data.datasets[0].data = values;

        // Refresh the chart
        weatherChart.update();

        // Ensure the chart resizes to its container
        weatherChart.resize();
    }

    // Call updateGraph to set initial values when the page loads
    updateGraph();
</script>

<script>
    //THIS IS FOR HUMIDITY
    // Humidity Chart
const humidityCtx = document.getElementById('humidityChart').getContext('2d');
const humidityChart = new Chart(humidityCtx, {
    type: 'line',
    data: {
        labels: [
            getMonthName(0),
            getMonthName(1),
            getMonthName(2),
            getMonthName(3),
            getMonthName(4)
        ],
        datasets: [{
            label: 'Average Monthly Humidity (%)',
            data: [], // Placeholder for dynamic data
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.2)',
            borderWidth: 3,
            fill: true,
            pointBackgroundColor: '#22c55e',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#22c55e',
            pointRadius: 6,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.raw}%`;
                    }
                },
                backgroundColor: '#22c55e',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#15803d',
                borderWidth: 1
            },
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: '#fff',
                    font: { size: 14 }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    color: '#fff',
                    font: { size: 14 }
                },
                title: {
                    display: true,
                    text: 'Humidity (%)',
                    color: '#fff',
                    font: { size: 16, weight: 'bold' }
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.2)'
                }
            },
            x: {
                ticks: {
                    color: '#fff',
                    font: { size: 14 }
                },
                title: {
                    display: true,
                    text: 'Month',
                    color: '#fff',
                    font: { size: 16, weight: 'bold' }
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.2)'
                }
            }
        }
    }
});

// Function to update the humidity chart
function updateHumidityGraph() {
    const humidityValues = [
        parseFloat(document.getElementById('humidityValue1').value) || 0,
        parseFloat(document.getElementById('humidityValue2').value) || 0,
        parseFloat(document.getElementById('humidityValue3').value) || 0,
        parseFloat(document.getElementById('humidityValue4').value) || 0,
        parseFloat(document.getElementById('humidityValue5').value) || 0
    ];

    humidityChart.data.datasets[0].data = humidityValues;
    humidityChart.update();
}

// Call the update function on page load
updateHumidityGraph();

</script>

<script>
    // Get the current date
const pressureDate = new Date();

// Function to get the name of a month
function getPressureMonthName(monthOffset) {
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const monthIndex = (pressureDate.getMonth() - monthOffset + 12) % 12;
    return months[monthIndex];
}

// Initialize the pressure chart
const pressureCtx = document.getElementById('pressureChart').getContext('2d');
const pressureChart = new Chart(pressureCtx, {
    type: 'line',
    data: {
        labels: [
            getPressureMonthName(0), // Month 5 (current month - 4)
            getPressureMonthName(1), // Month 4 (current month - 3)
            getPressureMonthName(2), // Month 3 (current month - 2)
            getPressureMonthName(3), // Month 2 (current month - 1)
            getPressureMonthName(4)  // Month 1 (current month - 0)
        ],
        datasets: [{
            label: 'Average Monthly Pressure (hPa)',
            data: [], // Placeholder for dynamic data
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.2)',
            borderWidth: 3,
            fill: true,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#f59e0b',
            pointRadius: 6,
            tension: 0.4 // Smooth curves
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.raw} hPa`;
                    }
                },
                backgroundColor: '#f59e0b',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#d97706',
                borderWidth: 1,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 12 },
                padding: 10
            },
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: '#fff',
                    font: { size: 14 }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 1100, // Adjust max pressure value as needed
                ticks: {
                    color: '#fff',
                    font: { size: 14 }
                },
                title: {
                    display: true,
                    text: 'Pressure (hPa)',
                    color: '#fff',
                    font: { size: 16, weight: 'bold' }
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.2)'
                }
            },
            x: {
                ticks: {
                    color: '#fff',
                    font: { size: 14 }
                },
                title: {
                    display: true,
                    text: 'Month',
                    color: '#fff',
                    font: { size: 16, weight: 'bold' }
                },
                grid: {
                    color: 'rgba(200, 200, 200, 0.2)'
                }
            }
        }
    }
});

// Function to update the pressure chart with input values
function updatePressureGraph() {
    const values = [
        parseFloat(document.getElementById('pressure1').value) || 0,
        parseFloat(document.getElementById('pressure2').value) || 0,
        parseFloat(document.getElementById('pressure3').value) || 0,
        parseFloat(document.getElementById('pressure4').value) || 0,
        parseFloat(document.getElementById('pressure5').value) || 0
    ];

    // Update chart data
    pressureChart.data.datasets[0].data = values;

    // Refresh the chart
    pressureChart.update();

    // Ensure the chart resizes to its container
    pressureChart.resize();
}

// Call updatePressureGraph to set initial values when the page loads
updatePressureGraph();
</script>

<script>
    // Get the current date for month names
    const soilDate = new Date();

    // Function to get the name of a month
    function getSoilMonthName(monthOffset) {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const monthIndex = (soilDate.getMonth() - monthOffset + 12) % 12;
        return months[monthIndex];
    }

    // Initialize the soil moisture chart
    const soilCtx = document.getElementById('soilmoistureChart').getContext('2d');
    const soilChart = new Chart(soilCtx, {
        type: 'line',
        data: {
            labels: [
                getSoilMonthName(0), // Month 5 (current month - 4)
                getSoilMonthName(1), // Month 4 (current month - 3)
                getSoilMonthName(2), // Month 3 (current month - 2)
                getSoilMonthName(3), // Month 2 (current month - 1)
                getSoilMonthName(4)  // Month 1 (current month - 0)
            ],
            datasets: [{
                label: 'Average Monthly Soil Moisture',
                data: [], // Placeholder for dynamic data
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderWidth: 3,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#10b981',
                pointRadius: 6,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `${tooltipItem.raw} units`; // Display the units for soil moisture
                        }
                    },
                    backgroundColor: '#10b981',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#047857',
                    borderWidth: 1,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 10
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#fff',
                        font: { size: 14 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 3000, // Set max value based on soil moisture range (0 to 3000)
                    ticks: {
                        color: '#fff',
                        font: { size: 14 }
                    },
                    title: {
                        display: true,
                        text: 'Soil Moisture (units)', // Customize as needed
                        color: '#fff',
                        font: { size: 16, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff',
                        font: { size: 14 }
                    },
                    title: {
                        display: true,
                        text: 'Month',
                        color: '#fff',
                        font: { size: 16, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    }
                }
            }
        }
    });

    // Function to update the soil moisture chart with input values
    function updateSoilMoistureGraph() {
        const values = [
            parseFloat(document.getElementById('soil1').value) || 0,
            parseFloat(document.getElementById('soil2').value) || 0,
            parseFloat(document.getElementById('soil3').value) || 0,
            parseFloat(document.getElementById('soil4').value) || 0,
            parseFloat(document.getElementById('soil5').value) || 0
        ];

        // Update chart data
        soilChart.data.datasets[0].data = values;

        // Refresh the chart
        soilChart.update();

        // Ensure the chart resizes to its container
        soilChart.resize();
    }

    // Call updateSoilMoistureGraph to set initial values when the page loads
    updateSoilMoistureGraph();
</script>




    <!-- Data Entries Section -->
    <section id="data" class="content-section">
    <h2>Data Entries</h2>
    <div class="content">
        <p>View and manage your data entries here.</p>
        <!-- Data table -->
        <div class="table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Temperature</th>
                        <th>Humidity</th>
                        <th>Pressure</th>
                        <th>S. Moisture</th>
                        <th>S. Temperature</th>
                        <th>S. Humidity</th>
                        <th>Camera</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Initialize totals and count
                    $totalTemperature = 0;
                    $totalHumidity = 0;
                    $totalPressure = 0;
                    $totalSoilMoisture = 0;
                    $totalSoilTemperature = 0;
                    $totalSoilHumidity = 0;
                    $entryCount = 0;

                    if (!empty($nestedEntries)) {
                        foreach ($nestedEntries as $randomID1 => $entries) {
                            foreach ($entries as $randomID2 => $entry) {
                                // Standard data
                                $time = isset($entry['time']) ? htmlspecialchars($entry['time']) : 'N/A';
                                $temperature = isset($entry['temperature']) ? htmlspecialchars($entry['temperature']) : 'N/A';
                                $humidity = isset($entry['humidity']) ? htmlspecialchars($entry['humidity']) : 'N/A';
                                $pressure = isset($entry['pressure']) ? htmlspecialchars($entry['pressure']) : 'N/A';

                                // Soil data
                                $soilMoisture = isset($entry['soilmois']) ? htmlspecialchars($entry['soilmois']) : 'N/A';
                                $soilTemperature = isset($entry['soiltemperature']) ? htmlspecialchars($entry['soiltemperature']) : 'N/A';
                                $soilHumidity = isset($entry['soilhumidity']) ? htmlspecialchars($entry['soilhumidity']) : 'N/A';

                                // Camera
                                $cameraValue = isset($entry['camera']) ? htmlspecialchars($entry['camera']) : 'N/A';

                                // Increment totals for averaging
                                if (
                                    is_numeric($temperature) && is_numeric($humidity) && is_numeric($pressure) &&
                                    is_numeric($soilMoisture) && is_numeric($soilTemperature) && is_numeric($soilHumidity)
                                ) {
                                    $totalTemperature += (float)$temperature;
                                    $totalHumidity += (float)$humidity;
                                    $totalPressure += (float)$pressure;
                                    $totalSoilMoisture += (float)$soilMoisture;
                                    $totalSoilTemperature += (float)$soilTemperature;
                                    $totalSoilHumidity += (float)$soilHumidity;
                                    $entryCount++;
                                }

                                // Display table row
                                echo "<tr>";
                                echo "<td>$time</td>";
                                echo "<td>$temperature</td>";
                                echo "<td>$humidity</td>";
                                echo "<td>$pressure</td>";
                                echo "<td>$soilMoisture</td>";
                                echo "<td>$soilTemperature</td>";
                                echo "<td>$soilHumidity</td>";
                                echo "<td>";
                                if ($cameraValue !== 'N/A') {
                                    echo "<form method='POST' action='javascript:void(0);' onsubmit='viewImage(\"$cameraValue\")'>";
                                    echo "<input type='submit' value='View' class='view-button'>";
                                    echo "</form>";
                                } else {
                                    echo "No Image";
                                }
                                echo "</td>";
                                echo "<td>";
                                echo "<form method='POST' action=''>";
                                echo "<input type='hidden' name='randomID1' value='" . htmlspecialchars($randomID1) . "'>";
                                echo "<input type='hidden' name='randomID2' value='" . htmlspecialchars($randomID2) . "'>";
                                echo "<input type='submit' name='deleteEntry' class='delete-button' value='Delete'>";
                                echo "</form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }

                        // Calculate averages
                        $averageTemperature = $entryCount > 0 ? $totalTemperature / $entryCount : 0;
                        $averageHumidity = $entryCount > 0 ? $totalHumidity / $entryCount : 0;
                        $averagePressure = $entryCount > 0 ? $totalPressure / $entryCount : 0;
                        $averageSoilMoisture = $entryCount > 0 ? $totalSoilMoisture / $entryCount : 0;
                        $averageSoilTemperature = $entryCount > 0 ? $totalSoilTemperature / $entryCount : 0;
                        $averageSoilHumidity = $entryCount > 0 ? $totalSoilHumidity / $entryCount : 0;

                        // Add a row displaying the averages
                        echo "<tr>";
                        echo "<td><b>Averages:</b></td>";
                        echo "<td>" . number_format($averageTemperature, 2) . " °C</td>";
                        echo "<td>" . number_format($averageHumidity, 2) . " %</td>";
                        echo "<td>" . number_format($averagePressure, 2) . " hPa</td>";
                        echo "<td>" . number_format($averageSoilMoisture, 2) . " %</td>";
                        echo "<td>" . number_format($averageSoilTemperature, 2) . " °C</td>";
                        echo "<td>" . number_format($averageSoilHumidity, 2) . " %</td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "</tr>";
                    } else {
                        echo "<tr><td colspan='9'>No data available</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
        <div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <img id="modalImage" src="" alt="Camera Image" />
        <!-- Button container with spacing between the image and the button -->
        <div class="modal-button-container">
            <button id="downloadButton" onclick="downloadImage()" class="download-button">Download</button>
        </div>
    </div>
        </div>
    </section>

    <!-- Google Map Section -->
    <section id="map" class="content-section">
    <h2>Google Map</h2>
    <div class="content">
    <form method="POST" action="">
    <input type="hidden" name="update_gps" value="1">
    <input type="submit" value="Update GPS Location" class="button-style">
</form>
        <p>This is your Google Map:</p>
        <!-- Google Map Container -->
        <div id="google-map"></div>
        
<p>This is your Windy Map:</p>
        <div id="windy"></div>
    </div>
</section>

<!-- Include Google Maps API with your API Key -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC-wQBhEZFPG_h7PH-_nl_D3RGjBWqfvgs&callback=initMap" async defer></script>

<script>
    // Function to initialize the Google Map
    function initMap() {
        // Get latitude and longitude from PHP session
        const userLat = <?php echo isset($_SESSION['user']['currentlat']) ? $_SESSION['user']['currentlat'] : 4.2105; ?>;
        const userLng = <?php echo isset($_SESSION['user']['currentlng']) ? $_SESSION['user']['currentlng'] : 101.9758; ?>;

        // Create the map and center it on the user's location
        const userLocation = { lat: userLat, lng: userLng };

        // Create the map with satellite view
        const map = new google.maps.Map(document.getElementById("google-map"), {
            zoom: 10, // Adjust zoom based on user's location
            center: userLocation,
            mapTypeId: google.maps.MapTypeId.SATELLITE, // Satellite mode
        });

        // Add a marker at the user's location
        const marker = new google.maps.Marker({
            position: userLocation,
            map: map,
            title: "Your Location",
        });
    }
</script>
<section id="live" class="content-section">
    <h2>Live Streaming</h2>
    <div class="content">
        <p> Check this out: </p>
        <div>
    <label for="camera-ip">ESP32 Camera IP Address:</label>
    <input type="text" id="camera-ip" value="http://192.168.0.102">
    <button onclick="updateStream()">Connect</button>
  </div>
  <div>
    <iframe id="stream" src="http://192.168.0.102" allowfullscreen></iframe>
  </div>

  <script>
    function updateStream() {
      const ipAddress = document.getElementById('camera-ip').value;
      const stream = document.getElementById('stream');
      stream.src = ipAddress;
    }
    </script>

<h1>ESP32 Servo Motor Control</h1>
  <!-- Input Box for IP Address -->
  <label for="ipInput">ESP32 IP Address:</label>
  <input type="text" id="ipInput" value="<?php echo htmlspecialchars($servoip); ?>">

  <!-- Servo Slider -->
  <input type="range" id="servoSlider" min="0" max="180" value="90" oninput="updateDegree(this.value)">
  <div id="degreeValue">90°</div>

  <script>
    function updateDegree(degree) {
      document.getElementById('degreeValue').textContent = degree + '°';

      // Get the current IP address from the input box
      const ip = document.getElementById('ipInput').value;

      // Send the degree to the ESP32
      fetch(`http://${ip}/setServo?angle=${degree}`)
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
    }
  </script>
    </div>
</section>

<section id="ai" class="content-section">
    <h2>Artificial Intelligence</h2>
    <div class="content">
        <label for="ai-options">Select AI Analysis:</label>
        <select id="ai-options" style="
            width: 50%; 
            padding: 10px; 
            margin-top: 10px; 
            border: 1px solid #205c2d; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); 
            font-size: 14px; 
            font-family: Arial, sans-serif; 
            background-color: #f9f9f9;">
            <option value="plant">About Plant</option>
            <option value="location">About Location</option>
            <option value="suitability">Suitability Analysis</option>
        </select>
        <br><br>
        <button id="generateResponseButton" onclick="generateResponse();" class="button-style">
            <img src="icon.png" alt="Icon" style="width: 20px; height: 20px; margin-right: 5px;">
            Generate Response
        </button>
    </div>
    <br><br>
    <div id="response2"></div>
</section>

<script>
    function generateResponse() {
        // Get the selected option from the dropdown
        var selectedOption = document.getElementById("ai-options").value;

        // Variables
        var plant = <?php echo json_encode($plantset); ?>;
        var locationSet = <?php echo json_encode($locationset); ?>;

        // Define prompt based on selection
        var prompt;
        if (selectedOption === "plant") {
            prompt = `Provide a detailed analysis about the plant: ${plant}. Include information about its growth characteristics, ideal conditions, and best practices for cultivation.`;
        } else if (selectedOption === "location") {
            prompt = `Describe the location: ${locationSet}. Discuss its climate, soil type, and any relevant agricultural details that could impact planting decisions.`;
        } else if (selectedOption === "suitability") {
            prompt = `Analyze whether ${plant} can be successfully cultivated in ${locationSet}. Consider factors such as climate compatibility, soil suitability, and provide recommendations for optimizing yield.`;
        } else {
            prompt = "Invalid selection. Please try again.";
        }

        // Target response element
        var response2 = document.getElementById("response2");

        // Send request to server
        fetch("response.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ text: prompt })
        })
        .then((res) => res.text())
        .then((res) => {
            // Convert Markdown to HTML
            var formattedResponse = res
                .replace(/\*\*(.*?)\*\*/g, "<b style='color:#205c2d;'>$1</b>") // Bold for double stars
                .replace(/\n/g, "<br>")                                     // New lines for paragraph breaks
                .replace(/\*(?!\*)(.*?)\*/g, "<br>$1");                     // New line for single stars

            // Display the formatted response
            response2.innerHTML = formattedResponse;
        })
        .catch((error) => {
            response2.innerHTML = "Error: " + error.message;
        });
    }
</script>


<section id="setting" class="content-section">
    <h2>User Settings</h2>
    <!-- User Information -->
    <div class="user-info">
                <p><strong>User: </strong> <?php echo htmlspecialchars($_SESSION['user']['name'] ?? "Unknown User"); ?></p>
                <p><strong>Email: </strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Location: </strong> <?php echo htmlspecialchars($locationset ?? "Not Set"); ?></p>

                <p><strong>Plant: </strong> <?php echo htmlspecialchars($plantset ?? "Not Set"); ?></p>            </div>
                <form method="POST" action="">
            <!-- Edit Channel -->
            <p><strong>Edit Your Telegram Channel:</strong> <?php echo htmlspecialchars($channel ?? "Not Set"); ?></p>
            <input type="text" id="channel" name="channel" value="<?php echo htmlspecialchars($channel); ?>">        
    <p><strong>Location:</strong> <?php echo htmlspecialchars($locationset ?? "Not Set"); ?></p>
    <input type="text" name="locationset" value="<?php echo htmlspecialchars($locationset ?? ""); ?>" placeholder="Enter location name">

    <p><strong>Plant:</strong> <?php echo htmlspecialchars($plantset ?? "Not Set"); ?></p>
    <input type="text" name="plantset" value="<?php echo htmlspecialchars($plantset ?? ""); ?>" placeholder="Enter plant name">
    <br>
    <button type="submit" name="save_settings">Save Settings</button>
</form>

        <form method="POST" action="">
            <!-- Dynamic Messages Table -->
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Time</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display messages in a table format
                    if (!empty($userMessages)) {
                        $messageCount = 1;
                        foreach ($userMessages as $key => $msg) {
                            echo '<tr>';
                            echo '<td>message' . $messageCount . '</td>';  // Display message(number)
                            echo '<td>' . htmlspecialchars($msg) . '</td>';  // Display message content (time)
                            // Delete button that submits the form with the message key to delete
                            echo '<td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="messageKey" value="' . $key . '">
                                        <button type="submit" name="delete_message" class="delete-button">Delete</button>
                                    </form>
                                  </td>';
                            echo '</tr>';
                            $messageCount++;
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- Add New Message Section -->
            <div>
            <br>
                <label for="newMessageTime">Add New Message:</label>
                <br>
                <input type="text" id="newMessageTime" name="newMessageTime">
                <br>
                <button type="submit" name="add_message">Add Message</button>
            </div>
        </form>
    </div>
</section>
               
                










<script>
    document.addEventListener("DOMContentLoaded", function () {
        const container = document.getElementById("message-container");
        const addButton = document.getElementById("add-message");

        // Dynamic addition of new message fields
        addButton.addEventListener("click", function () {
            const count = container.children.length + 1;
            const newKey = "message" + count;
            const div = document.createElement("div");
            div.classList.add("message-field");
            div.innerHTML = `
                <label for="${newKey}">${newKey}:</label>
                <input type="time" id="${newKey}" name="messages[${newKey}]" required>
                <button type="button" class="remove-message" data-key="${newKey}">-</button>
            `;
            container.appendChild(div);

            // Attach event listener to remove button
            div.querySelector(".remove-message").addEventListener("click", function () {
                div.remove();
            });
        });

        // Handle removal of existing message fields
        container.addEventListener("click", function (e) {
            if (e.target.classList.contains("remove-message")) {
                const key = e.target.dataset.key;
                e.target.closest(".message-field").remove();
            }
        });
    });
</script>





<script>
    function openNav() {
        document.getElementById("mySidebar").style.width = "300px";
        document.getElementById("main").style.marginLeft = "300px";
    }

    function closeNav() {
        document.getElementById("mySidebar").style.width = "0";
        document.getElementById("main").style.marginLeft = "0";
    }

    // Highlight the current section on click
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function() {
            // Remove highlight from all links
            document.querySelectorAll('.sidebar a').forEach(l => l.style.color = "#555");

            // Highlight the clicked link
            this.style.color = "white";
        });
    });



    // On page load, decide which section to show
    window.onload = function () {
    const currentHash = window.location.hash; // Get the current hash (e.g., #data)
    if (currentHash) {
        // Remove the '#' and show the respective section
        showSection(currentHash.substring(1));
    } else {
        // Default to the 'home' section if no hash is present
        showSection('home');
    }
};


    // Function to show the desired section
    function showSection(sectionID) {
        // Hide all sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => section.style.display = 'none');

        // Show the selected section
        const selectedSection = document.getElementById(sectionID);
        if (selectedSection) {
            selectedSection.style.display = 'block';
        }

        // Update the hash in the URL
        window.location.hash = `#${sectionID}`;
    }
</script>

<script src="https://unpkg.com/leaflet@1.4.0/dist/leaflet.js"></script>
<script src="https://api.windy.com/assets/map-forecast/libBoot.js"></script>
<script>
    const latitude = <?php echo isset($_SESSION['user']['currentlat']) ? $_SESSION['user']['currentlat'] : 4.2105; ?>;
    const longitude = <?php echo isset($_SESSION['user']['currentlng']) ? $_SESSION['user']['currentlng'] : 101.9758; ?>;

    const options = {
        key: '9R5QVEl5y5rToJWKVsIyEiY5AYr4RpKh', // REPLACE WITH YOUR WINDY KEY
        verbose: true,
        lat: latitude,
        lon: longitude,
        zoom: 20,
        overlay: 'wind', // Default overlay for Windy (wind)
    };

    let windyAPI;
    let map;
    let store;

    windyInit(options, api => {
        windyAPI = api;
        map = windyAPI.map;
        store = windyAPI.store;

        // Add a popup with the current location
        L.popup()
            .setLatLng([latitude, longitude])
            .setContent('Current Location: Latitude ' + latitude + ', Longitude ' + longitude)
            .openOn(map);

        // Set the default map overlay
        store.set('overlay', 'wind');
    });
    </script>





<script>
    document.addEventListener("DOMContentLoaded", function () {
        // PHP variables for averages
        var month1avgtmp = <?php echo round($month1avgtmp, 2); ?>;
        var month2avgtmp = <?php echo round($month2avgtmp, 2); ?>;
        var month3avgtmp = <?php echo round($month3avgtmp, 2); ?>;
        var month4avgtmp = <?php echo round($month4avgtmp, 2); ?>;
        var month5avgtmp = <?php echo round($month5avgtmp, 2); ?>;

        var month1avghum = <?php echo round($month1avghum, 2); ?>;
        var month2avghum = <?php echo round($month2avghum, 2); ?>;
        var month3avghum = <?php echo round($month3avghum, 2); ?>;
        var month4avghum = <?php echo round($month4avghum, 2); ?>;
        var month5avghum = <?php echo round($month5avghum, 2); ?>;

        var month1avgprs = <?php echo round($month1avgprs, 2); ?>;
        var month2avgprs = <?php echo round($month2avgprs, 2); ?>;
        var month3avgprs = <?php echo round($month3avgprs, 2); ?>;
        var month4avgprs = <?php echo round($month4avgprs, 2); ?>;
        var month5avgprs = <?php echo round($month5avgprs, 2); ?>;

        var month1avgsoi = <?php echo round($month1avgsoi, 2); ?>;
        var month2avgsoi = <?php echo round($month2avgsoi, 2); ?>;
        var month3avgsoi = <?php echo round($month3avgsoi, 2); ?>;
        var month4avgsoi = <?php echo round($month4avgsoi, 2); ?>;
        var month5avgsoi = <?php echo round($month5avgsoi, 2); ?>;

        //THIS IS THE CODING FOR THE AI PROMPT
        // Default plant and location
        var plant = <?php echo json_encode($plantset); ?>;
var locationSet = <?php echo json_encode($locationset); ?>;

        // Construct the prompt
        var prompt = `Analyze the weather for growing ${plant} in ${locationSet}:\n\n` +
        `Remember that your response should be repeated back all the information I've given you below and analyze it for each month, BUT I want you to remember that IF the data is 0 data inserted MEANS it wasn't RECORDED, SO IGNORE THE MONTH that has ` +
        `0 data inserted. For soil moisture the reference is that high values (3500) means dry soil but lower value (2000+/-) means wet soil` +
            `**Month 1:**\n` +
            `- Average Temperature: ${month1avgtmp}°C\n` +
            `- Average Humidity: ${month1avghum}%\n` +
            `- Average Pressure: ${month1avgprs} hPa\n` +
            `- Average Soil Moisture: ${month1avgsoi}%\n\n` +
            `**Month 2:**\n` +
            `- Average Temperature: ${month2avgtmp}°C\n` +
            `- Average Humidity: ${month2avghum}%\n\n` +
            `- Average Pressure: ${month2avgprs} hPa\n` +
            `- Average Soil Moisture: ${month2avgsoi}%\n\n` +
            `**Month 3:**\n` +
            `- Average Temperature: ${month3avgtmp}°C\n` +
            `- Average Humidity: ${month3avghum}%\n\n` +
            `- Average Pressure: ${month3avgprs} hPa\n` +
            `- Average Soil Moisture: ${month3avgsoi}%\n\n` +
            `**Month 4:**\n` +
            `- Average Temperature: ${month4avgtmp}°C\n` +
            `- Average Humidity: ${month4avghum}%\n\n` +
            `- Average Pressure: ${month4avgprs} hPa\n` +
            `- Average Soil Moisture: ${month4avgsoi}%\n\n` +
            `**Month 5:**\n` +
            `- Average Temperature: ${month5avgtmp}°C\n` +
            `- Average Humidity: ${month5avghum}%\n\n` +
            `- Average Pressure: ${month5avgprs} hPa\n` +
            `- Average Soil Moisture: ${month5avgsoi}%\n\n` +
            
            `Based on this data, determine if it's suitable to plant ${plant}. If yes, provide tips for maximizing yield. ` +
            `If not, suggest next steps to optimize conditions.`;

        // Fetch response from the server
        fetch("response.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ text: prompt })
        })
        .then((res) => res.text())
        .then((res) => {
            // Format the response
            var formattedResponse = res
                .replace(/\*\*(.*?)\*\*/g, "<b style='color:#205c2d;'>$1</b>") // Bold for double stars
                .replace(/\n/g, "<br>");                                     // New lines for paragraph breaks

            // Display the formatted response
            document.getElementById("response").innerHTML = formattedResponse;
        })
        .catch((error) => {
            document.getElementById("response").innerHTML = "Error: " + error.message;
        });
    });
</script>


    
  </script>
<!-- JavaScript to update time every second and trigger fetch data action -->
<script>
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds}`;

    // Update the displayed time
    document.getElementById('currentTime').textContent = timeString;

    // Retrieve the array of messages passed from PHP
    const userMessages = JSON.parse('<?php echo json_encode($userMessages); ?>');

    // Check if the current time matches any of the target message times
    for (const key in userMessages) {
        if (userMessages[key] === timeString) {
            // Trigger the fetch data action
            document.getElementById('fetchDataForm').submit();
            break;
        }
    }
}

// Run the updateTime function every second
setInterval(updateTime, 1000);


// Check time every second
setInterval(updateTime, 1000);

// Initial call to set time immediately
updateTime();

const gaugeElement = document.querySelector(".gauge2");

function setGaugeValue(gauge, value) {
  if (value < 0 || value > 1) {
    return;
  }

  gauge.querySelector(".gauge__fill2").style.transform = `rotate(${value / 2}turn)`;
  gauge.querySelector(".gauge__cover2").textContent = `${Math.round(value * 100)}%`;
}
const divhum = <?php echo $averageHumidity; ?> / 100; // Adjust the division as needed

setGaugeValue(gaugeElement, divhum);

// Tutorial board
function openPopup() {
    document.getElementById('popup-container').style.display = 'flex';
}

// Function to close the pop-up
function closePopup() {
    document.getElementById('popup-container').style.display = 'none';
}

// Function to view the image and show the download button
function viewImage(base64Value) {
    const modal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");
    const downloadButton = document.getElementById("downloadButton");
    
    modalImage.src = "data:image/png;base64," + base64Value; // Assuming the image is in PNG format
    modal.style.display = "block"; // Show the modal

    // Set up the download button functionality
    downloadButton.onclick = function() {
        downloadBase64Image(base64Value); // Trigger download
    };
}

// Function to close the modal
function closeModal() {
    document.getElementById("imageModal").style.display = "none"; // Hide the modal
}

// Close the modal when clicking anywhere outside of the modal content
window.onclick = function(event) {
    const modal = document.getElementById("imageModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Function to handle the image download
function downloadBase64Image(base64Data) {
    const link = document.createElement('a');
    link.href = "data:image/png;base64," + base64Data;
    link.download = "image.png"; // Default download file name
    link.click(); // Trigger the download
}

// Open the popup2 container
function openPopup2() {
    document.getElementById("popup2-container").style.display = "flex";
}

// Close the popup2 container
function closePopup2() {
    document.getElementById("popup2-container").style.display = "none";
}

function showSection(sectionID) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.style.display = 'none');

    // Show the selected section
    const selectedSection = document.getElementById(sectionID);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }

    // Update the hash in the URL dynamically
    window.location.hash = `#${sectionID}`;
}

//THIS IS FOR WEATHER ☀️
document.addEventListener('DOMContentLoaded', function () {
    fetch('fetch_weather.php', {
        method: 'GET',
    })
    .then(response => response.json())
    .then(data => {
        const forecastDiv = document.getElementById('forecast');
        forecastDiv.innerHTML = '';

        if (data.error) {
            forecastDiv.innerHTML = `<p>${data.error}</p>`;
            return;
        }

        // Display weather forecast
        data.list.forEach(item => {
            const date = new Date(item.dt_txt);
            const forecastItem = document.createElement('div');
            forecastItem.className = 'forecast-item';

            forecastItem.innerHTML = `
                <p><strong>${date.toDateString()} ${date.toLocaleTimeString()}</strong></p>
                <img src="http://openweathermap.org/img/wn/${item.weather[0].icon}.png" alt="${item.weather[0].description}">
                <p>${item.weather[0].description}</p>
                <p>Temp: ${item.main.temp}°C</p>
                <p>Humidity: ${item.main.humidity}%</p>
                <p>Wind: ${item.wind.speed} m/s</p>
            `;

            forecastDiv.appendChild(forecastItem);
        });
    })
    .catch(error => {
        document.getElementById('forecast').innerHTML = '<p>Failed to fetch data. Please try again later.</p>';
        console.error('Error fetching weather data:', error);
    });
});



</script>




<!-- Hidden form to trigger data fetching -->
<form id="fetchDataForm" method="POST" action="">
    <input type="hidden" name="fetchData2" value="1">
</form>


</script>

</body>
</html>
