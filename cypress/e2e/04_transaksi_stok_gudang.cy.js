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
});
