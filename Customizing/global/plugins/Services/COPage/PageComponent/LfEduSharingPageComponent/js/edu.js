// This file is part of edu-sharing created by metaVentis GmbH — http://metaventis.com
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//

/**
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

!function() {function a(a,b) {var c=void 0!==window.pageYOffset?window.pageYOffset:(document.documentElement||document.body.parentNode||document.body).scrollTop,d=document.documentElement.clientHeight,e=c+d;b=b||0;var f=a.getBoundingClientRect();if (0===f.height)return!1;var g=f.top+c-b,h=f.bottom+c+b;return h>c&&e>g}jQuery.expr[":"]["near-viewport"]=function(b,c,d) {var e=parseInt(d[3])||0;return a(b,e)}}();

function getJQueryCodeForEdusharing($) {

		$.ajaxSetup({ cache: false });
		
		var videoFormat = 'webm';
		var v = document.createElement('video');
		if(v.canPlayType && v.canPlayType('video/mp4').replace(/no/, '')) {
			videoFormat = 'mp4';
		}

		function renderEsObject(esObject, wrapper) {
			var url = esObject.attr("data-url")+'&videoFormat='+videoFormat;
			if(typeof wrapper == 'undefined')
				var wrapper = esObject.parent();
			$.get(url, function(data) {
				wrapper.html('').append(data).css({height: 'auto', width: 'auto'});
				if (data.toLowerCase().indexOf('data-view="lock"') >= 0)
					setTimeout(function(){ renderEsObject(esObject, wrapper);}, 1111);
			});
			esObject.removeAttr("data-type");
		}
		
		$("div[data-type='esObject']:near-viewport(400)").each(function() {
			renderEsObject($(this));
		})
		
		$(window).scroll(function() {
			$("div[data-type='esObject']:near-viewport(400)").each(function() {
				renderEsObject($(this));
			})
		});

    $("body").click(function(e) {
        if ($(e.target).closest(".edusharing_metadata").length) {
            //clicked inside ".edusharing_metadata" - do nothing
        } else {
            $(".edusharing_metadata_toggle_button").text($(".edusharing_metadata_toggle_button").data('textopen'));
            $(".edusharing_metadata").hide();
            if ($(e.target).closest(".edusharing_metadata_toggle_button").length) {
                $(".edusharing_metadata_toggle_button").text($(this).data('textopen'));
                $(".edusharing_metadata").hide();
                toggle_button = $(e.target);
                metadata = toggle_button.parent().find(".edusharing_metadata");
                if(metadata.hasClass('open')) {
                    metadata.toggleClass('open');
                    metadata.hide();
                    toggle_button.text(toggle_button.data('textopen'));
                } else {
                    $(".edusharing_metadata").removeClass('open');
                    metadata.toggleClass('open');
                    metadata.show();
                    toggle_button.text(toggle_button.data('textclose'));
                }
            } else {
                $(".edusharing_metadata").removeClass('open');
            }
        }
    });
}

if(typeof require == 'undefined') {
	$(document).ready(function() {
		getJQueryCodeForEdusharing($);
	});
} else {
	require(['jquery'], function($) {
		$(document).ready(function() {
			getJQueryCodeForEdusharing($);
		});
	});
}

