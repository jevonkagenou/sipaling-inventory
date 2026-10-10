describe('Modul 01: Autentikasi, Hak Akses Multi-Peran, & Keamanan Rute', () => {
  it('Memverifikasi Landing Page memuat identitas SIPALING dan navigasi masuk via klik tombol Login', () => {
    cy.visit('/');
    cy.contains(/sipaling/i).should('be.visible');
    // Klik fisik tautan tombol Login di landing page
    cy.get('a[href*="login"]').first().click();
    cy.url().should('include', '/login');
    cy.get('input#email').should('be.visible');
  });

  it('Keamanan: Akses langsung ke rute pendaftaran publik (/register) wajib dicegat dan dialihkan ke /login', () => {
    cy.visit('/register', { failOnStatusCode: false });
    cy.url().should('include', '/login');
  });

  it('Keamanan Tamu: Akses tanpa autentikasi ke halaman privat dialihkan otomatis ke /login', () => {
    cy.clearCookies();
    cy.clearLocalStorage();
    cy.visit('/dashboard', { failOnStatusCode: false });
    cy.url().should('include', '/login');

    cy.visit('/inventory', { failOnStatusCode: false });
    cy.url().should('include', '/login');

    cy.visit('/transactions', { failOnStatusCode: false });
    cy.url().should('include', '/login');
  });

  it('Negative Testing: Input email tanpa karakter "@" dicegat validasi HTML5 dan form gagal submit', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('felixsipaling.com');
    cy.get('input#password').clear().type('password123');

    // Validasi bahwa browser menandai email tidak valid (typeMismatch)
    cy.get('input#email').then(($input) => {
      expect($input[0].checkValidity()).to.be.false;
      expect($input[0].validity.typeMismatch).to.be.true;
    });

    // Menekan tombol submit: form dicegat oleh browser client-side
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/login');
  });

  it('Negative Testing: Upaya masuk dengan akun tidak terdaftar ditolak oleh Laravel dengan pesan error', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('hacker.palsu@sipaling.com');
    cy.get('input#password').clear().type('password_salah_total');
    cy.get('button[type="submit"]').click();

    // Verifikasi sistem menolak dan tetap di /login dengan pesan kesalahan kredensial
    cy.url().should('include', '/login');
    cy.contains(/credentials|match|kredensial|tidak cocok|tidak sesuai|salah/i).should('be.visible');
  });

  it('Interaktivitas UI: Pengalihan visibilitas kata sandi (Toggle Show/Hide Password)', () => {
    cy.visit('/login');
    cy.get('input#password').should('have.attr', 'type', 'password');
    cy.get('input#password').clear().type('RahasiaKu123');

    // Klik tombol toggle ikon mata
    cy.get('input#password').parent().find('button').click();
    cy.get('input#password').should('have.attr', 'type', 'text');

    // Klik sekali lagi untuk mengembalikan ke mode tersembunyi
    cy.get('input#password').parent().find('button').click();
    cy.get('input#password').should('have.attr', 'type', 'password');
  });

  it('Autentikasi Multi-Peran: Masuk sebagai Staf Gudang & Navigasi Fisik Sidebar (Mutasi Stok)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Klik fisik menu Mutasi Stok di aside sidebar
    cy.get('aside').contains(/mutasi stok/i).should('be.visible').click();
    cy.url().should('include', '/transactions');

    // Verifikasi RBAC: Staf Gudang tidak memiliki menu privat Jejak Audit Log & Manajemen Pengguna di sidebar
    cy.get('aside').contains(/jejak audit log/i).should('not.exist');
    cy.get('aside').contains(/manajemen pengguna/i).should('not.exist');
  });

  it('Autentikasi Multi-Peran: Masuk sebagai Auditor Internal & Navigasi Fisik Sidebar (Audit Forensik)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('luxoqwasery@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Klik fisik menu Jejak Audit Log di aside sidebar
    cy.get('aside').contains(/jejak audit log/i).should('be.visible').click();
    cy.url().should('include', '/audit');
    cy.contains(/audit|log|forensik|riwayat/i).should('exist');
  });

  it('Autentikasi Multi-Peran: Masuk sebagai Komisaris & Navigasi Fisik Sidebar (Approval Restock)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Klik fisik menu Approval Restock di aside sidebar
    cy.get('aside').contains(/approval restock/i).should('be.visible').click();
    cy.url().should('include', '/restock');
  });

  it('Preservasi Sesi & Logout Riil via Dropdown Pengguna', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Buka dropdown profil pengguna di header kanan atas
    cy.get('header').find('button').filter(':has(.h-7.w-7), :has(svg)').last().click({ force: true });
    cy.contains(/keluar/i).should('be.visible').click();

    // Verifikasi pengguna ter-logout dan kembali ke /login
    cy.url().should('include', '/login');
  });
});
