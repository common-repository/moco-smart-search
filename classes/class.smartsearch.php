<?php
/**
 * Class MoCo_SmartSearch
 */
class MoCo_SmartSearch extends MoCo_SmartBase{
    protected $found_posts, $results, $post_ids, $temp_posts = array();
    protected $searchTerm, $currentPage;

    /**
     * SmartSearch constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->classType = 'SmartSearch';
        add_action('pre_get_posts', array($this, 'smartsearch_results_override'));
    }

    /**
     * @param WP_Query $query
     * @return bool
     */
    protected function performSearch(WP_Query $query) {
        return ((!$query->is_admin && $query->is_search()) || isset($query->query_vars['smartsearch']));
    }

    /**
     *  Get current page for pagination
     */
    protected function getCurrentPage(){
        $this->currentPage = 1;
        if (get_query_var('paged')) {
            $this->currentPage = get_query_var('paged');
        } elseif (get_query_var('page')) {
            $this->currentPage = get_query_var('page');
        }
    }

    /**
     * Display SmartSearch Results
     * @param WP_Query $query
     */
    public function smartsearch_results_override(WP_Query $query) {
        if ($this->performSearch($query)) {
            $this->getCurrentPage();
            $this->searchTerm = $query->query_vars['s'];
            if ($this->smartSearchReady() && trim($this->searchTerm) !== "") {
                $results = $this->smartSearch->search($this->searchTerm, array(), $this->resultLimit, $this->currentPage);
                $this->prepareResults($query, $results);
            }
        }
    }

    /**
     * @param WP_Query $query
     * @param $results
     */
    public function prepareResults(WP_Query $query, $results){
        if ($results->count) {
            $this->setupFilters();
            $this->found_posts = $results->count;

            $post_ids = array();
            foreach ($results->results as $result) {
                $post_ids[] = $result->post_id;
            }

            $this->post_ids = $post_ids;
            $this->results = $results->results;
            if($this instanceof MoCo_SmartSearch_Filter) {
                $this->facets = $results->facets;
            }

            $query->post_count = count($this->results);
            $query->max_num_pages = ceil( $this->found_posts / $this->resultLimit );
            $query->set('paged', (int)$results->page);
            $query->found_posts = (int) $this->found_posts ;
            $this->setupQueryVars($query, $post_ids);
        }
    }

    /**
     *  Hook into post filters
     */
    protected function setupFilters(){
        add_filter( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
        add_filter( 'found_posts', array( $this, 'found_posts' ), 10, 2 );
        add_filter( 'posts_search', array( $this, 'posts_search' ), 10, 2 );
        add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
        add_filter( 'posts_pre_query', array( $this, 'posts_pre_query'), 10, 2 );
    }

    /**
     * @param WP_Query $query
     * @param array $post_ids
     */
    protected function setupQueryVars(WP_Query $query, $post_ids = array()){
        $query->set('posts_per_page', $this->resultLimit);
        $query->set('offset', 0);
        $query->set('post_type', (empty(trim(strtolower($_GET['post_type'])))) ? 'product' : trim(strtolower($_GET['post_type'])));
        $query->set('post__in', $post_ids);
        $query->set('post_status', 'any');
        $query->set('smartsearch', true);
    }

    /**
     * @param array $posts
     * @param WP_Query $query
     * @return array
     */
    public function the_posts(array $posts, WP_Query $query ) {
        if ( ! $this->performSearch( $query ) ) {
            return $posts;
        }
        return $posts;
    }

    /**
     * @param $found_posts
     * @param WP_Query $query
     * @return mixed
     */
    public function found_posts($found_posts, WP_Query $query ) {
        return ($this->performSearch( $query )) ? $this->found_posts : $found_posts;
    }

    /**
     * @param $search
     * @param WP_Query $query
     * @return string
     */
    public function posts_search($search, WP_Query $query ) {
        return ($this->performSearch( $query )) ? '' : $search;
    }

    /**
     * @param $pieces
     * @param WP_Query $query
     * @return mixed
     */
    public function posts_clauses($pieces, WP_Query $query ) {
        if (! $this->performSearch( $query ) ) {
            return $pieces;
        }
        $pieces['where'] = ' AND 1=2';
        $pieces['orderby'] = '';
        return $pieces;
    }

    /**
     * @param $null
     * @param WP_Query $query
     * @return array
     */
    public function posts_pre_query($null, WP_Query $query){
        if ( $this->performSearch( $query ) ) {
            $tempPosts = $post_ids = array();
            
            foreach ($this->results as $ssID => $ssPost) {
                $tempPost = new \stdClass();
                $tempPost->ID = $ssPost->post_id;
                $tempPost->post_author = "1";
                $tempPost->post_name = $ssPost->sename;
                $tempPost->post_title = $ssPost->name . (($_GET['conf'] == 'ss') ? ' xSS' : '');
                $tempPost->post_type = (is_array($ssPost->contentType)) ? trim(strtolower($ssPost->contentType[1])) : trim(strtolower($ssPost->contentType));
                $tempPost->filter = 'raw';
                $tempPost->thumb = $ssPost->thumb;

                $tempPosts[] = new \WP_Post($tempPost);
                $this->temp_posts[$ssPost->post_id] = $tempPost;
            }
            return $tempPosts;
        }
        return $query->posts;
    }
}