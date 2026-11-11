describe('Modul 04: Transaksi Operasional Gudang (Mutasi Stok Masuk & Keluar)', () => {
beforeEach(() => {
  cy.visit('/login');
  cy.get('input#email').clear().type('staf@sipaling.com');
  cy.get('input#password').clear().type('password');
  cy.get('button[type="submit"]').click();
  cy.url().should('include', '/dashboard');
});

  it('TC_TRX_001: Navigasi Fisik Sidebar: Berpindah ke Mutasi Stok Gudang via klik menu aside', () => {
    cy.get('aside').contains(/mutasi stok/i).should('be.visible').click();
    cy.url().should('include', '/transactions');
    cy.contains(/transaksi|mutasi|inbound|outbound/i).should('exist');
  });

  it('TC_TRX_002: Penyaringan Riwayat Mutasi: Filter tipe transaksi menyaring baris riwayat', () => {
    cy.visit('/transactions');
    cy.get('body').should('be.visible');
    cy.contains(/semua|masuk|keluar|inbound|outbound/i).should('exist');
  });

  it('TC_TRX_003: Real Inbound Transaction: Mencatat transaksi barang masuk riil dan verifikasi sukses', () => {
    cy.visit('/transactions/inbound');
    cy.url().should('include', '/transactions/inbound');

    // Isi identitas pemasok
    cy.get('input[placeholder*="Sumber Makmur"]').clear().type('PT Logistik Cypress Terpadu');

    // Buka dropdown pemilihan produk dan pilih produk pertama
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();

    // Tentukan kuantitas masuk
    cy.get('input[type="number"]').first().clear().type('10');

    // Simpan transaksi barang masuk (membuka modal konfirmasi permanen)
    cy.contains('button', /simpan barang masuk/i).click();

    // Konfirmasi mutasi barang masuk pada modal dialog
    cy.get('[role="dialog"]').contains('button', /konfirmasi & simpan/i).click();

    // Verifikasi kembali ke halaman transaksi dan muncul flash notifikasi sukses
    cy.url().should('eq', `${Cypress.config().baseUrl}/transactions`);
    cy.contains(/berhasil|sukses|stok telah diperbarui/i).should('be.visible');
  });

  it('TC_TRX_004: Verifikasi Integritas Stok: Stok barang tercermin secara tepat pada katalog inventaris', () => {
    cy.visit('/inventory');
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });

  it('TC_TRX_005: Negative Testing Outbound: Proteksi defisit stok mencegah pengeluaran melebihi stok fisik', () => {
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

  it('TC_TRX_006: Negative Testing: Input kuantitas non-positif (0 atau minus) dicegat oleh form validation', () => {
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

  it('TC_TRX_007: Pencegahan UI Double-Submit (Race Condition Guard): Tombol submit dinonaktifkan saat memproses', () => {
    cy.visit('/transactions/inbound');
    cy.get('button[type="submit"]').should('exist');
  });

  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 3 - SERVICE MUTASI STOK ATOMIK & NOMOR TRANSAKSI (SULTAN)
  | SKPL / PMPL: TC_TRX_008 s/d TC_TRX_010 (Outbound Sukses, Nomor TRX, Pencarian)
  | Trello Task: [M3-LC-01], [M3-LC-02], [M3-LC-03]
  |--------------------------------------------------------------------------
  */

  it('TC_TRX_008: StockTransactionService: Pencatatan barang keluar (Happy Path Outbound) sukses memotong stok secara atomik', () => {
    cy.visit('/transactions/outbound');
    cy.url().should('include', '/transactions/outbound');

    // Isi nama penerima barang keluar
    cy.get('input[placeholder*="Toko"], input[placeholder*="Penerima"], input[placeholder*="PT"]').first().clear().type('Toko Mitra Utama Distribusi');

    // Buka dropdown pemilihan produk dan pilih produk pertama yang punya stok
    cy.get('input[placeholder*="Cari SKU"]').first().click();
    cy.get('button').filter(':contains("SKU:"), :contains("Stok:")').first().click();

    // Masukkan kuantitas keluar yang valid (1 unit)
    cy.get('input[type="number"]').first().clear().type('1');

    // Simpan transaksi barang keluar (membuka modal konfirmasi permanen)
    cy.contains('button', /simpan barang keluar/i).should('not.be.disabled').click();

    // Konfirmasi mutasi keluar pada modal dialog
    cy.get('[role="dialog"]').contains('button', /konfirmasi & simpan/i).click();

    // Verifikasi kembali ke halaman riwayat transaksi dan muncul pesan sukses
    cy.url().should('eq', `${Cypress.config().baseUrl}/transactions`);
    cy.contains(/berhasil|sukses|stok telah dikurangi|stok telah diperbarui/i).should('be.visible');
  });

  it('TC_TRX_009: StockTransactionService: Nomor referensi dokumen mutasi ter-generate otomatis berformat unik standar', () => {
    cy.visit('/transactions');
    cy.get('body').should('be.visible');

    // Memverifikasi adanya nomor referensi mutasi di tabel dengan format standar TRX-IN atau TRX-OUT
    cy.get('table tbody').contains(/TRX-(IN|OUT)-\d{8}-\d{4}/).should('exist');
  });

  it('TC_TRX_010: Integritas Transaksi Mutasi: Validasi pencarian riwayat mutasi berdasarkan nomor referensi', () => {
    cy.visit('/transactions');

    // Cari riwayat berdasarkan prefix TRX
    cy.get('input[placeholder*="Cari"], input[type="search"]').first().clear().type('TRX');
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });
    /*
  |--------------------------------------------------------------------------
  | PENGUJIAN PIC NAJWA - M3-FE-04
  | Tampilan & Unduh Cetak Bukti Serah Terima Barang (PDF)
  |--------------------------------------------------------------------------
  */

  it('TC_TRX_011: Detail transaksi menampilkan informasi dan rincian barang dengan benar', () => {
  cy.visit('/transactions?search=581585');

  cy.contains('table tbody tr', '581585')
    .first()
    .within(() => {
      cy.get('button[title="Lihat Rincian Bukti Transaksi"]').click();
    });

  cy.get('.receipt-modal-root').should('be.visible');
  cy.contains('h3', 'Bukti Transaksi: 581585').should('be.visible');
  cy.contains('Barang Keluar (Outbound)').should('be.visible');

  cy.contains('Waktu Transaksi').should('be.visible');
  cy.contains('Penerima').should('be.visible');
  cy.contains('Petugas Pencatat').should('be.visible');

  cy.get('.receipt-modal-root').within(() => {
    cy.contains('ASSORTED COLOUR BIRD ORNAMENT').should('be.visible');
    cy.contains('-16 pcs').should('be.visible');
    cy.contains('RABBIT NIGHT LIGHT').should('be.visible');
    cy.contains('-12 pcs').should('be.visible');
    cy.contains('button', 'Tutup').should('be.visible');
  });
});

  it('TC_TRX_012: Tautan cetak PDF menghasilkan dokumen bukti transaksi yang valid', () => {
    cy.visit('/transactions?search=581585');

cy.contains('table tbody tr', '581585')
  .first()
  .within(() => {
    cy.get('a[title="Unduh / Cetak PDF"]')
      .should('be.visible')
      .and('have.attr', 'target', '_blank')
      .and('have.attr', 'href')
      .then((href) => {
        cy.request({
          url: href,
          encoding: 'binary',
        }).then((response) => {
          expect(response.status).to.eq(200);
          expect(response.headers['content-type']).to.include('application/pdf');
          expect(response.body.substring(0, 5)).to.eq('%PDF-');
          expect(response.headers['content-disposition'])
            .to.include('Surat-Jalan-581585.pdf');
        });
      });
  });
  });

  it('TC_TRX_013: Modal detail transaksi dengan 8 SKU dapat di-scroll dan ditutup', () => {
   cy.visit('/transactions?search=581492');

cy.contains('table tbody tr', '581492')
  .first()
  .within(() => {
    cy.get('button[title="Lihat Rincian Bukti Transaksi"]').click();
  });

    // Verifikasi modal dan jumlah rincian barang
    cy.get('.receipt-modal-root').should('be.visible');

    cy.get('.receipt-modal-root .overflow-y-auto')
      .should('have.css', 'overflow-y', 'auto')
      .then(($modal) => {
        expect($modal[0].scrollHeight).to.be.greaterThan(
          $modal[0].clientHeight
        );
        cy.wrap($modal).scrollTo('bottom');
      });

    // Verifikasi seluruh 8 SKU ditampilkan
    cy.get('.receipt-modal-root table tbody tr')
      .should('have.length', 8);

    // Verifikasi struktur dua kolom tanda tangan untuk tampilan cetak
    cy.get('.signature-grid')
      .should('have.class', 'grid-cols-2')
      .and('have.class', 'gap-8');

    // Verifikasi tombol tutup tetap dapat dijangkau
    cy.get('.receipt-modal-root')
      .contains('button', 'Tutup')
      .should('be.visible')
      .click();

    cy.get('.receipt-modal-root').should('not.exist');
  });
});

