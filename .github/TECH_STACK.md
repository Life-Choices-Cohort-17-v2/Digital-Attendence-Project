# 🛠️ Tech Stack & Getting Started Guide

This document outlines our official technical architecture and provides step-by-step instructions on how to download, install, and execute the Clock-It codebase locally.

---

## 🏗️ 1. The Core Tech Stack



| Layer | Technology | Key Ecosystem Libraries |
| :--- | :--- | :--- |
| **Frontend** | React 19 + TypeScript | Vite 6, Tailwind CSS v4, React Icons |
| **Backend**  | Node.js + Express     | TypeScript, Prisma ORM, JSON Web Tokens |
| **Database** | PostgreSQL            | Hosted Instance |

---

## 💻 2. System Pre-requisites
To avoid dependency locking or compilation failures, your local machine must match our target execution runtimes:

* **Node.js Version:** `v20.x` or `v22.x` (LTS Releases Only)
* **Package Manager:** Standard `npm` (Do not mix with `yarn` or `pnpm` lockfiles)

Verify your system setup before continuing:
```bash
node -v
npm -v
```

---

## 🚀 3. How to Install and Run the Project

Always ensure you have synced your local `main` branch before setting up your workspace environment.

### 🎨 Part A: The Frontend Workspace

#### 1. Navigate to the frontend directory:
```bash
cd frontend
```

#### 2. Install dependencies securely:
```bash
npm install --no-audit --no-fund
```
*💡 Note: The `--no-audit` and `--no-fund` flags prevent the terminal installer from freezing up on Windows environments.*

#### 3. Launch the development server:
```bash
npm run dev
```
Open your browser and navigate to `http://localhost:5173/` to view the application.

---

### ⚙️ Part B: The Backend Workspace

#### 1. Navigate to the backend directory:
```bash
cd ../backend
```

#### 2. Install server-side packages:
```bash
npm install
```

#### 3. Spin up the application server:
```bash
npm run dev
```

---

## 🛑 4. Troubleshooting Fast Fixes

### 🔒 Package Manager Installation Hangs
If your terminal completely freezes while running `npm install`, your local system cache or file trees are corrupted. Force a clean reset using this exact block:
```bash
# 1. Clear the hidden engine cache
npm cache clean --force

# 2. Delete the broken local folders
rm -rf node_modules package-lock.json

# 3. Force a fresh installation bypass
npm install --no-audit --no-fund
```
