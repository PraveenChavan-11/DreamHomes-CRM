<?php
$path = 'd:\\Personal\\TalentHub\\app\\Views\\admin\\layouts\\main.php';
$content = file_get_contents($path);

$target = '<header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm relative z-40">';
$replacement = '<header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm relative z-50">';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    $content_norm = str_replace($target_norm, $replacement_norm, $content_norm);
    
    // Also enhance dropdown wrapper and container
    $target2 = '<div class="relative">
          <button id="admin-notify-bell-btn" type="button" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Recruitment Alerts">';
    $replacement2 = '<div class="relative z-50">
          <button id="admin-notify-bell-btn" type="button" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Recruitment Alerts">';
    
    $target2_norm = str_replace("\r\n", "\n", $target2);
    $replacement2_norm = str_replace("\r\n", "\n", $replacement2);
    $content_norm = str_replace($target2_norm, $replacement2_norm, $content_norm);

    $target3 = '<div id="admin-notify-dropdown" style="z-index: 9999 !important; background-color: #ffffff !important;" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white text-slate-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.25)] border border-slate-200 z-[9999] overflow-hidden">';
    $replacement3 = '<div id="admin-notify-dropdown" style="z-index: 99999 !important; background-color: #ffffff !important;" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white text-slate-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.35)] border border-slate-200 overflow-hidden">';
    
    $target3_norm = str_replace("\r\n", "\n", $target3);
    $replacement3_norm = str_replace("\r\n", "\n", $replacement3);
    $content_norm = str_replace($target3_norm, $replacement3_norm, $content_norm);

    file_put_contents($path, $content_norm);
    echo "SUCCESS: admin/layouts/main.php updated!\n";
} else {
    echo "FAILED: target not found in admin/layouts/main.php!\n";
}

