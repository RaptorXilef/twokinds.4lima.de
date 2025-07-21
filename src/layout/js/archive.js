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
	 * Displays the thumbnails of a given chapter.
	 * @param {HTMLElement} chapter The chapter element.
	 * @param {bool} noStore If true, the chapter's expanded state will not be stored in local storage.
	 * @returns {undefined}
	 */
	function showThumbnails(chapter, noStore) {
		if (chapter == null) {
			return;
		}

		var linkContainer = chapter.querySelector(".chapter-links");
		var arrow = chapter.querySelector(".arrow-left, .arrow-down");

		if (linkContainer.style.display == "none") {
			// Switch arrow direction and animate the container
			arrow.classList.remove("arrow-left");
			arrow.classList.add("arrow-down");
			arrow.classList.add("animate");
			setTimeout(() => { arrow.classList.remove("animate"); }, 1500);

			linkContainer.closest(".chapter").classList.add("expanded");
			linkContainer.style.display = "flex"; // Changed from "block" to "flex" for better layout

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
				// 10 minutes from now
				expireTime: (new Date).getTime() + 600000,
				expandedChapters: chapterArray
			};

			window.localStorage.archiveExpansion = JSON.stringify(storageObj);
		}
	}
})();
