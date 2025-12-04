class DashboardManager {
  constructor() {
    this.baseUrl = 'http://localhost:3000/api';
    this.init();
  }

  async loadDashboardData() {
    try {
      // Učitaj statistike
      const statsResponse = await fetch(`${this.baseUrl}/stats`);
      const stats = await statsResponse.json();
      
      document.getElementById('projectsCount').textContent = stats.projects || 0;
      document.getElementById('investorsCount').textContent = stats.investors || 0;
      document.getElementById('messagesCount').textContent = 0;

      // Učitaj korisnike za preporuku
      const usersResponse = await fetch(`${this.baseUrl}/users`);
      const usersData = await usersResponse.json();
      
      if (this.currentUser.user_type === 'entrepreneur') {
        this.displayInvestors(usersData.investors.slice(0, 4));
      } else {
        // Učitaj projekte za investitore
        const projectsResponse = await fetch(`${this.baseUrl}/projects`);
        const projectsData = await projectsResponse.json();
        this.displayProjects(projectsData.projects.slice(0, 6));
      }
    } catch (error) {
      console.error('Error loading dashboard data:', error);
      this.showNotification('Greška pri učitavanju podataka', 'error');
    }
  }

  async handleProjectSubmit(e) {
    e.preventDefault();
    
    const formData = {
      title: document.getElementById('projectTitle').value,
      description: document.getElementById('projectDescription').value,
      funding: parseInt(document.getElementById('projectFunding').value),
      category: document.getElementById('projectCategory').value,
      entrepreneur_id: this.currentUser.id,
      entrepreneur_name: this.currentUser.name
    };

    try {
      const response = await fetch(`${this.baseUrl}/projects`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      });

      const result = await response.json();
      
      if (result.success) {
        document.getElementById('projectModal').style.display = 'none';
        document.getElementById('projectForm').reset();
        
        // Osvježi dashboard
        this.loadDashboardData();
        this.showNotification('Projekt je uspješno objavljen!', 'success');
      }
    } catch (error) {
      this.showNotification('Greška pri objavi projekta', 'error');
    }
  }

  // Ostale metode ostaju iste...
}