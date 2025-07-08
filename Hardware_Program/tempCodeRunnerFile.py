import serial
import serial.tools.list_ports
import time

def detect_esp32_port():
    ports = list(serial.tools.list_ports.comports())
    for p in ports:
        if ("USB" in p.description or "CP210" in p.description or "CH340" in p.description) and "COM" in p.device:
            print(f"✅ Detected likely ESP32 port: {p.device} ({p.description})")
            return p.device
    print("❌ Could not detect ESP32 automatically.")
    return None

def connect_serial(port):
    try:
        ser = serial.Serial(port, 115200, timeout=2)
        time.sleep(2)  # Wait for ESP32 to reset
        print(f"🔌 Connected to {port}")
        return ser
    except serial.SerialException as e:
        print(f"⚠️ Serial connection failed: {e}")
        return None

def main():
    port = detect_esp32_port()
    if not port:
        return

    ser = connect_serial(port)
    if not ser:
        return

    # Flush any old buffer
    ser.flushInput()

    print("📡 Ready to send commands to ESP32.")
    print("👉 Type 'READ 4' or 'WRITE 4 Hello World'")
    print("👉 Type 'exit' to quit.\n")

    while True:
        cmd = input("💬 Command: ").strip()
        if cmd.lower() in ['exit', 'quit']:
            print("👋 Exiting.")
            break

        ser.write((cmd + '\n').encode())

        # Read lines until something comes back
        while True:
            response = ser.readline().decode().strip()
            if response:
                print("🟢 ESP32 >", response)
                break

if __name__ == "__main__":
    main()
