# Deploying SEO · AEO · Performance Report on Render (Free Tier)

This guide covers how to deploy this application to **Render Free** with zero local PHP installation required.

---

## 1. Quick Overview

- **Hosting platform:** [Render](https://render.com) (Web Service, Free instance).
- **Environment:** Docker (`php:8.2-apache`).
- **Data storage:** Ephemeral disk (`cache/` folder stores results for 24 hours while container is active).
- **Security:**
  - `app/` and `cache/` are blocked from public browser access.
  - Optional HTTP Basic Authentication protects the site for your internal team (5 people).
  - API keys and passwords are kept securely in Render Environment Variables (never committed to git).

---

## 2. Where and How to Configure Your API Key (`VEBAPI_KEY`)

You do **not** need to edit any code or commit your key to GitHub. Render injects the key securely as an environment variable.

### In the Render Dashboard:
1. Open your Web Service in [Render Dashboard](https://dashboard.render.com).
2. Click **Environment** in the left sidebar.
3. Under **Environment Variables**, click **Add Environment Variable**:
   - **Key:** `VEBAPI_KEY`
   - **Value:** `your_actual_vebapi_key_here` (paste your real key from VebAPI)
4. Click **Save Changes**. Render will automatically redeploy with the key active.

> **Testing with sample data:** If you ever want to preview without using VebAPI credits, set `VEBAPI_KEY` to `demo`.

---

## 3. How to Set Up Team Password Protection (Optional but Recommended)

Because this tool is for internal team use only (max 5 people), you can add a shared username and password prompt using Render environment variables:

1. In your service's **Environment** tab, add:
   - **Key:** `AUTH_USER` &rarr; **Value:** e.g. `team`
   - **Key:** `AUTH_PASSWORD` &rarr; **Value:** e.g. `MySecurePassword123`
2. Click **Save Changes**.

When anyone opens your Render URL, the browser will prompt for the username and password before granting access.
*(The health check `/healthz.php` remains accessible so Render's automated monitor doesn't trigger false alarms).*

---

## 4. How to Deploy (Step-by-Step)

### Option A: Using the Render Blueprint (`render.yaml` - Easiest)
1. Push your repository to **GitHub** (public or private).
2. Go to [Render Dashboard](https://dashboard.render.com) and click **New +** &rarr; **Blueprint**.
3. Connect your GitHub repository.
4. Render will read `render.yaml` automatically and prompt you to input:
   - `VEBAPI_KEY`: Paste your VebAPI key.
   - `AUTH_USER` *(optional)*: Your team login username.
   - `AUTH_PASSWORD` *(optional)*: Your team login password.
5. Click **Apply**. Render will build the Docker container and deploy the app.

---

### Option B: Manual Web Service Setup
1. Push your repository to **GitHub**.
2. Go to [Render Dashboard](https://dashboard.render.com) and click **New +** &rarr; **Web Service**.
3. Select **Build and deploy from a Git repository** and connect your repository.
4. Fill in the service details:
   - **Name:** `seo-aeo-performance-report` (or your preferred name)
   - **Region:** Any region close to you (e.g. `Oregon (US West)` or `Frankfurt (EU Central)`)
   - **Branch:** `main`
   - **Runtime:** `Docker`
   - **Dockerfile Path:** `./Dockerfile`
   - **Instance Type:** `Free`
5. Scroll down to **Environment Variables** and add:
   - `VEBAPI_KEY` = `your_actual_vebapi_key_here`
   - `AUTH_USER` = *(optional, e.g. `team`)*
   - `AUTH_PASSWORD` = *(optional, e.g. `your_password`)*
6. Click **Advanced**:
   - **Health Check Path:** `/healthz.php`
7. Click **Create Web Service**.

---

## 5. What Happens During Build & Run

1. **Build Phase (on Render's servers):**
   - Render downloads `php:8.2-apache`.
   - Copies your application code.
   - Configures Apache modules (`rewrite`, `headers`, `auth_basic`).
   - Runs syntax checks (`php -l`) on every `.php` file.
   - Runs a demo check (`app/core.php`) to confirm no fatal runtime errors.
2. **Start Phase:**
   - Detects the Render port (`$PORT`) and binds Apache to it.
   - Sets up HTTP Basic Auth if credentials are provided.
   - Sets ownership and `775` permissions on `cache/`.
   - Starts Apache in the foreground.

---

## 6. How the Free Tier Behaves

- **Sleep after inactivity:** Render Free spins down after 15 minutes of zero traffic.
- **Waking up:** When a team member opens the URL, Render wakes up in ~40–60 seconds.
- **Cache retention:** The file cache in `cache/` remains valid during an active session (24h TTL). If the free instance spins down or redeploys, new checks simply fetch fresh data from VebAPI.
