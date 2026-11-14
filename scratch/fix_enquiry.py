with open('app/Services/EnquiryAutomationService.php', 'r', encoding='utf-8') as f:
    text = f.read()

target = """            $renderedSubject = strtr($template['subject'] ?? $subject, $replacements);
            $renderedHtml    = strtr($template['body_html'] ?? $content, $replacements);
            $renderedText    = strtr($template['body_text'] ?? $content, $replacements);"""

repl = """            $defaultBodyHtml = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;"><h2 style="color:#072412;">{{announcement_headline}}</h2><p style="color:#334155;line-height:1.6;">{{announcement_content}}</p>' . $ctaHtml . '</div>';
            $renderedSubject = strtr($template['subject'] ?? $subject, $replacements);
            $renderedHtml    = strtr($template['body_html'] ?? $defaultBodyHtml, $replacements);
            $renderedText    = strtr($template['body_text'] ?? ($headline . "\\n\\n" . $content), $replacements);"""

text = text.replace("\r\n", "\n")
target_norm = target.replace("\r\n", "\n")
repl_norm = repl.replace("\r\n", "\n")

if target_norm in text:
    text = text.replace(target_norm, repl_norm)
    with open('app/Services/EnquiryAutomationService.php', 'w', encoding='utf-8') as f:
        f.write(text)
    print("SUCCESS")
else:
    print("TARGET NOT FOUND")
