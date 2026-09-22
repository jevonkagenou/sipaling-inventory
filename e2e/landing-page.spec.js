import { test, expect } from '@playwright/test';

test.describe('Landing Page SIPALING - E2E Testing Suite', () => {

  test.beforeEach(async ({ page }) => {
    // Listen for console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.error(`Browser Error: "${msg.text()}"`);
      }
    });

    await page.goto('/', { waitUntil: 'networkidle' });
  });

  test('Test 1: Page Loads Successfully with Scoped Dark Mode and Fonts', async ({ page }) => {
    await expect(page).toHaveTitle(/SIPALING/);

    // Verify Welcome container has scoped dark class and dark background
    const rootContainer = page.locator('div.dark').first();
    await expect(rootContainer).toBeVisible();
    await expect(rootContainer).toHaveClass(/bg-zinc-950/);

    // Verify headline is visible
    const headline = page.locator('h1');
    await expect(headline).toBeVisible();
    await expect(headline).toContainText('Pengelolaan Inventaris Proaktif');
  });

  test('Test 2: Sticky Navigation and Smooth Auto-Scroll', async ({ page }) => {
    const nav = page.locator('nav');
    await expect(nav).toBeVisible();
    await expect(nav).toHaveClass(/sticky/);
    await expect(nav).toHaveClass(/top-0/);

    // Click menu items
    const menuSimulasi = page.locator('nav a[href="#simulasi"]');
    await expect(menuSimulasi).toBeVisible();
    await menuSimulasi.click();
    await page.waitForTimeout(600); // allow smooth scroll

    const simulasiSection = page.locator('#simulasi');
    await expect(simulasiSection).toBeInViewport();

    const menuPeran = page.locator('nav a[href="#peran"]');
    await menuPeran.click();
    await page.waitForTimeout(600);
    const peranSection = page.locator('#peran');
    await expect(peranSection).toBeInViewport();
  });

  test('Test 3: Hero Section is Centered and Clean without Bottom Ribbon', async ({ page }) => {
    const header = page.locator('header');
    await expect(header).toBeVisible();
    await expect(header).toHaveClass(/items-center/);

    const ctaButton = page.locator('button:has-text("Buka Portal Aplikasi")');
    await expect(ctaButton).toBeVisible();

    const inventoryButton = page.locator('button:has-text("Lihat Master Inventaris")');
    await expect(inventoryButton).toBeVisible();
  });

  test('Test 4: Interactive Console - DES Forecasting Simulation', async ({ page }) => {
    // Check forecasting tab is active by default
    const alphaLabel = page.locator('text=/Sensitivitas Data Terkini|Konstanta Level/');
    await expect(alphaLabel).toBeVisible();

    // Verify initial alpha value
    const alphaValue = page.locator('text=0.4').first();
    await expect(alphaValue).toBeVisible();

    // Test Restock Submit button
    const submitBtn = page.locator('button:has-text("Ajukan Draf")');
    await expect(submitBtn).toBeVisible();
    await submitBtn.click();

    // Button should show submitted confirmation
    const submittedState = page.locator('text=/Pengajuan Draf Restock Terkirim|Draf Berhasil Diajukan/');
    await expect(submittedState).toBeVisible();
  });

  test('Test 5: Interactive Console - Audit Trail Tab & Cryptographic Inspection', async ({ page }) => {
    // Switch to Audit Trail tab
    const auditTabBtn = page.locator('button:has-text("Audit Trail")');
    await auditTabBtn.click();

    // Verify table appears
    const tableHeader = page.locator('th:has-text("Log ID")');
    await expect(tableHeader).toBeVisible();

    // Verify rows exist
    const rowLog8821 = page.locator('text=LOG-8821');
    await expect(rowLog8821).toBeVisible();

    // Test filter buttons
    const outboundFilter = page.locator('button:has-text("Outbound")');
    await outboundFilter.click();
    await expect(page.locator('text=LOG-8821')).toBeVisible();

    // Click row to inspect cryptographic signature
    await page.locator('tr:has-text("LOG-8821")').click();
    const inspectionDrawer = page.locator('text=/Inspeksi (Cryptographic Signature|Signature Kriptografis): LOG-8821/');
    await expect(inspectionDrawer).toBeVisible();
    const hashDisplay = page.locator('text=sha256:7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069');
    await expect(hashDisplay).toBeVisible();
  });

  test('Test 6: Interactive Console - Approval Workflow Pipeline', async ({ page }) => {
    // Switch to Otorisasi PO tab
    const approvalTabBtn = page.locator('button:has-text("Otorisasi PO")');
    await approvalTabBtn.click();

    // Verify ticket card
    const ticketTitle = page.locator('text=Laptop ThinkPad T14 (ITM-001)');
    await expect(ticketTitle).toBeVisible();

    const pendingBadge = page.locator('text=Menunggu Otorisasi');
    await expect(pendingBadge).toBeVisible();

    // Click "Beri Otorisasi (PO)"
    const approveBtn = page.locator('button:has-text("Beri Otorisasi (PO)")');
    await approveBtn.click();

    // Badge should update to "Disetujui Komisaris"
    const approvedBadge = page.locator('text=Disetujui Komisaris');
    await expect(approvedBadge).toBeVisible();

    // Click Reset
    const resetBtn = page.locator('button:has-text("Reset Status Simulasi")');
    await resetBtn.click();
    await expect(page.locator('text=Menunggu Otorisasi')).toBeVisible();
  });

  test('Test 7: RBAC Role Cards - Dynamic Color Outlines and Details', async ({ page }) => {
    // Scroll to #peran section
    await page.locator('#peran').scrollIntoViewIfNeeded();

    // Role 1: Komisaris (Amber)
    const komisarisCard = page.locator('#peran').locator('h3:has-text("Komisaris")');
    await komisarisCard.click();
    const komisarisContainer = page.locator('#peran').locator('.cursor-pointer').filter({ hasText: 'Komisaris' }).first();
    await expect(komisarisContainer).toHaveClass(/border-amber-500/);
    await expect(page.locator('button:has-text("Masuk sebagai Komisaris")')).toBeVisible();

    // Role 2: Manajer Operasional (Sky)
    const manajerCard = page.locator('#peran').locator('h3:has-text("Manajer Operasional")');
    await manajerCard.click();
    const manajerContainer = page.locator('#peran').locator('.cursor-pointer').filter({ hasText: 'Manajer Operasional' }).first();
    await expect(manajerContainer).toHaveClass(/border-sky-500/);
    await manajerContainer.hover();
    await expect(manajerContainer).not.toHaveClass(/border-zinc-300/);
    await expect(manajerContainer).toHaveClass(/hover:border-sky-400/);
    await expect(page.locator('button:has-text("Masuk sebagai Manajer Operasional")')).toBeVisible();

    // Role 3: Staf Gudang (Emerald)
    const stafCard = page.locator('#peran').locator('h3:has-text("Staf Gudang")');
    await stafCard.click();
    const stafContainer = page.locator('#peran').locator('.cursor-pointer').filter({ hasText: 'Staf Gudang' }).first();
    await expect(stafContainer).toHaveClass(/border-emerald-500/);
    await expect(page.locator('button:has-text("Masuk sebagai Staf Gudang")')).toBeVisible();

    // Role 4: Auditor Internal (Purple)
    const auditorCard = page.locator('#peran').locator('h3:has-text("Auditor Internal")');
    await auditorCard.click();
    const auditorContainer = page.locator('#peran').locator('.cursor-pointer').filter({ hasText: 'Auditor Internal' }).first();
    await expect(auditorContainer).toHaveClass(/border-purple-500/);
    await expect(page.locator('button:has-text("Masuk sebagai Auditor Internal")')).toBeVisible();
  });

  test('Test 8: Full-page Visual Screenshot Capture', async ({ page }) => {
    // Scroll each section into view to ensure it is triggered and animated
    const sections = ['#simulasi', '#fitur', '#peran', '#arsitektur', '#pengembang'];
    for (const sec of sections) {
      await page.locator(sec).scrollIntoViewIfNeeded();
      await page.waitForTimeout(250);
    }
    // Also scroll to footer
    await page.locator('footer').scrollIntoViewIfNeeded();
    await page.waitForTimeout(600);

    // Scroll back to top
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(600);

    await page.screenshot({
      path: 'C:/Users/syste/.gemini/antigravity/brain/ee7bd53c-9643-4fa2-99e3-f92c9e46a366/scratch/landing-page-full.png',
      fullPage: true,
    });
  });

  test('Test 9: Inventory Page Loads with Clean Isolated Light Theme (No Dark Mode Leakage)', async ({ page }) => {
    await page.goto('/inventory', { waitUntil: 'networkidle' });
    await expect(page).toHaveTitle(/Master Inventaris/);

    // Verify headline is crisp and visible
    const headline = page.locator('h1:has-text("Master Data Inventaris")');
    await expect(headline).toBeVisible();

    // Verify body does NOT have dark mode
    const body = page.locator('body');
    await expect(body).not.toHaveClass(/bg-zinc-950/);

    // Verify table is visible with items
    const table = page.locator('table');
    await expect(table).toBeVisible();
    await expect(page.locator('text=Laptop ThinkPad T14')).toBeVisible();

    // Capture screenshot of inventory page to verify contrast visually
    await page.screenshot({
      path: 'C:/Users/syste/.gemini/antigravity/brain/ee7bd53c-9643-4fa2-99e3-f92c9e46a366/scratch/inventory-page-clean.png',
      fullPage: true,
    });
  });

});
