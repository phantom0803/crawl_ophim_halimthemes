<?php

define('API_DOMAIN', 'https://ophim1.com');
define('CRAWL_OPHIM_OPTION_SETTINGS', 'crawl_ophim_schedule_settings');
define('CRAWL_OPHIM_OPTION_RUNNING', 'crawl_ophim_schedule_running');
define('CRAWL_OPHIM_OPTION_SECRET_KEY', 'crawl_ophim_schedule_secret_key');

define('SCHEDULE_CRAWLER_TYPE_NOTHING', 0);
define('SCHEDULE_CRAWLER_TYPE_INSERT', 1);
define('SCHEDULE_CRAWLER_TYPE_UPDATE', 2);
define('SCHEDULE_CRAWLER_TYPE_ERROR', 3);
define('SCHEDULE_CRAWLER_TYPE_FILTER', 4);