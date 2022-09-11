<?php
/**
 * Plugin Name: Crawl OPHIM.CC
 * Description: Crawl + Update Dữ liệu từ OPhim.CC (WP - Halimthemes - 5.5.4)
 * Version: 1.1
 * Author: Phantom0803@Ophim.Cc
 * Author URI: https://ophim.cc/
 */
set_time_limit(0);
define('CRAWL_OPHIM_URL', plugin_dir_url(__FILE__));
define('CRAWL_OPHIM_PATH', plugin_dir_path(__FILE__));

function crawl_tools_script()
{
	global $pagenow;
	if ('admin.php' == $pagenow && ($_GET['page'] == 'crawl-ophim-tools' || $_GET['page'] == 'crawl-tools')) {
		wp_enqueue_script('crawl_tools_js', CRAWL_OPHIM_URL . 'assets/js/main.js');
		wp_enqueue_style('crawl_tools_css', CRAWL_OPHIM_URL . 'assets/css/styles.css');
	} else {
		return;
	}
}
add_action('in_admin_header', 'crawl_tools_script');

// Custom metabox in post
function ophim_meta_box() {
	add_meta_box( 'ophim-custom-edit', 'OPhim Custom Edit', 'ophim_custom_meta_box', 'post', 'advanced', 'high' );
}
add_action( 'add_meta_boxes', 'ophim_meta_box' );

function ophim_custom_meta_box($post, $metabox) {
	$_halim_metabox_options = get_post_meta($post->ID, '_halim_metabox_options', true);
	wp_nonce_field(basename(__FILE__), 'post_media_metabox');
?>
  <div class="inside">
    <label for="fetch_ophim_id">OPhim ID: </label><input styles="width: 100%" name="fetch_ophim_id" type="text" id="fetch_ophim_id" value="<?php echo $_halim_metabox_options["fetch_ophim_id"];?>">
    <label for="fetch_ophim_update_time">Thời gian cập nhật: </label><input styles="width: 100%" name="fetch_ophim_update_time" type="text" id="fetch_ophim_update_time" value="<?php echo $_halim_metabox_options["fetch_ophim_update_time"];?>">
	</div>
<?php
}

function ophim_custom_save_metabox($post_id, $post)
{
  if (!wp_verify_nonce($_POST["post_media_metabox"], basename(__FILE__))) {
		return $post_id;
	}
	if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
		return $post_id;
	}
	if ('post' != $post->post_type) {
		return $post_id;
	}
  $fetch_ophim_id = (isset($_POST["fetch_ophim_id"])) ? sanitize_text_field($_POST["fetch_ophim_id"]) : '';
  $fetch_ophim_update_time = (isset($_POST["fetch_ophim_update_time"])) ? sanitize_text_field($_POST["fetch_ophim_update_time"]) : '';

	$_halim_metabox_options = get_post_meta($post_id, '_halim_metabox_options', true);
	$_halim_metabox_options["fetch_ophim_id"] = $fetch_ophim_id;
	$_halim_metabox_options["fetch_ophim_update_time"] = $fetch_ophim_update_time;
	
	update_post_meta($post_id, '_halim_metabox_options', $_halim_metabox_options);
}
add_action('save_post', 'ophim_custom_save_metabox', 20, 2);

include_once CRAWL_OPHIM_PATH . 'functions.php';
include_once CRAWL_OPHIM_PATH . 'crawl_movies.php';
