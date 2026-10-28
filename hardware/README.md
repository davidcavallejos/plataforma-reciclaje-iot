# Hardware — ESP32 + 4 × L298N

Esta carpeta contiene el firmware de la máquina. La aplicación Laravel **no**
reemplaza al ESP32: lo comanda por HTTP y lee su estado. Este documento es el
contrato que deben respetar ambos lados.

## Archivos

| Archivo | Descripción |
|---|---|
| `Maquina_jabon_sin_servo_con_28BYJ48.ino` | Firmware completo: PWM, sensores, servidor web propio. |

## Mapa de pines (GPIO)

| Módulo | Señal | GPIO | Componente |
|---|---|---|---|
| L298N #1 | ENA | 27 | Motoreductor 1 |
| | IN1 / IN2 | 16 / 17 | |
| | ENB | 33 | Motoreductor 2 |
| | IN3 / IN4 | 18 / 19 | |
| L298N #2 | ENA | 13 | Bomba de agua |
| | ENB | 14 | Bomba de aceite |
| L298N #3 | ENA | 25 | Filtro 1 |
| | ENB | 26 | Filtro 2 |
| L298N #4 | ENA | 23 | Motoreductor 3 |
| | IN1 / IN2 | 22 / 4 | |
| | ENB | 32 | Bomba 4 |
| Sensores | IR1 / IR2 | 35 / 34 | Fin de carrera de molde (activo en LOW) |
| | DS18B20 | 21 | Temperatura (OneWire) |

GPIO 34 y 35 **no tienen pull-up interno** y no toleran 5 V: usa divisor de
tensión si el módulo IR se alimenta a 5 V.

## PWM

Resolución de 8 bits (`0..255`), frecuencia 1 kHz (`ledcAttach`/`ledcWrite`).

| Componente | Comando | Valor por defecto del firmware |
|---|---|---|
| Motores 1 y 2 (L298N #1) | `V <0-255>` | 150 |
| Agua | `WATER <0-255>` | 255 |
| Aceite | `OIL <0-255>` | 150 |
| Filtro 1 | `FILTER1 <0-255>` | 150 |
| Filtro 2 | `FILTER2 <0-255>` | 150 |
| Motoreductor 3 | `MOTOR3 SPEED <0-255>` | 120 |
| Bomba 4 | `PUMP4 <0-255>` | 150 |

> **Ojo con los valores por defecto.** El prompt del sistema fija como valores
> de la web `FILTER1 250`, `WATER 200`, `OIL 250`, `FILTER2 250`, `PUMP4 250`,
> `L298N #1 150` y `MOTOR3` igual a `L298N #1`. El firmware arranca con otros
> valores (255 en agua, 120 en el motor 3, etc.). Por eso el panel **debe
> enviar el PWM explícito** al activar un perfil: no puede confiar en los
> valores de arranque del ESP32.

## Protocolo HTTP del ESP32

Autenticación Basic (`admin` / `jabon123`) en todos los endpoints.

| Endpoint | Respuesta |
|---|---|
| `GET /` | Panel HTML de emergencia servido por el propio ESP32. |
| `GET /cmd?c=<COMANDO>` | Texto plano con el resultado. |
| `GET /status` | JSON: `{"motors":bool,"temp":float,"m3":"CW\|CCW\|OFF","p4":int,"water":int}` |

### Comandos

```
HELP / STATUS / SENSORS
START / STOP / V <0-255> / HOUR
WATER ON | WATER OFF | WATER <0-255>
OIL ON | OIL OFF | OIL <0-255>
FILTER1 ON | FILTER1 OFF | FILTER1 <0-255>
FILTER2 ON | FILTER2 OFF | FILTER2 <0-255>
FSTOP / PSTOP / ALLSTOP
MOTOR3 ON | MOTOR3 OFF | MOTOR3 SPEED <0-255> | MOTOR3 CW | MOTOR3 CCW
PUMP4 ON | PUMP4 OFF | PUMP4 <0-255>
IR1 / IR2 / TEMP
```

`ALLSTOP` es una parada **por software**. No sustituye a un interruptor físico
de corte de alimentación.

## Brechas entre el firmware actual y lo que pide el panel

Estas funciones están en los requisitos de la web pero **todavía no existen en
el `.ino`**; hay que decidir dónde implementarlas antes de programar los
controladores:

1. **Automatización IR.** El firmware solo *lee* IR1/IR2; no apaga `FILTER1` ni
   enciende el L298N #1 al detectar molde lleno.
2. **Intervalos de aceite (ON/OFF/repeticiones).** No hay temporización por
   pulsos; solo encendido/apagado manual.
3. **Temporizadores ("encender 15 s") y programación por hora.** Solo existe
   `HOUR` (60 min fijos) para los motores 1 y 2.
4. **Estado incompleto.** `/status` no informa filtros, aceite ni el estado de
   los relés de dirección, así que la web no puede reflejarlos sin ampliar el JSON.

Recomendación: implementar la automatización IR y los pulsos de aceite **en el
firmware** (siguen funcionando si se cae el WiFi) y dejar en Laravel la
orquestación de perfiles, registro de eventos y reportes. La base de datos ya
guarda esos parámetros en `perfiles.configuraciones_json`.
