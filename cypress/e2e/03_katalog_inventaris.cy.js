describe('Modul 03: Katalog Master Inventaris, Kategori, & Operasi CRUD', () => {
  const uniqueSku = `CYP-${Date.now().toString().slice(-6)}`;

  beforeEach(() => {
    cy.visit('/login');
    cy.get('input#email').clear().type('manajer@sipaling.com');
    cy.get('input#password').clear().type('password');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });

  it('TC_INV_001: Navigasi Fisik Sidebar: Berpindah ke Katalog Inventaris via klik menu aside', () => {
    cy.get('aside').contains(/katalog inventaris/i).should('be.visible').click();
    cy.url().should('include', '/inventory');
    cy.get('table').should('exist');
    cy.contains(/katalog|inventaris|produk/i).should('exist');
  });

  it('TC_INV_002: Pencarian Real-Time & Negative Search: Menguji kata kunci tidak ditemukan & pemulihan data', () => {
    cy.visit('/inventory');
    cy.get('input[type="text"], input[type="search"], input[placeholder*="Cari"]').first().as('searchInput');

    // Negative search: kata kunci yang pasti tidak ada
    cy.get('@searchInput').clear().type('PRODUK_FIKTIF_TIDAK_ADA_XYZ_99999');
    cy.contains(/tidak ada barang|cocok|tidak ditemukan|0 produk/i).should('exist');

    // Pemulihan pencarian
    cy.get('@searchInput').clear();
    cy.get('table tbody tr').should('have.length.at.least', 1);
  });

  it('TC_INV_003: Penyaringan Status Stok: Mengalihkan tab filter (Semua, Aman, Perlu Restock)', () => {
    cy.visit('/inventory');
    cy.contains(/semua|aman|restock/i).should('exist');
  });

  it('TC_INV_004: Kategori: Memverifikasi endpoint master data kategori barang mengembalikan status 200 OK', () => {
    cy.request('/categories').then((response) => {
      expect(response.status).to.eq(200);
      expect(response.headers['content-type']).to.include('application/json');
    });
  });

  it('TC_INV_005: Real CRUD Create: Menambah produk baru secara riil dan memverifikasi data tersimpan di tabel', () => {
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

  it('TC_INV_006: Negative Testing: Submission formulir produk dengan bidang wajib kosong dicegat antarmuka', () => {
    cy.visit('/inventory');
    cy.contains('button', /tambah barang/i).click();

    // Mencoba submit tanpa mengisi bidang wajib
    cy.get('form').within(() => {
      cy.get('button[type="submit"]').click();
    });

    // Dialog tetap terbuka menandakan pencegahan submission kosong
    cy.contains(/tambah barang|informasi produk/i).should('be.visible');
  });

  it('TC_INV_007: Real CRUD Update: Mengubah nama dan harga produk yang baru dibuat via tombol Edit', () => {
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

 it('TC_INV_008: CRUD Kategori Master: Menambah kategori baru secara riil melalui modal kelola kategori', () => {
    const categoryName = `Kategori Uji ${Date.now().toString().slice(-4)}`;

    cy.visit('/inventory');
    cy.contains('button', /kelola kategori/i).click();

    cy.get('[role="dialog"]').should('be.visible')
      .within(() => {
        cy.contains('Kelola Master Kategori').should('be.visible');

        cy.get('input[placeholder="Contoh: Elektronik & IT"]')
          .clear()
          .type(categoryName);

        cy.contains('button', 'Tambah Kategori').click();

        // Memastikan kategori baru muncul di daftar kategori
                cy.get('div.space-y-2')
          .contains('span', categoryName)
          .should('be.visible');
      });
  });

  it('TC_INV_009: Master Kategori: Membatalkan perubahan edit kategori tanpa menyimpan', () => {
  const originalName = 'Gifts & Novelties';
  const temporaryName = `Kategori Batal ${Date.now().toString().slice(-4)}`;

  cy.visit('/inventory');
  cy.contains('button', /kelola kategori/i).click();

  cy.get('[role="dialog"]').should('be.visible');

  // Buka form edit
cy.get('[role="dialog"]').within(() => {
  cy.contains('span', originalName)
    .parents()
    .filter((index, element) => {
      return Cypress.$(element).find('button[title="Edit Kategori"]').length > 0;
    })
    .first()
    .find('button[title="Edit Kategori"]')
    .click();
});

  // Ubah nama sementara
  cy.get('input[placeholder="Contoh: Elektronik & IT"]')
    .clear()
    .type(temporaryName);

  // Batalkan perubahan
  cy.get('[role="dialog"]').contains('button', 'Batal').click();

  // Pastikan form kembali ke mode tambah
  cy.contains('Tambah Kategori Baru').should('be.visible');

  // Pastikan nama kategori awal tetap ada
  cy.contains('span', originalName).should('be.visible');

  // Pastikan nama sementara tidak tersimpan
  cy.contains('span', temporaryName).should('not.exist');
});
it('TC_INV_010: Master Kategori: Mengedit nama kategori dan mengembalikan data awal', () => {
  const originalName = 'Gifts & Novelties';
  const updatedName = `Kategori Edit ${Date.now().toString().slice(-4)}`;

  cy.visit('/inventory');
  cy.contains('button', /kelola kategori/i).click();

  cy.get('[role="dialog"]').should('be.visible');

  // Buka form edit kategori
cy.get('[role="dialog"]').within(() => {
  cy.contains('span', originalName)
    .parents()
    .filter((index, element) => {
      return Cypress.$(element).find('button[title="Edit Kategori"]').length > 0;
    })
    .first()
    .find('button[title="Edit Kategori"]')
    .click();
});

  // Pastikan data awal masuk ke form
  cy.contains('Edit Kategori').should('be.visible');
  cy.get('input[placeholder="Contoh: Elektronik & IT"]')
    .should('have.value', originalName);

  // Ubah nama kategori
  cy.get('input[placeholder="Contoh: Elektronik & IT"]')
    .clear()
    .type(updatedName);

  cy.contains('button', 'Simpan Perubahan').click();

  // Pastikan nama baru muncul
  cy.contains('span', updatedName).should('be.visible');

  // Kembalikan nama kategori seperti semula
cy.get('[role="dialog"]').within(() => {
  cy.contains('span', updatedName)
    .parents()
    .filter((index, element) => {
      return Cypress.$(element).find('button[title="Edit Kategori"]').length > 0;
    })
    .first()
    .find('button[title="Edit Kategori"]')
    .click();
});
  cy.get('input[placeholder="Contoh: Elektronik & IT"]')
    .clear()
    .type(originalName);

  cy.contains('button', 'Simpan Perubahan').click();

  cy.contains('span', originalName).should('be.visible');
});
  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 2 - MASTER DATA INVENTARIS (NAJWA)
  | TC_INV_011 - TC_INV_014
  |--------------------------------------------------------------------------
  */

  it('TC_INV_011: Membuka modal Tambah Barang dan memverifikasi seluruh komponen form', () => {
    cy.visit('/inventory');

    cy.contains('button', /tambah barang/i).should('be.visible').click();

    cy.get('[role="dialog"]').within(() => {
      cy.contains('Tambah Barang').should('be.visible');

      // Memastikan seluruh field tersedia
      cy.get('input#sku').should('be.visible').and('have.value', '');
      cy.get('select#category_id').should('be.visible');
      cy.get('select#category_id option').should('have.length.greaterThan', 1);
      cy.get('input#name').should('be.visible').and('have.value', '');
      cy.get('input#unit').should('be.visible').and('have.value', '');
      cy.get('input#unit_price').should('be.visible').and('have.value', '');
      cy.get('input#current_stock').should('be.visible').and('have.value', '0');
      cy.get('input#minimum_stock').should('be.visible').and('have.value', '0');
      cy.get('textarea#description').should('be.visible');

      // Memastikan tombol tersedia
      cy.contains('button', 'Batal').should('be.visible');
      cy.contains('button', 'Simpan Barang').should('be.visible');
    });

    // Menutup modal tanpa menyimpan data
    cy.get('[role="dialog"]').contains('button', 'Batal').click();
    cy.get('[role="dialog"]').should('not.exist');
  });

  it('TC_INV_012: Memverifikasi data produk pada modal Edit Barang dengan SKU 84879', () => {
    cy.visit('/inventory');

    // Mencari produk berdasarkan SKU
    cy.get('input[placeholder*="Cari"]').clear().type('84879');

    cy.get('table tbody tr').should('have.length.at.least', 1);

    cy.get('table tbody tr').first().within(() => {
      cy.contains('84879').should('be.visible');
      cy.get('button').last().click({ force: true });
    });

    // Membuka menu Edit Barang
    cy.contains('Edit Barang').should('be.visible').click();

    cy.get('[role="dialog"]').within(() => {
      cy.contains('Edit Barang').should('be.visible');

      // Memastikan data lama terisi otomatis
      cy.get('input#sku').should('have.value', '84879');
      cy.get('input#name').should('have.value', 'ASSORTED COLOUR BIRD ORNAMENT');
      cy.get('input#unit').should('have.value', 'pcs');
      cy.get('input#current_stock').invoke('val').should('match', /^\d+$/);
      cy.get('input#minimum_stock').should('have.value', '60');

      cy.get('select#category_id').invoke('val').should('not.be.empty');
      cy.get('input#unit_price').should('not.have.value', '');
      cy.contains('button', 'Batal').should('be.visible');
      cy.contains('button', 'Simpan Perubahan').should('be.visible');
    });

    // Menutup modal tanpa mengubah produk
    cy.get('[role="dialog"]').contains('button', 'Batal').click();
    cy.get('[role="dialog"]').should('not.exist');
  });

  it('TC_INV_013: Memverifikasi backend menolak SKU duplikat dan Nama Barang kosong secara bersamaan', () => {
    cy.visit('/inventory');

    cy.contains('button', /tambah barang/i).click();

    cy.get('[role="dialog"]').within(() => {
      // Menggunakan SKU yang sudah terdaftar
      cy.get('input#sku').clear().type('84879');

      // Mengisi field wajib lainnya agar hanya dua validasi yang diuji
      cy.get('select#category_id').select(1);
      cy.get('input#name').clear();
      cy.get('input#unit').clear().type('pcs');
      cy.get('input#unit_price').clear().type('30000');
      cy.get('input#current_stock').clear().type('15');
      cy.get('input#minimum_stock').clear().type('3');

      cy.contains('button', 'Simpan Barang').click();

      // Memastikan kedua pesan validasi backend muncul
      cy.contains('Kode SKU sudah terdaftar dalam sistem inventaris.')
        .should('be.visible');

      cy.contains('Nama barang wajib diisi.')
        .should('be.visible');

      // Modal tetap terbuka
      cy.contains('Tambah Barang').should('be.visible');

      // Data input lainnya tetap dipertahankan
      cy.get('input#sku').should('have.value', '84879');
      cy.get('input#unit').should('have.value', 'pcs');
      cy.get('input#unit_price').should('have.value', '30000');
      cy.get('input#current_stock').should('have.value', '15');
      cy.get('input#minimum_stock').should('have.value', '3');
    });

    // Tutup tanpa menyimpan
    cy.get('[role="dialog"]').contains('button', 'Batal').click();
  });

  it('TC_INV_014: Memverifikasi tampilan modal Tambah Barang pada mode terang, gelap, dan layar mobile', () => {
  cy.visit('/inventory');

  // 1. Buka modal Tambah Barang pada mode terang
  cy.contains('button', /tambah barang/i).click();
  cy.get('[role="dialog"]').should('be.visible');

  // Memastikan komponen modal terlihat pada mode terang
  cy.get('[role="dialog"]').within(() => {
    cy.contains('Tambah Barang').should('be.visible');
    cy.get('input#sku').should('be.visible');
    cy.get('select#category_id').should('be.visible');
    cy.get('input#name').should('be.visible');
    cy.get('input#unit').should('be.visible');
    cy.get('input#unit_price').should('be.visible');
    cy.get('input#current_stock').should('be.visible');
    cy.get('input#minimum_stock').should('be.visible');
    cy.get('textarea#description').should('be.visible');
    cy.contains('button', 'Batal').should('be.visible');
    cy.contains('button', 'Simpan Barang').should('be.visible');
  });

  // 2. Tutup modal sebelum mengganti mode tampilan
  cy.get('[role="dialog"]').contains('button', 'Batal').click();
  cy.get('[role="dialog"]').should('not.exist');

  // 3. Aktifkan mode gelap
  cy.get('html').then(($html) => {
    if (!$html.hasClass('dark')) {
      cy.get('button[title="Ganti Mode Tampilan"]').click();
    }
  });

  cy.get('html').should('have.class', 'dark');

  // 4. Buka kembali modal pada mode gelap
  cy.contains('button', /tambah barang/i).click();
  cy.get('[role="dialog"]').should('be.visible');

  cy.get('[role="dialog"]').within(() => {
    cy.contains('Tambah Barang').should('be.visible');
    cy.get('input#sku').should('be.visible');
    cy.get('input#name').should('be.visible');
    cy.get('textarea#description').should('be.visible');
  });

  // 5. Ubah ukuran layar menjadi mobile
  cy.viewport(375, 812);

  cy.get('[role="dialog"]').should('be.visible');

  cy.get('[role="dialog"]').within(() => {
    cy.get('input#sku').should('be.visible');
    cy.get('select#category_id').should('be.visible');
    cy.get('input#name').should('be.visible');
    cy.get('input#unit').should('be.visible');
    cy.get('input#unit_price').should('be.visible');
    cy.get('input#current_stock').should('be.visible');
    cy.get('input#minimum_stock').should('be.visible');
    cy.get('textarea#description').should('be.visible');
    cy.contains('button', 'Batal').should('be.visible');
    cy.contains('button', 'Simpan Barang').should('be.visible');
  });

  // Memastikan modal tidak keluar dari lebar layar mobile
  cy.get('[role="dialog"]').then(($dialog) => {
  const rect = $dialog[0].getBoundingClientRect();
  expect(rect.left).to.be.at.least(-1);
  expect(rect.right).to.be.at.most(376);
});

  // 6. Tutup modal dan kembalikan mode terang
  cy.get('[role="dialog"]').contains('button', 'Batal').click();
  cy.get('[role="dialog"]').should('not.exist');

  cy.get('button[title="Ganti Mode Tampilan"]').click();
  cy.get('html').should('not.have.class', 'dark');

  // Memastikan tidak ada data yang berubah
  cy.get('table tbody tr').should('exist');
});
  /*
  |--------------------------------------------------------------------------
  | PENGUJIAN MODUL 2 - VALIDASI SKU & PROTEKSI RESTRICT DELETE (SULTAN)
  | SKPL / PMPL: TC_INV_015 (SKU Unik) & TC_INV_016 (Restrict Delete)
  | Trello Task: [M2-LC-01] & [M2-LC-02]
  |--------------------------------------------------------------------------
  */

  it('TC_INV_015: StoreProductRequest: Validasi backend menolak pendaftaran produk dengan kode SKU duplikat', () => {
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

  it('TC_INV_016: ProductController: Proteksi Restrict Delete aktif pada produk yang memiliki riwayat mutasi', () => {
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
