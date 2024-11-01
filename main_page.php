<?php
/*
Plugin Name: WP Group List
Plugin URI: http://wpgroup.org/wp-group-list
Description: This is a plugin for link all WordPress Blog post to here in widget.
Author: Richer Yang
Version: 1.0.8
Author URI: http://fantasyworld.idv.tw/
*/

include_once(ABSPATH . WPINC . '/class-IXR.php');
include_once(ABSPATH . WPINC . '/class-wp-http-ixr-client.php');

function wgl_install()
{
	$wgl_xmlrpc = new wgl_xmlrpc;
	$blogid = $wgl_xmlrpc->get_blogid('wpgroup', 'DApcrJqwzG5X');
	add_option('WGL_config', array('wgl_name' => 'wpgroup', 'wgl_passwd' => 'DApcrJqwzG5X', 'share_num' => 200, 'blogid' => $blogid, 'is_comment' => 1));
}
register_activation_hook( __FILE__, 'wgl_install');

$wgl_config = get_option('WGL_config');
$currentLocale = get_locale();
if( !empty($currentLocale) )
{
	$moFile = dirname(__FILE__) . '/lang/' . $currentLocale . '.mo';
	if( @file_exists($moFile) && is_readable($moFile) )
	{
		load_textdomain('wgl', $moFile);
	}
}

if( is_admin() )
{
	require_once dirname( __FILE__ ) . '/admin.php';
}

function my_substr($text, $length)
{
	if( function_exists('mb_internal_encoding') )
	{
		mb_internal_encoding(get_bloginfo('charset'));
		if( mb_strlen($text) > $length )
		{
			return mb_substr($text, 0, $length) . ' ...';
		}
		else
		{
			return $text;
		}
	}
	else
	{
		if( strlen($text) > $length )
		{
			return substr($text, 0, $length) . ' ...';
		}
		else
		{
			return $text;
		}
	}
}

function wgl_newpost($post_id, $post_data)
{
	global $wgl_config;
	$wgl_xmlrpc = new wgl_xmlrpc;
	$post = array();	
	$post['title'] = $post_data->post_title;
	$post['description'] = strip_tags($post_data->post_content);
	$post['description'] = preg_replace('|\[\/?[^[\[]*]|si', '', $post['description']);
	$post['real_description'] = $post['description'];
	$post['description'] = my_substr($post['description'], $wgl_config['share_num']);
	$author = get_the_author_meta('display_name', $post_data->post_author);
	$post['description'] .= "\r\n\r\n" . __('Post By: ', 'wgl') .sprintf('<a href="%3$s">%1$s</a> @ <a href="%2$s" title="%1$s">%2$s</a>', $author, get_permalink($post_data->ID), get_bloginfo('wpurl'));
	$post['categories'] = array($author);
	$post['mt_tb_ping_urls'] = get_permalink($post_data->ID);
	$post['custom_fields'] = array(array('key' => 'wgl_link', 'value' => get_permalink($post_data->ID)), array('key' => 'wgl_host_id', 'value' => md5(get_bloginfo('wpurl'))));
	$post['mt_keywords'] = array();
	$cats = get_the_category($post_data->ID);
	if( isset($cats[0]) )
	{
		foreach( $cats AS $cat )
		{
			$post['mt_keywords'][] = $cat->cat_name;
		}
	}
	$tags = get_the_tags($post_data->ID);
	if( isset($tags[0]) )
	{
		foreach( $tags AS $tag )
		{
			$post['mt_keywords'][] = $tag->name;
		}
	}
	if( $wgl_config['is_comment'] == false )
	{
		$post['mt_allow_comments'] = 'closed';
	}
	$data = $wgl_xmlrpc->client_query('wgl.newPost', $wgl_config['blogid'], $wgl_config['wgl_name'], $wgl_config['wgl_passwd'], $post, true);
	return true;
}
add_action('publish_post', 'wgl_newpost', 9999, 2);

class wgl_xmlrpc extends WP_HTTP_IXR_Client
{
	private $client;
	private $request;
	
	function wgl_xmlrpc()
	{
		$this->WP_HTTP_IXR_Client('http://wpgroup.org/xmlrpc.php');
		$this->timeout = 5;
//		$this->debug = true;
	}
	function client_query()
	{
		$args = func_get_args();
		$method = array_shift($args);
		$this->query($method, $args);
		if( $this->message->faultCode == '' )
		{
			return $this->message->params;
		}
		return false;
	}
	function get_blogid($name, $passwd)
	{
		$this->query('wp.getUsersBlogs', $name, $passwd);
		if( isset($this->message->params[0][0]['blogid']) )
		{
			return intval($this->message->params[0][0]['blogid']);
		}
		return false;
	}
}

function wgl_widget_js()
{
	wp_enqueue_script('jquery');
	wp_enqueue_style('wgl.css', plugin_dir_url( __FILE__ ) . 'wgl.css');
}

class WP_WGL_Widget extends WP_Widget {
	function WP_WGL_Widget() {
		$widget_ops = array('description' => __('WordPress Group post', 'wgl'));
		$this->WP_Widget('wgl-widget', __('WGL post list', 'wgl'), $widget_ops);
		if( is_active_widget(false, false, 'wgl-widget') )
		{
			add_action('wp_head', 'wgl_widget_js', 1);
		}
	}
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __('WordPress Group post', 'wgl') : $instance['title'], $instance, $this->id_base);
		$maxnum = intval($instance['maxnum']);
		$upnum = intval($instance['upnum']);
		$num = intval($instance['num']);
		$samelang = $instance['samelang'] ? '1' : '0';
		echo($before_widget . $before_title . $title . $after_title);
		echo('<div id="marquee"><dl>');
		for( $i = 1; $i <= $maxnum; $i++ )
		{
			echo('<dd id="wgl_' .  $i . '"></dd>');
		}
		echo('</dl></div>' . $after_widget);
		?>
		<script type="text/javascript">
		var _ajax=true,_now=<?php echo($upnum); ?>,_dff=0,_height=new Array(<?php echo($maxnum+1); ?>);
		jQuery.ajax({type:'GET',url:'http://wpgroup.org/wgl_ajax.php?n=<?php echo($maxnum); ?>&id=<?php echo(md5(get_bloginfo('wpurl'))); ?>&sl=<?php echo($samelang); ?>',cache:false,dataType:'script',beforeSend:function(xhr){xhr.setRequestHeader('Accept','text/javascript');},success:function(){_height[0]=0;for(i=1;i<=<?php echo($maxnum); ?>;i++){if(_data[i]!='undefined'){jQuery('#wgl_'+i).html(_data[i]);_height[i]=_height[i-1]+jQuery('#wgl_'+i).outerHeight(true);}else{_height[i]=height[i-1]+0;}}for(i=1;i<=<?php echo($maxnum); ?>;i++){_height[i+<?php echo($maxnum); ?>]=_height[<?php echo($maxnum); ?>]+_height[i];}jQuery('#marquee dl').append(jQuery('#marquee dl').html());jQuery('#marquee').height(_height[<?php echo($num); ?>]);_ajax=false;}});
		jQuery('#marquee').hover(function(){clearInterval(MyMar);},function(){MyMar=setInterval(fMarquee,3000);});var MyMar=setInterval(fMarquee,3000);
		function fMarquee(){if(_ajax){return false;}jQuery('#marquee dl').animate({'top':-_height[_now]},800,function(){if(_now>=<?php echo($maxnum); ?>){_now-=<?php echo($maxnum); ?>;jQuery('#marquee dl').css({'top':-_height[_now]});}_now+=<?php echo($upnum); ?>;});}
		</script>
		<?php
	}
	function update($new_instance, $old_instance) {
		if( !isset($new_instance['submit']) ) {
			return false;
		}
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['maxnum'] = intval($new_instance['maxnum']);
		$instance['upnum'] = intval($new_instance['upnum']);
		$instance['num'] = intval($new_instance['num']);
		$instance['samelang'] = !empty($new_instance['samelang']) ? 1 : 0;
		return $instance;
	}
	function form($instance) {
		global $wpdb, $wgl_config;
		$instance = wp_parse_args((array) $instance, array('title' => '', 'maxnum' => 20, 'num' => 5, 'upnum' => 1));
		$title = esc_attr($instance['title']);
		$maxnum = intval($instance['maxnum']);
		$upnum = intval($instance['upnum']);
		$num = intval($instance['num']);
		$samelang = isset($instance['samelang']) ? (bool) $instance['samelang'] : false;
		?>
		<p>
			<label for="<?php echo($this->get_field_id('title')); ?>"><?php _e('Title:', 'wgl'); ?></label> <input class="widefat" id="<?php echo($this->get_field_id('title')); ?>" name="<?php echo($this->get_field_name('title')); ?>" type="text" value="<?php echo($title); ?>" />
		</p>
		<p>
			<label for="<?php echo($this->get_field_id('maxnum')); ?>"><?php _e('Total Posts:', 'wgl'); ?></label> <input id="<?php echo($this->get_field_id('maxnum')); ?>" name="<?php echo($this->get_field_name('maxnum')); ?>" type="text" value="<?php echo($maxnum); ?>" size="3" /><br />
			<label for="<?php echo($this->get_field_id('upnum')); ?>"><?php _e('Winding Posts:', 'wgl'); ?></label> <input id="<?php echo($this->get_field_id('upnum')); ?>" name="<?php echo($this->get_field_name('upnum')); ?>" type="text" value="<?php echo($upnum); ?>" size="3" /><br />
			<label for="<?php echo($this->get_field_id('num')); ?>"><?php _e('Show Posts:', 'wgl'); ?></label> <input id="<?php echo($this->get_field_id('num')); ?>" name="<?php echo($this->get_field_name('num')); ?>" type="text" value="<?php echo($num); ?>" size="3" />
		</p>
		<?php if( isset($wgl_config['add_list']) && $wgl_config['add_list'] > 0 ) { ?>
			<input id="<?php echo($this->get_field_id('samelang')); ?>" name="<?php echo($this->get_field_name('samelang')); ?>" class="checkbox" type="checkbox" <?php checked($samelang); ?> /> <label for="<?php echo($this->get_field_id('samelang')); ?>"><?php _e('Show only with the language.', 'wgl'); ?></label>
		<?php } ?>
		<input type="hidden" id="<?php echo($this->get_field_id('submit')); ?>" name="<?php echo($this->get_field_name('submit')); ?>" value="1" />
		<?php
	}
}
function wgl_widget_views_init() {
	register_widget('WP_WGL_Widget');
}
add_action('widgets_init', 'wgl_widget_views_init');
?>