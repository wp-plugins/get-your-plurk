<?php
/*
Plugin Name:  Get your plurk
Version: 1.1.3
Plugin URI: http://blog.roga.tw/get-your-plurk
Description: "Get your Plurk" could get your plurks from www.plurk.com, and show them on your sidebar.
Author: roga
Author URI: http://blog.roga.tw
*/

/*
the "Get your Plurk" is follwoing GPL v2.
@see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

$plurk_config['debug'] = true;
// show whether you are reading cache file or not.

define('MAGPIE_CACHE_ON', 0); 
// for fix wp-option rss-@W@YEH @#GEM@E records.
define('MAGPIE_INPUT_ENCODING', 'UTF-8');

$plurk_config['cache'] = WP_CONTENT_DIR . '/cache/gyp-cache.tmp';
// maybe  you want to save it in another place.

function get_plurk_head()
{
	echo '<link rel="stylesheet" type="text/css" media="screen" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/get-your-plurk/style.css" />';
}

function get_plurk_feeds($username = '', $count = 10, $showtime = true, $timediff = true, $lang = 'zh_tw', $showusername = true)
{
	
	global $plurk_config;
	
	$result =  '<ul class="gyp-get-plurks">';
	$isBroken = false; // judge if feed exists or not.

	if($username == '')
		$result .=  '<li>Get the Plurk ATOM Feed Error. No Username.</li>';
	else
	{
		include_once(ABSPATH . WPINC . '/rss.php');
		$feeds = fetch_rss('http://www.plurk.com/user/' . $username . '.xml');

		if (empty($feeds->items))
			$isBroken = true;	//we got some problem to fetch the feed.
		else
		{			
			$i = 1;
			foreach ( $feeds->items as $feed )
			{				
				$result .= '<li>';
				
				// 	make patterns and generate links
				$locale = explode ("|",file_get_contents( WP_PLUGIN_DIR . '/get-your-plurk/lang.'. $lang . '.cfg'));			
				
				for($j = 0; $j<count($locale); $j++)
					$patterns[$j] = '/^' .  $feed['author_name'] . $locale[$j] . '/';
					
				$replacements = explode ("|",file_get_contents( WP_PLUGIN_DIR . '/get-your-plurk/lang.css.cfg'));

				for($j = 0; $j<count($replacements); $j++)				
					($showusername) ? $replacements[$j] = '&nbsp;<span class="gyp-fancy ' . $replacements[$j] .'">'. $locale[$j]  . '</span>&nbsp;' : $replacements[$j] = '';
				
				$tmp_result = preg_replace($patterns, $replacements , $feed['atom_content'], 1);
					
				($showusername) ? $result .= '<a href="http://www.plurk.com' . $feed['link_'] . '" class="gyp-userlink">'. $feed['author_name'] . '</a>' . $tmp_result : $result .= $tmp_result;
				
				// generate date time start 
				if($timediff && $showtime)
				{
					$time = (time() - strtotime($feed['published']));

					if($time < 600) // 10 mins
						$time = substr($time / 60, 0, 1) . ' minutes ago';
					else if($time < 3600) //  < 60 mins
						$time = substr($time / 60, 0, 2) . ' minutes ago';
					else if($time <	36000) // < 10 hours
						$time = substr($time / 3600, 0, 3) . ' hours ago';
					else if($time < 86400) // < 1 day
						$time = substr($time / 3600, 0, 4) . ' hours ago';
					else if($time < 864000) // < 10 days
						$time = substr($time / 86400, 0, 1) . ' days ago';
					else if($time < 2592000) // < 30 days
						$time = substr($time / 864000, 0, 2) . ' days ago';
					else		
						$time = "very long ago.";
						
					$result .= ' <span class="gyp-plurk-time">' . $time . '</span></li>' . "\n";
				}
				else if($showtime)			
				{
					$time = strftime("%Y/%m/%d %H:%M", strtotime($feed['published']));
					$result .= ' <span class="gyp-plurk-time">' . $time . '</span></li>' . "\n";
				}
				else
					$result .= '</li>' . "\n";
															
				if($i == $count)
					break;
				$i++;
			}
		}
		$result .= '</ul>';

		($isBroken) ? $result = file_get_contents($plurk_config['cache']) : @file_put_contents($plurk_config['cache'], $result);
		
		return $result;

	}
}

function widget_get_plurks($args)
{
	global $plurk_config;
	
	extract($args);
	
	$plurk_options = get_option('widget_get_plurks');

	$refresh = true;

	if(file_exists($plurk_config['cache'])) $refresh = (time() > filemtime($plurk_config['cache']) + $plurk_options['plurk-cache']);
	
	echo $before_widget . $before_title . $plurk_options['plurk-title'] . $after_title;
	
	($refresh || $plurk_options['plurk-cache'] == 0) ? $result = get_plurk_feeds($plurk_options['plurk-username'], $plurk_options['plurk-counts'], $plurk_options['plurk-publish-time'], $plurk_options['plurk-timediff'], $plurk_options['plurk-lang'], $plurk_options['plurk-show-username']) : $result = file_get_contents($plurk_config['cache']);

	echo $result;

	echo '<div class="gyp-plurk-detail">visit <a href="http://www.plurk.com/' . $plurk_options['plurk-username'] . '">' . $plurk_options['plurk-username'] . "'s Plurk</a>";
	if($plurk_config['debug'])
	{ 
		if ($refresh)
			echo ' - with fresh feeds!'; 
		else
			echo ' - next refresh: '. (filemtime($plurk_config['cache']) + $plurk_options['plurk-cache'] - time()).'s';
	}
	echo "</div>";
	
	if(!file_exists($plurk_config['cache'])) echo 'OOPS! Create <strong>' . $plurk_config['cache'] . '</strong> and make it wriatble first, see readme file.';  
		
	echo $after_widget;
}

function widget_get_plurks_control()
{

	$plurk_options = $new_plurk_options = get_option('widget_get_plurks');

	if(isset($_POST['plurk-submit']) )
	{
		$new_plurk_options['plurk-title'] = $_POST['plurk-title'];
		$new_plurk_options['plurk-username'] = $_POST['plurk-username'];
		$new_plurk_options['plurk-counts'] = $_POST['plurk-counts'];
		$new_plurk_options['plurk-show-username'] = $_POST['plurk-show-username'];
		$new_plurk_options['plurk-publish-time'] = $_POST['plurk-publish-time'];		
		$new_plurk_options['plurk-timediff'] = $_POST['plurk-timediff'];
		$new_plurk_options['plurk-lang'] = $_POST['plurk-lang'];
		$new_plurk_options['plurk-cache'] = $_POST['plurk-cache'];		
	}
	
	if ( $plurk_options != $new_plurk_options )
	{
		$plurk_options = $new_plurk_options;
		get_plurk_feeds($plurk_options['plurk-username'], $plurk_options['plurk-counts'], $plurk_options['plurk-publish-time'], $plurk_options['plurk-timediff'], $plurk_options['plurk-lang'], $plurk_options['plurk-show-username']);			
		update_option('widget_get_plurks', $plurk_options);
	}

	if(!isset($plurk_options['plurk-cache'])) $plurk_options['plurk-cache'] = 60 ; 	
	
	($plurk_options['plurk-publish-time']) ? $time_is_checked = 'checked = "checked"' : $time_is_checked = '';
	($plurk_options['plurk-timediff'] && $plurk_options['plurk-publish-time']) ? $timediff_is_checked = 'checked = "checked"' :	$timediff_is_checked = '';
	($plurk_options['plurk-show-username']) ? $show_username_is_checked = 'checked' : $show_username_is_checked = '';
	($plurk_options['plurk-lang'] == 'zh_tw') ? $lang_is_selected = 'selected' : $lang_is_selected = '';

?>
	<p><label for="plurk-title" >Title: </label><input id="plurk-title" name="plurk-title" type="text" value="<?php echo $plurk_options['plurk-title']; ?>" size="20" /></p>
	<p><label for="plurk-username" >Username: </label><input id="plurk-username" name="plurk-username" type="text" value="<?php echo $plurk_options['plurk-username']; ?>" size="5" /></p>
	<p><label for="plurk-counts" >Plurk counts: </label><input id="plurk-counts" name="plurk-counts" type="text" value="<?php echo $plurk_options['plurk-counts']; ?>" size="5" /> </p>	
	<p><label for="plurk-lang" >localization:</label> 
		<select name="plurk-lang">
			<option value="en_us">English</option>
			<option value="zh_tw" <?php echo $lang_is_selected; ?>"> 繁體中文 </option>
		</select>
	<p><label for="plurk-show-username" >Show User Name: </label><input id="plurk-show-username" name="plurk-show-username" type="checkbox" value="true" <?echo $show_username_is_checked ?> /></p>
	<p><label for="plurk-publish-time" >Show Publish Time: </label><input id="plurk-publish-time" name="plurk-publish-time" type="checkbox" value="true" <?echo $time_is_checked ?> /></p>
	<p><label for="plurk-timediff" >Show Timediff: </label><input id="plurk-timediff" name="plurk-timediff" type="checkbox" value="true" <?echo $timediff_is_checked ?> /></p>
	<p><label for="plurk-cache" >Cache Time: </label><input id="plurk-cache" name="plurk-cache" type="text" value="<?echo $plurk_options['plurk-cache']; ?>" size="5" /> set 0 to ignore cache</p>
	<input type="hidden" name="plurk-submit" value="1" />
<?php
}

function get_plurk_init()
{
	if(function_exists('register_sidebar_widget'))
		register_sidebar_widget('Get Plurks', 'widget_get_plurks');
	if(function_exists('register_widget_control'))
		register_widget_control('Get Plurks', 'widget_get_plurks_control', 500, 200);
}

add_action('plugins_loaded', 'get_plurk_init');
add_action('wp_head', 'get_plurk_head');
?>