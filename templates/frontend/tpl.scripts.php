<script>
    if(typeof $ === "undefined"){
        $ = jQuery;
    }
    (function ( $ ) {
        bindFilters();
    }( jQuery ));

    function bindFilters(){
        var links = $('a.filter');
        links.each(function(i){
            $(this).on('click',function(e){
                addFilter($(this).text(),$(this).attr('facet'));
            });

        });
    }
    function addFilter(label,facet){
        var filters = $('.filters');
        filters.append('<a href="javascript: void(0)" class="filter" facet="'+facet+'" onclick="removeFilter(this)">'+label+'</a>');
        $('.song-list-container').animate({'opacity':.25});
    }

    function removeFilter(labelElement){
        $(labelElement).remove();
        $('.song-list-container').animate({'opacity':.25});
    }

</script>