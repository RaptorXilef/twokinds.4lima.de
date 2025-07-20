/**
 * Enables dynamic loading of thumbnails for archive page.
 */
(function() {
	addEventListener("DOMContentLoaded", () => {
		document
			.querySelectorAll(".chapter h2")
			.forEach((el) => el.addEventListener('click', (ev) => { showThumbnails(ev.target.closest(".chapter")); }));

		document.querySelectorAll(".chapter-links").forEach((el) => { el.style.display = "none"; });

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
			const firstChapter = document.querySelector(".chapter:first-of-type");
			showThumbnails(firstChapter, true);
		} else {
			for (var idx = 0; idx < expandedChapters.length; ++idx) {
				var chapter = document.querySelector(".chapter[data-ch-id='" + expandedChapters[idx] + "']");
				showThumbnails(chapter, true);
			}
		}
	});

	/**
	 * Displays the thumbnails under a chapter
	 * @param {Element} element The chapter heading to expand.
	 * @param {bool} noAnimation If true, the heading is expanded with no animation.
	 * @param {bool} noStore Don't store the expansion in local storage.
	 * @returns {undefined}
	 */
	function showThumbnails(element, noAnimation, noStore) {
		if (!element) {
			return;
		}

		const chapterHeader = element.querySelector("h2");
		const linkContainer = element.querySelector(".chapter-links");

		// If the links are hidden, load the thumbnails and display them. Otherwise collapse the section.
		const arrow = chapterHeader.querySelector(".arrow-down, .arrow-left");
		if (linkContainer.style.display === "none") {
			// Switch arrow direction and animate the container
			if (!noAnimation) {
				arrow.classList.add("animate");
			}
			arrow.classList.remove("arrow-left");
			arrow.classList.add("arrow-down");

			setTimeout(() => { arrow.classList.remove("animate"); }, 1500);

			linkContainer.closest(".chapter").classList.add("expanded");
			linkContainer.style.display = "flex";

			// Dynamically load images
			if (!linkContainer.dataset.loaded) {
				linkContainer.dataset.loaded = true;

				linkContainer.querySelectorAll('img').forEach((img) => {
					// When the image is done loading, fade it in
					// At the end of the fade, swap the page number to the front and only show it on hover.
					img.addEventListener('load', () => {
						img.closest('a').classList.add('loaded');
					});

					// Load each image
					img.setAttribute('src', img.dataset.src);
				});
			}
		} else {
			// Switch arrow direction and animate the container
			arrow.classList.add("arrow-left");
			arrow.classList.remove("arrow-down");
			linkContainer.closest(".chapter").classList.remove("expanded");
			linkContainer.style.display = "none";
		}

		// Write a new list of expanded chapters to local storage
		if (typeof window.localStorage != "undefined" && !noStore) {
			var expandedChapters = document.querySelectorAll(".chapter.expanded");
			var chapterArray = [];

			expandedChapters.forEach((chapter) => {
				chapterArray.push(chapter.dataset.chId);
			});

			var storageObj = {
				// 10 minute expire time
				"expireTime": (new Date).getTime() + 1000000,
				"expandedChapters": chapterArray
			};

			window.localStorage.setItem("archiveExpansion", JSON.stringify(storageObj));
		}
	}
})();
