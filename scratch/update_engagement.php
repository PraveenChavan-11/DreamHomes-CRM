<?php
$path = 'd:\\Personal\\TalentHub\\public\\assets\\js\\engagement.js';
$content = file_get_contents($path);

$old_block = '  window.openJobAlertModal = function (prefillCategory) {
    let modal = document.getElementById(\'th-job-alert-modal\');
    if (!modal) {
      modal = document.createElement(\'div\');
      modal.id = \'th-job-alert-modal\';
      modal.className = \'fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9995] flex items-center justify-center p-4 transition-opacity\';
      modal.innerHTML = `
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100 relative">
          <button id="th-alert-modal-close" class="absolute top-4 right-4 p-2 rounded-full text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div class="w-12 h-12 rounded-2xl bg-brand-light text-brand-primary flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <h3 class="text-xl font-black text-slate-900 mb-1">Get Pune Job Alerts</h3>
          <p class="text-xs text-slate-500 mb-4">Receive instant or weekly notifications when matching IT, engineering, or leadership mandates open in Pune.</p>

          <form id="th-job-alert-form" class="space-y-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Your Email Address <span class="text-red-500">*</span></label>
              <input type="email" id="th-alert-email" required placeholder="name@example.com" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-primary focus:ring-brand-primary">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Target Sector / Role</label>
              <input type="text" id="th-alert-category" placeholder="e.g. Java, Project Engineer, HR, Finance" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-primary focus:ring-brand-primary">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Preferred Location</label>
                <select id="th-alert-location" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-primary focus:ring-brand-primary">
                  <option value="Pune">All Pune Corridors</option>
                  <option value="Hinjawadi">Hinjawadi IT Park</option>
                  <option value="Chakan">Chakan Industrial</option>
                  <option value="Kharadi">Kharadi / Viman Nagar</option>
                  <option value="Katraj">Katraj / Swargate</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Frequency</label>
                <select id="th-alert-freq" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-primary focus:ring-brand-primary">
                  <option value="weekly">Weekly Summary</option>
                  <option value="daily">Daily Digest</option>
                  <option value="immediate">Immediate</option>
                </select>
              </div>
            </div>
            <button type="submit" id="th-alert-submit" class="w-full py-3 px-4 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold transition shadow-md mt-2">
              Activate Job Alerts
            </button>
          </form>
        </div>
      `;
      document.body.appendChild(modal);

      document.getElementById(\'th-alert-modal-close\')?.addEventListener(\'click\', () => modal.remove());
      modal.addEventListener(\'click\', (e) => {
        if (e.target === modal) modal.remove();
      });

      document.getElementById(\'th-job-alert-form\')?.addEventListener(\'submit\', async (e) => {
        e.preventDefault();
        const email = document.getElementById(\'th-alert-email\').value;
        const category = document.getElementById(\'th-alert-category\').value;
        const location = document.getElementById(\'th-alert-location\').value;
        const freq = document.getElementById(\'th-alert-freq\').value;
        const btn = document.getElementById(\'th-alert-submit\');

        btn.disabled = true;
        btn.innerText = \'Activating...\';';

$new_block = '  window.openJobAlertModal = function (prefillCategory) {
    let modal = document.getElementById(\'th-job-alert-modal\');
    if (!modal) {
      modal = document.createElement(\'div\');
      modal.id = \'th-job-alert-modal\';
      modal.style.cssText = \'position: fixed !important; inset: 0 !important; width: 100vw !important; height: 100vh !important; background-color: rgba(0,0,0,0.75) !important; z-index: 99999999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 16px !important; box-sizing: border-box !important;\';
      modal.innerHTML = `
        <div style="background-color: #FFFFFF !important; color: #0F172A !important; z-index: 100000000 !important; position: relative !important; max-width: 460px !important; width: 100% !important; border-radius: 24px !important; padding: 28px !important; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.85) !important; border: 1px solid #E2E8F0 !important; box-sizing: border-box !important;">
          <button id="th-alert-modal-close" style="position: absolute !important; top: 16px !important; right: 16px !important; padding: 8px !important; border-radius: 9999px !important; background: #F1F5F9 !important; border: none !important; color: #64748B !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important;">
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div style="width: 44px !important; height: 44px !important; border-radius: 14px !important; background-color: #EAF5EF !important; color: #1D6A34 !important; display: flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 16px !important;">
            <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <h3 style="font-size: 20px !important; font-weight: 900 !important; color: #0F172A !important; margin: 0 0 6px 0 !important; letter-spacing: -0.02em !important;">Get Pune Job Alerts</h3>
          <p style="font-size: 12px !important; color: #64748B !important; margin: 0 0 18px 0 !important; line-height: 1.5 !important;">Receive instant or weekly notifications when matching IT, engineering, or leadership mandates open in Pune.</p>

          <form id="th-job-alert-form" style="display: flex !important; flex-direction: column !important; gap: 12px !important;">
            <div>
              <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Your Email Address <span style="color: #EF4444 !important;">*</span></label>
              <input type="email" id="th-alert-email" required placeholder="name@example.com" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 12px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
            </div>
            <div>
              <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Target Sector / Role</label>
              <input type="text" id="th-alert-category" placeholder="e.g. Java, Project Engineer, HR, Finance" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 12px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
            </div>
            <div style="display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 10px !important;">
              <div>
                <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Preferred Location</label>
                <select id="th-alert-location" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 8px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
                  <option value="Pune">All Pune Corridors</option>
                  <option value="Hinjawadi">Hinjawadi IT Park</option>
                  <option value="Chakan">Chakan Industrial</option>
                  <option value="Kharadi">Kharadi / Viman Nagar</option>
                  <option value="Katraj">Katraj / Swargate</option>
                </select>
              </div>
              <div>
                <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Frequency</label>
                <select id="th-alert-freq" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 8px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
                  <option value="weekly">Weekly Summary</option>
                  <option value="daily">Daily Digest</option>
                  <option value="immediate">Immediate</option>
                </select>
              </div>
            </div>
            <button type="submit" id="th-alert-submit"
                    style="width: 100% !important; display: block !important; padding: 13px 20px !important; border-radius: 14px !important; background-color: #1D6A34 !important; color: #FFFFFF !important; font-size: 13px !important; font-weight: 800 !important; border: none !important; cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 14px rgba(29, 106, 52, 0.35) !important; margin-top: 14px !important; letter-spacing: 0.02em !important;">
              Activate Job Alerts &rarr;
            </button>
          </form>
        </div>
      `;
      document.body.appendChild(modal);

      document.getElementById(\'th-alert-modal-close\')?.addEventListener(\'click\', () => modal.remove());
      modal.addEventListener(\'click\', (e) => {
        if (e.target === modal) modal.remove();
      });

      document.getElementById(\'th-job-alert-form\')?.addEventListener(\'submit\', async (e) => {
        e.preventDefault();
        const email = document.getElementById(\'th-alert-email\').value;
        const category = document.getElementById(\'th-alert-category\').value;
        const location = document.getElementById(\'th-alert-location\').value;
        const freq = document.getElementById(\'th-alert-freq\').value;
        const btn = document.getElementById(\'th-alert-submit\');

        btn.disabled = true;
        btn.innerText = \'Activating...\';
        btn.style.backgroundColor = \'#155227\';
        btn.style.color = \'#FFFFFF\';';

$content_norm = str_replace("\r\n", "\n", $content);
$old_norm = str_replace("\r\n", "\n", $old_block);
$new_norm = str_replace("\r\n", "\n", $new_block);

if (strpos($content_norm, $old_norm) !== false) {
    $updated = str_replace($old_norm, $new_norm, $content_norm);
    file_put_contents($path, $updated);
    echo "SUCCESS: engagement.js updated!\n";
} else {
    echo "FAILED: old_block not found in engagement.js!\n";
}

