#include <WiFi.h>
#include <PubSubClient.h>

#include <HTTPClient.h>



// WiFi credentials
const char* ssid = "Converge_AZU9";
const char* password = "497362SW";

// MQTT settings
const char* mqtt_server = "192.168.1.2"; 
const int mqtt_port = 1883;              
const char* mqtt_topic = "RFID_LOGIN";

WiFiClient espClient;
PubSubClient client(espClient);

// Declare functions at the top
void connectWiFi();
void connectMQTT();



void setup() {
    Serial.begin(115200);
    connectWiFi();         // Connect to WiFi
    client.setServer(mqtt_server, mqtt_port); // Set MQTT server and port
    connectMQTT();         // Connect to MQTT broker
}

void loop() {
    if (!client.connected()) {
        connectMQTT();      // Reconnect to MQTT if connection lost
    }
    client.loop();    
    
}

void connectWiFi() {
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(1000);
        Serial.println("Connecting to WiFi...");
    }
    Serial.println("Connected to WiFi");
    Serial.print("WiFi IP Address: ");
    Serial.println(WiFi.localIP());
}

void connectMQTT() {
    while (!client.connected()) {
        Serial.print("Connecting to MQTT...");
        if (client.connect("ESP32Client")) {  // Use unique client ID
            Serial.println("connected");
            client.subscribe(mqtt_topic);
        } else {
            Serial.print("failed, rc=");
            Serial.print(client.state());
            Serial.println(" retrying in 5 seconds");
            delay(5000);
        }
    }
}
