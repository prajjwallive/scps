# read_nfc_once.py
# This script reads NFC data from an ESP32 connected via serial,
# prints the NFC ID, and plays a system sound upon successful read.

import serial
import serial.tools.list_ports
import time
import sys
import re # Import the regular expression module
import os # For path manipulation
from playsound import playsound # Import the playsound library

# --- Configuration ---
# IMPORTANT: Adjust this path to your sound file!
# It should be relative to where this Python script is located.
NFC_SOUND_FILE = os.path.join(os.path.dirname(__file__), 'sounds', 'ding.wav')
# You might need to change 'nfc_read.mp3' to 'beep.wav' or whatever your file is named.
# If your sound file is directly in scps1/, use: NFC_SOUND_FILE = os.path.join(os.path.dirname(__file__), 'beep.wav')
# --- End Configuration ---

# Function to detect the ESP32 serial port
def detect_esp32_port():
    """
    Automatically detects the serial port where an ESP32 is likely connected.
    Looks for common descriptions associated with ESP32 USB-to-serial chips.
    Returns the port name (e.g., 'COM3', '/dev/ttyUSB0') or None if not found.
    """
    ports = list(serial.tools.list_ports.comports())
    for p in ports:
        # Check for common keywords in description and ensure it's a COM port (Windows)
        # or ttyUSB/ttyACM (Linux) or cu.usbserial (macOS)
        if ("USB" in p.description or "CP210" in p.description or "CH340" in p.description or "ACM" in p.description or "USB Serial" in p.description) \
           and ("COM" in p.device or "ttyUSB" in p.device or "ttyACM" in p.device or "cu.usbserial" in p.device):
            return p.device
    return None

# Function to connect to the serial port
def connect_serial(port):
    """
    Establishes a serial connection to the specified port.
    Returns the serial object or None if connection fails.
    """
    try:
        # 115200 baud rate, 2-second timeout for read operations
        ser = serial.Serial(port, 115200, timeout=2)
        time.sleep(2)  # Give the ESP32 time to reset after connection
        return ser
    except serial.SerialException as e:
        # Print error to stderr for logging purposes, not stdout (which we'll parse)
        print(f"ERROR: Serial connection failed: {e}", file=sys.stderr)
        return None

# Main function to read NFC data once
def read_nfc_data_once():
    """
    Attempts to read NFC data from the detected ESP32 and prints the extracted NFC ID.
    Outputs "ERROR: <message>" to stderr if an error occurs.
    Outputs the NFC ID (e.g., "1019") to stdout upon success.
    Plays a sound if an NFC ID is successfully read.
    """
    port = detect_esp32_port()
    if not port:
        print("ERROR: Could not detect ESP32 automatically.", file=sys.stderr)
        return

    ser = connect_serial(port)
    if not ser:
        return # Error already printed by connect_serial

    try:
        ser.flushInput() # Clear any old data in the buffer

        # Send the "READ 4" command to the ESP32 to read block 4 (adjust block number as needed)
        # IMPORTANT: Your ESP32 sketch MUST be programmed to:
        # 1. Listen for this command on Serial.
        # 2. Perform an NFC read upon receiving it.
        # 3. Print the RAW NFC ID (e.g., "1019") to Serial.
        #    Example ESP32 serial output after reading card: "DATA: 1019\n" (or just "1019\n")
        ser.write(b'READ 4\n') # Send command, with newline

        # Read lines until a response is found or timeout occurs
        nfc_id = None
        start_time = time.time()
        timeout_seconds = 10 # Max time to wait for NFC data

        while time.time() - start_time < timeout_seconds:
            if ser.in_waiting > 0:
                response = ser.readline().decode().strip() # Read a line and strip whitespace
                if response:
                    # Attempt to extract NFC ID using a regular expression.
                    match = re.search(r"(?:DATA:\s*)?([a-zA-Z0-9]+)", response)
                    if match:
                        nfc_id = match.group(1) # Get the captured ID (e.g., "1019")
                    else:
                        # Fallback: if no specific pattern found, assume the whole response (first word) is the ID
                        nfc_id = response.split(' ')[0]

                    if nfc_id: # Ensure we successfully extracted something
                        break
            time.sleep(0.05) # Small delay to prevent busy-waiting

        if nfc_id:
            # Print only the extracted NFC ID to stdout. This is what the PHP script will capture.
            print(nfc_id)
            sys.stdout.flush() # Ensure the output is immediately sent

            # --- Play sound notification ---
            try:
                if os.path.exists(NFC_SOUND_FILE):
                    playsound(NFC_SOUND_FILE)
                else:
                    print(f"ERROR: Sound file not found at: {NFC_SOUND_FILE}", file=sys.stderr)
            except Exception as sound_e:
                print(f"ERROR: Could not play sound: {sound_e}", file=sys.stderr)
            # --- End Play sound notification ---

        else:
            print("ERROR: No valid NFC data received within timeout or card not tapped.", file=sys.stderr)

    except serial.SerialException as e:
        print(f"ERROR: Serial communication error: {e}", file=sys.stderr)
    except Exception as e:
        print(f"ERROR: An unexpected error occurred in Python script: {e}", file=sys.stderr)
    finally:
        if ser and ser.is_open:
            ser.close()

if __name__ == "__main__":
    read_nfc_data_once()
