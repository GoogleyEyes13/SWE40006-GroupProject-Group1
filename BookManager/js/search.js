(() => {
  function element(tag, className, text) {
    const item = document.createElement(tag);
    if (className) item.className = className;
    if (text !== undefined) item.textContent = text;
    return item;
  }

  function init() {
    const searchBox = document.querySelector('.search-container');
    const input = searchBox?.querySelector('input');
    const button = searchBox?.querySelector('button');
    if (!searchBox || !input || !button) return;

    let results = document.getElementById('search-results');
    if (!results) {
      results = element('section', 'container-fluid py-4');
      results.id = 'search-results';
      searchBox.after(results);
    }
    results.setAttribute('aria-live', 'polite');

    async function addBook(book, status, clickedButton) {
      clickedButton.disabled = true;
      try {
        if (!await window.BookManager.refresh()) {
          throw new Error('Please log in or register before saving books.');
        }
        await window.BookManager.request('library.php', {
          action: 'add',
          book: {
            key: book.key,
            title: book.title,
            author_name: book.author_name || [],
            first_publish_year: book.first_publish_year ?? null,
            cover_i: book.cover_i ?? null
          },
          status,
          quantity: status === 'owned' ? 1 : 0
        });
        clickedButton.textContent = 'Saved';
      } catch (error) {
        window.alert(error.message);
        clickedButton.disabled = false;
      }
    }

    function renderBooks(books) {
      results.replaceChildren();
      if (!books.length) {
        results.append(element('p', '', 'No books found.'));
        return;
      }

      const grid = element('div', 'row g-3');
      for (const book of books) {
        if (typeof book.key !== 'string' || typeof book.title !== 'string') continue;

        const column = element('div', 'col-12 col-md-6 col-lg-4');
        const card = element('article', 'card h-100');
        const body = element('div', 'card-body');
        body.append(element('h3', 'h5 card-title', book.title));
        body.append(element('p', 'card-text', Array.isArray(book.author_name) ? book.author_name.join(', ') : 'Author unknown'));
        if (Number.isInteger(book.first_publish_year)) {
          body.append(element('p', 'small text-muted', `First published: ${book.first_publish_year}`));
        }

        if (Number.isSafeInteger(book.cover_i) && book.cover_i > 0) {
          const cover = element('img', 'card-img-top');
          cover.src = `https://covers.openlibrary.org/b/id/${book.cover_i}-M.jpg`;
          cover.alt = `Cover of ${book.title}`;
          cover.loading = 'lazy';
          cover.style.cssText = 'max-height:220px;object-fit:contain;padding:12px';
          card.append(cover);
        }

        const actions = element('div', 'd-flex flex-wrap gap-2');
        const wishlist = element('button', 'btn btn-outline-primary btn-sm', 'Add to wishlist');
        wishlist.type = 'button';
        wishlist.addEventListener('click', () => addBook(book, 'want_to_read', wishlist));
        const owned = element('button', 'btn btn-primary btn-sm', 'Add as owned');
        owned.type = 'button';
        owned.addEventListener('click', () => addBook(book, 'owned', owned));
        actions.append(wishlist, owned);
        body.append(actions);
        card.append(body);
        column.append(card);
        grid.append(column);
      }
      results.append(grid);
    }

    async function search() {
      const query = input.value.trim();
      if (!query) {
        results.textContent = 'Enter a book title or author.';
        return;
      }
      button.disabled = true;
      results.textContent = 'Searching...';
      try {
        const data = await window.BookManager.request(`search.php?q=${encodeURIComponent(query)}`);
        renderBooks(Array.isArray(data.docs) ? data.docs : []);
      } catch (error) {
        results.textContent = error.message;
      } finally {
        button.disabled = false;
      }
    }

    button.addEventListener('click', search);
    input.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        search();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
