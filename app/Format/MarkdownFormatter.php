<?php

namespace App\Format;

use CodeIgniter\Format\FormatterInterface;

/**
 * TalentHub Consultant — Autonomous Agent Markdown Formatter (SEP-1649)
 * Formats API, Agent, or Content-Negotiated responses directly to Markdown
 */
class MarkdownFormatter implements FormatterInterface
{
    /**
     * Format given data as Markdown
     *
     * @param mixed $data
     * @return string
     */
    public function format($data)
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            $out = '';
            if (!empty($data['title'])) {
                $out .= "# " . $data['title'] . "\n\n";
            }
            if (!empty($data['message'])) {
                $out .= "> " . $data['message'] . "\n\n";
            }
            foreach ($data as $k => $v) {
                if (in_array($k, ['title', 'message'], true)) {
                    continue;
                }
                if (is_scalar($v)) {
                    $out .= "- **" . ucfirst(str_replace('_', ' ', (string) $k)) . "**: " . $v . "\n";
                } elseif (is_array($v)) {
                    $out .= "- **" . ucfirst(str_replace('_', ' ', (string) $k)) . "**:\n";
                    foreach ($v as $subKey => $subVal) {
                        if (is_scalar($subVal)) {
                            $out .= "  - " . (is_string($subKey) ? "**" . ucfirst($subKey) . "**: " : "") . $subVal . "\n";
                        }
                    }
                }
            }
            return !empty($out) ? trim($out) . "\n" : (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
        }

        return (string) $data;
    }
}
