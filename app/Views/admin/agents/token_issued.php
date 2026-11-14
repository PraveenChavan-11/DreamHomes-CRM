<?php include __DIR__ . '/_ui.php'; ?>
<div class="space-y-5 max-w-3xl">
  <h1 class="text-2xl font-extrabold text-slate-900">Credential <?= esc($what) ?> for “<?= esc($agentName) ?>”</h1>
  <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-5 space-y-3" role="alert">
    <p class="text-sm font-bold text-amber-900">Copy this token now. It is shown only once and cannot be retrieved later.</p>
    <div class="flex flex-col sm:flex-row gap-2">
      <input id="agent-token" readonly value="<?= esc($token) ?>" class="flex-1 rounded-lg border border-amber-300 bg-white px-3 py-2 font-mono text-xs" onclick="this.select()" aria-label="Agent API token">
      <button type="button" class="px-4 py-2 rounded-lg bg-amber-600 text-white text-xs font-bold" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('agent-token').value).then(function(){this.textContent='Copied'}.bind(this))">Copy</button>
    </div>
    <p class="text-xs text-amber-900">Expires <strong><?= esc($expires) ?></strong>. Treat it like a password: store it only in the AI provider's secret/credential store, never in a prompt, chat, document or repository. If it leaks, revoke it from the agent page — that takes effect immediately.</p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-5 text-xs text-slate-700 space-y-2">
    <p><strong>How the agent uses it:</strong> send <code>Authorization: Bearer &lt;token&gt;</code> over HTTPS to <code><?= esc(base_url('api/v1/integrations/agents/')) ?></code>.</p>
    <p>This agent can create <strong>drafts only</strong>. A person must approve every draft before it can be scheduled or published. See <code>docs/transformation/EXTERNAL_AGENT_API.md</code> for the endpoints.</p>
  </div>
  <a href="<?= base_url('admin/agents/' . (int) $agentId) ?>" class="inline-block px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">I have stored the token — continue</a>
</div>
