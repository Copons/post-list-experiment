wp.domReady( () => {
	// More Menu
	const toggleMoreMenu = button => event => {
		event.preventDefault;
		const moreMenu = button.parentElement;
		if ( moreMenu ) {
			moreMenu.classList.toggle( 'is-menu-visible' );
		}
	}
	const moreMenuButtons = document.querySelectorAll( '.more-menu-toggle' );
	moreMenuButtons.forEach( button => button.addEventListener( 'click', toggleMoreMenu( button ) ) );
	document.addEventListener( 'click', event => {
		const moreMenus = document.querySelectorAll('.more-menu-wrapper.is-menu-visible');
		moreMenus.forEach( menu => {
			if ( ! menu.contains( event.target ) ) {
				menu.classList.remove( 'is-menu-visible' );
			}
		} );
	} );

	// Copy Link
	const copyLink = button => event => {
		event.preventDefault();
		navigator.clipboard.writeText( button.href );
	}
	const copyLinkButtons = document.querySelectorAll( '.more-menu-popover a[data-action="copy-link"]' );
	copyLinkButtons.forEach( button => button.addEventListener( 'click', copyLink( button ) ) );
} );