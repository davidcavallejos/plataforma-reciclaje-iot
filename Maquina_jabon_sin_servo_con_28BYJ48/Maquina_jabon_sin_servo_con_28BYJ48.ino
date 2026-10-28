/*
===============================================================================
MÁQUINA DE JABÓN - FIRMWARE INALÁMBRICO ESP32 + 4 L298N
Control por HTTP / Web y Monitor Serial.

Módulos conectados:
  L298N #1: Dos motoreductores principales (tanque de mezcla)
  L298N #2: Bomba de agua y Bomba de aceite
  L298N #3: Dos bombas de filtrado (Filtro 1 y Filtro 2)
  L298N #4: Motoreductor 3 y Bomba 4
Sensores:
  IR1 (GPIO 35), IR2 (GPIO 34), Temperatura DS18B20 (GPIO 21)
===============================================================================
*/

#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ---------------- CONFIGURACIÓN INALÁMBRICA (WIFI) ----------------
// 1) Modo Red Local: Pon aquí el nombre y clave del WiFi de tu taller/casa
const char* ssid = "pc";
const char* password = "diseloal";

// 2) Modo Punto de Acceso (AP): Si falla el WiFi, el ESP32 crea su propia red inalámbrica
const char* ap_ssid = "Maquina_Jabon_ESP32";
const char* ap_password = "jabon123";

// Credenciales HTTP Basic (coincidentes con el .env de Laravel)
const char* www_username = "admin";
const char* www_password = "jabon123";

WebServer server(80);

// ---------------- PWM (Frecuencia y resolución) ----------------
const int PWM_FREQ = 1000;
const int PWM_BITS = 8; // Rango 0 a 255

// ---------------- PINES (GPIO) ----------------
// L298N #1: Motoreductores 1 y 2
const int MOTOR1_EN  = 27; // ENA
const int MOTOR1_IN1 = 16;
const int MOTOR1_IN2 = 17;
const int MOTOR2_EN  = 33; // ENB
const int MOTOR2_IN3 = 18;
const int MOTOR2_IN4 = 19;

// L298N #2: Agua y Aceite
const int WATER_EN = 13; // ENA
const int OIL_EN   = 14; // ENB

// L298N #3: Filtros 1 y 2
const int FILTER1_EN = 25; // ENA
const int FILTER2_EN = 26; // ENB

// L298N #4: Motoreductor 3 y Bomba 4
const int MOTOR3_EN  = 23; // ENA
const int MOTOR3_IN1 = 22;
const int MOTOR3_IN2 = 4;
const int PUMP4_EN   = 32; // ENB

// Sensores
const int IR1_PIN = 35;
const int IR2_PIN = 34;
const int DS18B20_PIN = 21;

const int IR1_ACTIVE_LEVEL = LOW;
const int IR2_ACTIVE_LEVEL = LOW;

OneWire oneWire(DS18B20_PIN);
DallasTemperature temperatureSensor(&oneWire);

// ---------------- VELOCIDADES PWM INICIALES ----------------
int motorSpeed   = 150;
int waterSpeed   = 200;
int oilSpeed     = 150;
int filter1Speed = 150;
int filter2Speed = 150;
int motor3Speed  = 120;
int pump4Speed   = 150;

// ---------------- ESTADOS ----------------
bool motorsRunning = false;
bool motor3Running = false;
bool hourMode = false;
int motor3Direction = 1; // 1 = CW, -1 = CCW

unsigned long hourStartMillis = 0;
const unsigned long HOUR_DURATION = 3600000UL;

float lastTempC = 0.0;
unsigned long lastTempReadMillis = 0;

// ---------------- PANEL HTML EMBEBIDO (DE EMERGENCIA) ----------------
const char INDEX_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Máquina de Jabón · ESP32</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #082d27; color: #ecfdf7; margin: 0; padding: 15px; text-align: center; }
    h1 { color: #34d399; margin-bottom: 5px; font-size: 1.4rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; max-width: 900px; margin: 15px auto; }
    .card { background: rgba(255,255,255,0.06); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
    .card h3 { margin: 0 0 10px; color: #7fe3cf; font-size: 1rem; }
    button { background: #0f766e; color: white; border: none; padding: 9px 14px; margin: 4px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    button.on { background: #10b981; }
    button.off { background: #ef4444; }
    button.stop { background: #b91c1c; width: 100%; max-width: 500px; padding: 14px; font-size: 1.1rem; margin: 20px auto; display: block; border-radius: 10px; }
    input[type=number] { width: 70px; padding: 6px; border-radius: 6px; border: 1px solid #7fe3cf; text-align: center; background: #fff; }
    .status { font-size: 0.9rem; color: #a7f3d0; margin-top: 5px; font-weight: 500; }
  </style>
</head>
<body>
  <h1>🧼 ESP32 · Servidor Inalámbrico</h1>
  <div class="status" id="status">Cargando telemetría...</div>

  <div class="grid">
    <div class="card">
      <h3>Motores 1 y 2 (L298N #1)</h3>
      <button class="on" onclick="cmd('START')">START</button>
      <button class="off" onclick="cmd('STOP')">STOP</button><br><br>
      PWM: <input type="number" id="v_mot" value="150" min="0" max="255">
      <button onclick="cmd('V ' + document.getElementById('v_mot').value)">Fijar</button>
    </div>

    <div class="card">
      <h3>Bomba de Agua</h3>
      <button class="on" onclick="cmd('WATER ON')">ON</button>
      <button class="off" onclick="cmd('WATER OFF')">OFF</button><br><br>
      PWM: <input type="number" id="v_wat" value="200" min="0" max="255">
      <button onclick="cmd('WATER ' + document.getElementById('v_wat').value)">Fijar</button>
    </div>

    <div class="card">
      <h3>Bomba de Aceite</h3>
      <button class="on" onclick="cmd('OIL ON')">ON</button>
      <button class="off" onclick="cmd('OIL OFF')">OFF</button><br><br>
      PWM: <input type="number" id="v_oil" value="150" min="0" max="255">
      <button onclick="cmd('OIL ' + document.getElementById('v_oil').value)">Fijar</button>
    </div>

    <div class="card">
      <h3>Filtros 1 y 2</h3>
      <button class="on" onclick="cmd('FILTER1 ON')">F1 ON</button>
      <button class="off" onclick="cmd('FILTER1 OFF')">F1 OFF</button>
      <button class="on" onclick="cmd('FILTER2 ON')">F2 ON</button>
      <button class="off" onclick="cmd('FILTER2 OFF')">F2 OFF</button>
    </div>

    <div class="card">
      <h3>Motoreductor 3</h3>
      <button class="on" onclick="cmd('MOTOR3 CW')">Giro CW</button>
      <button class="on" onclick="cmd('MOTOR3 CCW')">Giro CCW</button>
      <button class="off" onclick="cmd('MOTOR3 OFF')">OFF</button><br><br>
      PWM: <input type="number" id="v_m3" value="120" min="0" max="255">
      <button onclick="cmd('MOTOR3 SPEED ' + document.getElementById('v_m3').value)">Fijar</button>
    </div>

    <div class="card">
      <h3>Bomba 4</h3>
      <button class="on" onclick="cmd('PUMP4 ON')">ON</button>
      <button class="off" onclick="cmd('PUMP4 OFF')">OFF</button><br><br>
      PWM: <input type="number" id="v_p4" value="150" min="0" max="255">
      <button onclick="cmd('PUMP4 ' + document.getElementById('v_p4').value)">Fijar</button>
    </div>
  </div>

  <button class="stop" onclick="cmd('ALLSTOP')">🚨 PARADA TOTAL (ALLSTOP)</button>

  <script>
    function cmd(c) {
      fetch('/cmd?c=' + encodeURIComponent(c)).then(() => updateStatus());
    }
    function updateStatus() {
      fetch('/status').then(r => r.json()).then(d => {
        document.getElementById('status').innerText =
          `Motores: ${d.motors ? 'ENCENDIDOS' : 'APAGADOS'} | Temp: ${d.temp} °C | M3: ${d.m3} | Bomba4: ${d.p4} | Agua: ${d.water}`;
      }).catch(() => {});
    }
    setInterval(updateStatus, 2000);
    updateStatus();
  </script>
</body>
</html>
)rawliteral";

// ---------------- ASIGNACIÓN PWM ----------------
bool attachPWM(int pin) {
  bool ok = ledcAttach(pin, PWM_FREQ, PWM_BITS);
  if (ok) ledcWrite(pin, 0);
  return ok;
}

void setPWM(int pin, int speed) {
  speed = constrain(speed, 0, 255);
  ledcWrite(pin, speed);
}

// ---------------- ACCIONES DE HARDWARE ----------------
void motorsStart() {
  digitalWrite(MOTOR1_IN1, HIGH); digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, HIGH); digitalWrite(MOTOR2_IN4, LOW);
  setPWM(MOTOR1_EN, motorSpeed);
  setPWM(MOTOR2_EN, motorSpeed);
  motorsRunning = (motorSpeed > 0);
}

void motorsStop() {
  setPWM(MOTOR1_EN, 0);
  setPWM(MOTOR2_EN, 0);
  digitalWrite(MOTOR1_IN1, LOW); digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, LOW); digitalWrite(MOTOR2_IN4, LOW);
  motorsRunning = false;
  hourMode = false;
}

void motor3ApplyDirection() {
  if (motor3Direction > 0) {
    digitalWrite(MOTOR3_IN1, HIGH);
    digitalWrite(MOTOR3_IN2, LOW);
  } else {
    digitalWrite(MOTOR3_IN1, LOW);
    digitalWrite(MOTOR3_IN2, HIGH);
  }
}

void motor3On() {
  motor3ApplyDirection();
  setPWM(MOTOR3_EN, motor3Speed);
  motor3Running = (motor3Speed > 0);
}

void motor3Off() {
  setPWM(MOTOR3_EN, 0);
  digitalWrite(MOTOR3_IN1, LOW);
  digitalWrite(MOTOR3_IN2, LOW);
  motor3Running = false;
}

void motor3SetDirection(int direction) {
  motor3Direction = (direction >= 0) ? 1 : -1;
  if (motor3Running) {
    motor3ApplyDirection();
    setPWM(MOTOR3_EN, motor3Speed);
  }
}

void waterOn()    { setPWM(WATER_EN, waterSpeed); }
void waterOff()   { setPWM(WATER_EN, 0); }
void oilOn()      { setPWM(OIL_EN, oilSpeed); }
void oilOff()     { setPWM(OIL_EN, 0); }
void filter1On()  { setPWM(FILTER1_EN, filter1Speed); }
void filter1Off() { setPWM(FILTER1_EN, 0); }
void filter2On()  { setPWM(FILTER2_EN, filter2Speed); }
void filter2Off() { setPWM(FILTER2_EN, 0); }
void pump4On()    { setPWM(PUMP4_EN, pump4Speed); }
void pump4Off()   { setPWM(PUMP4_EN, 0); }

void pumpsStop() {
  waterOff(); oilOff(); filter1Off(); filter2Off(); pump4Off();
}

void allStop() {
  motorsStop(); motor3Off(); pumpsStop();
}

String getIRState(int pin, int activeLevel, const char *name) {
  int state = digitalRead(pin);
  String res = String(name) + ": ";
  res += (state == activeLevel) ? "DETECCION ACTIVA" : "SIN DETECCION";
  return res;
}

void updateTemperature() {
  if (millis() - lastTempReadMillis > 2000) {
    temperatureSensor.requestTemperatures();
    float t = temperatureSensor.getTempCByIndex(0);
    if (t != DEVICE_DISCONNECTED_C && t > -100.0) {
      lastTempC = t;
    }
    lastTempReadMillis = millis();
  }
}

// ---------------- INTÉRPRETE DE COMANDOS ----------------
String processCommand(String command) {
  command.trim();
  command.toUpperCase();
  if (command.length() == 0) return "";

  String response = "Comando: " + command + "\n";

  if (command == "START") {
    hourMode = false;
    motorsStart();
    response += "Motoreductores principales encendidos.";
  }
  else if (command == "STOP") {
    motorsStop();
    response += "Motoreductores principales detenidos.";
  }
  else if (command.startsWith("V ")) {
    int value = command.substring(2).toInt();
    if (value >= 0 && value <= 255) {
      motorSpeed = value;
      if (motorsRunning) {
        setPWM(MOTOR1_EN, motorSpeed);
        setPWM(MOTOR2_EN, motorSpeed);
        if (motorSpeed == 0) motorsRunning = false;
      }
      response += "Velocidad motores: " + String(motorSpeed);
    } else response += "Error: usa V 0-255.";
  }
  else if (command == "HOUR") {
    motorSpeed = constrain(motorSpeed, 1, 255);
    motorsStart();
    hourMode = true;
    hourStartMillis = millis();
    response += "Prueba de 1 hora iniciada.";
  }
  else if (command == "WATER ON") {
    waterOn(); response += "Bomba de agua encendida.";
  }
  else if (command == "WATER OFF") {
    waterOff(); response += "Bomba de agua apagada.";
  }
  else if (command.startsWith("WATER ")) {
    int value = command.substring(6).toInt();
    if (value >= 0 && value <= 255) {
      waterSpeed = value; setPWM(WATER_EN, waterSpeed);
      response += "PWM agua = " + String(waterSpeed);
    } else response += "Error: WATER 0-255.";
  }
  else if (command == "OIL ON") {
    oilOn(); response += "Bomba de aceite encendida.";
  }
  else if (command == "OIL OFF") {
    oilOff(); response += "Bomba de aceite apagada.";
  }
  else if (command.startsWith("OIL ")) {
    int value = command.substring(4).toInt();
    if (value >= 0 && value <= 255) {
      oilSpeed = value; setPWM(OIL_EN, oilSpeed);
      response += "PWM aceite = " + String(oilSpeed);
    } else response += "Error: OIL 0-255.";
  }
  else if (command == "FILTER1 ON") {
    filter1On(); response += "Filtro 1 encendido.";
  }
  else if (command == "FILTER1 OFF") {
    filter1Off(); response += "Filtro 1 apagado.";
  }
  else if (command.startsWith("FILTER1 ")) {
    int value = command.substring(8).toInt();
    if (value >= 0 && value <= 255) {
      filter1Speed = value; setPWM(FILTER1_EN, filter1Speed);
      response += "PWM filtro 1 = " + String(filter1Speed);
    } else response += "Error: FILTER1 0-255.";
  }
  else if (command == "FILTER2 ON") {
    filter2On(); response += "Filtro 2 encendido.";
  }
  else if (command == "FILTER2 OFF") {
    filter2Off(); response += "Filtro 2 apagado.";
  }
  else if (command.startsWith("FILTER2 ")) {
    int value = command.substring(8).toInt();
    if (value >= 0 && value <= 255) {
      filter2Speed = value; setPWM(FILTER2_EN, filter2Speed);
      response += "PWM filtro 2 = " + String(filter2Speed);
    } else response += "Error: FILTER2 0-255.";
  }
  else if (command == "MOTOR3 ON") {
    motor3On(); response += "Motoreductor 3 encendido.";
  }
  else if (command == "MOTOR3 OFF") {
    motor3Off(); response += "Motoreductor 3 apagado.";
  }
  else if (command == "MOTOR3 CW") {
    motor3SetDirection(1); motor3On(); response += "Motoreductor 3 girando CW.";
  }
  else if (command == "MOTOR3 CCW") {
    motor3SetDirection(-1); motor3On(); response += "Motoreductor 3 girando CCW.";
  }
  else if (command.startsWith("MOTOR3 SPEED ")) {
    int value = command.substring(13).toInt();
    if (value >= 0 && value <= 255) {
      motor3Speed = value;
      if (motor3Running) {
        setPWM(MOTOR3_EN, motor3Speed);
        if (motor3Speed == 0) motor3Running = false;
      }
      response += "Velocidad motoreductor 3 = " + String(motor3Speed);
    } else response += "Error: MOTOR3 SPEED 0-255.";
  }
  else if (command == "PUMP4 ON") {
    pump4On(); response += "Bomba nueva encendida.";
  }
  else if (command == "PUMP4 OFF") {
    pump4Off(); response += "Bomba nueva apagada.";
  }
  else if (command.startsWith("PUMP4 ")) {
    int value = command.substring(6).toInt();
    if (value >= 0 && value <= 255) {
      pump4Speed = value; setPWM(PUMP4_EN, pump4Speed);
      response += "PWM bomba nueva = " + String(pump4Speed);
    } else response += "Error: PUMP4 0-255.";
  }
  else if (command == "FSTOP") {
    filter1Off(); filter2Off(); response += "Filtros apagados.";
  }
  else if (command == "PSTOP") {
    pumpsStop(); response += "Todas las bombas apagadas.";
  }
  else if (command == "ALLSTOP") {
    allStop(); response += "¡PARADA TOTAL! Todo apagado.";
  }
  else if (command == "IR1") {
    response += getIRState(IR1_PIN, IR1_ACTIVE_LEVEL, "IR1");
  }
  else if (command == "IR2") {
    response += getIRState(IR2_PIN, IR2_ACTIVE_LEVEL, "IR2");
  }
  else if (command == "TEMP") {
    response += "Temperatura: " + String(lastTempC, 2) + " C";
  }
  else if (command == "SENSORS") {
    response += getIRState(IR1_PIN, IR1_ACTIVE_LEVEL, "IR1") + "\n";
    response += getIRState(IR2_PIN, IR2_ACTIVE_LEVEL, "IR2") + "\n";
    response += "Temperatura: " + String(lastTempC, 2) + " C";
  }
  else if (command == "STATUS") {
    response += "M1/M2: " + String(motorsRunning ? "ON" : "OFF") + " (Vel " + String(motorSpeed) + ")\n";
    response += "M3: " + String(motor3Running ? (motor3Direction > 0 ? "CW" : "CCW") : "OFF") + " (Vel " + String(motor3Speed) + ")\n";
    response += "Agua: " + String(waterSpeed) + " | Aceite: " + String(oilSpeed) + "\n";
    response += "Filtro1: " + String(filter1Speed) + " | Filtro2: " + String(filter2Speed) + "\n";
    response += "Bomba4: " + String(pump4Speed) + "\n";
    response += "Temp: " + String(lastTempC, 2) + " C";
  }
  else {
    response += "Comando no reconocido. Escribe HELP.";
  }

  return response;
}

// ---------------- TELEMETRÍA JSON COMPATIBLE CON LARAVEL ----------------
String getStatusJson() {
  String json = "{";
  json += "\"motors\":" + String(motorsRunning ? "true" : "false") + ",";
  json += "\"temp\":" + String(lastTempC, 1) + ",";
  json += "\"m3\":\"" + String(motor3Running ? (motor3Direction > 0 ? "CW" : "CCW") : "OFF") + "\",";
  json += "\"p4\":" + String(pump4Speed) + ",";
  json += "\"water\":" + String(waterSpeed);
  json += "}";
  return json;
}

// ---------------- SERVIDOR WEB HTTP ----------------
void setupServer() {
  server.on("/", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    server.send_P(200, "text/html", INDEX_HTML);
  });

  server.on("/cmd", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    String c = server.arg("c");
    String res = processCommand(c);
    Serial.println("[HTTP CMD] " + res);
    server.send(200, "text/plain", res);
  });

  server.on("/status", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    server.send(200, "application/json", getStatusJson());
  });

  server.begin();
  Serial.println("[HTTP] Servidor Web iniciado en el puerto 80.");
}

// ---------------- INICIALIZACIÓN (SETUP) ----------------
void setup() {
  Serial.begin(115200);
  delay(500);

  // Configuración de pines de dirección
  pinMode(MOTOR1_IN1, OUTPUT); pinMode(MOTOR1_IN2, OUTPUT);
  pinMode(MOTOR2_IN3, OUTPUT); pinMode(MOTOR2_IN4, OUTPUT);
  pinMode(MOTOR3_IN1, OUTPUT); pinMode(MOTOR3_IN2, OUTPUT);

  digitalWrite(MOTOR1_IN1, LOW); digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, LOW); digitalWrite(MOTOR2_IN4, LOW);
  digitalWrite(MOTOR3_IN1, LOW); digitalWrite(MOTOR3_IN2, LOW);

  pinMode(IR1_PIN, INPUT);
  pinMode(IR2_PIN, INPUT);

  // Inicializar canales PWM
  attachPWM(MOTOR1_EN);  attachPWM(MOTOR2_EN);
  attachPWM(WATER_EN);   attachPWM(OIL_EN);
  attachPWM(FILTER1_EN); attachPWM(FILTER2_EN);
  attachPWM(MOTOR3_EN);  attachPWM(PUMP4_EN);

  // Sensor DS18B20
  temperatureSensor.begin();
  temperatureSensor.setWaitForConversion(false);

  // Asegurar todo apagado al arranque
  allStop();

  Serial.println();
  Serial.print("Intentando conectar a WiFi: ");
  Serial.println(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[WiFi] Conectado exitosamente a la red local.");
    Serial.print("[WiFi] Dirección IP asignada: http://");
    Serial.println(WiFi.localIP());
  } else {
    // Si no conecta al router, activa su propia red inalámbrica
    Serial.println("\n[WiFi] No se pudo conectar a la red local.");
    Serial.println("[WiFi] Activando Punto de Acceso (Modo AP inalámbrico)...");
    WiFi.mode(WIFI_AP);
    WiFi.softAP(ap_ssid, ap_password);
    Serial.print("[WiFi AP] Conéctate a la red: ");
    Serial.println(ap_ssid);
    Serial.print("[WiFi AP] Contraseña: ");
    Serial.println(ap_password);
    Serial.print("[WiFi AP] Dirección IP: http://");
    Serial.println(WiFi.softAPIP()); // Habitualmente 192.168.4.1
  }

  // Activar mDNS para acceder como http://maquina-jabon.local
  if (MDNS.begin("maquina-jabon")) {
    Serial.println("[mDNS] Acceso por nombre: http://maquina-jabon.local");
  }

  setupServer();
  Serial.println("Escribe HELP para ver los comandos en el Monitor Serial.");
}

// ---------------- BUCLE PRINCIPAL (LOOP) ----------------
void loop() {
  server.handleClient();

  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');
    String res = processCommand(command);
    if (res.length() > 0) Serial.println(res);
  }

  updateTemperature();

  if (hourMode && millis() - hourStartMillis >= HOUR_DURATION) {
    motorsStop();
    Serial.println("Prueba de 1 hora finalizada. Motores detenidos.");
  }
}
