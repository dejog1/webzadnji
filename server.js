const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const app = express();
const PORT = 3000;

// Middleware
app.use(express.json());
app.use(express.static('.'));

// JSON datoteke za bazu
const USERS_FILE = 'users.json';
const PROJECTS_FILE = 'projects.json';

// Kreiraj prazne datoteke ako ne postoje
async function initDB() {
  try {
    // users.json
    try {
      await fs.access(USERS_FILE);
    } catch {
      await fs.writeFile(USERS_FILE, JSON.stringify({
        entrepreneurs: [],
        investors: []
      }, null, 2));
      console.log('users.json kreiran');
    }
    
    // projects.json
    try {
      await fs.access(PROJECTS_FILE);
    } catch {
      await fs.writeFile(PROJECTS_FILE, JSON.stringify({
        projects: []
      }, null, 2));
      console.log('projects.json kreiran');
    }
  } catch (error) {
    console.log('Greska pri inicijalizaciji:', error);
  }
}

// ---------- API ----------
// Registracija
app.post('/api/register', async (req, res) => {
  try {
    const user = req.body;
    user.id = Date.now();
    user.created_at = new Date().toISOString();
    
    // Ucitaj postojece korisnike
    let data;
    try {
      data = JSON.parse(await fs.readFile(USERS_FILE, 'utf8'));
    } catch {
      data = { entrepreneurs: [], investors: [] };
    }
    
    // Provjeri email
    const allUsers = [...data.entrepreneurs, ...data.investors];
    if (allUsers.find(u => u.email === user.email)) {
      return res.status(400).json({ error: 'Email vec postoji' });
    }
    
    // Dodaj korisnika
    if (user.user_type === 'entrepreneur') {
      data.entrepreneurs.push(user);
    } else {
      data.investors.push(user);
    }
    
    // Spremi
    await fs.writeFile(USERS_FILE, JSON.stringify(data, null, 2));
    
    // Vrati bez lozinke
    const { password, ...safeUser } = user;
    res.json({ success: true, user: safeUser });
    
  } catch (error) {
    console.log('Greska:', error);
    res.status(500).json({ error: 'Server error' });
  }
});

// Prijava
app.post('/api/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    
    // Ucitaj korisnike
    let data;
    try {
      data = JSON.parse(await fs.readFile(USERS_FILE, 'utf8'));
    } catch {
      return res.status(401).json({ error: 'Pogresni podaci' });
    }
    
    // Pronadi korisnika
    const allUsers = [...data.entrepreneurs, ...data.investors];
    const user = allUsers.find(u => u.email === email && u.password === password);
    
    if (!user) {
      return res.status(401).json({ error: 'Pogresni podaci' });
    }
    
    // Vrati bez lozinke
    const { password: _, ...safeUser } = user;
    res.json({ success: true, user: safeUser });
    
  } catch (error) {
    res.status(500).json({ error: 'Server error' });
  }
});

// Dohvati korisnike
app.get('/api/users', async (req, res) => {
  try {
    const data = JSON.parse(await fs.readFile(USERS_FILE, 'utf8'));
    const entrepreneurs = data.entrepreneurs.map(({ password, ...rest }) => rest);
    const investors = data.investors.map(({ password, ...rest }) => rest);
    res.json({ entrepreneurs, investors });
  } catch (error) {
    res.status(500).json({ error: 'Server error' });
  }
});

// Dodaj projekt
app.post('/api/projects', async (req, res) => {
  try {
    const project = req.body;
    project.id = Date.now();
    project.created_at = new Date().toISOString();
    project.status = 'active';
    
    let data;
    try {
      data = JSON.parse(await fs.readFile(PROJECTS_FILE, 'utf8'));
    } catch {
      data = { projects: [] };
    }
    
    data.projects.push(project);
    await fs.writeFile(PROJECTS_FILE, JSON.stringify(data, null, 2));
    
    res.json({ success: true, project });
  } catch (error) {
    res.status(500).json({ error: 'Server error' });
  }
});

// Dohvati projekte
app.get('/api/projects', async (req, res) => {
  try {
    const data = JSON.parse(await fs.readFile(PROJECTS_FILE, 'utf8'));
    res.json(data);
  } catch (error) {
    res.json({ projects: [] });
  }
});

// Statistike
app.get('/api/stats', async (req, res) => {
  try {
    let users = { entrepreneurs: 0, investors: 0 };
    let projects = 0;
    
    try {
      const usersData = JSON.parse(await fs.readFile(USERS_FILE, 'utf8'));
      users = {
        entrepreneurs: usersData.entrepreneurs.length,
        investors: usersData.investors.length
      };
    } catch {}
    
    try {
      const projectsData = JSON.parse(await fs.readFile(PROJECTS_FILE, 'utf8'));
      projects = projectsData.projects.length;
    } catch {}
    
    res.json({
      entrepreneurs: users.entrepreneurs,
      investors: users.investors,
      projects: projects,
      messages: 0
    });
  } catch (error) {
    res.json({
      entrepreneurs: 0,
      investors: 0,
      projects: 0,
      messages: 0
    });
  }
});

// Test endpoint
app.get('/api/test', (req, res) => {
  res.json({ 
    message: 'Server radi!',
    time: new Date().toISOString()
  });
});

// Pokreni server
initDB().then(() => {
  app.listen(PORT, () => {
    console.log('='.repeat(40));
    console.log('INVESTIT SERVER POKRENUT');
    console.log('URL: http://localhost:' + PORT);
    console.log('='.repeat(40));
  });
});