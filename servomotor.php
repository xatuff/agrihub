<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servo Motor Control</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 50px;
    }
    #degreeValue {
      font-size: 20px;
      margin-top: 10px;
    }
    #ipInput {
      margin: 20px auto;
      padding: 5px;
      width: 200px;
      font-size: 16px;
    }
  </style>
</head>
<body>
  <h1>ESP32 Servo Motor Control</h1>
  <!-- Input Box for IP Address -->
  <label for="ipInput">ESP32 IP Address:</label>
  <input type="text" id="ipInput" placeholder="192.168.4.1" value="192.168.4.1">

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
</body>
</html>
