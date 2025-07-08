import serial
import webbrowser
import time

# Adjust COM port as needed
ser = serial.Serial('COM9', 115200, timeout=2)
time.sleep(2)  # Wait for ESP32 reset

def read_block(block=4):
    ser.write(f"READ {block}\n".encode())
    while True:
        line = ser.readline().decode().strip()
        print("ESP32:", line)
        if line.startswith("DATA:"):
            return line[6:].strip()
        elif line.startswith("ERROR"):
            return None

while True:
    print("Waiting for NFC tag...")
    data = read_block()
    if data:
        name = data.lower()
        if "1000" in name:
            webbrowser.open("https://www.youtube.com")
        elif "saurav" in name:
            webbrowser.open("https://www.facebook.com")
        else:
            webbrowser.open("https://www.google.com")
        break
    time.sleep(1)
