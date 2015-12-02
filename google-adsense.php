<?php

/**
 * @file
 * WordPress Plugin
 *
 * @cond
 * Plugin Name: Google AdSense Widget
 * Version: 1.02
 * Plugin URI: http://wordpress.smullindesign.com/plugins/google-adsense-widget
 * Description: Easily monetize your blog with Google AdSense.
 * Author: Smullin Design
 * Author URI: http://www.smullindesign.com
 *
 * Copyright 2008 Smullin Design and Development, LLC (email: support@smullindesign.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * @endcond
 *
 * @TODO: Make the Ad Size option a select drop-down field.
 * @TODO: Option to paste code and parse options out of it.
 * @TODO: Option to select text or image ads from within widget.
 * @TODO: Add revenue sharing feature.
 */

// Define commonly used strings.
define('WGA_NAME', __('Google AdSense'));
define('WGA_CLEAN', 'widget_google_adsense');
define('WGA_ELEMENT', 'widget-google-adsense');

// Register hooks with WordPress core.
add_action('plugins_loaded', 'widget_google_adsense_register'); // Fired after all other plugins, such as the Automattic Sidebar Widgets plugin for WordPress, has loaded.
add_action('profile_personal_options', 'widget_google_adsense_profile_personal_options'); // An action intended for insertion of options into the personal profile option form.

/**
 * Implementation of init action.
 *
 * Put functions into one big function we'll call at the plugins_loaded
 * action. This ensures that all required plugin functions are defined.
 */
function widget_google_adsense_register() {
  // Check for the required plugin functions. This will prevent fatal
  // errors occurring when you deactivate the dynamic-sidebar plugin.
  if (!function_exists('register_sidebar_widget')) {
    return FALSE; // Missing Automattic Sidebar Widgets plugin (became core in WordPress v2.3)
  }

  // Initialize variables.
  $options = (array) get_option(WGA_CLEAN);
  $widget_ops = array('classname' => WGA_CLEAN, 'description' => __('A single advertisement unit. (Google limits to 3 per page.)'));
  $control_ops = array('width' => 220, 'height' => 50 * count(widget_google_adsense_options()), 'id_base' => WGA_CLEAN);

  // Register each instance of this widget.
  $id = '';
  foreach (array_keys($options) as $i) {
    $id = WGA_CLEAN .'-'. $i;
    // WARNING: wp_register_sidebar_widget() was not introduced until WordPress 2.2.
    // @TODO: Make backward-compatible? Find out how multiple widgets used to 
    //        work and switch back to that method if those functions are still 
    //        supported, even if deprecated.
    wp_register_sidebar_widget($id, WGA_NAME, 'widget_google_adsense', $widget_ops, array('number' => $i));
    wp_register_widget_control($id, WGA_NAME, 'widget_google_adsense_control', $control_ops, array('number' => $i));
  }

  // If there are no instances of this widget, register a generic widget.
  if (!$id) {
    wp_register_sidebar_widget(WGA_CLEAN .'-1', 'widget_google_adsense', WGA_CLEAN, $widget_ops, array('number' => -1));
    wp_register_widget_control(WGA_CLEAN .'-1', 'widget_google_adsense_control', $control_ops, array('number' => -1));
  }
}

/**
 * Fetch widget options and their default values.
 *
 * @return Array
 *   Widget options.
 */
function widget_google_adsense_options() {
  return array(
    'title' => '',
    'google_ad_client' => '',
    'google_ad_width' => 160,
    'google_ad_height' => 600,
    'google_ad_format' => '160x600_as',
    'google_ad_type' => 'text',
    'google_ad_channel' => '',
    'google_color_border' => '336699',
    'google_color_bg' => 'FFFFFF',
    'google_color_link' => '0000FF',
    'google_color_url' => '008000',
    'google_color_text' => '000000',
  );
}

/**
 * Generate the XHTML and JavaScript code for this widget.
 *
 * @param Array $args
 *   Any additional code required to help this widget to conform to
 *   the active theme. Options include: before_widget, before_title,
 *   after_widget, and after_title.
 */
function widget_google_adsense($args, $widget_args = 1) {
  extract($args, EXTR_SKIP);
  if (is_numeric($widget_args)) {
    $widget_args = array('number' => $widget_args);
  }
  $widget_args = wp_parse_args($widget_args, array('number' => -1));
  extract($widget_args, EXTR_SKIP);

  $options = get_option(WGA_CLEAN);

  // Compile Google AdSense variables for output in JavaScript format.
  $output = '';
  foreach($options[$number] as $key => $value) {
    if (substr($key, 0, 7) == 'google_') {
      $output .= $key ." = '". js_escape($value) ."';\n";
    }
  }

  // Apply WordPress filters.
  $title = apply_filters('widget_title', $options[$number]['title']);

  // Output to browser.
  print
    $before_widget .
    (empty($title)? '' :
      $before_title .
      $title .
      $after_title) .
    <<< XHTML
<!-- Google AdSense -->
<script type="text/javascript">
//<![CDATA[
{$output}
//]]>
</script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
<!-- /Google AdSense -->
XHTML
   .
   $after_widget;
}

/**
 * Generate widget options form and manage user changes to options.
 */
function widget_google_adsense_control($widget_args) {
  global $wp_registered_widgets;
  static $updated = false;

  // Parse function arguments.
  if (is_numeric($widget_args)) {
    $widget_args = array('number' => $widget_args);
  }
  $widget_args = wp_parse_args($widget_args, array('number' => -1));
  extract($widget_args, EXTR_SKIP);

  // Load all widget options from database.
  $options = (array) get_option(WGA_CLEAN);
  $default_options = widget_google_adsense_options();

  // User made changes.
  if (!$updated && !empty($_POST['sidebar'])) {
    // Check for widget removal.
    $sidebar = (string) $_POST['sidebar'];

    $sidebars_widgets = wp_get_sidebars_widgets();
    if (isset($sidebars_widgets[$sidebar])) {
      $this_sidebar =& $sidebars_widgets[$sidebar];
    }
    else {
      $this_sidebar = array();
    }
    foreach ($this_sidebar as $_widget_id) {
      if (WGA_CLEAN == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])) {
        $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
        if (!in_array(WGA_CLEAN .'-'. $widget_number, $_POST['widget-id'])) { // the widget has been removed.
          unset($options[$widget_number]);
        }
      }
    }

    // Otherwise, gather changes to widget options.
    foreach ((array) $_POST[WGA_ELEMENT] as $widget_number => $widget) {
      foreach ($default_options as $key => $value) {
        if (array_key_exists($key, $widget)) {
          $options[$widget_number][$key] = strip_tags(stripslashes($widget[$key])); // sanitize user input
        }
      }
    }

    // Save changes to database.
    update_option(WGA_CLEAN, $options);
    $updated = TRUE;
  }

  if ($number == -1) {
    $number = '%i%'; // generates random number for new widgets
  }

  // Retrieve this widget's options specifically, relying on widget defaults to fill the gaps.
  $options[$number] = array_merge($default_options, (array) $options[$number]);

  // Generate form elements and output to browser.
  $output = '';
  foreach($options[$number] as $key => $value) {
    $value = attribute_escape($value);
    $label = ucwords(str_replace('_', ' ', $key)); // Make human-readable.
    $output .= '<p><label for="'. WGA_ELEMENT.'-'.$number.'-'.$key .'">'. $label .': '.
      '<input type="text" style="width:200px" '.
        'id="'. WGA_ELEMENT.'-'.$number.'-'.$key .'" '.
        'name="'. WGA_ELEMENT.'['.$number.']['.$key.']" '.
        'value="'. $value .'" /></label></p>';
  }
  $output .= '<input type="hidden" name="'. WGA_ELEMENT.'['.$number.'][submit]" value="1" />';
  print $output;
}

/**
 * Implementation of profile_personal_options action.
 * 
 * Provide a way for users to enter a Google AdSense Publisher ID.
 */
function widget_google_adsense_profile_personal_options() {
  if ($not_allowed) {
    return; // do not display the form field
  }

  $element = WGA_ELEMENT .'-publisher-id';
  $publisher_id = attribute_escape($profileuser->$$element);
  print 
    '<table class="form-table"><tr>'.
    '<th scope="row">'. __('Google AdSense Publisher ID') .'</th>'.
    '<td><label for="'. $element .'"><input type="text" name="'. $element .'" id="'. $element .'" value="'. $publisher_id .'" /></label><br />'.
    __('Required for advertising revenue.') .'</td>'.
    '</tr></table>';
XHTML;
}