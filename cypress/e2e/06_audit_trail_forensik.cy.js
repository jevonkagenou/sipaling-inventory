describe('Modul 06: Audit Trail & Investigasi Forensik Log', () => {
  it('TC_ADT_001: Akses Auditor: Membuka modul Jejak Audit Forensik (/audit) via klik fisik sidebar', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Klik fisik menu Jejak Audit Log di sidebar aside
    cy.get('aside').contains(/jejak audit log/i).should('be.visible').click();
    cy.url().should('include', '/audit');
    cy.contains(/audit|log|aktivitas|forensik/i).should('exist');
  });

  it('TC_ADT_002: Integritas Log: Tabel audit menampilkan rekam jejak aktivitas pengguna dan timestamp append-only', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.get('body').should('be.visible');
    cy.contains(/log|riwayat|pengguna|waktu/i).should('exist');
    cy.get('table').should('exist');
  });


  // ===================================================================
  // TEST CASES PIC: MEUTIA (Modul 6: Audit Trail & Investigasi Forensik Log)
  // ===================================================================

  it('TC_ADT_003: Memverifikasi portal investigasi log khusus', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Buka rute portal audit log
    cy.visit('/audit');
    cy.url().should('include', '/audit');

    // Verifikasi judul dan deskripsi modul
    cy.contains('h1', /Jejak Rekam Audit|Audit Trail/i).should('be.visible');
    cy.contains(/Pencatatan terpusat dan bukti forensik digital/i).should('be.visible');

    // Verifikasi 4 StatCard metrik audit
    cy.contains('Total Rekam Jejak').should('be.visible');
    cy.contains('Mutasi & Inventaris').should('be.visible');
    cy.contains('Aktivitas Pengguna').should('be.visible');
    cy.contains('Log Prediksi Kebutuhan').should('be.visible');

    // Verifikasi tabel log riwayat append-only termuat dengan header lengkap
    cy.get('table').should('be.visible');
    cy.get('table thead').within(() => {
      cy.contains(/Waktu/i).should('be.visible');
      cy.contains(/Kategori/i).should('be.visible');
      cy.contains(/Aktivitas/i).should('be.visible');
      cy.contains(/Pengguna/i).should('be.visible');
      cy.contains(/Aksi/i).should('be.visible');
    });
  });

  it('TC_ADT_004: Memverifikasi fungsionalitas pencarian histori log multi-parameter', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.url().should('include', '/audit');

    // 1. Uji filter search box
    cy.get('input[placeholder*="Cari deskripsi, aktor, IP"]').clear().type('Pengguna');
    cy.wait(400); // debounce 350ms
    cy.url().should('include', 'search=Pengguna');

    // 2. Uji filter dropdown modul
    cy.get('select[aria-label="Filter Modul Sistem"]').select('users');
    cy.url().should('include', 'log_name=users');

    // 3. Uji filter event tab (misal: "Dibuat" / created)
    cy.contains('button', /Dibuat/i).click();
    cy.url().should('include', 'event=created');

    // 4. Uji tombol Reset Filter
    cy.contains('button', /Reset Filter/i).should('be.visible').click();
    cy.url().should('not.include', 'search=Pengguna');
    cy.url().should('not.include', 'log_name=users');
    cy.url().should('not.include', 'event=created');
  });

  it('TC_ADT_005: Memverifikasi fitur ekspor laporan log ke format CSV', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.url().should('include', '/audit');

    // Buka dropdown menu "Unduh Laporan"
    cy.contains('button', /Unduh Laporan/i).should('be.visible').click();
    cy.contains(/Pilih Format Unduhan/i).should('be.visible');

    // Dapatkan tautan ekspor CSV
    cy.contains('a', /Teks CSV \(\.csv\)/i)
      .should('be.visible')
      .and('have.attr', 'href')
      .then((href) => {
        expect(href).to.include('/audit/export');
        expect(href).to.include('format=csv');

        // Request berkas CSV untuk memverifikasi HTTP 200 dan format isi
        cy.request({
          url: href,
          encoding: 'utf-8',
        }).then((response) => {
          expect(response.status).to.eq(200);
          expect(response.headers['content-type']).to.include('text/csv');
          expect(response.headers['content-disposition']).to.include('.csv');
          // Memastikan header kolom ada di dalam berkas CSV
          expect(response.body).to.include('Waktu & Tanggal');
          expect(response.body).to.include('Kategori (Log Name)');
          expect(response.body).to.include('Deskripsi Aktivitas');
        });
      });
  });

  it('TC_ADT_006: Memverifikasi fitur ekspor laporan log ke dokumen PDF', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.url().should('include', '/audit');

    // Buka dropdown menu "Unduh Laporan"
    cy.contains('button', /Unduh Laporan/i).should('be.visible').click();
    cy.contains(/Pilih Format Unduhan/i).should('be.visible');

    // Dapatkan tautan ekspor PDF
    cy.contains('a', /Dokumen PDF \(\.pdf\)/i)
      .should('be.visible')
      .and('have.attr', 'href')
      .then((href) => {
        expect(href).to.include('/audit/export');
        expect(href).to.include('format=pdf');

        // Request berkas PDF untuk memverifikasi HTTP 200 dan magic header PDF
        cy.request({
          url: href,
          encoding: 'binary',
        }).then((response) => {
          expect(response.status).to.eq(200);
          expect(response.headers['content-type']).to.include('application/pdf');
          expect(response.headers['content-disposition']).to.include('.pdf');
          expect(response.body.substring(0, 5)).to.eq('%PDF-');
        });
      });
  });

  // ===================================================================
  // TEST CASES PIC: DESY (Google Sheet Modul 6)
  // ===================================================================

  it('TC_ADT_007: Investigasi perubahan data — Dialog Visual Diff Viewer menampilkan nilai lama dan baru', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.url().should('include', '/audit');

    // Tabel audit harus memuat baris data
    cy.get('table tbody tr').should('have.length.at.least', 1);

    // Langkah 3: Klik aksi "Detail" untuk melihat perubahan
    cy.get('table tbody tr').first().find('button').contains('Detail').click();

    // Verifikasi dialog Visual Diff Viewer terbuka
    cy.contains('h2', 'Perbandingan Perubahan Atribut').should('be.visible');
    cy.get('section[role="dialog"]').should('be.visible');

    // Tutup dialog secara spesifik di dalam section modal (hindari tombol tutup sidebar)
    cy.get('section[role="dialog"]').contains('button', 'Tutup').click();
    cy.contains('h2', 'Perbandingan Perubahan Atribut').should('not.exist');
  });

  it('TC_ADT_008: Investigasi perubahan data — Dialog perbandingan tampil responsif di layar mobile (375x812)', () => {
    cy.viewport(375, 812);

    cy.visit('/login');
    cy.get('input#email').clear().type('sultansyarif630@gmail.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/audit');
    cy.url().should('include', '/audit');

    cy.get('table tbody tr').should('have.length.at.least', 1);
    cy.get('table tbody tr').first().find('button').contains('Detail').click();

    // Verifikasi dialog perbandingan terbuka di layar mobile
    cy.contains('h2', 'Perbandingan Perubahan Atribut').should('be.visible');
    cy.get('section[role="dialog"]').should('be.visible');

    // Toleransi sub-pixel rendering browser Edge/Chrome (375.2px <= 376px)
    cy.get('body').invoke('outerWidth').should('be.lte', 376);

    // Tutup dialog dari dalam modal
    cy.get('section[role="dialog"]').contains('button', 'Tutup').click();
    cy.contains('h2', 'Perbandingan Perubahan Atribut').should('not.exist');

    // Kembalikan viewport ke ukuran desktop standar
    cy.viewport(1280, 720);
  });
});
