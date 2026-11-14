with open('tests/unit/EnquiryAutomationTest.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Replace hardcoded document_id => 1 with dynamic lookup
# Line 134: 'document_id'     => 1,
text = text.replace(
    "'document_id'     => 1,",
    "'document_id'     => (int) ($db->table('candidate_documents')->select('id')->get()->getRowArray()['id'] ?? 13),"
)

# Line 345: 'document_id'  => 1,
text = text.replace(
    "'document_id'  => 1,",
    "'document_id'  => (int) ($db->table('candidate_documents')->select('id')->get()->getRowArray()['id'] ?? 13),"
)

with open('tests/unit/EnquiryAutomationTest.php', 'w', encoding='utf-8') as f:
    f.write(text)

print("Updated EnquiryAutomationTest.php successfully")
