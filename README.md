# 🛠️ Development & Git Guide

To keep our codebase from breaking, everyone must strictly follow this Git workflow.

---

## 📂 1. Branch Naming Rules
Never branch off or push directly to `main`. Always create your branch from an up-to-date `main` using these exact naming rules:


| Your Role / Task            | Branch Name Format                 | Real-World Example |
| :--- | :---                 | :---                               |
| **Frontend Features**       | `your-name/frontend/ticket-title`  | `jake/frontend/sidebar-icons` |
| **Backend Features**        | `your-name/backend/ticket-title`   | `jacob/backend/auth-middleware` |
| **Scrum Masters**           | `your-name/SM-team/branch-purpose` | `sarah/SM-team/repo-cleanup` |
| **Bug Fixes**               | `bugfix/ticket-title`              | `bugfix/vite-config-crash` |

---

## 💻 2. Your Daily Workflow (Terminal Commands)
Follow these exact terminal steps in order whenever you work on a task:

### 🔄 Step A: Sync your machine with production
Before making a branch, make sure your local machine has the latest remote updates:
```bash
git checkout main
git pull origin main
```

### 🌿 Step B: Create your feature branch
Create and switch to your new branch. Ensure it perfectly matches the formats in Section 1:
```bash
git checkout -b your-name/frontend/sidebar-icons
```

### 🚀 Step C: Your VERY FIRST Push (Setting Upstream)
The first time you push your brand-new branch to GitHub, you **must** link it to the server using the upstream flag. Run this exact command:
```bash
git push -u origin HEAD
```
*💡 Tip: Using `HEAD` automatically copies your current branch name, preventing typing errors.*

### 💾 Step D: All Routine Daily Pushes
For all subsequent daily progress updates on this specific task, track, commit, and upload sequentially:
```bash
git add .
git commit -m "feat: short description of what you changed"
git push
```

---

## 🚦 3. Getting Your Code Merged (The Scrum Master Handoff)
Once your assignment is complete, follow this process to submit your work:

- [ ] **1. Open a Pull Request**: Go to GitHub and open a PR from your feature branch into the target `main` branch.
- [ ] **2. Use the Template**: Fill out the PR description using our official **🚀 PULL REQUEST DETAILS** template.
- [ ] **3. Assign Reviewers**: Navigate to the right-hand panel on the PR page, click **Reviewers**, and tag your team's handle:
  * **If Frontend**: Tag `@lcs-cohort-17/frontend-sm`
  * **If Backend**: Tag `@lcs-cohort-17/backend-sm`
- [ ] **4. Hands Off**: Your job is done here! Your Scrum Master will test your build locally. Once they approve it, they will personally escalate it to the Product Owners for final deployment.
