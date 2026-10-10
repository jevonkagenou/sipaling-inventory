describe('Capture 8 Authentic Screenshots for PMPL Bug Report', () => {
  beforeEach(() => {
    // Set viewport wide enough for clear screenshots
    cy.viewport(1280, 800);
  });

  function login(email, password = 'password') {
    cy.visit('/login');
    cy.get('input#email').clear().type(email);
    cy.get('input#password').clear().type(password);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
    cy.get('header').should('be.visible');
  }

  it('Captures BG-001 (TC_AUT_005): Login Credentials Mismatch Error', () => {
    cy.visit('/login');
    cy.get('input#email').clear().type('hacker.palsu@sipaling.com');
    cy.get('input#password').clear().type('password_salah_total');
    cy.get('button[type="submit"]').click();
    cy.contains(/credentials|match|kredensial|tidak cocok/i, { timeout: 10000 }).should('be.visible');
    cy.wait(500);
    cy.screenshot('bg001_tc_aut_005');
  });

  it('Captures BG-002 (TC_DSH_004): Header Profile Dropdown Element Ambiguity', () => {
    login('manajer@sipaling.com');
    cy.visit('/dashboard');
    cy.get('header').should('be.visible');
    cy.get('header').find('button').filter(':has(.h-7)').click();
    cy.get('header a[href*="profile"]').should('be.visible');
    cy.wait(500);
    cy.screenshot('bg002_tc_dsh_004');
  });

  it('Captures BG-003 (TC_INV_002): Inventory Negative Search Empty State', () => {
    login('manajer@sipaling.com');
    cy.visit('/inventory');
    cy.get('table', { timeout: 10000 }).should('be.visible');
    cy.get('input[placeholder*="Cari"]').clear().type('PRODUK_FIKTIF_TIDAK_ADA_XYZ_99999');
    cy.contains(/tidak ada/i, { timeout: 10000 }).should('be.visible');
    cy.wait(500);
    cy.screenshot('bg003_tc_inv_002');
  });

  it('Captures BG-004 (TC_INV_005): Inventory Product Dialog Form Control Identification', () => {
    login('manajer@sipaling.com');
    cy.visit('/inventory');
    cy.contains('button', /tambah barang/i, { timeout: 10000 }).should('be.visible').click();
    cy.contains(/informasi produk|katalog master|tambah barang/i, { timeout: 10000 }).should('be.visible');
    cy.get('input[placeholder*="SKU"]').should('be.visible');
    cy.wait(500);
    cy.screenshot('bg004_tc_inv_005');
  });

  it('Captures BG-005 (TC_INV_007): Inventory Row Action Edit Dropdown', () => {
    login('manajer@sipaling.com');
    cy.visit('/inventory');
    cy.get('table tbody tr', { timeout: 10000 }).should('have.length.at.least', 1);
    cy.get('table tbody tr').first().within(() => {
      cy.get('button').last().click({ force: true });
    });
    cy.contains(/edit barang|perbarui|ubah/i, { timeout: 10000 }).should('be.visible');
    cy.wait(500);
    cy.screenshot('bg005_tc_inv_007');
  });

  it('Captures BG-006 (TC_INV_008): Category Management Modal Overlay Backdrop', () => {
    login('manajer@sipaling.com');
    cy.visit('/inventory');
    cy.contains('button', /kelola kategori/i, { timeout: 10000 }).should('be.visible').click();
    cy.contains(/kelola master kategori|daftar kategori/i, { timeout: 10000 }).should('be.visible');
    cy.wait(500);
    cy.screenshot('bg006_tc_inv_008');
  });

  it('Captures BG-007 (TC_TRX_003): Inbound Transaction HTML5 Decimal Step Mismatch', () => {
    login('staf@sipaling.com');
    cy.visit('/transactions/inbound');
    cy.get('input[placeholder*="Sumber Makmur"]', { timeout: 10000 }).should('be.visible').clear().type('PT Logistik Cypress Terpadu');
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();
    cy.get('input[type="number"]').first().clear().type('10');
    cy.wait(500);
    cy.screenshot('bg007_tc_trx_003');
  });

  it('Captures BG-008 (TC_TRX_005): Outbound Stock Deficit Red Warning Banner & Disabled Guard', () => {
    login('staf@sipaling.com');
    cy.visit('/transactions/outbound');
    cy.get('input[placeholder*="Cari SKU"]', { timeout: 10000 }).first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();
    cy.get('input[type="number"]').first().clear().type('99999');
    cy.contains(/defisit|stok tidak mencukupi|kuantitas melebihi stok/i, { timeout: 10000 }).should('be.visible');
    cy.wait(500);
    cy.screenshot('bg008_tc_trx_005');
  });
});
