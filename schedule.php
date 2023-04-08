<?php
require_once __DIR__ . '/../../../wp-load.php';
require_once __DIR__ . '/../../../wp-admin/includes/taxonomy.php';
require_once __DIR__ . '/../../../wp-admin/includes/image.php';

set_time_limit(0);
define('CRAWL_OPHIM_PATH', plugin_dir_path(__FILE__));
define('CRAWL_OPHIM_PATH_SCHEDULE_JSON', CRAWL_OPHIM_PATH . 'schedule.json');
require_once CRAWL_OPHIM_PATH . 'constant.php';

if(!isset($argv[1])) return;
if($argv[1] != get_option(CRAWL_OPHIM_OPTION_SECRET_KEY, 'secret_key')) return;

require_once CRAWL_OPHIM_PATH . 'functions.php';
require_once CRAWL_OPHIM_PATH . 'crawl_movies.php';

// Get & Check Settings
$crawl_ophim_settings = json_decode(get_option(CRAWL_OPHIM_OPTION_SETTINGS, false));
if(!$crawl_ophim_settings) return;

// Check enable
if(getEnable() === false) {
	update_option(CRAWL_OPHIM_OPTION_RUNNING, 0);
	return;
}
// Check running
if((int) get_option(CRAWL_OPHIM_OPTION_RUNNING, 0) === 1) return;

// Update Running
update_option(CRAWL_OPHIM_OPTION_RUNNING, 1);

try {
	// Crawl Pages
	$pageFrom = $crawl_ophim_settings->pageFrom;
	$pageTo = $crawl_ophim_settings->pageTo;
	$listMovies = array();
	for ($i=$pageFrom; $i >= $pageTo; $i--) {
		if(getEnable() === false) {
			update_option(CRAWL_OPHIM_OPTION_RUNNING, 0);
			return;
		}
		$result = crawl_ophim_page_handle(API_DOMAIN . "/danh-sach/phim-moi-cap-nhat?page=$i");
		$result = explode("\n", $result);
		$listMovies = array_merge($listMovies, $result);
	}
	shuffle($listMovies);

	$countMovies = count($listMovies);
	$countDone = 0;
	$countStatus = array(0,0,0,0,0);

	write_log("Start crawler {$countMovies} movies");
	// Crawl Movies
	foreach ($listMovies as $key => $data_post) {
		if(getEnable() === false) {
			update_option(CRAWL_OPHIM_OPTION_RUNNING, 0);
			write_log("Force Stop => Done {$countDone}/{$countMovies} movies (Nothing Update: {$countStatus[0]} | Insert: {$countStatus[1]} | Update: {$countStatus[2]} | Error: {$countStatus[3]} | Filter: {$countStatus[4]})");
			return;
		}

		$url 								= explode('|', $data_post)[0];
		$ophim_id 					= explode('|', $data_post)[1];
		$ophim_update_time 	= explode('|', $data_post)[2];

		$result = crawl_ophim_movies_handle($url, $ophim_id, $ophim_update_time, $crawl_ophim_settings->filterType, $crawl_ophim_settings->filterCategory, $crawl_ophim_settings->filterCountry);
		$result = json_decode($result);
		if ($result->schedule_code == SCHEDULE_CRAWLER_TYPE_ERROR) write_log(sprintf("ERROR: %s ==>>> %s", $url, $result->msg));
		$countStatus[$result->schedule_code]++;
		$countDone++;
	}

} catch (\Throwable $th) {
	write_log(sprintf("ERROR: THROW ==>>> %s", $th->getMessage()));
}

// Update Running
update_option(CRAWL_OPHIM_OPTION_RUNNING, 0);

write_log("Done {$countDone}/{$countMovies} movies (Nothing Update: {$countStatus[0]} | Insert: {$countStatus[1]} | Update: {$countStatus[2]} | Error: {$countStatus[3]} | Filter: {$countStatus[4]})");

function getEnable()
{
	$schedule = json_decode(file_get_contents(CRAWL_OPHIM_PATH_SCHEDULE_JSON));
	if ($schedule->enable) {
		return $schedule->enable;
	}
	return false;
}

function write_log($log_msg, $new_line = "\n") {
	$log_filename = __DIR__ . '/../../crawl_ophim_logs';
	if (!file_exists($log_filename))
	{
		mkdir($log_filename, 0777, true);
	}
	$log_file_data = $log_filename.'/log_' . date('d-m-Y') . '.log';
	file_put_contents($log_file_data, '['. date("d-m-Y H:i:s") .'] ' . $log_msg . $new_line, FILE_APPEND);
}
