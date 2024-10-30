<?php
/*
* Plugin Name: Smart Search for WooCommerce
* Plugin URI: https://woodev.us/product/smart-search-woocommerce/
* Description: Create an intuative and dynamic browsing experience for your customers base on our search technology. Easily filter, segment, and sort your content allowing increased conversions.
* Author: WooDev
* Author URI: https://woodev.us/
* Version: 1.4
*
* Copyright: © 2007-2017 Morrison Consulting, LLC.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('classes/MoCo_SmartSearch_Base.php');
require_once('classes/MoCo_SmartSearch.php');
require_once('classes/MoCo_SmartSearch_Assets.php');
require_once('classes/MoCo_SmartSearch_Hooks.php');

function MoCo_SmartSearch(){
    return MCSmartSearch\MoCo_SmartSearch::instance();
}
function MoCo_SmartSearch_Front(){
    return new MCSmartSearch\MoCo_SmartSearch_Hooks();
}
$GLOBALS['SmartSearchWP_Admin'] = MoCo_SmartSearch();
$GLOBALS['SmartSearchWP'] = null;
MoCo_SmartSearch_Front();


/**
 *  Error handler when updating settings in admin area
 */
function smartsearch_bad_post_data__error() {
    global $SmartSearchWP_Admin;
    $SmartSearchWP_Admin->process_post();
    $transientKey = $SmartSearchWP_Admin::ERROR_TRANSIENT;
    $last_smartsearch_admin_error = get_transient($transientKey);
    if( !is_null( $last_smartsearch_admin_error ) && $last_smartsearch_admin_error ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php _e('SmartSearch Data', 'moco'); ?>
                    :</strong> <?php _e($last_smartsearch_admin_error, 'moco'); ?>
            </p>
        </div>
        <?php
        delete_transient($transientKey);
    }
}
add_action('admin_notices', 'smartsearch_bad_post_data__error' );

register_activation_hook( __FILE__ , array( new MCSmartSearch\MoCo_SmartSearch_Hooks(), 'mocoss_activation') );
