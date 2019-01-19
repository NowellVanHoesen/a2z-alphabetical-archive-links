/**
 * maybe report that a background process is still going
 * if not ensure anything marked processing is cleared
 */
jQuery( document ).on( 'heartbeat-tick', function ( event, data ) {

	if ( ! data.a2zaal_background_processing_response ) {
		maybeCompleteProcessing();
		return;
	}

	var $potentialElements = document.querySelectorAll( '[data-post_type]' ),
		$postTypeData = data.a2zaal_background_processing_response.post_type;

	if ( 0 >= $potentialElements.length ) {
		return;
	}

	$potentialElements.forEach( function( element ) {
		var $post_type = element.dataset.post_type;
		if ( $postTypeData.includes( $post_type ) ) {
			maybeAddProcessingStatus( element );
		} else {
			maybeRemoveProcessingStatus( element );
		}
	} );
});

/**
 * send process check request with the heartbeat-send event
 */
jQuery( document ).on( 'heartbeat-send', function ( event, data ) {
	data.a2zaal_background_processing_check = 'check';
});

/**
 * If a post type is processing but not marked as processing, mark it
 * @param element
 */
function maybeAddProcessingStatus( element ) {
	var $processingStatus = element.getElementsByClassName( 'a2zaal_processing' );
	if ( 0 < $processingStatus.length ) {
		return;
	}

	$processingStatus = document.createElement( 'span' );
	var $processingStatusText = document.createTextNode( 'Processing ' );
	$processingStatus.className = 'a2zaal_processing';
	$processingStatus.appendChild( $processingStatusText );
	element.insertBefore( $processingStatus, element.firstChild );

	var $postTypeCheckbox = element.querySelector( 'input[type=checkbox]' ),
		$hiddenInput = document.createElement( 'input' );
	if ( typeof $postTypeCheckbox == 'undefined') {
		console.log( "Post Type checkbox not found" );
		return;
	}

	$hiddenInput.type = 'hidden';
	$hiddenInput.checked = 'checked';

	$hiddenInput.value = $postTypeCheckbox.value;
	$hiddenInput.name = $postTypeCheckbox.name;

	element.insertBefore( $hiddenInput, $postTypeCheckbox );
	element.removeChild( $postTypeCheckbox );
}

/**
 * remove processing marking if the post type has complete or been stopped
 * @param element
 */
function maybeRemoveProcessingStatus( element ) {
	var $processingStatus = element.getElementsByClassName( 'a2zaal_processing' )[0];
	if ( typeof $processingStatus == 'undefined' ) {
		return;
	}
	$processingStatus.classList.add( 'complete' );
	removeProcessingStatus();
	//element.removeChild( $processingStatus );

	var $postTypeCheckbox = document.createElement( 'input' );
	$postTypeCheckbox.type = 'checkbox';
	$postTypeCheckbox.checked = 'checked';

	var $hiddenInput = element.querySelector( 'input[type=hidden]' );
	if ( typeof $hiddenInput == 'undefined' ) {
		console.log( "hidden input not found" );
		return;
	}

	$postTypeCheckbox.value = $hiddenInput.value;
	$postTypeCheckbox.name = $hiddenInput.name;

	element.insertBefore( $postTypeCheckbox, $hiddenInput );
	element.removeChild( $hiddenInput );

}

/**
 * verify that nothing is marked as processing when no processing is happening
 */
function maybeCompleteProcessing() {
	var $processingStatusList = document.querySelectorAll( '.a2zaal_processing' );
	if ( 1 > $processingStatusList.length ) {
		return;
	}

	$processingStatusList.forEach( function( element ) {
		var $parent = element.parentElement;
		maybeRemoveProcessingStatus( $parent );
	} );

}

/**
 * fade out the processing marking then remove the element
 */
function removeProcessingStatus() {
	jQuery( '.a2zaal_processing.complete' ).fadeOut( 1000, function() {
		jQuery( this ).remove();
	});
}

/**
 * speed up the heartbeat iterations when something is marked as processing
 */
jQuery( document ).ready( function( $ ) {
	var $processing = $( '[data-post_type]' ).find( '.a2zaal_processing' );
	if ( 0 < $processing.length ) {
		wp.heartbeat.interval( 'fast' );
	}
} );
