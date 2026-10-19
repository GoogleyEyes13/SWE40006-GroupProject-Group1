import os
import secrets

import pytest
from playwright.sync_api import Page, expect

EMAIL = os.environ.get("E2E_EMAIL") or f"e2e-{secrets.token_hex(6)}@example.test"
PASSWORD = secrets.token_urlsafe(18)

expect.set_options(timeout=15_000)


@pytest.fixture(autouse=True)
def accept_dialogs(page: Page):
    page.on("dialog", lambda dialog: dialog.accept())


def open_page(page: Page, link: str, path: str):
    page.get_by_role("link", name=link).click()
    page.wait_for_url(f"**/{path}")


def submit_auth_form(page: Page, button: str):
    page.click(button)
    page.fill("#bm-email", EMAIL)
    page.fill("#bm-password", PASSWORD)
    page.click("#bm-submit")
    expect(page.locator("#bm-auth-status")).to_have_text("Signed in")


def test_home_page_loads(page: Page):
    page.goto("/")

    expect(page).to_have_title("Book Manager")
    expect(page.get_by_role("heading", name="Welcome to Book Manager")).to_be_visible()
    expect(page.locator("#bm-auth-status")).to_have_text("Not signed in")


def test_navigation_between_pages(page: Page):
    page.goto("/")

    open_page(page, "Library", "library.html")
    expect(page).to_have_title("Book Manager - Library")
    expect(page.get_by_placeholder("Search Books...")).to_be_visible()

    open_page(page, "My Collection", "mycollection.html")
    expect(page).to_have_title("Book Manager - My Collection")
    expect(page.locator(".collection-table-headings")).to_have_count(4)


def test_deployed_version(page: Page):
    expected = os.environ.get("EXPECTED_VERSION")

    if not expected:
        pytest.skip("EXPECTED_VERSION is not set")

    response = page.request.get("/backend/health.php")

    assert response.ok
    assert response.json()["version"] == expected


def test_collection_requires_login(page: Page):
    page.goto("/mycollection.html")

    expect(page.locator(".collection-message")).to_have_text("Log in to view your collection.")


def test_reader_journey(page: Page):
    page.goto("/")
    submit_auth_form(page, "#bm-register-button")

    open_page(page, "Library", "library.html")
    page.get_by_placeholder("Search Books...").fill("The Hobbit Tolkien")
    page.get_by_role("button", name="Search").click()

    first_result = page.locator(".book-card").first
    expect(first_result).to_be_visible(timeout=30_000)
    title = first_result.locator(".card-title").inner_text()

    first_result.get_by_role("button", name="Add to wishlist").click()
    expect(first_result.get_by_role("button", name="Saved")).to_be_disabled()

    open_page(page, "My Collection", "mycollection.html")
    columns = page.locator(".collection-status-column")

    wishlist_book = columns.nth(0).locator(".collection-book")
    expect(wishlist_book).to_have_count(1)
    expect(wishlist_book.locator(".collection-book-title")).to_have_text(title)

    wishlist_book.get_by_label("Reading status").select_option("read")
    expect(wishlist_book.get_by_label("Quantity")).to_have_value("1")
    wishlist_book.get_by_role("button", name="Save Changes").click()

    read_book = columns.nth(3).locator(".collection-book")
    expect(read_book.locator(".collection-book-title")).to_have_text(title)
    expect(columns.nth(0).locator(".collection-book")).to_have_count(0)

    read_book.get_by_role("button", name="Remove").click()
    expect(page.locator(".collection-message")).to_have_text(
        "Your collection is empty. Search for books in Library."
    )

    page.click("#bm-logout-button")
    expect(page.locator("#bm-auth-status")).to_have_text("Not signed in")
    expect(page.locator(".collection-message")).to_have_text("Log in to view your collection.")

    submit_auth_form(page, "#bm-login-button")
    expect(page.locator(".collection-message")).to_have_text(
        "Your collection is empty. Search for books in Library."
    )
