<?php
/**
 * Article feed shortcode for AMC Magazin.
 * Usage: [article_feed per_page="8" category="zeitgeist"]
 */

// [article_feed per_page="8" category="zeitgeist"]
add_shortcode('article_feed', function ($atts) {
    $atts = shortcode_atts([
        'per_page' => 8,
        'category' => '', // optional: filter by category slug
    ], $atts, 'article_feed');

    // Current page for pagination (works on pages & archives)
    $paged = get_query_var('paged') ?: (get_query_var('page') ?: 1);

    $args = [
        'post_type'           => 'post',
        'posts_per_page'      => (int) $atts['per_page'],
        'paged'               => (int) $paged,
        'ignore_sticky_posts' => 1,
        'no_found_rows'       => false,
    ];
    if (!empty($atts['category'])) {
        $args['category_name'] = sanitize_text_field($atts['category']);
    }

    $q = new WP_Query($args);
    ob_start();

    if ($q->have_posts()) {
        echo '<section class="amc-feed" aria-label="Artikel">';
        while ($q->have_posts()) {
            $q->the_post();
            $post_id = get_the_ID();

            // Category label (first category or primary if available)
            $cats = get_the_category($post_id);
            $cat_title = $cats ? $cats[0]->name : '';
            if (function_exists('yoast_get_primary_term_id')) {
                $primary_cat_id = yoast_get_primary_term_id('category', $post_id);
                if ($primary_cat_id) {
                    $primary_cat = get_term( $primary_cat_id, 'category' );
                    if ($primary_cat && !is_wp_error($primary_cat)) {
                        $cat_title = $primary_cat->name;
                    }
                }
            }
            $cat_label = $cat_title ? (function_exists('mb_strtoupper') ? mb_strtoupper($cat_title) : strtoupper($cat_title)) : '';

            // Sponsored flag: ACF true/false OR tag "anzeige"/"advertorial"
            $is_sponsored = false;
            if (function_exists('get_field')) {
                $is_sponsored = (bool) get_field('is_sponsored', $post_id);
            }
            if (!$is_sponsored && (has_term(['anzeige','advertorial'], 'post_tag', $post_id))) {
                $is_sponsored = true;
            }

            echo '<article class="amc-card">';

            // Media
            echo '  <a class="amc-card__media" href="' . esc_url(get_permalink()) . '">';
            if (has_post_thumbnail()) {
                echo wp_get_attachment_image(
                    get_post_thumbnail_id($post_id),
                    'amc-card',
                    false,
                    [
                        'class'         => 'amc-card__img',
                        'loading'       => ($q->current_post === 0 && (int)$paged === 1) ? 'eager' : 'lazy',
                        'fetchpriority' => ($q->current_post === 0 && (int)$paged === 1) ? 'high' : 'auto',
                        'sizes'         => '(min-width:1200px) 1152px, (min-width:981px) 960px, 100vw',
                    ]
                );
            } else {
                echo '<span class="amc-card__placeholder" aria-hidden="true"></span>';
            }
            echo '  </a>';

            // Body
            echo '  <div class="amc-card__body">';
            if ($cat_label) {
                echo '    <div class="amc-eyebrow">';
                echo '      <span class="amc-eyebrow__bar" aria-hidden="true"></span>';
                echo '      <span class="amc-eyebrow__label">' . esc_html($cat_label) . '</span>';
                echo '    </div>';
            }
            if ($is_sponsored) {
                echo '    <div class="amc-adlabel">ANZEIGE</div>';
            }

            echo '    <h2 class="amc-card__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h2>';

            $author = get_the_author_meta('display_name', get_post_field('post_author', $post_id));
            if ($author) {
                echo '    <p class="amc-card__meta">' . esc_html($author) . '</p>';
            }
            echo '  </div>';

            echo '</article>';
        }
        echo '</section>';

        // Pagination (works for pages & archives; supports non-pretty permalinks)
        $is_archive_ctx = is_home() || is_archive();
        $using_permalinks = (bool) get_option( 'permalink_structure' );

        if ( $is_archive_ctx ) {
            $base   = str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) );
            $format = $using_permalinks ? 'page/%#%/' : '&paged=%#%';
        } else {
            if ( $using_permalinks ) {
                $base   = trailingslashit( get_permalink( get_queried_object_id() ) ) . 'page/%#%/';
                $format = '';
            } else {
                $base   = add_query_arg( 'paged', '%#%', get_permalink( get_queried_object_id() ) );
                $format = '';
            }
        }

        $pagination = paginate_links([
            'base'      => $base,
            'format'    => $format,
            'current'   => max(1, (int)$paged),
            'total'     => (int)$q->max_num_pages,
            'type'      => 'list',
            'prev_text' => 'Zurück',
            'next_text' => 'Weiter',
        ]);
        if ($pagination) {
            echo '<nav class="amc-pagination" aria-label="Seiten">' . $pagination . '</nav>';
        }

        // SEO: ItemList JSON-LD
        $items = [];
        foreach ($q->posts as $index => $p) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => ($q->query_vars['posts_per_page'] * ((int)$paged - 1)) + ($index + 1),
                'url'      => get_permalink($p),
                'name'     => get_the_title($p),
            ];
        }
        echo '<script type="application/ld+json">' . wp_json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $items,
        ]) . '</script>';

    } else {
        echo '<p>Keine Beiträge gefunden.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});
