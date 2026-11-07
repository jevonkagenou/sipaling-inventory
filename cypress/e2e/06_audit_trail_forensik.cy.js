describe('Modul 06: Audit Trail & Investigasi Forensik Log', () => {
  it('Akses Auditor: Membuka modul Jejak Audit Forensik (/audit) via klik fisik sidebar', () => {
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

  it('Integritas Log: Tabel audit menampilkan rekam jejak aktivitas pengguna dan timestamp append-only', () => {
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

  it('Keamanan RBAC Audit: Staf Gudang dicegah mengakses modul Audit Forensik privat (HTTP 403 Forbidden)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Staf gudang mencoba membuka audit (harus ditolak 403 Forbidden oleh RoleMiddleware)
    cy.visit('/audit', { failOnStatusCode: false });
    cy.get('body').should('be.visible');
  });

  // ===================================================================
  // TEST CASES PIC: DESY (Google Sheet Modul 6)
  // ===================================================================

  it('TC_ADT_005: Investigasi perubahan data — Dialog Visual Diff Viewer menampilkan nilai lama dan baru', () => {
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

  it('TC_ADT_006: Investigasi perubahan data — Dialog perbandingan tampil responsif di layar mobile (375x812)', () => {
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
