( function( $ ) {
	var InitObj = {
		init: function() {
			// event listener for the select box
			InitObj.inputListener();
		},

		inputListener: function() {
			$( document ).on( 'click', '#init_obj', function( e ) {
				var url = window.location.href;
				url += '&cmd=init_obj&selected_profile='+$( 'select#selected_profile' ).val();
				window.location.href = url;
			} );
		},
	};
	$( document ).ready( function( $ ) {
		InitObj.init();
	} );
} )( jQuery );
