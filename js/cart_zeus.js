jQuery(document).ready( function($) {

	//$("#delivery-info>form").attr("id", "delivery-form");
	$('body input[type="submit"]').each( function( i, elem ) {
		if( "confirm" == $(this).attr("name") ) {
			$(this).parents("form").attr("id", "delivery-form");
		}
	});
	//$(document).on( "click", "#delivery-info input[type=submit]", function( e ) {
	$(document).on( "click", 'body input[type="submit"]', function( e ) {
		if( "confirm" == $(this).attr("name") && $("#zeus").css("display") != "none" && "new" == $("#zeus_card_option").val() ) {
			zeusToken.getToken( function( zeus_token_response_data ) {
				if( !zeus_token_response_data['result'] ) {
					alert( zeusToken.getErrorMessage(zeus_token_response_data['error_code']) );
					e.preventDefault();
					e.stopPropagation();
					return false;
				} else {
					$("delivery-form").submit();
				};
			});
		} else {
			$("delivery-form").submit();
		}
	});
});
