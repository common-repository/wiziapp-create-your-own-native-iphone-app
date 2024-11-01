<?php
	require_once(dirname(dirname(__FILE__)).'/includes/settings.php');
	require_once(dirname(dirname(__FILE__)).'/includes/hook.php');
	require_once(dirname(dirname(__FILE__)).'/includes/purchase_hook.php');

	class WiziappPluginModuleMonetization
	{
		function init()
		{
			$hook = new WiziappPluginPurchaseHook();
			$hook->hook('ads', '/build/ads', array(&$this, '_licensed'), array(&$this, '_analytics'));
                       
			$hook->hookExpiration(array(&$this, '_expiration'));
			wiziapp_plugin_hook()->hookLoadAdmin(array(&$this, 'loadAdmin'));
			wiziapp_plugin_hook()->hookLoad(array(&$this, 'load'));
			wiziapp_plugin_hook()->hookInstall( array( $this, 'installCheckLicense' ) ) ;
		}

		function _licensed($params, $license)
		{
			if ($license === false)
			{
				return;
			}
			require(dirname(dirname(__FILE__)).'/config.php');
			$siteurl = trailingslashit(get_bloginfo('wpurl'));
			$response = wp_remote_get($wiziapp_plugin_config['build_host'].'/build/ads/license/expiration?url='.urlencode($siteurl));
			if (!is_wp_error($response))
			{
				$res = json_decode($response['body'], true);
				if (is_array($res) && isset($res['expiration']))
				{
					wiziapp_plugin_settings()->setAdExpiration($res['expiration']);
?>
					<script type="text/javascript">
						if (window.parent && window.parent.jQuery) {
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization-body-buy").hide();
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-option-monetization_license-state-licensed").text(new Date(<?php echo json_encode($res['expiration']); ?>).toString());
							window.parent.jQuery("#wiziapp-plugin-admin-settings-box-monetization .wiziapp-plugin-admin-settings-box-option[data-wiziapp-plugin-admin-option-id=monetization_license]").show();
						}
						if (window.parent && window.parent.tb_remove) {
							window.parent.tb_remove();
						}
					</script>
<?php
				}
			}
		}

		public function getLicenseResponse() {
			require(dirname(dirname(__FILE__)).'/config.php');
			$siteurl = trailingslashit(get_bloginfo('wpurl'));
			$req = $wiziapp_plugin_config['build_host'].'/build/ads/license/expiration?url='.urlencode($siteurl).'&theme=global';
			return wp_remote_get($req);
		}

		function _expiration($expiration)
		{
			$response = $this->getLicenseResponse();
			$has_license = false;
			
			if (!is_wp_error($response))
			{
				$res = json_decode($response['body'], true);
				if (is_array($res) && isset($res['expiration']))
				{
					if ($expiration['expiration'] === false || ($res['expiration'] !== false || $res['expiration'] > $expiration['expiration']))
					{
						$expiration['expiration'] = $res['expiration'];
					}

					if ( $expiration['expiration'] != 'false' ) {
						$has_license = true;
					}
				}
			}
			wiziapp_plugin_settings()->setAdExpiration($expiration['expiration']);

			update_option( 'wiziapp_has_ad_license', $has_license, true );

			return $expiration;
		}

		function _analytics()
		{
			return '/ads/purchased';
		}

		function loadAdmin()
		{
			$expire = wiziapp_plugin_settings()->getAdExpiration();
			if ($expire !== false)
			{
				$expire = strtotime($expire)-time();
				if ($expire > 0 && $expire < 2592000)
				{
					add_action('admin_notices', array(&$this, '_expire_notice'));
				}
			}
		}

		function _expire_notice()
		{
?>
		<div class="error fade">
			<p style="line-height: 150%">
				<?php _e('The WiziApp Ad space license will expire in less than a month. To extend it for additional one year, please click the "Extend" button on the Wiziapp plugin - "Settings" - "Ad Space".', 'wiziapp-plugin'); ?>
			</p>
		</div>
<?php
		}

		public function load()
		{
			add_action( 'wp', array( $this, '_hook_on_search' ) );
		}

		public function _hook_on_search() {
			if ( is_search() && is_main_query() && ! $this->hasAdLicense() ) {
				add_action( 'loop_start', array( $this, '_print_sarcho_iframe' ) );
			}
		}

		private function hasAdLicense() {
			return get_option( 'wiziapp_has_ad_license', false );
		}

		public function _print_sarcho_iframe( WP_Query $query ) {
			// There could be multiple loops on the page
			// make sure we're inside the main query loop
			if ( ! $query->is_main_query() ) {
				return;
			}

			// Show only in mobile
			if ( ! wp_is_mobile() ) {
				return;
			}

			$term = trim( get_search_query() );

			if ( empty( $term ) ) {
				return;
			}

			$url = 'https://mobile.sarcho.com/wiziapp/?q=' . $term;

			ob_start();
			?>

				<div style="margin: 15px 0;">
					<iframe id="wiziapp-sarcho" src="<?php echo esc_url( $url ); ?>" frameborder="0" style="
						display: block;
						width: 100%;
						border: 0;
					"></iframe>
					<script>
					(function() {
						var iframe = document.getElementById('wiziapp-sarcho');
						if ( iframe ) {
							window.addEventListener('message', function(e) {
								if (!e.data.iframe) return;
								if (e.data.iframe == iframe.src) {
									iframe.style.height = e.data.height + 'px';
								}
							}, false);
						}
					})();
					</script>
				</div>

			<?php
			ob_get_flush();
		}

		public function installCheckLicense() {
			$response = $this->getLicenseResponse();
			$has_license = false;

			if ( ! is_wp_error( $response ) ) {
				$body = json_decode( $response['body'], true );
				if ( isset( $body['expiration'] ) && $body['expiration'] != 'false' ) {
					$has_license = true;
				}
			}

			update_option( 'wiziapp_has_ad_license', $has_license, true );
		}
	}

	$module = new WiziappPluginModuleMonetization();
	$module->init();
