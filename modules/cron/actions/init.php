<?

add_action('clicklib_hourly_cron',  array(Click::$event_dispatcher, 'cron_hourly'));

if ( !wp_next_scheduled( 'clicklib_hourly_cron' ) ) {
	wp_schedule_event(time(), 'hourly', 'clicklib_hourly_cron');
}
