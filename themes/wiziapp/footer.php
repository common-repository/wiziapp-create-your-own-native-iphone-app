			</div>
<?php
$a = wiziapp_theme_is_in_plugin() ;
$b =  wiziapp_plugin_settings()->getAdFooter();
$c = wiziapp_plugin_module_switcher()->getExtra('no_ads');
$d = isset( $_POST['wp_customize'] ) && $_POST['wp_customize'] === 'on';
$ad = $a && $b && !$c && !$d;
	if ($ad)
	{
?>

        <script type="text/javascript" src="https://mobile.sarcho.com/layer/sarcho.js"></script>

        <?php
	}
	switch (wiziapp_theme_settings()->getMenuType())
	{
		case 'popup':
		case 'panel':
			$display = (wiziapp_theme_settings()->getMenuType() == 'popup')?'overlay':'push';
?>
			<div data-role="panel" id="left-panel" class="wiziapp-panel" data-display="<?php echo esc_attr($display); ?>" data-position-fixed="true">
<?php
			if (has_nav_menu('wiziapp_custom'))
			{
				wp_nav_menu(array(
					'theme_location' => 'wiziapp_custom',
					'container' => false,
					'items_wrap' => '<ul data-role="listview" id="%1$s" class="%2$s">%3$s</ul>',
					'container' => '',
					'fallback_cb' => ''
				));
			}
			else
			{
?>
				<ul data-role="listview">
<?php
					$count = wiziapp_theme_settings()->getMenuItemCount();
					for ($i = 0; $i < $count; $i++)
					{
						echo wiziapp_theme_get_menu_item($i);
					}
?>
				</ul>
<?php
			}
?>
				<div class="wiziapp-menu-links">
					<p>
						Wordpress mobile theme by <a href="http://www.wiziapp.com/" target="_blank" data-rel="external">WiziApp</a>
					</p>
<?php
		if (wiziapp_theme_is_in_plugin())
		{
			foreach (wiziapp_plugin_module_switcher()->getExtra('links', array()) as $title => $link)
			{
?>
					<p>
						<a href="<?php echo $link; ?>" data-ajax="false"><?php echo $title; ?></a>
					</p>
<?php
			}
		}
?>
				</div>
			</div>
<?php
			break;
	}
?>

			<!-- @todo This WP feature is not implemented yet -->
			<div data-role="popup" data-overlay-theme="a" data-position-to="window" data-tolerance="15,15">
				<?php dynamic_sidebar(-1); ?>
			</div>
<?php
	do_action('wiziapp_theme_customized_style');
?>
		</div>
<?php
	wp_footer();
?>
	</body>
</html>