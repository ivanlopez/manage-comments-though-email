<?php 

/*
Plugin Name: Mange Comments Through Email
Plugin URI: http://IvanLopezDeveloper.com/
Description: Easily mange your blog comments directly from your email. Quickly approve, reply, mark as span or trash any comment without ever having to leave your inbox.
Version: 0.1
Author: Ivan Lopez
Author URI: http://IvanLopezDeveloper.com/
*/

/**
 * Copyright (c) 2014 Ivan Lopez. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

/**
* Initiate Comment Class
*/
class Mange_Comments_Through_Email
{
	
	function __construct()
	{
		add_action( 'admin_init', array($this, 'init_admin') );
	}

	/**
	 * Init Plugin
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function init_plugin()
	{

	}

	/**
	 * Init Plugin Backend
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function init_admin()
	{
		add_settings_section(
			'mcte_api_section',
			__( 'Mailgun API', 'mcte' ),
			array( $this, 'generate_api_section' ),
			'discussion'
		);
	 	
	 	add_settings_field(
			'mcte_api_key',
			__( 'API Key', 'mcte' ),
			array( $this, 'generate_api_key_field' ),
			'discussion',
			'mcte_api_section'
		);

		register_setting( 'discussion', 'mcte_setting' , array($this, 'sanitize_api_key') );
	}

	/**
	 * Wistia API Section
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function generate_api_section() {
	 	echo '<p>'.  __( 'You can create and account and get your API key from <a href="htpp://Mailgun.com" target="_blank">Mailgun.com</a>.', 'mcte' ) .'</p>';
	}

	/**
	 * Register API field
	 * Genereate API field
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function generate_domain_field() {
		$settings = (array) get_option( 'mcte_setting' );
		$domain_name = isset($settings['domain_name']) ? esc_attr( $settings['domain_name'] ) : '' ;
	 	echo '<input name="mcte_setting[domain_name]" id="wev_domain_name" type="text"  class="regular-text"  value="' . $domain_name . '" /> ';
	}

	/**
	 * Generate domain name field
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function generate_api_key_field() {
		$settings = (array) get_option( 'mcte_setting' );
		$api_key = isset($settings['api_key']) ? esc_attr( $settings['api_key'] ) : '' ;
	 	echo '<input name="mcte_setting[api_key]" id="wev_api_key" type="text"  class="regular-text"  value="' . $api_key . '" /> ';
	}

	/**
	 * Sanitize API key
	 *
	 * @since    0.1.0
	 *
	 * @return string
	 */
	public function sanitize_api_key( $input )
	{
		$output = array();  
		      
		foreach( $input as $key => $value ) {  
			if( isset( $input[$key] ) ) 
			    $output[$key] = trim(strip_tags( stripslashes( $input[ $key ] ) ));  
		}
		
		return  $output;  
	}


}

$GLOBAL['il_email_comment'] = new Mange_Comments_Through_Email();