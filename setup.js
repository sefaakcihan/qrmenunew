
(() => {
  /* -----------------------------------------------------
   *  SETUP WIZARD CLASS
   * --------------------------------------------------- */
  class SetupWizard {
    constructor() {
      this.currentStep = 1;
      this.totalSteps  = 5;

      this.setupData   = {};   // DB, admin, restoran …
      this.themes      = [];

      this.init();
    }

    /* ----------  INITIALISATION  ---------- */
    init() {
      this.cacheDom();
      this.setupEventListeners();
      this.startSystemCheck();
      this.loadThemes();
    }

    cacheDom() {
      this.btnNext = {
        1: document.getElementById('next-step-1'),
        2: document.getElementById('next-step-2'),
        3: document.getElementById('next-step-3'),
        4: document.getElementById('next-step-4')
      };
      this.btnPrev = {
        2: document.getElementById('prev-step-2'),
        3: document.getElementById('prev-step-3'),
        4: document.getElementById('prev-step-4'),
        5: document.getElementById('prev-step-5')
      };

      this.btnTestDB  = document.getElementById('test-db');
      this.btnInstall = document.getElementById('start-installation');
    }

    setupEventListeners() {
      /* — Adım ileri / geri — */
      Object.values(this.btnNext).forEach(btn =>
        btn?.addEventListener('click', () => this.nextStep())
      );
      Object.values(this.btnPrev).forEach(btn =>
        btn?.addEventListener('click', () => this.prevStep())
      );

      /* — Veritabanı testi — */
      this.btnTestDB?.addEventListener('click', () => this.testDatabaseConnection());

      /* — Kurulumu başlat — */
      this.btnInstall?.addEventListener('click', () => this.startInstallation());

      /* — Canlı validasyonlar — */
      document.getElementById('admin-form')?.addEventListener('input', () => {
        this.toggleButton(this.btnNext[3], this.validateAdminForm());
      });

      document.getElementById('database-form')?.addEventListener('input', () => {
        this.toggleButton(this.btnTestDB, this.validateDatabaseForm());
      });

      document
        .querySelector('input[name="admin_password"]')
        ?.addEventListener('input', () => this.validatePassword());
    }

    /* ----------  YARDIMCI METOT ---------- */
    toggleButton(btn, enabled) {
      if (!btn) return;
      btn.disabled = !enabled;
      btn.classList.toggle('bg-gray-400', !enabled);
      btn.classList.toggle('cursor-not-allowed', !enabled);
      btn.classList.toggle('bg-primary', enabled);
      btn.classList.toggle('hover:bg-opacity-90', enabled);
    }

    /* ----------  1. SİSTEM KONTROLÜ ---------- */
    async startSystemCheck() {
      const checks = [
        { label: 'PHP Versiyonu (8.0+)',        key: 'php_version'      },
        { label: 'MySQL/PDO Extension',         key: 'pdo_extension'    },
        { label: 'GD Extension (Resim işleme)', key: 'gd_extension'     },
        { label: 'Uploads Dizini Yazılabilir',  key: 'uploads_writable' },
        { label: 'Logs Dizini Yazılabilir',     key: 'logs_writable'    }
      ];

      let allPassed = true;

      for (let i = 0; i < checks.length; i++) {
        const { key }  = checks[i];
        const item     = document.querySelectorAll('.check-item')[i];
        const icon     = item.querySelector('.check-icon');
        const resultEl = item.querySelector('.check-result');

        try {
          const res = await this.makeRequest('setup_check.php', {
            action: 'system_check',
            check : key
          });
          if (!res.success) throw new Error(res.message);

          icon.textContent = '✓';
          icon.className   = 'check-icon w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center mr-3';
          resultEl.textContent = res.message || 'Geçti';
          resultEl.className   = 'check-result text-sm text-green-600';
        } catch (err) {
          icon.textContent = '✗';
          icon.className   = 'check-icon w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center mr-3';
          resultEl.textContent = err.message || 'Başarısız';
          resultEl.className   = 'check-result text-sm text-red-600';
          allPassed = false;
        }

        await new Promise(r => setTimeout(r, 300)); // ufak animasyon
      }

      this.toggleButton(this.btnNext[1], allPassed);
    }

    /* ----------  2. VERİTABANI TESTİ ---------- */
    validateDatabaseForm() {
      const fd = new FormData(document.getElementById('database-form'));
      return ['db_host', 'db_name', 'db_user'].every(f => fd.get(f)?.trim());
    }

    async testDatabaseConnection() {
      if (!this.validateDatabaseForm()) return;

      const fd      = new FormData(document.getElementById('database-form'));
      const box     = document.getElementById('db-test-result');
      const originalLabel = this.btnTestDB.textContent;

      this.btnTestDB.disabled = true;
      this.btnTestDB.innerHTML =
        '<div class="spinner mr-2"></div>Test ediliyor...';

      try {
        const res = await this.makeRequest('setup_check.php', {
          action : 'test_database',
          db_host: fd.get('db_host'),
          db_port: fd.get('db_port'),
          db_name: fd.get('db_name'),
          db_user: fd.get('db_user'),
          db_pass: fd.get('db_pass')
        });
        if (!res.success) throw new Error(res.message);

        box.className =
          'mt-4 p-4 rounded-lg bg-green-50 border border-green-200';
        box.innerHTML = `
          <div class="flex">
            <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <div>
              <h3 class="text-sm font-medium text-green-800">Bağlantı Başarılı</h3>
              <p class="text-sm text-green-700 mt-1">${res.message}</p>
            </div>
          </div>
        `;

        this.setupData.database = {
          host: fd.get('db_host'),
          port: fd.get('db_port'),
          name: fd.get('db_name'),
          user: fd.get('db_user'),
          pass: fd.get('db_pass')
        };

        this.toggleButton(this.btnNext[2], true);
      } catch (err) {
        box.className =
          'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
        box.innerHTML = `
          <div class="flex">
            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
              <h3 class="text-sm font-medium text-red-800">Bağlantı Başarısız</h3>
              <p class="text-sm text-red-700 mt-1">${err.message}</p>
            </div>
          </div>
        `;
        this.toggleButton(this.btnNext[2], false);
      } finally {
        box.classList.remove('hidden');
        this.btnTestDB.disabled = false;
        this.btnTestDB.textContent = originalLabel;
      }
    }

    /* ----------  3. ADMİN FORMU ---------- */
    validatePassword() {
      const pwd = document.querySelector('input[name="admin_password"]')?.value || '';
      const ok  = {
        length   : pwd.length >= 8,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number   : /[0-9]/.test(pwd),
        special  : /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(pwd)
      };

      for (const [k, v] of Object.entries(ok)) {
        const el = document.querySelector(`[data-req="${k}"]`);
        if (el) {
          el.classList.toggle('text-green-600', v);
          el.classList.toggle('text-gray-500', !v);
        }
      }
      return Object.values(ok).every(Boolean);
    }

    validateAdminForm() {
      const fd = new FormData(document.getElementById('admin-form'));

      const valid =
        fd.get('admin_username')?.trim().match(/^[a-zA-Z0-9_]{3,50}$/) &&
        fd.get('admin_email')?.trim().match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/) &&
        fd.get('admin_fullname')?.trim().length >= 2 &&
        this.validatePassword() &&
        fd.get('admin_password') === fd.get('admin_password_confirm');

      return !!valid;
    }

    /* ----------  4. TEMA SEÇİMİ ---------- */
    async loadThemes() {
      try {
        const res = await this.makeRequest('setup_check.php', { action: 'get_themes' });
        if (res.success) {
          this.themes = res.data;
        } else {
          throw new Error(res.message);
        }
      } catch {
        this.themes = [
          { id: 1, name: 'Classic Restaurant', slug: 'classic',        primary_color: '#C8102E' },
          { id: 2, name: 'Modern Minimalist',  slug: 'modern',         primary_color: '#667EEA' },
          { id: 3, name: 'Dark Elegance',      slug: 'dark-elegance',  primary_color: '#D4AF37' },
          { id: 4, name: 'Colorful Cafe',      slug: 'colorful',       primary_color: '#FF6B6B' }
        ];
      } finally {
        this.renderThemes();
      }
    }

    renderThemes() {
      const wrap = document.getElementById('theme-selection');
      if (!wrap) return;

      wrap.innerHTML = this.themes
        .map(
          (t, i) => `
        <label class="theme-option cursor-pointer">
          <input type="radio" name="selected_theme" value="${t.id}" class="sr-only" ${i === 0 ? 'checked' : ''}>
          <div class="theme-card border-2 ${
            i === 0 ? 'border-primary bg-blue-50' : 'border-gray-300'
          } rounded-lg p-3 hover:border-primary transition-colors">
            <div class="w-full h-12 rounded mb-2" style="background:${t.primary_color}"></div>
            <div class="text-xs font-medium text-center">${t.name}</div>
          </div>
        </label>`
        )
        .join('');

      wrap.querySelectorAll('input[name="selected_theme"]').forEach(input =>
        input.addEventListener('change', e => {
          wrap.querySelectorAll('.theme-card').forEach(card => {
            card.classList.remove('border-primary', 'bg-blue-50');
            card.classList.add('border-gray-300');
          });
          const card = e.target.closest('label').querySelector('.theme-card');
          card.classList.remove('border-gray-300');
          card.classList.add('border-primary', 'bg-blue-50');
        })
      );
    }

    /* ----------  ADIM GEÇİŞLERİ  ---------- */
    validateCurrentStep() {
      switch (this.currentStep) {
        case 1:
          return !this.btnNext[1]?.disabled;
        case 2:
          return this.validateDatabaseForm() && !!this.setupData.database;
        case 3:
          return this.validateAdminForm();
        case 4:
          return (
            document
              .querySelector('input[name="restaurant_name"]')
              ?.value.trim().length > 0
          );
        default:
          return true;
      }
    }

    nextStep() {
      if (this.currentStep >= this.totalSteps) return;
      if (!this.validateCurrentStep()) return;

      document.getElementById(`step-${this.currentStep}`).classList.add('hidden');
      this.currentStep += 1;
      document.getElementById(`step-${this.currentStep}`).classList.remove('hidden');

      if (this.currentStep === 4) this.loadRestaurantForm();

      this.updateProgress();
    }

    prevStep() {
      if (this.currentStep <= 1) return;
      document.getElementById(`step-${this.currentStep}`).classList.add('hidden');
      this.currentStep -= 1;
      document.getElementById(`step-${this.currentStep}`).classList.remove('hidden');
      this.updateProgress();
    }

    updateProgress() {
      for (let i = 1; i <= this.totalSteps; i++) {
        const dot = document.querySelector(`[data-step="${i}"]`);
        if (!dot) continue;

        dot.className =
          'step flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold';

        if (i < this.currentStep) {
          dot.classList.add('step-completed');
          dot.textContent = '✓';
        } else if (i === this.currentStep) {
          dot.classList.add('step-active');
          dot.textContent = i;
        } else {
          dot.classList.add('step-inactive');
          dot.textContent = i;
        }
      }
    }

    /* ----------  KURULUM  ---------- */
    loadRestaurantForm() {
      const nameInput = document.querySelector('input[name="restaurant_name"]');
      if (nameInput && !nameInput.value.trim()) nameInput.value = 'Lezzet Restaurant';
    }

    collectAllData() {
      /* DB zaten store edildi */
      const admin = new FormData(document.getElementById('admin-form'));
      this.setupData.admin = {
        username: admin.get('admin_username'),
        email   : admin.get('admin_email'),
        fullname: admin.get('admin_fullname'),
        password: admin.get('admin_password')
      };

      const rest = new FormData(document.getElementById('restaurant-form'));
      this.setupData.restaurant = {
        name       : rest.get('restaurant_name'),
        phone      : rest.get('restaurant_phone'),
        email      : rest.get('restaurant_email'),
        address    : rest.get('restaurant_address'),
        description: rest.get('restaurant_description'),
        theme_id   : rest.get('selected_theme'),
        currency   : rest.get('currency'),
        language   : rest.get('language')
      };
    }

    async startInstallation() {
      this.collectAllData();
      document.getElementById('prev-step-5')?.classList.add('hidden');
      this.btnInstall?.classList.add('hidden');

      const tasks = [
        { action: 'create_database',     icon: 0 },
        { action: 'create_tables',       icon: 1 },
        { action: 'insert_sample_data',  icon: 2 },
        { action: 'create_admin',        icon: 3 },
        { action: 'write_config',        icon: 4 }
      ];

      for (const t of tasks) {
        const icon = document.querySelectorAll('.install-icon')[t.icon];
        try {
          const res = await this.makeRequest('setup_install.php', {
            action    : t.action,
            setup_data: this.setupData
          });
          if (!res.success) throw new Error(res.message);

          icon.textContent = '✓';
          icon.className =
            'install-icon w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center mr-3';
        } catch (err) {
          icon.textContent = '✗';
          icon.className =
            'install-icon w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center mr-3';
          this.showInstallationError(err.message);
          return;
        }
        await new Promise(r => setTimeout(r, 500));
      }

      document.getElementById('installation-progress')?.classList.add('hidden');
      document.getElementById('installation-success')?.classList.remove('hidden');
    }

    showInstallationError(msg) {
      const box = document.getElementById('installation-progress');
      box.innerHTML = `
        <div class="text-center py-8">
          <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Kurulum Başarısız</h3>
          <p class="text-red-600 mb-4">${msg}</p>
          <button onclick="location.reload()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">
            Tekrar Dene
          </button>
        </div>`;
    }

    /* ----------  ORTAK FETCH  ---------- */
    async makeRequest(url, data) {
      const res = await fetch(url, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify(data)
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    }
  }

  /* -----------------------------------------------------
   *  SCRIPT BOOTSTRAP
   * --------------------------------------------------- */
  document.addEventListener('DOMContentLoaded', () => {
    window.setupWizard = new SetupWizard();
  });

  window.addEventListener('beforeunload', e => {
    if (window.setupWizard?.currentStep > 1) {
      e.preventDefault();
      e.returnValue =
        'Kurulum devam ediyor. Sayfayı kapatmak istediğinizden emin misiniz?';
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
      const step   = document.querySelector('.setup-step:not(.hidden)');
      const nextBtn = step?.querySelector('[id^="next-step"]');
      if (nextBtn && !nextBtn.disabled) nextBtn.click();
    }
  });
})();
