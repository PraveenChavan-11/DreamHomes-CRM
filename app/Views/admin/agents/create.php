<?php include __DIR__ . '/_ui.php'; use App\Services\Agent\AgentScopes; use App\Services\Agent\AgentTokenService; ?>
<div class="space-y-6 max-w-3xl">
  <a href="<?= base_url('admin/agents') ?>" class="text-xs font-bold text-brand-primary hover:underline">← Back to External AI Agents</a>
  <div>
    <h1 class="text-2xl font-extrabold text-slate-900">Register an AI agent</h1>
    <p class="text-xs text-slate-500 mt-1">Policy is fixed to <strong>Draft only</strong>: the agent can create drafts and ask for review. It cannot publish, schedule, approve, change settings or see platform credentials.</p>
  </div>
  <?= th_flash() ?>
  <form method="post" action="<?= base_url('admin/agents/store') ?>" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="text-xs font-bold text-slate-700">Name
        <input name="name" required maxlength="120" value="<?= esc(old('name')) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
      <label class="text-xs font-bold text-slate-700">Agent type
        <select name="provider" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
          <?php foreach (AgentScopes::PROVIDERS as $k => $label): ?><option value="<?= esc($k) ?>"><?= esc($label) ?></option><?php endforeach; ?>
        </select>
        <span class="block mt-1 text-[11px] font-normal text-slate-500">A label only. It does not connect to that provider.</span></label>
    </div>
    <label class="block text-xs font-bold text-slate-700">Description (optional)
      <input name="description" maxlength="500" value="<?= esc(old('description')) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>

    <fieldset>
      <legend class="text-xs font-bold text-slate-700">Permissions</legend>
      <p class="text-[11px] text-slate-500 mb-2">Defaults are read-only access plus draft creation. There is no permission for publishing, scheduling, credentials or administration.</p>
      <div class="space-y-2">
        <?php foreach (AgentScopes::CATALOGUE as $scope => [$label, $desc]): ?>
          <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="scopes[]" value="<?= esc($scope) ?>" <?= in_array($scope, AgentScopes::DEFAULTS, true) ? 'checked' : '' ?> class="mt-0.5">
            <span><strong><?= esc($label) ?></strong> — <span class="text-slate-500"><?= esc($desc) ?></span></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="grid sm:grid-cols-3 gap-4">
      <label class="text-xs font-bold text-slate-700">Requests per minute<input type="number" name="rate_limit_per_minute" value="30" min="5" max="120" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
      <label class="text-xs font-bold text-slate-700">Drafts per day<input type="number" name="daily_draft_limit" value="25" min="1" max="100" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
      <label class="text-xs font-bold text-slate-700">Token lifetime (days)<input type="number" name="ttl_days" value="<?= AgentTokenService::DEFAULT_TTL_DAYS ?>" min="1" max="<?= AgentTokenService::MAX_TTL_DAYS ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
    </div>
    <label class="block text-xs font-bold text-slate-700">Allowed IP addresses (optional)
      <input name="allowed_ips" placeholder="e.g. 203.0.113.10, 2001:db8::1" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
      <span class="block mt-1 text-[11px] font-normal text-slate-500">Leave empty to allow any address. Hosted AI services often use changing addresses, so only set this if you know the agent's fixed IP.</span></label>

    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
      <label class="block text-xs font-bold text-amber-900">Confirm with your password
        <input type="password" name="confirm_password" required autocomplete="current-password" class="mt-1 block w-full max-w-xs rounded-lg border border-amber-300 px-3 py-2 text-sm font-normal"></label>
      <p class="mt-1 text-[11px] text-amber-800">Creating a credential is a security operation. The token is shown <strong>once</strong> on the next screen and cannot be retrieved again.</p>
    </div>
    <button class="px-5 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold">Register agent and issue credential</button>
  </form>
</div>
