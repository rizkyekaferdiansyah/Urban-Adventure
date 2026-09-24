<?php $title='Daftar | Urban Adventure'; require __DIR__ . '/_header.php'; ?>
<section class="auth-panel">
    <p class="eyebrow dark">MULAI BERTUALANG</p>
    <h1>Buat akun rental.</h1>
    <form id="authForm">
        <label>Nama
            <input class="input" name="name" required>
        </label>
        <label>Email
            <input class="input" type="email" name="email" required>
        </label>
        <label>Nomor HP
            <input class="input" name="phone" required>
        </label>
        <label>Password
            <input class="input" type="password" name="password" minlength="6" required>
        </label>
        <label>Konfirmasi password
            <input class="input" type="password" name="password_confirmation" minlength="6" required>
        </label>
        <button class="button" type="submit">Daftar</button>
        <p id="message" class="form-message"></p>
    </form>
    <p>Sudah punya akun? <a href="login.php">Masuk</a></p>
</section>
<script src="../assets/js/app.js"></script>
<script>authForm('register');</script>
<?php require __DIR__ . '/_footer.php'; ?>
