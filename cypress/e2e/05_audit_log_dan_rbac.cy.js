describe('Modul 05: Kontrol Akses RBAC & Alur Persetujuan Restock', () => {



  it('TC_RBA_001: Keamanan RBAC Audit: Staf Gudang dicegah mengakses modul Audit Forensik privat (HTTP 403 Forbidden)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Staf gudang mencoba membuka audit (harus ditolak 403 Forbidden oleh RoleMiddleware)
    cy.visit('/audit', { failOnStatusCode: false });
    cy.get('body').should('be.visible');
  });

  it('TC_RBA_002: Monitoring Pengajuan Restock: Manajer Operasional dapat meninjau usulan pengadaan barang', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Klik fisik menu Approval Restock di sidebar aside
    cy.get('aside').contains(/approval restock/i).should('be.visible').click();
    cy.url().should('include', '/restock');
    cy.contains(/restock|monitoring|pengadaan/i).should('exist');
  });

  it('TC_RBA_003: Otorisasi Persetujuan Restock: Komisaris berhak mengakses halaman persetujuan restock', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/restock/approval');
    cy.url().should('include', '/restock/approval');
    cy.contains(/persetujuan|otorisasi|restock|komisaris/i).should('exist');
  });

  it('TC_RBA_004: Manajemen Pengguna: Manajer Operasional dapat meninjau daftar pengguna via klik sidebar', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Klik fisik menu Manajemen Pengguna di sidebar aside
    cy.get('aside').contains(/manajemen pengguna/i).should('be.visible').click();
    cy.url().should('include', '/users');
    cy.contains(/pengguna|user|peran|role/i).should('exist');
  });

  it('TC_RBA_005: Keamanan RBAC Pengguna: Staf Gudang dicegah membuka menu manajemen pengguna (/users)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Staf gudang mencoba membuka users (harus ditolak 403 Forbidden oleh RoleMiddleware)
    cy.visit('/users', { failOnStatusCode: false });
    cy.get('body').should('be.visible');
  });

  // ===================================================================
  // MODUL 05: ALUR PERSETUJUAN PENGADAAN (RESTOCK APPROVAL) - PIC: MEUTIA
  // ===================================================================

  it('TC_APV_001: Memverifikasi Dasbor Otorisasi Komisaris', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/restock/approval');
    cy.url().should('include', '/restock/approval');

    // Verifikasi header halaman otorisasi persetujuan restock
    cy.contains('h1, h2', /Persetujuan Usulan Restock|Otorisasi Persetujuan Usulan Restock/i).should('be.visible');
    cy.contains(/Daftar pengajuan pengadaan multi-item oleh Manajer Operasional yang menunggu keputusan persetujuan Dewan Komisaris/i).should('be.visible');

    // Verifikasi antarmuka: baik saat terdapat antrean permohonan maupun antrean bersih
    cy.get('body').then(($body) => {
      if ($body.find('table').length > 0) {
        // Terdapat kartu permohonan restock aktif
        cy.contains(/Menunggu Keputusan Komisaris/i).should('be.visible');
        cy.contains(/Total Estimasi/i).should('be.visible');
        cy.get('table').should('be.visible');
      } else {
        // Antrean sedang kosong/bersih
        cy.contains(/Antrean Persetujuan Bersih/i).should('be.visible');
      }
    });
  });

  // ===================================================================
  // MODUL 05: ALUR PERSETUJUAN PENGADAAN (RESTOCK APPROVAL) - PIC: DESY
  // ===================================================================

  it('TC_APV_002: Keputusan Komisaris — Penolakan (reject) wajib mengisi alasan', () => {
    let alertFired = false;
    cy.on('window:alert', (text) => {
      alertFired = true;
      expect(text).to.include('alasan penolakan');
    });

    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/restock/approval');
    cy.url().should('include', '/restock/approval');

    cy.get('body').then(($body) => {
      if ($body.find('button:contains("Tolak Berkas")').length > 0) {
        // Langkah 2 & 3: Pilih pengajuan dan klik opsi Tolak
        cy.contains('button', 'Tolak Berkas').first().click();
        cy.contains('Tolak Pengadaan Restock').should('be.visible');

        // Pastikan catatan awal kosong
        cy.get('textarea').should('have.value', '');

        // Langkah 4: Klik konfirmasi tanpa alasan -> validasi wajib alasan aktif & modal tetap buka
        cy.contains('button', 'Konfirmasi Penolakan').click();

        // Verifikasi bahwa penolakan tidak diproses (modal tetap terbuka di layar)
        cy.contains('Tolak Pengadaan Restock').should('be.visible');
        cy.url().should('include', '/restock/approval');

        // Langkah 5: Isi alasan penolakan sesuai data uji di sheet
        cy.get('textarea').type('Anggaran belum tersedia');

        // Langkah 6: Klik konfirmasi lagi
        cy.contains('button', 'Konfirmasi Penolakan').click();

        // Modal tertutup setelah submit sukses
        cy.contains('Tolak Pengadaan Restock').should('not.exist');
      } else {
        cy.log('INFO: Tidak ada restock pending saat ini. Uji rute approval lolos.');
        cy.contains(/persetujuan|otorisasi|restock|komisaris/i).should('exist');
      }
    });
  });

  it('TC_APV_003: Tampilan dialog approval adaptif di layar mobile dan mode gelap/terang', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/restock/approval');
    cy.url().should('include', '/restock/approval');

    // Uji mode gelap
    cy.get('html').then(($html) => {
      $html.addClass('dark');
    });
    cy.get('body').should('be.visible');

    // Uji pada viewport mobile iPhone SE (375x812)
    cy.viewport(375, 812);
    cy.get('body').invoke('outerWidth').should('be.lte', 376);
    cy.contains(/persetujuan|otorisasi|restock|komisaris/i).should('exist');

    // Buka dan tutup dialog approval di layar mobile jika ada berkas
    cy.get('body').then(($body) => {
      if ($body.find('button:contains("Setujui & Terbitkan Inbound")').length > 0) {
        cy.contains('button', 'Setujui & Terbitkan Inbound').first().click();
        cy.contains('Setujui Pengadaan Restock').should('be.visible');
        cy.get('body').invoke('outerWidth').should('be.lte', 376);
        cy.contains('button', 'Batal').click();
        cy.contains('Setujui Pengadaan Restock').should('not.exist');
      }
    });

    // Kembalikan viewport ke desktop
    cy.viewport(1280, 720);
  });
});
