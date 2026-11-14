import re

sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"
with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    sql_text = f.read()

# Let's inspect where example.com is used
print("=== Lines with example.com ===")
for i, line in enumerate(sql_text.splitlines()):
    if "example.com" in line.lower():
        # Find which table this belongs to by looking upwards
        print(f"Line {i+1}: {line[:140]}...")
