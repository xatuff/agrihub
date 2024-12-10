# agrihub
Agriculture-themed robot helping farmers observing their farm from afar. Project based on Arduino microcontroller. 
Special thanks to my beloved project members: Hureen and Umar for making everything possible. 



The Hardware Design:
<br><br>
![image](https://github.com/user-attachments/assets/cca4e9af-e968-4174-823d-48322e31a397)
<br><br><br>
This is an attachment of the design of the website. Works best with dark mode.
<h2>Home Overview, Weather Forecast powered by OpenWeather:</h2>

![Screenshot 2024-12-06 162542](https://github.com/user-attachments/assets/b92eb1e3-0462-4842-9b0b-60f01bdb85d3)

<h2>Home Overview with working AI text generative, the generated text is based on the graph which is also retrieved from data entries in Firebase.</h2>

![Screenshot 2024-12-01 141515](https://github.com/user-attachments/assets/3c5b5300-ef3a-45eb-9963-70561dbf08ef)

<h2>Data Entries from sensors (BME280 + GPS + SOIL MOISTURE ) :</h2>

![Screenshot 2024-12-06 162618](https://github.com/user-attachments/assets/e06a2e25-4850-43a0-b617-2fea4e08dae8)

<h2>Google Map and Windy Map ( PIN POINT is based on latitude and longitude obtained by the GPS ) :</h2>

![Screenshot 2024-12-01 141348](https://github.com/user-attachments/assets/e6877993-5991-4e37-a247-ddefee684494)

<h2>Simple User Setting. Each User can setup as many time they want, the website will fetch the data automatically for every time user has set and store it in to cloud database.</h2>
![Screenshot 2024-12-06 163044](https://github.com/user-attachments/assets/655d87a7-4eaa-4b52-801a-0ce2f41c9d65)

Image Analyzer :
<br><br>
![Screenshot 2024-11-15 112423](https://github.com/user-attachments/assets/82c16be2-b533-4b9e-9582-af29419e959f)
![Screenshot 2024-11-15 112359](https://github.com/user-attachments/assets/7caff123-999e-46d3-8e3f-9ea6bce8ac98)
![Screenshot 2024-11-15 112346](https://github.com/user-attachments/assets/f33cd56d-ccf8-460a-bdd8-c0790c3ebf35)

<br>
To people who want to use our website functionally. Please generate the API key/Cloud Address below and replace it in the coding, I commented every api key you need to replace in the coding.
Fear not, every API key is free to use for personal use
<br>
CONFIG.PHP : Firebase realtime database<br>
NEWDASHBOARD.PHP : Google Map API Key ( ENABLE JAVASCRIPT IN THE GOOGLE MAP ) , Open Weather API, Windy Map API.<br>
RESPONSE.PHP: Google Gemini API Key<br>
<br>
Image Analyzer : ( Uploading soon, coding hosted on streamlit )


