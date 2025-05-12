<form action="login.php" method="POST" onsubmit="return validateLogin()">
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Password" required minlength="6">
  <button type="submit">Login</button>
</form>

<script>
function validateLogin() {
  const email = document.querySelector('input[name="email"]').value;
  const password = document.querySelector('input[name="password"]').value;

  const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;

  if (!emailPattern.test(email)) {
    alert("Invalid email.");
    return false;
  }

  if (password.length < 6) {
    alert("Password too short.");
    return false;
  }

  return true;
}
</script>