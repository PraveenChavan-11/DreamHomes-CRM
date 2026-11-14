<?php
$path = 'd:\\Personal\\TalentHub\\public\\assets\\css\\custom.css';
$content = file_get_contents($path);

$target = '.bg-brand-accent {
  background-color: #37CE97 !important;
  color: #072412 !important;
}';

$replacement = '.bg-brand-accent {
  background-color: #37CE97 !important;
  color: #072412 !important;
}

/* Fail-safe high-contrast dark card inputs */
.input-dark-contrast {
  background-color: rgba(255, 255, 255, 0.12) !important;
  border: 1px solid rgba(255, 255, 255, 0.25) !important;
  color: #FFFFFF !important;
}
.input-dark-contrast::placeholder {
  color: rgba(255, 255, 255, 0.65) !important;
}
.input-dark-contrast:focus {
  background-color: rgba(255, 255, 255, 0.18) !important;
  border-color: #37CE97 !important;
  outline: none !important;
}';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    $updated = str_replace($target_norm, $replacement_norm, $content_norm);
    file_put_contents($path, $updated);
    echo "SUCCESS: custom.css updated!\n";
} else {
    echo "FAILED: target not found!\n";
}

