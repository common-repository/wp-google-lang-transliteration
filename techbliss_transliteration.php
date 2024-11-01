<?php

/*
Plugin Name: WPGoogleLangTransliteration
Plugin URI: http://indiafacinates.com/WPGoogleLangTransliteration/
Description: Language Transliteration Support for your Wordpress blog.
Version: 1.3
Authors: Suhas - India Fascinates Dot Com and Rajesh - Techblissonline Dot Com
Author URIs: Suhas (http://indiafascinates.com/) and Rajesh (http://techblissonline.com/)
*/

/*
Copyright (C) 2008 Subha ( http://indiafascinates.com/) and Rajesh (http://techblissonline.com) (transliteration AT techblissonline DOT com)  
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists('WPGoogleLangTransliteration')) {
class WPGoogleLangTransliteration {

 	var $version = "1.3"; 	

	function WPGoogleLangTransliteration() {
	
		// Pre-2.6 compatibility
		if ( ! defined( 'WP_CONTENT_URL' ) )
			define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
		if ( ! defined( 'WP_CONTENT_DIR' ) )
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		if ( ! defined( 'WP_PLUGIN_URL' ) )
			define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
		if ( ! defined( 'WP_PLUGIN_DIR' ) )
			define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );		
		
		add_action('admin_print_scripts-post-new.php', array(&$this, 'wpt_post_admin_scripts'));	
		add_action('admin_print_scripts-post.php', array(&$this, 'wpt_post_admin_scripts'));	
		add_action('admin_print_scripts-page.php', array(&$this, 'wpt_page_admin_scripts'));
		add_action('admin_print_scripts-page-new.php', array(&$this, 'wpt_page_admin_scripts'));
		add_action('init', array(&$this, 'init'));
		add_action('wp_head', array(&$this, 'wpt_head_scripts'));
		add_action('wp_footer',array($this,'wpt_footer_script'));		
		add_action('comment_form', array(&$this, 'comment_form'));	//add Transliteration options to comment form
        add_action('edit_form_advanced', array(&$this, 'post_form'));	//aadd Transliteration options to Advanced Post
        add_action('simple_edit_form', array(&$this, 'post_form'));	//add Transliteration options to Simple Post
        add_action('edit_page_form', array(&$this, 'post_form'));	//add Transliteration options to Page	
			
	}	
	
	function render($parentElement,$txtareaName)
    {
	if ($txtareaName === "comment") {
    ?>
		<div style="display:left;align:left; margin:5px 5px;" id='translControl'>
		<input style="width:15px;" type="checkbox" id="checkboxId" onclick="javascript:checkboxClickHandler()"></input><select style="width:100px;" id="languageDropDown" onchange="javascript:languageChangeHandler()"></select>&nbsp;&nbsp;<small>(To Type in English, deselect the checkbox. Read more <a href="http://indiafascinates.com/wordpress/wordpress-google-language-transliteration-plugin/" target="_blank">here</a>)</small>
		</div>
		<div id="errorDiv"></div>
		<?php } else { ?>
		<div style="display: left;align:left;" id='translControl'>
			<p style="align:left;"><input type="checkbox" id="checkboxId" onclick="javascript:checkboxClickHandler()"></input>
			Type in <select id="languageDropDown" onchange="javascript:languageChangeHandler()"></select></p>
		</div>
		<div id="errorDiv"></div>
		<?php } ?>
		<script language="JavaScript" type="text/javascript">
			var urlp;            
			var mozilla = document.getElementById && !document.all;			
            var url = document.getElementById("<?php echo $parentElement; ?>");
			if (mozilla)
	            urlp = url.parentNode;
			else
				    urlp = url.parentElement;
            var sub = document.getElementById("translControl");
            urlp.appendChild(sub, url);
        </script>	
<?php
}
	function comment_form()
	{
        global $user_ID;
		if (isset($user_ID)) 
			$this->render("url","comment");
		else
			$this->render("url","comment");
	}
	function post_form()
	{
		$this->render("quicktags","content");
	}
	
	function wpt_post_admin_scripts() {	
	?>		
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      // Load the Google Transliteration API
      google.load("elements", "1", {
        packages: "transliteration"
      });
      var transliterationControl;
      function onLoad() {		
        var options = {
            sourceLanguage: google.elements.transliteration.LanguageCode.ENGLISH,
            destinationLanguage: ['ar', 'bn', 'gu', 'hi', 'kn', 'ml', 'mr', 'ne', 'pa', 'ta', 'te', 'ur'],
            transliterationEnabled: true,
            shortcutKey: 'ctrl+g'
        };
		
        // Create an instance on TransliterationControl with the required
        // options.
        transliterationControl = new google.elements.transliteration.TransliterationControl(options);		

        // Enable transliteration in the textfields with the given ids.		
		var ids = [ "content", "title", "new-tag-post_tag", "newcat" , "excerpt", "metakeyinput" , "metavalue"];
		
        transliterationControl.makeTransliteratable(ids);		
		
		//Enable transliteration
		transliterationControl.enableTransliteration();

        // Add the STATE_CHANGED event handler to correcly maintain the state
        // of the checkbox.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.STATE_CHANGED,
            transliterateStateChangeHandler);

        // Add the SERVER_UNREACHABLE event handler to display an error message
        // if unable to reach the server.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.SERVER_UNREACHABLE,
            serverUnreachableHandler);

        // Add the SERVER_REACHABLE event handler to remove the error message
        // once the server becomes reachable.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.SERVER_REACHABLE,
            serverReachableHandler);

        // Set the checkbox to the correct state.
        document.getElementById('checkboxId').checked =
          transliterationControl.isTransliterationEnabled();

        // Populate the language dropdown
        var destinationLanguage =
          transliterationControl.getLanguagePair().destinationLanguage;
        var languageSelect = document.getElementById('languageDropDown');
        var supportedDestinationLanguages =
          google.elements.transliteration.getDestinationLanguages(
            google.elements.transliteration.LanguageCode.ENGLISH);
			
        for (var lang in supportedDestinationLanguages) {
          var opt = document.createElement('option');
          opt.text = lang;
          opt.value = supportedDestinationLanguages[lang];
          if (destinationLanguage == opt.value) {
            opt.selected = true;
          }
          try {
            languageSelect.add(opt, null);
          } catch (ex) {
            languageSelect.add(opt);
          }
        }
      }

      // Handler for STATE_CHANGED event which makes sure checkbox status
      // reflects the transliteration enabled or disabled status.
      function transliterateStateChangeHandler(e) {
        document.getElementById('checkboxId').checked = e.transliterationEnabled;
      }

      // Handler for checkbox's click event.  Calls toggleTransliteration to toggle
      // the transliteration state.
      function checkboxClickHandler() {
        transliterationControl.toggleTransliteration();
      }

      // Handler for dropdown option change event.  Calls setLanguagePair to
      // set the new language.
      function languageChangeHandler() {
        var dropdown = document.getElementById('languageDropDown');
        transliterationControl.setLanguagePair(
            google.elements.transliteration.LanguageCode.ENGLISH,
            dropdown.options[dropdown.selectedIndex].value);
      }

      // SERVER_UNREACHABLE event handler which displays the error message.
      function serverUnreachableHandler(e) {
        document.getElementById("errorDiv").innerHTML =
            "Transliteration server unreachable!";
      }

      // SERVER_UNREACHABLE event handler which clears the error message.
      function serverReachableHandler(e) {
        document.getElementById("errorDiv").innerHTML = "";
      }
      google.setOnLoadCallback(onLoad);
    </script> 
	<?php
	}
	
	function wpt_page_admin_scripts() {	
	?>		
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      // Load the Google Transliteration API
      google.load("elements", "1", {
        packages: "transliteration"
      });
      var transliterationControl;
      function onLoad() {		
        var options = {
            sourceLanguage: google.elements.transliteration.LanguageCode.ENGLISH,
            destinationLanguage: ['ar', 'bn', 'gu', 'hi', 'kn', 'ml', 'mr', 'ne', 'pa', 'ta', 'te', 'ur'],
            transliterationEnabled: true,
            shortcutKey: 'ctrl+g'
        };
		
        // Create an instance on TransliterationControl with the required
        // options.
        transliterationControl = new google.elements.transliteration.TransliterationControl(options);		

        // Enable transliteration in the textfields with the given ids.		
		var ids = ["content", "title", "metakeyinput", "metavalue"];
		
        transliterationControl.makeTransliteratable(ids);		
		
		//Enable transliteration
		transliterationControl.enableTransliteration();

        // Add the STATE_CHANGED event handler to correcly maintain the state
        // of the checkbox.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.STATE_CHANGED,
            transliterateStateChangeHandler);

        // Add the SERVER_UNREACHABLE event handler to display an error message
        // if unable to reach the server.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.SERVER_UNREACHABLE,
            serverUnreachableHandler);

        // Add the SERVER_REACHABLE event handler to remove the error message
        // once the server becomes reachable.
        transliterationControl.addEventListener(
            google.elements.transliteration.TransliterationControl.EventType.SERVER_REACHABLE,
            serverReachableHandler);

        // Set the checkbox to the correct state.
        document.getElementById('checkboxId').checked =
          transliterationControl.isTransliterationEnabled();

        // Populate the language dropdown
        var destinationLanguage =
          transliterationControl.getLanguagePair().destinationLanguage;
        var languageSelect = document.getElementById('languageDropDown');
        var supportedDestinationLanguages =
          google.elements.transliteration.getDestinationLanguages(
            google.elements.transliteration.LanguageCode.ENGLISH);
			
        for (var lang in supportedDestinationLanguages) {
          var opt = document.createElement('option');
          opt.text = lang;
          opt.value = supportedDestinationLanguages[lang];
          if (destinationLanguage == opt.value) {
            opt.selected = true;
          }
          try {
            languageSelect.add(opt, null);
          } catch (ex) {
            languageSelect.add(opt);
          }
        }
      }

      // Handler for STATE_CHANGED event which makes sure checkbox status
      // reflects the transliteration enabled or disabled status.
      function transliterateStateChangeHandler(e) {
        document.getElementById('checkboxId').checked = e.transliterationEnabled;
      }

      // Handler for checkbox's click event.  Calls toggleTransliteration to toggle
      // the transliteration state.
      function checkboxClickHandler() {
        transliterationControl.toggleTransliteration();
      }

      // Handler for dropdown option change event.  Calls setLanguagePair to
      // set the new language.
      function languageChangeHandler() {
        var dropdown = document.getElementById('languageDropDown');
        transliterationControl.setLanguagePair(
            google.elements.transliteration.LanguageCode.ENGLISH,
            dropdown.options[dropdown.selectedIndex].value);
      }

      // SERVER_UNREACHABLE event handler which displays the error message.
      function serverUnreachableHandler(e) {
        document.getElementById("errorDiv").innerHTML =
            "Transliteration server unreachable!";
      }

      // SERVER_UNREACHABLE event handler which clears the error message.
      function serverReachableHandler(e) {
        document.getElementById("errorDiv").innerHTML = "";
      }
      google.setOnLoadCallback(onLoad);
    </script>  
	<?php
	}
	
	function init() {
		
		wp_enqueue_script('WPGoogletransliterategeneral-js', WP_PLUGIN_URL . '/wp-google-lang-transliteration/transliterate.js');	
				
	}
	
	function wpt_head_scripts() {	
		if (is_single() || is_page()) {
	?>		
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      // Load the Google Transliteration API
      google.load("elements", "1", {
        packages: "transliteration"
      });	  
      google.setOnLoadCallback(onLoad);
    </script>
	<?php
		
	}
	}
	function wpt_footer_script()
	{
	?>
		<small>Lingual Support by <a href="http://indiafascinates.com/" title="India" target="_blank">India Fascinates</a></small>
	<?php	
	}
}	//end class
}   //end if
$WPT = new WPGoogleLangTransliteration();
?>