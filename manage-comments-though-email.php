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

if( !defined('ABSPATH') ) exit;

if (! class_exists('Mailgun')) 
	require 'vendor/autoload.php';

use Mailgun\Mailgun;

/**
* Initiate Comment Class
*/
class Mange_Comments_Through_Email
{
	
	function __construct()
	{
		add_action( 'init', array($this, 'init_plugin') );
		add_action( 'admin_init', array($this, 'init_admin') );
		add_action( 'template_redirect', array( $this, 'email_comment_request' ), -1);
		add_action( 'update_option_mcte_setting', array($this, 'create_mailgun_routes') );
		add_action( 'add_option_mcte_setting', array($this, 'create_mailgun_routes') );

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
		add_rewrite_endpoint( 'email-comment', EP_ALL );

	}

	public function create_mailgun_routes()
	{
	
		$settings = (array) get_option( 'mcte_setting' );
		$domain_name = isset($settings['domain_name']) ? esc_attr( $settings['domain_name'] ) : '' ;
		$api_key = isset($settings['api_key']) ? esc_attr( $settings['api_key'] ) : '' ;

		if (!empty($domain_name) && !empty($api_key)) {

			$mgClient = new Mailgun( $api_key );
			$routes = $mgClient->get("routes");

			foreach ($routes->http_response_body->items as $route ) {
				if ($route->actions[0] ==  'forward("'. get_site_url() .'/email-comment")') 
					return;
			}

			$result = $mgClient->post("routes",
			           array('priority'    => 1,
			                 'expression'  => 'match_recipient("comment@'. $domain_name .'")',
			                 'action'      => array('forward("'. get_site_url() .'/email-comment")',
			                                        'stop()'),
			                 'description' => 'Route that manages comments.'));

		}
	
	}

	/**
	 * Rout API Call to action
	 *
	 * @since    1.0.0
	 * @return JSON
	 */
	public function email_comment_request()
	{
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ 'email-comment' ] ) )
			return;

	    	exit;
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

		add_settings_field(
			'mcte_domain_name',
			__( 'Domain Name', 'mcte' ),
			array( $this, 'generate_domain_field' ),
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
	 * Genereate API field
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
	 * Generate domain name field
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


/**
 * Send Moderator email through mailgun wp_notify_moderator()
 *
 * @since    0.1.0
 *
 * @return string
 */
if ( ! function_exists( 'wp_notify_moderator' ) ){

	function wp_notify_moderator($comment_id)
	{
		global $wpdb;
		$settings = (array) get_option( 'mcte_setting' );

		$mgClient = new Mailgun($settings['api_key']);
		$domain = $settings['domain_name'];

		$comment = get_comment($comment_id);
		$post = get_post($comment->comment_post_ID);
		$user = get_userdata( $post->post_author );
		// Send to the administration and to the post author if the author can modify the comment.
		$emails = array( get_option( 'admin_email' ) );
		if ( user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) ) {
			if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
				$emails[] = $user->user_email;
		}

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		switch ( $comment->comment_type ) {
			case 'trackback':
				$notify_message  = sprintf( __('A new trackback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				break;
			case 'pingback':
				$notify_message  = sprintf( __('A new pingback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				break;
			default: // Comments
				$notify_message  = sprintf( __('A new comment on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
				$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				break;
		}

		$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
	 		'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
		$notify_message .= admin_url("edit-comments.php?comment_status=moderated") . "\r\n";

		$subject = sprintf( __('[%1$s] Please moderate: "%2$s" %3$s'), $blogname, $post->post_title , 'MCTE:'. $comment_id );
		$message_headers = '';

		$emails          = apply_filters( 'comment_moderation_recipients', $emails,          $comment_id );
		$notify_message  = apply_filters( 'comment_moderation_text',       $notify_message,  $comment_id );
		$subject         = apply_filters( 'comment_moderation_subject',    $subject,         $comment_id );
		$message_headers = apply_filters( 'comment_moderation_headers',    $message_headers, $comment_id );

		$result = $mgClient->sendMessage("$domain",
		                  array('from'    => 'New Comment <comment@'.$domain.'>',
		                        'to'      => implode(', ', $emails ),
		                        'subject' => $subject ,
		                        'text'    => $notify_message ));

		return true;
	}
}


$GLOBAL['il_email_comment'] = new Mange_Comments_Through_Email();