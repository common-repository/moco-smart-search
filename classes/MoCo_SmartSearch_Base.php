<?php
namespace MCSmartSearch;

class MoCo_SmartSearch_Base{

    /**
     * @var null
     */
    protected static $_instance = null;

    /**
     * @var null|string
     */
    protected $_feed = null;

    /**
     * @var null|string
     */
    protected $_dom = null;

    /**
     * @var MoCo_SmartSearch_Assets|null
     */
    protected $_assets = null;

    /**
     * Scripts required for extension
     * @var array
     */
    private static $scripts = array();

    public $post_processed = false;

    const ERROR_TRANSIENT = 'last_smartsearch_admin_error';
    
    protected $plugin_url = null;

    /**
     * @return MoCo_SmartSearch_Base|null
     */
    public static function instance(){
        if( is_null(self::$_instance )){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return null|string
     */
    public function getFeed() {
        return $this->_feed;
    }

    /**
     * @return null|string
     */
    public function getDom() {
        return $this->_dom;
    }

    /**
     * @return MoCo_SmartSearch_Assets|null
     */
    public function assets(){
        return $this->_assets;
    }

    /**
     * MoCo_SmartSearch_Base constructor.
     */
    public function __construct(){
        $this->_feed = untrailingslashit(get_site_url()) . "/feed/smartsearch";
        $this->_dom = "https://wooss.moco.biz";
        $this->plugin_url = plugins_url('/', __DIR__);

        require_once(__DIR__ . '/class.base.php');
        require_once(__DIR__ . '/class.smartsearch.php');
        require_once(__DIR__ . '/class.navigation.php');
        require_once(__DIR__ . '/class.autocomplete.php');
        require_once(__DIR__ . '/class.simplexmlextend.php');
        require_once(__DIR__ . '/class.frontend.php');

        require_once(__DIR__ . '/MoCo_SmartSearch_Frontend.php');

        $this->_assets = new MoCo_SmartSearch_Assets();

        add_action( 'admin_menu', array( $this, 'mcss_admin_actions' ) );
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'mcss_actions' ) );
        add_action( 'init', array( $this, 'smartsearch_init' ));
        add_filter( 'feed_content_type', array( $this, 'smartsearch_content_type' ) , 10, 2 );
    }


    /**
     *  Add SmartSearch to left admin menu
     */
    public function mcss_admin_actions()
    {
        add_menu_page("SmartSearch", "SmartSearch", 1, "mc-smartsearch", "MCSmartSearch\MoCo_SmartSearch::smartsearch_dashboard", plugins_url( 'moco-smart-search/assets/logo.png' ), 5);
    }

    /**
     * @param $links
     * @return array
     */
    public function mcss_actions($links ) {
        $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=mc-smartsearch') ) .'">Settings</a>';
        return $links;
    }

    /**
     *   Setup dynamic XML Feed - via Feeds System
     */
    public function smartsearch_init(){
        global $wp_rewrite;
        $smartSearchKey = get_option('mocoss_key');
        if(!empty($smartSearchKey)){
            $smartSearchKey = '/' . $smartSearchKey;
        }
        add_feed('smartsearch' . $smartSearchKey, array( $this, 'smartsearch_feed' ));
        $wp_rewrite->flush_rules( false );
    }

    /**
     * @param $content_type
     * @param $type
     * @return mixed|void
     */
    public function smartsearch_content_type($content_type, $type ) {
        if ( 'smartsearch_feed' === $type ) {
            return feed_content_type( 'text/plain' );
        }
        return $content_type;
    }

    /**
     *  Echo XML Feed
     */
    public function smartsearch_feed() {
        set_time_limit(-1);
        MoCo_SmartSearch::smartsearch_getFeed(true);
        die();
    }

    /**
     * @param $a
     * @param $b
     * @param bool $inArray
     */
    public static function ssMarkSelected($a, $b, $inArray = false){
        if($inArray === true) {
            if (in_array($a, $b)) {
                echo(' checked="checked"');
            }
        }elseif ($inArray === 'negative'){
            if (!in_array($a, $b)) {
                echo(' checked="checked"');
            }
        }elseif($a == $b){
            echo(' checked="checked"');
        }
    }

    /**
     * @return array|mixed
     */
    public static function startTimer() {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        return $time;
    }

    /**
     * @param $start
     * @return float
     */
    public static function getLoadTime($start){
        return round((self::startTimer() - $start), 4);
    }

    /**
     * @param $attr
     * @return string
     */
    public static function convert_attr_key($attr){
        return ucfirst(trim(str_replace('attribute_','',$attr)));
    }

    /**
     * Register a script for use.
     *
     * @uses   wp_register_script()
     * @access private
     * @param  string   $handle
     * @param  string   $path
     * @param  string[] $deps
     * @param  string   $version
     * @param  boolean  $in_footer
     */
    protected static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = SS_VERSION, $in_footer = true ) {
        self::$scripts[] = $handle;
        wp_register_script( $handle, $path, $deps, $version, $in_footer );
    }

    /**
     * Register and enqueue a script for use.
     *
     * @uses   wp_enqueue_script()
     * @access private
     * @param  string   $handle
     * @param  string   $path
     * @param  string[] $deps
     * @param  string   $version
     * @param  boolean  $in_footer
     */
    protected static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = SS_VERSION, $in_footer = true ) {
        if ( ! in_array( $handle, self::$scripts ) && $path ) {
            self::register_script( $handle, $path, $deps, $version, $in_footer );
        }
        wp_enqueue_script( $handle );
    }


    /**
     * @param array $data
     * @param bool $unset
     * @return array
     * @throws \Exception
     */
    protected static function clean_post_data($data = array(), $unset = false){

        foreach($data as $postName => $postValue){
            $postName = strip_tags(trim($postName));
            $postValue = is_array($postValue) ? $postValue : strip_tags(trim($postValue));
            switch(trim($postName)){
                case 'code':
                    // short string
                    if(is_string($postValue) && preg_match('/^([A-Z]{4}[0-9]{4})$/',$postValue)){
                        $data[$postName] = esc_attr($postValue);
                        continue;
                    }
                    break;
                case 'url':
                    // url
                    if(is_string($postValue) && preg_match('/^https\:\/\/wooss\.moco\.biz\/([A-Z]{4}[0-9]{4})\/s\.aspx$/',$postValue)){
                        $data[$postName] = esc_url($postValue);
                        continue;
                    }
                    break;
                case 'step':
                    // int
                    if(is_numeric($postValue) && preg_match('/^([1-5]{1})$/',$postValue)){
                        $data[$postName] = esc_attr($postValue);
                        continue;
                    }
                    break;
                case 'push':
                    // bool
                    if(is_bool($postValue) || $postValue === 'true'){
                        continue;
                    }
                    break;
                case 'types':
                case 'facets':
                case 'enabled':
                    // array of strings
                    if(!is_array($postValue)){
                        throw new \Exception('Unable to process ' . esc_attr($postName) . '.');
                    }else{
                        $data[$postName] = self::clean_post_data($postValue);
                    }
                    continue;
                    break;
                default:
                    // Default string cleaning
                    if(!is_array($postValue)){
                        $data[$postName] = esc_attr($postValue);
                    }else{
                        $data[$postName] = self::clean_post_data($postValue);
                    }
                    continue;
                    break;
            }
        }

        return $data;
    }
}