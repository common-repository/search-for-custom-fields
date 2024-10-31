<?php
class sfcf_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('sfcf_widget', 'Search For Custom Fields', array('description' => 'Search through custom fields'));
    }
    
    public function widget($args, $instance)	// Function called for the widget and displaying the search form to user
    {
        echo $args['before_widget'];
		echo $args['before_title'];
		echo apply_filters('widget_title', $instance['title']);
		echo $args['after_title'];
		echo '<form class="sfcf_form" action="" method="post"><input type="hidden" name="search_sfcf" value="1">';
		global $wpdb;
		
		$options = array();
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_options ORDER BY %s", "keyy")) ;
		foreach ($resultats as $cv) {
			$options[$cv->keyy] = $cv->valuee;
		}
		
		
		
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sfcf_fields ORDER BY %s", "keyy")) ;
		foreach ($resultats as $cv) {
			$keyy = str_replace('\\','',$cv->keyy);
			$vals = array_unique($this->sfcf_get_meta_values($keyy));
			if (sizeof($vals) > 0){
				$width = "100%";
				if ($cv->field_type == "NUM"){
					$width = "80%";
				}
				echo '<label style="display:block" for="'."sfcf_".$cv->id.'">'.str_replace("_"," ",$keyy).' :</label>';
				echo '<select id="'."sfcf_".$cv->id.'" name="'."sfcf_".$cv->id.'" style="float:right;width:'.$width.';margin-top:0px;margin-bottom: 10px"><option value="(sfcf_all)">'. __('All', 'searchforcustomfields') .'</option>';
				
				if ($cv->field_type == "NUM"){
					sort($vals, SORT_NUMERIC);
				}else{
					sort($vals);
				}
				
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
						echo "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";
					}
				}
				echo '</select>';
				if ($cv->field_type == "NUM"){
					$selected_upp = "";
					$selected_low = "";
					if ((isset($_POST["sfcf_".$cv->id."_compare"]))&&($_POST["sfcf_".$cv->id."_compare"] == "UPP")){
						$selected_upp = "selected";
					}
					if ((isset($_POST["sfcf_".$cv->id."_compare"]))&&($_POST["sfcf_".$cv->id."_compare"] == "LOW")){
						$selected_low = "selected";
					}
					echo '<select name="'."sfcf_".$cv->id.'_compare" style="float:right;margin-top:0px;width:20%"><option value="EQU">=</option><option value="UPP" '.$selected_upp.'>>=</option><option value="LOW" '.$selected_low.'><=</option></select>';
				}
				echo '<br style="clear:both">';
			}
		}
		
		$sortby = "dateDesc"; if ((isset($_POST['sfcf_orderby007']))&&($_POST['sfcf_orderby007'] != "")){$sortby = sanitize_text_field($_POST['sfcf_orderby007']);}
		
		echo '<label style="display:block" for="sfcf_orderby007">'. __('Sort by :', 'searchforcustomfields') .'</label>
			<select name="sfcf_orderby007" style="width:100%;margin-top:0px;margin-bottom: 10px">
				<option value="dateDesc" '.(($sortby == "dateDesc")? 'selected':'').'>'. __('New to Old', 'searchforcustomfields') .'</option>
				<option value="dateAsc" '.(($sortby == "dateAsc")? 'selected':'').'>'. __('Old to New', 'searchforcustomfields') .'</option>
				<option value="alpha" '.(($sortby == "alpha")? 'selected':'').'>'. __('Alphabetical order (A..Z)', 'searchforcustomfields') .'</option>
				<option value="notAlpha" '.(($sortby == "notAlpha")? 'selected':'').'>'. __('Inverted alphabetical order (Z..A)', 'searchforcustomfields') .'</option>
			<select>
			<br>';
			
		$rpp = "10"; if ((isset($_POST['sfcf_rpp']))&&($_POST['sfcf_rpp'] != "")){$rpp = intval(sanitize_text_field($_POST['sfcf_rpp']));}
		
		echo '<label style="display:block" for="sfcf_rpp">'. __('Results by page :', 'searchforcustomfields') .'</label>
			<select name="sfcf_rpp" style="width:100%;margin-top:0px;margin-bottom: 10px">
				<option value="5" '.(($rpp == "5")? 'selected':'').'>5</option>
				<option value="10" '.(($rpp == "10")? 'selected':'').'>10</option>
				<option value="20" '.(($rpp == "20")? 'selected':'').'>20</option>
				<option value="30" '.(($rpp == "30")? 'selected':'').'>30</option>
				<option value="40" '.(($rpp == "40")? 'selected':'').'>40</option>
				<option value="50" '.(($rpp == "50")? 'selected':'').'>50</option>
			<select>
			<br>';
			
		echo '<input type="submit" value="'. __('Search', 'searchforcustomfields') .'" style="float:right"/><br style="clear:both">
			</form>';
			
			
		echo $args['after_widget'];
    }
	

	public function form($instance)	// Outputs the widget settings update form
	{
		$title = isset($instance['title']) ? $instance['title'] : '';
		echo '<p>
			<label for="'.$this->get_field_name( 'title' ).'">'._e( 'Title:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'title' ).'" name="'.$this->get_field_name( 'title' ).'" type="text" value="'.$title.'" />
			</p>';
	}
	
	public function sfcf_get_meta_values( $key = '', $status = 'publish' ) {	// Function that returns all possible values ​​for a field

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
}
?>