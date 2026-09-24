<?php $title='Masuk | Urban Adventure'; require __DIR__ . '/_header.php'; ?>
<section class="auth-panel">
    <p class="eyebrow dark">URBAN ADVENTURE</p>
    <h1>Selamat datang kembali.</h1>
    <form id="authForm">
        <label>Email
            <input class="input" type="email" name="email" required>
        </label>
        <label>Password
            <input class="input" type="password" name="password" minlength="6" required>
        </label>
        <button class="button" type="submit">Masuk</button>
        <p id="message" class="form-message"></p>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
</section>
<script src="../assets/js/app.js"></script>
<script>authForm('login');</script>
<?php require __DIR__ . '/_footer.php'; ?>
