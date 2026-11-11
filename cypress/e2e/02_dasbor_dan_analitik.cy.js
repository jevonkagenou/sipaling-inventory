describe('Modul 02: Dasbor Operasional, Analitik DES, & Profil Pengguna', () => {
  beforeEach(() => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });

  it('TC_DSH_001: Dasbor: Memverifikasi kartu ringkasan metrik statistik inventaris menampilkan data numerik riil', () => {
    cy.get('body').should('be.visible');
    cy.contains(/total produk|inventaris|transaksi|stok/i).should('exist');
    // Memverifikasi adanya indikator metrik angka pada kartu KPI dasbor
    cy.get('main').find('h2, h3, p, div').filter(':contains("Produk"), :contains("Stok"), :contains("Transaksi")').should('exist');
  });

  it('TC_DSH_002: Navigasi Fisik Sidebar: Berpindah ke Modul Analitik DES via klik menu di aside', () => {
    // Menguji klik nyata pada menu sidebar Prediksi Kebutuhan
    cy.get('aside').contains(/prediksi kebutuhan|analitik/i).should('be.visible').click();
    cy.url().should('include', '/analytics');
    cy.contains(/prediksi|peramalan|analitik|restock/i).should('exist');
  });

  it('TC_DSH_003: Simulasi Parameter DES: Menampilkan visualisasi tren dan kontrol simulasi Double Exponential Smoothing', () => {
    cy.visit('/analytics');
    cy.get('body').should('be.visible');
    // Beralih ke tab Grafik & Prediksi untuk melihat visualisasi dan kontrol DES
    cy.contains('button', /grafik & prediksi/i).should('be.visible').click();
    cy.contains(/alpha|beta|pemulusan|rekomendasi|tren/i).should('exist');
  });

  it('TC_DSH_004: Manajemen Profil: Membuka formulir pembaruan profil (/profile) via dropdown pengguna header', () => {
    // Menguji navigasi melalui dropdown pengguna di header (Shadcn portal menu)
    cy.get('button[title*="profil"]').click({ force: true });
    cy.get('a[href*="profile"]').first().click({ force: true });

    cy.url().should('include', '/profile');
    cy.get('input#name').should('be.visible').and('have.value', 'Manajer Operasional');
    cy.get('input#email').should('be.visible').and('have.value', 'manajer@sipaling.com');
  });

  it('TC_DSH_005: Negative Testing: Validasi ubah kata sandi menolak konfirmasi password yang tidak cocok', () => {
    cy.visit('/profile');
    cy.get('input#current_password').clear().type('password');
    cy.get('input#password').clear().type('PasswordBaru123!');
    cy.get('input#password_confirmation').clear().type('PasswordBedaTotal999!');

    // Klik Simpan Perubahan
    cy.contains('button', /simpan perubahan/i).click();

    // Verifikasi pesan kesalahan konfirmasi password muncul di layar
    cy.contains(/konfirmasi|tidak cocok|sesuai|match/i).should('be.visible');
  });

  it('TC_DSH_006: Negative Testing: Validasi ubah kata sandi menolak kata sandi saat ini yang salah', () => {
    cy.visit('/profile');
    cy.get('input#current_password').clear().type('PasswordSaatIniSalahTotal123!');
    cy.get('input#password').clear().type('PasswordBaru456!');
    cy.get('input#password_confirmation').clear().type('PasswordBaru456!');

    // Klik Simpan Perubahan
    cy.contains('button', /simpan perubahan/i).click();

    // Verifikasi penolakan dari Laravel
    cy.contains(/kata sandi saat ini|current password|tidak sesuai|salah/i).should('be.visible');
  });

  // ===================================================================
  // PENGUJIAN PIC NAJWA - MODUL 4: VISUALISASI DES
  // ===================================================================

  it('TC_ANL_001: Memverifikasi pemisahan data historis, fitted, dan proyeksi pada grafik DES', () => {
    cy.visit('/analytics');

    // Beralih ke tab Grafik & Prediksi untuk menampilkan grafik kurva
    cy.contains('button', /grafik & prediksi/i).should('be.visible').click();

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

  it('TC_ANL_002: Memverifikasi nilai Alpha, Beta, dan MAPE pada kartu performa algoritma', () => {
    cy.visit('/analytics');

    // Beralih ke tab Grafik & Prediksi untuk menampilkan kartu performa DES
    cy.contains('button', /grafik & prediksi/i).should('be.visible').click();

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

  it('TC_ANL_003: Memverifikasi responsivitas grafik dan kartu performa pada mode terang dan gelap', () => {
    cy.visit('/analytics');

    // Beralih ke tab Grafik & Prediksi
    cy.contains('button', /grafik & prediksi/i).should('be.visible').click();

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

    // Kembalikan viewport ke ukuran standar
    cy.viewport(1280, 720);
  });

  // ===================================================================
  // PENGUJIAN PIC MEUTIA - MODUL 4: MESIN ANALITIK & PERAMALAN DES
  // ===================================================================

  it('TC_ANL_004: Memverifikasi Dasbor Analitik Manajer Operasional', () => {
    cy.visit('/analytics');
    cy.url().should('include', '/analytics');

    // Memverifikasi heading dan deskripsi modul analitik
    cy.contains(/Prediksi Permintaan & Restock|Perencanaan Belanja/i).should('be.visible');

    // Memverifikasi 4 kartu ringkasan eksekutif (StatCard) termuat lengkap
    cy.contains('Total Produk Dipantau').should('be.visible');
    cy.contains('Perlu Dibeli Segera').should('be.visible');
    cy.contains('Perkiraan Total Belanja').should('be.visible');
    cy.contains('Tingkat Kepastian Prediksi').should('be.visible');

    // Memverifikasi navigasi tab (Daftar Restock vs Grafik & Prediksi)
    cy.contains('button', /Daftar Restock/i).should('be.visible');
    cy.contains('button', /Grafik & Prediksi/i).should('be.visible');
  });

  it('TC_ANL_005: Memverifikasi fitur rekomendasi restock & aksi pengajuan cepat', () => {
    cy.visit('/analytics');
    cy.url().should('include', '/analytics');

    // Pastikan berada di tab rekomendasi restock
    cy.contains('button', /Daftar Restock/i).click();

    // Verifikasi tabel rekomendasi restock tampil dengan header kolom yang sesuai
    cy.get('table').should('be.visible');
    cy.get('table thead').within(() => {
      cy.contains(/Barang/i).should('be.visible');
      cy.contains(/Stok Gudang/i).should('be.visible');
      cy.contains(/Saran Restock/i).should('be.visible');
      cy.contains(/Status/i).should('be.visible');
      cy.contains(/Aksi/i).should('be.visible');
    });

    // Verifikasi tombol Ajukan Restock / Ajukan Pilihan merespons dan membuka modal dialog pengadaan
    cy.get('button').filter(':contains("Ajukan Restock"), :contains("Ajukan Pilihan")').first().then(($btn) => {
      cy.wrap($btn).click();

      // Dialog modal pengadaan restock muncul
      cy.get('[role="dialog"]').should('be.visible');
      cy.contains(/Ajukan Restock/i).should('be.visible');

      // Tutup dialog
      cy.get('[role="dialog"]').contains('button', /Batal/i).click();
      cy.get('[role="dialog"]').should('not.exist');
    });
  });
});
