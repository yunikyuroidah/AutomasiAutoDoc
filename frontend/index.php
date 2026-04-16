<?php
  $loginPath = './login.html';
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | AutoDoc Demo</title>
    <link rel="stylesheet" href="./dashboard.css" />
  </head>
  <body>
    <div class="dashboard-shell">
      <div class="dashboard-frame">
        <header class="dashboard-header">
          <div class="brand-block">
            <img src="../assets/logo.png" alt="Logo Dinas" />
            <div class="brand-text">
              <h1>
                <span
                  class="secret-trigger"
                  data-secret-login="<?= $loginPath ?>"
                  role="link"
                  tabindex="0"
                  aria-label="Masuk ke ruang autentikasi"
                >
                  AUTODOC
                </span>
                DEMO
              </h1>
              <p>Unit Administrasi Dokumen · Akses Internal</p>
            </div>
          </div>
          <div class="badge-carousel">
            <span class="badge-dot"></span>
            <span data-words='["Manajemen Dokumen","Template Otomatis","Arsip Terstruktur"]'>Manajemen Dokumen</span>
          </div>
        </header>

        <main class="dashboard-main">
          <section class="hero-panel">
            <p class="news-ticker">
              <span class="news-keyword">Workspace Demo</span>
              Lingkungan simulasi untuk otomasi dokumen, validasi formulir, dan pelacakan arsip.
            </p>
            <h2 class="hero-title">
              Portal <span class="hero-accent">AutoDoc</span> Demo
            </h2>
            <p class="hero-copy">
              Dirancang untuk pengujian alur dokumen: membuat draft, memvalidasi data, dan mengekspor berkas.
              Seluruh konten pada mode demo menggunakan data fiktif untuk kebutuhan pengembangan.
            </p>
          </section>
        </main>
      </div>
    </div>

    <script>
      const badge = document.querySelector('[data-words]');
      if (badge) {
        const words = JSON.parse(badge.dataset.words || '[]');
        let index = 0;
        setInterval(() => {
          index = (index + 1) % words.length;
          badge.textContent = words[index];
        }, 2600);
      }

      const secretTrigger = document.querySelector('[data-secret-login]');
      if (secretTrigger) {
        const redirectToLogin = () => {
          const target = secretTrigger.dataset.secretLogin;
          if (target) {
            window.location.href = target;
          }
        };

        secretTrigger.addEventListener('click', redirectToLogin);
        secretTrigger.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            redirectToLogin();
          }
        });
      }
    </script>
  </body>
</html>
