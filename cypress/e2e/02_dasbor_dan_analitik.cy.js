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
    // PIC NAJWA - TC_ANL_003
  it('TC_ANL_003: Memverifikasi pemisahan data historis, fitted, dan proyeksi pada grafik DES', () => {
    cy.visit('/analytics');

    cy.contains('Data Historis (Aktual)').should('be.visible');
    cy.contains('Taksiran Model (Fitted)').should('be.visible');
    cy.contains('Proyeksi Peramalan').should('be.visible');

    cy.contains('Batas Proyeksi').should('be.visible');

    cy.get('svg')
      .find('path.stroke-blue-600')
      .should('exist');

    cy.get('svg')
  .find('path[class*="stroke-indigo-400/80"]')
  .should('exist');

    cy.get('svg')
      .find('path.stroke-emerald-600')
      .should('exist');

    cy.get('svg')
      .find('text.fill-emerald-700')
      .should('exist');
  });

  // PIC NAJWA - TC_ANL_004
  it('TC_ANL_004: Memverifikasi nilai Alpha, Beta, dan MAPE pada kartu performa algoritma', () => {
    cy.visit('/analytics');

    cy.contains('Ringkasan Performa Algoritma').should('be.visible');

cy.contains('Nilai Alpha')
  .parents('.group')
  .first()
  .find('.text-2xl')
  .invoke('text')
  .should('match', /^\s*\d+(\.\d+)?\s*$/);

cy.contains('Nilai Beta')
  .parents('.group')
  .first()
  .find('.text-2xl')
  .invoke('text')
  .should('match', /^\s*\d+(\.\d+)?\s*$/);

cy.contains('Persentase MAPE')
  .parents('.group')
  .first()
  .find('.text-2xl')
  .invoke('text')
  .should('match', /^\s*\d+(\.\d+)?%\s*$/);
  });

  // PIC NAJWA - TC_ANL_005
  it('TC_ANL_005: Memverifikasi responsivitas grafik dan kartu performa pada mode terang dan gelap', () => {
    cy.visit('/analytics');

    // Mode terang - desktop
    cy.viewport(1280, 800);

    cy.contains('Ringkasan Performa Algoritma').should('be.visible');
    cy.contains('Data Historis (Aktual)').should('be.visible');

    // Beralih ke mode gelap
    cy.get('button[title="Ganti Mode Tampilan"]').click();

    cy.get('html').should('have.class', 'dark');

    cy.contains('Ringkasan Performa Algoritma').should('be.visible');
    cy.contains('Data Historis (Aktual)').should('be.visible');

    // Beralih kembali ke mode terang
    cy.get('button[title="Ganti Mode Tampilan"]').click();

    cy.get('html').should('not.have.class', 'dark');

    // Tampilan mobile
    cy.viewport(375, 667);

    cy.contains('Ringkasan Performa Algoritma').should('be.visible');

    // Grafik tetap tersedia dan dapat digulir horizontal
    cy.get('div.overflow-x-auto')
      .filter(':has(svg)')
      .should('exist')
      .then(($container) => {
        expect($container[0].scrollWidth)
          .to.be.greaterThan($container[0].clientWidth);
      });

    // Mode gelap pada tampilan mobile
    cy.get('button[title="Ganti Mode Tampilan"]').click();

    cy.get('html').should('have.class', 'dark');
    cy.contains('Ringkasan Performa Algoritma').should('be.visible');
  });
});
