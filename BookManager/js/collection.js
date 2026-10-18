(() => {
  const statuses = [
    ['want_to_read', 'Want to Read'],
    ['owned', 'Owned'],
    ['reading', 'Reading'],
    ['read', 'Read']
  ];

  function element(tag, className, text) {
    const item = document.createElement(tag);
    if (className) item.className = className;
    if (text !== undefined) item.textContent = text;
    return item;
  }

  function init() {
    let results = document.getElementById('collection-results');
    if (!results) {
      results = element('section', 'container-fluid py-4');
      results.id = 'collection-results';
      const heading = [...document.querySelectorAll('h2')]
        .find(item => item.textContent.trim() === 'My Collection');
      if (heading) {
        const placeholder = heading.nextElementSibling;
        if (placeholder?.textContent.trim() === 'My Collection Content') placeholder.remove();
        heading.after(results);
      } else {
        document.body.append(results);
      }
    }
    results.setAttribute('aria-live', 'polite');

    let currentLoad = 0;

    async function load() {
      const loadId = ++currentLoad;
      results.textContent = 'Loading collection...';
      try {
        const data = await window.BookManager.request('library.php');
        if (loadId !== currentLoad) return;
        render(Array.isArray(data.books) ? data.books : []);
      } catch (error) {
        if (loadId === currentLoad) {
          results.textContent = error.message === 'Authentication required'
            ? 'Log in to view your collection.'
            : error.message;
        }
      }
    }

    function render(books) {
      results.replaceChildren();
      if (!books.length) {
        results.append(element('p', '', 'Your collection is empty. Search for books in Library.'));
        return;
      }
      const grid = element('div', 'row g-3');
      for (const book of books) {
        const column = element('div', 'col-12 col-md-6 col-lg-4');
        const card = element('article', 'card h-100');
        const body = element('div', 'card-body');
        body.append(element('h3', 'h5 card-title', book.title || 'Untitled'));
        body.append(element('p', 'card-text', book.author || 'Author unknown'));

        if (Number.isSafeInteger(Number(book.cover_id)) && Number(book.cover_id) > 0) {
          const cover = element('img', 'card-img-top');
          cover.src = `https://covers.openlibrary.org/b/id/${Number(book.cover_id)}-M.jpg`;
          cover.alt = `Cover of ${book.title || 'book'}`;
          cover.loading = 'lazy';
          cover.style.cssText = 'max-height:220px;object-fit:contain;padding:12px';
          card.append(cover);
        }

        const statusLabel = element('label', 'form-label mt-2', 'Reading status');
        const status = element('select', 'form-select mb-2');
        for (const [value, label] of statuses) {
          const option = element('option', '', label);
          option.value = value;
          status.append(option);
        }
        status.value = book.status;
        const quantityLabel = element('label', 'form-label', 'Quantity');
        const quantity = element('input', 'form-control mb-3');
        quantity.type = 'number';
        quantity.min = '0';
        quantity.max = '1000';
        quantity.step = '1';
        quantity.value = String(book.quantity ?? 0);

        const actions = element('div', 'd-flex gap-2');
        const save = element('button', 'btn btn-primary btn-sm', 'Save changes');
        save.type = 'button';
        const remove = element('button', 'btn btn-outline-danger btn-sm', 'Remove');
        remove.type = 'button';
        const message = element('p', 'small mt-2 mb-0');

        save.addEventListener('click', async () => {
          const amount = Number(quantity.value);
          if (!Number.isInteger(amount) || amount < 0 || amount > 1000) {
            message.textContent = 'Quantity must be a whole number from 0 to 1000.';
            return;
          }
          save.disabled = true;
          message.textContent = '';
          try {
            await window.BookManager.request('library.php', {
              action: 'update',
              book_id: Number(book.id),
              status: status.value,
              quantity: amount
            });
            message.textContent = 'Saved.';
          } catch (error) {
            message.textContent = error.message;
          } finally {
            save.disabled = false;
          }
        });

        remove.addEventListener('click', async () => {
          if (!window.confirm(`Remove "${book.title}" from your collection?`)) return;
          remove.disabled = true;
          message.textContent = '';
          try {
            await window.BookManager.request('library.php', {
              action: 'remove',
              book_id: Number(book.id)
            });
            await load();
          } catch (error) {
            message.textContent = error.message;
            remove.disabled = false;
          }
        });

        actions.append(save, remove);
        body.append(statusLabel, status, quantityLabel, quantity, actions, message);
        card.append(body);
        column.append(card);
        grid.append(column);
      }
      results.append(grid);
    }

    let knownLoginState = null;
    window.addEventListener('bookmanager:authchange', event => {
      const loggedIn = event.detail.loggedIn;
      if (loggedIn === knownLoginState) return;
      knownLoginState = loggedIn;
      if (!loggedIn) {
        currentLoad++;
        results.textContent = 'Log in to view your collection.';
      } else {
        load();
      }
    });
    load();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
