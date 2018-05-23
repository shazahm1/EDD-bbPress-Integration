;jQuery(document).ready(function($) {

	$('.edd-bbpress-purchase-details.closed, .edd-bbpress-customer-details.closed' ).toggle();

	$( document ).on( 'click', '.edd-bbpress-customer-toggle', function(e) {
		e.preventDefault();

		var el = $(this);

		el.next('.edd-bbpress-customer-details').toggle().toggleClass('closed');
		el.find( 'i.dashicons' ).toggleClass( 'dashicons-arrow-right' ).toggleClass( 'dashicons-arrow-down' );
	});

	$( document ).on( 'click', '.edd-bbpress-purchase-toggle', function(e) {
		e.preventDefault();

		var el = $(this);

		el.next('.edd-bbpress-purchase-details').toggle().toggleClass('closed');
		el.find( 'i.dashicons' ).toggleClass( 'dashicons-arrow-right' ).toggleClass( 'dashicons-arrow-down' );
	});

	$( document ).on( 'click', '.edd-bbpress-purchase-resend-receipt', function(e) {
		e.preventDefault();

		if ( confirm( "Are you sure you wish to resend the purchase receipt?" ) ) {

			var targetUrl = $(this).attr('rel');

			$.ajax({
				url: targetUrl,
				type: "GET",
				success:function(){
					alert("sent");
					return false;
				},
				error:function (){
					alert("error");
				}
			});

		}

	});

	$( document ).on( 'click', '.edd-bbpress-deactivate-site', function(e) {
		e.preventDefault();

		if ( confirm( "Are you sure you wish to deactivate the site?" ) ) {

			var el        = $(this);
			var targetUrl = el.attr('rel');

			$.ajax({
				url: targetUrl,
				type: "GET",
				success:function() {

					var counter = el.closest( '.manage-sites' ).find( '.active-sites span.count' ); //console.log( counter );
					var active  = counter.data( 'active_site_count' ); //console.log( active );

					active = parseInt( active ) - 1;
					active = 0 == active ? '0' : active;
					//console.log( active );

					counter.data = active;
					counter.attr( 'data-active_site_count', active );
					counter.html( active );

					el.parent().remove();

					alert("deactivated");
					return false;
				},
				error:function (){
					alert("error");
				}
			});

		}

	});
});
