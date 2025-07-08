# python_pin_hasher.py
# --- TEMPORARY PIN HASHER ---
# Use this file to get a hash for a test PIN.
# DELETE THIS FILE IMMEDIATELY AFTER USE FOR SECURITY.

import bcrypt
import re # For regular expression validation

# <-- *** CHANGE THIS to the 4-digit PIN you want to hash! ***
test_pin = "1234" 

# Validate that the PIN is a 4-digit string
if not re.fullmatch(r'\d{4}', test_pin):
    print("Error: Please provide a valid 4-digit PIN.")
    exit()

# Generate a salt for bcrypt. The default rounds (12) are usually good.
# It's important to use bcrypt.gensalt() so a unique salt is generated each time.
salt = bcrypt.gensalt()

# Hash the PIN. bcrypt.hashpw expects bytes, so encode the PIN.
# password_hash in PHP also uses a similar mechanism.
hashed_pin = bcrypt.hashpw(test_pin.encode('utf-8'), salt)

# Decode the hashed PIN to a string for display
hashed_pin_str = hashed_pin.decode('utf-8')

print("The PIN hash for '{}' is:".format(test_pin))
print("<strong>{}</strong>\n".format(hashed_pin_str))
print("Copy the hash and paste it into the 'pin_hash' field in your 'nfc_card' table in phpMyAdmin.")
print("\n<strong style='color:red;'>REMEMBER TO DELETE THIS FILE AFTER YOU HAVE THE HASH!</strong>")

