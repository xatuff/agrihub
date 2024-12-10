<?php
require "vendor/autoload.php";

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;

// Get the data from the POST request
$data = json_decode(file_get_contents("php://input"));
$text = $data->text;

// Initialize the Gemini API client with the API key
$client = new Client("API HERE);

// Call the Gemini API to generate content
$response = $client->geminiPro()->generateContent(
    new TextPart($text)
);

// Output the generated text
echo $response->text();
?>
