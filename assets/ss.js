var aryDisplayedList = [];										// array of objects currently displayed in search results panel
var aryDisplayedImgs = [];                                       // array of objects containing image info for currently displayed images in search results panel
(function ($) {
    var searchForm = $('input[name="s"]').closest('form'),
        searchBox = null,
        searchBtn = null,
        lastSearch = null;

    var mocoSmartSearchAutoComplete_On = true;

    var mocoSmartSearchAutocomplete_DebugOn = false;

// INPUT FIELD
    var sInputFldName = "searchterm";								//V10 Default implementation
    var sInputFieldId = ""; 									// input field used by user for search inputting
    var sInputFldIdIncludes = "";
    var sInputFldIdEndsWith = "";
    var sInputFldHtmlTag = "input";
    var jqSrchInpFldString;
    var jqRefToSearchInpFld; 									// reference to search input field


// SEARCH BUTTON
    var searchButtonClass = "search-go";
    var searchButtonId = ""; 									// button used by search control to submit search request
    var searchButtonIdIncludes = "";
    var searchButtonIdEndsWith = "";
    var searchButtonHtmlTag = "input";
    var jqSearchBtnString;
    var jqRefToSearchBtn; 										// reference to search button

    var objDefJson; 											// default list that displays when item has focus with nothing in search field
    var aryDefLst;

//var objMstJson											// object containing all data, key is property name
    var aryDefLst; 												// array of objects containing default items to display
    var aryDefImg;												// array of image objects currently displayed

    var aryCurLst; 												// array of objects containing default items to display
    var aryCurImg; 												// array of image objects currently displayed



    var sRsltOffset_x = 3;										// horizontal position offset of result panel
    var sRsltOffset_y = 0;										// vertical position offset of result panel
    var sRsltMinWidth = 400;									// minimum width of search result panel
    var sRsltMinHeight = 170;									// minimum height of search result panel
    var sRsltAutoSize = false;									// auto size search results panel true/false
    var jqRefToElemIdRsltsPnlSzsTo;								// reference to the element that moco smart search autocomplete will size to
    var sRsltMaxDspCnt = 4; 									// max number of items displayed in search result panel
    var sRsltAlign = "left"; 									// align search results panel on left/right ("left"/"right") of search input field
    var imgClass = "img-thumbnail img-responsive entity-product-image grid-item-image";  // set the class on the images
    var pnlBckGrnd = "#ffffff";                                 // the css background color of the panel when there is a result
    var emptyPnlBckGrnd = "#fffff";                             // the css background color of the results panel when there is no result

    var sRsltBrandDsp = true;									// branding display on/off (true/false)
    var sRsltBrandText = "Moco Smart Search Autocomplete"; 		// branding text
    var sRsltBrandUrl = "https://smartsearch.aspdotnetstorefront.com";	// branding url link
    var sRsltBrandPosOffset_x = 10;								// branding horizontal offset
    var sRsltBrandPosOffset_y = 0; 								// branding vertical offset

    var maxListItemLength = 200;								    // maximum length of an item, at which point it is truncated and ellipses are added
    var maxImgesToDisplay = 4;
    var imgLabelTextLength = 14;

    var sRsltCloseOnBlur = true;								// close search results panel when search field focus lost

    var sAjaxUrl = "/ssAutoComplete.axd";						// URL used to make AJAX calls to server for search results
    var sAjaxTimeoutMs = 60000; 								// timeout dur in milliseconds

    var WaitingForSearchResults = false; 						// flag indicating still waiting for search results to return from server
    var WaitingForImages = false; 								// flag indicating still waiting for image results to return from server


    var arwIndxPos = -1;										// index position in array of displayed item that just fired mouse over event, in search results panel
    var lastLstItmNm; 											// last list item name (label) that previously fired mouse over event, in search results panel
    var curLstItmNm; 											// current list item name (label) that fired mouse over event, in search results panel

    var keyDelayIntv;                                           // key delay interval flag indicating call to server for images is okay
    var keyDelayPauseMs = 100;                                  // key delay lag maximum milliseconds, is the max pause between keystrokes in search input field
    var keyDelayLstIntv = true;                                 //
    var keyDelayTmr = false;


    $(document).ready(function(){
        if(searchForm.length){

            CheckForSearchResultsPanel();

            searchBox = searchForm.find('input[type="text"],input[type="search"]');
            searchBox.attr('autocomplete','off');
            jqRefToSearchBtn = searchForm.find('button');
            jqRefToSearchInpFld = searchBox;
            jqRefToElemIdRsltsPnlSzsTo = jqRefToSearchInpFld;

            searchBox.keyup( SearchField_KeyUp_Handler )
                     .focus( SearchField_Focus_Handler )
                     .blur( SearchField_Blur_Handler );

            keyDelayIntv = setInterval(function(){
                keyDelayTmr = false;
            },keyDelayPauseMs);

            $( window ).resize( winResize );
        }
    });

    function ssAutoComplete( sTxt ){
        if(lastSearch === sTxt){
            return;
        }else{
            lastSearch = sTxt;
        }
        WaitingForSearchResults = true;

        $.ajax( {
                type: "POST",
                url: ss_object.url,
                data: { action: 'smartsearch_autocomplete', s: sTxt },
                cache: false,
                dataType: "html",
                timeout: sAjaxTimeoutMs
            } )
            .done( function ( rspTxt ) {

                try {

                    if ( !rspTxt || $.trim( rspTxt ).length == 0 ) {
                        return;
                    }

                    var tmpObj = JSON.parse( rspTxt );

                    if ( !tmpObj || tmpObj.length == 0 ) {
                        return;
                    }

                    aryCurLst = tmpObj[0];
                    aryCurImg = tmpObj[1];

                    DisplayResultList(aryCurLst, jqRefToSearchInpFld.val());
                } catch ( err ) {
                    // ignore error, but catch it to prevent client-side JavaScript failure
                }

            } )
            .fail( function ( jqXHR, textStatus, errorThrown ) {
                //closeSrchPanel( true );
                console.log(errorThrown);
            } )
            .always( function () {
                WaitingForSearchResults = false;
            } );
    }

    function SearchField_KeyUp_Handler(event) {
        if ( event.keyCode == 38 || event.keyCode == 40 || event.keyCode == 13 ) {
            return;
        }

        if(keyDelayTmr || lastSearch == $.trim(searchBox.val())){
            return;
        }
        keyDelayTmr = true;
        if ($.trim(searchBox.val()).length !== 0) {
            ssAutoComplete( searchBox.val() );
        }
    }

    // handler for focus event of search input field
    function SearchField_Focus_Handler() {

        // if search input field has content, display what is in the displayed arrays
        if ( $.trim( jqRefToSearchInpFld.val() ).length > 0  && lastSearch == $.trim(jqRefToSearchInpFld.val())) {
            DisplayResultList(aryDisplayedList, $.trim(jqRefToSearchInpFld.val()));
        } else {
            closeSrchPanel();
        }
    }

    // handler for blur event of search input field
    function SearchField_Blur_Handler() {
        closeSrchPanel();
    }

    function CheckForSearchResultsPanel() {

        if ( $( "#srchResContainer" ).length == 0 ) {
            $( "body" ).append( "<div id='srchResContainer' style='display:none;'><div id='srchRes'></div><div id='imgs'></div><div id='brand'>moco.smart.search.auto.complete</div></div>" );
        }
    }

    // display result list in search results panel
    function DisplayResultList( ary, txtHighlighted ) {
        ary = ary.terms;

        var len = ( txtHighlighted ) ? txtHighlighted.length : 0,
            searchRes = $( "#srchRes" ),
            searchResContainer = $("#srchResContainer");

        searchRes.empty();

        searchRes.append( "<ul class='lstCnt'>" );

        if (ary && ary.length > 0) {
            aryDisplayedList = { terms: [] };
            var cnt = 0;
            $("#srchResContainer").css({ backgroundColor: pnlBckGrnd });
            for (e in ary) {

                if(cnt === 0){
                    DisplayImages(aryCurImg[ary[e].name], true);
                }

                if (++cnt > sRsltMaxDspCnt) {
                    break;
                }

                aryDisplayedList.terms.push(ary[e]);

                // truncate label if necessary
                var nm = ((ary[e].name.length < maxListItemLength) ? ary[e].name : ary[e].name.substr(0, maxListItemLength - 3) + "...");

                // display list item
                if (len > 0) {
                    $("#srchRes").append("<li id='" + ary[e].name + "' class='lstItmNormal' ent='" + ary[e].entity + "' idx='" + e + "'>" + FormatTitleCase(nm).replace(new RegExp("\\b" + txtHighlighted, "gi"), "<span class='bld'>" + TxtHighlight(ary[e].name, txtHighlighted) + "</span>") + "</li>");

                } else {
                    $("#srchRes").append("<li id='" + ary[e].name + "' class='lstItmNormal' ent='" + ary[e].entity + "' idx='" + e + "'>" + FormatTitleCase(nm) + "</li>");
                }
            }
        } else {
            searchResContainer.css({ backgroundColor: emptyPnlBckGrnd });
        }

        searchRes.append( "</ul>" );

        searchRes.append( "<div id='PressEnterToSearch' class='pressEnterToSearch'>press enter to search <span style='font-style:italic; font-weight:bold;'>" + jqRefToSearchInpFld.val() + "</span>...</div>" );

        $( "#srchRes li" ).mouseover( ListItemMouseoverEventHandler )
                   .mouseout( ListItemMouseoutEventHandler )
                   .click( ListItemClickEventHandler );

        DisplaySearchPanel();
    }

    // display search panel
    function DisplaySearchPanel() {

        if ( $( "#srchResContainer:visible" ).length == 0 ) {
            searchPanelPos();
            $( "#srchResContainer" ).fadeIn( 'fast' );
        }

    }

    // close search results panel
    function closeSrchPanel(err) {
        if ( sRsltCloseOnBlur || err ) {
            $( "#srchResContainer" ).fadeOut( 'fast', clearListAndImages );
        }
    }

    // clear displayed list and images
    function clearListAndImages() {
        $( "#srchRes" ).empty();
        $( "#imgs" ).empty();
    }

    function searchPanelPos() {

        var pos = jqRefToSearchInpFld.offset();
        var posHeight = jqRefToElemIdRsltsPnlSzsTo.outerHeight();
        var posWidth = jqRefToElemIdRsltsPnlSzsTo.outerWidth();
        if ($("#srchResContainer").outerWidth() < posWidth) {
            $("#srchResContainer").css({width: (posWidth + "px")});
        }
        var fnlOffset = ((sRsltAlign.toLowerCase() === 'left') ? ((pos.left + jqRefToSearchInpFld.outerWidth() - $("#srchResContainer").outerWidth()) + "px") : (pos.left + sRsltOffset_x) + "px");
        var hThs = $( "#srchResContainer" ).height();

        $("#srchResContainer").css({
            top: (pos.top + posHeight + sRsltOffset_y) + "px",
            left: fnlOffset
        });
    }

    function winResize() {
        if ( $( "#srchResContainer:visible" ).length > 0 ) {
            searchPanelPos();
        }
    }

    // mouse out event for list items displayed in search result panel
    function ListItemMouseoutEventHandler() {
        $( this ).attr( "class", "lstItmNormal" );
    }

    // mouse over event for list items displayed in search result panel
    function ListItemMouseoverEventHandler() {

        arwIndxPos = parseInt( $( this ).attr( "idx" ) );
        $( "#srchRes li[id='" + curLstItmNm + "']" ).attr( "class", "lstItmNormal" );
        lastLstItmNm = curLstItmNm;
        curLstItmNm = $(this).attr("id");
        $( this ).attr( "class", "lstItmOver" );

        if ( WaitingForImages ) {
            return;
        }

        DisplayImages(aryCurImg[this.id], true);
        return;
    }

    // return highlighted text in proper case: i.e. the same case as in label
    function TxtHighlight(txt, highlightTxt) {
        var s = "";
        for (var i = 0; i < highlightTxt.length; i++) {
            s += (i == 0) ? highlightTxt.substr(i, 1).toUpperCase() : highlightTxt.substr(i, 1);
        }
        return s;
    }


    function FormatTitleCase(txt) {
        var aTxt = txt.split(" ");
        var s = "";
        for (var i = 0; i < aTxt.length; i++) {
            s += aTxt[i].substr(0, 1).toUpperCase() + aTxt[i].substr(1).toLowerCase() + " ";
        }
        return $.trim(s);
    }


    // click event for list items displayed in search result panel
    function ListItemClickEventHandler() {

        var thisItem = $(this);

        // place item value in search field
        if ( jqRefToSearchInpFld ) {
            jqRefToSearchInpFld.val( thisItem.attr( "id" ) );
        }

        // click search button
        if (jqRefToSearchBtn) {
            if (jqRefToSearchBtn.is("button[type=button]") || jqRefToSearchBtn.is("input[type=button]") || jqRefToSearchBtn.is("input[type=submit]")) {
                jqRefToSearchBtn.click();
                searchForm.submit();
            } else {
                window.location = '/?s=' + jqRefToSearchInpFld.val() + '&post_type=product';
            }

        }
    }

    // display images in search results panel
    function DisplayImages( imgAry, dspImgsOnly ) {
        // clear image panel
        $( "#imgs" ).empty();
        var cnt = 0;

        // if array has content, display images
        if ( dspImgsOnly && imgAry && imgAry.length > 0 ) {
            for ( var i = 0; i < imgAry.length; i++ ) {
                if ( imgAry[i] != undefined ) {
                    if (++cnt > sRsltMaxDspCnt) {
                        break;
                    }
                    $("#imgs").append("<div class='imgcon'><div><a href='" + imgAry[i].url + "' title='" + imgAry[i].label + "'><img src='" + imgAry[i].image + "' class='"+ imgClass + "'/></div><div class='imgCpt'>" + ((imgAry[i].label && imgAry[i].label.length > imgLabelTextLength) ? imgAry[i].label.substr(0, imgLabelTextLength) + "..." : ((imgAry[i].label) ? imgAry[i].label : "")) + "</a></div></div>");
                    aryDisplayedImgs.push( imgAry[i] );
                }
            }
        }

        // if displaying default results, display default images
        if ( $.trim( $(sInputFieldId).val() ).length == 0 ) {

            if ( imgAry && imgAry.length > 0 && objDefJson && objDefJson[imgAry[0]] && objDefJson[imgAry[0]].images && objDefJson[imgAry[0]].images.length > 0 ) {

                // display images
                for ( var i = 0; i < 4; i++ ) {
                    if ( imgAry[i] != undefined ) {
                        $( "#imgs" ).append( "<div class='imgcon'><div><a href='" + objDefJson[imgAry[0]].images[i].url + "' title='" + objDefJson[imgAry[0]].images[i].desc + "'><img src='" + objDefJson[imgAry[0]].images[i].image + "'/></div><div class='imgCpt'>" + objDefJson[imgAry[0]].images[i].label + "</a></div></div>" );
                        aryDisplayedImgs.push( imgAry[i] );
                    }
                }
            }

            //DisplayBrand();

            return;
        }


        //DisplayBrand();
    }

}(jQuery));
