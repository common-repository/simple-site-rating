<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wsmssr_post_array = array(
    'attachment',
    'revision',
    'nav_menu_item',
    'custom_css',
    'customize_changeset',
    'user_request',
    'wp_template',
    'wp_template_part',
    'wp_global_styles',
    'wp_navigation',
    'gp_elements',
    'gblocks_templates',
    'gblocks_global_style',
    'oembed_cache',
    'wp_block',
    'shop_order',
    'product_variation',
    'shop_order_refund',
    'shop_coupon',
    'shop_order_placehold',
    'wpcf7_contact_form',
    'acf-field-group',
    'acf-field'
);

/**
 * Add plugin action links ( setting link beside of deactive plugin btn ).
 */
function wsmssr_plugin_action_links($links) {

    $links = array_merge(array(
        '<a href="' . esc_url(admin_url('/options-general.php')) . '?page=simple-site-rating">' . esc_html__('Settings', 'simple-site-rating') . '</a>'
    ), $links);

    return $links;
}
add_action('plugin_action_links_' . WSMSSR_PLUGIN_BASENAME, 'wsmssr_plugin_action_links');


/**
 * WSM Custom Shortcode To display Question.
 * [simple-site-rating]
 */
add_shortcode('simple-site-rating', 'wsmssr_shortcode_display');
function wsmssr_shortcode_display() {
    global $post;
    $Content = '';
    $post_type = get_post_type($post->ID);
    $options_post_type = get_option('wsmssr_posttype_settings_options');

    if (in_array($post_type, $options_post_type)) {

        $wsmssr_question = get_option('wsmssr_question');
        $thankyou_text = get_option('wsmssr_thank_you_html');
        $wsmssr_thank_you_heading_html = get_option('wsmssr_thank_you_heading_html');
        $icon_color = get_option('wsmssr_icon_color_html');
        $icon_hover_color = get_option('wsmssr_icon_hover_color_html');

        $Content .= '<style>
                        .wsmssr-page-feed-answer .icon .fa-laugh,
                        .wsmssr-page-feed-answer .icon .fa-smile,
                        .wsmssr-page-feed-answer .icon .fa-meh,
                        .wsmssr-page-feed-answer .icon .fa-frown,
                        .wsmssr-page-feed-image .fa-question,
                        .wsmssr-page-feed-image .fa-exclamation{color:' . $icon_color . ';}
                        .wsmssr-page-feed-answer .icon .svg-inline--fa:hover { color: ' . $icon_hover_color . '; }
                    </style>';

        $Content .= '<div class="wsmssr-page-feed-container">';

        $Content .= '<div class="wsmssr-page-feed-title-section">';

        $Content .= '<div class="wsmssr-page-feed-title">';
        $Content .= '<h4 class="wsmssr_title_question">' . $wsmssr_question . '</h4>';
        $Content .= '<h4 class="wsmssr_thankyou_heading" style="display:none;">' . $wsmssr_thank_you_heading_html . '</h4>';
        $Content .= '</div>';

        $Content .= '</div>';

        $Content .= '<div class="wsmssr-page-feed-answer" >';

        $Content .= '<div class="icon" data="very_good"><i class="far fa-laugh"></i></div>';
        $Content .= '<div class="icon" data="good"><i class="far fa-smile"></i></div>';
        $Content .= '<div class="icon" data="bad"><i class="far fa-meh"></i></div>';
        $Content .= '<div class="icon" data="very_bad"><i class="far fa-frown"></i></div>';

        $Content .= '<h5 class="wsmssr_thankyou_text" style="display:none;">' . $thankyou_text . '</h5>';

        $Content .= '</div>';

        $Content .= '</div>';
    }
    return $Content;
}

/**
 * AJAX call back for save Feed data in table
 */
add_action('wp_ajax_nopriv_wsmssr_save_feed', 'wsmssr_save_feed');
add_action('wp_ajax_wsmssr_save_feed', 'wsmssr_save_feed');
function wsmssr_save_feed() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['nonce'] ) ) , 'wsmssr-ajax-nonce' ) ) {
        die('Busted!');
    }

    global $wpdb;

    $id = sanitize_text_field($_POST['id']);
    $fedd = sanitize_text_field($_POST['feed']);
    $date = date('Y-m-d H:i:s');

    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $data = array('pageid' => $id, 'rate_date' => $date, 'rate' => $fedd);
    $format = array('%s', '%s', '%s');

    $wpdb->insert($table, $data, $format);

    return true;
    die();
}

/**
 * Add Rating Average colomn in each post type's admin table
 */
add_action('admin_init', 'wsmssr_admin_init_hooks');
function wsmssr_admin_init_hooks() {
    global $wsmssr_post_array;
    $value = "";

    $options = get_option('wsmssr_posttype_settings_options');

    if (isset($options) && !empty($options)) {
        $value = $options;
    }
    
    if ( !empty($value) ) {

        foreach (get_post_types() as $post_name) {
            if (!in_array($post_name, $wsmssr_post_array)) {
                if (in_array($post_name, $value)) {
                    add_filter('manage_' . $post_name . 's_columns', 'wsmssr_columns_head');
                    add_filter('manage_edit-' . $post_name . '_sortable_columns', 'wsmssr_columns_head');
                    add_action('manage_' . $post_name . 's_custom_column', 'wsmssr_columns_content', 10, 2);
                }
            }
        }
        
    }
}

function wsmssr_columns_head($columns) {
    $columns['wsmssr_col'] = esc_html__('Rating Average', 'simple-site-rating');
    return $columns;
}

function wsmssr_column_orderby($vars) {
    if (isset($vars['orderby']) && 'Rating Average' == $vars['orderby']) {
        $vars = array_merge($vars, array(
            'meta_key' => 'wsmssr_rating_avg',
            'orderby' => 'meta_value_num'
        ));
    }
    return $vars;
}
add_filter('request', 'wsmssr_column_orderby');

function wsmssr_columns_content($column_name, $post_ID) {
    if ($column_name == 'wsmssr_col') {
        $very_good = wsmssr_get_very_good_feed_score($post_ID);
        $good = wsmssr_get_good_feed_score($post_ID);
        $bad = wsmssr_get_bad_feed_score($post_ID);
        $very_bad = wsmssr_get_very_bad_feed_score($post_ID);

        $html = '';
        $votes = esc_html__('votes', 'simple-site-rating');
        $html .= '<div class="wsmssr-pagerank-col"><div class="wsmssr-tooltip">
                    <span class="dashicons dashicons-chart-bar"></span> <strong>' . wsmssr_get_post_page_rank_avg($post_ID) . '</strong> (' . wsmssr_get_table_data_count($post_ID) . ' ' . $votes . ')      
                    <span class="wsmssr-tooltiptext">
                        <ul>
                            <li>😀 ' . $very_good . ' ' . $votes . '</li>
                            <li>🙂 ' . $good . ' ' . $votes . '</li>
                            <li>😐 ' . $bad . ' ' . $votes . '</li>
                            <li>🙁 ' . $very_bad . ' ' . $votes . '</li>
                        </ul>
                    </span>
                </div></div>';
        echo wp_kses_post($html);
    }
}

function wsmssr_custom_admin_head_css() {
    echo '<style>
    .wsmssr-tooltip { position: relative; display: inline-block; border-bottom: 1px dotted black; cursor: pointer; }
    .wsmssr-tooltip .wsmssr-tooltiptext { visibility: hidden; width: 120px; background-color: #FFFFFF; color: #000000; text-align: center;   border:1px dotted black; border-radius: 0; padding: 5px 0; z-index: 1; cursor: unset; position: absolute; right: 0; top: 100%; }
    .wsmssr-tooltip:hover .wsmssr-tooltiptext { visibility: visible; }
    .wsmssr-tooltip .wsmssr-tooltiptext ul,
    .wsmssr-tooltip .wsmssr-tooltiptext ul li{ margin:0; padding:0px; }
    </style>';
}
add_action( 'admin_head', 'wsmssr_custom_admin_head_css' );

/** 
 * Code for Rating Average calculation
 */
function wsmssr_get_post_page_rank_avg($post_id) {

    $count = wsmssr_get_table_data_count($post_id);
    if ($count >= 0) {
        $very_good = wsmssr_get_very_good_feed_score($post_id);
        $good = wsmssr_get_good_feed_score($post_id);
        $bad = wsmssr_get_bad_feed_score($post_id);
        $very_bad = wsmssr_get_very_bad_feed_score($post_id);
        /* very_good = 4 , good = 3 , bad = 2 , very_bad = 1  */
        if ($count !== 0) {
            $avg = (($very_good * 4) + ($good * 3) + ($bad * 2) + ($very_bad * 1)) / $count;
            $num_format = number_format($avg, 2);
        } else {
            $num_format = 0.00;
        }

        $meta_value = get_post_meta($post_id, 'wsmssr_rating_avg', true);
        if ($meta_value != '') {
            update_post_meta($post_id, 'wsmssr_rating_avg', $num_format);
        } else {
            add_post_meta($post_id, 'wsmssr_rating_avg', $num_format);
        }

        return $num_format;
    } else {
        return '0';
    }
}

function wsmssr_get_table_data_count($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $query = $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}wsmssr_page_rank WHERE pageid= %d",$post_id);
    $post_id = $wpdb->get_results($query);
    return count($post_id);
}

function wsmssr_get_very_good_feed_score($post_ID) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $meta_key = 'very_good';
    $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}wsmssr_page_rank WHERE pageid= %d AND rate= %s",$post_ID,$meta_key);
    $res = $wpdb->get_results($query);
    return count($res);
}

function wsmssr_get_good_feed_score($post_ID) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $meta_key = 'good';
    $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}wsmssr_page_rank WHERE pageid= %d AND rate= %s",$post_ID,$meta_key);
    $res = $wpdb->get_results($query);
    return count($res);
}

function wsmssr_get_bad_feed_score($post_ID) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $meta_key = 'bad';
    $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}wsmssr_page_rank WHERE pageid= %d AND rate= %s",$post_ID,$meta_key);
    $res = $wpdb->get_results($query);
    return count($res);
}

function wsmssr_get_very_bad_feed_score($post_ID) {
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $meta_key = 'very_bad';
    $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}wsmssr_page_rank WHERE pageid= %d AND rate= %s",$post_ID,$meta_key);
   
    $res = $wpdb->get_results($query);
    return count($res);
}

/**
 * Add Admin menu option Name: WSM Options 
 */
add_action('admin_menu', 'wsmssr_options_page');

function wsmssr_options_page() {

    add_options_page(
        esc_html__('Simple Site Rating', 'simple-site-rating'), // page <title>Title</title>
        esc_html__('Simple Site Rating', 'simple-site-rating'), // menu link text
        'manage_options', // capability to access the page
        'simple-site-rating', // page URL slug
        'wsmssr_page_content', // callback function with content
        7 // priority
    );
}

/**
 * Display content on option page
 */
function wsmssr_page_content() {

?>
    <div class="wrap">

        <style>
            p.submit {
                display: inline-block;
                padding-right: 20px;
            }

            a.wsmssr_delete_rating_btn {
                color: #D63638;
                text-decoration: underline;
                cursor: pointer;
            }

            .wsmssr-notice {
                display: none;
            }

            .wp_shortcode_display,
            .php_shortcode_display {
                display: flex;
            }

            .shortcode_display .wp_shortcode_display a,
            .shortcode_display .php_shortcode_display a {
                width: 16px;
                margin-left: 15px;
                display: flex;
            }

            .shortcode_display a:focus {
                box-shadow: none;
            }
        </style>

        <style>
            #notification_display {
                visibility: hidden;
                min-width: 150px;
                margin-left: -50px;
                background-color: #333;
                color: #fff;
                text-align: center;
                border-radius: 2px;
                padding: 16px;
                position: fixed;
                z-index: 1;
                left: 50%;
                bottom: 30px;
                font-size: 17px;
            }

            #notification_display.show {
                visibility: visible;
                -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
                animation: fadein 0.5s, fadeout 0.5s 2.5s;
            }

            @-webkit-keyframes fadein {
                from {
                    bottom: 0;
                    opacity: 0;
                }

                to {
                    bottom: 30px;
                    opacity: 1;
                }
            }

            @keyframes fadein {
                from {
                    bottom: 0;
                    opacity: 0;
                }

                to {
                    bottom: 30px;
                    opacity: 1;
                }
            }

            @-webkit-keyframes fadeout {
                from {
                    bottom: 30px;
                    opacity: 1;
                }

                to {
                    bottom: 0;
                    opacity: 0;
                }
            }

            @keyframes fadeout {
                from {
                    bottom: 30px;
                    opacity: 1;
                }

                to {
                    bottom: 0;
                    opacity: 0;
                }
            }
        </style>
        <div id="notification_display"></div>

        <h1><?php esc_html_e('Simple Site Rating Settings', 'simple-site-rating'); ?></h1>

        <div id="setting-error-settings_updated" class=" wsmssr-notice notice notice-success settings-error is-dismissible">
            <p><strong><?php esc_html_e('All ratings are deleted.', 'simple-site-rating'); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'simple-site-rating'); ?></span></button>
        </div>

        <form method="post" action="options.php">

            <?php
            settings_fields('wsmssr_settings');
            do_settings_sections('simple-site-rating');
            ?>
            <div class="shortcode_display">
                <div class="wp_shortcode_display">
                    <h4>
                        <span style=\"display:inline-block;padding-right:10px;width:200px;\">Shortcode:</span><code data-short="[simple-site-rating]">[simple-site-rating]</code>
                    </h4>
                    <a href="#" onClick="return copyshortcode(this);">
                        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 115.77 122.88" style="enable-background:new 0 0 115.77 122.88" xml:space="preserve">
                            <style type="text/css">
                                .st0 {
                                    fill-rule: evenodd;
                                    clip-rule: evenodd;
                                }
                            </style>
                            <g>
                                <path class="st0" d="M89.62,13.96v7.73h12.19h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02v0.02 v73.27v0.01h-0.02c-0.01,3.84-1.57,7.33-4.1,9.86c-2.51,2.5-5.98,4.06-9.82,4.07v0.02h-0.02h-61.7H40.1v-0.02 c-3.84-0.01-7.34-1.57-9.86-4.1c-2.5-2.51-4.06-5.98-4.07-9.82h-0.02v-0.02V92.51H13.96h-0.01v-0.02c-3.84-0.01-7.34-1.57-9.86-4.1 c-2.5-2.51-4.06-5.98-4.07-9.82H0v-0.02V13.96v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07V0h0.02h61.7 h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02V13.96L89.62,13.96z M79.04,21.69v-7.73v-0.02h0.02 c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v64.59v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h12.19V35.65 v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07v-0.02h0.02H79.04L79.04,21.69z M105.18,108.92V35.65v-0.02 h0.02c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v73.27v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h61.7h0.02 v0.02c0.91,0,1.75-0.39,2.37-1.01c0.61-0.61,1-1.46,1-2.37h-0.02V108.92L105.18,108.92z" />
                            </g>
                        </svg>
                    </a>
                </div>
                <div class="php_shortcode_display">
                    <h4><span style=\"display:inline-block;padding-right:10px;width:200px;\">PHP Code:</span> <code data-short="echo do_shortcode('[simple-site-rating]');">echo do_shortcode('[simple-site-rating]');</code></h4>
                    <a href="#" onClick="return copyshortcode(this);">
                        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 115.77 122.88" style="enable-background:new 0 0 115.77 122.88" xml:space="preserve">
                            <style type="text/css">
                                .st0 {
                                    fill-rule: evenodd;
                                    clip-rule: evenodd;
                                }
                            </style>
                            <g>
                                <path class="st0" d="M89.62,13.96v7.73h12.19h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02v0.02 v73.27v0.01h-0.02c-0.01,3.84-1.57,7.33-4.1,9.86c-2.51,2.5-5.98,4.06-9.82,4.07v0.02h-0.02h-61.7H40.1v-0.02 c-3.84-0.01-7.34-1.57-9.86-4.1c-2.5-2.51-4.06-5.98-4.07-9.82h-0.02v-0.02V92.51H13.96h-0.01v-0.02c-3.84-0.01-7.34-1.57-9.86-4.1 c-2.5-2.51-4.06-5.98-4.07-9.82H0v-0.02V13.96v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07V0h0.02h61.7 h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02V13.96L89.62,13.96z M79.04,21.69v-7.73v-0.02h0.02 c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v64.59v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h12.19V35.65 v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07v-0.02h0.02H79.04L79.04,21.69z M105.18,108.92V35.65v-0.02 h0.02c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v73.27v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h61.7h0.02 v0.02c0.91,0,1.75-0.39,2.37-1.01c0.61-0.61,1-1.46,1-2.37h-0.02V108.92L105.18,108.92z" />
                            </g>
                        </svg>
                    </a>
                </div>
            </div>
            <?php submit_button(); ?>
            <a class="wsmssr_delete_rating_btn"><?php esc_html_e('Delete all ratings', 'simple-site-rating'); ?></a>
        </form>

    </div>
<?php

}

/**
 * Add Settings in admin menu under setting option
 */
add_action('admin_init',  'wsmssr_register_setting');
function wsmssr_register_setting() {

    register_setting('wsmssr_settings',  'wsmssr_question',  'sanitize_text_field');

    add_settings_section('wsmssr_settings_section_id', '', '', 'simple-site-rating');

    add_settings_field(
        'wsmssr_question',
        esc_html__('Question', 'simple-site-rating'),
        'wsmssr_text_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_question',
            'class' => 'wsmssr-class',
        )
    );

    register_setting('wsmssr_settings',  'wsmssr_thank_you_heading_html');

    register_setting('wsmssr_settings',  'wsmssr_icon_color_html');

    add_settings_field(
        'wsmssr_icon_color_html',
        esc_html__('Icon Color', 'simple-site-rating'),
        'wsmssr_color_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_icon_color_html',
            'class' => 'wsmssr-class',
        )
    );

    register_setting('wsmssr_settings',  'wsmssr_icon_hover_color_html');

    add_settings_field(
        'wsmssr_icon_hover_color_html',
        esc_html__('Icon Hover Color', 'simple-site-rating'),
        'wsmssr_color_hover_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_icon_color_html',
            'class' => 'wsmssr-class',
        )
    );

    add_settings_field(
        'wsmssr_thank_you_heading_html',
        esc_html__('Thank You Content Heading', 'simple-site-rating'),
        'wsmssr_thank_heading_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_thank_you_heading_html',
            'class' => 'wsmssr-class',
        )
    );

    register_setting('wsmssr_settings',  'wsmssr_thank_you_html');

    add_settings_field(
        'wsmssr_thank_you_html',
        esc_html__('Thank You Content', 'simple-site-rating'),
        'wsmssr_textarea_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_thank_you_html',
            'class' => 'wsmssr-class',
        )
    );

    if (false == get_option('wsmssr_posttype_settings_options')) {
        add_option('wsmssr_posttype_settings_options');
    }

    register_setting('wsmssr_settings',  'wsmssr_posttype_settings_options');

    add_settings_field('wsmssr_posttype_settings_options',  esc_html__('Select The Post Types', 'simple-site-rating'),  'wsmssr_post_type_list_field',  'simple-site-rating', 'wsmssr_settings_section_id');

    // Implemented new option on settings page: "Deactivate FontAwesome"
    register_setting('wsmssr_settings',  'wsmssr_fontawesome_toggle_html');
    add_settings_field(
        'wsmssr_fontawesome_toggle_html',
        esc_html__('Deactivate FontAwesome', 'simple-site-rating'),
        'wsmssr_fontawesome_toggle_field_html',
        'simple-site-rating',
        'wsmssr_settings_section_id',
        array(
            'label_for' => 'wsmssr_fontawesome_toggle_html',
            'class' => 'wsmssr-class',
        )
    );
}

function wsmssr_fontawesome_toggle_field_html() {
    $font_toggle = get_option('wsmssr_fontawesome_toggle_html');
    $checked = '';
    if (get_option('wsmssr_fontawesome_toggle_html') == 'on') {
        $checked = 'checked';
    }

    printf(
        '<input type="checkbox" %s name="wsmssr_fontawesome_toggle_html" />',
        esc_attr($checked)
    );
}

function wsmssr_text_field_html() {

    $text = get_option('wsmssr_question');
    printf(
        '<input type="text" id="wsmssr_question" name="wsmssr_question" value="%s" class="regular-text" />',
        esc_attr($text)
    );
}

function wsmssr_post_type_list_field() {
    global $wsmssr_post_array;
    $options = get_option('wsmssr_posttype_settings_options');
    $value = array();
    $html = '';

    if (isset($options) && !empty($options)) {
        $value = $options;
    }

    foreach (get_post_types() as $post_name) {

        if (!in_array($post_name, $wsmssr_post_array)) {

            if (in_array($post_name, $value)) {
                $html .= '<input type="checkbox" name="wsmssr_posttype_settings_options[]" value="' . $post_name . '" checked /> ' . $post_name;
                $html .= '<br><br>';
            } else {
                $html .= '<input type="checkbox" name="wsmssr_posttype_settings_options[]" value="' . $post_name . '" /> ' . $post_name;
                $html .= '<br><br>';
            }
        }
    }

    echo wp_kses( $html, array( 'br' => array(), 'input' => array('type' => true,'name' => true,'value' => true,'checked' => true)) );
}


function wsmssr_color_field_html() {
    $text = get_option('wsmssr_icon_color_html');

    printf(
        '<input type="text" value="%s" name="wsmssr_icon_color_html" id="wsmssr_icon_color_html" class="my-color-field" data-default-color="#effeff" />',
        esc_attr($text)
    );
}

function wsmssr_color_hover_field_html() {
    $text = get_option('wsmssr_icon_hover_color_html');

    printf(
        '<input type="text" id="wsmssr_icon_hover_color_html" name="wsmssr_icon_hover_color_html" value="%s" >',
        esc_attr($text)
    );
}

function wsmssr_textarea_field_html() {
    $text = get_option('wsmssr_thank_you_html');

    printf(
        '<textarea class="regular-text" id="wsmssr_thank_you_html" name="wsmssr_thank_you_html" rows="4">%s</textarea><br><small>' . esc_html__('HTML tags allowed.', 'simple-site-rating') . '</small>',
        esc_attr($text)
    );
}

function wsmssr_thank_heading_field_html() {

    $text = get_option('wsmssr_thank_you_heading_html');
    printf(
        '<input type="text" class="regular-text" id="wsmssr_thank_you_heading_html" name="wsmssr_thank_you_heading_html" value="%s" />',
        esc_attr($text)
    );
}




/**
 * AJAX call back for Admin to delete all feeds
 */
add_action('wp_ajax_nopriv_wsmssr_admin_delete_feeds', 'wsmssr_admin_delete_feeds');
add_action('wp_ajax_wsmssr_admin_delete_feeds', 'wsmssr_admin_delete_feeds');
function wsmssr_admin_delete_feeds() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['nonce'] ) ) , 'wsmssr-admin-ajax-nonce' ) ) {
        die('Busted!');
    }
    global $wpdb;
    $table = $wpdb->prefix . 'wsmssr_page_rank';
    $result =  $wpdb->get_results("SELECT pageid FROM " . $table);
    $pageid = array();
    foreach ($result as $result_list) {
        $pageid[] = $result_list->pageid;
    }
    $store_ids = array_unique($pageid);
    foreach ($store_ids as $store_ids_list) {
        update_post_meta($store_ids_list, 'wsmssr_rating_avg', 0);
    }
    $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'wsmssr_page_rank');
    return true;
    die();
}

function wsmssr_sample_admin_notice__success() {
?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e('Done!', 'simple-site-rating'); ?></p>
    </div>
<?php
}

/* Dashboard Widget */
add_action('wp_dashboard_setup', 'wsmssr_dashboard_add_widgets');
function wsmssr_dashboard_add_widgets() {
    wp_add_dashboard_widget('wsmssr_dashboard_widget_simple_site_rating', esc_html__('Simple Site Rating', 'simple-site-rating'), 'wsmssr_dashboard_widget_news_handler');
}

function wsmssr_get_plural_post_type_name($pname) {
    global $wp_post_types;    
    $post_type_name = $pname;
    $labels = &$wp_post_types[$post_type_name]->labels;
    return $labels;
}

function wsmssr_dashboard_widget_news_handler() {

    global $wsmssr_post_array;

    $html = '';

    foreach (get_post_types() as $post_name) {
        
        if (!in_array($post_name, $wsmssr_post_array)) {            
            $html .= '<div id="wsmssr_dashboard_status" class="rating_box ">';
                $html .= '<div class="wsmssr_inside">';
                    $html .= '<ul class="wsmssr_list">';

                        $html .= '<li class="wsmssr-li">' . ucfirst(wsmssr_get_plural_post_type_name($post_name)->name) . '<a href="'.get_admin_url().'edit.php?post_type='.$post_name.'" class="wsmssr-li-view-more" >'.esc_html__('View More...','simple-site-rating') .'</a></li>';
                        
                        $args = array(
                            'post_type'     => $post_name,
                            'post_status'   => 'publish',
                            'posts_per_page' => 3,
                            'meta_key'      => 'wsmssr_rating_avg',
                            'orderby'       => 'meta_value_num',
                            'order'         => 'DESC'
                        );

                        $loop = new WP_Query($args);
                        $html .= '<li>';

                            $html .= '<ul class="wsmssr-inner-list">';
                                while ($loop->have_posts()) : $loop->the_post();

                                    $post_ID = get_the_ID();
                            
                                    $html .= '<li class="wsmssr-inner-li">';
                                        $html .= '<div class="inner-left-div" ><span><a href="'.get_edit_post_link($post_ID).'" class="page-link" >'. get_the_title().'</a></span></div>';
                                        $html .= '<div class="inner-right-div" >'.wsmssr_admin_display_ratings($post_ID).'</div>';
                                    $html .= '</li>';

                                endwhile;
                            $html .= '</ul>';
                    
                        $html .= '</li>';
                        
                        wp_reset_postdata();

                    $html .= '</ul>';
                $html .= '</div>';
            $html .= '</div>';

        }

    }

    echo wp_kses_post($html);

}

function wsmssr_admin_display_ratings($post_ID) {

    $very_good = wsmssr_get_very_good_feed_score($post_ID);
    $good = wsmssr_get_good_feed_score($post_ID);
    $bad = wsmssr_get_bad_feed_score($post_ID);
    $very_bad = wsmssr_get_very_bad_feed_score($post_ID);

    $html = '';
    $votes = esc_html__('votes', 'simple-site-rating');
    $html .= '<div class="wsmssr-pagerank-col">
                <div class="wsmssr-tooltip">
                    <span class="dashicons dashicons-chart-bar"></span> <strong>' . wsmssr_get_post_page_rank_avg($post_ID) . '</strong> (' . wsmssr_get_table_data_count($post_ID) . ' ' . $votes . ')      
                    <span class="wsmssr-tooltiptext">
                        <ul>
                            <li>😀 ' . $very_good . ' ' . $votes . '</li>
                            <li>🙂 ' . $good . ' ' . $votes . '</li>
                            <li>😐 ' . $bad . ' ' . $votes . '</li>
                            <li>🙁 ' . $very_bad . ' ' . $votes . '</li>
                        </ul>
                    </span>
                </div>
            </div>';
    return $html;

}

?>