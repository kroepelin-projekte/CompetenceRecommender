( function( $ ) {
	var CheckboxChecker = {
		init: function() {
			// checkbox checker
			CheckboxChecker.checkboxChecker();
		},

		checkboxChecker: function() {
			$( document ).on( 'click', '#onlymaterial', function( e ) {
				var url = window.location.href;
				if ($( this )[0].checked) {
					url = url.replace('&onlymaterial=0', '');
					url += '&onlymaterial=1';
				} else {
					url = url.replace('&onlymaterial=1', '');
					url += '&onlymaterial=0';
				}
				window.location.href = url;
			} );
			$( document ).on( 'click', '#withoutdata', function( e ) {
				var url = window.location.href;
				if ($( this )[0].checked) {
					url = url.replace('&withoutdata=0', '');
					url += '&withoutdata=1';
				} else {
					url = url.replace('&withoutdata=1', '');
					url += '&withoutdata=0';
				}
				window.location.href = url;
			} );
			$( document ).on( 'click', '#notfinished', function( e ) {
				var url = window.location.href;
				if ($( this )[0].checked) {
					url = url.replace('&notfinished=0', '');
					url += '&notfinished=1';
				} else {
					url = url.replace('&notfinished=1', '');
					url += '&notfinished=0';
				}
				window.location.href = url;
			} );
		}
	};
	$( document ).ready( function( $ ) {
		CheckboxChecker.init();
	} );
} )( jQuery );
