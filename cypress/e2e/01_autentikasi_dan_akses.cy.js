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
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
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

  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 1 - ALUR PEMULIHAN KATA SANDI & 2-STEP FA OTP (SULTAN)
  | SKPL / PMPL: TC_AUT_013 s/d TC_AUT_018
  | Trello Task: [M1-BE-04], [M1-LC-04], [M1-LC-05]
  |--------------------------------------------------------------------------
  */

  it('TC_AUT_013: Alur Pemulihan Sandi: Membuka formulir lupa kata sandi dari tautan login', () => {
    cy.visit('/login');
    cy.contains(/lupa kata sandi/i).should('be.visible').click();
    cy.url().should('include', '/forgot-password');
    cy.contains(/pemulihan kata sandi/i).should('be.visible');
    cy.get('input#email').should('be.visible');
    cy.contains('button', /lanjut ke verifikasi/i).should('be.visible');
  });

  it('TC_AUT_014: Negative Testing: Validasi form lupa sandi menolak email tidak terdaftar', () => {
    cy.visit('/forgot-password');
    cy.get('input#email').clear().type('akun.palsu.tidak.ada@sipaling.com');
    cy.contains('button', /lanjut ke verifikasi/i).click();

    // Memverifikasi penolakan validasi dari PasswordResetLinkController
    cy.contains(/tidak terdaftar|not registered/i).should('be.visible');
    cy.url().should('include', '/forgot-password');
  });

  it('TC_AUT_015: Proteksi Anti-Spam: Verifikasi cooldown timer tombol kirim ulang OTP 60 detik', () => {
    // Akses halaman verifikasi OTP dengan parameter email terdaftar
    cy.visit('/verify-otp?email=manajer@sipaling.com');
    cy.url().should('include', '/verify-otp');
    cy.contains(/masukkan kode verifikasi/i).should('be.visible');
    cy.contains('manajer@sipaling.com').should('be.visible');

    // Verifikasi timer cooldown aktif (00:XX)
    cy.contains(/kirim ulang kode dalam/i).should('be.visible');
    cy.contains(/00:\d{2}/).should('be.visible');

    // Tombol kirim ulang harus berstatus disabled selama timer berjalan
    cy.contains('button', /kirim ulang/i).should('be.disabled');
  });

  it('TC_AUT_016: Negative Testing 2FA: Verifikasi penolakan sistem saat kode OTP salah dimasukkan', () => {
    cy.visit('/verify-otp?email=manajer@sipaling.com');

    // Memasukkan kode OTP 6 digit acak yang salah
    cy.get('input').filter('.text-center, [data-pin-input-input]').each(($el, index) => {
      cy.wrap($el).clear().type(String(index));
    });

    // Klik tombol Verifikasi
    cy.contains('button', /verifikasi/i).click();

    // Memverifikasi penolakan error dari TwoFactorResetService
    cy.contains(/tidak cocok|tidak valid|salah|kedaluwarsa|invalid/i).should('be.visible');
  });

  it('TC_AUT_017: Proteksi Brute-Force & Validasi Input OTP: Mencegah submit kode tidak lengkap', () => {
    cy.visit('/verify-otp?email=manajer@sipaling.com');

    // Coba klik tombol submit tanpa mengisi seluruh 6 digit
    cy.contains('button', /verifikasi/i).click();

    // Memverifikasi pesan validasi kode keamanan harus lengkap 6 digit
    cy.contains(/6 digit kode keamanan|lengkap/i).should('be.visible');
  });

  it('TC_AUT_018: Navigasi Pemulihan: Tautan pengembalian rute ke lupa sandi dan halaman login', () => {
    cy.visit('/verify-otp?email=manajer@sipaling.com');

    // Menguji tombol Ubah Alamat Email mengembalikan pengguna ke step 1
    cy.contains(/ubah alamat email/i).should('be.visible').click();
    cy.url().should('include', '/forgot-password');

    // Menguji tautan Kembali ke Halaman Masuk
    cy.contains(/kembali ke halaman masuk/i).should('be.visible').click();
    cy.url().should('include', '/login');
  });
  
/*TC_AUT_011 dan TC_AUT_012*/

describe('Pengujian Najwa - Validasi Status Akun Nonaktif', () => {
  const email = 'staf@sipaling.com';
  const password = 'password';
  const pesanNonaktif =
    'Akun Anda non-aktif. Hubungi Administrator.';

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
    cy.visit('/login');
  });
it('TC_AUT_011 - Verifikasi toast hilang otomatis setelah 5 detik', () => {
    loginAkunNonaktif();

    cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
        .should('be.visible');

    cy.wait(5200);

    cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
        .should('not.exist');
});
  function loginAkunNonaktif() {
    cy.get('#email').clear().type(email);
    cy.get('#password').clear().type(password);
    cy.get('button[type="submit"]').click();

    cy.contains(pesanNonaktif).should('be.visible');
    cy.url().should('include', '/login');
  }

it('TC_AUT_011 - Login ditolak untuk akun staf nonaktif', () => {
    loginAkunNonaktif();

cy.get('div.fixed.bottom-6.right-6')
  .filter(':visible')
  .filter((index, element) =>
    element.innerText.includes(pesanNonaktif)
  )
  .first()
  .should('be.visible')
  .within(() => {
    cy.contains(pesanNonaktif).should('be.visible');
    cy.get('button').should('be.visible');
  });

    // Memastikan pesan tidak muncul ganda
    cy.get('p').filter((index, element) =>
      element.innerText.includes(pesanNonaktif)
    ).should('have.length', 1);

    // Menguji tombol tutup toast
cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
  .filter(':visible')
  .first()
  .find('button')
  .click();

cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
  .should('not.exist');

    // Memastikan pengguna tidak dapat masuk dashboard
    cy.visit('/dashboard');
    cy.url().should('include', '/login');
  });

 it('TC_AUT_012 - Toast akun nonaktif pada mobile, mode terang dan gelap', () => {
    cy.viewport(375, 812);

    // Mengaktifkan mode gelap
   cy.get('button[title="Ganti ke mode gelap"]').click();
    cy.get('html').should('have.class', 'dark');

    loginAkunNonaktif();

    cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
  .filter(':visible')
  .first()
  .should(($toast) => {
    const rect = $toast[0].getBoundingClientRect();

    expect(rect.left).to.be.at.least(0);
    expect(rect.right).to.be.at.most(375);
    expect(rect.top).to.be.at.least(0);
    expect(rect.bottom).to.be.at.most(812);
  });

    // Menutup toast dan kembali ke login
  cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
  .filter(':visible')
  .first()
  .find('button')
  .click();
    cy.visit('/login');

    // Mengubah ke mode terang
    cy.get('button[title="Ganti ke mode terang"]').click();
    cy.get('html').should('not.have.class', 'dark');

    loginAkunNonaktif();

cy.contains('div.fixed.bottom-6.right-6', pesanNonaktif)
  .filter(':visible')
  .should('be.visible');
    cy.contains(pesanNonaktif).should('be.visible');

// Memastikan tidak ada scroll horizontal
cy.document().then((doc) => {
  expect(doc.documentElement.scrollWidth)
    .to.be.at.most(375);
});
  
});
});
/* TC_PRF_003 - Validasi Informasi Kartu Identitas Profil */

describe('Pengujian Najwa - Kartu Identitas Profil', () => {

it('TC_PRF_003 - Memastikan informasi identitas akun Manajer Operasional tampil dengan benar', () => {

    // Login sebagai Manajer Operasional
    cy.visit('/login');
    cy.get('#email').clear().type('manajer@sipaling.com');
    cy.get('#password').clear().type('password');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Membuka halaman profil
    cy.visit('/profile');
    cy.url().should('include', '/profile');

    // Memastikan identitas pengguna tampil
    cy.contains('h3', 'Manajer Operasional').should('be.visible');
    cy.contains('Akun Aktif').should('be.visible');

    // Memastikan informasi kartu identitas
    cy.contains('ID Pengguna').should('be.visible');
    cy.contains('ID-01A0FFA1').should('be.visible');

    cy.contains('Email').should('be.visible');
    cy.contains('manajer@sipaling.com').should('be.visible');

    cy.contains('Telepon').should('be.visible');
    cy.contains('081200000002').should('be.visible');

    cy.contains('Bergabung Sejak').should('be.visible');
    cy.contains('3 Oktober 2026').should('be.visible');

    // Memastikan keterangan verifikasi identitas tampil
    cy.contains('Identitas terverifikasi sistem SIPALING')
      .should('be.visible');

  });

/* TC_PRF_004 - Kartu Responsif pada Mode Terang dan Gelap */

it('TC_PRF_004 - Memastikan kartu identitas responsif pada desktop, mobile, mode terang dan gelap', () => {

  // Login sebagai Manajer Operasional
  cy.visit('/login');
  cy.get('#email').clear().type('manajer@sipaling.com');
  cy.get('#password').clear().type('password');
  cy.get('button[type="submit"]').click();

  cy.url().should('include', '/dashboard');

  // Membuka halaman profil
  cy.visit('/profile');
  cy.url().should('include', '/profile');

  // Memastikan kartu identitas tampil
  cy.contains('h3', 'Manajer Operasional').should('be.visible');

  // Pengujian desktop mode terang
  cy.viewport(1280, 800);

  cy.get('html').should('not.have.class', 'dark');
  cy.contains('Identitas terverifikasi sistem SIPALING')
    .should('be.visible');

  // Pengujian desktop mode gelap
  cy.get('button[title="Ganti Mode Tampilan"]').click();

  cy.get('html').should('have.class', 'dark');
  cy.contains('Identitas terverifikasi sistem SIPALING')
    .should('be.visible');

  // Pengujian mobile mode gelap
  cy.viewport(375, 812);

  cy.get('html').should('have.class', 'dark');

  cy.contains('h3', 'Manajer Operasional').should('be.visible');
  cy.contains('manajer@sipaling.com').should('be.visible');
  cy.contains('081200000002').should('be.visible');

  // Memastikan tidak ada scroll horizontal pada mobile
  cy.document().then((doc) => {
    expect(doc.documentElement.scrollWidth).to.be.at.most(375);
  });

 // Pengujian mobile mode terang
  cy.get('button[title="Ganti Mode Tampilan"]').click();

  cy.get('html').should('not.have.class', 'dark');

  cy.contains('h3', 'Manajer Operasional').should('be.visible');
  cy.contains('Identitas terverifikasi sistem SIPALING')
    .should('be.visible');

  // Memastikan tidak ada scroll horizontal pada mobile mode terang
  cy.document().then((doc) => {
    expect(doc.documentElement.scrollWidth).to.be.at.most(375);
  });

});
});
});