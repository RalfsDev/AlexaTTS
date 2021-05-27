# AlexaTTS
Using Amazon Alexa Text to Speech (TTS) via Smart Home. Implementation via PHP and HTTP request.

## requirements
- Php server must have curl enabled.

## installation
- Download alexa.php and put it on your server.
- upload your cookies.txt file

For a more detailed installation tutorial visit https://iqhaus.de/tutorials/smart-home-tutorials/amazon-alexa-text-to-speech-tts-ubers-smart-home-nutzen/


## usage with GUI
In the GUI you can see all alexa devices in a table and under the table a small form, which you can do TTS calls.

## usage as API
alexa.php?API=true&mode=XXX
- mode=getDevices
  This will result in a JSON String with all of your Alexa devices
  eg. alexa.php?API=true&mode=getDevices
- mode=sendTTS
  This will send a text to a device.
  Additional parameters: device_name, text_tts
  eg. alexa.php?API=true&mode=sendTTS&device_name=myAlexa&text_tts=Hello World
  

## TODO
- manage errors in function
- API json return, if ok or error occured and which error