<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(dirname(__FILE__)) . '/includes/hook.php');
require_once(dirname(dirname(__FILE__)) . '/includes/settings.php');

class AppConfig {

      function init() {

        if (isset($_GET['wiziapp_display']) && $_GET['wiziapp_display'] == 'getconfig') {
             if(wiziapp_plugin_settings()->getIosActive()){
				
				/**
				 * 	WPTouch compatibility
				 *
				 *  @author 1do
				 *  @date	6/12/2016
				 */
				if ( array_intersect( array( 'wptouch/wptouch.php', 'wptouch-pro/wptouch-pro.php' ), apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
					add_filter( 'wptouch_should_show_mobile_theme', '__return_false' );
				}
				
                add_action('roni_wiziapp_config', array($this,'print_config'),10,2);
             }
             else{
                wiziapp_plugin_hook()->json_output(array(
                    'rooturl' => trailingslashit(get_bloginfo('wpurl')),//  get_bloginfo('wpurl'),
                    'layouttype' => 'pushNavigation',
                    'header' => (object) array(),
                    'menu' => array()
                ));
             }
        }
    }

    function print_config($layoutType,$header){
       
            wiziapp_plugin_hook()->json_output($this->getConfiguration($layoutType,$header));
        
    }
    function getConfiguration($layoutType,$header) {
        return array('layouttype' => $layoutType,
            'rooturl' => trailingslashit(get_bloginfo('wpurl')),// get_bloginfo('wpurl'),
            'header' => $header,
            'menu' => $this->getMenu()
                );
    }

    function getMenu() {
        $menu_id = wiziapp_plugin_settings()->getIosMenu();
        $menu = wp_get_nav_menu_object($menu_id);
        $menu_items = wp_get_nav_menu_items($menu->term_id);

        $items = array();
        foreach ((array) $menu_items as $key => $menu_item) {
            $title = array_push($items, array('name' => $menu_item->title,
                //'icon'=>$menu_item->icon,
                'url' => $menu_item->url));
        }
        return $items;
    }

}

$module = new AppConfig();
$module->init();

function foo() {
    
}
