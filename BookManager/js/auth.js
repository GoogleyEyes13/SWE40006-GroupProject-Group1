(() => {
  const session = { loggedIn: false, csrfToken: null };

  async function parseResponse(response) {
    let payload;
    try {
      payload = await response.json();
    } catch {
      throw new Error(`Unexpected server response (${response.status})`);
    }
    if (!response.ok) {
      throw new Error(payload.error || `Request failed (${response.status})`);
    }
    return payload;
  }

  async function refresh() {
    const response = await fetch('backend/auth.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const result = await parseResponse(response);
    session.loggedIn = result.logged_in === true;
    session.csrfToken = result.csrf_token || null;
    window.dispatchEvent(new CustomEvent('bookmanager:authchange', {
      detail: { loggedIn: session.loggedIn }
    }));
    return session.loggedIn;
  }

  async function request(path, data) {
    if (data !== undefined && !session.csrfToken) {
      await refresh();
    }

    const options = {
      credentials: 'same-origin',
      cache: 'no-store'
    };

    if (data !== undefined) {
      options.method = 'POST';
      options.headers = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': session.csrfToken
      };
      options.body = JSON.stringify(data);
    }

    return parseResponse(await fetch(`backend/${path}`, options));
  }

  window.BookManager = {
    request,
    refresh,
    isLoggedIn: () => session.loggedIn
  };

  async function init() {
    const navbar = document.querySelector('nav');
    const panel = document.createElement('section');
    panel.className = 'container-fluid py-3';
    panel.innerHTML = `
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span id="bm-auth-status" class="me-2" aria-live="polite">Checking session...</span>
        <button type="button" id="bm-login-button" class="btn btn-outline-primary btn-sm">Log in</button>
        <button type="button" id="bm-register-button" class="btn btn-outline-primary btn-sm">Register</button>
        <button type="button" id="bm-logout-button" class="btn btn-outline-secondary btn-sm">Log out</button>
      </div>
      <form id="bm-auth-form" class="row g-2 mt-2" style="display:none;max-width:600px">
        <div class="col-12 col-sm-5">
          <input id="bm-email" type="email" class="form-control" placeholder="Email" autocomplete="email" required>
        </div>
        <div class="col-12 col-sm-5">
          <input id="bm-password" type="password" class="form-control" placeholder="Password" minlength="12" required>
        </div>
        <div class="col-12 col-sm-2">
          <button id="bm-submit" type="submit" class="btn btn-primary w-100">Log in</button>
        </div>
      </form>
      <p id="bm-auth-message" class="small mt-2 mb-0" role="status"></p>
    `;
    if (navbar) {
      navbar.after(panel);
    } else {
      document.body.prepend(panel);
    }

    const status = panel.querySelector('#bm-auth-status');
    const login = panel.querySelector('#bm-login-button');
    const register = panel.querySelector('#bm-register-button');
    const logout = panel.querySelector('#bm-logout-button');
    const form = panel.querySelector('#bm-auth-form');
    const password = panel.querySelector('#bm-password');
    const submit = panel.querySelector('#bm-submit');
    const message = panel.querySelector('#bm-auth-message');
    let action = 'login';

    function render() {
      status.textContent = session.loggedIn ? 'Signed in' : 'Not signed in';
      login.style.display = session.loggedIn ? 'none' : '';
      register.style.display = session.loggedIn ? 'none' : '';
      logout.style.display = session.loggedIn ? '' : 'none';
      if (session.loggedIn) form.style.display = 'none';
    }

    function showForm(nextAction) {
      action = nextAction;
      message.textContent = '';
      submit.textContent = action === 'register' ? 'Register' : 'Log in';
      password.autocomplete = action === 'register' ? 'new-password' : 'current-password';
      form.style.display = 'flex';
      panel.querySelector('#bm-email').focus();
    }

    login.addEventListener('click', () => showForm('login'));
    register.addEventListener('click', () => showForm('register'));

    form.addEventListener('submit', async event => {
      event.preventDefault();
      submit.disabled = true;
      message.textContent = '';
      try {
        await request('auth.php', {
          action,
          email: panel.querySelector('#bm-email').value.trim(),
          password: password.value
        });
        await refresh();
        form.reset();
        message.textContent = action === 'register' ? 'Account created.' : 'Logged in.';
      } catch (error) {
        message.textContent = error.message;
      } finally {
        submit.disabled = false;
        render();
      }
    });

    logout.addEventListener('click', async () => {
      logout.disabled = true;
      message.textContent = '';
      try {
        await request('auth.php', { action: 'logout' });
        await refresh();
        message.textContent = 'Logged out.';
      } catch (error) {
        message.textContent = error.message;
      } finally {
        logout.disabled = false;
        render();
      }
    });

    window.addEventListener('bookmanager:authchange', render);
    try {
      await refresh();
    } catch (error) {
      status.textContent = 'Authentication unavailable';
      message.textContent = error.message;
    }
    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
