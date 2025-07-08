import serial
import time
import subprocess
import threading

# Configuration
COM_PORT = 'COM9'  # Change to your ESP32 serial port
BLOCK_TO_READ = 4

ser = serial.Serial(COM_PORT, 115200, timeout=2)
time.sleep(2)

shutdown_thread = None
shutdown_active = False

def schedule_shutdown():
    global shutdown_active
    shutdown_active = True
    print("🛑 Shutdown in 20 seconds...")
    subprocess.call("shutdown /s /t 40", shell=True)

def cancel_shutdown():
    global shutdown_active
    if shutdown_active:
        print("✅ Shutdown canceled by Prajjwal!")
        subprocess.call("shutdown /a", shell=True)
        shutdown_active = False

def read_nfc_block():
    ser.write(f"READ {BLOCK_TO_READ}\n".encode())
    while True:
        line = ser.readline().decode().strip()
        if line.startswith("DATA:"):
            return line[6:].strip().lower()
        elif line.startswith("ERROR"):
            return None

print("🌀 Waiting for NFC tags...")

while True:
    name = read_nfc_block()
    if name:
        print(f"📲 Tag detected: {name}")

        if "1000" in name and not shutdown_active:
            # Start shutdown in a separate thread
            shutdown_thread = threading.Thread(target=schedule_shutdown)
            shutdown_thread.start()

        elif "prajjwal" in name and shutdown_active:
            cancel_shutdown()

    time.sleep(1)
# Clean up serial connection on exit