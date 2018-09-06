<?php
/**
 * Plugin Name: Short Slide
 * Description: Create Slideshow just by writing shortcode short-slide
 * Version: 1.0 
 * Author: Juned Khatri 
 */

function myplugin_options_page() {
	?>

    <h2><?php esc_html_e( 'Photos', 'rest-uploader' ); ?></h2>
    
    <button id="delete_photos_button" class="wp-core-ui button button-primary">Delete photos</button>
    <button id="delete_selected_button" class="wp-core-ui button button-primary">Delete selected</button>
    <div id="multiple_select_note">
        control + click to multiple select.<br/>
        click 'Delete photos' again to toggle to sorting mode.
    </div>

    <div id="photos">

            <?php
            // <img width="30%" height="auto" imageid="%image_id%" src="%image_url%">
            ?>
            
    </div>
    Note: Drag photos to re-arrange it.<br/><br/>
    
    <div id="upload_progress">
        <div id="upload_progress_bar"></div>
    </div>
    
	<form method="post">
        <label for="ss_myfile">Upload an Image</label>
        <input type="file" id="ss_myfile" accept="image/jpeg,image/jpg,image/png,image/x-png" multiple/><br/>
        <input type="button" id="ss_submit" value="Submit"/>
    </form>
	<?php
}

function myplugin_register_options_page() {
    add_options_page('Short Slide', 'Short Slide', 'manage_options', 'short-slide-settings', 'myplugin_options_page');
}

function demo_settings_page()
{
    include_assets();

    //bootstrap
    wp_register_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-css');

    wp_register_script( 'bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');
    wp_enqueue_script('bootstrap-js');

    //jqueryui
    wp_register_style('jqueryui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('jqueryui-css');

    wp_register_script( 'jqueryui-js', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js');
    wp_enqueue_script('jqueryui-js');
}

function ss_scripts_include() {

    wp_register_script( 'ss_front_script_js', plugins_url( '/assets/js/script-front.js?v='.time(), __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script('ss_front_script_js');

	wp_register_script( 'ss_lib_slick_js', plugins_url( '/lib/slick/slick.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script('ss_lib_slick_js');
    
    wp_register_style('ss_lib_slick_css',  plugins_url( '/lib/slick/slick.css', __FILE__ ));
    wp_enqueue_style('ss_lib_slick_css');
    
    wp_register_style('ss_lib_slick_theme_css',  plugins_url( '/lib/slick/slick-theme.css', __FILE__ ));
    wp_enqueue_style('ss_lib_slick_theme_css');
}

function include_assets() {
    wp_register_script( 'ss_rest-uploader', plugins_url( '/assets/js/script.js?v='.time(), __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script('ss_rest-uploader');

    wp_register_style('ss-styles',  plugins_url( '/assets/css/style.css?v='.time(), __FILE__ ));
    wp_enqueue_style('ss-styles');

    wp_localize_script('ss_rest-uploader', 'apiVars', array(
        'nonce' => wp_create_nonce('wp_rest')
    ));
}

add_action("admin_init", "demo_settings_page");

add_action('rest_api_init', function () {
    register_rest_route( 'short-slide/v1', '/image_upload', array(
      'methods' => 'POST',
      'callback' => 'my_upload_func',
      'permission_callback' => 'rest_permissions_check'
    ) );
});

add_action('rest_api_init', function () {
    register_rest_route('short-slide/v1', '/image_delete', array(
        'methods' => 'POST',
        'callback' => 'delete_image_callback',
        'permission_callback' => 'rest_permissions_check'
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('short-slide/v1', '/get_images', array(
        'methods' => 'GET',
        'callback' => 'get_images_callback',
        'permission_callback' => 'rest_permissions_check'
    ));
});
    
add_action('rest_api_init', function () {
    register_rest_route('short-slide/v1', '/sort_list', array(
        'methods' => 'POST',
        'callback' => 'sort_list_callback',
        'permission_callback' => 'rest_permissions_check'
    ));
});

function rest_permissions_check(WP_REST_Request $request) {
    return current_user_can( 'edit_others_posts' );
}

function sort_list_callback(WP_REST_Request $request) {
    $new_list = json_decode($request['new_list']);
    $result = update_sort_into_database($new_list);
    if($result === false) {
        return array(
            'success' => false
        );
    }
    return array(
        'success' => true
    );
}

function get_images_callback(WP_REST_Request $request) {
    $photos = get_all_photos_from_database();
    if ($photos === false) {
        return array(
            'success' => false
        );
    }
    return array(
        'success' => true,
        'photos' => $photos
    );
}

function delete_image_callback(WP_REST_Request $request) {
    global $wpdb;
    $image_ids = $request['image_ids'];
    $query = 'UPDATE wp_short_slide SET is_deleted = 1, sort_order = NULL WHERE image_id IN (';
    $count_imageids = count($image_ids);
    for($i = 0; $i < $count_imageids; $i++) {
        $query .= $image_ids[$i];
        if($i != $count_imageids - 1) {
            $query .= ', ';
        }
    }
    $query .= ')';
    $result = $wpdb->query($query);
    if ($result === false) {
        return array(
            'success' => false
        );
    }
    return array(
        'success' => true
    );
}

function my_upload_func(WP_REST_Request $request) {
    $files   = $request->get_file_params();

    $uploaded_files = ss_upload_file( $files);
    for($i = 0; $i < count($uploaded_files); $i++) {
        if ( is_wp_error( $uploaded_files[$i] ) ) {
            return $uploaded_files[$i];
        }
    }

    $result = create_database_entry($uploaded_files);

    if($result === false) {
        return array(
            'success' => false
        );
    }
    return array(
        'success' => true
    );
}

function ss_upload_file( $files) {
	if ( empty( $files ) ) {
		return new WP_Error( 'rest_upload_no_data', __( 'No data supplied' ), array( 'status' => 400 ) );
    }
	$overrides = array(
		'test_form'   => false,
	);
	if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
		$overrides['action'] = 'wp_handle_mock_upload';
	}
	require_once ABSPATH . 'wp-admin/includes/admin.php';
	$result_uploads = my_multiple_wp_handle_upload( $files['files'], $overrides );
	return $result_uploads;
}

function my_multiple_wp_handle_upload($files, $overrides) {
    $result_uploads = array();
    foreach ($files['name'] as $key => $value) {
        if ($files['name'][$key]) {
            $file = array (
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            );
            $result = wp_handle_upload($file, $overrides);
            if ( isset( $result['error'] ) ) {
                return new WP_Error( 'rest_upload_unknown_error', $result['error'], array( 'status' => 500 ) );
            }
            $result_uploads[] = $result;
        }
    }
    return $result_uploads;
}

function create_short_slide_table() {
    global $wpdb, $table_prefix;
    $tblname = 'short_slide';
    $wp_track_table = $table_prefix . "$tblname";
    #Check to see if the table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
    {
        $sql = "CREATE TABLE `". $wp_track_table . "` ( ";
        $sql .= "  `image_id`  int(11)   NOT NULL PRIMARY KEY auto_increment, ";
        $sql .= "  `image_url`  varchar(255)   NOT NULL, ";
        $sql .= "  `sort_order`  integer, ";
        $sql .= "  `is_deleted`  tinyint(1)   NOT NULL DEFAULT 0";
        $sql .= ");";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

function create_database_entry($files) {
    global $wpdb;
    $count = (int) $wpdb->get_var("SELECT MAX(sort_order) FROM wp_short_slide WHERE is_deleted = 0");
    $query = 'INSERT INTO wp_short_slide (image_url, sort_order) VALUES ';
    $count_arr = count($files);
    for($i = 0; $i < $count_arr; $i++) {
        $query .= "('" . $files[$i]['url'] . "', " . ($count + $i + 1) . ")";
        if ($i != $count_arr - 1) {
            $query .= ', ';
        }
    }
    $result = $wpdb->query($query);
    return $result;
}

function get_all_photos_from_database() {
    global $wpdb;
    $query="SELECT image_id, image_url FROM wp_short_slide WHERE is_deleted = 0 ORDER BY sort_order";
    return $wpdb->get_results($query);
}

function update_sort_into_database($new_sort) {
    global $wpdb;
    $query = 'UPDATE wp_short_slide SET sort_order = (CASE ';
    for ($i = 0; $i < count($new_sort); $i++) {
        $query .= "WHEN image_id = " . $new_sort[$i] . " THEN " . ($i+1). " ";
    }
    $query .= "ELSE NULL END);";
    $result = $wpdb->query($query);

    return $result;
}

function show_slideshow () {
    ?>
    <div class="slideshow">
        <?php
            $photos = get_all_photos_from_database();
            if($photos === false) {
                echo "error";
                return;
            }
            foreach($photos as $photo) {
                ?>
                <div>
                    <img src="<?=$photo->image_url?>">
                </div>
                <?php
            }
        ?>
    </div>

    <?php
}

function enqueue_shortcode_plugin_script($plugin_array) {
    $plugin_array["shortcode_slideshow"] =  plugins_url( '/assets/js/shortcode-slideshow-plugin.js?v='.time(), __FILE__ );
    return $plugin_array;
}

function register_button_editor($buttons)
{
    array_push($buttons, '|', "shortcode_slideshow");
    return $buttons;
}

add_shortcode('myslideshow', 'show_slideshow');
add_action('admin_menu', 'myplugin_register_options_page');
add_action('wp_enqueue_scripts', 'ss_scripts_include');
add_filter("mce_external_plugins", "enqueue_shortcode_plugin_script");
add_filter("mce_buttons", "register_button_editor");

register_activation_hook( __FILE__, 'create_short_slide_table' );
?>