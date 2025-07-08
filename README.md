# 🥗 Smart Canteen Payment System

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Platform](https://img.shields.io/badge/platform-Web%20%7C%20IoT%20%7C%20ESP32-lightgrey)]()
[![Tech Stack](https://img.shields.io/badge/Tech-HTML%20%7C%20CSS%20%7C%20PHP%20%7C%20MySQL%20%7C%20ESP32-green)]()

## 🚀 Overview
The **Smart Canteen Payment System** is a modern, IoT-enabled platform that automates traditional canteen operations.  
It integrates a **web application** with **NFC-based payment using ESP32 and PN532 modules**, offering a seamless, cashless, and transparent experience for students, staff, and administrators.

Designed to replace manual ordering and billing systems, it ensures:
- Faster transactions
- Automated billing & receipt generation
- Email notifications for every transaction
- Time-based menu scheduling
- Real-time sales tracking & reporting

---

## 🎯 Key Features

✅ **NFC-Enabled Payments**  
Students pay using NFC cards linked to prepaid balances, removing the need for cash handling.

✅ **Web-Based Dashboard**  
Separate interfaces for students, canteen staff, and administrators to view menus, track orders, and manage sales.

✅ **Time-Based Dynamic Menus**  
Menus automatically update based on the time of day (breakfast, lunch, dinner).

✅ **Automated Emails**  
Parents & students receive transaction emails, promoting transparency.

✅ **Sales Analytics & Reports**  
Admins can generate daily, weekly, and monthly sales summaries.

✅ **Semi-Automated Printing**  
Supports on-premise receipt printing, integrated with transactions.

---

## 🌐 Technology Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP, MySQL
- **IoT Hardware:** ESP32-WROOM DevKit V1, PN532 NFC Reader
- **Communication:** SPI between ESP32 and PN532
- **Notifications:** SMTP for sending automated emails

---

## 🚀 Future Enhancements

🔹 **Cloud Deployment:**  
Migrate backend to cloud servers for multi-campus support and higher scalability.

🔹 **Biometric Authentication:**  
Use fingerprint or facial recognition for secure payment confirmations.

🔹 **AI & ML Integration:**  
Predict demand, optimize menu planning, and monitor health-related consumption patterns.

🔹 **Expand NFC Use:**  
Integrate the same card for library checkouts, transportation, and attendance systems.

---

## 📸 Screenshots

<p align="center">
  <img src="/screenshots/dashboard.png" alt="Dashboard" width="320"/> 
  <img src="/screenshots/SPI_Connection.png" alt="Connection" width="320"/>
</p>
<p align="center">
  <img src="/screenshots/time-menu.png" alt="Time-Menu" width="320"/> 
  <img src="/screenshots/transaction.png" alt="Transaction" width="320"/>
</p>
<p align="center">
  <img src="/screenshots/Reciept.jpg" alt="Receipt" width="320"/> 
  <img src="/screenshots/Hardware.jpg" alt="Hardware" width="320"/>
</p>
<p align="center">
  <img src="/screenshots/billing1.png" alt="Billing" width="320"/> 
  <img src="/screenshots/billing2.png" alt="Printing" width="320"/>
</p>


---

## ⚙️ Getting Started

### 🚀 Prerequisites
- ESP32-WROOM DevKit V1
- PN532 NFC Module
- XAMPP / LAMP stack for PHP + MySQL
- SMTP credentials for email

### 🔥 Quick Setup

```bash
# Clone the repository
git clone https://github.com/prajjwallive/scps
cd smart-canteen-system

# Set up database
# Import `canteen.sql` into your MySQL server

# Configure backend
cp config.sample.php config.php
# Edit database credentials and SMTP settings
 Flash ESP32
Upload provided Arduino sketches (located in /esp32-nfc-code/) using Arduino IDE.

📝 License
This project is licensed under the MIT License.

🤝 Contributing
Pull requests are welcome! If you find any issue or have feature suggestions, please open an issue or submit a PR.

✨ Author
👤 Prajjwal Adhikari, Aman Paudel, Raj Gurung, Rozal Dahal
🚀 Developed as part of my final year engineering capstone project.
