<?php
function opbandit_admin_add_page() {
  add_options_page('OpBandit Plugin Settings', 'OpBandit Settings', 'manage_options', 'opbandit', 'opbandit_options_page');
}
add_action('admin_menu', 'opbandit_admin_add_page');

function opbandit_section_text() {
  echo '<p>Main description of this section here.</p>';
}

function opbandit_automark_setting_string() {
  $options = get_option('opbandit_options');
  $checked = "";
  if($options['automark'] == 'on')
    $checked = 'checked="on"';
  echo "<input id='automark' name='opbandit_options[automark]' type='checkbox' {$checked}/>";
}

function opbandit_api_key_setting_string() {
  $options = get_option('opbandit_options');
  echo "<input id='api_key' name='opbandit_options[api_key]' size='40' type='text' value='{$options['api_key']}' />";
}

function opbandit_api_secret_setting_string() {
  $options = get_option('opbandit_options');
  echo "<input id='api_secret' name='opbandit_options[api_secret]' size='40' type='text' value='{$options['api_secret']}' />";
}

function opbandit_options_validate($input) {
  return $input;
}

function opbandit_admin_init() {
  register_setting('opbandit_options', 'opbandit_options', 'opbandit_options_validate');
  add_settings_section('opbandit_main', 'Authentication Settings', 'opbandit_section_text', 'opbandit');
  add_settings_field('api_key', 'API Key', 'opbandit_api_key_setting_string', 'opbandit', 'opbandit_main');
  add_settings_field('api_secret', 'API Secret', 'opbandit_api_secret_setting_string', 'opbandit', 'opbandit_main');
  add_settings_field('automark', 'Auto-mark OpBandit Titles?', 'opbandit_automark_setting_string', 'opbandit', 'opbandit_main');
}
add_action('admin_init', 'opbandit_admin_init');

function opbandit_options_page() {
?>
<div class="wrap">
<h2>OpBandit Config</h2>
OpBandit configuration options.
<form action="options.php" method="post">
<?php settings_fields('opbandit_options'); do_settings_sections('opbandit'); ?>
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div> 
<?php
}
