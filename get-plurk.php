<?php
/*
Plugin Name:  Get your plurk
Version: 1.0.5
Plugin URI: http://blog.roga.tw/get-your-plurk
Description: Get your plurk
Author: roga
Author URI: http://blog.roga.tw
*/

/*

the "Get your Plurk" is follwoing GPL v2.
@see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

*/

$plurk_config['cachepath'] = WP_PLUGIN_DIR . '/get-your-plurk/cache.tmp';
// maybe  you want to save it in another place.

$plurk_config['image'] = '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/get-your-plurk/extlink.gif" alt="plurk-link" style="vertical-align: middle; margin-right: 5px;" />';
// the externel link image.

$plurk_config['fancyfeed'] = true;

$plurk_config['origin'] = "your-name-you-want-to-replace";

$plurk_config['timediff'] = true;
// show  48 hours ago or date time

$plurk_config['debug'] = true;
// show whether you are reading cache file or not.

define('MAGPIE_CACHE_ON', 0); // for fix wp-option rss-@W@YEH @#GEM@E records.
define('MAGPIE_INPUT_ENCODING', 'UTF-8');

function get_plurk_head()
{
	echo '<link rel="stylesheet" type="text/css" media="screen" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/get-your-plurk/style.css" />';
}

function get_plurk_feeds($username = '', $count = 5, $showtime = true, $link = true)
{
	global $plurk_config;

	$extlink = $plurk_config['image'];

	$result =  '<ul>';

	$isBroken = false; // judge if feed exists or not.

	if($username == '')
		$result .=  '<li>Get the Plurk ATOM Feed Error</li>';
	else
	{
		include_once(ABSPATH . WPINC . '/rss.php');

		$feeds = fetch_rss('http://www.plurk.com/user/' . $username . '.xml');

		if (empty($feeds->items))
		{
			$isBroken = true;
			//we have some problem.
		}
		else
		{
			$i = 1;
			foreach ( $feeds->items as $feed )
			{
				$msg = $feed['atom_content'];

				$result .= '<li>';
				$result .= '<span class="plurk">';
				if ($link != false)
				{
					if($plurk_config['fancyfeed'])
					{
						$fancy = '<a href="http://www.plurk.com' . $feed['link_'] . '">' . $feed['author_name'] .'</a><span class="qualifier">says</span>';
						$msg = str_replace($plurk_config['origin'] , $fancy , $msg);
						$result .= $msg;
					}
					else
					{
						$result .= ' <a href="http://www.plurk.com' . $feed['link_'] . '" title="plurk">' . $extlink .'</a>'. $msg ;
					}
				}
				else
					$result .= $msg;

				$result .= '</span>';

				if($showtime)
				{
					if($plurk_config['timediff'])
					{
						$time = substr(((time() - strtotime($feed['published'])) / 3600), 0, 4);
						if(strpos($time, ".") == 4)
							$time = str_replace(".", "", $time);
						$result .= ' <span class="plurk-time">' . $time . ' hours ago</span>';
					}
					else
					{
						$time = strftime("%Y/%m/%d %H:%M", strtotime($feed['published']));
						$result .= ' <span class="plurk-time">' . $time . '</span>';
					}
				}

				$result .= '</li>';

				if($i == $count)
					break;
				$i++;
			}
		}
		$result .= '</ul>';
		$result .= '<div class="plurk-detail"><a href="http://www.plurk.com/'. $username . '">'. $username . "'s Plurk</a>";

		if($isBroken == false)
		{
			file_put_contents($plurk_config['cachepath'], $result);
			return $result;
		}
		else
		{
			$result = file_get_contents($plurk_config['cachepath']);
			// just read old file. it will try again next time.
			return $result;
		}

	}
}

function widget_get_plurks($args)
{
	global $plurk_config;
	extract($args);
	$plurk_options = get_option('widget_get_plurks');

	$refresh = true;

	if(file_exists($plurk_config['cachepath']))
	{
		$refresh = (time() > filemtime($plurk_config['cachepath']) + $plurk_options['plurk-cache']);
	}
	echo $before_widget . $before_title . $plurk_options['plurk-title'] . $after_title;

	if($plurk_options['plurk-cache'] == 0)
		$refresh = true; // for ignore cache

	if($refresh)
	{
		$result = get_plurk_feeds($plurk_options['plurk-username'], $plurk_options['plurk-counts'], $plurk_options['plurk-publish-time'], $plurk_options['plurk-link']);
		echo $result;
		if($plurk_config['debug'])
			echo ' - with fresh feeds!</div>';
	}
	else
	{
		$result = file_get_contents($plurk_config['cachepath']);
		echo $result;
		if($plurk_config['debug'])
			echo ' - seconds to next re-fresh: '. (filemtime($plurk_config['cachepath']) + $plurk_options['plurk-cache'] - time()) . '</div>';
	}
	if(!$plurk_config['debug'])
		echo '</div>';
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
		$new_plurk_options['plurk-link'] = $_POST['plurk-link'];
		$new_plurk_options['plurk-publish-time'] = $_POST['plurk-publish-time'];
		$new_plurk_options['plurk-cache'] = $_POST['plurk-cache'];
	}
	if ( $plurk_options != $new_plurk_options )
	{
		$plurk_options = $new_plurk_options;
		get_plurk_feeds($plurk_options['plurk-username'], $plurk_options['plurk-counts'], $plurk_options['plurk-publish-time'], $plurk_options['plurk-link']);
		update_option('widget_get_plurks', $plurk_options);
	}

	if($plurk_options['plurk-link'] == true)
		$link_is_checked = 'checked = "checked"';
	else
		$link_is_checked = '';

	if($plurk_options['plurk-publish-time'] == true)
		$time_is_checked = 'checked = "checked"';
	else
		$time_is_checked = '';

?>
	<p><label for="plurk-title" >Title: <input id="plurk-title" name="plurk-title" type="text" value="<?php echo $plurk_options['plurk-title']; ?>" size="20" /></p>
	<p><label for="plurk-username" >Username: <input id="plurk-username" name="plurk-username" type="text" value="<?php echo $plurk_options['plurk-username']; ?>" size="5" /></p>
	<p><label for="plurk-counts" >Plurk counts: <input id="plurk-counts" name="plurk-counts" type="text" value="<?php echo $plurk_options['plurk-counts']; ?>" size="5" /> </p>
	<p><label for="plurk-link" >Show links: <input id="plurk-link" name="plurk-link" type="checkbox" value="true" <?echo $link_is_checked ?> /> </p>
	<p><label for="plurk-publish-time" >Show publish time: <input id="plurk-publish-time" name="plurk-publish-time" type="checkbox" value="true" <?echo $time_is_checked ?> /> </p>
	<p><label for="plurk-cache" >Cache Time: <input id="plurk-cache" name="plurk-cache" type="text" value="<?echo $plurk_options['plurk-cache']; ?>" size="5" /> set 0 to ignore cache</p>
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