<?php
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');
	require_once(dirname(dirname(__FILE__)).'/includes/menus.php');

	class WiziappPluginModuleSwitcher
	{
		var $template;
		var $stylesheet;
		var $hooks_get_theme = array();
		var $hooks_theme_customization = array();
		var $hooked_root = false;
		var $extras = array();

		/**
		* @author refael
		* @since 4.2.4 Is viewing using customizer?
		*/
		public $is_customizer_view = false;

		function init()
		{
			global $wp_version;

			if( version_compare( $wp_version, '4.7', '>=' ) ) {

				/**
				* @author refael
				* @since 4.2 Invoke functions without wiziapp_plugin_hook
				*            For wordpress 4.7 customizer compatibility.
				*/
				if( isset( $_REQUEST['customize_theme'] ) ) {
					/**
					* When in front customizer
					* invoke without wiziapp_plugin_hook
					*/
					$this->is_customizer_view = true;
					$this->load();
				} else {
					/**
					* When in front without customizer
					* Invoke in wiziapp_plugin_hook
					*/
					wiziapp_plugin_hook()->hookLoad(array($this, 'load'));
				}

				/**
				* Invoke without wiziapp_plugin_hook
				*/
				if( is_admin() || isset( $_REQUEST['customize_theme'] ) ) {
					$this->loadAdmin();
				}

			} else {

				wiziapp_plugin_hook()->hookLoad(array($this, 'load'));
				wiziapp_plugin_hook()->hookLoadAdmin(array($this, 'loadAdmin'));

			}
		}

		function hookGetTheme($cb)
		{
			$this->hooks_get_theme[] = $cb;
		}

		function hookThemeCustomization($cb)
		{
			$this->hooks_theme_customization[] = $cb;
		}

		function load()
		{
			if (isset($GLOBALS['wp_customize']))
			{
				$setting = new WP_Customize_Setting($GLOBALS['wp_customize'], 'wiziapp_plugin', array('default' => 'customize', 'type' => 'constant'));
				if ($setting->post_value() === 'customize')
				{
					$GLOBALS['wp_customize']->add_setting($setting);
					$this->_hook_root();
					add_filter('theme_root_uri', array(&$this, 'theme_root_uri'), 99);
					add_action('start_previewing_theme', array(&$this, 'set_wiziapp_theme_menu'), 1);
					foreach ($this->hooks_theme_customization as $cb)
					{
						call_user_func($cb);
					}
					return;
				}
			}
			if (isset($_GET['wiziapp_display']) && $_GET['wiziapp_display'] === 'none')
			{
				// Make sure the parameter survives implicit redirects
				add_filter('query_vars', array(&$this, '_query_vars'));
				return;
			}

			/**
			* @author refael
			* @since 4.2.4 when in customizer, we should call loop_hooks_get_theme() a little bit later
			*/
			if ( $this->is_customizer_view ) {
				add_action( 'setup_theme', array( $this, 'loop_hooks_get_theme' ), 9 );
			} else {
				$this->loop_hooks_get_theme();
			}

		}

		/**
		* @since 4.2.4
		*/
		function loop_hooks_get_theme() {

			foreach ( $this->hooks_get_theme as $cb ) {
				$theme = call_user_func($cb);

				if ( empty( $theme ) || empty( $theme['theme'] ) ) {
					
					if ( isset( $theme['theme'] ) && $this->is_customizer_view ) {

			 			/**
						* @author refael
						* @since 4.2.4 Check for theme name in customizer
						*/
						if ( isset( $_REQUEST['theme'] ) ) {
							$theme['theme'] = $_REQUEST['theme'];
						} elseif ( isset( $_REQUEST['customize_theme'] ) ) {
							$theme['theme'] = $_REQUEST['customize_theme'];
						} else {
							continue;
						}

					} else {
						continue;
					}

				}

				$this->_hook_root();

				if ( ! ( $parent = $this->_theme_get_parent( $theme['theme'] ) ) ) {
					continue;
				}

				if ( isset( $theme['menu'] ) ) {
					wiziapp_plugin_menus()->setMenu($theme['menu']);
				}

				if ( isset( $theme['head'] ) ) {
					add_action( 'wp_head', $theme['head'] );
				}

				$this->extras = isset( $theme['extras'] ) ? $theme['extras'] :array();
				$this->stylesheet = $theme['theme'];
				$this->template = $parent;

				add_filter( 'theme_root_uri', array( &$this, 'theme_root_uri' ), 99);
				add_filter( 'template', array( &$this, 'template' ), 99 );
				add_filter( 'stylesheet', array( &$this, 'stylesheet' ), 99 );

				$this->themeSwitched();

				return;
			}

			$this->_unhook_root();

		}

		function loadAdmin()
		{
			/**
			* @author refael
			* @since 4.2 Added: && !isset( $_REQUEST['customize_theme'] )
			*            This in order to fix the 'Cheating?' error.
			*/
			if (($GLOBALS['pagenow'] !== 'customize.php' || !isset($_GET['wiziapp_plugin']) || $_GET['wiziapp_plugin'] !== 'customize') && $GLOBALS['pagenow'] !== 'theme-editor.php' && !isset( $_REQUEST['customize_theme'] ) )
			{
				if ($GLOBALS['pagenow'] !== 'admin-ajax.php' || $_REQUEST['action'] !== 'customize_save' || !isset($GLOBALS['wp_customize']))
				{
					return;
				}
				$setting = new WP_Customize_Setting($GLOBALS['wp_customize'], 'wiziapp_plugin', array('default' => 'customize', 'type' => 'constant'));
				if ($setting->post_value() !== 'customize')
				{
					return;
				}
			}
			if ($GLOBALS['pagenow'] === 'customize.php' && isset($GLOBALS['wp_customize']))
			{
				$GLOBALS['wp_customize']->add_setting('wiziapp_plugin', array('default' => 'customize', 'type' => 'constant'));
				if (!empty($_REQUEST['wiziapp_theme_menu']))
				{
					$GLOBALS['wp_customize']->add_setting('wiziapp_theme_menu', array('default' => $_REQUEST['wiziapp_theme_menu']));
				}
			}

 			/**
			* @author refael
			* @since 4.2 Check for customize_theme
			*            to avoid the 'Cheating?' error.
			*/
			if ( isset( $_REQUEST['theme'] ) ) {
				$theme = $_REQUEST['theme'];
			} elseif ( isset( $_REQUEST['customize_theme'] ) ) {
				$theme = $_REQUEST['customize_theme'];
			} else {
				return;
			}

			if (!array_key_exists($theme, $this->get_themes()))
			{
				return;
			}
			$this->_hook_root();
			add_filter('theme_root_uri', array(&$this, 'theme_root_uri'), 99);
			if ($theme && ($parent = $this->_theme_get_parent($theme)))
			{
				$this->stylesheet = $theme;
				$this->template = $parent;
				add_filter('template', array(&$this, 'template'), 99);
				add_filter('stylesheet', array(&$this, 'stylesheet'), 99);

				/**
				* @author refael
				* @since 4.2
				*/
				if( version_compare( $GLOBALS['wp_version'], '4.7', '>=' ) ) {

					// After WP_Customize_Manager::customize_pane_settings()
					add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_settings' ), 1001 );

					// After WP_Customize_Manager::customize_preview_settings()
					add_action( 'wp_footer', array( $this, 'customize_settings' ), 21 );

				}

				$this->themeSwitched();
			}
		}
		
		/**
		* Make the customizer think the current wizi theme
		* is not an active theme.
		* This is needed for force passing the theme name as parameter in requests.
		* 
		* @author refael
		* @since 4.2
		*/
		function customize_settings() {
			?>
			<script type="text/javascript">
			/**
			* Set as not active
			*/
			if( typeof _wpCustomizeSettings !== 'undefined' ) {
				_wpCustomizeSettings.theme.active = false;
			}
			
			/**
			* Due to the inactive state, the button will say 'save & activate'
			* This will change it back to 'save & publish'
			*/
			if( typeof _wpCustomizeControlsL10n !== 'undefined' ) {
				_wpCustomizeControlsL10n.activate = _wpCustomizeControlsL10n.save;
			}
			</script>
			<?php
		}

		function getExtra($key, $default = false)
		{
			return isset($this->extras[$key])?$this->extras[$key]:$default;
		}

		function wiziapp_theme_settings_name($name)
		{
			return 'wiziapp_plugin_'.$name;
		}

		function theme_root()
		{
			return dirname(dirname(__FILE__)).'/themes';
		}

		function theme_root_uri()
		{
			return wiziapp_plugin_hook()->plugins_url('/themes');
		}

		function template()
		{
			return $this->template;
		}

		function stylesheet()
		{
			return $this->stylesheet;
		}

		function get_themes($titles_only = true)
		{
			$ret = array();
			$this->_hook_root();
			if (function_exists('wp_get_themes'))
			{
				$themes = wp_get_themes();
				foreach ($themes as $theme => $data)
				{
                                    $ret[$theme] = $titles_only?$data['Title']:$data;
				}
			}
			else
			{
				$themes = get_themes();
				foreach ($themes as $theme => $data)
				{
					$ret[$data['Stylesheet']] = $titles_only?$data['Title']:$data;
				}
			}
			$this->_unhook_root();
			return $ret;
		}

		function get_theme($theme)
		{
			$hook = !$this->hooked_root;
			if ($hook)
			{
				$this->_hook_root();
			}
			if (function_exists('wp_get_theme'))
			{
				$theme_data = wp_get_theme($theme);
				if (!$theme_data->exists())
				{
					$theme_data = array();
				}
			}
			else
			{
				$theme_data = get_theme_data($theme);
				if (!$theme_data)
				{
					$theme_data = array();
				}
			}
			if ($hook)
			{
				$this->_unhook_root();
			}
			return $theme_data;
		}

		function get_theme_title($theme)
		{
			$hook = !$this->hooked_root;
			if ($hook)
			{
				$this->_hook_root();
			}
			if (function_exists('wp_get_theme'))
			{
				$theme_data = wp_get_theme($theme);
				if (!$theme_data->exists())
				{
					$theme_data = array();
				}
			}
			else
			{
				$theme_data = get_theme_data($theme);
				if (!$theme_data)
				{
					$theme_data = array();
				}
			}
			if ($hook)
			{
				$this->_unhook_root();
			}
			return isset($theme_data['Title'])?$theme_data['Title']:false;
		}

		function _theme_get_parent($theme)
		{
			$theme_root = get_theme_root($theme);
			$theme_dir = "$theme_root/$theme";
			if (!file_exists($theme_dir.'/style.css'))
			{
				return false;
			}

			if (function_exists('wp_get_theme'))
			{
				$theme_data = wp_get_theme($theme);
				if (!$theme_data->exists())
				{
					return false;
				}
				$parent = $theme_data->get_template();
			}
			else
			{
				$theme_data = get_theme_data($theme);
				if (!$theme_data)
				{
					return false;
				}
				$parent = isset($theme_data['Template'])?$theme_data['Template']:false;
			}
			if (!$parent)
			{
				$parent = $theme;
			}

			if ($parent === $theme)
			{
				$parent_dir = $theme_dir;
			}
			else
			{
				$parent_root = get_theme_root($parent);
				$parent_dir = "$parent_root/$parent";
				if (!file_exists($parent_dir.'/style.css'))
				{
					return false;
				}
			}

			if (!file_exists($parent_dir.'/index.php'))
			{
				return false;
			}

			return $parent;
		}

		function _hook_root()
		{
			global $wp_theme_directories;
			if ($this->hooked_root)
			{
				return;
			}
			$this->hooked_root = array('hooked' => true);
			if (isset($wp_theme_directories))
			{
				$this->hooked_root['wp_theme_directories'] = $wp_theme_directories;
			}
			add_filter('wiziapp_theme_settings_name', array(&$this, 'wiziapp_theme_settings_name'), 99);
			add_filter('theme_root', array(&$this, 'theme_root'), 99);
			// Ugly hack: We set the theme root twice, so that it doesn't assume the single available root is the default root
			$wp_theme_directories = array($this->theme_root(), $this->theme_root());
			if (function_exists('search_theme_directories'))
			{
				search_theme_directories(true);
			}
		}

		function _unhook_root()
		{
			global $wp_theme_directories;
			if (!$this->hooked_root)
			{
				return;
			}
			if (isset($this->hooked_root['wp_theme_directories']))
			{
				$wp_theme_directories = $this->hooked_root['wp_theme_directories'];
			}
			else
			{
				unset($wp_theme_directories);
			}
			remove_filter('wiziapp_theme_settings_name', array(&$this, 'wiziapp_theme_settings_name'), 99);
			remove_filter('theme_root', array(&$this, 'theme_root'), 99);
			$this->hooked_root = false;
			if (function_exists('search_theme_directories'))
			{
				search_theme_directories(true);
			}
		}

		function set_wiziapp_theme_menu($wp_customize)
		{
			$setting = new WP_Customize_Setting($wp_customize, 'wiziapp_theme_menu', array('default' => ''));
			$menu = $setting->post_value();
			$wp_customize->add_setting($setting);
			if (!empty($menu))
			{
				wiziapp_plugin_menus()->setMenu($menu);
			}
		}

		function themeSwitched()
		{
			// FIXME: Insert aspect here?
			add_filter('the_content', array(&$this, '_ad_iframe'));
		}

		function _ad_iframe($content)
		{
			// FIXME: Refactor this to a separate module
			if (!is_single())
			{
				return $content;
			}
			$iframe = '';
			$final = '';
			if (wiziapp_plugin_settings()->getAdIFrameUrl())
			{
				$iframe = '<iframe class="wiziapp-no-wrap" style=\"border:none\" src="'.esc_attr(wiziapp_plugin_settings()->getAdIFrameUrl()).'" width="'.esc_attr(wiziapp_plugin_settings()->getAdIFrameWidth()).'" height="'.esc_attr(wiziapp_plugin_settings()->getAdIFrameHeight()).'"></iframe>';
			}
			return $iframe.$content.$iframe.$final;
		}

		function _query_vars($qvars)
		{
			$qvars[] = 'wiziapp_display';
			return $qvars;
		}
	}

	function &wiziapp_plugin_module_switcher()
	{
		static $inst = null;
		if (!$inst)
		{
			$inst = new WiziappPluginModuleSwitcher();
			$inst->init();
		}
		return $inst;
	}

	wiziapp_plugin_module_switcher();
