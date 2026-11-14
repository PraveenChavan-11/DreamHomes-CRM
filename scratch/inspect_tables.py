import re

sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"
with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    sql_text = f.read()

tables_to_check = [
    'applications',
    'candidates',
    'contact_enquiries',
    'employer_enquiries',
    'general_resume_submissions',
    'newsletter_subscribers',
    'job_alert_subscriptions',
    'admin_users',
    'website_pages',
    'website_settings'
]

for tbl in tables_to_check:
    print(f"\n================ TABLE: {tbl} ================")
    # find INSERT INTO `tbl`
    pattern = rf"INSERT INTO [`']?{tbl}[`']?\s*(\(.*?\))?\s*VALUES\s*(.*?);"
    m = re.search(pattern, sql_text, re.DOTALL)
    if m:
        cols = m.group(1) or ""
        vals = m.group(2)
        # count rows roughly
        tuples = re.findall(r"\((.*?)\)(?:,\n|;)", vals, re.DOTALL)
        print(f"Columns: {cols[:100]}")
        print(f"Total Rows: {len(tuples)}")
        for i, t in enumerate(tuples):
            print(f"  [{i+1}] {t[:160]}...")
    else:
        print("  NO INSERT STATEMENT FOUND (Table is empty)")
