<?php
namespace MCSmartSearch;

class MoCo_SmartSearch_Assets {

    private $wpdb, $db, $woo, $wooPages, $ignores, $includes, $lite;
    public $wooConversions;
    public function __construct($lite = false) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->db = new \mysqli(str_replace(':3306','',DB_HOST),DB_USER,DB_PASSWORD,DB_NAME);
        $this->lite = $lite;
        $this->includes = array();
        $this->ignores = array();
        $this->ignores[] = 'revision';
        $this->ignores[] = 'auto-draft';
        if(function_exists('is_plugin_active')){
            $this->woo = is_plugin_active( 'woocommerce/woocommerce.php' );
        }else{
            $this->woo  = is_dir( __DIR__ . '/../../woocommerce');
        }
        if ( $this->woo ) {
            $this->ignores[] = 'product_variation';
            $this->ignores[] = '[woocommerce_cart]';
            $this->ignores[] = '[woocommerce_checkout]';
            $this->ignores[] = '[woocommerce_my_account]';

            $this->wooPages = array();
            $woocommerce_pages = array('shop','cart','checkout','terms','myaccount');
            foreach($woocommerce_pages as $wooPage){
                $this->wooPages[] = get_option('woocommerce_' . $wooPage . '_page_id');
            }

            $this->wooConversions = array();
            $this->wooConversions['_sku'] = 'Sku';
            $this->wooConversions['_price'] = 'Price';
            $this->wooConversions['_sale_price'] = 'SalePrice';
            $this->wooConversions['total_sales'] = 'Sold';
        }
    }

    public function getPostTypes(){
        $options = array();
        $optionsQuery = "SELECT post_type AS type FROM {$this->wpdb->prefix}posts GROUP BY post_type ORDER BY post_type ASC";
        $optionsResult = mysqli_query($this->db,$optionsQuery) or $this->Complain(mysqli_error($this->db));
        while($option = mysqli_fetch_assoc($optionsResult)){
            $options[] = $option['type'];
        }
        return (count($options) > 0) ? $options : false;
    }

    public function getTerms(){
        $options = array();
        $optionsQuery = "SELECT name AS term FROM {$this->wpdb->prefix}terms GROUP BY name ORDER BY name ASC";
        $optionsResult = mysqli_query($this->db,$optionsQuery) or $this->Complain(mysqli_error($this->db));
        while($option = mysqli_fetch_assoc($optionsResult)){
            $options[] = $option['term'];
        }
        return (count($options) > 0) ? $options : false;
    }

    public function getMeta(){
        $options = array();
        $optionsQuery = "SELECT meta_key AS `key` FROM {$this->wpdb->prefix}postmeta GROUP BY meta_key ORDER BY meta_key ASC";
        $optionsResult = mysqli_query($this->db,$optionsQuery) or $this->Complain(mysqli_error($this->db));
        while($option = mysqli_fetch_assoc($optionsResult)){
            $options[] = $option['key'];
        }
        return (count($options) > 0) ? $options : false;
    }

    public function getPosts($postType,$ignores = array(),$metas = array(), $count = false){
        $posts = array();
        $ignores = (is_array($this->ignores)) ? $ignores + $this->ignores : $ignores;
        if(is_array($postType)){
            foreach($postType as $pt){
                if(!in_array($pt,$ignores) && in_array($pt,$this->includes)){
                    $posts[$pt] = $this->_getPosts($pt,$metas,$count);
                }
            }
        }else{
            $posts = $this->_getPosts($postType,$metas,$count);
        }
        return $posts;
    }

    private function _getPosts($postType,$validMetas,$count = false){
        global $wpdb, $more, $post;
        $args = array( 'post_type' => $postType, 'posts_per_page' => -1, 'post_status' => 'publish', 'has_password' => false);
        $loop = new \WP_Query( $args );

        if($count){
            return (int)$loop->post_count;
        }

        $posts = array();
        $post = null;
        if(!$this->lite) {
            while ($loop->have_posts()) : $loop->the_post();
                $postId = $post->ID;

                $checkContent = trim($post->post_content);
                if (in_array($checkContent, $this->ignores)) {
                    continue;
                }
                if ($this->woo && in_array($postId, $this->wooPages)) {
                    continue;
                }
                $isProduct = ($post->post_type == 'product');
                
                if($isProduct){
                    $_product = wc_get_product($postId);
                    $current_stock = $_product->get_stock_quantity();
                    if($current_stock < 1 || $_product->get_stock_status() !== 'instock'){
                        continue;
                    }
                }
                
                $posts[$postId] = array(
                    'key' => $postId,
                    'name' => $post->post_title,
                    'url' => wp_get_shortlink(),
                    'permalink' => get_permalink(),
                    'sename' => $loop->post->post_name,
                    'categories' => array(),
                    'terms' => array(),
                    'taxonomies' => get_object_taxonomies($post),
                    'thumb' => null,
                    'body' => null,
                    'meta' => array(),
                    'author' => get_the_author(),
                    'type' => ucfirst($post->post_type),
                    'misc' => array()
                );

                // Get the Body, after shortcode evaluation
                $more = 1;
                $content = get_the_content(null, null);
                $content = apply_filters('the_content', $content);
                $content = str_replace(']]>', ']]&gt;', $content);
                $posts[$postId]['body'] = $content;

                if ($isProduct) {
                    $custom_attributes = get_post_meta($postId, '_product_attributes', true);
                    $count = 0;
                    $attributes = array();
                    if ($custom_attributes && count($custom_attributes) > 0) {
                        foreach ($custom_attributes as $taxonomy) {
                            $name = $taxonomy['name'];
                            if (!array_key_exists($name, $attributes)) {
                                $attributes[$name] = array('display' => wc_attribute_label($name), 'labels' => array(), 'url' => null);
                            }
                            if ($taxonomy['is_taxonomy'] == 1) {
                                $terms = get_the_terms($postId, $name);
                                if ($terms && !is_wp_error($terms)) {
                                    foreach ($terms as $term) {
                                        $count++;

                                        // TODO: Find a way to track URL's to maintain SEO
                                        //$attributes[$name]['labels'][] = array( 'name' => esc_attr($term->name),'url' => '/' . $term->taxonomy . '/' . $term->slug);
                                        $attributes[$name]['labels'][] = esc_attr($term->name);
                                    }
                                }
                                $posts[$postId]['misc'] = $attributes;
                            }
                        }
                    }
                    $posts[$postId] = apply_filters('smart_search_product', $posts[$postId], $postId);
                }

                // Get Categories
                if (is_array($categories = get_the_category($postId))) {
                    if (count($categories)) {
                        foreach ($categories as $category) {
                            $posts[$postId]['categories'][$category->slug] = $category->name;
                        }
                    }
                }

                // Get Taxonomies
                $taxos = get_taxonomies();
                foreach ($taxos as $key => $taxVal) {
                    $terms = get_the_terms($postId, $taxVal);

                    if (is_array($terms)) {
                        foreach ($terms as $term) {
                            $posts[$postId][(($isProduct) ? 'categories' : 'terms')][$term->slug] = $term->name;
                            if($isProduct){
                                $posts[$postId]['attributes'][str_replace('pa_','',$term->taxonomy)] = $term->name;
                            }
                        }
                    }
                }

                // Get Product Variations
                if ($isProduct && $this->woo) {
                    $product = get_product($postId);
                    if ($product->product_type === 'variable') {
                        $posts[$postId]['variations'] = $product->get_available_variations();
                    }

                    // Prepare Woo Metas
                    $validMetas = $validMetas + array_keys($this->wooConversions);
                }

                // Get Metas
                if (is_array($metas = get_post_meta($postId))) {
                    foreach ($metas as $metaKey => $metaValue) {
                        if (!array_key_exists($metaKey, $posts[$postId]['meta'])) {
                            $posts[$postId]['meta'][$metaKey] = array();
                        }
                        foreach ($validMetas as $vMeta) {
                            if (trim($metaKey) == trim($vMeta)) {
                                $posts[$postId]['meta'][$metaKey] = ($metaKey == '_price' || $metaKey == '_sale_price') ? number_format((double) $metaValue[0], 3) : $metaValue[0];
                            }
                        }
                    }
                }

                $thumb = wp_get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail');
                $posts[$postId]['thumb'] = $thumb['0'];

            endwhile;
        }else{
            $begin_time = microtime(true);
            $elapsed_time = 0;

            $wpdb->query('SET SESSION group_concat_max_len = 10000'); // necessary to get more than 1024 characters in the GROUP_CONCAT columns below
            $query = "
    SELECT p.*,
    (SELECT ip.guid
     FROM $wpdb->postmeta AS ipm
     INNER JOIN $wpdb->posts AS ip ON ip.ID = ipm.meta_value
     WHERE ipm.meta_key = '_thumbnail_id'
     AND ipm.post_id = p.ID) AS thumb,
    GROUP_CONCAT(pm.meta_key ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_keys,
    GROUP_CONCAT(pm.meta_value ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_values
    FROM $wpdb->posts p
    LEFT JOIN $wpdb->postmeta pm on pm.post_id = p.ID
    WHERE p.post_type = 'product' and p.post_status = 'publish' AND pm.meta_key NOT IN  ('_sold_individually','_stock')
    GROUP BY p.ID
    LIMIT 1000
";

            $products = $wpdb->get_results($query);

            $elapsed_time = microtime(true) - $begin_time;
            $timer_seconds = $elapsed_time; //10 seconds

            // massages the products to have a member ->meta with the unserialized values as expected
            $products = array_map(function($a){
                $keys = explode('||',$a->meta_keys);
                $values = array_map('maybe_unserialize',explode('||',$a->meta_values));
                $a->meta = array_combine($keys,$values);
                unset($a->meta_keys);
                unset($a->meta_values);
                return $a;
            },$products);

            foreach($products as $post){
                $postId = $post->ID;

                $checkContent = trim($post->post_content);
                if (in_array($checkContent, $this->ignores)) {
                    continue;
                }
                if ($this->woo && in_array($postId, $this->wooPages)) {
                    continue;
                }
                $isProduct = ($post->post_type == 'product');
                $posts[$postId] = array(
                    'key' => $postId,
                    'name' => $post->post_title . ' in ' . $timer_seconds,
                    'url' => wp_get_shortlink(),
                    'permalink' => get_permalink(),
                    'sename' => $post->post_name,
                    'categories' => array(),
                    'terms' => array(),
                    'taxonomies' => null,
                    'thumb' => $post->thumb,
                    'body' => null,
                    'meta' => $post->meta,
                    'author' => null,
                    'type' => ucfirst($post->post_type),
                    'misc' => array()
                );

                // Get the Body, after shortcode evaluation
                $more = 1;
                $content = apply_filters('the_content', $checkContent);
                $content = str_replace(']]>', ']]&gt;', $content);
                $posts[$postId]['body'] = $content;

                // Get Product Variations
                if (1 == 2 && $isProduct && $this->woo) {
                    $product = get_product($postId);
                    if ($product->product_type === 'variable') {
                        $posts[$postId]['variations'] = $product->get_available_variations();
                    }

                    // Prepare Woo Metas
                    $validMetas = $validMetas + array_keys($this->wooConversions);
                }
            }
        }
        return $posts;
    }

    /**
     * @param array $ignores
     */
    public function setIgnores($ignores) {
        $this->ignores = ($ignores) ? unserialize($ignores) : array();
    }

    public function inc($includes){
        $this->includes = ($includes) ? unserialize($includes) : array();
    }

    public function hasWoo(){
        return $this->woo;
    }

    private function Complain($e){
        throw new \Exception($e);
    }
}
