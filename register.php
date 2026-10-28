<?php
// Load the handler first. It starts the session and gives us $csrfToken.
require_once __DIR__ . '/Api/register_handler.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Learner Registration · LMSG</title>
<style>
  :root{
    --bg:#102519;
    --card:#FFFFFF;
    --border:#E2E9E3;
    --text:#18221B;
    --muted:#637067;
    --accent:#2F8152;
    --accent-ring:rgba(47,129,82,0.18);
    --grad:linear-gradient(135deg,#174A30 0%,#2F8152 100%);
    --ok:#16A34A;
    --error:#DC2626;
  }

  *{box-sizing:border-box;}
  body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
    position:relative;
    isolation:isolate;
    background:
      radial-gradient(ellipse at 14% 16%,rgba(102,169,121,0.28),transparent 36%),
      radial-gradient(ellipse at 88% 82%,rgba(48,119,75,0.32),transparent 40%),
      linear-gradient(135deg,#102519 0%,#1C3A28 52%,#102219 100%);
    background-attachment:fixed;
  }
  body::before{
    content:"";
    position:fixed;
    inset:0;
    z-index:-1;
    pointer-events:none;
    background-image:
      linear-gradient(rgba(231,245,234,0.035) 1px,transparent 1px),
      linear-gradient(90deg,rgba(231,245,234,0.035) 1px,transparent 1px);
    background-size:48px 48px;
    mask-image:radial-gradient(ellipse at center,#000 0%,transparent 88%);
  }

  .card{
    background:var(--card);
    width:100%;
    max-width:400px;
    padding:32px;
    border-radius:20px;
    border:1px solid var(--border);
    box-shadow:0 16px 48px rgba(4,17,9,0.24);
  }
  .card h2{margin:0 0 4px;text-align:center;}
  .subtitle{margin:0 0 10px;text-align:center;color:var(--muted);font-size:14px;}

  label{display:block;margin-top:16px;font-size:14px;font-weight:600;}
  input{
    width:100%;
    padding:11px 12px;
    margin-top:6px;
    border:1px solid #D1D5DB;
    border-radius:10px;
    font-size:14px;
  }
  input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-ring);}

  button{
    width:100%;
    margin-top:22px;
    padding:12px;
    border:0;
    border-radius:10px;
    background:var(--grad);
    color:#fff;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
  }
  button:disabled{opacity:.6;cursor:not-allowed;}

  #message{margin-top:16px;text-align:center;font-size:14px;min-height:20px;}
  #message.ok{color:var(--ok);}
  #message.error{color:var(--error);}
</style>
</head>
<body>

  <div class="card">
    <h2>Learner Registration</h2>
    <p class="subtitle">Create your account to start learning</p>

    <form id="registerForm">
      <label>Full Name
        <input type="text" id="full_name" maxlength="100" required>
      </label>
      <label>Email
        <input type="email" id="email" maxlength="150" required>
      </label>
      <label>Institute
        <select id="institute_id" required>
          <option value="">Loading institutes...</option>
        </select>
      </label>
      <label>Password
        <input type="password" id="password" required>
      </label>
      <label>Confirm Password
        <input type="password" id="confirm_password" required>
      </label>
      <button type="submit" id="btn">Register</button>
    </form>

    <div id="message"></div>
  </div>

<script>
  // PHP prints these two values into the page
  const API_URL    = 'Api/register_handler.php';
  const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

  const form    = document.getElementById('registerForm');
  const message = document.getElementById('message');
  const btn     = document.getElementById('btn');
  const instituteSelect = document.getElementById('institute_id');

  fetch('Api/institutes_handler.php', { credentials: 'same-origin' })
    .then(function (response) {
      return response.json().then(function (result) {
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Could not load institutes.');
        }
        return result.institutes;
      });
    })
    .then(function (institutes) {
      instituteSelect.replaceChildren(new Option('Select your institute', ''));
      institutes.forEach(function (institute) {
        instituteSelect.add(new Option(institute.name, institute.id));
      });
      btn.disabled = institutes.length === 0;
      if (!institutes.length) {
        message.textContent = 'No active institutes are available for registration.';
        message.className = 'error';
      }
    })
    .catch(function (error) {
      instituteSelect.replaceChildren(new Option('Institutes unavailable', ''));
      btn.disabled = true;
      message.textContent = error.message || 'Could not load institutes.';
      message.className = 'error';
    });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();              // stop the normal page reload
    message.textContent = '';
    message.className = '';
    btn.disabled = true;

    const payload = {
      full_name:        document.getElementById('full_name').value,
      email:            document.getElementById('email').value,
      institute_id:     instituteSelect.value,
      password:         document.getElementById('password').value,
      confirm_password: document.getElementById('confirm_password').value
    };

    try {
      const response = await fetch(API_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      message.textContent = result.message;
      message.className = result.success ? 'ok' : 'error';

      if (result.success) form.reset();
    } catch (err) {
      message.textContent = 'Could not reach the server.';
      message.className = 'error';
    }

    btn.disabled = false;
  });
</script>

</body>
</html>