# 🚀 Deployment Guide - Smart Hostel Management System

This guide explains how to deploy your Smart Hostel Complaint Management System live on the internet so anyone can access it as a real website.

---

## 🌟 Method 1: Render.com (Recommended — 100% Free & Automatic)

Render is the easiest, most reliable free cloud platform for PHP applications. It connects directly to your GitHub repository and automatically redeploys whenever you push changes.

### Step-by-Step Instructions:
1. Go to **[https://render.com](https://render.com)** and click **Sign Up** (or Log In) with your **GitHub** account.
2. In the Render Dashboard, click the **New +** button at the top right and select **Web Service**.
3. Under **Connect a repository**, find and select:
   ```
   yanasareen/smart-hostel-management-system
   ```
4. Fill in the service settings:
   - **Name**: `smart-hostel-system` (or any name you like)
   - **Region**: Choose the closest region (e.g., `Singapore` or `Frankfurt` or `Oregon`)
   - **Environment**: `Docker` *(Render will automatically detect the Dockerfile!)*
   - **Instance Type**: `Free` ($0/month)
5. Under **Environment Variables** (optional, recommended):
   - Add Key: `DB_TYPE`
   - Add Value: `sqlite`
6. Click **Deploy Web Service** at the bottom.
7. Wait 2 to 3 minutes while Render builds and launches your site.
8. Once finished, Render will provide your public live HTTPS URL, such as:
   ```
   https://smart-hostel-system.onrender.com
   ```

---

## ⚡ Method 2: Railway.app (Fast 1-Click Cloud Deployment)

Railway gives you a quick cloud deployment with free monthly trial credits.

### Step-by-Step Instructions:
1. Go to **[https://railway.app](https://railway.app)** and sign in with GitHub.
2. Click **New Project** -> **Deploy from GitHub repo**.
3. Select `smart-hostel-management-system`.
4. Railway will automatically detect the `Dockerfile` and start the build.
5. Once deployed, click on the service card -> **Settings** tab -> click **Generate Domain** under *Networking*.
6. Your live website is now accessible at `https://your-app.up.railway.app`.

---

## 🌐 Method 3: InfinityFree (Free Traditional PHP + MySQL cPanel Hosting)

If you prefer traditional cPanel hosting with free MySQL phpMyAdmin:

1. Sign up for a free account at **[infinityfree.com](https://www.infinityfree.com)**.
2. Create a new hosting account and pick a free subdomain (e.g., `smarthostel.infinityfreeapp.com`).
3. Open **Control Panel** -> **MySQL Databases**, create a database `if0_xxxx_hostel`.
4. Open **phpMyAdmin**, click your database, and import `schema.sql`.
5. Open the **Online File Manager** (or connect via FileZilla FTP) and upload all files from this project into the `htdocs` directory.
6. Open `config.php` in the file manager and set your InfinityFree database credentials:
   - `DB_HOST`: Your MySQL host (e.g., `sqlXXX.infinityfree.com`)
   - `DB_NAME`: Your created database name
   - `DB_USER`: Your InfinityFree MySQL username
   - `DB_PASS`: Your InfinityFree vPanel password

---

## ⚡ Method 4: Instant Live Website from Your Mac (Cloudflare Tunnel)

If you have XAMPP running locally and want to show your live website to your professor or classmates immediately without signing up for cloud hosting:

1. Install Cloudflare tunnel using Homebrew:
   ```bash
   brew install cloudflared
   ```
2. Start PHP server or XAMPP:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8000
   ```
3. In another terminal, run:
   ```bash
   cloudflared tunnel --url http://127.0.0.1:8000
   ```
4. Cloudflare will give you an instant public HTTPS URL like:
   `https://random-words.trycloudflare.com`
   Anyone on mobile or PC anywhere in the world can open that link immediately!

---

## 🔑 Evaluation Accounts for Live Testing

Once your website is deployed, you and your evaluators can test all 4 SRS roles:

| Role | Email | Password |
|---|---|---|
| **System Administrator** | `admin@hostel.edu` | `admin123` |
| **Hostel Warden** | `warden@hostel.edu` | `warden123` |
| **Accountant** | `accountant@hostel.edu` | `accountant123` |
| **Student** | `student@hostel.edu` | `student123` |
