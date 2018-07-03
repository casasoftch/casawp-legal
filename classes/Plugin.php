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

    public $translations = [
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
      ],
      'vat' => [
        'de' => 'MWST',
        'fr' => 'TVA',
        'en' => 'MWST',
        'it' => 'IVA',
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

  public function addToFootmenu(){
    $transcript['msgs'] = [];
    if (is_admin()) {
      echo '<div class="updated"><p><strong>' . __('Gennerating menu items', 'casawp' ) . '</strong></p></div>';
    }
    $transcript['msgs'][] = "gennerating foot menu and items";

    $imprintpage = get_option('casawp_legal_imprint', false);
    $termspage = get_option('casawp_legal_terms', false);

    $location = 'footmenu';
    $locations = get_nav_menu_locations();
    $menu_id = (array_key_exists($location, $locations) ?  $locations[ $location ] : false);
    $menu_exists = false;
    if ($menu_id) {
      $menu_exists = wp_get_nav_menu_object($menu_id);
    }

    // If it doesn't exist, let's create it.
    if( !$menu_exists){
        $menu_id = wp_create_nav_menu(__( 'Legal Menu', 'theme' ));
        if (is_admin()) {
          echo '<div class="updated"><p><strong>' . __('Menu did not exists so we created one', 'casawp' ) . '</strong></p></div>';
        }
        if ($imprintpage) {
          wp_update_nav_menu_item($menu_id, 0, array(
              'menu-item-title' => '',
              'menu-item-object-id' => $imprintpage,
              'menu-item-object' => 'page',
              'menu-item-status' => 'publish',
              'menu-item-type' => 'post_type',
          ));
        }
        if ($termspage) {
          wp_update_nav_menu_item($menu_id, 0, array(
              'menu-item-title' => '',
              'menu-item-object-id' => $termspage,
              'menu-item-object' => 'page',
              'menu-item-status' => 'publish',
              'menu-item-type' => 'post_type',
          ));
        }

        // Grab the theme locations and assign our newly-created menu
        // to the BuddyPress menu location.
        if( !has_nav_menu( $location ) ){
            if (is_admin()) {
              echo '<div class="updated"><p><strong>' . __('Menu was added to location ', 'casawp' ) . '</strong></p></div>';
            }
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }

        if (is_admin() && function_exists('icl_object_id')) {
          echo '<div class="updated"><p><strong>' . __('Your using WPML, go ahead and sync the menu here: ', 'casawp' ) . ': <a href="/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Fmenu-sync%2Fmenus-sync.php">Link Here</a></strong></p></div>';
        }
    } else {
      if (is_admin()) {
          echo '<div class="error"><p><strong>' . __('Menu exists already', 'casawp' ) . '</strong></p></div>';
        }
    }

    return $transcript;
  }

    public function makeSureTermsExists(){
      $transcript['msgs'] = [];
      if (is_admin()) {
        echo '<div class="updated"><p><strong>' . __('Gennerating term page', 'casawp' ) . '</strong></p></div>';
      }
      $transcript['msgs'][] = "gennerating term page";
      
      $main_lang = 'de';
      if (function_exists('icl_object_id') ) {
        $wpml_options = get_option( 'icl_sitepress_settings', false);
        if ($wpml_options) {
          $main_lang = $wpml_options['default_language'];
        }
      }

      $termspage = get_option('casawp_legal_terms', false);
      if ( 'publish' != get_post_status ( $termspage ) ) {
        $termspage = false;
      }
      if (!$termspage) {
    
        $page = get_page_by_title('Datenschutz');
        if (!$page) {
          $pageId = wp_insert_post(array(
            'post_title' => (array_key_exists($main_lang, $this->translations['terms']) ? $this->translations['terms'][$main_lang] : 'terms'),
            'post_status' => 'publish',
            'post_author'   => 1,
            'post_content'  => '<p style="display:none">&nbsp;</p>',
            'post_type' => 'page'
          ));
          if (is_admin()) {
            echo '<div class="updated"><p><strong>' . __('Gennerated terms page', 'casawp' ) . ' ' .$pageId . '</strong></p></div>';
          }
          $transcript['msgs'][] = "gennerated terms page". ' ' .$pageId;
          if (function_exists('icl_object_id') && get_option( 'icl_sitepress_settings', false )) {
            foreach (array_keys($this->translations['terms']) as $altLang) {
              if ($altLang !== $main_lang) {
                $altPageId = wp_insert_post(array(
                  'post_title' => $this->translations['terms'][$altLang],
                  'post_status' => 'publish',
                  'post_author'   => 1,
                  'post_content'  => '<p style="display:none">&nbsp;</p>',
                  'post_type' => 'page'
                ));
                $this->wpml_connect_page($pageId, $altPageId, $altLang);  
                if (is_admin()) {
                  echo '<div class="updated"><p><strong>' . __('Gennerated terms page', 'casawp' ) . ' for ' . $altLang . ' ' .$altPageId . '</strong></p></div>';
                }
                $transcript['msgs'][] = "gennerated terms page" . ' for ' . $altLang . ' ' .$altPageId;
              }
            }
          }
          
        } else {
          $pageId = $page->ID;
          $transcript['msgs'][] = "page already existed> " . $pageId;
        }
        update_option('casawp_legal_terms', $pageId);
      }

      return $transcript;
    }

    public function makeSureImprintExists(){
      $transcript['msgs'] = [];
      if (is_admin()) {
        echo '<div class="updated"><p><strong>' . __('Gennerating imprint page', 'casawp' ) . '</strong></p></div>';
      }
      $transcript['msgs'][] = "gennerating imprint page";
      
      $main_lang = 'de';
      if (function_exists('icl_object_id') ) {
        $wpml_options = get_option( 'icl_sitepress_settings', false);
        if ($wpml_options) {
          $main_lang = $wpml_options['default_language'];
        }
      }

      $imprintpage = get_option('casawp_legal_imprint', false);
      if ( 'publish' != get_post_status ( $imprintpage ) ) {
        $imprintpage = false;
      }
      if (!$imprintpage) {
        $page = get_page_by_title('Impressum');
        if (!$page) {
          $pageId = wp_insert_post(array(
            'post_title' => (array_key_exists($main_lang, $this->translations['imprint']) ? $this->translations['imprint'][$main_lang] : 'imprint'),
            'post_status' => 'publish',
            'post_author'   => 1,
            'post_content'  => '<p style="display:none">&nbsp;</p>',
            'post_type' => 'page'
          ));
          if (is_admin()) {
            echo '<div class="updated"><p><strong>' . __('Gennerated imprint page', 'casawp' ) . ' ' .$pageId . '</strong></p></div>';
          }
          $transcript['msgs'][] = "gennerated imprint page". ' ' .$pageId;
          if (function_exists('icl_object_id') && get_option( 'icl_sitepress_settings', false )) {
            foreach (array_keys($this->translations['imprint']) as $altLang) {
              if ($altLang !== $main_lang) {
                $altPageId = wp_insert_post(array(
                  'post_title' => $this->translations['imprint'][$altLang],
                  'post_status' => 'publish',
                  'post_author'   => 1,
                  'post_content'  => '<p style="display:none">&nbsp;</p>',
                  'post_type' => 'page'
                ));
                $this->wpml_connect_page($pageId, $altPageId, $altLang);  
                if (is_admin()) {
                  echo '<div class="updated"><p><strong>' . __('Gennerated imprint page', 'casawp' ) . ' for ' . $altLang . ' ' .$altPageId . '</strong></p></div>';
                }
                $transcript['msgs'][] = "gennerated imprint page" . ' for ' . $altLang . ' ' .$altPageId;
              }
            }
          }
        } else {
          $pageId = $page->ID;
          $transcript['msgs'][] = "page already existed> " . $pageId;
        }
        update_option('casawp_legal_imprint', $pageId);
      }

      return $transcript;
    }



    public function doReq($apikey, $privateKey, $options, $apiurl){
        //specify the current UnixTimeStamp
        $timestamp = time();

        //specify your api key
        $apikey = $apikey;

        //specify your private key
        $privatekey = $privateKey;

        //sort the options alphabeticaly and combine it into the checkstring
        ksort($options);
        $checkstring = '';
        foreach ($options as $key => $value) {
            $checkstring .= $key . $value;
        }
        
        //add private key at end of the checkstring
        $checkstring .= $privatekey;

        //add the timestamp at the end of the checkstring
        $checkstring .= $timestamp;

        //hash it to specify the hmac
        $hmac = hash('sha256', $checkstring, false);

        //combine the query (DONT INCLUDE THE PRIVATE KEY!!!)
        $query = array(
            'hmac' => $hmac,
            'apikey' => $apikey,
            'timestamp' => $timestamp
        ) + $options;

        //build url
        $url = $apiurl . '?' . http_build_query($query);

        $response = false;
        try {
            //$url = 'http://casacloud.cloudcontrolapp.com' . '/rest/provider-properties?' . http_build_query($query);
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $response = curl_exec($ch); 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($httpCode == 404) {
                $response = $httpCode;
            }
            curl_close($ch); 
        } catch (Exception $e) {
            $response =  $e->getMessage() ;
        }

        //present the result
        return array(
            'response' => $response,
            'url' => $url
        );
    }

    public function updateFieldIfEmpty($field, $value, $forced = false) {
      if ($forced || !get_option($field, false)) {
        update_option( $field, $value );
        if (is_admin()) {
          echo '<div class="updated"><p>updated Field: ' . $field . ' with value ' . $value . '</p></div>';
        } 
        return 'updated Field: ' . $field . ' with value ' . $value;
      } else {
        return 'skipped Field: ' . $field;
      }
    }

    public function fetchCompanyDataFromGateway($private_key, $public_key, $forced = false){
      $transcript = [];
      if (is_admin()) {
        echo '<div class="updated"><p><strong>' . __('Fetching data', 'casawp' ) . '</strong></p></div>';
      }
      $transcript['msgs'][] = print_r('Fetching data', true);

      $request = array(
          'apiurl' => 'http://casagateway.ch/rest/fetch-company-data-with-provider-key',
          'privatekey' => $private_key,
          'apikey' => $public_key,
          'options' => array(
              'debug' => 1
          )
      );

      $result = $this->doReq($request['apikey'], $request['privatekey'], $request['options'], $request['apiurl']);

      if (isset($result['response']) && $result['response']) {
        $response = json_decode($result['response'], true);
        if ($response && isset($response['companies']) && $response['companies'] && isset($response['companies'][0])) {
          $transcript['msgs'][] = 'successfully connected to casagateway and casaauth';
          if (is_admin()) {
            echo '<div class="updated"><p>'.print_r($response['companies'][0], true).'</div>';
          } 
          $transcript['msgs'][] = print_r($response['companies'][0], true);

          $prefix = 'casawp_legal_';

          if (isset($response['companies'][0]['legalName']) && $response['companies'][0]['legalName']) {
            $field = $prefix . 'company_legal_name';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['legalName'], $forced);
          }
          if (isset($response['companies'][0]['phone']) && $response['companies'][0]['phone']) {
            $field = $prefix . 'company_phone';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['phone'], $forced);
          }
          if (isset($response['companies'][0]['fax']) && $response['companies'][0]['fax']) {
            $field = $prefix . 'company_fax';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['fax'], $forced);
          }
          if (isset($response['companies'][0]['email']) && $response['companies'][0]['email']) {
            $field = $prefix . 'company_email';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['email'], $forced);
          }
          if (isset($response['companies'][0]['websiteUrl']) && $response['companies'][0]['websiteUrl']) {
            $field = $prefix . 'company_website_url';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['websiteUrl'], $forced);
          }
          if (isset($response['companies'][0]['uid']) && $response['companies'][0]['uid']) {
            $field = $prefix . 'company_uid';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['uid'], $forced);
          }
          if (isset($response['companies'][0]['vat']) && $response['companies'][0]['vat']) {
            $field = $prefix . 'company_vat';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['vat'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['street']) {
            $field = $prefix . 'company_address_street';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['street'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['streetNumber']) {
            $field = $prefix . 'company_address_street_number';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['streetNumber'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['postOfficeBoxNumber']) {
            $field = $prefix . 'company_address_post_office_box_number';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['postOfficeBoxNumber'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['postalCode']) {
            $field = $prefix . 'company_address_postal_code';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['postalCode'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['locality']) {
            $field = $prefix . 'company_address_locality';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['locality'], $forced);
          }
          if (isset($response['companies'][0]['address']) && $response['companies'][0]['address'] && $response['companies'][0]['address']['country']) {
            $field = $prefix . 'company_address_country';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['address']['country'], $forced);
          }
          if (isset($response['companies'][0]['legalPerson']) && $response['companies'][0]['legalPerson'] && $response['companies'][0]['legalPerson']['firstName']) {
            $field = $prefix . 'company_person_first_name';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['legalPerson']['firstName'], $forced);
          }
          if (isset($response['companies'][0]['legalPerson']) && $response['companies'][0]['legalPerson'] && $response['companies'][0]['legalPerson']['lastName']) {
            $field = $prefix . 'company_person_last_name';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['legalPerson']['lastName'], $forced);
          }
          if (isset($response['companies'][0]['legalPerson']) && $response['companies'][0]['legalPerson'] && $response['companies'][0]['legalPerson']['email']) {
            $field = $prefix . 'company_person_email';
            $transcript['msgs'][] = $this->updateFieldIfEmpty($field, $response['companies'][0]['legalPerson']['email'], $forced);
          }

        } else {
          $transcript['msgs'][] = 'no companies in response';
          $transcript['msgs'][] = $result;
        }
       
      } else {
        $transcript['msgs'][] = 'falsy response';
      }

      return $transcript;

    }

    static function countryIsoToLang($country){
      if ($country === "CH") {
        $country = "Schweiz";
      }
      if ($country === "LI") {
        $country = "Liechtenstein";
      }
      if ($country === "FL") {
        $country = "Liechtenstein";
      }
      return $country;
    }

    public function legalPageRenders($content){
      $cur_lang = 'de';
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
                'website_url' => get_option($prefix . 'company_website_url', null),
                'vat' => (get_option($prefix . 'company_vat', false) && array_key_exists($cur_lang, $this->translations['vat']) ? $this->translations['vat'][$cur_lang] : ''),
              ],
              'address' => [
                'street' => get_option($prefix.'company_address_street', null),
                'street_number' => get_option($prefix.'company_address_street_number', null),
                'post_office_box_number' => get_option($prefix.'company_address_post_office_box_number', null),
                'postal_code' => get_option($prefix.'company_address_postal_code', null),
                'locality' => get_option($prefix.'company_address_locality', null),
                'country' => $this->countryIsoToLang(get_option($prefix.'company_address_country', null)),
              ],
              'person' => [
                'first_name' => get_option($prefix.'company_person_first_name', null),
                'last_name' => get_option($prefix.'company_person_last_name', null),
                'email' => get_option($prefix.'company_person_email', null),
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
          $person = [
            'first_name' => get_option($prefix.'company_person_first_name', null),
            'last_name' => get_option($prefix.'company_person_last_name', null),
            'email' => get_option($prefix.'company_person_email', null),
          ];
          $person_is_empty = true;
          foreach ($person as $key => $value) {
            if ($value) {
              $person_is_empty = false;
              break;
            }
          }
          if ($person_is_empty) {
            $person = null;
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
                'website_url' => get_option($prefix . 'company_website_url', null),
                'vat' => (get_option($prefix . 'company_vat', false) && array_key_exists($cur_lang, $this->translations['vat']) ? $this->translations['vat'][$cur_lang] : ''),
              ],
              'address' => [
                'street' => get_option($prefix.'company_address_street', null),
                'street_number' => get_option($prefix.'company_address_street_number', null),
                'post_office_box_number' => get_option($prefix.'company_address_post_office_box_number', null),
                'postal_code' => get_option($prefix.'company_address_postal_code', null),
                'locality' => get_option($prefix.'company_address_locality', null),
                'country' => $this->countryIsoToLang(get_option($prefix.'company_address_country', null)),
              ],
              'person' => $person
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
        // $locale = get_locale();

        // switch (substr($locale, 0, 2)) {
        //     case 'de': $locale = 'de_DE'; break;
        //     case 'en': $locale = 'en_US'; break;
        //     case 'it': $locale = 'it_CH'; break;
        //     case 'fr': $locale = 'fr_CH'; break;
        //     default: $locale = 'de_DE'; break;
        // }


        //$locale_file = get_template_directory_uri() . "/includes/languages/$locale.php";
        /* $locale_file = CASAWP_LEGAL_PLUGIN_DIR . "languages/$locale.php";
        if ( is_readable( $locale_file ) ) {
            require_once( $locale_file );
        }*/
        // load_plugin_textdomain('casawp-legal', false, '/casawp-legal/languages/' );

        if (ICL_LANGUAGE_CODE) {
          $this->locale = ICL_LANGUAGE_CODE;
        }
    }

    function setUploadDir($upload) {
        $upload['subdir'] = '/casawp-legal' . $upload['subdir'];
        $upload['path']   = $upload['basedir'] . $upload['subdir'];
        $upload['url']    = $upload['baseurl'] . $upload['subdir'];
        return $upload;
    }

}
