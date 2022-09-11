<?php
add_action('admin_menu', 'tool_add_menu');
function tool_add_menu()
{
	add_menu_page(
		'Thông Tin Tools',
		'Crawl OPhim.CC',
		'manage_options',
		'crawl-ophim-tools',
		'crawl_tools',
		'',
		'2'
	);
}

function crawl_tools()
{

	$cache = new Cache();

	$categoryFromApi = $cache->readCache(API_DOMAIN . "/the-loai");
	if(!$categoryFromApi) { // Kiểm tra nếu cache tồn tại
		$categoryFromApi = file_get_contents(API_DOMAIN . "/the-loai");
		$cache->timeCache = 86400;
		$cache->saveCache(API_DOMAIN . "/the-loai", $categoryFromApi); // Lưu cache
	}
	$categoryFromApi = json_decode($categoryFromApi);
	
	$countryFromApi = $cache->readCache(API_DOMAIN . "/quoc-gia");
	if(!$countryFromApi) { // Kiểm tra nếu cache tồn tại
		$countryFromApi = file_get_contents(API_DOMAIN . "/quoc-gia");
		$cache->timeCache = 86400;
		$cache->saveCache(API_DOMAIN . "/quoc-gia", $countryFromApi); // Lưu cache
	}
	$countryFromApi = json_decode($countryFromApi);	
?>
	<div class="crawl_main">
		<div class="crawl_page">
			<div class="postbox">
				<div class="inside">
					Ophim.CC là website dữ liệu phim miễn phí vĩnh viễn. Cập nhật nhanh, chất lượng cao, ổn định và lâu dài. Tốc độ phát cực nhanh với đường truyền băng thông cao, đảm bảo đáp ứng được lượng xem phim trực tuyến lớn. Đồng thời giúp nhà phát triển website phim giảm thiểu chi phí của các dịch vụ lưu trữ và stream. <br />
					- Hàng ngày chạy tools tầm 10 đến 20 pages đầu (tùy số lượng phim được cập nhật trong ngày) để update tập mới hoặc thêm phim mới!<br />
					- Trộn link vài lần để thay đổi thứ tự crawl & update. Giúp tránh việc quá giống nhau về content của các website!<br />
					- API được cung cấp miễn phí: <a href="https://ophim.cc/api-document" target="_blank">https://ophim.cc/api-document</a> <br />
					- Tham gia trao đổi tại: <a href="https://t.me/+QMfjBOtNpkZmNTc1" target="_blank">https://t.me/+QMfjBOtNpkZmNTc1</a> <br />
				</div>
			</div>
		</div>
		
		<div class="crawl_filter notice notice-info">

			<div class="filter_title"><strong>Định dạng</strong></div>
			<div class="filter_item">
				<label><input type="checkbox" class="" name="filter_type[]" value="single"> Phim lẻ</label>
				<label><input type="checkbox" class="" name="filter_type[]" value="series"> Phim bộ</label>
				<label><input type="checkbox" class="" name="filter_type[]" value="hoathinh"> Hoạt hình</label>
				<label><input type="checkbox" class="" name="filter_type[]" value="tvshows"> Tv shows</label>
			</div>

			<div class="filter_title"><strong>Bỏ qua thể loại</strong></div>
			<div class="filter_item">
				<?php
					foreach($categoryFromApi as $category) {
				?>
						<label><input type="checkbox" class="" name="filter_category[]" value="<?php echo $category->name;?>"> <?php echo $category->name;?></label>
				<?php
					}
				?>
			</div>

			<div class="filter_title"><strong>Bỏ qua quốc gia</strong></div>
			<div class="filter_item">
				<?php
					foreach($countryFromApi as $country) {
				?>
						<label><input type="checkbox" class="" name="filter_country[]" value="<?php echo $country->name;?>"> <?php echo $country->name;?></label>
				<?php
					}
				?>
			</div>

		</div>

		<div class="crawl_page">
			Page Crawl: From <input type="number" name="page_from" value="10">
			To <input type="number" name="page_to" value="1">
			<div id="get_list_movies" class="primary">Get List Movies</div>
		</div>
		<div class="crawl_page">
			<div style="display: none" id="msg" class="notice notice-success">
				<p id="msg_text"></p>
			</div>
			<textarea rows="10" id="result_list_movies" class="list_movies"></textarea>
			<div id="roll_movies" class="roll">Trộn Link</div>
			<div id="crawl_movies" class="primary">Crawl Movies</div>

			<div style="display: none;" id="result_success" class="notice notice-success">
				<p>Crawl Thành Công</p>
				<textarea rows="10" id="list_crawl_success"></textarea>
			</div>

			<div style="display: none;" id="result_error" class="notice notice-error">
				<p>Crawl Lỗi</p>
				<textarea rows="10" id="list_crawl_error"></textarea>
			</div>
		</div>
	</div>
<?php
}

add_action('wp_ajax_crawl_ophim_page', 'crawl_ophim_page');
function crawl_ophim_page()
{
	$url 							= $_POST['url'];
	$sourcePage 			=  HALIMHelper::cURL($url);
	$sourcePage       = json_decode($sourcePage);
	$listMovies 			= [];

	if(count($sourcePage->items) > 0) {
		foreach ($sourcePage->items as $key => $item) {
			array_push($listMovies, "https://ophim.tv/phim/{$item->slug}|{$item->_id}|{$item->modified->time}|{$item->name}|{$item->origin_name}|{$item->year}");
		}
		echo join("\n", $listMovies);
	} else {
		echo [];
	}

	die();
}

add_action('wp_ajax_crawl_ophim_movies', 'crawl_ophim_movies');
function crawl_ophim_movies()
{
	try {
		$data_post 					= $_POST['url'];
		$url 								= explode('|', $data_post)[0];
		$ophim_id 					= explode('|', $data_post)[1];
		$ophim_update_time 	= explode('|', $data_post)[2];
		$title 							= explode('|', $data_post)[3];
		$org_title 					= explode('|', $data_post)[4];
		$year 							= explode('|', $data_post)[5];
		
		$filterType 				= $_POST['filterType'] ?: [];
		$filterCategory 		= $_POST['filterCategory'] ?: [];
		$filterCountry 			= $_POST['filterCountry'] ?: [];

		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_halim_metabox_options',
					'value' => $url,
					'compare' => 'LIKE'
				)
			)
		);
		$wp_query = new WP_Query($args);
		$total = $wp_query->found_posts;
	
		if ($total > 0) { # Trường hợp đã có

			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key' => '_halim_metabox_options',
						'value' => $ophim_id,
						'compare' => 'LIKE'
					)
				)
			);
			$wp_query = new WP_Query($args);
			if ($wp_query->have_posts()) : while ($wp_query->have_posts()) : $wp_query->the_post();
					global $post;
					$_halim_metabox_options = get_post_meta($post->ID, '_halim_metabox_options', true);

					if($_halim_metabox_options["fetch_ophim_update_time"] == $ophim_update_time) { // Không có gì cần cập nhật
						$result = array(
							'status'   			=> true,
							'post_id' 			=> null,
							'list_episode' 	=> [],
							'msg' 					=> 'Nothing needs updating!'
						);
						echo json_encode($result);
						die();
					}

					$api_url 			= str_replace('ophim.tv', 'ophim1.com', $url);
					$sourcePage 	=  HALIMHelper::cURL($api_url);
					$sourcePage 	= json_decode($sourcePage, true);
					$data 				= create_data($sourcePage, $url, $ophim_id, $ophim_update_time);

					// Re-Update Movies Info
					$formality 																					= ($data['type'] == 'tv_series') ? 'tv_series' : 'single_movies';
					$_halim_metabox_options["halim_movie_formality"] 		= $formality;
					$_halim_metabox_options["halim_movie_status"] 			= strtolower($data['status']);
					$_halim_metabox_options["fetch_info_url"] 					= $data['fetch_url'];
					$_halim_metabox_options["fetch_ophim_update_time"] 	= $data['fetch_ophim_update_time'];
					$_halim_metabox_options["halim_original_title"] 		= $data['org_title'];
					$_halim_metabox_options["halim_trailer_url"] 				= $data['trailer_url'];
					$_halim_metabox_options["halim_runtime"] 						= $data['duration'];
					$_halim_metabox_options["halim_episode"] 						= $data['episode'];
					$_halim_metabox_options["halim_total_episode"] 			= $data['total_episode'];
					$_halim_metabox_options["halim_quality"] 						= $data['lang'] . ' - ' . $data['quality'];
					$_halim_metabox_options["halim_showtime_movies"] 		= $data['showtime'];
					update_post_meta($post->ID, '_halim_metabox_options', $_halim_metabox_options);

					// Re-Update Episodes
					$list_episode = get_list_episode($sourcePage, $post->ID);
					$result = array(
						'status'				=> true,
						'post_id' 			=> $post->ID,
						'data'					=> $data,
						'list_episode' 	=> $list_episode
					);
					wp_update_post($post);
					echo json_encode($result);
					die();
				endwhile;
			endif;
		}

		$api_url 		= str_replace('ophim.tv', 'ophim1.com', $url);
		$sourcePage =  HALIMHelper::cURL($api_url);
		$sourcePage = json_decode($sourcePage, true);
		$data 			= create_data($sourcePage, $url, $ophim_id, $ophim_update_time, $filterType, $filterCategory, $filterCountry);
		if($data['crawl_filter']) {
			$result = array(
				'status'				=> false,
				'post_id' 			=> null,
				'data'					=> null,
				'list_episode' 	=> null,
				'msg' 					=> "Lọc bỏ qua"
			);
			echo json_encode($result);
			die();
		}

		$post_id 		= add_posts($data);
		$list_episode = get_list_episode($sourcePage, $post_id);
		$result = array(
			'status'				=> true,
			'post_id' 			=> $post_id,
			'data'					=> $data,
			'list_episode' 	=> $list_episode
		);
		echo json_encode($result);
		die();
  }catch (Exception $e) {
		$result = array(
			'status'				=> false,
			'post_id' 			=> null,
			'data'					=> null,
			'list_episode' 	=> null,
			'msg' 					=> "Crawl error"
		);
		echo json_encode($result);
		die();
  }
}

function create_data($sourcePage, $url, $ophim_id, $ophim_update_time, $filterType = [], $filterCategory = [], $filterCountry = []) {
	if(in_array($sourcePage["movie"]["type"], $filterType))  {
		return array(
			'crawl_filter' => true,
		);
	}

	if($sourcePage["movie"]["type"] == "single") {
		$type = "single_movies";
	} else {
		$type	= "tv_series";
	}

	$arrCat = [];
	foreach ($sourcePage["movie"]["category"] as $key => $value) {
		if(in_array($value["name"], $filterCategory))  {
			return array(
				'crawl_filter' => true,
			);
		}
		array_push($arrCat, $value["name"]);
	}
	if($sourcePage["movie"]["chieurap"] == true) {
		array_push($arrCat, "Chiếu Rạp");
	}
	if($sourcePage["movie"]["type"] == "hoathinh") {
		array_push($arrCat, "Hoạt Hình");
	}
	if($sourcePage["movie"]["type"] == "tvshows") {
		array_push($arrCat, "TV Shows");
	}

	$arrCountry 	= [];
	foreach ($sourcePage["movie"]["country"] as $key => $value) {
		if(in_array($value["name"], $filterCountry))  {
			return array(
				'crawl_filter' => true,
			);
		}
		array_push($arrCountry, $value["name"]);
	}

	$arrTags 			= [];
	array_push($arrTags, $sourcePage["movie"]["name"]);
	if($sourcePage["movie"]["name"] != $sourcePage["movie"]["origin_name"]) array_push($arrTags, $sourcePage["movie"]["origin_name"]);

	$data = array(
		'crawl_filter'						=> false,
		'fetch_url' 							=> $url,
		'fetch_ophim_id' 					=> $ophim_id,
		'fetch_ophim_update_time' => $ophim_update_time,
		'title'     							=> $sourcePage["movie"]["name"],
		'org_title' 							=> $sourcePage["movie"]["origin_name"],
		'thumbnail' 							=> $sourcePage["movie"]["thumb_url"],
		'poster'   		 						=> $sourcePage["movie"]["poster_url"],
		'trailer_url'   		 			=> $sourcePage["movie"]["trailer_url"],
		'episode'									=> $sourcePage["movie"]["episode_current"],
		'total_episode'						=> $sourcePage["movie"]["episode_total"],
		'tags'      							=> $arrTags,
		'content'   							=> preg_replace('/\\r?\\n/s', '', $sourcePage["movie"]["content"]),
		'actor'										=> implode(',', $sourcePage["movie"]["actor"]),
		'director'								=> implode(',', $sourcePage["movie"]["director"]),
		'country'									=> $arrCountry,
		'cat'											=> $arrCat,
		'type'										=> $type,
		'lang'										=> $sourcePage["movie"]["lang"],
		'showtime'								=> $sourcePage["movie"]["showtime"],
		'year'										=> $sourcePage["movie"]["year"],
		'status'									=> $sourcePage["movie"]["status"],
		'duration'								=> $sourcePage["movie"]["time"],
		'quality'									=> $sourcePage["movie"]["quality"]
	);

	return $data;
}

function add_posts($data)
{
	$director  = explode(',', sanitize_text_field($data['director']));
	$actor     = explode(',', sanitize_text_field($data['actor']));

	$cat_id = array();
	foreach ($data['cat'] as $cat) {
		if (!category_exists($cat) && $cat != '') {
			wp_create_category($cat);
		}
		$cat_id[] = get_cat_ID($cat);
	}
	foreach ($data['tags'] as $tag) {
		if (!term_exists($tag) && $tag != '') {
			wp_insert_term($tag, 'post_tag');
		}
	}
	$formality = ($data['type'] == 'tv_series') ? 'tv_series' : 'single_movies';
	$post_data = array(
		'post_title'   		=> $data['title'],
		'post_content' 		=> $data['content'],
		'post_status'  		=> 'publish',
		'comment_status' 	=> 'closed',
		'ping_status'  		=> 'closed',
		'post_author'  		=> get_current_user_id()
	);
	$post_id 						= wp_insert_post($post_data);

	if($data['poster'] && $data['poster'] != "") {
		$res 								= save_images($data['poster'], $post_id, $data['title']);
		$poster_image_url 	= str_replace(get_site_url(), '', $res['url']);
	}
	save_images($data['thumbnail'], $post_id, $data['title'], true);
	$thumb_image_url 		= get_the_post_thumbnail_url($post_id, 'movie-thumb');

	if (isset($data['status'])) {
		wp_set_object_terms($post_id, $data['status'], 'status', false);
	}

	$post_format 				= halim_get_post_format_type($formality);
	set_post_format($post_id, $post_format);

	$post_meta_movies = array(
		'halim_movie_formality' 		=> $formality,
		'halim_movie_status'    		=> strtolower($data['status']),
		'fetch_info_url'						=> $data['fetch_url'],
		'fetch_ophim_id'						=> $data['fetch_ophim_id'],
		'fetch_ophim_update_time'		=> $data['fetch_ophim_update_time'],
		'halim_poster_url'      		=> $poster_image_url,
		'halim_thumb_url'       		=> $thumb_image_url,
		'halim_original_title'			=> $data['org_title'],
		'halim_trailer_url' 				=> $data['trailer_url'],
		'halim_runtime'							=> $data['duration'],
		'halim_rating' 							=> '',
		'halim_votes' 							=> '',
		'halim_episode'         		=> $data['episode'],
		'halim_total_episode' 			=> $data['total_episode'],
		'halim_quality'         		=> $data['lang'] . ' - ' . $data['quality'],
		'halim_movie_notice' 				=> '',
		'halim_showtime_movies' 		=> $data['showtime'],
		'halim_add_to_widget' 			=> false,
		'save_poster_image' 				=> false,
		'set_reatured_image' 				=> false,
		'save_all_img' 							=> false,
		'is_adult' 									=> false,
		'is_copyright' 							=> false,
	);

	$default_episode     									= array();
	$ep_sv_add['halimmovies_server_name'] = "Server #1";
	$ep_sv_add['halimmovies_server_data'] = array();
	array_push($default_episode, $ep_sv_add);

	wp_set_object_terms($post_id, $director, 'director', false);
	wp_set_object_terms($post_id, $actor, 'actor', false);
	wp_set_object_terms($post_id, sanitize_text_field($data['year']), 'release', false);
	wp_set_object_terms($post_id, $data['country'], 'country', false);
	wp_set_post_terms($post_id, $data['tags']);
	wp_set_post_categories($post_id, $cat_id);
	update_post_meta($post_id, '_halim_metabox_options', $post_meta_movies);
	update_post_meta($post_id, '_halimmovies', json_encode($default_episode, JSON_UNESCAPED_UNICODE));
	update_post_meta($post_id, '_edit_last', 1);
	return $post_id;
}

function save_images($image_url, $post_id, $posttitle, $set_thumb = false)
{
	$file				 	= file_get_contents($image_url);
	$postname 		= sanitize_title($posttitle);
	$im_name 			= "$postname-$post_id.jpg";
	$res 					= wp_upload_bits($im_name, '', $file);
	insert_attachment($res['file'], $post_id, $set_thumb);
	return $res;
}

function insert_attachment($file, $post_id, $set_thumb)
{
	$dirs 							= wp_upload_dir();
	$filetype 					= wp_check_filetype($file);
	$attachment 				= array(
		'guid' 						=> $dirs['baseurl'] . '/' . _wp_relative_upload_path($file),
		'post_mime_type' 	=> $filetype['type'],
		'post_title' 			=> preg_replace('/\.[^.]+$/', '', basename($file)),
		'post_content' 		=> '',
		'post_status' 		=> 'inherit'
	);
	$attach_id 					= wp_insert_attachment($attachment, $file, $post_id);
	$attach_data 				= wp_generate_attachment_metadata($attach_id, $file);
	wp_update_attachment_metadata($attach_id, $attach_data);
	if ($set_thumb != false) set_post_thumbnail($post_id, $attach_id);
	return $attach_id;
}

function get_list_episode($sourcePage, $post_id)
{
	# Xử lý episodes
	$server_add = array();
	if ($sourcePage["episodes"][0]["server_data"][0]["link_m3u8"] !== "") {
		foreach ($sourcePage["episodes"] as $key => $servers) {
			$server_info["halimmovies_server_name"] = $servers["server_name"];
			$server_info["halimmovies_server_data"] = array();

			foreach ($servers["server_data"] as $episode) {
				$slug_array 											= slugify($episode["name"], '_');
				$slug_ep 													= sanitize_title($episode["name"]);
				$episode["link_m3u8"]							= str_replace('http:', 'https:', $episode["link_m3u8"]);
				$episode["link_embed"]						= str_replace('http:', 'https:', $episode["link_embed"]);

				$ep_data['halimmovies_ep_name'] 	= $episode["name"];
				$ep_data['halimmovies_ep_slug'] 	= $slug_ep;
				$ep_data['halimmovies_ep_type'] 	= 'link';
				$ep_data['halimmovies_ep_link'] 	= $episode["link_m3u8"];
				$ep_data['halimmovies_ep_subs'] 	= array();
				$ep_data['halimmovies_ep_listsv'] = array();
				# Sử dụng link embed làm server dự phòng.
				$subServerData = array(
					"halimmovies_ep_listsv_link" => $episode["link_embed"],
					"halimmovies_ep_listsv_type" => "embed",
					"halimmovies_ep_listsv_name" => "#Dự Phòng"
				);
				array_push($ep_data['halimmovies_ep_listsv'], $subServerData);
				$server_info["halimmovies_server_data"][$slug_array] = $ep_data;
			}
			array_push($server_add, $server_info);
		}
		update_post_meta($post_id, '_halimmovies', json_encode($server_add, JSON_UNESCAPED_UNICODE));
	}
	return json_encode($server_add);
}

function slugify($str, $divider = '-')
{
	$str = trim(mb_strtolower($str));
	$str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
	$str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
	$str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
	$str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
	$str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
	$str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
	$str = preg_replace('/(đ)/', 'd', $str);
	$str = preg_replace('/[^a-z0-9-\s]/', '', $str);
	$str = preg_replace('/([\s]+)/', $divider, $str);
	return $str;
}