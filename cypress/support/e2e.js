// Cypress E2E Support File
import './commands';

// Mencegah error uncaught exception pihak ketiga menghentikan Cypress test
Cypress.on('uncaught:exception', (err, runnable) => {
  // Mengembalikan false agar Cypress tidak otomatis gagal jika ada exception non-fatal di client browser
  return false;
});
