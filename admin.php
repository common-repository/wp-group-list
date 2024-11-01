<?php
function wgl_action_links($links, $file)
{
	if( $file == plugin_basename(dirname(__FILE__) . '/main_page.php' ) )
	{
		$links[] = '<a href="options-general.php?page=wgl-config">' . __('Settings', 'wgl') . '</a>';
	}
	return $links;
}
add_filter('plugin_action_links', 'wgl_action_links', 10, 2);

function wgl_conf_set()
{
	global $wgl_config;
	$temp_config = $wgl_config;
	if( !is_active_widget(false, false, 'wgl-widget') )
	{
		echo('<div id="message" class="error fade"><p><strong>' . __('Please add the "WGL post" widget.', 'wgl') . '</strong></p></div>');
	}
	if( !empty($_POST['submit']) )
	{
		$error_message = array();
		$temp_config['share_num'] = intval($_POST['share_num']);
		if( $temp_config['share_num'] < 50 )
		{
			$temp_config['share_num'] = 50;
		}
		$temp_config['is_comment'] = false;
		if( intval($_POST['is_comment']) == 1 )
		{
			$temp_config['is_comment'] = true;
		}
		$wgl_name = stripslashes(strip_tags($_POST['wgl_name']));
		$wgl_passwd = stripslashes(strip_tags($_POST['wgl_passwd']));
		if( $wgl_name != '' && $wgl_passwd != '' )
		{
			$wgl_name = stripslashes(strip_tags($_POST['wgl_name']));
			$wgl_passwd = stripslashes(strip_tags($_POST['wgl_passwd']));
			$wgl_xmlrpc = new wgl_xmlrpc;
			$blogid = $wgl_xmlrpc->get_blogid($wgl_name, $wgl_passwd);
			if( $blogid )
			{
				$temp_config['wgl_name'] = $wgl_name;
				$temp_config['wgl_passwd'] = $wgl_passwd;
				$temp_config['blogid'] = $blogid;
			}
			else
			{
				$error_message[] = __('Bad login/pass combination.', 'wgl');
			}
		}
		if( isset($error_message[0]) )
		{
			echo('<div id="message" class="error fade"><p>' . implode('<br />', $error_message) . '<br />' . __('All setting are not change!', 'wgl') . '</p></div>');
		}
		else if( $temp_config != $wgl_config )
		{
			update_option('WGL_config', $temp_config);
			$wgl_config = $temp_config;
			echo('<div id="message" class="updated fade"><p><strong>' . __('Options saved.', 'wgl') . '</strong></p></div>');
		}
	}
	if( !empty($_POST['add_list']) )
	{
		$data = array();
		$data['host_id'] = md5(get_bloginfo('wpurl'));
		$data['host_title'] = get_bloginfo('name');
		$data['host_url'] = get_bloginfo('wpurl');
		$data['host_desc'] = get_bloginfo('description');
		if( defined('WPLANG') )
		{
			$data['host_lang'] = WPLANG;
		}
		else
		{
			$data['host_lang'] = 'en_US';
		}
		$wgl_xmlrpc = new wgl_xmlrpc;
		if( $wgl_xmlrpc->client_query('wgl.addList', $data) )
		{
			echo('<div id="message" class="updated fade"><p><strong>' . __('Add in list success.', 'wgl') . '</strong></p></div>');
			$wgl_config['add_list'] = time();
			update_option('WGL_config', $wgl_config);
		}
		
	}
	if( !empty($_POST['update_list']) )
	{
		$data = array();
		$data['host_id'] = md5(get_bloginfo('wpurl'));
		$data['host_title'] = get_bloginfo('name');
		$data['host_url'] = get_bloginfo('wpurl');
		$data['host_desc'] = get_bloginfo('description');
		if( defined('WPLANG') )
		{
			$data['host_lang'] = WPLANG;
		}
		else
		{
			$data['host_lang'] = 'en_US';
		}
		$wgl_xmlrpc = new wgl_xmlrpc;
		if( $wgl_xmlrpc->client_query('wgl.addList', $data) )
		{
			echo('<div id="message" class="updated fade"><p><strong>' . __('Update data success.', 'wgl') . '</strong></p></div>');
		}
		
	}
	if( !isset($wgl_config['add_list']) )
	{
		echo('<div id="message" class="updated fade"><p><strong>' . __('I suggest you join the WordPress Group list, in order to have better communication.', 'wgl') . '</strong></p></div>');
	}
	?>
<div class="wrap">
	<h2><?php _e('WP Group List setting', 'wgl'); ?></h2>
	<form method="post" action="">
	<h3><?php _e('Share Setting', 'wgl'); ?></h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('The number of words sharing the post', 'wgl'); ?></th>
			<td><select name="share_num">
			<option value="50" <?php echo(($wgl_config['share_num'] == 50) ? 'selected="selected"' : ''); ?>>50</option>
			<option value="100" <?php echo(($wgl_config['share_num'] == 100) ? 'selected="selected"' : ''); ?>>100</option>
			<option value="200" <?php echo(($wgl_config['share_num'] == 200) ? 'selected="selected"' : ''); ?>>200</option>
			<option value="350" <?php echo(($wgl_config['share_num'] == 350) ? 'selected="selected"' : ''); ?>>350</option>
			<option value="500" <?php echo(($wgl_config['share_num'] == 500) ? 'selected="selected"' : ''); ?>>500</option>
			<option value="800" <?php echo(($wgl_config['share_num'] == 800) ? 'selected="selected"' : ''); ?>>800</option>
		</select></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('The sharing post comment?', 'wgl'); ?></th>
			<td><select name="is_comment">
			<option value="0" <?php echo(($wgl_config['is_comment'] == 0) ? 'selected="selected"' : ''); ?>><?php _e('disallow', 'wgl'); ?></option>
			<option value="1" <?php echo(($wgl_config['is_comment'] == 1) ? 'selected="selected"' : ''); ?>><?php _e('allow', 'wgl'); ?></option>
		</select></td>
		</tr>
	</table>
	<h3><?php _e('XML-RPC Setting', 'wgl'); ?></h3>
	<p><?php _e('You can use the default username and password, or goto <a href="http://wpgroup.org">WordPress Group</a> signup you own username/password', 'wgl'); ?></p>
	<p><?php _e('Login account you are currently using is:', 'wgl'); echo(($wgl_config['wgl_name']=='wpgroup') ? __('default', 'wgl') : $wgl_config['wgl_name']); ?>
	</p>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Login username', 'wgl');?></th>
			<td><input name="wgl_name" id="wgl_name" type="text" /><span class="description"><?php _e('If you want to change the account password, please enter a new username and password. Otherwise blank!', 'wgl'); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('login password', 'wgl');?></th>
			<td><input name="wgl_passwd" id="wgl_name" type="password" /><span class="description"><?php _e('If you want to change the account password, please enter a new username and password. Otherwise blank!', 'wgl'); ?></span></td>
		</tr>
	</table>
	<?php submit_button(); ?>
	<h3><?php _e('Add in list', 'wgl'); ?></h3>
	<?php
	if( isset($wgl_config['add_list']) && $wgl_config['add_list'] > 0 )
	{
		_e('Add in the list at', 'wgl');
		echo(' ' . date(get_option('links_updated_date_format'), $wgl_config['add_list']));
		submit_button(__('Update data', 'wgl'), 'primary', 'update_list');
	}
	else
	{
		submit_button(__('Add in list', 'wgl'), 'primary', 'add_list');
	}?>
	</form>
</div>
	<?php
}

function wgl_config_page()
{
	add_submenu_page('options-general.php', 'WP group list', 'WP group list', 'manage_options', 'wgl-config', 'wgl_conf_set');
}
add_action('admin_menu', 'wgl_config_page');
?>