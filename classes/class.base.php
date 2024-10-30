<?php
/**
 * Class MoCo_SmartBase
 */
class MoCo_SmartBase {
    protected $smartSearch, $useWpSearch, $smartSearchActive, $ssKey, $resultLimit, $smartSearchUrl, $classType;

    /**
     * SmartSearch constructor.
     */
    public function __construct() {
        add_action('pre_get_posts', array($this, 'smartsearch_results_override'));

        $this->useWpSearch = (isset($_GET['engine']) && $_GET['engine'] == "wp");
        $this->smartSearchActive = get_option('mocoss_enabled');
        $this->ssKey = get_option('mocoss_key');
        $this->resultLimit = (int) get_option('posts_per_page');
        $this->smartSearchUrl = get_option('mocoss_url');
        if ($this->smartSearchActive == "1" && !is_null($this->ssKey) && trim($this->ssKey) !== '') {
            $this->smartSearch = new \MCSmartSearch\MoCo_SmartSearch_Frontend($this->ssKey, $this->smartSearchUrl);
        }else{
            $this->smartSearch = new \MCSmartSearch\MoCo_SmartSearch_Frontend();
        }
    }

    /**
     * @return bool
     */
    protected function smartSearchReady(){
        return (!$this->useWpSearch && !is_null($this->smartSearch) && $this->smartSearch->isSetup());
    }

    /**
     * @param $var
     */
    protected function cleanVar($var){
        $this->$var = $this->nullOrVal($var);
    }

    /**
     * @param $var
     * @return null|string
     */
    private function nullOrVal($var){
        return (isset($this->$var) && trim($this->$var) !== '') ? trim($this->$var) : null;
    }

    public function is($classType){
        return ($classType === $this->classType);
    }

    public function cleanString($str){
        return trim(strip_tags($str));
    }

    public function kd($d, $k = true){
        if($_SERVER['REMOTE_ADDR'] == get_option('mocoss_debugger')){
            if(is_string($d)){
                echo($d);
            }elseif (is_array($d)){
                echo('<pre>'.print_r($d,true).'</pre>');
            }elseif (is_object($d)){
                echo('<pre>'.print_r($d,true).'</pre>');
            }
            if($k) {
                die();
            }
        }
    }
}