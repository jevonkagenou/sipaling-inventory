import { defineConfig } from 'cypress';
import { generatePmpleReport } from './cypress/support/report-generator.js';

export default defineConfig({
  e2e: {
    // URL host server lokal Laravel SIPALING
    baseUrl: 'http://127.0.0.1:8000',

    // Resolusi layar standar pengujian antarmuka
    viewportWidth: 1280,
    viewportHeight: 720,

    // Konfigurasi performa
    video: false,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 10000,
    pageLoadTimeout: 30000,

    setupNodeEvents(on, config) {
      // Hook lifecycle: Dijalankan otomatis setelah seluruh pengujian selesai
      on('after:run', async (results) => {
        if (!results) return;

        const triggerTime = new Date();

        // 1. Kirim hasil pengujian ke Webhook n8n di background (Non-blocking)
        const webhookUrls = [
          'http://localhost:5678/webhook/cypress-report',      // Mode Aktif / Production 24/7
          'http://localhost:5678/webhook-test/cypress-report', // Mode Uji Coba n8n (Listening)
        ];

        let n8nTriggered = false;
        let triggeredEndpoint = 'http://localhost:5678/webhook/cypress-report';

        for (const url of webhookUrls) {
          try {
            const res = await fetch(url, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(results),
            });
            if (res.ok) {
              n8nTriggered = true;
              triggeredEndpoint = url;
              console.log(`\n[n8n Automation] Webhook ter-trigger: ${url} (HTTP ${res.status})`);
              break;
            }
          } catch (_) {
            // Abaikan jika n8n offline / tidak aktif
          }
        }

        // 2. Generate Laporan HTML PMPL Mandiri (Selalu Diperbarui Seketika!)
        try {
          generatePmpleReport(results, {
            n8nTriggered: n8nTriggered,
            endpoint: triggeredEndpoint,
            timestamp: triggerTime,
          });
        } catch (e) {
          console.error('Gagal menghasilkan laporan HTML PMPL:', e);
        }
      });

      on('task', {
        log(message) {
          console.log(message);
          return null;
        },
      });

      return config;
    },
  },
});
