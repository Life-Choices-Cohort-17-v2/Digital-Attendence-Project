# Contributing to FuturePath

This guide covers everything you need to know to contribute correctly. Please read your relevant section fully before getting started.

---

## For developers

### Daily workflow

#### Step 1 — Sync with dev

Always start your day by pulling the latest code from `dev`:

```bash
git checkout dev
git pull origin dev
```

This ensures you have everyone's latest approved work before you start.

---

#### Step 2 — Create your feature branch

Create a new branch from `dev` using the correct naming format:

```bash
git checkout -b your-name/feature/screen-name
```

See branch naming rules below.

---

#### Step 3 — First push (setting upstream)

The first time you push your new branch, link it to GitHub:

```bash
git push -u origin HEAD
```

Using `HEAD` automatically copies your branch name — no typing errors.

---

#### Step 4 — Commit regularly throughout the day

Save your progress often:

```bash
git add .
git commit -m "feat: short description of what changed"
git push
```

Do not wait until the end of the day to commit. Small, frequent commits are better than one large one.

---

#### Step 5 — Open a pull request when your task is done

1. Go to the repository on GitHub
2. Open a Pull Request from your feature branch into `dev`
3. Fill in the PR description using the template — every field matters
4. Add a reviewer using the **Reviewers panel** on the right side of the PR page
5. Wait for approval — do not merge your own PR

---

### Branch naming

| Type | Format | Example |
|---|---|---|
| Flutter feature | `your-name/feature/screen-name` | `jake/feature/home-screen` |

---

### Commit message format

| Prefix | When to use | Example |
|---|---|---|
| `feat:` | New screen or feature | `feat: add programme directory screen` |
| `fix:` | Bug fix | `fix: search bar not filtering results` |
| `chore:` | Config or cleanup | `chore: update pubspec dependencies` |
| `style:` | UI tweaks only | `style: adjust card padding on home screen` |

---

### Rules

- Never push directly to `dev` or `main`
- Never merge your own PR
- Always pull from `dev` at the start of the day
- One branch per task — do not mix multiple screens in one branch
- Keep API keys out of the repo — use a `.env` file listed in `.gitignore`
- Delete your feature branch after it has been merged (unless you are fixing code)

---

## Branch structure reference

```
main          ← sprint-complete, demo-ready only
dev           ← active integration branch (source of truth)
your-name/feature/home-screen
your-name/feature/programme-directory
your-name/feature/opportunities
your-name/feature/applicant-profile
bugfix/description
```
