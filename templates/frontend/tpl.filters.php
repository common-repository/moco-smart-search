<div class="filters" style="margin-bottom: 20px;">
    <?php
    global $SmartSearchWP;
    $currentFacets = $GLOBALS['facets'];
    if(isset($currentFacets) && is_array($currentFacets)) {
        foreach ($currentFacets as $i => $facetFilterCombo) {
            $facetFilter = $facetFilterCombo['field'];
            $facetValue = $facetFilterCombo['term'];
            $removeUrl = $SmartSearchWP->makeQueryString($i, array(), $currentFacets);
            $baseURL = str_replace($_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']);
            if(substr($baseURL,-1) !== '?'){
                $baseURL .= '?';
            }

            if(isset($_GET['s'])){
                $baseURL .= 's=' . $_GET['s']. '&';
            }
            if(trim($removeUrl) == ''){
                $clearFacet = $baseURL;
            }else {
                $clearFacet = $baseURL . 'facets=' . $removeUrl;
            }
            $clearFacet = strip_tags($clearFacet);

            $clearFacet = home_url() . preg_replace('/([&]{2,})/','&',$clearFacet);
            ?>
            <a href="<?php echo($clearFacet); ?>" class="filter removable"
               facet="<?php echo($facetFilter); ?>"><?php echo($facetValue); ?></a>
            <?php
        }
    }
    ?>
</div>