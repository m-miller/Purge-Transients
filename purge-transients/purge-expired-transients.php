<?php
/*
Plugin Name:  Purge Expired Transients
Description:  Delete transient variables in the options table older than one week. Runs weekly at 7am Sunday. 
Version:      1.0
Author:   <a href="http://quarterly.mayo.edu/directory/person/person.htm?per_id=15469921" target="_blank">Martin Miller</a>, Mayo Clinic Department of Nursing
Author URL:  nursing.mayo.edu    
*/

/* TODO:
	settings page for run times, etc.
	refactor as OO


*/
	$tz = get_option('timezone_string');
	if ( $tz ) date_default_timezone_set($tz);
	add_filter( 'cron_schedules', 'cron_weekly' );
	function cron_weekly( $schedules ) {
 		// Adds once weekly to the existing schedules.
 	$schedules['weekly'] = array(
 		'interval' => 604800,
 		'display' => __( 'Once Weekly' )
 	);
 	return $schedules;
	}

if ( ! function_exists('purge_transients') ) {
	function purge_transients() {
		date_default_timezone_set('America/Chicago');
		global $wpdb;
		$exdp = strtotime( '-1 week' );	
		// from wp-admin/includes/schema.php :)
		// WP purges transients every time a db version upgrade occurs during a WP upgrade 
	$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
		
	$remtrans = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', $exdp ) );
	//return $remtrans;
	}
}

function purge_transients_activation () {
	if (!wp_next_scheduled('purge_transients_cron')) {		
		wp_schedule_event( 1430049600, 'weekly', 'purge_transients_cron');
		// first fire 7am Sunday, April 26, 2015 1430049600
	}
}
register_activation_hook(__FILE__, 'purge_transients_activation');

function purge_transients_deactivation () {
	if (wp_next_scheduled('purge_transients_cron')) {
		wp_clear_scheduled_hook('purge_transients_cron');
	}
}
register_deactivation_hook(__FILE__, 'purge_transients_deactivation');

add_action('purge_transients_cron', 'purge_transients');
?>