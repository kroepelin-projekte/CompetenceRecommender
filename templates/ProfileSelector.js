( function( $ ) {
	var ProfileSelector = {
		init: function() {
			// event listener for the select box
			ProfileSelector.inputListener();
		},

		inputListener: function() {
			$( document ).on( 'change', 'select#selected_profile', function( e ) {
				var url = window.location.href;
				url += '&selected_profile=' + $( this ).val();
				window.location.href = url;
			} );
		},
	};
	$( document ).ready( function( $ ) {
		ProfileSelector.init();
	} );
} )( jQuery );
