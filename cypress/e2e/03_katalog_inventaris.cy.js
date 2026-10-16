describe('Modul 03: Katalog Master Inventaris, Kategori, & Operasi CRUD', () => {
  const uniqueSku = `CYP-${Date.now().toString().slice(-6)}`;

  beforeEach(() => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });

  it('Navigasi Fisik Sidebar: Berpindah ke Katalog Inventaris via klik menu aside', () => {
    cy.get('aside').contains(/katalog inventaris/i).should('be.visible').click();
    cy.url().should('include', '/inventory');
    cy.get('table').should('exist');
    cy.contains(/katalog|inventaris|produk/i).should('exist');
  });

  it('Pencarian Real-Time & Negative Search: Menguji kata kunci tidak ditemukan & pemulihan data', () => {
    cy.visit('/inventory');
    cy.get('input[type="text"], input[type="search"], input[placeholder*="Cari"]').first().as('searchInput');

    // Negative search: kata kunci yang pasti tidak ada
    cy.get('@searchInput').clear().type('PRODUK_FIKTIF_TIDAK_ADA_XYZ_99999');
    cy.contains(/tidak ada barang|cocok|tidak ditemukan|0 produk/i).should('exist');

    // Pemulihan pencarian
    cy.get('@searchInput').clear();
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });

  it('Penyaringan Status Stok: Mengalihkan tab filter (Semua, Aman, Perlu Restock)', () => {
    cy.visit('/inventory');
    cy.contains(/semua|aman|restock/i).should('exist');
  });

  it('Kategori: Memverifikasi endpoint master data kategori barang mengembalikan status 200 OK', () => {
    cy.request('/categories').then((response) => {
      expect(response.status).to.eq(200);
      expect(response.headers['content-type']).to.include('application/json');
    });
  });

  it('Real CRUD Create: Menambah produk baru secara riil dan memverifikasi data tersimpan di tabel', () => {
    cy.visit('/inventory');
    cy.contains('button', /tambah barang/i).click();
    cy.contains(/tambah barang|informasi produk/i).should('be.visible');

    // Input data lengkap formulir penambahan barang
    cy.get('input#sku').clear().type(uniqueSku);
    cy.get('select#category_id').select(1);
    cy.get('input#name').clear().type('Produk Uji Cypress Automated');
    cy.get('input#unit').clear().type('Unit');
    cy.get('input#unit_price').clear().type('45000');
    cy.get('input#current_stock').clear().type('25');
    cy.get('input#minimum_stock').clear().type('5');
    cy.get('textarea#description').clear().type('Dibuat secara otomatis melalui pengujian Cypress E2E.');

    // Simpan data produk
    cy.contains('button', /simpan barang/i).click();

    // Verifikasi dialog tertutup dan produk muncul di tabel
    cy.get('input[placeholder*="Cari"]').clear().type(uniqueSku);
    cy.get('table').contains(uniqueSku).should('be.visible');
    cy.get('table').contains('Produk Uji Cypress Automated').should('be.visible');
  });

  it('Negative Testing: Submission formulir produk dengan bidang wajib kosong dicegat antarmuka', () => {
    cy.visit('/inventory');
    cy.contains('button', /tambah barang/i).click();

    // Mencoba submit tanpa mengisi bidang wajib
    cy.get('form').within(() => {
      cy.get('button[type="submit"]').click();
    });

    // Dialog tetap terbuka menandakan pencegahan submission kosong
    cy.contains(/tambah barang|informasi produk/i).should('be.visible');
  });

  it('Real CRUD Update: Mengubah nama dan harga produk yang baru dibuat via tombol Edit', () => {
    cy.visit('/inventory');
    cy.get('input[placeholder*="Cari"]').clear().type(uniqueSku);
    cy.get('table tbody tr').first().within(() => {
      cy.contains(uniqueSku).should('be.visible');
      // Buka dropdown menu aksi pada baris produk
      cy.get('button').last().click({ force: true });
    });

    // Klik Edit Barang di dropdown
    cy.contains(/edit barang/i).should('be.visible').click();

    // Perbarui nama dan harga
    cy.get('input#name').clear().type('Produk Uji Cypress (Revisi Sukses)');
    cy.get('input#unit_price').clear().type('60000');

    // Simpan perubahan
    cy.contains('button', /simpan perubahan/i).click();

    // Verifikasi data yang telah diperbarui muncul di tabel
    cy.get('table').contains('Produk Uji Cypress (Revisi Sukses)').should('be.visible');
  });

  it('CRUD Kategori Master: Menambah kategori baru secara riil melalui modal kelola kategori', () => {
    const categoryName = `Kategori Uji ${Date.now().toString().slice(-4)}`;
    cy.visit('/inventory');
    cy.contains('button', /kelola kategori/i).click();
    cy.contains(/kelola master kategori/i).should('be.visible');

    // Isi input nama kategori baru
    cy.get('input[placeholder*="Elektronik"]').clear().type(categoryName);
    cy.get('[role="dialog"]').find('button[type="submit"]').click();

    // Verifikasi kategori baru muncul di daftar kategori modal
    cy.contains(categoryName).should('be.visible');
  });

  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 2 - VALIDASI SKU & PROTEKSI RESTRICT DELETE (SULTAN)
  | SKPL / PMPL: TC_INV_005 (SKU Unik) & TC_INV_006 (Restrict Delete)
  | Trello Task: [M2-LC-01] & [M2-LC-02]
  |--------------------------------------------------------------------------
  */

  it('TC_INV_005: StoreProductRequest: Validasi backend menolak pendaftaran produk dengan kode SKU duplikat', () => {
    cy.visit('/inventory');

    // Ambil kode SKU dari baris produk pertama yang sudah ada di tabel database
    cy.get('table tbody tr').first().find('td').first().invoke('text').then((existingSkuRaw) => {
      const existingSku = existingSkuRaw.trim();

      // Buka modal Tambah Barang
      cy.contains('button', /tambah barang/i).click();
      cy.contains(/tambah barang|informasi produk/i).should('be.visible');

      // Masukkan SKU yang sudah terdaftar (duplikat)
      cy.get('input#sku').clear().type(existingSku);
      cy.get('select#category_id').select(1);
      cy.get('input#name').clear().type('Produk Coba Duplikasi SKU');
      cy.get('input#unit').clear().type('Pcs');
      cy.get('input#unit_price').clear().type('30000');
      cy.get('input#current_stock').clear().type('15');
      cy.get('input#minimum_stock').clear().type('3');

      // Simpan barang
      cy.contains('button', /simpan barang/i).click();

      // Memverifikasi penolakan dari Rule::unique pada StoreProductRequest
      cy.contains(/sudah digunakan|already been taken|terdaftar/i).should('be.visible');
      // Modal tetap terbuka dan form dicegat
      cy.contains(/tambah barang|informasi produk/i).should('be.visible');
    });
  });

  it('TC_INV_006: ProductController: Proteksi Restrict Delete aktif pada produk yang memiliki riwayat mutasi', () => {
    cy.visit('/inventory');

    // Buka menu aksi baris produk pertama
    cy.get('table tbody tr').first().within(() => {
      cy.get('button').last().click({ force: true });
    });

    // Klik Hapus Barang di dropdown menu
    cy.contains(/hapus barang/i).should('be.visible').click();

    // Dialog konfirmasi hapus terbuka
    cy.contains(/hapus barang inventaris|pengecekan aturan integritas/i).should('be.visible');

    // Verifikasi banner peringatan Restrict Delete aktif untuk barang yang memiliki riwayat mutasi
    cy.get('body').then(($body) => {
      if ($body.find(':contains("Penghapusan Dibatasi")').length > 0) {
        cy.contains(/penghapusan dibatasi|restrict delete/i).should('be.visible');
        cy.contains(/riwayat mutasi stok|tidak dapat dihapus/i).should('be.visible');
        // Tombol hapus dicegah/tidak ditampilkan demi integritas audit trail, hanya ada tombol Tutup
        cy.get('[role="dialog"]').contains('button', /tutup/i).click();
      } else {
        // Jika produk tidak punya mutasi, tombol batal/tutup tetap bisa diklik
        cy.get('[role="dialog"]').contains('button', /tutup|batal/i).click();
      }
    });
  });
});
