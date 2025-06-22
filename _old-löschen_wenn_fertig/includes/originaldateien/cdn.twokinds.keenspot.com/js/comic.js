(() => {
	document.addEventListener("DOMContentLoaded", () => {
		var body = document.getElementsByTagName('body')[0];
		// Bind the left and right arrow keys and j/k to comic navigation
		var navprev = document.querySelector("a.navprev");
		var navnext = document.querySelector("a.navnext");
		body.addEventListener("keyup", e => {
			if ((e.which == 37 || e.which == 74) && navprev) {
				parent.location = navprev.getAttribute("href");
			} else if ((e.which == 39 || e.which == 75) && navnext)	{
				parent.location = navnext.getAttribute("href");
			}
		});
	});
})();
