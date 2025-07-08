#include <SPI.h>
#include <Adafruit_PN532.h>

#define PN532_SCK  (18)
#define PN532_MOSI (23)
#define PN532_SS   (5)
#define PN532_MISO (19)

Adafruit_PN532 nfc(PN532_SS);

void setup(void) {
  Serial.begin(115200);
  nfc.begin();
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata) {
    Serial.println("ERROR: PN532 not found");
    while (1);
  }
  nfc.SAMConfig();
  Serial.println("READY");
}

void loop(void) {
  if (Serial.available()) {
    String input = Serial.readStringUntil('\n');
    input.trim();

    if (input.startsWith("READ")) {
      int block = input.substring(5).toInt();
      readBlock(block);
    } else if (input.startsWith("WRITE")) {
      int spaceIndex = input.indexOf(' ', 6);
      int block = input.substring(6, spaceIndex).toInt();
      String data = input.substring(spaceIndex + 1);
      writeBlock(block, data);
    }
  }
}

void readBlock(int blockNumber) {
  uint8_t uid[] = { 0, 0, 0, 0, 0, 0, 0 };
  uint8_t uidLength;

  if (nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength)) {
    if (nfc.mifareclassic_AuthenticateBlock(uid, uidLength, blockNumber, 0, (uint8_t[]){0xFF,0xFF,0xFF,0xFF,0xFF,0xFF})) {
      uint8_t data[16];
      if (nfc.mifareclassic_ReadDataBlock(blockNumber, data)) {
        Serial.print("DATA: ");
        for (int i = 0; i < 16; i++) {
          if (data[i] >= 32 && data[i] <= 126) Serial.print((char)data[i]);
          else Serial.print('.');
        }
        Serial.println();
      } else {
        Serial.println("ERROR: Read failed");
      }
    } else {
      Serial.println("ERROR: Auth failed");
    }
  } else {
    Serial.println("ERROR: No NFC tag");
  }
}

void writeBlock(int blockNumber, String content) {
  uint8_t uid[] = { 0, 0, 0, 0, 0, 0, 0 };
  uint8_t uidLength;

  if (nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength)) {
    if (nfc.mifareclassic_AuthenticateBlock(uid, uidLength, blockNumber, 0, (uint8_t[]){0xFF,0xFF,0xFF,0xFF,0xFF,0xFF})) {
      uint8_t data[16] = { 0 };
      for (int i = 0; i < content.length() && i < 16; i++) {
        data[i] = content[i];
      }
      if (nfc.mifareclassic_WriteDataBlock(blockNumber, data)) {
        Serial.println("SUCCESS: Write done");
      } else {
        Serial.println("ERROR: Write failed");
      }
    } else {
      Serial.println("ERROR: Auth failed");
    }
  } else {
    Serial.println("ERROR: No NFC tag");
  }
}
