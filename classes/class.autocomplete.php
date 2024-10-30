<?php
/**
 * Class SmartSearch_AutoComplete
 */
class MoCo_SmartSearch_AutoComplete extends MoCo_SmartBase{

    private $fragment, $cache_hours, $transientKey = 'moco_smartsearch';

    /**
     * @param $fragment
     * @return array|bool|mixed
     * @throws Exception
     */
    public function complete($fragment){
        if ($this->smartSearchReady()) {
            $this->fragment = trim($fragment);
            $hash = $this->transientKey . '_' . md5($this->transientKey . $this->fragment);

            if(!($ac_results = get_transient($hash))) {
                $terms = $this->smartSearch->complete($this->fragment);
                $images = array();

                if ($terms->count) {

                    foreach($terms->terms as $term){
                        $results = $this->smartSearch->search($term->name, array(), 4, 1);

                        if ($results->count) {
                            if(!is_array($images[$term->name])){
                                $images[$term->name] = array();
                            }
                            foreach ($results->results as $iSR) {
                                $images[$term->name][] = array(
                                    'url'   => is_array($iSR->permalink) ? $iSR->permalink[0] : $iSR->permalink,
                                    'label' => $iSR->name,
                                    'image' => (trim($iSR->thumb) !== '' && is_string($iSR->thumb)) ? $iSR->thumb : plugins_url('woocommerce/assets/images/placeholder.png') ,
                                    'desc'  => ''
                                );
                            }
                        }
                    }

                    $ac_results = array($terms, $images);
                    set_transient($hash, $ac_results, $this->cache_hours * HOUR_IN_SECONDS);
                }
            }
            return $ac_results;
        }
        return false;
    }
}
