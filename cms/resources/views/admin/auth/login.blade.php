<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin login — AZ Portfolio</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0f1115;color:#eef2f7;font-family:Segoe UI,Tahoma,sans-serif}
    form{width:min(380px,92vw);background:#171a21;border:1px solid #2a3140;border-radius:14px;padding:22px}
    label{display:block;font-size:12px;color:#9aa6b2;margin:10px 0 4px}
    input{width:100%;padding:10px;border-radius:8px;border:1px solid #2a3140;background:#0e1218;color:#fff}
    button{margin-top:16px;width:100%;padding:11px;border:0;border-radius:8px;background:#d7b57a;color:#111;font-weight:700;cursor:pointer}
    .err{color:#f0b4b4;font-size:13px}
  </style>
</head>
<body>
<form method="post" action="{{ route('admin.login.submit') }}">
  @csrf
  <h1 style="margin:0 0 8px;font-size:20px">Owner login</h1>
  <p style="margin:0;color:#9aa6b2;font-size:13px">Private admin for Amirhossein Zamanzade portfolio.</p>
  @if ($errors->any())
    <p class="err">{{ $errors->first() }}</p>
  @endif
  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
  <label>Password</label>
  <input type="password" name="password" required autocomplete="current-password">
  <label style="display:flex;gap:8px;align-items:center;margin-top:12px"><input type="checkbox" name="remember" value="1"> Remember me</label>
  <button type="submit">Sign in</button>
</form>
</body>
</html>
