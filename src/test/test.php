<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registation Form</title>
</head>
<body>
   <style>
  :root{
    --bg:#F4F3FA;
    --card:#FFFFFF;
    --border:#ECEAF6;
    --text:#15121F;
    --muted:#6B7280;
    --purple:#7C3AED;
    --grad:linear-gradient(135deg,#7C3AED 0%,#D6409F 55%,#EC4899 100%);
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
  }

  .card{
    background:var(--card);
    width:100%;
    max-width:400px;
    padding:32px;
    border-radius:20px;
    border:1px solid var(--border);
    box-shadow:0 8px 24px rgba(20,15,50,0.08);
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
  input:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 3px rgba(124,58,237,0.15);}

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

    const form    = document.getElementById('registerForm');
    const message = document.getElementById('message');

    form.addEventListener('submit',function (e){
        e.preventDefault();
        
        const full_name        = document.getElementById('full_name').value.trim();
        const email            = document.getElementById('email').value.trim();
        const password         = document.getElementById('password').value;
        const confirm_password = document.getElementById('confirm_password').value;

         if (password !== confirm_password) {
            message.textContent = 'Passwords do not match.';
         }else{
            message.textContent = 'Welcome, ' + full_name + '! Your registration is complete.';
            message.className = 'ok';
            form.reset();   
         }
    });




</script>


</body>
</html>