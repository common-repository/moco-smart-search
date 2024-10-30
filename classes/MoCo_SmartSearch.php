<?php
namespace MCSmartSearch;

class MoCo_SmartSearch extends MoCo_SmartSearch_Base{

    /**
     *  Plugin Version
     */
    const SS_VERSION = '1.2';

    /**
     * Is setup complete?
     * @var bool
     */
    public $setupComplete = false;

    /**
     * SmartSearch API Key
     * @var null
     */
    public $smartSearchKey = null;

    /**
     * @var null
     */
    public $smartSearchItems = null;

    /**
     * URL To SmartSearch Engine
     * @var bool|string
     */
    public $smartSearchUrl = false;

    /**
     * Is SmartSearch turned on?
     * @var null
     */
    public $smartSearchActive = null;

    /**
     * Post types to include in index
     * @var null
     */
    public $smartSearchIncludes = null;

    /**
     * Post types ignored from indexing
     * @var array
     */
    public $ignored = array();

    /**
     * @var array
     */
    public $included = array();

    /**
     * Available facet groups
     * @var null
     */
    public $facets = null;

    /**
     * Enabled facet groups
     * @var null
     */
    public $facets_enabled = null;

    /**
     * @var array
     */
    private $post_options = array();

    private $admin_page = false;
    

    /**
     * MoCo_SmartSearch constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setupComplete = get_option('mocoss_setup');
        $this->smartSearchKey = get_option('mocoss_key');
        $this->smartSearchItems = get_option('mocoss_items');
        $this->smartSearchUrl = (trim($this->smartSearchKey) !== "") ? get_option('mocoss_url') : "";
        $this->smartSearchActive = get_option('mocoss_enabled');
        $this->smartSearchIncludes = get_option('mocoss_includes');
        $this->admin_page = (isset($_GET['page']) && $_GET['page'] == 'mc-smartsearch');
        if(is_admin() && $this->admin_page) {
            wp_enqueue_style('smartsearch_admin_bootstrap', $this->plugin_url . 'assets/admin/bootstrap.min.css');
            wp_enqueue_style('smartsearch_admin_bootstrap_theme', $this->plugin_url . 'assets/admin/bootstrap-theme.min.css');
            wp_enqueue_script('smartsearch_admin_bootstrap', $this->plugin_url . 'assets/admin/bootstrap.min.js', array('jquery'), MoCo_SmartSearch::SS_VERSION, true);
        }
    }

    /**
     * @return MoCo_SmartSearch|null
     */
    public static function instance(){
        if( is_null(self::$_instance )){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     *  Generate Dashboard
     */
    public static function smartsearch_dashboard()
    {
        global $SmartSearchWP_Admin;
        $SmartSearchWP_Admin->process_post();

        echo('<div class="wrap">');
        require_once(__DIR__ . '/../templates/header.php');

        $SmartSearchWP_Admin->dashboard_status();
        if($SmartSearchWP_Admin->setupComplete == "1") {
            require_once(__DIR__ . '/../templates/status.php');
        }else{
            wp_enqueue_style('smartsearch_admin_styles', $SmartSearchWP_Admin->plugin_url . 'assets/admin/admin_styles.css');
            require_once(__DIR__ . '/../templates/content.php');
        }
        echo('</div>');

    }

    /**
     *  Processes post data
     */
    public function process_post(){
        if( !$this->post_processed && $this->admin_page ) {
            $this->post_processed = true;
            if ($_POST) {
                check_ajax_referer('smartsearch_admin_status', 'security');
                try {
                    $posted = self::clean_post_data($_POST);
                    switch ($posted['step']) {
                        case 2:
                            update_option('mocoss_key', $posted['code']);
                            update_option('mocoss_url', $posted['url']);
                            $indexCount = self::smartsearch_getFeedCount();
                            update_option('mocoss_items', $indexCount);
                            echo('<indexCount>' . $indexCount . '</indexCount>');
                            break;
                        case 3:
                        case 4:
                            flush_rewrite_rules();
                            if (isset($posted['push'])) {
                                set_time_limit(0);
                                $feedToPush = self::smartsearch_getFeed(false);

                                $pushFeedResult = wp_remote_post($this->getDom() . '/manage/pluginfeedupload?psk=' . $this->smartSearchKey, array(
                                    'method' => 'POST',
                                    'timeout' => 45,
                                    'redirection' => 5,
                                    'httpversion' => '1.0',
                                    'blocking' => true,
                                    'headers' => array('Content-Type: text/plain'),
                                    'body' => $feedToPush,
                                    'cookies' => array()
                                ));

                                if ( is_wp_error( $pushFeedResult ) ) {
                                    $error_message = $pushFeedResult->get_error_message();
                                    echo "Something went wrong: $error_message";
                                }else {
                                    echo($pushFeedResult['body']);
                                }
                            }
                            update_option('mocoss_setup', '1');
                            update_option('mocoss_enabled', '1');
                            break;
                        case 5:
                            update_option('mocoss_enabled', '0');
                            flush_rewrite_rules();
                            break;
                    }
                    if (isset($posted['step'])) {
                        wp_die();
                        exit;
                    } else {
                        update_option('mocoss_includes', serialize($posted['types']));
                        update_option('mocoss_facets', serialize($posted['facets']));
                        update_option('mocoss_facets_enabled', serialize($posted['enabled']));
                    }
                } catch (\Exception $e) {
                    // Add error notice to admin
                    set_transient(self::ERROR_TRANSIENT, $e->getMessage());
                }
            }
        }
    }

    /**
     *  Prepare Data for Status Dashboard
     */
    public function dashboard_status(){
        wp_enqueue_style('smartsearch_admin_status_databales', $this->plugin_url . 'assets/gs-assets/DataTables/datatables.css');
        wp_enqueue_style('smartsearch_admin_styles', $this->plugin_url . 'assets/admin/admin_styles.css');

        wp_enqueue_script('smartsearch_admin_status_datatables', $this->plugin_url . 'assets/gs-assets/DataTables/datatables.js', array('jquery'),  MoCo_SmartSearch::SS_VERSION, true);
        wp_enqueue_script('smartsearch_admin_status_canvas', $this->plugin_url . 'assets/canvasjs/jquery.canvasjs.min.js', array('jquery'),  MoCo_SmartSearch::SS_VERSION, true);

        self::register_script( 'smartsearch_admin_status', $this->plugin_url . 'assets/admin/status.js', array('jquery'), self::SS_VERSION, true );
        if (!wp_script_is( 'smartsearch_admin_status', 'enqueued' )){
            self::enqueue_script('smartsearch_admin_status');
        }

        $this->secure_me();
        $this->set_vars();
    }

    /**
     *  Adds AJAX security and site url to JavaScript
     */
    private function secure_me(){
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('smartsearch_admin_status'),
            'siteurl' => get_site_url()
        );
        wp_localize_script( 'smartsearch_admin_status', 'moco_ss_status', $params );
    }
    
    /**
     *  Set object ignores, includes and facets
     */
    public function set_vars(){
        $this->ignored = get_option('mocoss_ignores');
        $this->facets = get_option('mocoss_facets');

        if(!$this->facets || is_null($this->facets)){
            add_option('mocoss_facets', serialize(array()), '', 'yes');
            $this->facets = array();
        }else{
            $this->facets = unserialize($this->facets);
        }

        $this->facets_enabled = get_option('mocoss_facets_enabled');
        if(!$this->facets_enabled || is_null($this->facets_enabled)){
            add_option('mocoss_facets_enabled', serialize($this->facets), '', 'yes');
            $this->facets_enabled = array();
        }else{
            $this->facets_enabled = unserialize($this->facets_enabled);
        }

        $this->assets()->setIgnores($this->ignored);
        $this->assets()->inc($this->smartSearchIncludes);
        $this->ignored = unserialize($this->ignored);
        $this->included = unserialize($this->smartSearchIncludes);
    }

    /**
     * Get posts with default data
     * @return array
     */
    public function getPosts(){
        $postOpts = $this->get_post_options();
        return $this->assets()->getPosts($postOpts['types']);
    }

    /**
     * Setup post options for SmartSearch
     * @return array
     */
    private function get_post_options(){
        if(!count($this->post_options)){
            $this->post_options = array(
                'types' => $this->assets()->getPostTypes(),
                'terms' => $this->assets()->getTerms(),
                'metas' => $this->assets()->getMeta()
            );
        }
        return $this->post_options;
    }
    
    /**
     * Get XML feed for indexing
     * @param bool $display
     * @return mixed
     */
    public static function smartsearch_getFeed($display = true){
        $defaults = array('contenttype','post_id','name','url','permalink','sename','hasimage','imageurl','author','realtype','body');
        $facetGroups = get_option('mocoss_facets');
        if(!$facetGroups || is_null($facetGroups)){
            add_option('mocoss_facets', serialize(array()), '', 'yes');
            $facetGroups = array();
        }else{
            $facetGroups = unserialize($facetGroups);
        }
        self::instance()->assets()->setIgnores(get_option('mocoss_ignores'));
        self::instance()->assets()->inc(get_option('mocoss_includes'));
        $keepMetas = array('yoast','slug');

        // Get Post Types, Terms, Metas
        $post_types = self::instance()->assets()->getPostTypes();
        $post_terms = self::instance()->assets()->getTerms();
        $post_metas = self::instance()->assets()->getMeta();

        $options = array(
            'types' => $post_types,
            'terms' => $post_terms,
            'metas' => $post_metas
        );

        $posts = self::instance()->assets()->getPosts($post_types);
        $xml = new SimpleXMLExtended('<ITEMS/>');

        foreach($posts as $type => $objects){
            foreach($objects as $postObject){
                $feedItem = $xml->addChild('ITEM');
                $feedItem->addChild('ContentType', 'Product');
                $feedItem->addChild('post_id', $postObject['key']);
                $feedItem->addChildCData('Name', $postObject['name']);
                $feedItem->addChildCData('url', $postObject['url']);
                @$feedItem->addChild('permalink', $postObject['permalink']);
                $feedItem->addChildCData('SEName', $postObject['sename']);
                $feedItem->addChild('HasImage', ($postObject['thumb'] !== '') ? 1 : 0);
                $feedItem->addChild('ImageUrl', $postObject['thumb']);
                $feedItem->addChild('Author', $postObject['author']);
                $feedItem->addChild('RealType', $postObject['type']);
                $feedItem->addChildCData('Body', htmlspecialchars($postObject['body']));

                $customFeedElements = apply_filters('smart_search_feed_elements',$postObject);
                if(is_array($customFeedElements)){
                    foreach($customFeedElements as $elementKey => $elementValue){
                        if(is_string($elementValue) && !in_array(strtolower($elementKey),$defaults)){
                            $feedItem->addChild($elementKey, $elementValue);
                        }
                    }
                }

                if(count($postObject['categories'])){
                    foreach($postObject['categories'] as $category){
                        $feedItem->addChild('CategoryName', $category);
                    }
                }

                if(count($postObject['terms'])){
                    foreach($postObject['terms'] as $term){
                        $feedItem->addChild('term', $term);
                    }
                }

                if(count($postObject['taxonomies'])){
                    foreach($postObject['taxonomies'] as $taxonomy){
                        $feedItem->addChild('taxonomy', $taxonomy);
                    }
                }

                if(self::instance()->assets()->hasWoo()) {
                    if (count($postObject['meta'])) {
                        foreach ($postObject['meta'] as $meta => $val) {
                            if (array_key_exists($meta, self::instance()->assets()->wooConversions)) {
                                $feedItem->addChild(self::instance()->assets()->wooConversions[$meta], $val);
                            }
                        }
                    }
                    if(count($postObject['misc'])){
                        foreach ($postObject['misc'] as $pattr) {
                            $pattr['display'] = trim(preg_replace('/[^a-zA-Z0-9]/','',$pattr['display']));
                            if(!in_array($pattr['display'],$facetGroups) && $pattr['display'] !== '') {
                                $facetGroups[] = $pattr['display'];
                            }
                            if(isset($pattr['labels']) && is_array($pattr['labels'])) {
                                foreach ($pattr['labels'] as $customAttribute) {
                                    $feedItem->addChild($pattr['display'], $customAttribute);
                                }
                            }
                        }
                    }
                    if(isset($postObject['variations']) && is_array($postObject['variations']) && count($postObject['variations'])){
                        $colors = array();
                        $variationsItem = $feedItem->addChild('Variants');
                        foreach($postObject['variations'] as $vItem){
                            $variant = $variationsItem->addChild('Variation');
                            $variant->addChild('VariantID', $vItem['variation_id']);
                            $variant->addChild('Price',number_format($vItem['display_price'],3));
                            $variant->addChild('Sku',$vItem['sku']);
                            $variant->addChild('Body',$vItem['variation_description']);
                            if(isset($vItem['attributes']) && is_array($vItem['attributes']) && count($vItem['attributes'])){
                                $attributes = $variant->addChild('Attributes');
                                foreach($vItem['attributes'] as $attrKey => $attrValue){
                                    $attrKeyConverted = self::instance()->convert_attr_key($attrKey);
                                    if($attrKeyConverted == "Color"){
                                        $colors[] = $attrValue;
                                    }
                                    $attributes->addChild($attrKeyConverted,$attrValue);
                                }
                            }
                        }
                        if(count($colors)){
                            $feedItem->addChild('Colors',implode(', ',array_unique($colors)));
                        }
                    }
                }
            }
        }
        if(count($facetGroups)){
            update_option('mocoss_facets', serialize($facetGroups));
        }
        if($display) {
            header('Content-type: text/plain');
            echo($xml->asXML());
            die();
        }else{
            return $xml->asXML();
        }
    }

    /**
     * Return count of indexable items
     * @return int
     */
    public static function smartsearch_getFeedCount(){
        self::instance()->assets()->setIgnores(get_option('mocoss_ignores'));
        self::instance()->assets()->inc(get_option('mocoss_includes'));

        // Get Post Types, Terms, Metas
        $post_types = self::instance()->assets()->getPostTypes();
        $post_terms = self::instance()->assets()->getTerms();
        $post_metas = self::instance()->assets()->getMeta();

        $options = array(
            'types' => $post_types,
            'terms' => $post_terms,
            'metas' => $post_metas
        );

        $posts = self::instance()->assets()->getPosts($options['types'],array(),array(),true);
        $indexCount = 0;
        foreach($posts as $type => $count){
            $indexCount += $count;
        }
        return $indexCount;
    }
}