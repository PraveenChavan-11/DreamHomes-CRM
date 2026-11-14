sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"
with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    lines = f.readlines()

def print_context(line_num, count=15):
    start = max(0, line_num - count)
    end = min(len(lines), line_num + count)
    print(f"--- Context around line {line_num} ---")
    for idx in range(start, end):
        if any(keyword in lines[idx] for keyword in ["CREATE TABLE", "INSERT INTO", "TABLE"]):
            print(f"{idx+1}: {lines[idx].strip()[:100]}")
        elif idx == line_num - 1:
            print(f">>> {idx+1}: {lines[idx].strip()[:100]}")

print_context(343, 20)
print_context(1025, 20)
print_context(1115, 20)
