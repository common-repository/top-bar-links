<?php
/*
Plugin Name: Top Bar Links
Description: It adds quick custom links to the admin top bar
Author: Jose Mortellaro
Author URI: https://josemortellaro.com
Text Domain: eos-quil
Domain Path: /languages/
Version: 1.0.6
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if( is_admin() ){
	define( 'EOS_QUICK_LINKS_VERSION','1.0.6' );
	define( 'EOS_QUICK_LINKS_URL',untrailingslashit( plugins_url( '', __FILE__ ) ) );
	define( 'EOS_QUICK_LINKS_DIR',untrailingslashit( dirname( __FILE__ ) ) );
	define( 'EOS_QUICK_LINKS_BASE_NAME',untrailingslashit( plugin_basename( __FILE__ ) ) );
	require_once EOS_QUICK_LINKS_DIR.'/admin/ql-admin.php';
	if( defined( 'DOING_AJAX' ) && DOING_AJAX ){
		require_once EOS_QUICK_LINKS_DIR.'/admin/ql-ajax.php';
	}	
	add_action( 'after_setup_theme', 'eos_quil_menu_location' );
}
else{
	add_filter( 'wp_nav_menu_args','eos_quil_exclude_on_frontend',10,2 );
}

//Add admin top bar location
function eos_quil_menu_location(){
	register_nav_menus( array(
		 'eos_quil_top_bar' => esc_html__( 'Admin Top Bar, it will be displayed on the admin top bar','top-bar-links' ),
	) );
}

//Prevent Top Bar Links called on front end
function eos_quil_exclude_on_frontend( $args ){
	if( isset( $args['theme_location'] ) && ( '' === $args['theme_location'] || 'eos_quil_top_bar' === $args['theme_location'] ) ){
		$locations = get_nav_menu_locations();
		if( $locations ){
			if( isset( $locations['eos_quil_top_bar'] ) ){
				unset( $locations['eos_quil_top_bar'] );
			}
			$keys = array_keys( $locations ); 
			$args['theme_location'] = isset( $keys[0] ) ? $keys[0] : '';
		}
	}
	return $args;
}

add_action( 'admin_bar_menu','eos_quick_links_admin_menu',40 );
// Add custom links to the admin top  bar
function eos_quick_links_admin_menu( $wp_admin_bar ) {
	if( ! apply_filters( 'top_bar_links_show_on_frontend', false ) && ! is_admin() ) return $wp_admin_bar;
	$current_user = wp_get_current_user();
	$roles = $current_user->roles;
	$opts = get_site_option( 'eos_quil' );
	$who_can = isset( $opts['who_can'] ) ? $opts['who_can'] : apply_filters( 'eos_quil_who_can_see','administrator' );
	$current_caps = array();
	$can_role = get_role( $who_can );
	$can_caps = $can_role->capabilities;
	foreach( $roles as $role_slug ){
		$role = get_role( $role_slug );
		foreach( $role->capabilities as $key => $v ){
			if( strpos( $key,'/' ) > 0 ){
				$keyArr = explode( '/',$key );
				$key = $keyArr[0];
			}
			$current_caps[$key] = $v;
		}
	}
	$current_caps = array_keys( array_filter( $current_caps,'strlen' ) );
	$can_caps = array_keys( array_filter( $can_caps,'strlen' ) );
	$user_can = true;
	foreach( $can_caps as $cap ){
		$capArr = explode( '/',$cap );
		$cap = $capArr[0];			
		if( !in_array( $cap,$current_caps ) ){
			$user_can = false;
			break;
		}
	}
	if( !$user_can ) return;
	$menu = false;
    $locations = get_nav_menu_locations();
    if ( $locations && isset( $locations['eos_quil_top_bar'] ) ) {
        $menu = wp_get_nav_menu_object( $locations['eos_quil_top_bar'] );
    }
	if( !$menu ){
		$wp_admin_bar->add_menu( array(
			'id'    => 'eos-quil-0',
			'title' => esc_html__( 'Add Menu','top-bar-links' ),
			'href' => esc_url( eos_quil_get_settings_url() ),
			'meta' => array( 'title' => esc_html__( 'Add Top Bar Menu','top-bar-links' ) )
		));		
		return $wp_admin_bar;
	}
	$items = wp_get_nav_menu_items( $menu );
	$wp_admin_bar->add_menu( array(
		'id'    => 'eos-quil-0',
		'title' => esc_html( $menu->name ),
	));
	foreach( $items as $item ){
		$target = isset( $item->target ) && '' !== $item->target ? $item->target : '_self';
		$title = isset( $item->attr_title ) ? $item->attr_title : '';
		$class = isset( $item->classes ) && is_array( $item->classes ) ? implode( ' ',$item->classes ) : '';
		$args = array(
			'parent' => 'eos-quil-'.esc_attr( $item->menu_item_parent ),
			'id'     => 'eos-quil-'.esc_attr( $item->ID ),
			'title'  => esc_html( $item->title ),
			'href' => esc_url( $item->url ),
			'meta' => array( 'target' => esc_attr( $target ),'title' => esc_attr( $title ),'class' => esc_attr( $class ) )
		);
		$wp_admin_bar->add_node( $args );
	}
	return $wp_admin_bar;
}