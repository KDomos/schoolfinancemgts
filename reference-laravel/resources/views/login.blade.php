<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · SFMS</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F2F4F7;font:15px/1.5 system-ui,sans-serif;color:#1B2740}
form{background:#fff;padding:28px;border-radius:12px;width:min(380px,92vw);box-shadow:0 10px 30px -12px #1b274055}
h1{margin:0 0 4px}p{color:#5C6880;margin:0 0 18px}label{display:block;font-size:13px;color:#5C6880;margin-bottom:12px}
input{width:100%;padding:10px;border:1px solid #D9DFE8;border-radius:8px;font:inherit;margin-top:4px}
button{width:100%;padding:11px;border:0;border-radius:999px;background:#8E2A3A;color:#fff;font-weight:600;cursor:pointer}
.err{color:#8E2A3A;margin-bottom:10px}
</style></head><body>
<form method="POST" action="/login">@csrf
<h1>SFMS</h1><p>Sign in to the school finance workspace.</p>
@error('email')<div class="err" role="alert">{{ $message }}</div>@enderror
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button>Sign in</button>
</form></body></html>
