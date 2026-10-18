import http.cookiejar
import json
import secrets
import subprocess
import urllib.error
import urllib.request

BASE = "http://127.0.0.1"
EMAIL = f"ci-{secrets.token_hex(8)}@example.test"
PASSWORD = secrets.token_urlsafe(24)

cookies = http.cookiejar.CookieJar()

client = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(cookies),
    urllib.request.ProxyHandler({})
)

def request(path, data=None, token=None):
    headers = {}
    payload = None

    if data is not None:
        payload = json.dumps(data).encode()
        headers["Content-Type"] = "application/json"

    if token:
        headers["X-CSRF-Token"] = token

    req = urllib.request.Request(
        BASE + path,
        data=payload,
        headers=headers
    )

    with client.open(req, timeout=15) as response:
        return response.status, json.load(response)


registered = False

try:
    _, session = request("/backend/auth.php")
    token = session["csrf_token"]

    status, result = request("/backend/auth.php", {
        "action": "register",
        "email": EMAIL,
        "password": PASSWORD
    }, token)

    assert status == 201
    assert result["success"]
    registered = True
    print("PASS: Registration")

    _, result = request("/backend/library.php")
    assert result["books"] == []
    print("PASS: Empty personal library")

    status, result = request("/backend/library.php", {
        "action": "add",
        "book": {
            "key": "/works/OL27448W",
            "title": "The Lord of the Rings",
            "author_name": ["J. R. R. Tolkien"],
            "first_publish_year": 1954
        },
        "status": "want_to_read",
        "quantity": 0
    }, token)

    assert status == 201
    book_id = result["book_id"]
    print("PASS: Add book")

    _, result = request("/backend/library.php")
    assert len(result["books"]) == 1
    print("PASS: Retrieve library")

    _, result = request("/backend/library.php", {
        "action": "update",
        "book_id": book_id,
        "status": "owned",
        "quantity": 1
    }, token)

    assert result["success"]

    _, result = request("/backend/library.php")
    assert result["books"][0]["status"] == "owned"
    assert int(result["books"][0]["quantity"]) == 1
    print("PASS: Update book")

    _, result = request("/backend/library.php", {
        "action": "remove",
        "book_id": book_id
    }, token)

    assert result["success"]
    print("PASS: Remove book")

    _, result = request("/backend/auth.php", {
        "action": "logout"
    }, token)

    assert result["success"]
    print("PASS: Logout")

    _, session = request("/backend/auth.php")
    token = session["csrf_token"]

    _, result = request("/backend/auth.php", {
        "action": "login",
        "email": EMAIL,
        "password": PASSWORD
    }, token)

    assert result["success"]
    print("PASS: Login")

    print("\nALL API TESTS PASSED")

finally:
    if registered:
        php = (
            'require "/opt/book-library/database/connection.php";'
            '$db = connectToDatabase();'
            '$stmt = $db->prepare("DELETE FROM users WHERE email = :email");'
            '$stmt->execute(["email" => getenv("SMOKE_EMAIL")]);'
        )

        subprocess.run([
            "docker", "exec",
            "-e", f"SMOKE_EMAIL={EMAIL}",
            "book-library-test",
            "php", "-r", php
        ], check=True)