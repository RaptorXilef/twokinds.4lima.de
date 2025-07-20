(() => {
	const bookmarkButtonId = "add-bookmark";
	const activeBookmarkClass = "bookmarked";
	const bookmarkMaxEntries = 50;

	document.addEventListener("DOMContentLoaded", async () => {
		// Bind the left and right arrow keys and j/k to comic navigation
		var body = document.getElementsByTagName('body')[0];
		var navprev = document.querySelector("a.navprev");
		var navnext = document.querySelector("a.navnext");
		body.addEventListener("keyup", e => {
			if ((e.key == "ArrowLeft" || e.key == "j" || e.key == "J") && navprev) {
				parent.location = navprev.getAttribute("href");
			} else if ((e.key == "ArrowRight" || e.key == "k" || e.key == "k") && navnext)	{
				parent.location = navnext.getAttribute("href");
			}
		});

		const bookmarkButton = document.getElementById(bookmarkButtonId);
		const bookmarks = await getStoredBookmarks();
		if (bookmarkButton) {
			bookmarkButton.addEventListener("click", async (e) => {
				await toggleBookmark(e.target);
			});
			if (bookmarks.has(bookmarkButton.dataset.id)) {
				setBookmarkButtonActive();
			}
		} else if (document.getElementById("bookmarksPage")) {
			populateBookmarksPage(bookmarks);
			document.getElementById("removeAll").addEventListener("click", handleRemoveAllBookmarks);
			document.getElementById("export").addEventListener("click", handleExportBookmarks);
			document.getElementById("import").addEventListener("click", handleImportBookmarks);
			document.getElementById("fileImport").addEventListener("change", handleFileImportStarted);
			document.getElementById("fileImport").addEventListener("cancel", handleFileImportEnded);
		}
	});

	async function toggleBookmark(element) {
		if (typeof window.localStorage == "undefined") {
			return;
		}

		const id = element.dataset.id;
		const page = element.dataset.page;
		const link = element.dataset.permalink;
		const thumb = element.dataset.thumb;

		let bookmarks = new Map();

		try {
			bookmarks = await getStoredBookmarks();
		} catch (e) {
			alertInsecureContext();
			console.error("Failed to load bookmarks", e);
			return;
		}

		let didAddBookmark = false;
		if (bookmarks.has(id)) {
			bookmarks.delete(id);
		} else {
			if (bookmarks.size >= bookmarkMaxEntries) {
				alert("You've already bookmarked the maximum number of pages! Please remove a bookmark first.");
				return;
			}

			bookmarks.set(id, {
				"id": id,
				"page": page,
				"link": link,
				"thumb": thumb
			});
			didAddBookmark = true;
		}

		try {
			await storeBookmarks(bookmarks);
		} catch (e) {
			alertInsecureContext();
			console.error("Failed to save bookmarks", e);
			return;
		}

		if (didAddBookmark) {
			setBookmarkButtonActive();
		} else {
			setBookmarkButtonInactive();
		}
	}

	function removeStoredBookmarks() {
		window.localStorage.removeItem("bookmarks");
		window.localStorage.removeItem("bchk");
	}

	function setBookmarkButtonInactive() {
		const button = document.getElementById(bookmarkButtonId);
		const buttonLabel = "Bookmark this page";
		button.classList.remove(activeBookmarkClass);
		button.innerHTML = buttonLabel;
		button.title = buttonLabel;
	}

	function setBookmarkButtonActive() {
		const button = document.getElementById(bookmarkButtonId);
		const buttonLabel = "Remove this bookmark";
		button.classList.add(activeBookmarkClass);
		button.innerHTML = buttonLabel;
		button.title = buttonLabel;
	}

	async function hash(data) {
		const enc = new TextEncoder();
		const hashBuffer = await window.crypto.subtle.digest("SHA-1", enc.encode(data));
		const hashArray = Array.from(new Uint8Array(hashBuffer));
		const hashHex = hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
		return hashHex;
	}

	async function getStoredBookmarks() {
		const bookmarks = new Map();

		if (typeof window.localStorage === "undefined") {
			return bookmarks;
		}

		if (typeof window.localStorage.bookmarks !== "undefined") {
			try {
				const bookmarkHash = window.localStorage.bchk;
				const bookmarkData = window.localStorage.bookmarks;

				if ((await hash(bookmarkData)) !== bookmarkHash) {
					throw new Error("Bookmark checksum invalid");
				}

				const parsedBookmarks =  JSON.parse(window.localStorage.bookmarks);
				if (parsedBookmarks.length > bookmarkMaxEntries) {
					throw new Error("Stored bookmarks exceeds maximum size allowed");
				}

				parsedBookmarks.forEach((b) => {
					if (b.id) {
						bookmarks.set(b.id, b);
					}
				});
			} catch (e) {
				console.error("Unable to load stored bookmarks - data is invalid.", e);
				removeStoredBookmarks();
			}
		}

		return bookmarks;
	}

	async function storeBookmarks(bookmarkMap) {
		if (typeof window.localStorage === "undefined") {
			return;
		}

		const bookmarkData = JSON.stringify(bookmarkMap.values().toArray());
		const bookmarkHash = await hash(bookmarkData);

		window.localStorage.bookmarks = bookmarkData;
		window.localStorage.bchk = bookmarkHash;
	}

	function handleRemoveAllBookmarks() {
		const confirm = window.confirm("Are you sure you want to remove ALL of your bookmarks?");

		if (confirm) {
			removeStoredBookmarks();
			populateBookmarksPage(new Map());
		}
	}

	async function handleExportBookmarks() {
		let bookmarks;
		try {
			bookmarks = await getStoredBookmarks();
		} catch (e) {
			alertInsecureContext();
			console.error("Failed to load bookmarks", e);
			return;
		}

		const bookmarkData = JSON.stringify(bookmarks.values().toArray());
		const encodedBookmarkData = encodeURIComponent(bookmarkData);

		var dlElement = document.createElement("a");
		dlElement.setAttribute("href", "data:application/json;charset=utf-8," + encodedBookmarkData);
		dlElement.setAttribute("download", "twokinds-bookmarks.json");

		document.body.appendChild(dlElement);
		dlElement.click();
		document.body.removeChild(dlElement);
	}

	function validateBookmarkImport(parsedData) {
		if (!Array.isArray(parsedData)) {
			console.error("Parsed JSON is not an array");
			return false;
		}

		if (parsedData.length > bookmarkMaxEntries) {
			console.error("Parsed JSON contains too many entries");
			return false;
		}

		for (i = 0; i < parsedData.length; i++) {
			const entry = parsedData[i];
			if (typeof entry !== "object") {
				console.error("Array entry is not an object");
				return false;
			}

			if (!('id' in entry) || !('page' in entry) || !('link' in entry) || !('thumb' in entry)) {
				console.error("Array entry is missing a required key");
				return false;
			}

			const values = Object.values(entry);
			for (v = 0; v < values.length; v++) {
				if (typeof values[v] !== "string" && typeof values[v] !== "undefined") {
					console.error("Object value is not a string")
					return false;
				}
			}

			if (!entry.link.startsWith("/comic/") && !entry.link.startsWith(window.origin + "/comic/")) {
				console.error("Invalid page link " + entry.link);
				return false;
			}

			if (!entry.thumb.includes("/comics/thumbnails/")) {
				console.error("Invalid thumbnail URL " + entry.thumb);
				return false;
			}
		}

		return true;
	}

	function alertNotValid() {
		alert("Sorry, this file is not a valid bookmark file.");
	}

	function alertInsecureContext() {
		alert("Failed to process bookmarks. Make sure you are using the HTTPS version of the website with an up-to-date browser!");
	}

	async function handleFileImportStarted(ev) {``
		const file = ev.target.files[0];
		if (!file) {
			handleFileImportEnded();
			return;
		}

		if (file.type !== "application/json") {
			console.error("Selected file is type " + file.type + "but must be application/json");
			alertNotValid();
			handleFileImportEnded();
			return;
		}

		if (file.size > 10240) {
			console.error("Selected file is too large (" + file.size + " bytes)");
			alertNotValid();
			handleFileImportEnded();
			return;
		}

		const reader = new FileReader();

		reader.onload = async () => {
			const fileContent = reader.result;

			let parsedData = undefined;
			try {
				parsedData = JSON.parse(fileContent);
			} catch (e) {
				console.error("Input file could not be parsed as JSON", e);
				alertNotValid();
				handleFileImportEnded();
				return;
			}

			if (!validateBookmarkImport(parsedData)) {
				alertNotValid();
			} else {
				await storeImportedBookmarks(parsedData);
			}

			handleFileImportEnded();
		};

		reader.onerror = () => {
			alert("Sorry, the file could not be read. Please try again.");
			handleFileImportEnded();
		};

		reader.readAsText(file);
	}

	async function storeImportedBookmarks(parsedData) {
		const bookmarksMap = new Map();

		parsedData.forEach((entry) => {
			bookmarksMap.set(entry.id, entry);
		});

		try {
			await storeBookmarks(bookmarksMap);
		} catch (e) {
			alertInsecureContext();
			console.error("Failed to save bookmarks", e);
			return;
		}
		populateBookmarksPage(bookmarksMap);
	}

	function handleFileImportEnded() {
		document.getElementById("import").disabled = false;
	}

	async function handleImportBookmarks() {
		document.getElementById("import").disabled = true;
		document.getElementById("fileImport").click();
	}

	async function handleRemoveBookmarkById(id) {
		const bookmarks = await getStoredBookmarks();
		if (!bookmarks.has(id)) {
			return;
		}

		bookmarks.delete(id);
		await storeBookmarks(bookmarks);
		populateBookmarksPage(bookmarks);
	}

	function populateBookmarksPage(bookmarkMap) {
		document.getElementById("removeAll").disabled = true;
		document.getElementById("export").disabled = true;
		const bookmarksSection = document.querySelector("#bookmarksWrapper");
		const noBookmarksTemplate = document.querySelector("#noBookmarks");
		const bookmarkWrapperTemplate = document.querySelector("#pageBookmarkWrapper");
		const pageBookmarkTemplate = document.querySelector("#pageBookmark");
		bookmarksSection.innerHTML = "";

		if (!bookmarkMap.size) {
			const noBookmarksElement = noBookmarksTemplate.content.cloneNode(true);
			bookmarksSection.appendChild(noBookmarksElement);
			return;
		}

		const wrapper = bookmarkWrapperTemplate.content.cloneNode(true);
		bookmarksSection.appendChild(wrapper);

		const bookmarksSorted = new Map([...bookmarkMap].sort((a, b) => a[1].id.localeCompare(b[1].id)));

		bookmarksSorted.values().forEach((b) => {
			const bookmark = pageBookmarkTemplate.content.cloneNode(true);
			const link = bookmark.querySelector("a");
			link.href = b.link;
			const pageNum = bookmark.querySelector("span");
			const pageNumTextNode = document.createTextNode(b.page || "");
			pageNum.appendChild(pageNumTextNode);
			const image = bookmark.querySelector("img");
			image.src = b.thumb;
			image.alt = b.page || "Page";

			pageNum.querySelector(".delete").addEventListener('click', async (e) => {
				e.preventDefault();
				e.stopPropagation();
				await handleRemoveBookmarkById(b.id);
			});

			document.querySelector(".chapter-links").appendChild(bookmark);
		});

		document.getElementById("removeAll").disabled = false;
		document.getElementById("export").disabled = false;
	}
})();
