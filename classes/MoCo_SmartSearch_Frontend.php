<?php
namespace MCSmartSearch;

/**
 * Class SmartSearch
 * @package MCSmartSearch
 */
class MoCo_SmartSearch_Frontend {

    private $_endpoint = 'https://wooss.moco.biz/%s/s.aspx';
    private $_feed;
    private $_endpointForced = false;
    protected $_setup = false;

    /**
     * MoCo_SmartSearch_Frontend constructor.
     * @param null $feedId
     * @param null $endPoint
     */
    public function __construct($feedId = null, $endPoint = null){
        if(!is_null($feedId)) {
            $this->setFeed($feedId);
        }
        if(!is_null($endPoint) && trim($endPoint) !== "") {
            $this->setEndpoint($endPoint);
        }
        if(isset($this->_feed) && isset($this->_endpoint)){
            $this->_setup = true;
        }
    }

    /**
     * @return bool
     */
    public function isSetup(){
        return $this->_setup;
    }

    /**
     * @param string $feed
     */
    public function setFeed($feed) {
        $this->_feed = $feed;
    }

    /**
     * @param $endpoint
     * @throws \Exception
     */
    public function setEndpoint($endpoint) {
        if(strpos($endpoint,'s.aspx') === false){
            throw new \Exception('Invalid Endpoint');
        }
        $this->_endpoint = $endpoint;
        $this->_endpointForced = true;
    }

    /**
     * @param $searchTerm
     * @param array $facets
     * @param int $pageSize
     * @param int $pageOffset
     * @return MoCo_SmartSearchResults
     * @throws \Exception
     */
    public function search($searchTerm, $facets = array(), $pageSize = 0, $pageOffset = 0){
        global $wp_version;
        $queryString = 'SearchString=' . str_replace(' ','+',$searchTerm);

        if((int)$pageSize > 0){
            $queryString .= '&pageSize=' . $pageSize;
        }

        if((int)$pageOffset > 0){
            $queryString .= '&pageNumber=' . $pageOffset;
        }

        if(is_array($facets)){
            foreach($facets as $field => $criteria){
                $queryString .= '&facetQuery=' . $field . ':' . str_replace(' ','+',$criteria);
            }
        }

        $curlEndpoint = (($this->_endpointForced) ? $this->_endpoint : sprintf($this->_endpoint,$this->_feed)) . '?' . $queryString;

        $remote = wp_remote_get($curlEndpoint, array(
            'timeout'     => 60,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
        ));

        try{

            if(!is_array($remote)){
                throw new \Exception('Getting SmartSearch data failed in Search!');
            }

            $output = $remote['body'];
            $simpleXml = simplexml_load_string($output);
            $json = json_decode(json_encode($simpleXml));

            $results = new MoCo_SmartSearchResults();
            $results->count = (int)$json->count;
            $results->pages = $json->pagecount;
            $results->page = $json->page;
            $results->pageSize = $json->pagesize;

            foreach((array)$json->suggest as $k => $suggestion){
                $results->suggestions[$k] = $suggestion;
            }

            if(is_array($json->hit)) {
                foreach ($json->hit as $found) {
                    $results->results[$found->id] = $this->makeItem($found);
                }
            }elseif (is_object($json->hit)){
                $results->results[$json->hit->id] = $this->makeItem($json->hit);
            }

        }catch(\Exception $e){
            throw new \Exception('Could not complete call to SmartSearch.');
        }

        return $results;
    }

    /**
     * @param $fragment
     * @return MoCo_SmartSearchItem
     * @throws \Exception
     */
    public function complete($fragment){
        global $wp_version;
        $queryString = 'autocomplete=' . str_replace(' ','+',$fragment);
        $curlEndpoint = (($this->_endpointForced) ? $this->_endpoint : sprintf($this->_endpoint,$this->_feed)) . '?' . $queryString;

        $remote = wp_remote_get($curlEndpoint, array(
            'timeout'     => 60,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
        ));

        try{

            if(!is_array($remote)){
                throw new \Exception('Getting SmartSearch data failed in AutoComplete!');
            }
            $output = $remote['body'];
            $simpleXml = simplexml_load_string($output);
            $json = json_decode(json_encode($simpleXml));

            $results = new MoCo_SmartSearchAutoComplete();
            if(is_array($json->term)) {
                foreach ($json->term as $act) {
                    $results->terms[] = new MoCo_SmartSearchAutoCompleteItem($act);
                    $results->count++;
                }
            }else{
                if(!is_null($json->term)) {
                    $results->terms[] = new MoCo_SmartSearchAutoCompleteItem($json->term);
                    $results->count = 1;
                }
            }

        }catch(\Exception $e){
            throw new \Exception('Could not complete call to SmartSearch.');
        }
        return $results;
    }

    /**
     * @param null $searchTerm
     * @param int $pageSize
     * @param array $facetGroups
     * @param int $pageOffset
     * @param array $facets
     * @return MoCo_SmartSearchFilteredResults
     * @throws \Exception
     */
    public function filteredSearch($searchTerm = null, $pageSize = 10, $facetGroups = array(), $pageOffset = 0, $facets = array()){
        global $wp_version;
        if(!count($facetGroups) || !is_array($facetGroups)) {
            $facetGroups = unserialize(get_option('mocoss_facets_enabled'));
        }
        if(!is_null($searchTerm) && $searchTerm !== 'all') {
            $queryString = 'searchstring=' . str_replace(' ', '+', $searchTerm);
        }else {
            $queryString = 'searchterm=MATCH_ALL:1';
        }
        $queryString .= '&pagenumber='.$pageOffset;
        $queryString .= '&pagesize='.$pageSize.'&facetgroup.mincount=1';
        $queryString .= '&facetgroup='.implode(',',$facetGroups);
        if(is_array($facets)){
            foreach($facets as $f){
                list($field, $criteria) = explode(':',$f,2);
                $queryString .= '&facetQuery=' . $field . ':' . str_replace(array(' ','&'),array('+','%26'),$criteria);
            }
        }
        $curlEndpoint = (($this->_endpointForced) ? $this->_endpoint : sprintf($this->_endpoint,$this->_feed)) . '?' . $queryString;

        $remote = wp_remote_get($curlEndpoint, array(
            'timeout'     => 60,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
        ));

        try{

            if(!is_array($remote)){
                throw new \Exception('Getting SmartSearch data failed in Filtered Search!');
            }
            $output = $remote['body'];
            $simpleXml = simplexml_load_string($output);
            $json = json_decode(json_encode($simpleXml));

            $results = new MoCo_SmartSearchFilteredResults();
            $results->count = (int)$json->count;
            $results->pages = $json->pagecount;
            $results->page = $json->page;
            $results->pageSize = $json->pagesize;

            foreach((array)$json->suggest as $k => $suggestion){
                $results->suggestions[$k] = $suggestion;
            }

            if(is_array($json->hit)) {
                foreach ($json->hit as $found) {
                    $results->results[$found->id] = $this->makeItem($found);
                }
            }elseif (is_object($json->hit)){
                $results->results[$json->hit->id] = $this->makeItem($json->hit);
            }

            if (is_object($json->facetgroups)){
                foreach($json->facetgroups->facetgroup as $fg){
                    if((int)$fg->{'@attributes'}->count > 0) {
                        $results->facets[$fg->name] = array(
                            'count' => $fg->{'@attributes'}->count,
                            'options' => array()
                        );

                        if(is_array($fg->facet)) {
                            foreach ($fg->facet as $f) {
                                if (trim($f->term) !== '') {
                                    $results->facets[$fg->name]['options'][$f->term] = $f->count;
                                }
                            }
                        }elseif (is_object($fg->facet)){
                            if (trim($fg->facet->term) !== '') {
                                $results->facets[$fg->name]['options'][$fg->facet->term] = $fg->facet->count;
                            }
                        }
                    }
                }
            }

            if (is_object($json->facetqueries)){
                foreach($json->facetqueries->facetquery as $fq){
                    if (is_object($fq)){
                        if (trim($fq->term) !== '' && $fq->field !== 'ContentType') {
                            $results->queries[] = array(
                                'field' => $fq->field,
                                'term' => $fq->term
                            );
                        }
                    }
                }
            }

        }catch(\Exception $e){
            throw new \Exception('Could not complete call to SmartSearch.');
        }

        return $results;
    }

    /**
     * @param $found
     * @return MoCo_SmartSearchItem
     */
    private function makeItem($found){
	    $item = new MoCo_SmartSearchItem();
        $item->id = (int) $found->id;
        $item->post_id = (int) $found->document->post_id;
        $item->url = (is_array($found->document->url)) ? $found->document->url[0] : $found->document->url;
        $item->permalink = (is_array($found->document->permalink)) ? $found->document->permalink[0] : $found->document->permalink;
        $item->thumb = ($found->document->ImageUrl !== '' && is_string($found->document->ImageUrl)) ? $found->document->ImageUrl : plugins_url('woocommerce/assets/images/placeholder.png');
        $item->name = $found->document->Name;
        $item->sename = $found->document->SEName;
        $item->body = $found->document->Body;
        $item->sku = $found->document->Sku;
        $item->price = $found->document->Price;
        $item->sale_price = $found->document->SalePrice;
	      $item->object = json_decode(base64_decode($found->document->Object));
        $item->type = $found->document->Type;

        if(is_array($found->document->ContentType)) {
            foreach ($found->document->ContentType as $contentType) {
                $item->contentType[] = $contentType;
            }
        }else{
            $item->contentType = $found->document->ContentType;
        }

        if(isset($found->document->category)) {
            if(!is_array($found->document->category)){
                $item->categories[] = $found->document->category;
            }else {
                foreach ($found->document->category as $category) {
                    $item->categories[] = $category;
                }
            }
        }
        return $item;
    }

    /**
     * @param $params
     * @param bool $remove
     * @return array
     */
    public static function queryFacets($params, $remove = false) {
        if(is_array($params)) {
            if($remove!==false){
                unset($params[$remove]);
            }
            $facetParams = array();
            foreach ($params as $facetFilterCombo) {
                list($facetFilter,$facetValue) = explode(':',$facetFilterCombo);
                $facetParams[] = $facetFilter . ':' . $facetValue;
            }
            $facetParams = implode(',',$facetParams);
            return $facetParams;
        }else{
            return $params;
        }
    }

}

/**
 * Class MoCo_SmartSearchResults
 * @package MCSmartSearch
 */
class MoCo_SmartSearchResults
{
    public $count, $pages, $page, $pageSize, $suggestions, $results;

    public function __construct(){
        $this->suggestions = $this->results = array();
    }
}

/**
 * Class MoCo_SmartSearchFilteredResults
 * @package MCSmartSearch
 */
class MoCo_SmartSearchFilteredResults extends MoCo_SmartSearchResults{
    public $facets, $queries;
}

/**
 * Class MoCo_SmartSearchItem
 * @package MCSmartSearch
 */
class MoCo_SmartSearchItem{
    public $id, $contentType, $post_id, $url, $permalink, $thumb, $name, $sename, $type, $categories, $body, $sku, $sale_price, $price, $object;

    public function __construct(){
        $this->contentType = $this->categories = array();
    }
}

/**
 * Class MoCo_SmartSearchAutoComplete
 * @package MCSmartSearch
 */
class MoCo_SmartSearchAutoComplete
{
    public $count, $terms;

    /**
     * MoCo_SmartSearchAutoComplete constructor.
     * @param $terms
     */
    public function __construct() {
        $this->count = 0;
        $this->terms = array();
    }

}

/**
 * Class MoCo_SmartSearchAutoCompleteItem
 * @package MCSmartSearch
 */
class MoCo_SmartSearchAutoCompleteItem{
    public $name;

    /**
     * MoCo_SmartSearchAutoCompleteItem constructor.
     * @param $name
     */
    public function __construct($name) {
        $this->name = $name;
    }
}
