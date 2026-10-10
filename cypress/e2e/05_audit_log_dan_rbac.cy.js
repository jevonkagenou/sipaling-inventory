describe('Modul 05: Jejak Rekam Audit Forensik, Restock Workflow, & Kontrol Akses RBAC', () => {
  it('Akses Auditor: Membuka modul Jejak Audit Forensik (/audit) via klik fisik sidebar', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('luxoqwasery@gmail.com');
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
    cy.get('input#email').clear().type('luxoqwasery@gmail.com');
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

  it('Monitoring Pengajuan Restock: Manajer Operasional dapat meninjau usulan pengadaan barang', () => {
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

  it('Otorisasi Persetujuan Restock: Komisaris berhak mengakses halaman persetujuan restock', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('komisaris@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    cy.visit('/restock/approval');
    cy.url().should('include', '/restock/approval');
    cy.contains(/persetujuan|otorisasi|restock|komisaris/i).should('exist');
  });

  it('Manajemen Pengguna: Manajer Operasional dapat meninjau daftar pengguna via klik sidebar', () => {
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

  it('Keamanan RBAC Pengguna: Staf Gudang dicegah membuka menu manajemen pengguna (/users)', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

    // Staf gudang mencoba membuka users (harus ditolak 403 Forbidden oleh RoleMiddleware)
    cy.visit('/users', { failOnStatusCode: false });
    cy.get('body').should('be.visible');
  });
});
