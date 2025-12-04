class AuthManager {
  constructor() {
    this.baseUrl = 'http://localhost:3000/api';
    this.init();
  }

  async processAuthentication(userData) {
    try {
      const endpoint = this.isLoginMode ? '/login' : '/register';
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
      });

      const result = await response.json();
      
      if (!response.ok) {
        throw new Error(result.error || 'Došlo je do pogreške');
      }

      if (result.success) {
        this.setUserSession(result.user);
        this.closeAuthModal();
        
        // Preusmjeri na dashboard
        window.location.href = 'dashboard.html';
      }
    } catch (error) {
      this.showError(error.message);
    }
  }

  async checkExistingSession() {
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
      this.currentUser = JSON.parse(savedUser);
      // Redirect to dashboard if user is already logged in
      if (window.location.pathname.endsWith('index.html')) {
        window.location.href = 'dashboard.html';
      }
    }
  }

  // Ostale metode ostaju iste...
}