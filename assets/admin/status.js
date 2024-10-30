var currentSettings = null, needsSave = false, forceSave = false;
(function ( $ ) {
    $(document).ready(function(){

        $(window).bind('beforeunload', function(){
            if( needsSave ){
                return "It looks like you have input you haven't submitted."
            }
        });

        $('#enableSS').on('click',function(e){
            $.post('',{
                step: 4,
                push: true,
                security: moco_ss_status.ajax_nonce
            },function(r) {
                window.location.href = moco_ss_status.siteurl + '/wp-admin/admin.php?page=mc-smartsearch';
            });
        });
        $('#disableSS').on('click',function(e){
            $.post('',{
                step: 5,
                security: moco_ss_status.ajax_nonce
            },function(r) {
                window.location.href = moco_ss_status.siteurl + '/wp-admin/admin.php?page=mc-smartsearch';
            });
        });
        $('#updateSaveButton').on('click',function(e){
            needsSave = false;
        });
        var ssItems = $('#smartSearchItems');
        ssItems.DataTable();
        ssItems.addClass('table table-bordered');

        var tabSettings = $('#tabSettings').find('input[type="checkbox"]');
        currentSettings = tabSettings.serialize();
        tabSettings.on('change',function(){
            if(!forceSave && currentSettings === tabSettings.serialize()){
                $('#updateSSsettings').fadeOut('fast');
                $('#save2h3').css({'marginTop': '20px', 'opacity': '0'});
                $('#save1h3').css({'marginTop': '40px', 'opacity': '0'});
                needsSave = false;
            }else {
                $('#updateSSsettings').fadeIn('fast', function () {
                    $('#save1h3').css({'marginTop': '20px', 'opacity': '1'});
                    $('#save2h3').css({'marginTop': '0', 'opacity': '1'});
                    needsSave = true;
                });
            }
        });

        $('#newFacetLink').on('click',function(e){
            e.preventDefault();
            var getFacet = prompt('What is the name of this new facet?','');
            if(getFacet && getFacet.trim() !== ''){
                $('#facetGroupList').append('<li>' +
                    '<label class="tempHide" style="display:none">' +
                    '<input type="checkbox" name="enabled[]" value="'+getFacet+'"> ' + getFacet +
                    '<input type="hidden" name="facets[]" value="'+getFacet+'">' +
                    '</label>' +
                    '</li>');

                $('.tempHide').fadeIn('fast',function(){
                    $(this).removeClass('tempHide');
                });

                $('#updateSSsettings').fadeIn('fast', function () {
                    $('#save1h3').css({'marginTop': '20px', 'opacity': '1'});
                    $('#save2h3').css({'marginTop': '0', 'opacity': '1'});
                    needsSave = forceSave = true;
                });
            }
        });

        $('#security').val(moco_ss_status.ajax_nonce);
    });
}( jQuery ));