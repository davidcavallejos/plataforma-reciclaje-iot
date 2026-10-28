/*
===============================================================================
MAQUINA DE JABON - CODIGO INTEGRADO + CONTROL WEB
ESP32 + 4 L298N:
  L298N #1: dos motoreductores del tanque principal
  L298N #2: bomba de agua y bomba de aceite
  L298N #3: dos bombas de filtrado
  L298N #4: motoreductor nuevo y bomba nueva

Conserva los comandos de las etapas anteriores.
ELIMINADOS: servo MG995, motor 28BYJ-48 y controlador ULN2003.
Arduino-ESP32 core 3.x (usa ledcAttach/ledcWrite por pin).
AÑADIDO: Conexión WiFi y Servidor Web para control remoto.

ANTES DE ENCENDER:
1) Desconecta todas las fuentes mientras cableas. No abras la fuente ATX.
2) Une GND de la ATX, todos los L298N y el GND del ESP32.
3) No alimentes motores ni bombas desde el pin 3V3 del ESP32.
4) Retira los jumpers ENA/ENB de cada L298N si controlas velocidad por PWM.
   No confundas esos jumpers con el jumper 5V-EN. Sigue el esquema del modulo.
5) En L298N #2 y #3, segun el cableado existente, IN1/IN3 van a 3V3 e
   IN2/IN4 a GND para fijar un sentido.
6) En L298N #4: motor nuevo a OUT1/OUT2; bomba nueva a OUT3/OUT4.
   IN3 del L298N #4 a 3V3 e IN4 a GND para un sentido fijo de bomba.
   ENA -> GPIO23; IN1 -> GPIO22; IN2 -> GPIO4; ENB -> GPIO32.
7) Mover IR1 OUT de GPIO32 a GPIO35. IR2 permanece en GPIO34.
   GPIO34 y GPIO35 son solo entradas y no tienen pull-up/down interno.
   Si los sensores IR se alimentan a 5 V y OUT puede dar 5 V, usa divisor
   o convertidor de nivel: los GPIO del ESP32 admiten como maximo 3.3 V.
8) DS18B20: DATA -> GPIO21, VDD -> 3V3, GND -> GND; resistencia 4.7k entre
   DATA y 3V3, salvo que el modulo ya la incorpore.
9) L298N tiene caida de tension y puede no dar suficiente voltaje a cargas
   nominales de 5 V. Comprueba corriente de arranque, voltaje y temperatura.
10) ALLSTOP es parada por software, no parada de emergencia fisica. Instala
    un interruptor fisico que corte la alimentacion de las cargas.
11) DS18B20 solo informa temperatura; no controla automaticamente la reaccion
    de sosa caustica. La disolucion de sosa es exotermica y peligrosa.

MAPA GPIO:
L298N #1:
  GPIO27 -> ENA; GPIO16 -> IN1; GPIO17 -> IN2; OUT1/OUT2 -> motoreductor 1
  GPIO33 -> ENB; GPIO18 -> IN3; GPIO19 -> IN4; OUT3/OUT4 -> motoreductor 2
L298N #2:
  GPIO13 -> ENA (agua); GPIO14 -> ENB (aceite)
L298N #3:
  GPIO25 -> ENA (filtro 1); GPIO26 -> ENB (filtro 2)
L298N #4:
  GPIO23 -> ENA (motoreductor 3); GPIO22 -> IN1; GPIO4 -> IN2
  GPIO32 -> ENB (bomba 4); IN3 -> 3V3; IN4 -> GND (sentido fijo)
Sensores:
  IR1 OUT -> GPIO35; IR2 OUT -> GPIO34; DS18B20 DATA -> GPIO21

COMANDOS DISPONIBLES (Monitor Serie o Web):
  HELP / STATUS / SENSORS
  START / STOP / V 80 / HOUR
  WATER ON / WATER OFF / WATER 80
  OIL 80 / OIL OFF / OIL ON
  FILTER1 ON / FILTER1 OFF / FILTER1 80
  FILTER2 ON / FILTER2 OFF / FILTER2 80
  FSTOP / PSTOP / ALLSTOP
  MOTOR3 ON / MOTOR3 OFF / MOTOR3 SPEED 100 / MOTOR3 CW / MOTOR3 CCW
  PUMP4 ON / PUMP4 OFF / PUMP4 100
  IR1 / IR2 / TEMP / SENSORS
===============================================================================
*/

#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ---------------- CONFIGURACION WIFI Y WEB ----------------
// 1) Modo Red Local (Router): Coloca aquí los datos de tu red WiFi
const char* ssid = "TU_NOMBRE_DE_WIFI";       
const char* password = "TU_CONTRASEÑA_DE_WIFI"; 

// 2) Modo Punto de Acceso Directo (Si no hay router, el ESP32 crea su propia red)
const char* ap_ssid = "Maquina_Jabon_ESP32";
const char* ap_password = "jabon123";

// Credenciales para el acceso HTTP (deben coincidir con el .env de Laravel)
const char* www_username = "admin";           
const char* www_password = "jabon123";        

WebServer server(80);

// ---------------- PWM ----------------
const int PWM_FREQ = 1000;
const int PWM_BITS = 8;  // rango 0..255

// ---------------- L298N #1: DOS MOTOREDUCTORES ----------------
const int MOTOR1_EN  = 27;  // ENA
const int MOTOR1_IN1 = 16;
const int MOTOR1_IN2 = 17;

const int MOTOR2_EN  = 33;  // ENB
const int MOTOR2_IN3 = 18;
const int MOTOR2_IN4 = 19;

// ---------------- L298N #2: AGUA Y ACEITE ----------------
const int WATER_EN = 13; // ENA
const int OIL_EN   = 14; // ENB

// ---------------- L298N #3: FILTRADO ----------------
const int FILTER1_EN = 25; // ENA
const int FILTER2_EN = 26; // ENB

// ---------------- L298N #4: NUEVO MOTOREDUCTOR Y BOMBA ----------------
const int MOTOR3_EN  = 23; // ENA
const int MOTOR3_IN1 = 22;
const int MOTOR3_IN2 = 4;
const int PUMP4_EN   = 32; // ENB

// ---------------- SENSORES ----------------
const int IR1_PIN = 35;
const int IR2_PIN = 34;
const int DS18B20_PIN = 21;

const int IR1_ACTIVE_LEVEL = LOW;
const int IR2_ACTIVE_LEVEL = LOW;

OneWire oneWire(DS18B20_PIN);
DallasTemperature temperatureSensor(&oneWire);

// ---------------- VELOCIDADES (VALORES INICIALES) ----------------
int motorSpeed = 150;
int waterSpeed = 255;       // Arranca al máximo
int oilSpeed = 150;         // CORREGIDO: Ahora arranca a 150 al darle ON
int filter1Speed = 150;     // CORREGIDO: Ahora arranca a 150 al darle ON
int filter2Speed = 150;     // CORREGIDO: Ahora arranca a 150 al darle ON
int motor3Speed = 120;
int pump4Speed = 150;       // CORREGIDO: Ahora arranca a 150 al darle ON

// ---------------- ESTADO ----------------
bool motorsRunning = false;     // motores 1 y 2
bool motor3Running = false;
bool hourMode = false;
int motor3Direction = 1;         // 1 = CW; -1 = CCW

unsigned long hourStartMillis = 0;
const unsigned long HOUR_DURATION = 3600000UL;

// Variables para temperatura no bloqueante
float lastTempC = 0.0;
unsigned long lastTempReadMillis = 0;

// ---------------- HTML DE LA WEB ----------------
const char INDEX_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Máquina de Jabón</title>
  <style>
    body { font-family: Arial, sans-serif; background: #222; color: #eee; margin: 0; padding: 15px; text-align: center; }
    h1 { color: #4CAF50; margin-bottom: 5px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 800px; margin: auto; }
    .card { background: #333; padding: 15px; border-radius: 8px; border: 1px solid #444; }
    .card h3 { margin-top: 0; color: #00bcd4; font-size: 16px; }
    button { background: #444; color: white; border: none; padding: 10px; margin: 4px; border-radius: 5px; cursor: pointer; font-weight: bold; width: 45%; }
    button:hover { background: #666; }
    button.on { background: #4CAF50; color: white; }
    button.off { background: #f44336; color: white; }
    button.stop { background: #d32f2f; width: 95%; padding: 15px; font-size: 18px; margin-top: 15px; }
    input[type=number] { width: 60px; padding: 8px; border-radius: 5px; border: none; text-align: center; }
    .status { font-size: 14px; color: #aaa; margin-top: 10px; }
    @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <h1>🧼 Máquina de Jabón</h1>
  <div class="status" id="status">Cargando estado...</div>

  <div class="grid">
    <div class="card">
      <h3>Motores 1 y 2</h3>
      <button class="on" onclick="cmd('START')">START</button>
      <button class="off" onclick="cmd('STOP')">STOP</button><br><br>
      Velocidad: <input type="number" id="v_mot" value="150">
      <button onclick="cmd('V ' + document.getElementById('v_mot').value)">Aplicar</button>
    </div>

    <div class="card">
      <h3>Bomba Agua</h3>
      <button class="on" onclick="cmd('WATER ON')">ON</button>
      <button class="off" onclick="cmd('WATER OFF')">OFF</button><br><br>
      Velocidad: <input type="number" id="v_wat" value="255">
      <button onclick="cmd('WATER ' + document.getElementById('v_wat').value)">Aplicar</button>
    </div>

    <div class="card">
      <h3>Bomba Aceite</h3>
      <button class="on" onclick="cmd('OIL ON')">ON</button>
      <button class="off" onclick="cmd('OIL OFF')">OFF</button><br><br>
      Velocidad: <input type="number" id="v_oil" value="150">
      <button onclick="cmd('OIL ' + document.getElementById('v_oil').value)">Aplicar</button>
    </div>

    <div class="card">
      <h3>Filtros 1 y 2</h3>
      <button class="on" onclick="cmd('FILTER1 ON')">F1 ON</button>
      <button class="off" onclick="cmd('FILTER1 OFF')">F1 OFF</button>
      <button class="on" onclick="cmd('FILTER2 ON')">F2 ON</button>
      <button class="off" onclick="cmd('FILTER2 OFF')">F2 OFF</button>
    </div>

    <div class="card">
      <h3>Motoreductor 3 (Nuevo)</h3>
      <button class="on" onclick="cmd('MOTOR3 CW')">Giro CW</button>
      <button class="on" onclick="cmd('MOTOR3 CCW')">Giro CCW</button>
      <button class="off" style="width:95%" onclick="cmd('MOTOR3 OFF')">DETENER M3</button><br><br>
      Velocidad: <input type="number" id="v_m3" value="120">
      <button onclick="cmd('MOTOR3 SPEED ' + document.getElementById('v_m3').value)">Aplicar</button>
    </div>

    <div class="card">
      <h3>Bomba 4 (Nueva)</h3>
      <button class="on" onclick="cmd('PUMP4 ON')">ON</button>
      <button class="off" onclick="cmd('PUMP4 OFF')">OFF</button><br><br>
      Velocidad: <input type="number" id="v_p4" value="150">
      <button onclick="cmd('PUMP4 ' + document.getElementById('v_p4').value)">Aplicar</button>
    </div>
  </div>

  <button class="stop" onclick="cmd('ALLSTOP')">🚨 PARADA TOTAL (ALLSTOP)</button>

  <script>
    function cmd(c) {
      fetch('/cmd?c=' + encodeURIComponent(c))
        .then(r => r.text())
        .then(t => { updateStatus(); });
    }

    function updateStatus() {
      fetch('/status')
        .then(r => r.json())
        .then(d => {
          document.getElementById('status').innerHTML = 
            `Motores: ${d.motors ? 'ON' : 'OFF'} | Temp: ${d.temp}°C | M3: ${d.m3} | P4: ${d.p4} | Agua: ${d.water}`;
        });
    }

    setInterval(updateStatus, 2000);
    updateStatus();
  </script>
</body>
</html>
)rawliteral";

// ---------------- PWM HELPERS ----------------
bool attachPWM(int pin) {
  bool ok = ledcAttach(pin, PWM_FREQ, PWM_BITS);
  if (ok) ledcWrite(pin, 0);
  return ok;
}

void setPWM(int pin, int speed) {
  speed = constrain(speed, 0, 255);
  ledcWrite(pin, speed);
}

// ---------------- MOTORES 1 Y 2 ----------------
void motorsStart() {
  digitalWrite(MOTOR1_IN1, HIGH);
  digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, HIGH);
  digitalWrite(MOTOR2_IN4, LOW);
  setPWM(MOTOR1_EN, motorSpeed);
  setPWM(MOTOR2_EN, motorSpeed);
  motorsRunning = (motorSpeed > 0);
}

void motorsStop() {
  setPWM(MOTOR1_EN, 0);
  setPWM(MOTOR2_EN, 0);
  digitalWrite(MOTOR1_IN1, LOW);
  digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, LOW);
  digitalWrite(MOTOR2_IN4, LOW);
  motorsRunning = false;
  hourMode = false;
}

// ---------------- NUEVO MOTOREDUCTOR (L298N #4) ----------------
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

// ---------------- BOMBAS ----------------
void waterOn()  { setPWM(WATER_EN, waterSpeed); }
void waterOff() { setPWM(WATER_EN, 0); }
void oilOn()    { setPWM(OIL_EN, oilSpeed); }
void oilOff()   { setPWM(OIL_EN, 0); }
void filter1On()  { setPWM(FILTER1_EN, filter1Speed); }
void filter1Off() { setPWM(FILTER1_EN, 0); }
void filter2On()  { setPWM(FILTER2_EN, filter2Speed); }
void filter2Off() { setPWM(FILTER2_EN, 0); }
void pump4On()  { setPWM(PUMP4_EN, pump4Speed); }
void pump4Off() { setPWM(PUMP4_EN, 0); }

void pumpsStop() {
  waterOff(); oilOff(); filter1Off(); filter2Off(); pump4Off();
}

void allStop() {
  motorsStop(); motor3Off(); pumpsStop();
}

// ---------------- SENSORES IR ----------------
String getIRState(int pin, int activeLevel, const char *name) {
  int state = digitalRead(pin);
  String res = String(name) + ": ";
  res += (state == activeLevel) ? "DETECCION ACTIVA" : "SIN DETECCION";
  return res;
}

// ---------------- LECTURA DE TEMPERATURA ----------------
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

// ---------------- PROCESAR COMANDOS ----------------
String processCommand(String command) {
  command.trim();
  command.toUpperCase();
  if (command.length() == 0) return "";

  String response = "Comando: " + command + "\n";

  // Motores principales
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
  // Agua
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
  // Aceite
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
  // Filtro 1
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
  // Filtro 2
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
  // Nuevo motoreductor
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
  // Bomba nueva
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
  // Paradas
  else if (command == "FSTOP") {
    filter1Off(); filter2Off(); response += "Filtros apagados.";
  }
  else if (command == "PSTOP") {
    pumpsStop(); response += "Todas las bombas apagadas.";
  }
  else if (command == "ALLSTOP") {
    allStop(); response += "¡PARADA TOTAL! Todo apagado.";
  }
  // Sensores
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

// ---------------- SERVIDOR WEB ----------------
void setupServer() {
  server.on("/", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    server.send_P(200, "text/html", INDEX_HTML);
  });

  server.on("/cmd", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    String c = server.arg("c");
    String res = processCommand(c);
    Serial.println("[WEB] " + res);
    server.send(200, "text/plain", res);
  });

  server.on("/status", []() {
    if (!server.authenticate(www_username, www_password)) return server.requestAuthentication();
    server.send(200, "application/json", getStatusJson());
  });

  server.begin();
  Serial.println("Servidor Web iniciado.");
}

// ---------------- SETUP PRINCIPAL ----------------
void setup() {
  Serial.begin(115200);
  delay(500);

  pinMode(MOTOR1_IN1, OUTPUT); pinMode(MOTOR1_IN2, OUTPUT);
  pinMode(MOTOR2_IN3, OUTPUT); pinMode(MOTOR2_IN4, OUTPUT);
  pinMode(MOTOR3_IN1, OUTPUT); pinMode(MOTOR3_IN2, OUTPUT);

  digitalWrite(MOTOR1_IN1, LOW); digitalWrite(MOTOR1_IN2, LOW);
  digitalWrite(MOTOR2_IN3, LOW); digitalWrite(MOTOR2_IN4, LOW);
  digitalWrite(MOTOR3_IN1, LOW); digitalWrite(MOTOR3_IN2, LOW);

  pinMode(IR1_PIN, INPUT);
  pinMode(IR2_PIN, INPUT);

  bool pwmOK = true;
  pwmOK &= attachPWM(MOTOR1_EN); pwmOK &= attachPWM(MOTOR2_EN);
  pwmOK &= attachPWM(WATER_EN);  pwmOK &= attachPWM(OIL_EN);
  pwmOK &= attachPWM(FILTER1_EN);pwmOK &= attachPWM(FILTER2_EN);
  pwmOK &= attachPWM(MOTOR3_EN); pwmOK &= attachPWM(PUMP4_EN);

  temperatureSensor.begin();
  temperatureSensor.setWaitForConversion(false);

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
    Serial.println("\n[WiFi] No se pudo conectar a la red local.");
    Serial.println("[WiFi] Activando Punto de Acceso (Modo AP inalámbrico)...");
    WiFi.mode(WIFI_AP);
    WiFi.softAP(ap_ssid, ap_password);
    Serial.print("[WiFi AP] Conéctate a la red: ");
    Serial.println(ap_ssid);
    Serial.print("[WiFi AP] Contraseña: ");
    Serial.println(ap_password);
    Serial.print("[WiFi AP] Dirección IP: http://");
    Serial.println(WiFi.softAPIP());
  }

  if (MDNS.begin("maquina-jabon")) {
    Serial.println("[mDNS] Acceso por nombre: http://maquina-jabon.local");
  }

  setupServer();
  Serial.println("Escribe HELP para ver los comandos en el Monitor Serial.");
}

// ---------------- LOOP PRINCIPAL ----------------
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