( function () {
	function hnrkDeleteVisitorCookie() {
		document.cookie = 'hnrk_visitor=; Max-Age=0; path=/; SameSite=Lax';
	}

	function hnrkHasAnalyticsConsent() {
		var match = document.cookie.split( '; ' ).find( function ( c ) {
			return c.startsWith( 'cookieyes-consent=' );
		} );
		return match ? match.indexOf( 'analytics:yes' ) !== -1 : false;
	}

	// On page load: if no analytics consent but cookie exists, remove it.
	if ( ! hnrkHasAnalyticsConsent() ) {
		hnrkDeleteVisitorCookie();
	}

	// On consent change: remove cookie if analytics is rejected.
	document.addEventListener( 'cookieyes_consent_update', function ( evt ) {
		var detail = evt.detail;
		if ( detail && detail.rejected && detail.rejected.indexOf( 'analytics' ) !== -1 ) {
			hnrkDeleteVisitorCookie();
		}
	} );
} )();
