wp.domReady( () => {
	// More Menu
	const toggleMoreMenuButton = button => event => {
		event.preventDefault;
		const moreMenu = button.parentElement;
		if ( moreMenu ) {
			moreMenu.classList.toggle( 'is-menu-visible' );
		}
	}
	const moreMenuButtons = document.querySelectorAll( '.more-menu-toggle' );
	moreMenuButtons.forEach( button => button.addEventListener( 'click', toggleMoreMenuButton( button ) ) );
	document.addEventListener( 'click', event => {
		const moreMenus = document.querySelectorAll('.more-menu-wrapper.is-menu-visible');
		moreMenus.forEach( menu => {
			if ( ! menu.contains( event.target ) ) {
				menu.classList.remove( 'is-menu-visible' );
			}
		} );
	} );
} );