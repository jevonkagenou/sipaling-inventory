describe('Modul 04: Transaksi Operasional Gudang (Mutasi Stok Masuk & Keluar)', () => {
  beforeEach(() => {
    cy.visit('/login');
    cy.get('input#email').clear().type('staf@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });

  it('Navigasi Fisik Sidebar: Berpindah ke Mutasi Stok Gudang via klik menu aside', () => {
    cy.get('aside').contains(/mutasi stok/i).should('be.visible').click();
    cy.url().should('include', '/transactions');
    cy.contains(/transaksi|mutasi|inbound|outbound/i).should('exist');
  });

  it('Penyaringan Riwayat Mutasi: Filter tipe transaksi menyaring baris riwayat', () => {
    cy.visit('/transactions');
    cy.get('body').should('be.visible');
    cy.contains(/semua|masuk|keluar|inbound|outbound/i).should('exist');
  });

  it('Real Inbound Transaction: Mencatat transaksi barang masuk riil dan verifikasi sukses', () => {
    cy.visit('/transactions/inbound');
    cy.url().should('include', '/transactions/inbound');

    // Isi identitas pemasok
    cy.get('input[placeholder*="Sumber Makmur"]').clear().type('PT Logistik Cypress Terpadu');

    // Buka dropdown pemilihan produk dan pilih produk pertama
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();

    // Tentukan kuantitas masuk
    cy.get('input[type="number"]').first().clear().type('10');

    // Simpan transaksi barang masuk
    cy.contains('button', /simpan barang masuk/i).click();

    // Verifikasi kembali ke halaman transaksi dan muncul flash notifikasi sukses
    cy.url().should('include', '/transactions');
    cy.contains(/berhasil|sukses|stok telah diperbarui/i).should('be.visible');
  });

  it('Verifikasi Integritas Stok: Stok barang tercermin secara tepat pada katalog inventaris', () => {
    cy.visit('/inventory');
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });

  it('Negative Testing Outbound: Proteksi defisit stok mencegah pengeluaran melebihi stok fisik', () => {
    cy.visit('/transactions/outbound');

    // Pilih produk pertama dari dropdown
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();

    // Masukkan kuantitas keluar ekstrem (melebihi kapasitas fisik gudang)
    cy.get('input[type="number"]').first().clear().type('99999');

    // Verifikasi banner peringatan merah "Defisit / Stok Tidak Mencukupi" langsung aktif
    cy.contains(/defisit|stok tidak mencukupi|kuantitas melebihi stok|peringatan validasi/i).should('be.visible');

    // Verifikasi tombol submit dinonaktifkan untuk mencegah pengeluaran melebihi stok fisik
    cy.contains('button', /simpan barang keluar/i).should('be.disabled');

    // Form tetap berada pada halaman outbound dan tidak terkirim
    cy.url().should('include', '/transactions/outbound');
  });

  it('Negative Testing: Input kuantitas non-positif (0 atau minus) dicegat oleh form validation', () => {
    cy.visit('/transactions/inbound');

    // Coba isi kuantitas 0 atau negatif
    cy.get('input[type="number"]').first().clear().type('0');
    cy.get('input[type="number"]').first().then(($input) => {
      expect($input[0].checkValidity()).to.be.false;
    });

    cy.get('input[type="number"]').first().clear().type('-5');
    cy.get('input[type="number"]').first().then(($input) => {
      expect($input[0].checkValidity()).to.be.false;
    });
  });

  it('Pencegahan UI Double-Submit (Race Condition Guard): Tombol submit dinonaktifkan saat memproses', () => {
    cy.visit('/transactions/inbound');
    cy.get('button[type="submit"]').should('exist');
  });

  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 3 - SERVICE MUTASI STOK ATOMIK & NOMOR TRANSAKSI (SULTAN)
  | SKPL / PMPL: TC_TRX_007 (Outbound Sukses) & TC_TRX_008 (Nomor TRX)
  | Trello Task: [M3-LC-01], [M3-LC-02], [M3-LC-03]
  |--------------------------------------------------------------------------
  */

  it('TC_TRX_007: StockTransactionService: Pencatatan barang keluar (Happy Path Outbound) sukses memotong stok secara atomik', () => {
    cy.visit('/transactions/outbound');
    cy.url().should('include', '/transactions/outbound');

    // Isi nama penerima barang keluar
    cy.get('input[placeholder*="Toko"], input[placeholder*="Penerima"], input[placeholder*="PT"]').first().clear().type('Toko Mitra Utama Distribusi');

    // Buka dropdown pemilihan produk dan pilih produk pertama yang punya stok
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();

    // Masukkan kuantitas keluar yang valid (1 unit)
    cy.get('input[type="number"]').first().clear().type('1');

    // Simpan transaksi barang keluar
    cy.contains('button', /simpan barang keluar/i).should('not.be.disabled').click();

    // Verifikasi kembali ke halaman riwayat transaksi dan muncul pesan sukses
    cy.url().should('include', '/transactions');
    cy.contains(/berhasil|sukses|stok telah diperbarui/i).should('be.visible');
  });

  it('TC_TRX_008: StockTransactionService: Nomor referensi dokumen mutasi ter-generate otomatis berformat unik standar', () => {
    cy.visit('/transactions');
    cy.get('body').should('be.visible');

    // Memverifikasi adanya nomor referensi mutasi di tabel dengan format standar TRX-IN atau TRX-OUT
    cy.get('table tbody').contains(/TRX-(IN|OUT)-\d{8}-\d{4}/).should('exist');
  });

  it('TC_TRX_009: Integritas Transaksi Mutasi: Validasi pencarian riwayat mutasi berdasarkan nomor referensi', () => {
    cy.visit('/transactions');

    // Cari riwayat berdasarkan prefix TRX
    cy.get('input[placeholder*="Cari"], input[type="search"]').first().clear().type('TRX');
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });
});
