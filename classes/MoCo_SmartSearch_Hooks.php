<?php
namespace MCSmartSearch;

class MoCo_SmartSearch_Hooks {
    public $SmartSearchWP_Admin = null;

    public function __construct(){
        add_action('wp_loaded', array( $this, 'initSmartSearch') );
        add_action('wp_enqueue_scripts', array( $this, 'smartsearch_assets' ) );
    }
    
    protected function smartSearch(){
        return MoCo_SmartSearch::instance();
    }

    public function mocoss_activation() {

        // Default Includes
        $defaultIncludes = array();
        $defaultIncludes[] = 'product';

        // Common Ignores
        $defaultIgnores = array();
        $defaultIgnores[] = 'attachment';
        $defaultIgnores[] = 'nav_menu_item';
        $defaultIgnores[] = 'revision';
        $defaultIgnores[] = 'wysijap';
        $defaultIgnores[] = 'acf';
        $defaultIgnores[] = 'contact-form';
        $defaultIgnores[] = 'newsletter';
        $defaultIgnores[] = 'shop_coupon';
        $defaultIgnores[] = 'scheduled-action';
        $defaultIgnores[] = 'sliders';
        $defaultIgnores[] = 'reply';

        add_option('mocoss_key', '', '', 'yes');
        add_option('mocoss_url', $this->smartSearch()->getDom() . '/%s/s.aspx', '', 'yes');
        add_option('mocoss_enabled', '0', '', 'yes');
        add_option('mocoss_setup', '0', '', 'yes');
        add_option('mocoss_items', '0', '', 'yes');
        add_option('mocoss_includes', serialize($defaultIncludes), '', 'yes');
        add_option('mocoss_ignores', serialize($defaultIgnores), '', 'yes');
        add_option('mocoss_facets', serialize(array()), '', 'yes');
        add_option('mocoss_facet_ignores', serialize(array()), '', 'yes');
        add_option('mocoss_object', 'MoCo_SmartSearch', '', 'yes');
        add_option('mocoss_autocomplete', '1', '', 'yes');
    }

    public function smartsearch_autocomplete() {
        $searchTerm = trim($_POST['s']);
        $result = array('count' => 0);

        $ssac = new \MoCo_SmartSearch_AutoComplete();
        if ($terms = $ssac->complete($searchTerm)) {
            $result = $terms;
        }
        echo(json_encode($result));
        wp_die();
    }

    public function smartsearch_assets() {
        $ssAutoComplete = get_option('mocoss_autocomplete');
        if ($ssAutoComplete !== false) {
            if ($ssAutoComplete == '1') {
                wp_enqueue_style('smartsearch', plugins_url('../assets/ss.css', __FILE__));
                $customCssFile = get_stylesheet_directory() . '/smartsearch/styles.css';
                if (file_exists($customCssFile)) {
                    wp_enqueue_style('smartsearch_custom', get_stylesheet_directory_uri() . '/smartsearch/styles.css');
                }
                wp_enqueue_script('smartsearch', plugins_url('../assets/ss.js', __FILE__), array('jquery'), MoCo_SmartSearch::SS_VERSION, true);
                wp_localize_script('smartsearch', 'ss_object', array('url' => admin_url('admin-ajax.php')));
            }
        }
    }



    public function initSmartSearch() {
        global $SmartSearchWP;
        $ssObj = get_option('mocoss_object');
        if ($ssObj) {
            $SmartSearchWP = new $ssObj();
        } else {
            add_option('mocoss_object', 'MoCo_SmartSearch', '', 'yes');
            $SmartSearchWP = new MoCo_SmartSearch_Frontend();
        }

        $ssAutoComplete = get_option('mocoss_autocomplete');
        if ($ssAutoComplete !== false) {
            if ($ssAutoComplete == '1') {
                add_action('wp_ajax_smartsearch_autocomplete', array( $this, 'smartsearch_autocomplete' ) );
                add_action('wp_ajax_nopriv_smartsearch_autocomplete', array( $this, 'smartsearch_autocomplete' ) );
            }
        } else {
            add_option('mocoss_autocomplete', '1', '', 'yes');
            add_action('wp_ajax_smartsearch_autocomplete', array( $this, 'smartsearch_autocomplete' ) );
            add_action('wp_ajax_nopriv_smartsearch_autocomplete', array( $this, 'smartsearch_autocomplete' ) );
        }

    }

}