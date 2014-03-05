<?php
/**
 * @package OpBandit
 */
/*
Plugin Name: OpBandit
Plugin URI: http://opbandit.com
Description: OpBandit allows you to optimize your content.
Version: 0.1.0
Author: OpBandit, Inc. <info@opbandit.com>
Author URI: http://opbandit.com
License: GPLv2 or later
*/
define('OPBANDIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPBANDIT_VERSION', '0.1.0');
include_once dirname( __FILE__ ) . '/admin.php';
include_once dirname( __FILE__ ) . '/service.php';

function opbandit_service() {
  $options = get_option('opbandit_options');
  if($options['api_key'] == "" || $options['api_secret'] == "")
    return null;
  $service = new OpBanditService($options['api_key'], $options['api_secret'], $options['hostname']);
  return $service;
}

function opbandit_load_pagejs() {
  $options = get_option('opbandit_options');
  if($options['api_key'] != "")
    wp_enqueue_script('opbandit', "http://cdn.opbandit.com/loader.min.js?key=" . $options['api_key'], false);
}
add_action('wp_enqueue_scripts', 'opbandit_load_pagejs');

function opbandit_load_css() {
  global $hook_suffix;

  if (in_array($hook_suffix, array('post.php'))) {
    wp_register_style('opbandit.css', OPBANDIT_PLUGIN_URL . 'opbandit.css', array(), OPBANDIT_VERSION);
    wp_enqueue_style('opbandit.css');
  }
}
add_action('admin_enqueue_scripts', 'opbandit_load_css');

function opbandit_inner_custom_box($post) {
  $service = opbandit_service();
  if($service == null) {
    $link = "<a href='" . admin_url("options-general.php?page=opbandit") . "'>settings page</a>";
    echo "You must set the OpBandit key and secret in the " . $link . " before you can enter alternatives.";
    return;
  }
  $response = $service->getExperiment($post->post_title);

  // if an error message
  if(!is_object($response)) {
    echo $response;
    return;
  }
  $alternatives = $response->success ? $response->result : array();

  wp_nonce_field('opbandit_inner_custom_box', 'opbandit_inner_custom_box_nonce');
  echo '<div id="opbandit_headlines">';
  for($i = 0; $i < 5; $i++) {
    echo '<div class="opbandit_headline"><label for="opbandit_headline_' . $i . '">' . ($i + 1) . ". </label>";
    echo '<input type="text" id="opbandit_headline_' . $i . '" name="opbandit_headline_' . $i . '" value="' . esc_attr($alternatives[$i]) . '" size="45" />';
    echo '</div>';
  }
  echo '</div>';
}

function opbandit_add_custom_box() {
    $screens = array('post', 'page');
    foreach ( $screens as $screen ) {
      add_meta_box('opbandit_sectionid', 'OpBandit Alternative Headlines', 'opbandit_inner_custom_box', $screen);
    }
}
add_action('add_meta_boxes', 'opbandit_add_custom_box');

function opbandit_save_postdata( $post_id ) {
  // Check if our nonce is set.
  if (!isset($_POST['opbandit_inner_custom_box_nonce']) || 
      !wp_verify_nonce($_POST['opbandit_inner_custom_box_nonce'], "opbandit_inner_custom_box"))
    return $post_id;

  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
    return $post_id;

  // Check the user's permissions.
  if('page' == $_POST['post_type']) {
    if(!current_user_can('edit_page', $post_id))
      return $post_id;
  } else {
    if(!current_user_can( 'edit_post', $post_id ))
      return $post_id;
  }

  $alternatives = array();
  for($i = 0; $i < 5; $i++)
    if($_POST["opbandit_headline_" . $i] != "")
      $alternatives[] = $_POST["opbandit_headline_" . $i];

  if(count($alternatives) > 0) {
    $service = opbandit_service();
    $response = $service->setExperiment($_POST['post_title'], $alternatives);

    // if an error message
    if(!is_object($response)) {
      //echo $response;
    }
  }
}
add_action('save_post', 'opbandit_save_postdata');

function append_opbandit_query_string($url, $post, $leavename) {
  $options = get_option('opbandit_options');
  if($options['automark'] == 'on' && $post->post_type == 'post')
    $url = add_query_arg('opbandit_optimize', '1', $url);
  return $url;
}
add_filter('post_link', 'append_opbandit_query_string', 10, 3);
