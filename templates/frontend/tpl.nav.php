<?php
global $SmartSearchWP, $wp_query;
if(!is_null($SmartSearchWP) && $SmartSearchWP->is('MoCo_SmartSearch_Filter')){
    if(isset($GLOBALS['ssQuery'])){
        unset($GLOBALS['ssQuery']);
    }
    $args['smartsearch'] = true;
    $args['s'] = get_query_var('s');
    $args['post_type'] = 'product';

    $cat = $wp_query->get_queried_object();
    if($cat && $cat->name){
        $facets = $SmartSearchWP->facetQueryFilters;
        $SmartSearchWP->facetQueryFilters[] = 'CategoryName:' . trim($cat->name);
    }

    $ssQuery = new WP_Query();
    $ssQuery->query($args);
    $SmartSearchWP->smartsearch_navigation($ssQuery);
    $currentFacets = $GLOBALS['facets'];
    $GLOBALS['ssQuery'] = $ssQuery;
    echo('<div class="catalog-ordering smartsearch_filters clearfix">');
    krsort($SmartSearchWP->facets);
    foreach($SmartSearchWP->facets as $facetGroup => $facetOptions) {
        $filtered = $SmartSearchWP->containsFacet($facetGroup);
        ?>
        <ul class="order-dropdown">
            <li>
                <span class="current-li"><a aria-haspopup="true"><?php echo $facetGroup;?></a></span>
                <ul>
                    <li class="current">
                        <a href="#"><?php echo $facetGroup;?></a>
                    </li>

                    <?php
                    if ( ! empty( $facetOptions['options'] ) ) {
                        foreach($facetOptions['options'] as $facetOption => $facetCount) {
                            if($SmartSearchWP->containsFacet($facetGroup,$facetOption)){
                                $facetOption = strtoupper(preg_replace('/^([0-9]+)\-/','',$facetOption));
                                ?>
                                <li class="">
                                    <strong><a href="#"
                                       class="filter"
                                       facet="<?php echo $facetGroup; ?>"><?php echo esc_attr($facetOption); ?></a></strong>
                                </li>
                                <?php
                            }else {
                                if(1 == 2 && $endUrl = apply_filters('smart_search_nav_links',$facetGroup,$facetOption)){
                                    $fullUrl = $endUrl;
                                }else{
                                    $clearPaging = str_replace('/page/' . get_query_var('paged'), '', urldecode(trim($_SERVER['REQUEST_URI'])));
                                    $baseURL = '';
                                    if(substr($baseURL,-1) !== '?'){
                                        $baseURL .= '?';
                                    }

                                    if(isset($_GET['s'])){
                                        $baseURL .= 's=' . $_GET['s']. '&';
                                    }
                                    $fullUrl = $baseURL . 'facets=' . $SmartSearchWP->makeQueryString(false,array($facetGroup.':'.$facetOption));
                                }
                                $fullUrl = home_url() . '/shop/' . preg_replace('/([&]{2,})/','&',$fullUrl);

                                $facetOption = strtoupper(preg_replace('/^([0-9]+)\-/','',$facetOption));
                                ?>
                                <li class="">
                                    <a href="<?php echo($fullUrl); ?>"
                                       class="filter"
                                       facet="<?php echo $facetGroup; ?>"><?php echo esc_attr($facetOption); ?></a>
                                </li>
                                <?php
                            }
                        }
                    }
                    ?>
                </ul>
            </li>
        </ul>
    <?php }
    echo('</div>');

    MoCo_SmartFront::view('filters');
}
?>