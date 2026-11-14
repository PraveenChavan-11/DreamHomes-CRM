import re

sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"
with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    sql_text = f.read()

# Extract website_pages rows
match = re.search(r"INSERT INTO [`']?website_pages[`']? \((.*?)\) VALUES\s*(.*?);", sql_text, re.DOTALL)
if match:
    cols = [c.strip(" `\r\n") for c in match.group(1).split(",")]
    print("Columns:", cols)
    values_str = match.group(2)
    # Print the lines / tuples
    # Let's split by ),\n(
    tuples = re.findall(r"\((.*?)\)(?:,\n|;)", values_str, re.DOTALL)
    print(f"Total rows in website_pages: {len(tuples)}")
    for i, t in enumerate(tuples):
        # find slug
        slug_m = re.search(r"^\d+,\s*'([^']+)'", t.strip())
        slug = slug_m.group(1) if slug_m else f"row {i}"
        print(f"Row {i+1}: slug = {slug}")
        # print first 200 chars
        print("  Preview:", t[:180])
