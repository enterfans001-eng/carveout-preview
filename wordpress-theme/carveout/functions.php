<?php
/**
 * CARVEOUT theme setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

function carveout_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
}
add_action('after_setup_theme', 'carveout_theme_setup');

function carveout_theme_asset(string $path): string
{
    return esc_url(get_template_directory_uri() . '/' . ltrim($path, '/'));
}

function carveout_theme_page_url(string $slug = ''): string
{
    $slug = trim($slug, '/');
    $slug = preg_replace('/\.html$/', '', $slug);
    if ($slug === '' || $slug === 'index') {
        return esc_url(home_url('/'));
    }
    return esc_url(home_url('/' . $slug . '/'));
}

function carveout_theme_static_page_templates(): array
{
    return [
        'about' => 'page-about.php',
        'about-detail' => 'page-about-detail.php',
        'audition' => 'page-audition.php',
        'partner' => 'page-partner.php',
        'livers' => 'page-livers.php',
        'liver' => 'page-livers.php',
        'news' => 'page-news.php',
        'news-detail' => 'page-news-detail.php',
        'events' => 'page-events.php',
        'event' => 'page-events.php',
        'interview' => 'page-interview.php',
        'interview-detail' => 'page-interview-detail.php',
        'benefit' => 'page-benefit.php',
        'services' => 'page-services.php',
        'next-stage' => 'page-next-stage.php',
        'message' => 'page-message.php',
        'kabuu' => 'page-kabuu.php',
        'privacy' => 'page-privacy.php',
        'compliance' => 'page-compliance.php',
        'contact' => 'page-contact.php',
    ];
}

function carveout_theme_current_static_slug(): string
{
    if (is_admin()) {
        return '';
    }

    $request_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $request_path = is_string($request_path) ? $request_path : '';
    $home_path = parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = is_string($home_path) ? trim($home_path, '/') : '';

    $slug = trim($request_path, '/');
    if ($home_path !== '' && ($slug === $home_path || strpos($slug, $home_path . '/') === 0)) {
        $slug = trim(substr($slug, strlen($home_path)), '/');
    }

    $slug = explode('/', $slug)[0] ?? '';
    $slug = preg_replace('/\.html$/', '', $slug);

    return is_string($slug) ? sanitize_title($slug) : '';
}

function carveout_theme_template_for_static_slug(string $slug): string
{
    $templates = carveout_theme_static_page_templates();
    if (!isset($templates[$slug])) {
        return '';
    }

    $template = get_template_directory() . '/' . $templates[$slug];
    return file_exists($template) ? $template : '';
}

function carveout_theme_static_template_include(string $template): string
{
    $static_template = carveout_theme_template_for_static_slug(carveout_theme_current_static_slug());
    if (!$static_template) {
        return $template;
    }

    status_header(200);
    global $wp_query;
    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
    }

    return $static_template;
}
add_filter('template_include', 'carveout_theme_static_template_include', 99);

function carveout_theme_static_pre_handle_404($preempt, WP_Query $query)
{
    if (carveout_theme_template_for_static_slug(carveout_theme_current_static_slug())) {
        $query->is_404 = false;
        return true;
    }

    return $preempt;
}
add_filter('pre_handle_404', 'carveout_theme_static_pre_handle_404', 10, 2);

function carveout_theme_disable_static_canonical($redirect_url)
{
    if (carveout_theme_template_for_static_slug(carveout_theme_current_static_slug())) {
        return false;
    }

    return $redirect_url;
}
add_filter('redirect_canonical', 'carveout_theme_disable_static_canonical');

function carveout_theme_print_wp_base(): void
{
    ?>
    <script>
      window.carveoutWpBaseUrl = <?php echo wp_json_encode(home_url('/')); ?>;
      window.carveoutThemeUrl = <?php echo wp_json_encode(get_template_directory_uri() . '/'); ?>;
    </script>
    <?php
}
add_action('wp_head', 'carveout_theme_print_wp_base', 1);

function carveout_theme_disable_admin_bar_on_frontend(): void
{
    if (!is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'carveout_theme_disable_admin_bar_on_frontend');

function carveout_theme_register_content_types(): void
{
    $post_types = [
        'carveout_news' => [
            'name' => 'ニュース',
            'singular_name' => 'ニュース',
            'menu_icon' => 'dashicons-megaphone',
            'rewrite' => 'cms-news',
        ],
        'carveout_event' => [
            'name' => '事務所イベント',
            'singular_name' => '事務所イベント',
            'menu_icon' => 'dashicons-calendar-alt',
            'rewrite' => 'cms-events',
        ],
        'carveout_interview' => [
            'name' => 'インタビュー',
            'singular_name' => 'インタビュー',
            'menu_icon' => 'dashicons-format-chat',
            'rewrite' => 'cms-interview',
        ],
        'carveout_liver' => [
            'name' => '所属ライバー',
            'singular_name' => '所属ライバー',
            'menu_icon' => 'dashicons-groups',
            'rewrite' => 'cms-livers',
        ],
        'carveout_ranking' => [
            'name' => 'ランキング',
            'singular_name' => 'ランキング',
            'menu_icon' => 'dashicons-awards',
            'rewrite' => 'cms-ranking',
        ],
    ];

    foreach ($post_types as $post_type => $config) {
        register_post_type($post_type, [
            'labels' => [
                'name' => $config['name'],
                'singular_name' => $config['singular_name'],
                'add_new_item' => $config['singular_name'] . 'を追加',
                'edit_item' => $config['singular_name'] . 'を編集',
                'new_item' => '新規' . $config['singular_name'],
                'view_item' => $config['singular_name'] . 'を見る',
                'search_items' => $config['singular_name'] . 'を検索',
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => $config['menu_icon'],
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
            'has_archive' => false,
            'rewrite' => ['slug' => $config['rewrite']],
        ]);
    }
}
add_action('init', 'carveout_theme_register_content_types');

function carveout_theme_register_liver_taxonomy(): void
{
    register_taxonomy('carveout_liver_app', ['carveout_liver'], [
        'labels' => [
            'name' => '配信アプリ',
            'singular_name' => '配信アプリ',
            'add_new_item' => '配信アプリを追加',
            'edit_item' => '配信アプリを編集',
        ],
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'liver-app'],
    ]);

    foreach (carveout_theme_default_liver_apps() as $term) {
        if (!get_term_by('slug', $term['slug'], 'carveout_liver_app')) {
            wp_insert_term($term['name'], 'carveout_liver_app', ['slug' => $term['slug']]);
        }
    }
}
add_action('init', 'carveout_theme_register_liver_taxonomy');

function carveout_theme_default_liver_apps(): array
{
    return [
        ['name' => '17LIVE', 'slug' => '17live'],
        ['name' => 'BIGOLIVE', 'slug' => 'bigolive'],
        ['name' => 'Pococha', 'slug' => 'pococha'],
        ['name' => 'TikTokLIVE', 'slug' => 'tiktoklive'],
        ['name' => 'ミクチャ', 'slug' => 'mixchannel'],
        ['name' => 'SHOWROOM', 'slug' => 'showroom'],
        ['name' => 'Palmu', 'slug' => 'palmu'],
        ['name' => 'ふわっち', 'slug' => 'whowatch'],
        ['name' => 'ライブコマース', 'slug' => 'livecommerce'],
        ['name' => '未分類', 'slug' => 'uncategorized'],
    ];
}

function carveout_theme_default_ranking_categories(): array
{
    return [
        ['name' => '17LIVE', 'slug' => '17live'],
        ['name' => 'BIGOLIVE', 'slug' => 'bigolive'],
    ];
}

function carveout_theme_register_ranking_taxonomy(): void
{
    register_taxonomy('carveout_ranking_category', ['carveout_ranking'], [
        'labels' => [
            'name' => 'ランキングカテゴリ',
            'singular_name' => 'ランキングカテゴリ',
            'add_new_item' => 'ランキングカテゴリを追加',
            'edit_item' => 'ランキングカテゴリを編集',
        ],
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'ranking-category'],
    ]);

    foreach (carveout_theme_default_ranking_categories() as $term) {
        if (!get_term_by('slug', $term['slug'], 'carveout_ranking_category')) {
            wp_insert_term($term['name'], 'carveout_ranking_category', ['slug' => $term['slug']]);
        }
    }
}
add_action('init', 'carveout_theme_register_ranking_taxonomy');

function carveout_theme_flush_rewrites_on_switch(): void
{
    carveout_theme_register_content_types();
    carveout_theme_register_liver_taxonomy();
    carveout_theme_register_ranking_taxonomy();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'carveout_theme_flush_rewrites_on_switch');

function carveout_theme_meta_fields(): array
{
    return [
        'carveout_news' => [
            'carveout_source_url' => ['label' => '元記事URL（任意）', 'type' => 'url'],
            'carveout_image_url' => ['label' => '画像URL（アイキャッチ未設定時）', 'type' => 'url'],
        ],
        'carveout_event' => [
            'carveout_source_url' => ['label' => '元記事URL（任意）', 'type' => 'url'],
            'carveout_image_url' => ['label' => '画像URL（アイキャッチ未設定時）', 'type' => 'url'],
        ],
        'carveout_interview' => [
            'carveout_image_url' => ['label' => '画像URL（アイキャッチ未設定時）', 'type' => 'url'],
        ],
        'carveout_liver' => [
            'carveout_liver_app_text' => ['label' => '配信アプリ名（例：17LIVE / BIGOLIVE / TikTokLIVE / Pococha）', 'type' => 'text'],
            'carveout_profile_url' => ['label' => 'プロフィールURL', 'type' => 'url'],
            'carveout_instagram_url' => ['label' => 'Instagram URL（任意）', 'type' => 'url'],
            'carveout_image_url' => ['label' => '画像URL（アイキャッチ未設定時）', 'type' => 'url'],
        ],
    ];
}

function carveout_theme_add_meta_boxes(): void
{
    foreach (carveout_theme_meta_fields() as $post_type => $fields) {
        add_meta_box(
            'carveout_content_settings',
            'CARVEOUT表示設定',
            'carveout_theme_render_meta_box',
            $post_type,
            'normal',
            'high',
            ['fields' => $fields]
        );
    }

    add_meta_box(
        'carveout_ranking_settings',
        'ランキング',
        'carveout_theme_render_ranking_meta_box',
        'carveout_ranking',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'carveout_theme_add_meta_boxes');

function carveout_theme_render_meta_box(WP_Post $post, array $box): void
{
    wp_nonce_field('carveout_theme_save_meta', 'carveout_theme_meta_nonce');
    $fields = $box['args']['fields'] ?? [];

    echo '<table class="form-table"><tbody>';
    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        $type = $field['type'] ?? 'text';
        printf(
            '<tr><th><label for="%1$s">%2$s</label></th><td><input class="regular-text" id="%1$s" name="%1$s" type="%3$s" value="%4$s"></td></tr>',
            esc_attr($key),
            esc_html($field['label']),
            esc_attr($type),
            esc_attr($value)
        );
    }
    echo '</tbody></table>';
    echo '<p>画像は「アイキャッチ画像」を優先します。URL欄は外部画像をそのまま使いたい場合に入力してください。</p>';
}

function carveout_theme_save_meta(int $post_id): void
{
    if (
        !isset($_POST['carveout_theme_meta_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['carveout_theme_meta_nonce'])), 'carveout_theme_save_meta')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $post_type = get_post_type($post_id);
    $fields = carveout_theme_meta_fields()[$post_type] ?? [];

    foreach ($fields as $key => $field) {
        if (!isset($_POST[$key])) {
            delete_post_meta($post_id, $key);
            continue;
        }

        $value = wp_unslash($_POST[$key]);
        if (($field['type'] ?? 'text') === 'url') {
            $value = esc_url_raw($value);
        } elseif (($field['type'] ?? 'text') === 'number') {
            $value = (string) absint($value);
        } else {
            $value = sanitize_text_field($value);
        }

        update_post_meta($post_id, $key, $value);
    }

    if ($post_type === 'carveout_ranking') {
        carveout_theme_save_ranking_meta($post_id);
    }
}
add_action('save_post', 'carveout_theme_save_meta');

function carveout_theme_ranking_groups(): array
{
    return [
        'total' => ['label' => '総合ランキング', 'type' => '総合'],
        'newcomer' => ['label' => '新人ランキング', 'type' => '新人'],
    ];
}

function carveout_theme_ranking_meta_key(string $group, int $rank, string $field): string
{
    return sprintf('carveout_ranking_%s_%d_%s', $group, $rank, $field);
}

function carveout_theme_render_ranking_meta_box(WP_Post $post): void
{
    wp_nonce_field('carveout_theme_save_meta', 'carveout_theme_meta_nonce');
    ?>
    <style>
      .carveout-ranking-group {
        border: 1px solid #dcdcde;
        margin: 0 0 18px;
      }
      .carveout-ranking-group h3 {
        background: #f6f7f7;
        border-bottom: 1px solid #dcdcde;
        margin: 0;
        padding: 12px 14px;
      }
      .carveout-ranking-rank {
        border-bottom: 1px solid #dcdcde;
        padding: 14px;
      }
      .carveout-ranking-rank:last-child {
        border-bottom: 0;
      }
      .carveout-ranking-rank h4 {
        margin: 0 0 12px;
      }
      .carveout-ranking-preview img {
        display: block;
        height: auto;
        margin-top: 10px;
        max-width: 220px;
      }
    </style>
    <p>右側の「ランキングカテゴリ」で、17LIVE または BIGOLIVE を選択してください。17LIVEはこの中に総合・新人の両方を入力できます。</p>
    <?php
    foreach (carveout_theme_ranking_groups() as $group_key => $group) {
        echo '<div class="carveout-ranking-group">';
        printf('<h3>%s</h3>', esc_html($group['label']));

        for ($rank = 1; $rank <= 5; $rank++) {
            $name_key = carveout_theme_ranking_meta_key($group_key, $rank, 'name');
            $image_key = carveout_theme_ranking_meta_key($group_key, $rank, 'image');
            $url_key = carveout_theme_ranking_meta_key($group_key, $rank, 'url');
            $name = get_post_meta($post->ID, $name_key, true);
            $image = get_post_meta($post->ID, $image_key, true);
            $url = get_post_meta($post->ID, $url_key, true);

            echo '<div class="carveout-ranking-rank">';
            printf('<h4>%d位</h4>', $rank);
            echo '<table class="form-table"><tbody>';
            printf(
                '<tr><th><label for="%1$s">ライバー名</label></th><td><input class="regular-text" id="%1$s" name="%1$s" type="text" value="%2$s"></td></tr>',
                esc_attr($name_key),
                esc_attr($name)
            );
            printf(
                '<tr><th><label for="%1$s">画像アップロード</label></th><td><input class="regular-text carveout-ranking-image-input" id="%1$s" name="%1$s" type="url" value="%2$s"> <button type="button" class="button carveout-ranking-image-button">画像を選択</button><div class="carveout-ranking-preview">%3$s</div></td></tr>',
                esc_attr($image_key),
                esc_attr($image),
                $image ? '<img src="' . esc_url($image) . '" alt="">' : ''
            );
            printf(
                '<tr><th><label for="%1$s">プロフィールURL</label></th><td><input class="regular-text" id="%1$s" name="%1$s" type="url" value="%2$s"></td></tr>',
                esc_attr($url_key),
                esc_attr($url)
            );
            echo '</tbody></table>';
            echo '</div>';
        }

        echo '</div>';
    }
}

function carveout_theme_save_ranking_meta(int $post_id): void
{
    foreach (carveout_theme_ranking_groups() as $group_key => $group) {
        for ($rank = 1; $rank <= 5; $rank++) {
            foreach (['name', 'image', 'url'] as $field) {
                $key = carveout_theme_ranking_meta_key($group_key, $rank, $field);
                if (!isset($_POST[$key])) {
                    delete_post_meta($post_id, $key);
                    continue;
                }

                $value = wp_unslash($_POST[$key]);
                $value = $field === 'name' ? sanitize_text_field($value) : esc_url_raw($value);

                if ($value === '') {
                    delete_post_meta($post_id, $key);
                } else {
                    update_post_meta($post_id, $key, $value);
                }
            }
        }
    }
}

function carveout_theme_admin_assets(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'carveout_ranking') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', <<<'JS'
jQuery(function ($) {
  $(document).on('click', '.carveout-ranking-image-button', function (event) {
    event.preventDefault();

    const button = $(this);
    const input = button.siblings('.carveout-ranking-image-input');
    const preview = button.siblings('.carveout-ranking-preview');
    const frame = wp.media({
      title: 'ランキング画像を選択',
      button: { text: 'この画像を使用' },
      multiple: false
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      input.val(attachment.url);
      preview.html('<img src="' + attachment.url + '" alt="">');
    });

    frame.open();
  });
});
JS);
}
add_action('admin_enqueue_scripts', 'carveout_theme_admin_assets');

function carveout_theme_post_image_url(int $post_id): string
{
    $featured = get_the_post_thumbnail_url($post_id, 'large');
    if ($featured) {
        return esc_url_raw($featured);
    }

    return esc_url_raw((string) get_post_meta($post_id, 'carveout_image_url', true));
}

function carveout_theme_format_date(int $post_id): array
{
    $timestamp = get_post_time('U', false, $post_id);

    return [
        'date' => wp_date('Y.m.d', $timestamp),
        'datetime' => wp_date('Y-m-d', $timestamp),
    ];
}

function carveout_theme_text_is_office_event(string $title, string $content): bool
{
    $haystack = $title . "\n" . $content;

    return strpos($haystack, '事務所イベント') !== false
        || (
            strpos($haystack, '17LIVEにて配信している') !== false
            && strpos($haystack, 'イベント') !== false
        );
}

function carveout_theme_text_has_excluded_platform(string $title, string $content): bool
{
    $haystack = strtolower($title . "\n" . $content);

    return strpos($haystack, 'tiktok') !== false
        || strpos($haystack, 'pococha') !== false;
}

function carveout_theme_get_cms_posts(string $post_type, int $limit = -1): array
{
    $query = new WP_Query([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => ['date' => 'DESC', 'menu_order' => 'ASC'],
        'no_found_rows' => true,
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        $post_title = get_the_title($post);
        $post_content_text = wp_strip_all_tags((string) $post->post_content);

        if (
            in_array($post_type, ['carveout_news', 'carveout_event', 'carveout_interview'], true)
            && carveout_theme_text_has_excluded_platform($post_title, $post_content_text)
        ) {
            continue;
        }

        if (
            $post_type === 'carveout_news'
            && carveout_theme_text_is_office_event($post_title, $post_content_text)
        ) {
            continue;
        }

        $date = carveout_theme_format_date($post->ID);
        $source_url = (string) get_post_meta($post->ID, 'carveout_source_url', true);

        $items[] = [
            'id' => (string) $post->ID,
            'date' => $date['date'],
            'datetime' => $date['datetime'],
            'title' => $post_title,
            'url' => $source_url ?: get_permalink($post),
            'detailUrl' => add_query_arg('id', $post->ID, get_permalink($post)),
            'image' => carveout_theme_post_image_url($post->ID),
            'body' => array_values(array_filter([wp_strip_all_tags(get_the_excerpt($post))])),
            'detailHtml' => apply_filters('the_content', $post->post_content),
        ];
    }

    wp_reset_postdata();

    return $items;
}

function carveout_theme_get_livers(): array
{
    $query = new WP_Query([
        'post_type' => 'carveout_liver',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows' => true,
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        $terms = wp_get_post_terms($post->ID, 'carveout_liver_app', ['fields' => 'names']);
        $category = (string) get_post_meta($post->ID, 'carveout_liver_app_text', true);
        if (!$category && !is_wp_error($terms) && !empty($terms)) {
            $category = $terms[0];
        }

        $items[] = [
            'name' => get_the_title($post),
            'category' => $category ?: '17LIVE',
            'url' => (string) get_post_meta($post->ID, 'carveout_profile_url', true),
            'instagramUrl' => (string) get_post_meta($post->ID, 'carveout_instagram_url', true),
            'image' => carveout_theme_post_image_url($post->ID),
        ];
    }

    wp_reset_postdata();

    return $items;
}

function carveout_theme_get_rankings(): array
{
    $query = new WP_Query([
        'post_type' => 'carveout_ranking',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows' => true,
    ]);

    $items = [];

    foreach ($query->posts as $post) {
        $term_meta = carveout_theme_infer_ranking_term_meta($post->ID);
        $category = $term_meta['category'] ?: '17LIVE';
        $has_grouped_meta = false;

        foreach (carveout_theme_ranking_groups() as $group => $group_config) {
            for ($rank = 1; $rank <= 5; $rank++) {
                $name = trim((string) get_post_meta($post->ID, carveout_theme_ranking_meta_key($group, $rank, 'name'), true));
                $image = trim((string) get_post_meta($post->ID, carveout_theme_ranking_meta_key($group, $rank, 'image'), true));
                $url = trim((string) get_post_meta($post->ID, carveout_theme_ranking_meta_key($group, $rank, 'url'), true));

                if ($name === '' && $image === '') {
                    continue;
                }

                $has_grouped_meta = true;
                $items[] = [
                    'name' => $name,
                    'category' => $category,
                    'type' => $group_config['type'],
                    'rank' => $rank,
                    'url' => $url,
                    'image' => esc_url_raw($image),
                ];
            }
        }

        if ($has_grouped_meta) {
            continue;
        }

        $legacy_category = trim((string) get_post_meta($post->ID, 'carveout_liver_app_text', true));
        $legacy_type = trim((string) get_post_meta($post->ID, 'carveout_ranking_type', true));
        $legacy_rank = (int) get_post_meta($post->ID, 'carveout_rank_number', true);

        if ($legacy_rank > 0) {
            $items[] = [
                'name' => get_the_title($post),
                'category' => $legacy_category ?: $category,
                'type' => $legacy_type ?: ($term_meta['type'] ?: '総合'),
                'rank' => $legacy_rank,
                'url' => (string) get_post_meta($post->ID, 'carveout_profile_url', true),
                'image' => carveout_theme_post_image_url($post->ID),
            ];
        }
    }

    wp_reset_postdata();

    return $items;
}

function carveout_theme_infer_ranking_term_meta(int $post_id): array
{
    $meta = [
        'category' => '',
        'type' => '',
    ];

    $terms = wp_get_post_terms($post_id, 'carveout_ranking_category');
    if (is_wp_error($terms) || empty($terms)) {
        return $meta;
    }

    foreach ($terms as $term) {
        $key = strtolower($term->slug . ' ' . $term->name);

        if (!$meta['category']) {
            if (strpos($key, 'bigolive') !== false || strpos($key, 'bigo') !== false) {
                $meta['category'] = 'BIGOLIVE';
            } elseif (strpos($key, '17live') !== false || strpos($key, '17') !== false) {
                $meta['category'] = '17LIVE';
            }
        }

        if (!$meta['type']) {
            if (strpos($key, 'newcomer') !== false || strpos($key, '新人') !== false) {
                $meta['type'] = '新人';
            } elseif (strpos($key, 'total') !== false || strpos($key, '総合') !== false) {
                $meta['type'] = '総合';
            }
        }
    }

    return $meta;
}

function carveout_theme_print_cms_data(): void
{
    if (is_admin()) {
        return;
    }

    $data = [
        'news' => carveout_theme_get_cms_posts('carveout_news'),
        'events' => carveout_theme_get_cms_posts('carveout_event'),
        'interviews' => carveout_theme_get_cms_posts('carveout_interview'),
        'livers' => carveout_theme_get_livers(),
        'rankings' => carveout_theme_get_rankings(),
    ];
    ?>
    <script>
      window.carveoutWpCms = <?php echo wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <?php
}
add_action('wp_head', 'carveout_theme_print_cms_data', 20);

function carveout_theme_source_imports(): array
{
    return [
        'news' => [
            'label' => 'ニュース',
            'endpoint' => 'news',
            'post_type' => 'carveout_news',
        ],
        'events' => [
            'label' => '事務所イベント',
            'endpoint' => 'news',
            'post_type' => 'carveout_event',
            'filter' => 'office_event_news',
        ],
        'interviews' => [
            'label' => 'インタビュー',
            'endpoint' => 'liver-interview',
            'post_type' => 'carveout_interview',
        ],
        'livers' => [
            'label' => '所属ライバー',
            'endpoint' => 'liver-list',
            'post_type' => 'carveout_liver',
        ],
    ];
}

function carveout_theme_source_item_image(array $item): string
{
    $media = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';

    return is_string($media) ? esc_url_raw($media) : '';
}

function carveout_theme_source_item_terms(array $item): array
{
    $term_groups = $item['_embedded']['wp:term'] ?? [];
    $names = [];

    if (!is_array($term_groups)) {
        return $names;
    }

    foreach ($term_groups as $terms) {
        if (!is_array($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            $name = $term['name'] ?? '';
            if (is_string($name) && $name !== '未分類') {
                $names[] = wp_strip_all_tags($name);
            }
        }
    }

    return array_values(array_unique(array_filter($names)));
}

function carveout_theme_fetch_source_items(string $endpoint): array
{
    $items = [];
    $page = 1;
    $total_pages = 1;

    do {
        $url = add_query_arg([
            'per_page' => 100,
            'page' => $page,
            '_embed' => 1,
        ], 'https://ccarveout.jp/wp-json/wp/v2/' . rawurlencode($endpoint));

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'redirection' => 3,
        ]);

        if (is_wp_error($response)) {
            return $items;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return $items;
        }

        $items = array_merge($items, $body);
        $total_pages = max(1, (int) wp_remote_retrieve_header($response, 'x-wp-totalpages'));
        $page++;
    } while ($page <= $total_pages);

    return $items;
}

function carveout_theme_find_imported_post(string $source_type, int $source_id, string $post_type): int
{
    $query = new WP_Query([
        'post_type' => $post_type,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [
            [
                'key' => '_carveout_source_type',
                'value' => $source_type,
            ],
            [
                'key' => '_carveout_source_id',
                'value' => (string) $source_id,
            ],
        ],
    ]);

    return !empty($query->posts) ? (int) $query->posts[0] : 0;
}

function carveout_theme_import_source_item(string $source_type, array $config, array $item): bool
{
    $source_id = (int) ($item['id'] ?? 0);
    if (!$source_id) {
        return false;
    }

    $post_type = $config['post_type'];
    $existing_id = carveout_theme_find_imported_post($source_type, $source_id, $post_type);
    $title = wp_strip_all_tags((string) ($item['title']['rendered'] ?? ''));
    $content = (string) ($item['content']['rendered'] ?? '');
    $excerpt = wp_strip_all_tags((string) ($item['excerpt']['rendered'] ?? ''));
    $date = (string) ($item['date'] ?? current_time('mysql'));

    $post_data = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'post_title' => $title ?: '無題',
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_date' => str_replace('T', ' ', $date),
    ];

    if ($existing_id) {
        $post_data['ID'] = $existing_id;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id) || !$post_id) {
        return false;
    }

    update_post_meta($post_id, '_carveout_source_type', $source_type);
    update_post_meta($post_id, '_carveout_source_id', (string) $source_id);

    $source_url = esc_url_raw((string) ($item['link'] ?? ''));
    $image_url = carveout_theme_source_item_image($item);

    if (in_array($post_type, ['carveout_news', 'carveout_event'], true)) {
        update_post_meta($post_id, 'carveout_source_url', $source_url);
    }

    if ($image_url) {
        update_post_meta($post_id, 'carveout_image_url', $image_url);
    }

    if ($post_type === 'carveout_liver') {
        $terms = carveout_theme_source_item_terms($item);
        $app = $terms[0] ?? '17LIVE';
        update_post_meta($post_id, 'carveout_liver_app_text', $app);
        wp_set_object_terms($post_id, $app, 'carveout_liver_app', false);
    }

    return true;
}

function carveout_theme_should_import_source_item(array $config, array $item): bool
{
    $title = wp_strip_all_tags((string) ($item['title']['rendered'] ?? ''));
    $content = wp_strip_all_tags((string) ($item['content']['rendered'] ?? ''));

    if (
        in_array(($config['post_type'] ?? ''), ['carveout_news', 'carveout_event', 'carveout_interview'], true)
        && carveout_theme_text_has_excluded_platform($title, $content)
    ) {
        return false;
    }

    if (($config['filter'] ?? '') !== 'office_event_news') {
        return true;
    }

    return carveout_theme_text_is_office_event($title, $content);
}

function carveout_theme_run_source_import(string $source_type): array
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(180);
    }

    $imports = carveout_theme_source_imports();
    $selected = $source_type === 'all' ? $imports : array_intersect_key($imports, [$source_type => true]);
    $result = [];

    foreach ($selected as $key => $config) {
        $items = carveout_theme_fetch_source_items($config['endpoint']);
        $count = 0;

        foreach ($items as $item) {
            if (
                is_array($item)
                && carveout_theme_should_import_source_item($config, $item)
                && carveout_theme_import_source_item($key, $config, $item)
            ) {
                $count++;
            }
        }

        $result[] = $config['label'] . '：' . $count . '件';
    }

    return $result;
}

function carveout_theme_add_import_admin_page(): void
{
    add_management_page(
        'CARVEOUT旧サイト取り込み',
        'CARVEOUT旧サイト取り込み',
        'manage_options',
        'carveout-source-import',
        'carveout_theme_render_import_admin_page'
    );
}
add_action('admin_menu', 'carveout_theme_add_import_admin_page');

function carveout_theme_render_import_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $imports = carveout_theme_source_imports();
    $messages = [];

    if (
        isset($_POST['carveout_import_source'], $_POST['carveout_import_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['carveout_import_nonce'])), 'carveout_import_source')
    ) {
        $source = sanitize_key(wp_unslash($_POST['carveout_import_source']));
        if ($source === 'all' || isset($imports[$source])) {
            $messages = carveout_theme_run_source_import($source);
        }
    }
    ?>
    <div class="wrap">
      <h1>CARVEOUT旧サイト取り込み</h1>
      <p>現在のCARVEOUT公式サイトから記事をコピーし、この新サイト側の投稿として保存します。取り込み後は新サイト側で編集できます。</p>
      <p>同じ元記事IDのデータは重複作成せず、既存投稿を更新します。</p>

      <?php foreach ($messages as $message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?> を取り込みました。</p></div>
      <?php endforeach; ?>

      <table class="widefat striped" style="max-width: 760px;">
        <thead><tr><th>取り込み内容</th><th>操作</th></tr></thead>
        <tbody>
          <?php foreach ($imports as $key => $config): ?>
            <tr>
              <td><?php echo esc_html($config['label']); ?></td>
              <td>
                <form method="post">
                  <?php wp_nonce_field('carveout_import_source', 'carveout_import_nonce'); ?>
                  <input type="hidden" name="carveout_import_source" value="<?php echo esc_attr($key); ?>">
                  <button class="button button-primary" type="submit"><?php echo esc_html($config['label']); ?>を取り込む</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td>すべて</td>
            <td>
              <form method="post">
                <?php wp_nonce_field('carveout_import_source', 'carveout_import_nonce'); ?>
                <input type="hidden" name="carveout_import_source" value="all">
                <button class="button" type="submit">すべて取り込む</button>
              </form>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php
}
