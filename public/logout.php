<?php
// Gunakan endpoint API auth untuk logout agar konsisten dengan penghapusan cookie
header('Location: ../api/auth.php?action=logout');
exit;
