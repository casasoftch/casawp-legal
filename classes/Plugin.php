<?php
namespace casawpLegal;

class Plugin {
    public $textids = false;
    public $fields = false;
    public $meta_box = false;
    public $admin = false;
    public $conversion = null;
    public $show_sticky = true;
    public $tax_query = array();
    public $translator = null;
    public $locale = 'de';
    public $configuration = array();
    public $m = false;

    public $page_titles = [
      'imprint' => [
        'de' => 'Impressum',
        'fr' => 'Impressum',
        'en' => 'Imprint',
        'it' => 'Impressum',
      ],
      'terms' => [
        'de' => 'Datenschutz',
        'fr' => 'Mention juridique',
        'en' => 'Legal information',
        'it' => 'Note legali',
      ]
    ];

    public function __construct($configuration){
        $this->configuration = $configuration;
        $this->locale = substr(get_bloginfo('language'), 0, 2);
        add_filter('icl_set_current_language', array($this, 'wpmlLanguageSwitchedTo'));
        add_action('wp_enqueue_scripts', array($this, 'registerScriptsAndStyles'));
        add_action('wp_enqueue_scripts', array($this, 'setOptionJsVars'));
        add_filter( 'template_include', array($this, 'include_template_function'), 1 );
        register_activation_hook(CASAWP_LEGAL_PLUGIN_DIR, array($this, 'casawp_legal_activation'));
        register_deactivation_hook(CASAWP_LEGAL_PLUGIN_DIR, array($this, 'casawp_legal_deactivation'));
        add_action('plugins_loaded', array($this, 'setTranslation'));

        // auto add pages if missing for private users
        //add_action('after_setup_theme', array($this, 'makeSurePagesExist') );

        // hook failed login
        add_action( 'wp_login_failed', array($this, 'privateUserRedirectToOrigin') );

        // pages
        add_filter('the_content', array($this, 'legalPageRenders'));
        $this->m = new \Mustache_Engine;

    }

    public function wpml_connect_page($original, $translation, $lang) {
   
      // https://wpml.org/wpml-hook/wpml_element_type/
      $wpml_element_type = apply_filters( 'wpml_element_type', 'page' );
       
      // get the language info of the original page
      // https://wpml.org/wpml-hook/wpml_element_language_details/
      $get_language_args = array('element_id' => $original, 'element_type' => 'page' );
      $original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
       
      $set_language_args = array(
          'element_id'    => $translation,
          'element_type'  => $wpml_element_type,
          'trid'   => $original_post_language_info->trid,
          'language_code'   => $lang,
          'source_language_code' => $original_post_language_info->language_code
      );

      do_action( 'wpml_set_element_language_details', $set_language_args );
  }

    public function makeSurePagesExist(){
      if (is_admin()) {
        echo '<div class="updated"><p><strong>' . __('Gennearating pages', 'casawp' ) . '</strong></p></div>';
      }
      

      
      $main_lang = 'de';
      if (function_exists('icl_object_id') ) {
        $wpml_options = get_option( 'icl_sitepress_settings', false);
        if ($wpml_options) {
          $main_lang = $wpml_options['default_language'];
        }
      }

      $imprintpage = get_option('casawp_legal_imprint', false);
      // if ($imprintpage) {
      //   $imprintpagepost = get_post($imprintpage);
      // }
      
      if ( 'publish' != get_post_status ( $imprintpage ) ) {
        $imprintpage = false;
      }
      if (!$imprintpage) {
        $page = get_page_by_title('Impressum');
        if (!$page) {
          $pageId = wp_insert_post(array(
            'post_title' => (array_key_exists($main_lang, $this->page_titles['imprint']) ? $this->page_titles['imprint'][$main_lang] : 'imprint'),
            'post_status' => 'publish',
            'post_author'   => 1,
            'post_content'  => '<p>...</p>',
            'post_type' => 'page'
          ));
          if (is_admin()) {
            echo '<div class="updated"><p><strong>' . __('Gennerated imprint page', 'casawp' ) . ' ' .$pageId . '</strong></p></div>';
          }
        } else {
          $pageId = $page->ID;
        }
        update_option('casawp_legal_imprint', $pageId);
      }

      $termspage = get_option('casawp_legal_terms', false);
      if ( 'publish' != get_post_status ( $termspage ) ) {
        $termspage = false;
      }
      if (!$termspage) {
    
        $page = get_page_by_title('Datenschutz');
        if (!$page) {
          $pageId = wp_insert_post(array(
            'post_title' => (array_key_exists($main_lang, $this->page_titles['terms']) ? $this->page_titles['terms'][$main_lang] : 'terms'),
            'post_status' => 'publish',
            'post_author'   => 1,
            'post_content'  => '<p style="display:none">&nbsp;</p>',
            'post_type' => 'page'
          ));
          if (is_admin()) {
            echo '<div class="updated"><p><strong>' . __('Gennerated terms page', 'casawp' ) . ' ' .$pageId . '</strong></p></div>';
          }
          if (function_exists('icl_object_id') && get_option( 'icl_sitepress_settings', false )) {
            foreach (array_keys($this->page_titles['terms']) as $altLang) {
              if ($altLang !== $main_lang) {
                $altPageId = wp_insert_post(array(
                  'post_title' => $this->page_titles['terms'][$altLang],
                  'post_status' => 'publish',
                  'post_author'   => 1,
                  'post_content'  => '<p style="display:none">&nbsp;</p>',
                  'post_type' => 'page'
                ));
                $this->wpml_connect_page($pageId, $altPageId, $altLang);  
                if (is_admin()) {
                  echo '<div class="updated"><p><strong>' . __('Gennerated terms page', 'casawp' ) . ' for ' . $altLang . ' ' .$altPageId . '</strong></p></div>';
                }
              }
            }
          }
          
        } else {
          $pageId = $page->ID;
        }
        update_option('casawp_legal_terms', $pageId);
      }
    }

    public function legalPageRenders($content){
      $prefix = 'casawp_legal_';
      $post_ID = get_the_ID();
      $default_post_ID = $post_ID;
      if (function_exists('icl_object_id') && get_option( 'icl_sitepress_settings', false )) {
        $options = get_option( 'icl_sitepress_settings' );
        $default_language = $options['default_language'];
        $default_post_ID = apply_filters( 'wpml_object_id', $post_ID, 'page', false, $default_language );
      }

      switch ($default_post_ID) {
        case get_option('casawp_legal_imprint', false):
          if (is_file(CASAWP_LEGAL_PLUGIN_DIR . 'templates/imprint-'.$this->locale.'.html')) {
            $template_file = CASAWP_LEGAL_PLUGIN_DIR . 'templates/imprint-'.$this->locale.'.html';
          } else {
            $template_file = CASAWP_LEGAL_PLUGIN_DIR . 'templates/imprint-de.html';
          }
          $content .= '<div class="casawp-legal__page casawp-legal__page--imprint">'.$this->m->render(
            file_get_contents($template_file), 
            array(
              'company' => [
                'legal_name' => get_option($prefix . 'company_legal_name', null),
                'phone' => get_option($prefix . 'company_phone', null),
                'fax' => get_option($prefix . 'company_fax', null),
                'email' => get_option($prefix . 'company_email', null),
                'uid' => get_option($prefix . 'company_uid', null),
                'vat' => get_option($prefix . 'company_vat', null),
              ],
              'address' => [
                'street' => get_option($prefix.'company_address_street', null),
                'street_number' => get_option($prefix.'company_address_street_number', null),
                'post_office_box_number' => get_option($prefix.'company_address_post_office_box_number', null),
                'postal_code' => get_option($prefix.'company_address_postal_code', null),
                'locality' => get_option($prefix.'company_address_locality', null),
              ]
            )
          ).'</div>';
          break;
        case get_option('casawp_legal_terms', false):
          if (is_file(CASAWP_LEGAL_PLUGIN_DIR . 'templates/terms-'.$this->locale.'.html')) {
            $template_file = CASAWP_LEGAL_PLUGIN_DIR . 'templates/terms-'.$this->locale.'.html';
          } else {
            $template_file = CASAWP_LEGAL_PLUGIN_DIR . 'templates/terms-de.html';
          }
          $content .= '<div class="casawp-legal__page casawp-legal__page--terms">'.$this->m->render(
            file_get_contents($template_file), 
            array(
              'company' => [
                'legal_name' => get_option($prefix . 'company_legal_name', null),
                'phone' => get_option($prefix . 'company_phone', null),
                'fax' => get_option($prefix . 'company_fax', null),
                'email' => get_option($prefix . 'company_email', null),
                'uid' => get_option($prefix . 'company_uid', null),
                'vat' => get_option($prefix . 'company_vat', null),
              ],
              'address' => [
                'street' => get_option($prefix.'company_address_street', null),
                'street_number' => get_option($prefix.'company_address_street_number', null),
                'post_office_box_number' => get_option($prefix.'company_address_post_office_box_number', null),
                'postal_code' => get_option($prefix.'company_address_postal_code', null),
                'locality' => get_option($prefix.'company_address_locality', null),
              ]
            )
          ).'</div>';
          break;
      }


      return $content;
    }

    public function wpmlLanguageSwitchedTo($lang) {
        if ($this->locale != substr($lang, 0, 2)) {
            $this->locale = substr($lang, 0, 2);
        }
        return $lang;
    }

    public function setOptionJsVars(){
        $script_params = array(
           'some_var'              => get_option('casawp_legal_some_var', 0),
        );
        wp_localize_script( 'casawp-legal', 'casawpLegalOptionParams', $script_params );
    }


    function casawp_legal_activation() {
        register_uninstall_hook(__FILE__, array($this, 'casawp_legal_uninstall'));
    }

    function casawp_legal_deactivation() {
        // actions to perform once on plugin deactivation go here
    }

    function casawp_legal_uninstall(){
        //actions to perform once on plugin uninstall go here
    }

    public function include_template_function( $template_path ) {
        // change template_path on the fly
        return $template_path;
    }

    function registerScriptsAndStyles(){
        wp_register_style( 'casawp_legal_css', CASAWP_LEGAL_PLUGIN_URL . 'plugin-assets/style.css' );
        wp_enqueue_style( 'casawp_legal_css' );
        //wp_register_style( 'casawp_legal_css', CASAWP_LEGAL_PLUGIN_URL . 'plugin-assets/global/casawp.css' );
        //wp_enqueue_script('casawp', CASAWP_LEGAL_PLUGIN_URL . 'plugin-assets/global/casawp.js', array( 'jquery' ), false, true );
        //get_option( 'casawp_legal_load_chosen', 1 )
    }

    public function setTranslation(){
        $locale = get_locale();

        switch (substr($locale, 0, 2)) {
            case 'de': $locale = 'de_DE'; break;
            case 'en': $locale = 'en_US'; break;
            case 'it': $locale = 'it_CH'; break;
            case 'fr': $locale = 'fr_CH'; break;
            default: $locale = 'de_DE'; break;
        }


        //$locale_file = get_template_directory_uri() . "/includes/languages/$locale.php";
        /* $locale_file = CASAWP_LEGAL_PLUGIN_DIR . "languages/$locale.php";
        if ( is_readable( $locale_file ) ) {
            require_once( $locale_file );
        }*/
        load_plugin_textdomain('casawp-legal', false, '/casawp-legal/languages/' );
    }

    function setUploadDir($upload) {
        $upload['subdir'] = '/casawp-legal' . $upload['subdir'];
        $upload['path']   = $upload['basedir'] . $upload['subdir'];
        $upload['url']    = $upload['baseurl'] . $upload['subdir'];
        return $upload;
    }

}
