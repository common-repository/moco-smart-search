<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>

<div class="row">

    <?php
    if($SmartSearchWP_Admin->smartSearchActive == 1){
        $actionButton = '<button id="disableSS" type="button" class="btn btn-warning btn-lg">Disable SmartSearch</button>';
        $systemStatus = <<<SS
<div class="alert alert-success" role="alert">
    <div class="row">
        <div class="col-md-12 text-left">
            <strong><span class="glyphicon glyphicon-ok"></span> SmartSearch is currently enabled.</strong>
        </div>
    </div>
</div>
SS;
    }else{
        $actionButton = '<button id="enableSS" type="button" class="btn btn-success btn-lg">Enable SmartSearch</button>';
        $systemStatus = <<<SS
<div class="alert alert-warning text-center" role="alert">
    <div class="row">
        <div class="col-md-12 text-left">
            <strong>SmartSearch is currently disabled.</strong>
        </div>
    </div>
</div>
SS;
    }
    echo($systemStatus);
    ?>
    <?php
    if ( $SmartSearchWP_Admin->assets()->hasWoo() ) {
        ?>
        <div class="alert alert-info text-center" role="alert">
            <h3 style="margin:0">We noticed you are using WooCommerce!</h3>
            That is cool with us, your products will be included into your SmartSearch feed and results.
        </div>
        <?php
    }
    ?>
    <div>
        <div class="col-md-3 text-center">
            <?php echo($actionButton); ?>
            <br>
            <a href="<?php echo($SmartSearchWP_Admin->getFeed()); ?>/<?php echo($SmartSearchWP_Admin->smartSearchKey); ?>" target="_blank">View XML Feed</a>
        </div>
        <div class="mocoStatusItem col-md-3">
            <strong>SmartSearch Code</strong>
            <span id="mss_key"><?php echo($SmartSearchWP_Admin->smartSearchKey); ?></span>
        </div>
        <div class="mocoStatusItem col-md-3">
            <strong>SmartSearch URL</strong>
            <span id="mss_url" style="font-size: 14px"><?php echo($SmartSearchWP_Admin->smartSearchUrl); ?></span>
        </div>
        <div class="mocoStatusItem col-md-3">
            <strong>Items Indexed Last</strong>
            <span id="mss_count"><?php echo($SmartSearchWP_Admin->smartSearchItems); ?></span> <a href="<?php echo(admin_url('admin.php?page=mc-smartsearch')) ?>" style="font-size:10px"><span class="glyphicon glyphicon-refresh" style="font-size:13px"></span></a>
        </div>
    </div>

    <div class="col-md-12 text-center" style="margin-top: 20px;" id="" >
        <div>

            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#tabItems" aria-controls="home" role="tab" data-toggle="tab">Indexed Items</a></li>
                <li role="presentation"><a href="#tabSettings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
                <li role="presentation"><a href="#tabSynonyms" aria-controls="synonyms" role="tab" data-toggle="tab">Synonym Library</a></li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="tabItems">
                    <?php
                    $posts = array(); //$SmartSearchWP_Admin->getPosts();
                    ?>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <table id="smartSearchItems">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Permalink</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Permalink</th>
                                </tr>
                                </tfoot>
                                <tbody>
                                <?php
                                $displayed = 0;
                                $limit = 10;
                                foreach ($posts as $type => $objects) {
                                    foreach ($objects as $postObject) {
                                        $displayed++;
                                        ?>
                                        <tr class="previewSSRow">
                                            <td><?php echo($postObject['key']); ?></td>
                                            <td class="text-left"><?php echo($postObject['name']); ?></td>
                                            <td><?php echo($type); ?></td>
                                            <td class="text-left"><a href="<?php echo($postObject['permalink']); ?>" target="_blank"><?php echo($postObject['permalink']); ?></a></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                if($displayed <= $limit){
                                    $limit = $displayed;
                                }
                                update_option( 'mocoss_items', $displayed );
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tabSettings" style="text-align: left">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-4">
                                <h3>Indexed Objects</h3>
                                <div class="h3info">Selecting items to index allows SmartSearch to only index what you would like. This is useful when hiding private information such as shopping cart orders or password protected pages.</div>
                            </div>
                            <div class="col-md-4">
                                <h3>Facet Groups</h3>
                                <div class="h3info">Facet groups are used to generate menus, reductive navigation and accurate browsing. We generate these based on information found in your system. These can be managed manually also if we happen to miss anything.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div>
                                    <?php
                                    $postOptions = $SmartSearchWP_Admin->get_post_options();
                                    if ($postOptions['types']) {
                                        foreach ($postOptions['types'] as $type) {
                                            ?>
                                            <label>
                                                <input type="checkbox" name="types[]" value="<?php echo($type); ?>"<?php $SmartSearchWP_Admin->ssMarkSelected($type,$SmartSearchWP_Admin->included,true) ?>> <?php echo(ucfirst($type)); ?>
                                            </label>
                                            <br>
                                            <?php
                                        }
                                    } else {
                                        echo('You do not have any post types. That is pretty strage!');
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div>
                                    <ul id="facetGroupList">
                                        <li>
                                            <a href="#smartsearchFTW" id="newFacetLink">+ Add New Facet</a>
                                        </li>
                                        <?php
                                        foreach($SmartSearchWP_Admin->facets as $fg){
                                            ?>
                                            <li>
                                                <label>
                                                    <input type="checkbox" name="enabled[]" value="<?php echo($fg); ?>"<?php $SmartSearchWP_Admin->ssMarkSelected($fg,$SmartSearchWP_Admin->facets_enabled,true) ?>> <?php echo($fg); ?>
                                                    <input type="hidden" name="facets[]" value="<?php echo($fg); ?>">
                                                </label>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-4" id="updateSSsettings" style="display: none; text-align: center">
                                <button type="submit" class="btn btn-lg btn-success" id="updateSaveButton">Update My Settings</button>
                                <h3 id="save1h3"><span class="glyphicon glyphicon-hand-up"></span></h3>
                                <h3 id="save2h3">Don't forget to save your settings!</h3>
                                <input type="hidden" name="security" id="security" value="">
                            </div>
                        </div>
                    </form>

                </div>
                <div role="tabpanel" class="tab-pane" id="tabSynonyms" style="text-align: left">

                    <div class="row">
                        <div class="col-md-12">
                            Coming soon!
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
