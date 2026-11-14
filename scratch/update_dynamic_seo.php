<?php
// 1. Update BaseController.php
$baseCtrlPath = 'app/Controllers/BaseController.php';
$baseContent = file_get_contents($baseCtrlPath);

$targetBase = "        // Default SEO meta fallback
        if (!isset(\$data['meta_title'])) {
            \$data['meta_title'] = \$this->siteSettings['site_name'] ?? 'TalentHub Consultant';
            if (!empty(\$this->siteSettings['site_tagline'])) {
                \$data['meta_title'] .= ' — ' . \$this->siteSettings['site_tagline'];
            }
        }
        if (!isset(\$data['meta_description'])) {
            \$data['meta_description'] = \$this->siteSettings['site_description'] ?? 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises.';
        }";

$replaceBase = "        \$rawPath = trim(\$this->request->getUri()->getPath(), '/');
        \$slug = empty(\$rawPath) ? 'home' : \$rawPath;

        // Dynamic Page SEO lookup from website_pages CMS
        try {
            \$pageModel = new \App\Models\WebsitePageModel();
            \$pageRecord = \$pageModel->where('slug', \$slug)->first();
            if (!\$pageRecord && str_contains(\$slug, '/')) {
                \$primarySegment = explode('/', \$slug)[0];
                \$pageRecord = \$pageModel->where('slug', \$primarySegment)->first();
            }

            if (\$pageRecord) {
                if (empty(\$data['meta_title']) && !empty(\$pageRecord['meta_title'])) {
                    \$data['meta_title'] = \$pageRecord['meta_title'];
                }
                if (empty(\$data['meta_description']) && !empty(\$pageRecord['meta_description'])) {
                    \$data['meta_description'] = \$pageRecord['meta_description'];
                }
                if (empty(\$data['meta_keywords']) && !empty(\$pageRecord['meta_keywords'])) {
                    \$data['meta_keywords'] = \$pageRecord['meta_keywords'];
                }
                if (empty(\$data['og_image']) && !empty(\$pageRecord['og_image'])) {
                    \$data['og_image'] = base_url(\$pageRecord['og_image']);
                }
            }
        } catch (\\Throwable \$e) {}

        // Default SEO meta fallback
        if (!isset(\$data['meta_title'])) {
            \$data['meta_title'] = \$this->siteSettings['site_name'] ?? 'TalentHub Consultant';
            if (!empty(\$this->siteSettings['site_tagline'])) {
                \$data['meta_title'] .= ' — ' . \$this->siteSettings['site_tagline'];
            }
        }
        if (!isset(\$data['meta_description'])) {
            \$data['meta_description'] = \$this->siteSettings['site_description'] ?? 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises.';
        }
        if (!isset(\$data['meta_keywords'])) {
            \$data['meta_keywords'] = 'recruitment agency Pune, placement consultancy Pune, IT jobs Hinjawadi, staffing agency Maharashtra, engineering jobs Chakan, executive search Pune';
        }";

$baseContent_norm = str_replace("\r\n", "\n", $baseContent);
$targetBase_norm = str_replace("\r\n", "\n", $targetBase);
$replaceBase_norm = str_replace("\r\n", "\n", $replaceBase);

if (strpos($baseContent_norm, $targetBase_norm) !== false) {
    file_put_contents($baseCtrlPath, str_replace($targetBase_norm, $replaceBase_norm, $baseContent_norm));
    echo "BaseController updated with dynamic page SEO lookup.\n";
} else {
    echo "FAILED: targetBase not found in BaseController\n";
}

// 2. Update main.php
$mainPath = 'app/Views/public/layouts/main.php';
$mainContent = file_get_contents($mainPath);

$targetMain = '  <meta name="keywords" content="recruitment agency Pune, placement consultancy Pune, IT jobs Hinjawadi, staffing agency Maharashtra, engineering jobs Chakan, executive search Pune">';
$replaceMain = '  <meta name="keywords" content="<?= esc($meta_keywords ?? \'recruitment agency Pune, placement consultancy Pune, IT jobs Hinjawadi, staffing agency Maharashtra, engineering jobs Chakan, executive search Pune\') ?>">';

$mainContent_norm = str_replace("\r\n", "\n", $mainContent);
$targetMain_norm = str_replace("\r\n", "\n", $targetMain);
$replaceMain_norm = str_replace("\r\n", "\n", $replaceMain);

if (strpos($mainContent_norm, $targetMain_norm) !== false) {
    $mainContent_norm = str_replace($targetMain_norm, $replaceMain_norm, $mainContent_norm);
    $mainContent_norm = str_replace('<meta property="og:type" content="website">', '<meta property="og:type" content="<?= esc($og_type ?? \'website\') ?>">', $mainContent_norm);
    file_put_contents($mainPath, $mainContent_norm);
    echo "main.php updated with dynamic SEO tags.\n";
} else {
    echo "FAILED: targetMain not found in main.php\n";
}

