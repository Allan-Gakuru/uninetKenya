<?php
/** Run with: php tools/test-archive-seo.php */

namespace RankMath {
    final class Term
    {
        public static function get_meta($key, $term, $taxonomy)
        {
            return $GLOBALS['archive_meta'][$key] ?? '';
        }
    }

    final class Helper
    {
        public static function replace_vars($text, $term)
        {
            return preg_replace('/ +/', ' ', strtr($text, [
                '%page%' => $GLOBALS['archive_page'] > 1 ? 'Page ' . $GLOBALS['archive_page'] : '',
                '%sep%' => '|',
                '%sitename%' => 'Uninet Technologies',
            ]));
        }
    }
}

namespace {
    define('ABSPATH', __DIR__);

    final class WP_Term
    {
        public function __construct(public string $taxonomy, public string $slug)
        {
        }
    }

    function add_filter($name, $callback)
    {
        $GLOBALS['archive_hooks'][$name] = $callback;
    }

    function is_tax($taxonomies)
    {
        return in_array($GLOBALS['archive_query'] ?? '', $taxonomies, true);
    }

    function get_queried_object()
    {
        return $GLOBALS['archive_term'] ?? null;
    }

    function check($condition, $message)
    {
        if (! $condition) {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }
    }

    require __DIR__ . '/../wp-content/plugins/uninet-core/includes/WooCommerce/ArchiveSeo.php';

    $module = new \Uninet\Core\WooCommerce\ArchiveSeo();
    $module->register();
    $title = $archive_hooks['rank_math/frontend/title'];
    $description = $archive_hooks['rank_math/frontend/description'];
    $archive_page = 1;

    $inventory = [
        'product_cat' => 'power-backup-solutions accessories-and-cables computer-mouse keyboards laptop-stands mousepads security-products access-control cctv-cameras digital-video-recorders desktops all-in-one-computers complete-desktop-computers high-end-desktop-computers office-desktop-computers laptops business-laptops entry-level-laptops executive-laptops laptops-for-power-users student-laptops monitors networking printers ink-tank laser-printers',
        'product_brand' => 'brother canon dell epson hik-vision hp kyocera lenovo',
    ];

    $seen_titles = $seen_descriptions = [];
    foreach ($inventory as $taxonomy => $slugs) {
        $archive_query = $taxonomy;
        foreach (explode(' ', $slugs) as $slug) {
            $archive_term = new WP_Term($taxonomy, $slug);
            $actual_title = $title('Original title');
            $actual_description = $description('Original description');
            check(str_contains($actual_title, 'in Kenya | Uninet Technologies'), "$slug has no Kenya-focused title");
            check(str_contains($actual_description, 'in Kenya') && strlen($actual_description) <= 160, "$slug has no concise description");
            check(! isset($seen_titles[$actual_title]) && ! isset($seen_descriptions[$actual_description]), "$slug has duplicate metadata");
            $seen_titles[$actual_title] = $seen_descriptions[$actual_description] = true;
        }
    }

    $archive_query = 'product_cat';
    $archive_term = new WP_Term('product_cat', 'printers');
    $archive_page = 2;
    check(str_contains($title('Original title'), 'Page 2'), 'Paginated titles should include a page number');
    $archive_meta['title'] = 'Staff title';
    check('Staff title' === $title('Staff title'), 'A saved title should take precedence');
    check('Original description' !== $description('Original description'), 'An override should affect only its own field');
    $archive_meta['description'] = 'Staff description';
    check('Staff description' === $description('Staff description'), 'A saved description should take precedence');
    $archive_meta = [];

    foreach ([new WP_Term('product_cat', 'uncategorized'), new WP_Term('product_cat', 'future-category'), new WP_Term('product_brand', 'printers'), null] as $term) {
        $archive_term = $term;
        check('Original title' === $title('Original title'), 'Unmapped terms should retain their existing title');
        check('Original description' === $description('Original description'), 'Unmapped terms should retain their existing description');
    }

    foreach (['product', 'shop', 'product_tag', 'category', 'post_tag'] as $archive_query) {
        $archive_term = new WP_Term($archive_query, 'printers');
        check('Original title' === $title('Original title'), "$archive_query titles should not change");
        check('Original description' === $description('Original description'), "$archive_query descriptions should not change");
    }

    printf("Archive SEO checks passed for %d archives.\n", count($seen_titles));
}
