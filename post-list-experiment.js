wp.domReady( () => {
	// More Menu
	const toggleMoreMenuButton = button => event => {
		event.preventDefault;
		const moreMenu = button.nextElementSibling;
		if ( moreMenu ) {
			moreMenu.classList.toggle( 'hidden' );
		}
	}
	const moreMenuButtons = document.querySelectorAll( '.column-more-menu a' );
	moreMenuButtons.forEach( button => button.addEventListener( 'click', toggleMoreMenuButton( button ) ) );
} );