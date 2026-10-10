// Cypress Custom Commands for SIPALING

// Helper custom command untuk login berbasis UI
Cypress.Commands.add('loginViaUi', (email = 'manajer@sipaling.com', password = 'password') => {
  cy.visit('/login');
  cy.get('input[type="email"], input#email').clear().type(email);
  cy.get('input[type="password"], input#password').clear().type(password);
  cy.get('button[type="submit"]').click();
});

// Helper custom command untuk login cepat dengan preset role
Cypress.Commands.add('loginAs', (role = 'manajer') => {
  const accounts = {
    'manajer': { email: 'manajer@sipaling.com', password: 'password' },
    'staf': { email: 'staf@sipaling.com', password: 'password' },
    'komisaris': { email: 'komisaris@sipaling.com', password: 'password' },
    'auditor': { email: 'luxoqwasery@gmail.com', password: 'password' },
  };

  const account = accounts[role] || accounts['manajer'];
  cy.loginViaUi(account.email, account.password);
});
