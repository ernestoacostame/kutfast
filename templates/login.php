<div class="login-wrap">
  <div class="login-brand-icon fade">
    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent)">
      <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
      <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
      <line x1="12" y1="19" x2="12" y2="22"/>
    </svg>
  </div>
  <div class="login-mark fade">KUT<span>FAST</span></div>
  <p class="login-sub fade">Transmisión Rápida &middot; KutStudio</p>
  <div class="login-box fade">
    <form method="POST" action="index.php?page=login" class="login-card">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="field">
        <label for="user">Usuario o Email</label>
        <input type="text" id="user" name="user" autofocus placeholder="tu@email.com" required autocomplete="username">
      </div>
      <div class="field">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.2rem">Iniciar Sesión</button>
    </form>
  </div>
</div>
