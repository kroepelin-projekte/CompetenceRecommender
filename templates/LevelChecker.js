( function( $ ) {
	var LevelChecker = {
		init: function() {
			// checkbox checker
			LevelChecker.check();
		},

		check: function() {
			$('#cmd[saveSelfEvaluation]').on('click', function() {
				url = window.location.href;
				$('input[type=radio]').each( function(radio) {
					if (radio[0].checked) {
						url += radio.val();
					}
				});
				window.location.href = url;
			});
		}
	};
	$( document ).ready( function( $ ) {
		LevelChecker.init();
	} );
} )( jQuery );
