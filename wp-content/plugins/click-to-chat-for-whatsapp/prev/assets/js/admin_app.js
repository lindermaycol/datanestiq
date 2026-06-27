// Click to Chat - prev
jQuery( document )
	.ready( function ( $ ) {
	// wpColorPicker
		$( '.color-wp' )
			.wpColorPicker();
	} );

// initialize materialize function ..
document.addEventListener( 'DOMContentLoaded', function () {
	var elems = document.querySelectorAll( 'select' );
	M.FormSelect.init( elems, {} );
	var elems = document.querySelectorAll( '.collapsible' );
	M.Collapsible.init( elems, {} );
} );

jQuery( document )
	.ready( function () {
		const position = document.querySelectorAll( '.position' );

		const default_display = () => {
			const val = jQuery( '.select' )
				.find( ':selected' )
				.val();
			const position1 = document.querySelector( '.position-1' );
			const position2 = document.querySelector( '.position-2' );
			const position3 = document.querySelector( '.position-3' );
			const position4 = document.querySelector( '.position-4' );

			if ( val == '1' ) {
				position1.classList.add( 'display-block' );
			} else if ( val == '2' ) {
				position2.classList.add( 'display-block' );
			} else if ( val == '3' ) {
				position3.classList.add( 'display-block' );
			} else if ( val == '4' ) {
				position4.classList.add( 'display-block' );
			}
		};
		default_display();

		//  incase display-block is added remove it ..
		const remove = () => {
			position.forEach( ( e ) => {
				e.classList.remove( 'display-block' );
			} );
		};

		jQuery( '.select' )
			.on( 'change', function ( e ) {
				const val = e.target.value;
				const position1 = document.querySelector( '.position-1' );
				const position2 = document.querySelector( '.position-2' );
				const position3 = document.querySelector( '.position-3' );
				const position4 = document.querySelector( '.position-4' );

				if ( val == '1' ) {
					remove();
					position1.classList.add( 'display-block' );
				} else if ( val == '2' ) {
					remove();
					position2.classList.add( 'display-block' );
				} else if ( val == '3' ) {
					remove();
					position3.classList.add( 'display-block' );
				} else if ( val == '4' ) {
					remove();
					position4.classList.add( 'display-block' );
				}
			} );
	} );
