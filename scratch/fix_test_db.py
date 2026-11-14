with open('tests/unit/EnquiryAutomationTest.php', 'r', encoding='utf-8') as f:
    text = f.read()

text = text.replace(
    "(int) ($db->table('candidate_documents')",
    "(int) (\\Config\\Database::connect()->table('candidate_documents')"
)

with open('tests/unit/EnquiryAutomationTest.php', 'w', encoding='utf-8') as f:
    f.write(text)

print("Fixed $db in EnquiryAutomationTest.php")
