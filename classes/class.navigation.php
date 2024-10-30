<?php

class MoCo_SmartSearch_Filter extends MoCo_SmartSearch {

    public $facets = array(), $facetQueryFilters = array(), $design = null;

    /**
     * SmartSearch constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->classType = 'MoCo_SmartSearch_Filter';
        $this->facetFilters();
        $this->design = new MoCo_SmartFront();
    }

    /**
     * @param WP_Query $query
     * @throws Exception
     */
    public function smartsearch_results_override(WP_Query $query) {

        if ($this->performSearch($query)) {
            $this->searchTerm = $query->query_vars['s'];
            $this->cleanVar('searchTerm');
            $this->getCurrentPage();
            $query->set('suppress_filters',true);

            if (is_null($this->searchTerm)) {
                $this->smartsearch_navigation($query);
                return;
            }

            if ($this->smartSearchReady() && trim($this->searchTerm) !== "") {
                $this->processFilteredSearch($query);
            }
        }
    }

    /**
     * @param WP_Query $query
     * @throws Exception
     */
    public function smartsearch_navigation(WP_Query $query) {
        if ($this->smartSearchReady()) {
            $this->processFilteredSearch($query);
        }
    }


    public function makeQueryString($remove = false, $append = array(), $current = array()){
        if(is_array($current) && count($current)){
            if(is_array($current)) {
                if($remove!==false){
                    unset($current[$remove]);
                }
                $facetParams = array();
                foreach ($current as $facetFilterCombo) {
                    $facetFilter = $facetFilterCombo['field'];
                    $facetValue = $facetFilterCombo['term'];
                    $facetParams[] = $facetFilter . ':' . $facetValue;
                }
                $facetParams = implode(',',$facetParams);
                return $facetParams;
            }else{
                return $current;
            }

        }else {
            $toQuery = $this->facetQueryFilters;
            if (is_array($append) && count($append)) {
                $toQuery = array_merge($toQuery, $append);
            }
        }
        return \MCSmartSearch\MoCo_SmartSearch_Frontend::queryFacets($toQuery, $remove);
    }
    
    public function containsFacet($facet,$option = false){
        foreach($this->facetQueryFilters as $ff){
            list($facetFilter,$facetValue) = explode(':',$ff);
            if($facet === $facetFilter){
                if($option){
                    if($option === $facetValue){
                        return true;
                    }
                }else {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param WP_Query $query
     * @throws Exception
     */
    private function processFilteredSearch(WP_Query $query) {
        $cached_query = md5(json_encode(array($this->searchTerm, $this->resultLimit, $this->currentPage, $this->facetQueryFilters)));
        if(1==1 || !($trans = get_transient('cached_smart_query_'.$cached_query))) {
            $results = $this->smartSearch->filteredSearch(
                $this->searchTerm,
                $this->resultLimit,
                null,
                $this->currentPage,
                $this->facetQueryFilters
            );
            set_transient('cached_smart_query_' . $cached_query, serialize($results));
        }else{
            $results = unserialize($trans);
        }
        $this->setupFacetFilters($results);
        $this->prepareResults($query, $results);
    }

    /**
     *  Gather Filtered Facets & Clean
     */
    private function facetFilters() {
        $this->facetQueryFilters = (isset($_GET['facets']) && is_string($_GET['facets'])) ? explode(',',$_GET['facets']) : array();
        if(!is_null($this->facetQueryFilters)) {
            $this->facetQueryFilters = array_map(function ($s) {
                return $this->cleanString($s);
            }, $this->facetQueryFilters);
        }
    }

    private function setupFacetFilters($results){
        $GLOBALS['facets'] = $results->queries;
    }
}