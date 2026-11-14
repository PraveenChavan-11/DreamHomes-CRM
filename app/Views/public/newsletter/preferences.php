<?= $this->extend('public/layouts/main') ?>

<?= $this->section('content') ?>
<div class="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-slate-50">
  <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-brand-primary to-brand-accent"></div>

    <div class="w-16 h-16 rounded-2xl bg-brand-light text-brand-primary flex items-center justify-center mx-auto mb-6">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Newsletter Preferences</h1>
    <p class="text-slate-600 text-sm leading-relaxed mb-6">
      Choose the topics and industrial sectors you want to receive intelligence about:
    </p>

    <form id="preferences-form" class="space-y-3.5 mb-6 text-left">
      <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-brand-accent transition cursor-pointer">
        <input type="checkbox" id="cat-engineering" name="categories[engineering]" value="1" checked class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
        <div>
          <span class="block text-xs font-bold text-slate-800">Engineering & Manufacturing</span>
          <span class="block text-[11px] text-slate-500">Chakan, Bhosari, Talegaon industrial & automotive engineering</span>
        </div>
      </label>

      <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-brand-accent transition cursor-pointer">
        <input type="checkbox" id="cat-it_tech" name="categories[it_tech]" value="1" checked class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
        <div>
          <span class="block text-xs font-bold text-slate-800">IT & Software Engineering</span>
          <span class="block text-[11px] text-slate-500">Hinjawadi & Kharadi tech park hiring, full-stack, cloud, AI</span>
        </div>
      </label>

      <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-brand-accent transition cursor-pointer">
        <input type="checkbox" id="cat-leadership" name="categories[leadership]" value="1" checked class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
        <div>
          <span class="block text-xs font-bold text-slate-800">Leadership & Executive Search</span>
          <span class="block text-[11px] text-slate-500">VP, Director, GM, and CXO level corporate search</span>
        </div>
      </label>

      <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-brand-accent transition cursor-pointer">
        <input type="checkbox" id="cat-general_recruitment" name="categories[general_recruitment]" value="1" checked class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
        <div>
          <span class="block text-xs font-bold text-slate-800">Market Intelligence & Salary Benchmarks</span>
          <span class="block text-[11px] text-slate-500">Quarterly Pune salary benchmarks and career growth insights</span>
        </div>
      </label>

      <button type="submit" id="btn-save-prefs" class="w-full py-3 px-4 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover shadow-md transition-all mt-4">
        Save Preferences
      </button>
    </form>

    <div class="pt-2 border-t border-slate-100 text-xs text-slate-400">
      <a href="<?= base_url('newsletter/unsubscribe?token=' . esc($token ?? '')) ?>" class="hover:text-red-500 transition">Unsubscribe completely</a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const token = <?= json_encode($token ?? '') ?>;
  const form = document.getElementById('preferences-form');
  const btnSave = document.getElementById('btn-save-prefs');

  // Load existing preferences
  if (token) {
    try {
      const res = await fetch(`<?= base_url('api/v1/newsletter/preferences') ?>?token=${encodeURIComponent(token)}`);
      const data = await res.json();
      if (data.success && data.preferences) {
        for (const [cat, enabled] of Object.entries(data.preferences)) {
          const el = document.getElementById(`cat-${cat}`);
          if (el) el.checked = Boolean(enabled);
        }
      }
    } catch (e) {
      console.debug('Failed to load preferences:', e);
    }
  }

  // Handle form submit
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      btnSave.disabled = true;
      btnSave.innerText = 'Saving...';

      const cats = {
        engineering: document.getElementById('cat-engineering').checked ? 1 : 0,
        it_tech: document.getElementById('cat-it_tech').checked ? 1 : 0,
        leadership: document.getElementById('cat-leadership').checked ? 1 : 0,
        general_recruitment: document.getElementById('cat-general_recruitment').checked ? 1 : 0,
      };

      try {
        const res = await fetch('<?= base_url('api/v1/newsletter/preferences') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ token: token, categories: cats }),
        });
        const data = await res.json();
        if (data.success) {
          alert('Preferences updated successfully!');
        } else {
          alert(data.message || 'Error updating preferences.');
        }
      } catch (err) {
        alert('Network error.');
      } finally {
        btnSave.disabled = false;
        btnSave.innerText = 'Save Preferences';
      }
    });
  }
});
</script>
<?= $this->endSection() ?>
