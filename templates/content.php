<?php
$wpa = $SmartSearchWP_Admin->assets();
?>
<style>
    input[type=checkbox] {
        margin: 4px 5px 0;
    }

    .previewSSRow, .previewSSRow td {
        transition: all 300ms ease-in-out 0ms;
        -webkit-transition:: all 300ms ease-in-out 0;
        background-size: cover;
        background-repeat: no-repeat;
    }

    .previewSSRow:hover, .previewSSRow td:hover {
        background-color: #ebebeb;
    }

    .pssrImage:hover {
        height: 100px;
    }

    h2{
        transition: all 300ms ease-in-out 0ms;
        -webkit-transition:: all 300ms ease-in-out 0;
    }
    .inactiveStep{
        color: #bbbbbb;
    }

    .glyphicon-refresh-animate {
        -animation: spin .7s infinite linear;
        -webkit-animation: spin2 .7s infinite linear;
    }

    @-webkit-keyframes spin2 {
        from { -webkit-transform: rotate(0deg);}
        to { -webkit-transform: rotate(360deg);}
    }

    @keyframes spin {
        from { transform: scale(1) rotate(0deg);}
        to { transform: scale(1) rotate(360deg);}
    }
</style>
<div class="row">
    <div class="col-md-3">
        <h2 step="1">1. Introduction</h2>
    </div>
    <div class="col-md-3">
        <h2 step="2" class="inactiveStep">2. Create Account</h2>
    </div>
    <div class="col-md-3">
        <h2 step="3" class="inactiveStep">3. Enable</h2>
    </div>
    <div class="col-md-3">
        <h2 step="4" class="inactiveStep">4. Auto Indexing</h2>
    </div>
    <div class="col-md-12">
        <div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                <span class="sr-only"></span>
            </div>
        </div>
    </div>
</div>

<div class="row" id="ss_config_1">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <h3>SmartSearch functions as a service and requires product and category information to be indexed for this plugin to work correctly.</h3>

                <p><span style="font-size: 14px; color: black;">The Morrison Consulting Smart Search incorporates the a high-performance, full-featured text search engine library, optimized to provide the best speed and accuracy for the WordPress platform. Smart Search technology uses the best in class relevance and ranking algorithm to display your search results in an order that makes sense.</span></p>
                <p><span style="font-size: 14px; color: black;">With our Smart Search technology you can:</span></p>
                <div style="padding-left: 15px;">
                    <ul style="font-size: 14px; color: black;">
                        <li>Match word stems and plurals. For example: running matches run, shelves matches shelf.</li>
                        <li>Custom spell check built by your own product database allowing you to suggest recommendations, such as “"Did you mean: Morrison Consulting (instead of Morisson Consult)."</li>
                        <li>Dynamic Sorting on Relevance, Entity, Price, Best Sellers.</li>
                        <li>Similar items term vectoring.</li>
                        <li>Incorporate it in to your product management with automated taxonomy suggestions and related products for new products.</li>
                        <li>Customize the retrieval sorting and filtering any content on your site.</li>
                    </ul>
                </div>
                <br>
                <p><span style="font-size: 14px; color: black;">Gain more customers by allowing them to find what they’re looking for more quickly and easily than ever before.</span></p>

                <p><span style="font-size: 14px; color: black;"><strong>Dynamically Relate Content &amp; Products</strong></span></p>
                <p><span style="font-size: 14px; color: black;">Our Smart Search technology also allows you to dynamically relate content and products. A unique feature of our Smart Search technology is that you can use it to power your entire site, calling all of the text and images. Utilizing our Smart Search to dynamically insert your text and content can actually allow your site to run faster than a traditional WordPress Search, since it minimizes the number of database calls. If your site is image-heavy, or just runs slower than you would like, consider integrating our Smart Search technology to maximize your site’s speed and help you gain even more customers.</span></p>
            </div>
        </div>
        <center>
            <button class="btn btn-lg btn-primary" goto="2">Move to Step 2: Create Account</button>
        </center>
    </div>
</div>

<div class="row" id="ss_config_2" style="display: none">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <h3>Create an account on the SmartSearch Platform</h3>
                <a class="btn btn-default" href="<?php echo($SmartSearchWP_Admin->getDom()); ?>/account/register" target="_blank"><?php echo($SmartSearchWP_Admin->getDom()); ?>/account/register?platform=wordpress</a>

                <br>
                <h3>After account has been created you will be prompted for a setup wizard, select "WordPress"</h3>
                You will then be shown your new SmartSearch URL, copy that and paste it into the field below.
                <input type="text" class="form-control" id="moco_ss_url" placeholder="<?php echo($SmartSearchWP_Admin->getDom()); ?>/CODE/s.aspx" value="<?php echo($smartSearchUrl); ?>">
                <br>
            </div>
        </div>
        <center>
            <button class="btn btn-lg btn-primary" goto="3" disabled>Move to Step 3: Generate Feed</button>
        </center>
    </div>
</div>

<div class="row" id="ss_config_3" style="display: none">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <h3>Everything looks good!</h3>

                <strong>SmartSearch Code:</strong> <span id="mss_key"><?php echo($smartSearchKey); ?></span><br>
                <strong>SmartSearch URL:</strong> <span id="mss_url"><?php echo($smartSearchUrl); ?></span><br>
                <strong>SmartSearch Items:</strong> <span id="mss_count">0</span>

                <br>
            </div>
        </div>
        <center>
            <button class="btn btn-lg btn-success" goto="4">Activate SmartSearch Results</button>
        </center>
    </div>
</div>

<div class="row" id="ss_config_4" style="display: none">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <h3>Your feed is on its way to the server!</h3>

                Once the upload has been completed, the big green button at the bottom of this page will activate and you can get outta here!
                <br><br>

                <strong>While we wait, let's go ahead and setup the Auto Indexing feature.</strong>
                <br>
                Go to your <a href="<?php echo($SmartSearchWP_Admin->getDom()); ?>/manage/settings" target="_blank">SmartSearch Dashboard</a> and setup your datasource.<br>
                <br>
                Click where it says "<font style="color: #FF0000">EMPTY</font>" in red, paste in the following feed URL:<br>
                <strong><?php echo(get_site_url().'/feed/smartsearch/'); ?><span id="mss_key_2"><?php echo($smartSearchKey); ?></span></strong>

                <br>
            </div>
        </div>
        <center>
            <button class="btn btn-lg btn-success" goto="5">Your All Done</button>
        </center>
    </div>
</div>

<script>
    var indexCount = 0;
    (function ( $ ) {
        $(document).ready(function(){
            $('button[goto]').on('click',function(e){
                var nextStep = parseInt($(this).attr('goto')),
                    progress = $('.progress-bar'),
                    step1 = $('#ss_config_1'),
                    step2 = $('#ss_config_2'),
                    step3 = $('#ss_config_3'),
                    step4 = $('#ss_config_4'),
                    displayKey = $('#mss_key'),
                    displayKey2 = $('#mss_key_2'),
                    displayUrl = $('#mss_url'),
                    displayCount = $('#mss_count');

                switch(nextStep){
                    case 1:
                    case 2:
                        step1.fadeOut('fast',function(){
                            progress.animate({width: '37%'},function(){
                                $('h2[step="2"]').removeClass('inactiveStep');
                                step2.fadeIn('fast');
                                $('#moco_ss_url').trigger('keyup');
                            });
                        });
                        break;
                    case 3:
                        var mocoSSUrl = $('#moco_ss_url').val(),
                            mocoSSCode = mocoSSUrl.match(/^https\:\/\/wooss\.moco\.biz\/([a-zA-Z0-9]+)\/s\.aspx$/);
                        displayKey.html(mocoSSCode[1]);
                        displayKey2.text(mocoSSCode[1]);
                        displayUrl.html(mocoSSUrl);
                        $.post('',{
                            step: 2,
                            url: mocoSSUrl,
                            code: mocoSSCode[1],
                            security: moco_ss_status.ajax_nonce
                        },function(r){
                            var parsed = r.match(/\<indexCount\>([0-9]+)\<\/indexCount\>/);
                            indexCount = parsed[0];
                            step2.fadeOut('fast',function(){
                                progress.animate({width: '62%'},1000,function(){
                                    $('h2[step="3"]').removeClass('inactiveStep');
                                    step3.fadeIn('fast');
                                    displayCount.html(indexCount);
                                    setTimeout(function(){
                                        $('#preview_ss_results').slideDown('fast');
                                        $('.glyphicon-refresh-animate').fadeOut('fast');
                                        $('button[goto="4"]').fadeIn('fast');
                                    },4000)
                                });
                            });
                        });
                        break;
                    case 4:
                        $('button[goto="5"]').prop('disabled',true);
                        step3.fadeOut('fast', function () {
                            progress.animate({width: '100%'}, function () {
                                $('h2[step="4"]').removeClass('inactiveStep');
                                step4.fadeIn('fast');
                            });
                        });
                        $.post('',{
                            step: 3,
                            push: true,
                            security: moco_ss_status.ajax_nonce
                        },function(r) {
                            $('button[goto="5"]').prop('disabled',false);
                        });
                        break;
                    case 5:
                        window.location.href = '<?php echo(admin_url('admin.php?page=mc-smartsearch')); ?>';
                        break;
                    default:

                        break;
                }
            });

            $('#moco_ss_url').on('keyup',function(){
                var url = $(this).val(),
                    pattern = /^https\:\/\/wooss\.moco\.biz\/([a-zA-Z0-9]+)\/s\.aspx$/,
                    valid = url.match(pattern);
                if(valid){
                    $('button[goto="3"]').prop('disabled',false);
                }else{
                    $('button[goto="3"]').prop('disabled',true);
                }
            });
        });
    }( jQuery ));

</script>
