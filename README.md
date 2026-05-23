# Paper Class Results System
## Deployment Guide for InfinityFree

---

### What This System Does

- Students enter their **index number** to view all paper results
- Each paper shows: **Marks, Percentage, Z-Score, Rank** (calculated per paper)
- Admin portal to **add students, papers, and enter marks**
- Admin page is completely hidden from students

---

### Files Overview

```
/
├── index.php          ← Student lookup page (public)
├── results.php        ← Results display page (public)
├── install.php        ← One-time setup wizard (DELETE after use!)
├── db.php             ← Database credentials (edit this first)
├── setup.sql          ← SQL schema (used by install.php)
├── assets/
│   └── style.css      ← Shared styles
└── admin/
    ├── login.php      ← Admin login
    ├── index.php      ← Dashboard
    ├── students.php   ← Add/edit/delete students
    ├── papers.php     ← Add/edit/delete papers
    ├── marks.php      ← Enter marks per paper
    ├── logout.php
    ├── auth.php       ← Session guard (do not delete)
    └── sidebar.php    ← Admin nav (do not delete)
```

---

### Step-by-Step Deployment on InfinityFree

#### 1. Create Your Hosting Account
- Sign up at **infinityfree.com**
- Create a new hosting account
- Note your **FTP credentials** and **cPanel URL**

#### 2. Create a MySQL Database
1. Log in to your **VistaPanel** (InfinityFree cPanel)
2. Go to **MySQL Databases**
3. Create a new database — note the **full database name** (e.g. `epiz_12345678_mydb`)
4. Create a database user with a password
5. Add the user to the database with **All Privileges**
6. Note:
   - **Host**: `sql200.infinityfree.com` (or as shown in your panel)
   - **Username**: your DB username
   - **Password**: your DB password
   - **Database name**: full database name

#### 3. Edit db.php
Open `db.php` and replace the placeholders:

```php
define('DB_HOST', 'sql200.infinityfree.com');   // Your MySQL host
define('DB_USER', 'epiz_12345678_user');         // Your DB username
define('DB_PASS', 'yourpassword');               // Your DB password
define('DB_NAME', 'epiz_12345678_mydb');         // Your full DB name
```

#### 4. Upload Files via FTP
1. Open **FileZilla** (free FTP client)
2. Connect using your FTP credentials from InfinityFree panel
3. Navigate to the `htdocs` folder on the server
4. Upload ALL files maintaining the folder structure

#### 5. Run the Installer
1. Visit `https://yourdomain.infinityfreeapp.com/install.php`
2. If the DB connection is successful, create your admin username and password
3. Click **Install**

#### 6. 🔴 DELETE install.php
**This is critical for security!**
- Use FTP or the File Manager in VistaPanel to delete `install.php`
- If someone else runs it, they can reset your admin password!

#### 7. Access the System
- **Student Portal**: `https://yourdomain.infinityfreeapp.com/`
- **Admin Login**: `https://yourdomain.infinityfreeapp.com/admin/login.php`

---

### How to Use

#### Adding Students
1. Admin login → Students
2. Enter: Index Number (unique), Full Name, Batch Year (optional)
3. Students use their **Index Number** to check results

#### Adding Papers
1. Admin → Papers
2. Enter: Paper Number, Paper Name, Date, Total Marks, Batch Year

#### Entering Marks
1. Admin → Marks
2. Select a paper from the top buttons
3. Enter marks for each student (leave blank to skip)
4. Click **Save All Marks**

Z-Scores and Ranks are **automatically calculated** and shown live.

---

### Z-Score Formula

```
Z = (Student Marks − Class Mean) / Standard Deviation
```

- **Z > 1.5** → Excellent
- **Z 0.5–1.5** → Above Average
- **Z -0.5–0.5** → Average
- **Z < -1.5** → Needs Improvement

---

### Security Notes

- The `admin/` folder is protected by PHP session — not visible to students
- Visitors who try `yourdomain.com/admin/` are redirected to login
- Change your admin password to something strong
- Delete `install.php` after setup

---

### Troubleshooting

| Problem | Solution |
|---------|----------|
| "Database connection failed" | Check db.php credentials, ensure DB host matches InfinityFree's MySQL host |
| Blank white page | Enable PHP error display or check error logs in VistaPanel |
| Can't log in to admin | Run install.php again to reset password (then delete it again) |
| Marks not saving | Ensure student and paper exist; check marks don't exceed total |
| Z-score shows "N/A" | Needs at least 2 students with different marks in a paper |

---

© Paper Class Results System. Built with PHP + MySQL.
