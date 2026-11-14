import re

sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"

with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    sql_text = f.read()

# Find CREATE TABLE statements
create_tables = re.findall(r"CREATE TABLE [`']?(\w+)[`']?", sql_text)
print("Tables in SQL dump:", create_tables)

# Find website_pages structure and data
match_wp_create = re.search(r"CREATE TABLE [`']?website_pages[`']?.*?;", sql_text, re.DOTALL)
if match_wp_create:
    print("\n--- website_pages CREATE TABLE ---")
    print(match_wp_create.group(0))

# Find website_pages INSERT statements
wp_inserts = re.findall(r"INSERT INTO [`']?website_pages[`']?.*?;", sql_text, re.DOTALL)
print(f"\n--- website_pages INSERT statements count: {len(wp_inserts)} ---")
for ins in wp_inserts:
    print(ins[:500] + "..." if len(ins) > 500 else ins)

# Search for example.com entries
example_matches = re.findall(r"['\"][^'\"]*?example\.com[^'\"]*?['\"]", sql_text, re.IGNORECASE)
print(f"\nTotal occurrences of example.com: {len(example_matches)}")
print("Sample matches:", set(example_matches[:20]))

# Search for tables containing test emails
print("\nTables with example.com:")
for line in sql_text.splitlines():
    if "example.com" in line.lower() and "insert into" in line.lower():
        tbl_match = re.search(r"INSERT INTO [`']?(\w+)[`']?", line)
        if tbl_match:
            print(" -", tbl_match.group(1))
