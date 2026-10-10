<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SFMS</title>
<style>body{font:15px/1.6 system-ui,sans-serif;max-width:640px;margin:48px auto;padding:0 16px;color:#1B2740}button{padding:8px 16px;border-radius:999px;border:1px solid #8E2A3A;background:none;color:#8E2A3A;cursor:pointer}</style></head><body>
<h1>Signed in</h1>
<p>{{ auth()->user()->email }} · <b>{{ auth()->user()->role }}</b></p>
<p>Your sign-in works and your role is enforced on the server. Finance pages are added to this area next, each limited to the roles that need it.</p>
<form method="POST" action="/logout">@csrf<button>Sign out</button></form>
</body></html>
