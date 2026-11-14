<?php
$path = 'd:\\Personal\\TalentHub\\app\\Views\\admin\\applications\\index.php';
$content = file_get_contents($path);

// 1. Add master checkbox to <thead>
$target1 = '        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Candidate Profile</th>';

$replacement1 = '        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="w-10 px-4 py-4 text-center">
              <input type="checkbox" id="bulk-select-all" class="rounded border-slate-300 text-brand-primary focus:ring-0 cursor-pointer" title="Select All Applications">
            </th>
            <th class="px-6 py-4">Candidate Profile</th>';

// 2. Colspan update
$target2 = '<td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">';
$replacement2 = '<td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">';

// 3. Row checkbox
$target3 = '              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($candidateName) ?></div>';

$replacement3 = '              <tr class="hover:bg-slate-50/60 transition">
                <td class="w-10 px-4 py-4 text-center">
                  <input type="checkbox" value="<?= $app[\'id\'] ?>" class="bulk-app-checkbox rounded border-slate-300 text-brand-primary focus:ring-0 cursor-pointer">
                </td>
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($candidateName) ?></div>';

// 4. Add floating action bar and bulk script at the end
$target4 = '</div>

<!-- Status Update Modal -->';

$replacement4 = '  <!-- FLOATING BULK ACTIONS BAR (GAP-02) -->
  <div id="bulk-action-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900/95 text-white backdrop-blur-md px-6 py-4 rounded-2xl shadow-2xl border border-white/10 hidden items-center gap-4 transition-all duration-300 max-w-4xl w-[calc(100%-2rem)] box-sizing-border">
    <div class="flex items-center gap-2 shrink-0">
      <span class="w-2.5 h-2.5 rounded-full bg-[#37CE97] animate-pulse"></span>
      <span id="bulk-selected-count" class="font-extrabold text-xs text-white">0 selected</span>
    </div>

    <form id="bulk-action-form" action="<?= base_url(\'admin/applications/bulk-action\') ?>" method="POST" class="flex flex-wrap items-center gap-3 flex-1 justify-end">
      <?= csrf_field() ?>
      <input type="hidden" name="bulk_action" id="bulk-action-type" value="transition">

      <select name="target_status" id="bulk-target-status" class="py-1.5 px-3 rounded-xl bg-slate-800 text-white text-xs border border-white/20 focus:outline-none focus:border-[#37CE97]">
        <option value="">Move to Pipeline Stage...</option>
        <option value="SHORTLISTED">Shortlist Candidates (Auto-Mail)</option>
        <option value="SCREENING">Move to Screening</option>
        <option value="INTERVIEW">Schedule Interview Round</option>
        <option value="OFFER">Extend Corporate Offer</option>
        <option value="HIRED">Mark Candidate Hired</option>
        <option value="REJECTED">Reject Candidates (Auto-Mail)</option>
        <option value="WITHDRAWN">Mark Withdrawn</option>
      </select>

      <button type="submit" onclick="document.getElementById(\'bulk-action-type\').value=\'transition\'" class="px-3.5 py-1.5 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white font-bold text-xs shadow-md transition cursor-pointer">
        Apply Stage Transition
      </button>

      <button type="submit" onclick="document.getElementById(\'bulk-action-type\').value=\'export\'" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition cursor-pointer flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export Selected</span>
      </button>

      <button type="button" id="bulk-clear-btn" class="text-xs text-slate-400 hover:text-white transition underline cursor-pointer ml-1">
        Clear
      </button>
    </form>
  </div>

</div>

<!-- Status Update Modal -->';

$content_norm = str_replace("\r\n", "\n", $content);
$target1_norm = str_replace("\r\n", "\n", $target1);
$replacement1_norm = str_replace("\r\n", "\n", $replacement1);
$target2_norm = str_replace("\r\n", "\n", $target2);
$replacement2_norm = str_replace("\r\n", "\n", $replacement2);
$target3_norm = str_replace("\r\n", "\n", $target3);
$replacement3_norm = str_replace("\r\n", "\n", $replacement3);
$target4_norm = str_replace("\r\n", "\n", $target4);
$replacement4_norm = str_replace("\r\n", "\n", $replacement4);

if (strpos($content_norm, $target1_norm) !== false && strpos($content_norm, $target3_norm) !== false) {
    $content_norm = str_replace($target1_norm, $replacement1_norm, $content_norm);
    $content_norm = str_replace($target2_norm, $replacement2_norm, $content_norm);
    $content_norm = str_replace($target3_norm, $replacement3_norm, $content_norm);
    $content_norm = str_replace($target4_norm, $replacement4_norm, $content_norm);

    // Append JavaScript for bulk actions at end of script
    $js_script = '
  // Bulk Selection Engine
  const bulkSelectAll = document.getElementById(\'bulk-select-all\');
  const bulkCheckboxes = document.querySelectorAll(\'.bulk-app-checkbox\');
  const bulkBar = document.getElementById(\'bulk-action-bar\');
  const bulkCountSpan = document.getElementById(\'bulk-selected-count\');
  const bulkForm = document.getElementById(\'bulk-action-form\');
  const bulkClearBtn = document.getElementById(\'bulk-clear-btn\');

  function updateBulkState() {
    const checked = Array.from(bulkCheckboxes).filter(c => c.checked);
    if (checked.length > 0) {
      bulkCountSpan.textContent = checked.length + \' application(s) selected\';
      bulkBar.classList.remove(\'hidden\');
      bulkBar.classList.add(\'flex\');
    } else {
      bulkBar.classList.add(\'hidden\');
      bulkBar.classList.remove(\'flex\');
      if (bulkSelectAll) bulkSelectAll.checked = false;
    }
  }

  bulkSelectAll?.addEventListener(\'change\', () => {
    bulkCheckboxes.forEach(c => c.checked = bulkSelectAll.checked);
    updateBulkState();
  });

  bulkCheckboxes.forEach(c => c.addEventListener(\'change\', updateBulkState));

  bulkClearBtn?.addEventListener(\'click\', () => {
    bulkCheckboxes.forEach(c => c.checked = false);
    if (bulkSelectAll) bulkSelectAll.checked = false;
    updateBulkState();
  });

  bulkForm?.addEventListener(\'submit\', (e) => {
    bulkForm.querySelectorAll(\'input[name="selected_ids[]"]\').forEach(el => el.remove());
    const checked = Array.from(bulkCheckboxes).filter(c => c.checked);
    if (checked.length === 0) {
      e.preventDefault();
      alert(\'Please select at least one application.\');
      return;
    }
    const actionType = document.getElementById(\'bulk-action-type\').value;
    if (actionType === \'transition\') {
      const targetStatus = document.getElementById(\'bulk-target-status\').value;
      if (!targetStatus) {
        e.preventDefault();
        alert(\'Please select a target pipeline stage.\');
        return;
      }
    }
    checked.forEach(c => {
      const input = document.createElement(\'input\');
      input.type = \'hidden\';
      input.name = \'selected_ids[]\';
      input.value = c.value;
      bulkForm.appendChild(input);
    });
  });
</script>';

    $content_norm = str_replace('</script>', $js_script, $content_norm);

    file_put_contents($path, $content_norm);
    echo "SUCCESS: admin/applications/index.php updated with bulk selection!\n";
} else {
    echo "FAILED: targets not found in admin/applications/index.php!\n";
}

