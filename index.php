<?php

/*
  Plugin Name:  KWD Word Filter Plugin
  Plugin URI:   https://github.com/Kernix13/kwd-word-filter
  Description:  Filter certain words in blog posts with an option to
                replace them.
  Version:      1.0.0
  Author:       James Kernicky
  Author URI:   https://kernixwebdesign.com/
  License:      GPLv2 or later
  License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

if( ! defined('ABSPATH') ) exit; // Exit if accessed directly

class KWDWordFilterPlugin {

  function __construct() {
    add_action('admin_menu', array($this, 'ourMenu'));
    add_action('admin_init', array($this, 'ourSettings'));

    if (get_option('plugin_words_to_filter')) add_filter('the_content', array($this, 'filterLogic'));
  }

  function ourSettings() {
    add_settings_section('replacement-text-section', null, null, 'word-filter-options');
    register_setting('replacementFields', 'replacementText');
    add_settings_field('replacement-text', 'Filtered Text', array($this, 'replacementFieldHTML'), 'word-filter-options', 'replacement-text-section');
  }

  function replacementFieldHTML() { ?>
    <input type="text" name="replacementText" value="<?php echo esc_attr(get_option('replacementText', '***')) ?>">
    <p class="description">Leave blank to simply remove the filtered words.</p>
  <?php }

  function filterLogic($content) {

    $badWords = explode(',', get_option('plugin_words_to_filter'));
    $badWordsTrimmed = array_map('trim', $badWords);

    return str_ireplace($badWordsTrimmed, esc_html(get_option('replacementText', '****')), $content);
  }

  function ourMenu() {

    // Main Page
    $mainPageHook = add_menu_page('Words To Filter', 'Word Filter', 'manage_options', 'kwdwordfilter', array($this, 'wordFilterPage'), plugin_dir_url(__FILE__) . 'custom.svg', 100);
    add_submenu_page('kwdwordfilter', 'Word To Filter', 'Words List', 'manage_options', 'kwdwordfilter', array($this, 'wordFilterPage'));

    // Options page
    add_submenu_page('kwdwordfilter', 'Word Filter Options', 'Options', 'manage_options', 'word-filter-options', array($this, 'optionsSubPage'));

    // Custom CSS
    add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
  }

  function mainPageAssets() {
    wp_enqueue_style('filterAdminCss', plugin_dir_url(__FILE__) . 'styles.css');
  }

  function handleForm() {
    if (wp_verify_nonce($_POST['ourNonce'], 'saveFilterWords') AND current_user_can('manage_options')) {
      update_option('plugin_words_to_filter', sanitize_text_field($_POST['plugin_words_to_filter'])); ?>
      <div class="updated">
        <p>Your filtered words were saved.</p>
      </div>
    <?php } else { ?>
      <div class="error">
        <p>Sorry, you do not have permission to perform that action.</p>
      </div>
    <?php } 
  }

  function wordFilterPage() { ?>
    <div class="wrap">
      <h1>Word Filter</h1>
      <?php if (isset($_POST["justsubmitted"]) && $_POST["justsubmitted"] =='true') $this->handleForm() ?>
      <form method="POST">
        <input type="hidden" name="justsubmitted" value="true">
        <?php wp_nonce_field('saveFilterWords', 'ourNonce') ?>
        <label for="plugin_words_to_filter"><p>Enter a <strong>comma-separated</strong> list of words to filter from your site's content</p></label>
        <div class="word-filter__flex-container">
          <textarea name="plugin_words_to_filter" id="plugin_words_to_filter" placeholder="bad, mean, profane, horrible"><?php echo esc_textarea( get_option('plugin_words_to_filter') ) ?></textarea>
        </div>
        <input type="submit" value="Save Changes" name="submit" id="submit" class="button button-primary">
      </form>
    </div>
  <?php }

  function optionsSubPage() { ?>
    <div class="wrap">
      <h1>Word Filter Options</h1>
      <form action="options.php" method="POST">
        <?php
          settings_errors();
          settings_fields('replacementFields');
          do_settings_sections('word-filter-options');
          submit_button();
        ?>
      </form>
    </div>
  <?php }
}

$kwdWordFilterPlugin = new KWDWordFilterPlugin();