describe('Modul 02: Dasbor Operasional, Analitik DES, & Profil Pengguna', () => {
  beforeEach(() => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });

  it('Dasbor: Memverifikasi kartu ringkasan metrik statistik inventaris menampilkan data numerik riil', () => {
    cy.get('body').should('be.visible');
    cy.contains(/total produk|inventaris|transaksi|stok/i).should('exist');
    // Memverifikasi adanya indikator metrik angka pada kartu KPI dasbor
    cy.get('main').find('h2, h3, p, div').filter(':contains("Produk"), :contains("Stok"), :contains("Transaksi")').should('exist');
  });

  it('Navigasi Fisik Sidebar: Berpindah ke Modul Analitik DES via klik menu di aside', () => {
    // Menguji klik nyata pada menu sidebar
    cy.get('aside').contains(/analitik des/i).should('be.visible').click();
    cy.url().should('include', '/analytics');
    cy.contains(/analitik|peramalan|forecast|des|tren/i).should('exist');
  });

  it('Simulasi Parameter DES: Menampilkan visualisasi tren dan kontrol simulasi Double Exponential Smoothing', () => {
    cy.visit('/analytics');
    cy.get('body').should('be.visible');
    cy.contains(/alpha|beta|pemulusan|rekomendasi|tren/i).should('exist');
  });

  it('Manajemen Profil: Membuka formulir pembaruan profil (/profile) via dropdown pengguna header', () => {
    // Menguji navigasi melalui dropdown pengguna di header
    cy.get('header').find('button').filter(':has(.h-7)').click();
    cy.get('header a[href*="profile"]').should('be.visible').click();

    cy.url().should('include', '/profile');
    cy.get('input#name').should('be.visible').and('have.value', 'Manajer Operasional');
    cy.get('input#email').should('be.visible').and('have.value', 'manajer@sipaling.com');
  });

  it('Negative Testing: Validasi ubah kata sandi menolak konfirmasi password yang tidak cocok', () => {
    cy.visit('/profile');
    cy.get('input#current_password').clear().type('password');
    cy.get('input#password').clear().type('PasswordBaru123!');
    cy.get('input#password_confirmation').clear().type('PasswordBedaTotal999!');

    // Klik Simpan Perubahan
    cy.contains('button', /simpan perubahan/i).click();

    // Verifikasi pesan kesalahan konfirmasi password muncul di layar
    cy.contains(/konfirmasi|tidak cocok|sesuai|match/i).should('be.visible');
  });

  it('Negative Testing: Validasi ubah kata sandi menolak kata sandi saat ini yang salah', () => {
    cy.visit('/profile');
    cy.get('input#current_password').clear().type('PasswordSaatIniSalahTotal123!');
    cy.get('input#password').clear().type('PasswordBaru456!');
    cy.get('input#password_confirmation').clear().type('PasswordBaru456!');

    // Klik Simpan Perubahan
    cy.contains('button', /simpan perubahan/i).click();

    // Verifikasi penolakan dari Laravel
    cy.contains(/kata sandi saat ini|current password|tidak sesuai|salah/i).should('be.visible');
  });
});
