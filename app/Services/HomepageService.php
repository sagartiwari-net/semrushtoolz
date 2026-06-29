<?php

namespace App\Services;

use App\Models\SiteSetting;

class HomepageService
{
    public const SETTING_KEY = 'homepage_content';

    public function config(): array
    {
        $defaults = config('homepage', []);
        $stored = json_decode((string) SiteSetting::get(self::SETTING_KEY, ''), true);

        if (! is_array($stored) || $stored === []) {
            return $this->normalizeConfig($defaults);
        }

        return $this->normalizeConfig(array_replace_recursive($defaults, $stored));
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function normalizeConfig(array $config): array
    {
        $defaults = config('homepage', []);

        $config['stats'] = $this->ensureList($config['stats'] ?? null);
        $config['plan_notes'] = $this->ensureList($config['plan_notes'] ?? null);
        $config['custom_sections'] = collect($this->ensureList($config['custom_sections'] ?? null))
            ->filter(fn ($section) => is_array($section))
            ->values()
            ->all();

        $visibilityDefaults = is_array($defaults['section_visibility'] ?? null)
            ? $defaults['section_visibility']
            : [];
        $visibilityStored = is_array($config['section_visibility'] ?? null)
            ? $config['section_visibility']
            : [];
        $config['section_visibility'] = array_merge($visibilityDefaults, $visibilityStored);

        foreach (['how_it_works', 'features', 'faq'] as $sectionKey) {
            if (! is_array($config[$sectionKey] ?? null)) {
                $config[$sectionKey] = is_array($defaults[$sectionKey] ?? null)
                    ? $defaults[$sectionKey]
                    : [];
            }
        }

        $config['how_it_works']['steps'] = $this->ensureList($config['how_it_works']['steps'] ?? null);
        $config['features']['items'] = $this->ensureList($config['features']['items'] ?? null);
        $config['faq']['items'] = $this->ensureList($config['faq']['items'] ?? null);

        $tags = $config['combo_block']['tags'] ?? '';
        if (! is_string($tags) && ! is_array($tags)) {
            $config['combo_block']['tags'] = is_array($defaults['combo_block']['tags'] ?? null)
                ? $defaults['combo_block']['tags']
                : [];
        }

        return $config;
    }

    /**
     * @return array<int, mixed>
     */
    protected function ensureList(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    public function save(array $content): void
    {
        SiteSetting::set(self::SETTING_KEY, json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function reset(): void
    {
        SiteSetting::set(self::SETTING_KEY, '');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function buildFromRequest(array $input): array
    {
        $config = $this->config();

        $config['seo'] = [
            'title' => trim((string) ($input['seo_title'] ?? '')),
            'description' => trim((string) ($input['seo_description'] ?? '')),
            'keywords' => trim((string) ($input['seo_keywords'] ?? '')),
            'og_image' => trim((string) ($input['seo_og_image'] ?? '')),
            'organization_description' => trim((string) ($input['seo_organization_description'] ?? '')),
        ];

        $config['hero'] = [
            'badge' => trim((string) ($input['hero_badge'] ?? '')),
            'heading_line1' => trim((string) ($input['hero_heading_line1'] ?? '')),
            'heading_line2' => trim((string) ($input['hero_heading_line2'] ?? '')),
            'subtext_html' => (string) ($input['hero_subtext_html'] ?? ''),
            'cta_primary_label' => trim((string) ($input['hero_cta_primary_label'] ?? '')),
            'cta_primary_url' => trim((string) ($input['hero_cta_primary_url'] ?? '')),
            'cta_secondary_label' => trim((string) ($input['hero_cta_secondary_label'] ?? '')),
            'cta_secondary_url' => trim((string) ($input['hero_cta_secondary_url'] ?? '')),
        ];

        $config['stats'] = $this->normalizePairs(
            $input['stat_value'] ?? [],
            $input['stat_label'] ?? [],
            fn (string $value, string $label) => ['value' => $value, 'label' => $label],
        );

        $config['plans'] = [
            'heading' => trim((string) ($input['plans_heading'] ?? '')),
            'subheading' => trim((string) ($input['plans_subheading'] ?? '')),
            'banner_image' => trim((string) ($input['plans_banner_image'] ?? '')),
            'banner_alt' => trim((string) ($input['plans_banner_alt'] ?? '')),
            'billing_note' => trim((string) ($input['plans_billing_note'] ?? '')),
        ];

        $config['plan_notes'] = $this->normalizePairs(
            $input['plan_note_marker'] ?? [],
            $input['plan_note_text'] ?? [],
            fn (string $marker, string $text) => ['marker' => $marker, 'text' => $text],
        );

        $config['ahrefs_plans'] = [
            'badge' => trim((string) ($input['ahrefs_plans_badge'] ?? '')),
            'heading' => trim((string) ($input['ahrefs_plans_heading'] ?? '')),
            'subheading' => trim((string) ($input['ahrefs_plans_subheading'] ?? '')),
            'footnote' => trim((string) ($input['ahrefs_plans_footnote'] ?? '')),
        ];

        foreach (['semrush_block', 'ahrefs_block'] as $block) {
            $prefix = str_replace('_block', '', $block);
            $config[$block] = [
                'logo_url' => trim((string) ($input["{$prefix}_logo_url"] ?? '')),
                'logo_alt' => trim((string) ($input["{$prefix}_logo_alt"] ?? '')),
                'heading' => trim((string) ($input["{$prefix}_heading"] ?? '')),
                'intro_html' => (string) ($input["{$prefix}_intro_html"] ?? ''),
                'bullets' => $this->linesToList($input["{$prefix}_bullets"] ?? ''),
                'cta_label' => trim((string) ($input["{$prefix}_cta_label"] ?? '')),
                'cta_url' => trim((string) ($input["{$prefix}_cta_url"] ?? '')),
                'sidebar_heading' => trim((string) ($input["{$prefix}_sidebar_heading"] ?? '')),
                'sidebar_html' => (string) ($input["{$prefix}_sidebar_html"] ?? ''),
            ];
        }

        $config['combo_block'] = [
            'heading' => trim((string) ($input['combo_heading'] ?? '')),
            'body_html' => (string) ($input['combo_body_html'] ?? ''),
            'tags' => implode(', ', $this->linesToList($input['combo_tags'] ?? '')),
        ];

        $config['how_it_works'] = [
            'heading' => trim((string) ($input['how_heading'] ?? '')),
            'subheading' => trim((string) ($input['how_subheading'] ?? '')),
            'steps' => $this->normalizeSteps($input),
        ];

        $config['features'] = [
            'heading' => trim((string) ($input['features_heading'] ?? '')),
            'subheading' => trim((string) ($input['features_subheading'] ?? '')),
            'items' => $this->normalizePairs(
                $input['feature_title'] ?? [],
                $input['feature_desc'] ?? [],
                fn (string $title, string $desc) => ['title' => $title, 'desc' => $desc],
            ),
        ];

        $config['faq'] = [
            'heading' => trim((string) ($input['faq_heading'] ?? '')),
            'subheading' => trim((string) ($input['faq_subheading'] ?? '')),
            'items' => $this->normalizePairs(
                $input['faq_q'] ?? [],
                $input['faq_a'] ?? [],
                fn (string $q, string $a) => ['q' => $q, 'a' => $a],
            ),
        ];

        $config['cta'] = [
            'heading' => trim((string) ($input['cta_heading'] ?? '')),
            'subtext_html' => (string) ($input['cta_subtext_html'] ?? ''),
            'primary_label' => trim((string) ($input['cta_primary_label'] ?? '')),
            'primary_url' => trim((string) ($input['cta_primary_url'] ?? '')),
            'secondary_label' => trim((string) ($input['cta_secondary_label'] ?? '')),
            'secondary_url' => trim((string) ($input['cta_secondary_url'] ?? '')),
        ];

        $visibility = [];
        foreach (array_keys(config('homepage.section_visibility', [])) as $key) {
            $visibility[$key] = (string) ($input['section_'.$key] ?? '0') === '1';
        }
        $config['section_visibility'] = $visibility;

        $config['custom_sections'] = $this->normalizeCustomSections($input);

        return $config;
    }

    public function isSectionVisible(array $config, string $key): bool
    {
        return (bool) ($config['section_visibility'][$key] ?? true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sortedCustomSections(array $config): array
    {
        return collect($config['custom_sections'] ?? [])
            ->filter(fn ($section) => (bool) ($section['enabled'] ?? true) && (filled($section['heading'] ?? null) || filled($section['body_html'] ?? null)))
            ->sortBy(fn ($section) => (int) ($section['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    public function resolveCtaHtml(string $html): string
    {
        return str_replace(
            ['{login_url}', '{register_url}'],
            [route('login'), route('register')],
            $html,
        );
    }

    public function resolveFeatureDesc(string $desc, int $affiliateRate): string
    {
        return str_replace('{affiliate_rate}', (string) $affiliateRate, $desc);
    }

    public function comboTags(array $comboBlock): array
    {
        $tags = $comboBlock['tags'] ?? '';

        if (is_array($tags)) {
            return array_values(array_filter($tags));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $tags))));
    }

    /**
     * @param  array<int, string>  $left
     * @param  array<int, string>  $right
     * @return array<int, array<string, string>>
     */
    protected function normalizePairs(array $left, array $right, callable $mapper): array
    {
        $items = [];
        $count = max(count($left), count($right));

        for ($i = 0; $i < $count; $i++) {
            $a = trim((string) ($left[$i] ?? ''));
            $b = trim((string) ($right[$i] ?? ''));

            if ($a === '' && $b === '') {
                continue;
            }

            $items[] = $mapper($a, $b);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, string>>
     */
    protected function normalizeSteps(array $input): array
    {
        $steps = [];
        $nums = $input['step_num'] ?? [];
        $titles = $input['step_title'] ?? [];
        $descs = $input['step_desc'] ?? [];
        $icons = $input['step_icon'] ?? [];
        $count = max(count($nums), count($titles), count($descs));

        for ($i = 0; $i < $count; $i++) {
            $title = trim((string) ($titles[$i] ?? ''));
            $desc = trim((string) ($descs[$i] ?? ''));

            if ($title === '' && $desc === '') {
                continue;
            }

            $steps[] = [
                'step' => trim((string) ($nums[$i] ?? sprintf('%02d', $i + 1))),
                'title' => $title,
                'desc' => $desc,
                'icon' => trim((string) ($icons[$i] ?? 'zap')) ?: 'zap',
            ];
        }

        return $steps;
    }

    /**
     * @return array<int, string>
     */
    protected function linesToList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value) ?: [])));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeCustomSections(array $input): array
    {
        $sections = [];
        $headings = $input['custom_heading'] ?? [];
        $count = is_array($headings) ? count($headings) : 0;

        for ($i = 0; $i < $count; $i++) {
            $heading = trim((string) ($headings[$i] ?? ''));

            if ($heading === '' && trim((string) ($input['custom_body'][$i] ?? '')) === '') {
                continue;
            }

            $sections[] = [
                'id' => trim((string) ($input['custom_id'][$i] ?? '')) ?: 'section-'.($i + 1),
                'enabled' => (string) ($input['custom_enabled'][$i] ?? '0') === '1',
                'sort_order' => (int) ($input['custom_sort'][$i] ?? $i),
                'layout' => trim((string) ($input['custom_layout'][$i] ?? 'centered')) ?: 'centered',
                'heading' => $heading,
                'subheading' => trim((string) ($input['custom_subheading'][$i] ?? '')),
                'body_html' => (string) ($input['custom_body'][$i] ?? ''),
                'image_url' => trim((string) ($input['custom_image'][$i] ?? '')),
                'image_alt' => trim((string) ($input['custom_image_alt'][$i] ?? '')),
                'bullets' => $this->linesToList($input['custom_bullets'][$i] ?? ''),
                'cta_label' => trim((string) ($input['custom_cta_label'][$i] ?? '')),
                'cta_url' => trim((string) ($input['custom_cta_url'][$i] ?? '')),
                'background' => trim((string) ($input['custom_background'][$i] ?? 'white')) ?: 'white',
            ];
        }

        return $sections;
    }
}
