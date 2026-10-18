
(() => {
    const statuses = [
        ['want_to_read', 'Want To Read'],
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
        const table = document.querySelector('.collection-table');
        const tbody = table?.querySelector('tbody');

        if (!table || !tbody) {
            console.error('Collection table or tbody was not found.');
            return;
        }

        let currentLoad = 0;

        function showMessage(message) {
            tbody.replaceChildren();

            const row = element('tr');
            const cell = element('td', 'collection-message', message);
            cell.colSpan = 4;

            row.append(cell);
            tbody.append(row);
        }

        async function load() {
            const loadId = ++currentLoad;
            showMessage('Loading your collection...');

            try {
                const data = await window.BookManager.request('library.php');

                if (loadId !== currentLoad) return;

                render(Array.isArray(data.books) ? data.books : []);
            } catch (error) {
                if (loadId !== currentLoad) return;

                showMessage(
                    error.message === 'Authentication required'
                        ? 'Log in to view your collection.'
                        : error.message
                );
            }
        }

        function render(books) {
            tbody.replaceChildren();

            if (!books.length) {
                showMessage(
                    'Your collection is empty. Search for books in Library.'
                );
                return;
            }

            const row = element('tr');

            for (const [statusValue, statusName] of statuses) {
                const cell = element('td', 'collection-status-column');
                const statusBooks = books.filter(
                    book => book.status === statusValue
                );

                if (!statusBooks.length) {
                    cell.append(
                        element('p', 'collection-empty', 'No books here yet.')
                    );
                }

                for (const book of statusBooks) {
                    const card = element('article', 'collection-book');

                    if (
                        book.cover_id &&
                        Number.isSafeInteger(Number(book.cover_id)) &&
                        Number(book.cover_id) > 0
                    ) {
                        const cover = element('img', 'collection-book-cover');
                        cover.src =
                            `https://covers.openlibrary.org/b/id/${Number(book.cover_id)}-M.jpg`;
                        cover.alt = `Cover of ${book.title || 'book'}`;
                        cover.loading = 'lazy';
                        card.append(cover);
                    }

                    const title = element(
                        'h3',
                        'collection-book-title',
                        book.title || 'Untitled'
                    );

                    const author = element(
                        'p',
                        'collection-book-author',
                        book.author || 'Author unknown'
                    );

                    card.append(title, author);

                    const statusLabel = element(
                        'label',
                        'form-label collection-label',
                        'Reading status'
                    );

                    const status = element(
                        'select',
                        'form-select collection-input'
                    );

                    status.id = `collection-status-${book.id}`;
                    statusLabel.htmlFor = status.id;

                    for (const [value, label] of statuses) {
                        const option = element('option', '', label);
                        option.value = value;
                        status.append(option);
                    }

                    status.value = statuses.some(
                        ([value]) => value === book.status
                    ) ? book.status : statusValue;

                    const quantityLabel = element(
                        'label',
                        'form-label collection-label',
                        'Quantity'
                    );

                    const quantity = element(
                        'input',
                        'form-control collection-input'
                    );

                    quantity.id = `collection-quantity-${book.id}`;
                    quantityLabel.htmlFor = quantity.id;
                    quantity.type = 'number';
                    quantity.min = '0';
                    quantity.max = '1000';
                    quantity.step = '1';
                    quantity.value = String(book.quantity ?? 0);

                    status.addEventListener('change', () => {
                        if (
                            status.value !== 'want_to_read' &&
                            Number(quantity.value) === 0
                        ) {
                            quantity.value = '1';
                        }
                    });

                    const actions = element(
                        'div',
                        'collection-actions'
                    );

                    const save = element(
                        'button',
                        'btn collection-button',
                        'Save Changes'
                    );

                    save.type = 'button';

                    const remove = element(
                        'button',
                        'btn collection-remove-button',
                        'Remove'
                    );

                    remove.type = 'button';

                    const message = element(
                        'p',
                        'collection-feedback',
                        ''
                    );
                    message.setAttribute('role', 'status');

                    save.addEventListener('click', async () => {
                        const amount = Number(quantity.value);

                        if (
                            !Number.isInteger(amount) ||
                            amount < 0 ||
                            amount > 1000
                        ) {
                            message.textContent =
                                'Quantity must be a whole number from 0 to 1000.';
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
                            await load();
                        } catch (error) {
                            message.textContent = error.message;
                        } finally {
                            save.disabled = false;
                        }
                    });

                    remove.addEventListener('click', async () => {
                        if (
                            !window.confirm(
                                `Remove "${book.title || 'this book'}" from your collection?`
                            )
                        ) {
                            return;
                        }

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

                    card.append(
                        statusLabel,
                        status,
                        quantityLabel,
                        quantity,
                        actions,
                        message
                    );

                    cell.append(card);
                }

                row.append(cell);
            }

            tbody.append(row);
        }

        let knownLoginState = null;

        window.addEventListener('bookmanager:authchange', event => {
            const loggedIn = event.detail.loggedIn;

            if (loggedIn === knownLoginState) return;

            knownLoginState = loggedIn;

            if (!loggedIn) {
                currentLoad++;
                showMessage('Log in to view your collection.');
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
