/**
 * Enables dynamic loading of thumbnails for archive page.
 */
(function() {
	$(document).ready(function () {
		$(".chapter h2").click(function () {
			showThumbnails($(this));
		});

		$(".chapter-links").css("display", "none");
		$(".chapter-links img").css("display", "none");

		var storedExpansion = null;
		var expandedChapters = [];
		var expireTime = null;
		if (typeof window.localStorage != "undefined" && typeof window.localStorage.archiveExpansion != "undefined") {
			storedExpansion = JSON.parse(window.localStorage.archiveExpansion);
			expandedChapters = storedExpansion.expandedChapters;
			expireTime = storedExpansion.expireTime;
		}

		// If the user has previously expanded sections of this page, re-expand those sections on return.
		var time = (new Date).getTime();
		if (!expandedChapters.length || expireTime <= time) {
			showThumbnails($(".chapter:first-of-type h2"), true);
		} else {
			for (var idx = 0; idx < expandedChapters.length; ++idx) {
				var identifier = ".chapter[data-ch-id='" + expandedChapters[idx] + "']";
				showThumbnails($(identifier), true);
			}
		}
	});

	/**
	 * Applies a hover effect to a thumbnail without interfering with mobile touch.
	 * @param {jQuery} target The link containing the thumbnail to be bound.
	 * @returns {undefined}
	 */
	function bindHover(target) {
		target.off('touchstart mouseenter mouseleave touchmove click');

		target.on('touchstart mouseenter', function () {
			$(this).addClass('hover');
		});

		target.on('mouseleave touchmove click', function () {
			$(this).removeClass('hover');
		});
	}

	/**
	 * Displays the thumbnails under a chapter
	 * @param {jQuery} target The chapter heading to expand.
	 * @param {bool} noAnimation If true, the heading is expanded with no animation.
	 * @param {bool} noStore Don't store the expansion in local storage.
	 * @returns {undefined}
	 */
	function showThumbnails(target, noAnimation, noStore) {
		var chapterHeader = target;
		var linkContainer = chapterHeader.closest(".chapter").find(".chapter-links").first();

		// If the links are hidden, load the thumbnails and display them. Otherwise collapse the section.
		if (linkContainer.is(":hidden")) {
			// Switch arrow direction and animate the container
			chapterHeader.find(".arrow-down, .arrow-left").removeClass("arrow-left").addClass("arrow-down");
			linkContainer.closest(".chapter").addClass("expanded");

			if (!noAnimation) {
				linkContainer.slideDown();
			} else {
				linkContainer.show();
			}

			// Dynamically load images
			if (!linkContainer.data('loaded')) {
				linkContainer.data('loaded', true);

				linkContainer.find('img').each(function () {
					var thisImg = $(this);

					// When the image is done loading, fade it in
					// At the end of the fade, swap the page number to the front and only show it on hover.
					thisImg.on('load', function () {
						// Add hover to the link
						bindHover(thisImg.closest('a'));

						thisImg.fadeIn(noAnimation ? 0 : 400, function () {
							thisImg.closest('a').addClass('loaded');
						});
					});

					// Load each image
					thisImg.attr('src', thisImg.data('src'));
				});
			}
		} else {
			// Switch arrow direction and animate the container
			chapterHeader.find(".arrow-down, .arrow-left").removeClass("arrow-down").addClass("arrow-left");
			linkContainer.closest(".chapter").removeClass("expanded");
			linkContainer.slideUp();
		}

		// Write a new list of expanded chapters to local storage
		if (typeof window.localStorage != "undefined" && !noStore) {
			var expandedChapters = $(".chapter.expanded");
			var chapterArray = [];
			for (var idx = 0; idx < expandedChapters.length; ++idx) {
				chapterArray.push($(expandedChapters[idx]).data("ch-id"));
			}

			var storageObj = {
				// 10 minute expire time
				"expireTime": (new Date).getTime() + 1000000,
				"expandedChapters": chapterArray
			};

			window.localStorage.setItem("archiveExpansion", JSON.stringify(storageObj));
		}
	}
})();
