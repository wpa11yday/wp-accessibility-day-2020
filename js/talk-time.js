(function ($) {
	'use strict';
	$(function () {
		$( 'h2.event-time' ).each( function ( index ) {
			var utcTime  = $( this ).attr( 'data-time' );
			var userTime = new Date( utcTime ).toLocaleTimeString().replace( ':00 ', ' ' );
			var userDate = new Date( utcTime ).toLocaleDateString();
			var zone     = Intl.DateTimeFormat().resolvedOptions().timeZone;
			// Handle Internet Explorer's lack of timezone info.
			if ( undefined === zone ) {
				zone = 'your local time';
			}
			$( this ).append( '<span class="localtime">' + userDate + ' at ' + userTime + ' ' + zone + '</span>' );
		});
		$( 'h2.talk-time' ).each( function ( index ) {
			var utcTime  = $( this ).attr( 'data-time' );
			var userTime = new Date( utcTime ).toLocaleTimeString().replace( ':00 ', ' ' );
			var zone     = Intl.DateTimeFormat().resolvedOptions().timeZone;
			// Handle Internet Explorer's lack of timezone info.
			if ( undefined === zone ) {
				zone = 'your local time';
			}
			$( this ).append( '<span class="localtime">' + userTime + ' ' + zone + '</span>' );
		});
	});
var transcript = $( '.wp-block-group.transcript' );
var summary    = transcript.find( 'h2' );
summary.wrap( '<summary></summary>' );

var details  = transcript.find( '.wp-block-group__inner-container' );

details.replaceWith( '<details>' + details.html() + '</details>' );
}(jQuery));