<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Rich-text editor (TinyMCE Cloud) configuration.
 *
 * The Tiny Cloud "API key" is a PUBLIC client-side identifier, not a secret: it is necessarily visible in the page that loads
 * the editor. Its protection is the **Approved Domains** list in the Tiny account dashboard (the key only works on the domains
 * listed there). It is kept out of the source tree so each environment (local, staging, production) uses its own key and domain
 * list, and so it can be rotated without a code change.
 *
 * Set it in `.env` (never committed):   editor.tinymceApiKey = your_key_from_the_tiny_dashboard
 * When it is empty or malformed the editor script is simply not loaded and the fields stay as plain text areas — every form still works.
 */
class Editor extends BaseConfig
{
    /** Tiny Cloud API key (lowercase letters/digits, 30–64 chars). Empty = editor disabled. */
    public string $tinymceApiKey = '';

    /** Pinned major version of the editor script served by Tiny Cloud. */
    public string $tinymceVersion = '7';

    public function isTinyMceEnabled(): bool
    {
        return (bool) preg_match('/^[a-z0-9]{30,64}$/', $this->tinymceApiKey);
    }

    public function tinymceScriptUrl(): string
    {
        return 'https://cdn.tiny.cloud/1/' . $this->tinymceApiKey . '/tinymce/' . preg_replace('/[^0-9a-z.]/i', '', $this->tinymceVersion) . '/tinymce.min.js';
    }
}
