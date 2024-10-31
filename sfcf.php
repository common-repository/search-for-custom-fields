<?php
/*
Plugin Name: Search For Custom Fields
Plugin URI: https://wordpress.org/plugins/search-for-custom-fields
Description: Create your own fields for your posts / pages and propose a search based on these fields to your visitors.
Version: 1.2
Author: Stéphane Lion
Author URI: https://aaclesoft.fr
Text Domain: searchforcustomfields
Domain Path: /languages
*/

include_once plugin_dir_path( __FILE__ ).'/sfcf_widget.php';
function sfcf_install(){	// function called when installing the plugin
	if (!isset($wpdb)) $wpdb = $GLOBALS['wpdb'];
    global $wpdb;
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfcf_fields (id INT AUTO_INCREMENT PRIMARY KEY, keyy VARCHAR(%d) NOT NULL, valuee TEXT, field_type VARCHAR(3));", "255"));
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfcf_options (id INT AUTO_INCREMENT PRIMARY KEY, keyy VARCHAR(%d) NOT NULL, valuee TEXT);", "255"));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_options (keyy, valuee) VALUES (%s, %s)", 'display_fields_in_posts', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_options (keyy, valuee) VALUES (%s, %s)", 'display_fields_in_pages', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_options (keyy, valuee) VALUES (%s, %s)", 'display_empty_fields', ''));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_options (keyy, valuee) VALUES (%s, %s)", 'border_color', '#DDDDDD'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_options (keyy, valuee) VALUES (%s, %s)", 'border_size', '0'));
}

function sfcf_uninstall(){ // Function called when disabling the plugin
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sfcf_options WHERE %s=%s", "1", "1"));
}

function sfcf_delete_fields(){ // Function called when uninstalling the plugin
	global $wpdb;
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sfcf_fields WHERE %s=%s;", "1", "1"));
}

function sfcf_register_sfcf_widget(){ // Function called when initializing the widget
	register_widget('sfcf_widget');
}

function sfcf_add_admin_menu(){	// Function called when initializing the plugin menu
    $hook = add_menu_page('Search For Custom Fields', 'Search For Custom Fields', 'manage_options', 'search-for-custom-fields', 'sfcf_menu_html');
	add_action('load-'.$hook, 'sfcf_process_action');
}

function sfcf_load_theme_textdomain() {
    load_plugin_textdomain( 'searchforcustomfields', false, dirname(plugin_basename(__FILE__)) . '/languages/' );
}
add_action( 'after_setup_theme', 'sfcf_load_theme_textdomain' );

function sfcf_process_action(){	// Function called when the administrator edits the plugin options
    if (isset($_POST['add_field'])) {	// Adding a new field chosen by the administrator
		global $wpdb;
		$new_field = sanitize_text_field($_POST['new_field']);
        $new_field = str_replace(" ","_", $new_field);
        $field_type = sanitize_text_field($_POST['field_type']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields WHERE keyy = %s", $new_field));
        if (is_null($row)) {
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}sfcf_fields (keyy, field_type) VALUES (%s, %s)", ucfirst($new_field), $field_type));
        }
    }

	if ((isset($_POST['delete_field'])) && (isset($_POST['fields']))) {	// Deleting fields selected by the administrator
		global $wpdb;
        $fields = $_POST['fields'];
		if (is_array($fields)){
			$inQuery = implode(',', array_fill(0, count($fields), '%d'));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sfcf_fields WHERE id IN ($inQuery)", $fields));
		}
    }

	if (isset($_POST['save_options'])) {	// Saving changes made by the administrator in the options
		global $wpdb;
        if (isset($_POST['display_fields_in_posts'])){$display_fields_in_posts = substr(sanitize_text_field($_POST['display_fields_in_posts'][0]),0,3);}else{$display_fields_in_posts = "";}
        if (isset($_POST['display_fields_in_pages'])){$display_fields_in_pages = substr(sanitize_text_field($_POST['display_fields_in_pages'][0]),0,3);}else{$display_fields_in_pages = "";}
        if (isset($_POST['display_empty_fields'])){$display_empty_fields = substr(sanitize_text_field($_POST['display_empty_fields'][0]),0,3);}else{$display_empty_fields = "";}
        $border_color = substr(sanitize_text_field($_POST['border_color']),0,7);
        $border_size = intval(sanitize_text_field($_POST['border_size']));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sfcf_options SET valuee = %s WHERE keyy = 'display_fields_in_posts'", $display_fields_in_posts));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sfcf_options SET valuee = %s WHERE keyy = 'display_fields_in_pages'", $display_fields_in_pages));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sfcf_options SET valuee = %s WHERE keyy = 'display_empty_fields'", $display_empty_fields));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sfcf_options SET valuee = %s WHERE keyy = 'border_color'", $border_color));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sfcf_options SET valuee = %s WHERE keyy = 'border_size'", $border_size));
    }

}

$options = array();
function sfcf_getOptions(){	// Function to retrieve option values
	global $options, $wpdb;
	if (sizeof($options) == 0){
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_options ORDER BY %s", "keyy")) ;
		foreach ($resultats as $cv) {
			$options[$cv->keyy] = $cv->valuee;
		}
	}
	return $options;
}

function sfcf_menu_html(){	// Display of the main plugin management page
	global $wpdb;
	echo '<h1>'.get_admin_page_title().'</h1>';
	echo '<br><div style="max-width:500px;display:inline-block;vertical-align:top;min-width:300px;padding: 0 15px;margin-bottom: 15px;margin-left: 15px;background-color: white;">
	<h2>'. __('Getting Started', 'searchforcustomfields') .'</h2>
	- '. __('Create the fields on this page', 'searchforcustomfields') .'<br>
	- '. __('(optional) Activate the widget', 'searchforcustomfields') .'<br>
	- '. __('Fill in the fields in your posts / pages (Please add manually for exisiting contents)', 'searchforcustomfields') .'<br>
	- '. __('Insert [sfcf_shortcode] where you want to display the fields', 'searchforcustomfields') .'<br>
	- '. __('Insert [sfcf_search_shortcode] where you want to include the search form. To choose the size of the form you can use the size parameter as in these examples: [sfcf_search_shortcode size="50%"] or [sfcf_search_shortcode size="250px"]', 'searchforcustomfields') .'<br>';
	echo '<br><i><font color=red>'. __('IMPORTANT : To view the fields when creating your posts or pages, consider displaying "custom fields" in "Screen Options" (At the top of the page when you write your post or page)', 'searchforcustomfields') .'</font></i><br><br></div>';
	// ------------------ Options editing form ------------------ //
	$options = sfcf_getOptions();
	echo '<div style="display:inline-block;vertical-align:top;padding: 0 15px;margin-bottom: 15px;margin-left: 15px;background-color: white;"><h2>'. __('Options', 'searchforcustomfields') .'</h2>
	<form method="post" action="">
		<input type="hidden" name="save_options" value="1"/>
		<table>
		<tr><td colspan="2"><input type="checkbox" name="display_fields_in_posts[]"';
	if ($options['display_fields_in_posts']){echo "checked";} 
	echo '> '. __('Show fields in posts when calling shortcode [sfcf_shortcode]', 'searchforcustomfields') .' </td></tr>
		<tr><td colspan="2"><input type="checkbox" name="display_fields_in_pages[]"';
	if ($options['display_fields_in_pages']){echo "checked";} 
	echo '> '. __('Show fields in pages when calling shortcode [sfcf_shortcode]', 'searchforcustomfields') .'</td></tr>
		<tr><td><input type="checkbox" name="display_empty_fields[]"';
	if ($options['display_empty_fields']){echo "checked";}
	echo '> '. __('Show empty fields', 'searchforcustomfields') .' </td><td> </td></tr>
		<tr><td>'. __('Border color', 'searchforcustomfields') .' </td><td> <input type="text" name="border_color" value="'.$options['border_color'].'"></td></tr>
		<tr><td>'. __('Border size', 'searchforcustomfields') .' </td><td> <input type="text" name="border_size" value="'.$options['border_size'].'"></td></tr>
		</table>';
	submit_button(__('Save', 'searchforcustomfields'));
    echo '</form></div>';
	
	// ------------------ Form to add fields ------------------ //

	echo '<hr><div style="width:40%;display:inline-block;vertical-align:top;min-width:300px;padding-left: 15px;margin-bottom: 15px;margin-left: 15px;background-color: white;"><h2>' . __('Create a new custom fields', 'searchforcustomfields') . '</h2>
		<form method="post" action="">
		<input type="hidden" name="add_field" value="1"/>
		<table>
		<tr><td><label><b> '. __('Name:', 'searchforcustomfields') .'</b></label></td>
		<td><input type="text" name="new_field" value=""/></td></tr>
		<tr><td><label><b>'. __('Type:', 'searchforcustomfields') .' </b></label></td>
		<td><select name="field_type"><option value="TEX">'. __('Text', 'searchforcustomfields') .'</option><option value="NUM">'. __('Number', 'searchforcustomfields') .'</option></select></td></tr>
		</table>
		<br><b>'. __('Memo:', 'searchforcustomfields') . '</b> '. __('Choose the "Number" type to allow your visitors to search >=, <= ou = to a number.', 'searchforcustomfields') .' <br>
		<i> '. __('For example: To display all the posts whose price field is "<=" to value "50$".', 'searchforcustomfields') .'
		<br>'. __('The comparison does not work if you place letters in front of the digits, for example "$50".', 'searchforcustomfields') .'</i>
		';
	submit_button(__('Add', 'searchforcustomfields') );
	echo '</form></div>';
	// ------------------ Display of fields ------------------ //
	echo '<div style="display:inline-block;vertical-align:top;padding: 0 15px;margin-bottom: 15px;margin-left: 15px;background-color: white;"><p><h2>' . __('List of fields', 'searchforcustomfields') . '</h2><form method="post" action=""><input type="hidden" name="delete_field" value="1"/>';
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy"));
	$type = array("N" => "Number", "T" => "Text");
	foreach ($resultats as $cv) {
		echo "<div style='display:block;padding:5px;margin:5px'><input type='checkbox' title='Delete' name='fields[]' value='".$cv->id."'> ".str_replace('\\','',str_replace("_", " ", $cv->keyy))." (".$type[substr($cv->field_type,0,1)].")</div>" ;
	}
	echo '</p>';
	submit_button(__('Delete the selected fields', 'searchforcustomfields'));
	echo '</form></div>';
}

function sfcf_insert_post($post_id) {	// Automatically add custom fields when creating a post
	if ((get_post_type($post_id) == 'post') || (get_post_type($post_id) == 'page')) {
		global $wpdb;
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy")) ;
		foreach ($resultats as $cv) {
			add_post_meta($post_id, $cv->keyy, '', true);
		}
	}
	return true;
}

function sfcf_insert_post2(){	// Automatically add custom fields when editing a post (For posts created before installing the plugin)
	sfcf_insert_post(get_the_ID());
}

function sfcf_shortcode($atts){	// Function called for the shortcode [sfcf_shortcode] and displaying the fields associated with the post or page
	$post_type = get_post_type();
	global $wpdb;
	$options = sfcf_getOptions();
	$result = "";
	if ((($post_type == "post") && ($options["display_fields_in_posts"])) || (($post_type == "page") && ($options["display_fields_in_pages"]))){
		$custom_fields = get_post_custom();
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy")) ;
		foreach ($resultats as $cv){
			if ( isset($custom_fields[$cv->keyy][0]) ) {	
				if (($options['display_empty_fields'] == true) || ($custom_fields[$cv->keyy][0] != ""))
				$result .= '<p><b>'.str_replace("_", " ", $cv->keyy).':</b> '.$custom_fields[$cv->keyy][0].'</p>';
			}
		}
	}
	return $result;
}

function sfcf_search_shortcode($atts){	// Function called for the shortcode [sfcf_search_shortcode] and displaying the search form
	$size = "100%";
	if (isset($atts['size'])){
		if ((strlen($atts['size']) < 6) && ((strpos(strtolower($atts['size']),"px") !== false) || (strpos($atts['size'],"%") !== false)))
		$size = $atts['size'];
	}
	$echo = '<form class="sfcf_form" action="" method="post" style="width:'.$size.'"><input type="hidden" name="search_sfcf" value="1">';	
	global $wpdb;
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy")) ;
	foreach ($resultats as $cv) {
		$keyy = str_replace('\\','',$cv->keyy);
		$vals = array_unique(sfcf_get_meta_values($keyy));
		if (sizeof($vals) > 0){
			$width = "100%";
			if ($cv->field_type == "NUM"){
				$width = "80%";
			}
			$echo .= '<label style="display:block" for="'."sfcf_".$cv->id.'">'.str_replace("_"," ",$keyy).' :</label>';
			$echo .= '<select id="'."sfcf_".$cv->id.'" name="'."sfcf_".$cv->id.'" style="width:'.$width.';margin-top:0px;margin-bottom: 10px"><option value="(sfcf_all)">'. __('All', 'searchforcustomfields') .'</option>';
			if ($cv->field_type == "NUM"){
				sort($vals, SORT_NUMERIC);
			}else{
				sort($vals);
			}
			$options = sfcf_getOptions();
			foreach ( $vals as $val ){
				$selected = "";
				if ((isset($_POST["sfcf_".$cv->id]))&&(str_replace('\\','',$_POST["sfcf_".$cv->id])) == $val){
					$selected = "selected";
				}
				$val2 = $val;
				if (($val2 == "") && ($options['display_empty_fields'])){
					$val2 = "(empty)";
				}
				if (($val2 != "")){
					$echo .= "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";
				}
			}
			$echo .= '</select>';
			if ($cv->field_type == "NUM"){
				$selected_upp = "";
				$selected_low = "";
				if ((isset($_POST["sfcf_".$cv->id."_compare"]))&&($_POST["sfcf_".$cv->id."_compare"] == "UPP")){
					$selected_upp = "selected";
				}
				if ((isset($_POST["sfcf_".$cv->id."_compare"]))&&($_POST["sfcf_".$cv->id."_compare"] == "LOW")){
					$selected_low = "selected";
				}
				$echo .= '<select name="'."sfcf_".$cv->id.'_compare" style="float:left;margin-top:0px;width:20%"><option value="EQU">=</option><option value="UPP" '.$selected_upp.'>>=</option><option value="LOW" '.$selected_low.'><=</option></select>';
			}
			$echo .= '<br style="clear:both">';
		}
	}
	$sortby = "dateDesc"; if ((isset($_POST['sfcf_orderby007']))&&($_POST['sfcf_orderby007'] != "")){$sortby = sanitize_text_field($_POST['sfcf_orderby007']);}
	$echo .= '<label for="sfcf_orderby007">'. __('Sort by :', 'searchforcustomfields') .'</label>
		<select name="sfcf_orderby007" style="width:100%;margin-top:0px;margin-bottom: 10px">
			<option value="dateDesc" '.(($sortby == "dateDesc")? 'selected':'').'>'. __('New to Old', 'searchforcustomfields') .'</option>
			<option value="dateAsc" '.(($sortby == "dateAsc")? 'selected':'').'>'. __('Old to New', 'searchforcustomfields') .'</option>
			<option value="alpha" '.(($sortby == "alpha")? 'selected':'').'>'. __('Alphabetical order (A..Z)', 'searchforcustomfields') .'</option>
			<option value="notAlpha" '.(($sortby == "notAlpha")? 'selected':'').'>'. __('Inverted alphabetical order (Z..A)', 'searchforcustomfields') .'</option>
		</select><br style="clear:both">';
		$rpp = "10"; if ((isset($_POST['sfcf_rpp']))&&($_POST['sfcf_rpp'] != "")){$rpp = intval(sanitize_text_field($_POST['sfcf_rpp']));}
		$echo .= '<label style="display:block" for="sfcf_rpp">'. __('Results by page :', 'searchforcustomfields') .'</label>
			<select name="sfcf_rpp" style="width:100%;margin-top:0px;margin-bottom: 10px">
				<option value="5" '.(($rpp == "5")? 'selected':'').'>5</option>
				<option value="10" '.(($rpp == "10")? 'selected':'').'>10</option>
				<option value="20" '.(($rpp == "20")? 'selected':'').'>20</option>
				<option value="30" '.(($rpp == "30")? 'selected':'').'>30</option>
				<option value="40" '.(($rpp == "40")? 'selected':'').'>40</option>
				<option value="50" '.(($rpp == "50")? 'selected':'').'>50</option>
			</select>
			<br>';
		$echo .= '<br style="clear:both"><input type="submit" value="'. __('Search', 'searchforcustomfields') .'" style="float:right;margin-bottom:20px"/><br style="clear:both"></form>';
	return $echo;
}

function sfcf_results_before_content() {	// Function called to display the results of the search made by a user of the website
	$custom_content = "";
	if ((isset($_POST['search_sfcf']))){
		// ------------------ Query initialisation ------------------ //
		$pageaff = 1; if ((isset($_POST['pageaff']))&&($_POST['pageaff'] != "")){$pageaff = intval(sanitize_text_field($_POST['pageaff']));}
		$posts_per_page = get_option('posts_per_page');
		if ((isset($_POST['sfcf_rpp']))&&($_POST['sfcf_rpp'] != "")){$posts_per_page = intval(sanitize_text_field($_POST['sfcf_rpp']));}
		if (($posts_per_page > 50) || ($posts_per_page == -1)){$posts_per_page = 50;}
		$sortby = "dateDesc"; if ((isset($_POST['sfcf_orderby007']))&&($_POST['sfcf_orderby007'] != "")){$sortby = sanitize_text_field($_POST['sfcf_orderby007']);}
		if($sortby == "dateDesc"){$orderby = "date"; $order = "DESC";}
		if($sortby == "dateAsc"){$orderby = "date"; $order = "ASC";}
		if($sortby == "alpha"){$orderby = "title"; $order = "ASC";}
		if($sortby == "notAlpha"){$orderby = "title"; $order = "DESC";}
		$myargs = array('orderby' => $orderby,
			'order' => $order,
			'posts_per_page' => $posts_per_page,
			'offset' => ($pageaff-1) * $posts_per_page,
			'meta_query' => array(
				'relation'		=> 'AND'
			),
			'post_type' => array( 'post', 'page' )
		);
		global $wpdb;
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy")) ;
		$debug = "";
		$filter_nb = 0;
		$keyfilter = "";
		foreach ($resultats as $cv) {
			if (isset($_POST["sfcf_".$cv->id])){
				$keyfilter = str_replace('\\','',$cv->keyy);
				$sfcf_cv_id = sanitize_text_field($_POST["sfcf_".$cv->id]);
				if ($sfcf_cv_id != ""){
					if ($sfcf_cv_id != "(sfcf_all)"){
						$type = "CHAR";
						if ($cv->field_type == "NUM"){
							$type = "NUMERIC";
						}
						$compare = '=';
						if (isset($_POST["sfcf_".$cv->id."_compare"])){
							$sfcf_cv_id_compare = sanitize_text_field($_POST["sfcf_".$cv->id."_compare"]);
							if ($sfcf_cv_id_compare == "UPP"){
								$compare = ">=";
							}
							if ($sfcf_cv_id_compare == "LOW"){
								$compare = "<=";
							}
						}
						$myargs["meta_query"][] = array('key' => $keyfilter, 'value' => str_replace('\\','',$sfcf_cv_id),'compare' => $compare, 'type' => $type);
						$filter_nb++;
					}
				}
			}
		}
		if ($filter_nb == 0){
			unset($myargs["meta_query"]);
		}
		$mythe_query = get_posts( $myargs );
		query_posts( '$myargs' );
		
		// ------------------ Display results ------------------ //
		$result = 0;
		$options = sfcf_getOptions();
		$list = $debug.'<div style="margin: 20px 0 20px 0;border: '.$options["border_size"].'px solid '.$options["border_color"].';padding:10px"><div class="content-headline"><h2 class="entry-headline"><span class="entry-headline-text">'. __('Search results', 'searchforcustomfields') .'</span></h2></div>';
		foreach ( $mythe_query as $post ) : setup_postdata( $post );
			$result++;
			// $new_content = strip_tags(strip_shortcodes(get_the_content()));
			// $new_content = substr($new_content,0,200);
			$new_content = get_the_excerpt();
			if (strlen($new_content) > 0){$new_content = ' - <span>' . $new_content . '</span>';}
			$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'medium');
			$height = "";
			if ($options["border_size"] == 0){
				$height = "height:0px;";
			}
			if (($feat_image) && (sizeof($feat_image) > 0)){
				$feat_image = '<img src="'.$feat_image[0].'">';
			}else{
				$feat_image = '';
			}
			$list .= '	<hr style="width:100%;'.$height.'border: '.($options["border_size"]-1).'px solid '.$options["border_color"].';clear:both;margin: 10px auto;background-color: '.$options["border_color"].'"><article class="grid-entry" style="float: left;margin: 0 20px 20px 0;width: 100%;"><a style="float:left;margin-right:10px;" href="' . get_permalink($post->ID) . '">'.$feat_image.'</a><span><a href="'. get_permalink($post->ID) . '"><b>' . ucfirst($post->post_title) . '</b> - '.get_the_date('d/m/Y',$post->ID). $new_content.'</a></span></article>';
		endforeach; 
		wp_reset_postdata();
		if ($result == 0){
			$list .= __('No results for this search, please try again with fewer criteria.', 'searchforcustomfields');
		}
		$list .= '<br style="clear:both;"></div>';
		// ------------------ Display "Previous page" if page 2 or above is displayed ------------------ //
		if ($pageaff > 1){
			$list .= '<div style="float:left"><form action="" method="post">';
			$list .= '<input type="hidden" name="search_sfcf" value="1"><input type="hidden" name="pageaff" value="'.($pageaff - 1).'">';
			$list .= '<input type="hidden" name="sfcf_orderby007" value="'.$sortby.'">';
			$list .= '<input type="hidden" name="sfcf_rpp" value="'.$posts_per_page.'">';
			foreach ($resultats as $cv) {
				if (isset($_POST["sfcf_".$cv->id])){
					$sfcf_cv_id = sanitize_text_field($_POST["sfcf_".$cv->id]);
					if ($sfcf_cv_id != ""){
						if ($sfcf_cv_id != "(sfcf_tous)"){
							$list .= '<input type="hidden" name="sfcf_'.$cv->id.'" value="'.esc_attr($sfcf_cv_id).'">';
						}
					}
					if (isset($_POST["sfcf_".$cv->id."_compare"])){
						$sfcf_cv_id_compare = sanitize_text_field($_POST["sfcf_".$cv->id."_compare"]);
						$list .= '<input type="hidden" name="sfcf_'.$cv->id.'_compare" value="'.esc_attr($sfcf_cv_id_compare).'">';
					}
				}
			}
			$list .= '<input type="submit" value="'. __('Previous page', 'searchforcustomfields') .'"></form></div>';
		}
		
		// ------------------ Display "Next page" if there are pages left to display ------------------ //
		if ($result == $posts_per_page){
			$list .= '<div style="float:right"><form action="" method="post">';
			$list .= '<input type="hidden" name="search_sfcf" value="1"><input type="hidden" name="pageaff" value="'.($pageaff + 1).'">';
			$list .= '<input type="hidden" name="sfcf_orderby007" value="'.$sortby.'">';
			$list .= '<input type="hidden" name="sfcf_rpp" value="'.$posts_per_page.'">';
			foreach ($resultats as $cv) {
				if (isset($_POST["sfcf_".$cv->id])){
					$sfcf_cv_id = sanitize_text_field($_POST["sfcf_".$cv->id]);
					if ($sfcf_cv_id != ""){
						if ($sfcf_cv_id != "(sfcf_tous)"){
							$list .= '<input type="hidden" name="sfcf_'.$cv->id.'" value="'.esc_attr($sfcf_cv_id).'">';
						}
					}
					if (isset($_POST["sfcf_".$cv->id."_compare"])){
						$sfcf_cv_id_compare = sanitize_text_field($_POST["sfcf_".$cv->id."_compare"]);
						$list .= '<input type="hidden" name="sfcf_'.$cv->id.'_compare" value="'.esc_attr($sfcf_cv_id_compare).'">';
					}
				}
			}
			$list .= '<input type="submit" value="'. __('Next page', 'searchforcustomfields') .'"></form></div>';
		}

		$list .= '<br style="clear:both;">';

		// ------------------ Adding the content of the results to the page ------------------
		$custom_content .= $list;
		$custom_content = nl2br($custom_content);
		$custom_content = str_replace("\r","",$custom_content);
		$custom_content = str_replace("\n","",$custom_content);
		$custom_content = str_replace("'","&#39;",$custom_content);
		wp_reset_query();
		unset($_POST["search_sfcf"]);
		echo "<script>
		var div = document.createElement('div');
		div.innerHTML = '$custom_content';
		//var child = document.getElementsByClassName('page-header')[0];
		var child = document.getElementById('main');
		if (!child){child = document.getElementById('content');}
		if (!child){child = document.getElementsByTagName('main')[0];}
		child.parentNode.insertBefore(div, child);
		</script>";
	}
}

function sfcf_get_meta_values( $key = '', $status = 'publish' ) {	// Function that returns all possible values ​​for a field
	global $wpdb;
	if( empty( $key ) )
		return;

	$r = $wpdb->get_col( $wpdb->prepare( "
		SELECT pm.meta_value FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '%s' 
		AND p.post_status = '%s' 
		AND (p.post_type = 'post' OR p.post_type = 'page')
	", $key, $status ) );
	return $r;
}

// Link the functions of the plugin with those of wordpress 

add_action('wp_footer','sfcf_results_before_content');
add_action('wp_insert_post', 'sfcf_insert_post');
add_action('edit_form_after_editor', 'sfcf_insert_post2');
register_activation_hook(__FILE__, 'sfcf_install');
register_deactivation_hook(__FILE__, 'sfcf_uninstall');
register_uninstall_hook(__FILE__, 'sfcf_delete_fields');
add_action('widgets_init', 'sfcf_register_sfcf_widget');
add_action('admin_menu', 'sfcf_add_admin_menu');
add_shortcode('sfcf_shortcode', 'sfcf_shortcode');
add_shortcode('sfcf_search_shortcode', 'sfcf_search_shortcode');

?>