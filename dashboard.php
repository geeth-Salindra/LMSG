<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// All authenticated roles share the current system features.
if (empty($_SESSION['user_id']) && empty($_SESSION['learner_id'])) {
    header('Location: login.php');
    exit;
}

$learnerName = $_SESSION['user_name'] ?? $_SESSION['learner_name'];
$roleLabel = ucwords(str_replace('_', ' ', $_SESSION['user_role'] ?? 'learner'));
$isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'super_admin';
$isInstituteAdmin = ($_SESSION['user_role'] ?? '') === 'institute_admin';
$canManageLearners = $isSuperAdmin || $isInstituteAdmin;

// First name for the welcome text, first letter for the round avatar
$firstName = explode(' ', trim($learnerName))[0];
$initial   = mb_strtoupper(mb_substr($learnerName, 0, 1));
$initialView = $initialView ?? 'dashboard';
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · LMSG</title>
<style>
  :root{
    --bg:#F2F5F2;
    --card:#FFFFFF;
    --border:#E2E9E3;
    --text:#18221B;
    --muted:#637067;
    --accent:#2F8152;
    --soft:#EAF2EC;
    --grad:linear-gradient(135deg,#174A30 0%,#2F8152 100%);
    --sidebar-w:250px;
    --header-height:67px;
    --sidebar-bg:#111814;
    --sidebar-border:#29382F;
    --sidebar-text:#F1F5F2;
    --sidebar-muted:#B2C0B6;
    --sidebar-button:#1B241E;
    --sidebar-button-hover:#25342A;
    --sidebar-button-active:#2F8152;
  }

  *{box-sizing:border-box;}
  body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
  }
  button{font-family:inherit;cursor:pointer;}

  /* ---------- Page layout: sidebar on the left, main area on the right ---------- */
  .app{display:flex;min-height:100vh;}

  /* ---------- Sidebar ---------- */
  .sidebar{
    display:flex;
    flex-direction:column;
    width:var(--sidebar-w);
    background:var(--sidebar-bg);
    border-right:1px solid var(--sidebar-border);
    color:var(--sidebar-text);
    padding:0 14px 20px;
    position:sticky;
    top:0;
    height:100vh;
    flex-shrink:0;
  }
  .brand{display:flex;align-items:center;gap:12px;height:var(--header-height);flex-shrink:0;margin:0 -14px;padding:4px 24px;border-bottom:1px solid var(--sidebar-border);}
  .brand-mark{
    width:40px;height:40px;border-radius:12px;
    background:linear-gradient(135deg,#237747,#43A96C);color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:15px;
  }
  .brand-name{font-weight:700;font-size:16px;}
  .brand-sub{font-size:12px;color:var(--sidebar-muted);}

  .nav-btn{
    width:100%;
    display:flex;align-items:center;gap:12px;
    padding:12px 14px;
    margin-bottom:6px;
    border:0;
    border-radius:12px;
    background:var(--sidebar-button);
    color:var(--sidebar-text);
    font-size:14px;
    font-weight:600;
    text-align:left;
  }
  .nav-link{text-decoration:none;}
  .brand + .nav-btn{margin-top:30px;margin-bottom:10px;}
  .nav-btn svg{width:20px;height:20px;flex-shrink:0;}
  .nav-btn:hover{background:var(--sidebar-button-hover);color:#fff;}
  .nav-btn.active{background:var(--sidebar-button-active);color:#fff;}
  .profile-nav{margin-top:auto;}
  .manage-nav{margin-top:auto;}
  .sidebar-logout{
    display:flex;
    align-items:center;
    gap:12px;
    width:100%;
    padding:12px 14px;
    margin-top:6px;
    border:0;
    border-radius:12px;
    background:#DC2626;
    color:#fff;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
  }
  .sidebar-logout:hover{background:#B91C1C;}
  .sidebar-logout svg{width:20px;height:20px;flex-shrink:0;}

  /* ---------- Main area ---------- */
  .main{flex:1;min-width:0;display:flex;flex-direction:column;}

  .topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    height:var(--header-height);
    padding:14px 32px;
    background:var(--card);
    border-bottom:1px solid var(--border);
    position:sticky;top:0;z-index:20;
  }
  .topbar-left{display:flex;align-items:center;gap:14px;}
  .menu-toggle{
    display:none;
    border:1px solid var(--border);
    background:var(--card);
    border-radius:10px;
    width:38px;height:38px;
    font-size:18px;
  }
  .page-title{margin:0;font-size:18px;}

  .topbar-right{display:flex;align-items:center;gap:14px;}
  .user{display:flex;align-items:center;gap:10px;}
  .avatar{
    width:38px;height:38px;border-radius:50%;
    background:var(--grad);color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-weight:700;
  }
  .user-name{font-size:14px;font-weight:600;}
  .user-role{font-size:12px;color:var(--muted);}

  .logout{
    padding:9px 18px;
    border-radius:10px;
    border:1px solid var(--border);
    background:var(--card);
    color:var(--text);
    font-size:14px;
    font-weight:600;
    text-decoration:none;
  }
  .logout:hover{background:var(--soft);}

  .content{padding:32px;}

  /* ---------- Views (only the active one is shown) ---------- */
  .view{display:none;}
  .view.active{display:block;}

  .banner{
    background:var(--grad);
    color:#fff;
    border-radius:20px;
    padding:30px 32px;
    margin-bottom:24px;
  }
  .banner h2{margin:0 0 6px;font-size:24px;}
  .banner p{margin:0;opacity:.9;font-size:14px;}
  .banner .link-btn{background:#fff;color:var(--accent);}
  .banner .link-btn:hover{background:#EAF2EC;}

  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;}

  .box{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:20px;
    padding:24px;
    box-shadow:0 8px 24px rgba(15,35,22,0.05);
  }
  .box h3{margin:0 0 16px;font-size:16px;}
  .box p{margin:0;color:var(--muted);font-size:14px;line-height:1.5;}

  .manage-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid var(--border);}
  .manage-tab{padding:12px 16px;border:0;border-bottom:3px solid transparent;background:transparent;color:var(--muted);font-size:14px;font-weight:700;}
  .manage-tab.active{border-bottom-color:var(--accent);color:var(--accent);}
  .manage-panel[hidden]{display:none;}
  .learner-search{width:100%;max-width:420px;margin:0 0 18px;padding:11px 14px;border:1px solid var(--border);border-radius:10px;font:inherit;}
  .learner-search:focus{outline:2px solid var(--accent);outline-offset:1px;}
  .learner-table-wrap{overflow-x:auto;background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 24px rgba(15,35,22,0.05);}
  .learner-table{width:100%;border-collapse:collapse;text-align:left;}
  .learner-table th,.learner-table td{padding:14px 16px;border-bottom:1px solid var(--border);font-size:14px;vertical-align:middle;}
  .learner-table th{background:#F7F9F7;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
  .learner-table tr:last-child td{border-bottom:0;}
  .learner-table td:first-child{font-weight:600;}
  .learner-status{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
  .learner-status.active{background:#EAF6EE;color:#237747;}
  .learner-status.inactive{background:#FEECEC;color:#B91C1C;}
  .learner-action{padding:9px 12px;border:0;border-radius:9px;background:var(--grad);color:#fff;font-size:13px;font-weight:700;white-space:nowrap;}
  .learner-action.deactivate{background:#B91C1C;}
  .learner-action:disabled{opacity:.6;cursor:not-allowed;}
  .manage-message{margin:0 0 16px;color:var(--muted);font-size:14px;}
  .manage-message.error{color:#B91C1C;}
  .survey-notice{margin:0 0 16px;padding:12px 16px;border:1px solid #B7DEC3;border-radius:10px;background:#EAF6EE;color:#237747;font-size:14px;}
  .survey-list-message{margin:0;color:var(--muted);font-size:14px;}
  .survey-list-message.error{color:#B91C1C;}
  .survey-grid{display:grid;grid-template-columns:repeat(auto-fill,262px);align-items:start;gap:16px;margin-top:18px;}
  .survey-card{display:flex;flex-direction:column;width:262px;min-height:378px;padding:18px 19px 19px;background:var(--card);border:1px solid var(--border);border-radius:8px;box-shadow:0 2px 6px rgba(15,35,22,.06);}
  .survey-card-header{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:7px;}
  .survey-card h3{min-width:0;margin:0;font-size:14px;line-height:1.3;overflow-wrap:anywhere;}
  .survey-status{flex-shrink:0;padding:4px 10px;border:1px solid #83c9a0;border-radius:999px;background:#d9f7e5;color:#187441;font-size:11px;font-weight:600;line-height:1.2;text-transform:capitalize;}
  .survey-status.draft{border-color:#d6dadd;background:#f1f3f2;color:#637067;}
  .survey-description{display:-webkit-box;overflow:hidden;min-height:0;margin:0 0 10px;color:#637067;font-size:12px;line-height:1.25;overflow-wrap:anywhere;-webkit-box-orient:vertical;-webkit-line-clamp:8;}
  .survey-created{display:flex;align-items:center;gap:5px;margin:0 0 11px;color:#637067;font-size:11px;}
  .survey-created svg{width:13px;height:13px;flex-shrink:0;}
  .survey-card-buttons{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;}
  .survey-card-button{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-width:0;min-height:32px;padding:5px 7px;border:1px solid #e2e9e3;border-radius:5px;background:#fff;color:#344054;font-size:11px;line-height:1.15;text-align:center;text-decoration:underline;cursor:pointer;}
  .survey-card-button svg{width:13px;height:13px;flex-shrink:0;}
  .survey-card-button:hover{filter:brightness(.97);}
  .survey-card-button:focus-visible{outline:2px solid var(--accent);outline-offset:2px;}
  .survey-card-button.assessees{border-color:#e3efff;background:#eef5ff;color:#2865b0;}
  .survey-card-button.raters{border-color:#eee5ff;background:#f5f0ff;color:#7041c3;}
  .survey-card-button.invitations{grid-column:1/-1;border-color:#d8f1e4;background:#eafaf2;color:#147d54;}
  .survey-card-divider{height:1px;margin:6px 0 11px;border:0;background:#e2e9e3;}
  .survey-card-button.danger{color:#c62828;}
  .survey-card-feedback{margin:5px 0 0;color:#637067;font-size:10px;line-height:1.3;}
  @media(max-width:600px){.survey-grid{grid-template-columns:repeat(auto-fill,minmax(min(262px,100%),1fr));}.survey-card{width:100%;}}

  .row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);font-size:14px;}
  .row:last-child{border-bottom:0;}
  .row span:first-child{color:var(--muted);}
  .row span:last-child{font-weight:600;text-align:right;word-break:break-all;}

  .badge{
    display:inline-block;
    margin-top:14px;
    padding:5px 12px;
    border-radius:999px;
    background:var(--soft);
    color:var(--accent);
    font-size:12px;
    font-weight:700;
  }

  .link-btn{
    display:inline-flex;
    align-items:center;
    margin-top:16px;
    padding:10px 18px;
    border:0;
    border-radius:10px;
    background:var(--grad);
    color:#fff;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    cursor:pointer;
  }

  #profileError{color:#DC2626;font-size:14px;}

  /* ---------- Small screens: sidebar slides in ---------- */
  @media (max-width:800px){
    :root{--header-height:63px;}
    .sidebar{position:fixed;left:calc(var(--sidebar-w) * -1);z-index:30;transition:left .2s;}
    .sidebar.open{left:0;box-shadow:0 12px 32px rgba(20,15,50,0.2);}
    .menu-toggle{display:block;}
    .topbar{padding:12px 16px;}
    .content{padding:20px 16px;}
    .user-info{display:none;}
  }
</style>
</head>
<body>

<div class="app">

  <!-- ================= LEFT SIDEBAR ================= -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">LM</div>
      <div>
        <div class="brand-name">LMSG</div>
        <div class="brand-sub">Learning Portal</div>
      </div>
    </div>

    <!-- Button 1 -->
    <button class="nav-btn<?= $initialView === 'dashboard' ? ' active' : '' ?>" data-view="dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </button>

    <!-- Button 2 -->
    <button class="nav-btn<?= in_array($initialView, ['surveys', 'survey360'], true) ? ' active' : '' ?>" data-view="surveys">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
      360/180 Surveys
    </button>

    <?php if ($canManageLearners): ?>
    <a class="nav-btn nav-link manage-nav<?= $initialView === 'manage' ? ' active' : '' ?>" href="manage.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 2.94-.08-.03a1.7 1.7 0 0 0-1.83.23l-.07.05h-3.4l-.03-.08a1.7 1.7 0 0 0-1.48-1.16l-.09-.01-2.94-1.7.03-.08a1.7 1.7 0 0 0-.23-1.83l-.05-.07v-3.4l.08-.03a1.7 1.7 0 0 0 1.16-1.48l.01-.09 1.7-2.94.08.03a1.7 1.7 0 0 0 1.83-.23l.07-.05h3.4l.03.08a1.7 1.7 0 0 0 1.48 1.16l.09.01 2.94 1.7-.03.08a1.7 1.7 0 0 0 .23 1.83l.05.07v3.4l-.08.03A1.7 1.7 0 0 0 19.4 15z"/>
      </svg>
      Manage
    </a>
    <?php endif; ?>

    <button class="nav-btn<?= $canManageLearners ? '' : ' profile-nav' ?>" data-view="profile">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>
      </svg>
      My Profile
    </button>
    
  </aside>

  <!-- ================= MAIN AREA ================= -->
  <div class="main">

    <!-- Top bar: page title on the left, user + logout on the right -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">☰</button>
        <h1 class="page-title" id="pageTitle"><?= $initialView === 'manage' ? 'Manage' : ($initialView === 'survey360' ? htmlspecialchars(($surveyType ?? '360') . '° Survey') : ($initialView === 'surveys' ? '360/180 Surveys' : 'Dashboard')) ?></h1>
      </div>

      <div class="topbar-right">
        <div class="user">
          <div class="avatar"><?= htmlspecialchars($initial) ?></div>
          <div class="user-info">
            <!-- htmlspecialchars prints the name safely as text -->
            <div class="user-name"><?= htmlspecialchars($learnerName) ?></div>
            <div class="user-role"><?= htmlspecialchars($roleLabel) ?></div>
          </div>
        </div>
        <a class="logout" href="Api/logout.php">Logout</a>
      </div>
    </header>

    <main class="content">

      <!-- ========== VIEW 1: Dashboard ========== -->
      <section class="view<?= $initialView === 'dashboard' ? ' active' : '' ?>" id="view-dashboard">
        <div class="banner">
          <h2>Welcome back, <?= htmlspecialchars($firstName) ?>!</h2>
          <p>Here is your learning portal. Use the menu on the left to move around.</p>
        </div>

        <div class="grid">
          <div class="box">
            <h3>360/180 Surveys</h3>
            <p>Collect feedback to understand your strengths and growth areas.</p>
            <button class="link-btn" id="goSurveys">Open Surveys</button>
          </div>
        </div>
      </section>

      <!-- ========== VIEW 3: My Profile ========== -->
      <section class="view" id="view-profile">
        <div class="banner">
          <h2>My Profile</h2>
          <p>View your learner account details.</p>
        </div>

        <div class="box">
          <h3>Profile details</h3>
          <div id="profileBox">
            <div class="row"><span>Full name</span><span id="pName">Loading...</span></div>
            <div class="row"><span>Email</span><span id="pEmail">Loading...</span></div>
            <div class="row"><span>Role</span><span id="pRole"><?= htmlspecialchars($roleLabel) ?></span></div>
            <div class="row"><span>Member since</span><span id="pSince">Loading...</span></div>
          </div>
          <div id="profileError"></div>
        </div>
        <a class="sidebar-logout" href="Api/logout.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
      </svg>
      Logout
    </a>
    
      </section>

      <!-- ========== VIEW 2: 360/180 Surveys ========== -->
      <section class="view<?= $initialView === 'surveys' ? ' active' : '' ?>" id="view-surveys">
        <div class="banner">
          <h2>360/180 Surveys</h2>
          <p>Feedback surveys that help learners grow.</p>
        </div>

        <div class="grid">
          <div class="box">
            <h3>360° Survey</h3>
            <p>Feedback from many people around you: your manager, peers, direct reports, and yourself.</p>
            <a class="link-btn" href="survey_180_360_insider.php?type=360">Open 360° Survey</a>
          </div>

          <div class="box">
            <h3>180° Survey</h3>
            <p>A shorter feedback round: your own view and your manager's view.</p>
            <a class="link-btn" href="survey_180_360_insider.php?type=180">Open 180° Survey</a>
          </div>
        </div>
      </section>

      <section class="view<?= $initialView === 'survey360' ? ' active' : '' ?>" id="view-survey360">
        <div class="banner">
          <h2><?= htmlspecialchars($surveyType ?? '360') ?>° Survey</h2>
          <p>Share specific, constructive feedback to support someone's growth.</p>
          <a class="link-btn" href="create_180_360_survey.php?type=<?= urlencode($surveyType ?? '360') ?>">+ Create Survey</a>
        </div>
        <?php if (($_GET['created'] ?? '') === '1'): ?>
          <div class="survey-notice" role="status">Your survey was created successfully.</div>
        <?php endif; ?>
        <h3>Your <?= htmlspecialchars($surveyType ?? '360') ?>° surveys</h3>
        <p class="survey-list-message" id="surveyListMessage" aria-live="polite">Loading surveys...</p>
        <div class="survey-grid" id="surveyCards"></div>
      </section>

      <?php if ($canManageLearners): ?>
      <section class="view<?= $initialView === 'manage' ? ' active' : '' ?>" id="view-manage">
        <div class="banner">
          <h2>Manage</h2>
          <p><?= $isSuperAdmin ? 'Super Admin workspace' : 'Manage learners and institute admins' ?></p>
        </div>
        <div class="manage-tabs" role="tablist" aria-label="Manage sections">
          <button type="button" class="manage-tab active" id="learnersTab" role="tab" aria-selected="true" aria-controls="manage-learners" data-manage-tab="learners">Manage Learners</button>
          <?php if ($canManageLearners): ?>
          <button type="button" class="manage-tab" id="institutesTab" role="tab" aria-selected="false" aria-controls="manage-institutes" data-manage-tab="institutes">Manage Institutes</button>
          <?php endif; ?>
        </div>
        <section class="manage-panel" id="manage-learners" role="tabpanel" aria-labelledby="learnersTab">
          <div class="box">
            <h3>Create Learner</h3>
            <p>Create a learner account for an active institute.</p>
            <form id="createLearnerForm">
              <label for="newLearnerName">Learner name</label>
              <input class="learner-search" id="newLearnerName" name="full_name" maxlength="100" required>
              <label for="newLearnerEmail">Learner email</label>
              <input class="learner-search" id="newLearnerEmail" name="email" type="email" maxlength="150" required>
              <?php if ($isSuperAdmin): ?>
              <label for="newLearnerInstitute">Institute</label>
              <select class="learner-search" id="newLearnerInstitute" name="institute_id" required>
                <option value="">Loading institutes...</option>
              </select>
              <?php endif; ?>
              <label for="newLearnerPassword">Temporary password (minimum 6 characters)</label>
              <input class="learner-search" id="newLearnerPassword" name="password" type="password" minlength="6" required>
              <button class="learner-action" type="submit" id="createLearnerButton"<?= $isSuperAdmin ? ' disabled' : '' ?>>Create learner</button>
            </form>
            <p class="manage-message" id="createLearnerMessage" aria-live="polite"></p>
          </div>
          <label for="learnerSearch">Search learners</label>
          <input class="learner-search" type="search" id="learnerSearch" placeholder="Search by name, email, or status">
          <p class="manage-message" id="manageLearnersMessage" aria-live="polite">Loading learners...</p>
          <div class="learner-table-wrap">
            <table class="learner-table">
              <thead>
                <tr>
                  <th scope="col">Name</th>
                  <th scope="col">Email</th>
                  <th scope="col">Institute</th>
                  <?php if ($isSuperAdmin): ?>
                  <th scope="col">Role</th>
                  <?php endif; ?>
                  <th scope="col">Member since</th>
                  <th scope="col">Status</th>
                  <?php if ($isSuperAdmin): ?>
                  <th scope="col">Action</th>
                  <?php endif; ?>
                  <?php if ($isInstituteAdmin): ?>
                  <th scope="col">Role action</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody id="learnerRows"></tbody>
            </table>
          </div>
        </section>
        <?php if ($isSuperAdmin): ?>
        <section class="manage-panel" id="manage-institutes" role="tabpanel" aria-labelledby="institutesTab" hidden>
          <div class="manage-tabs" role="tablist" aria-label="Institute management sections">
            <button type="button" class="manage-tab active" role="tab" aria-selected="true" aria-controls="institute-list-panel" data-institute-tab="institutes">Institutes</button>
            <button type="button" class="manage-tab" role="tab" aria-selected="false" aria-controls="institute-admins-panel" data-institute-tab="admins">Institute Admins</button>
          </div>
          <section class="manage-panel" id="institute-list-panel" role="tabpanel">
          <div class="box">
            <h3>Manage Institutes</h3>
            <p>Create an institute and its first institute administrator account.</p>
            <form id="createInstituteForm">
              <label for="instituteName">Institute name</label>
              <input class="learner-search" id="instituteName" name="name" maxlength="150" required>
              <label for="instituteAdminName">Institute administrator name</label>
              <input class="learner-search" id="instituteAdminName" name="admin_name" maxlength="100" required>
              <label for="instituteAdminEmail">Institute administrator email</label>
              <input class="learner-search" id="instituteAdminEmail" name="admin_email" type="email" maxlength="150" required>
              <label for="instituteAdminPassword">Temporary password (minimum 6 characters)</label>
              <input class="learner-search" id="instituteAdminPassword" name="admin_password" type="password" minlength="6" required>
              <button class="learner-action" type="submit" id="createInstituteButton">Create institute</button>
            </form>
            <p class="manage-message" id="manageInstitutesMessage" aria-live="polite"></p>
            <h3>Institutes</h3>
            <div class="learner-table-wrap">
              <table class="learner-table">
                <thead><tr><th scope="col">Institute</th><th scope="col">Created</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                <tbody id="instituteRows"></tbody>
              </table>
            </div>
          </div>
          </section>
          <section class="manage-panel" id="institute-admins-panel" role="tabpanel" hidden>
            <div class="box">
              <h3>Institute Admins</h3>
            <p>Create administrators and view which institute each administrator belongs to.</p>
            <form id="createInstituteAdminForm" class="create-institute-admin-form">
              <label for="newInstituteAdminName">Administrator name</label>
              <input class="learner-search" id="newInstituteAdminName" name="admin_name" maxlength="100" required>
              <label for="newInstituteAdminEmail">Administrator email</label>
              <input class="learner-search" id="newInstituteAdminEmail" name="admin_email" type="email" maxlength="150" required>
              <label for="newInstituteAdminInstitute">Institute</label>
              <select class="learner-search" id="newInstituteAdminInstitute" name="institute_id" required>
                <option value="">Loading institutes...</option>
              </select>
              <label for="newInstituteAdminPassword">Temporary password (minimum 6 characters)</label>
              <input class="learner-search" id="newInstituteAdminPassword" name="admin_password" type="password" minlength="6" required>
              <button class="learner-action" type="submit" id="createInstituteAdminButton">Create institute admin</button>
            </form>
            <p class="manage-message create-institute-admin-message" id="createInstituteAdminMessage" aria-live="polite"></p>
            <p class="manage-message" id="manageInstituteAdminsMessage" aria-live="polite">Loading institute admins...</p>
              <div class="learner-table-wrap">
                <table class="learner-table">
                  <thead>
                    <tr>
                      <th scope="col">Name</th>
                      <th scope="col">Email</th>
                      <th scope="col">Member since</th>
                      <th scope="col">Status</th>
                    </tr>
                  </thead>
                  <tbody id="instituteAdminRows"></tbody>
                </table>
              </div>
            </div>
          </section>
        </section>
        <?php elseif ($isInstituteAdmin): ?>
        <section class="manage-panel" id="manage-institutes" role="tabpanel" aria-labelledby="institutesTab" hidden>
          <div class="manage-tabs" role="tablist" aria-label="Institute sections">
            <button type="button" class="manage-tab active" role="tab" aria-selected="true" aria-controls="institute-list-panel" data-institute-tab="institutes">Institutes</button>
            <button type="button" class="manage-tab" role="tab" aria-selected="false" aria-controls="institute-admins-panel" data-institute-tab="admins">Institute Admins</button>
          </div>
          <section class="manage-panel" id="institute-list-panel" role="tabpanel">
            <div class="box">
              <h3>My Institute</h3>
              <p>This is the institute assigned to your account. Institute details are view-only.</p>
              <p class="manage-message" id="myInstituteMessage" aria-live="polite">Loading institute...</p>
              <div class="learner-table-wrap">
                <table class="learner-table">
                  <thead><tr><th scope="col">Institute</th><th scope="col">Created</th></tr></thead>
                  <tbody id="myInstituteRows"></tbody>
                </table>
              </div>
            </div>
          </section>
          <section class="manage-panel" id="institute-admins-panel" role="tabpanel" hidden>
          <div class="box">
            <h3>Institute Admins</h3>
            <p>Institute admins have learner permissions plus additional management features.</p>
            <p>Promote learners from the Manage Learners tab; demote institute admins here.</p>
            <form id="createMyInstituteAdminForm" class="create-institute-admin-form">
              <label for="myInstituteAdminName">Administrator name</label>
              <input class="learner-search" id="myInstituteAdminName" name="admin_name" maxlength="100" required>
              <label for="myInstituteAdminEmail">Administrator email</label>
              <input class="learner-search" id="myInstituteAdminEmail" name="admin_email" type="email" maxlength="150" required>
              <label for="myInstituteAdminPassword">Temporary password (minimum 6 characters)</label>
              <input class="learner-search" id="myInstituteAdminPassword" name="admin_password" type="password" minlength="6" required>
              <button class="learner-action" type="submit">Create institute admin</button>
            </form>
            <p class="manage-message create-institute-admin-message" id="createMyInstituteAdminMessage" aria-live="polite"></p>
            <p class="manage-message" id="manageInstituteAdminsMessage" aria-live="polite">Loading institute admins...</p>
            <div class="learner-table-wrap">
              <table class="learner-table">
                <thead>
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Member since</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody id="instituteAdminRows"></tbody>
              </table>
            </div>
          </div>
          </section>
        </section>
        <?php endif; ?>
      </section>
      <?php endif; ?>

    </main>
  </div>
</div>

<script>
  const API_URL = 'Api/dashboard_handler.php';
  const SURVEY_API_URL = 'Api/create_180_360_survey_hamdler.php';

  // ---------- Find the things we need on the page ----------
  const navButtons = document.querySelectorAll('button.nav-btn');
  const views      = document.querySelectorAll('.view');
  const pageTitle  = document.getElementById('pageTitle');
  const sidebar    = document.getElementById('sidebar');

  // The title shown in the top bar for each view
  const titles = {
    dashboard: 'Dashboard',
    surveys:   '360/180 Surveys',
    survey360: <?= json_encode(($surveyType ?? '360') . '° Survey') ?>,
    profile:   'My Profile',
    <?php if ($canManageLearners): ?>
    manage:    'Manage'
    <?php endif; ?>
  };

  // ---------- Show one view and hide the others ----------
  function showView(name) {
    if (!titles[name]) name = 'dashboard';        // unknown name -> go to dashboard

    views.forEach(function (v) {
      v.classList.toggle('active', v.id === 'view-' + name);
    });
    const activeNavigationView = name === 'survey360' ? 'surveys' : name;
    navButtons.forEach(function (b) {
      b.classList.toggle('active', b.dataset.view === activeNavigationView);
    });

    pageTitle.textContent = titles[name];
    history.replaceState(null, '', '#' + name);   // so refresh keeps the same page
    sidebar.classList.remove('open');             // close the menu on small screens
  }

  // Clicking a sidebar button
  navButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.dataset.view === 'surveys' && !location.pathname.endsWith('/survey_180_360.php')) {
        window.location.href = 'survey_180_360.php';
        return;
      }
      if (btn.dataset.view === 'dashboard' && (location.pathname.endsWith('/survey_180_360.php') || location.pathname.endsWith('/survey_180_360_insider.php'))) {
        window.location.href = 'dashboard.php';
        return;
      }
      showView(btn.dataset.view);
    });
  });

  // "Open Surveys" button inside the dashboard
  document.getElementById('goSurveys').addEventListener('click', function () {
    showView('surveys');
  });

  // Hamburger button (small screens)
  document.getElementById('menuToggle').addEventListener('click', function () {
    sidebar.classList.toggle('open');
  });

  const pageSurveyType = <?= json_encode($surveyType ?? null) ?>;
  if (pageSurveyType && new URLSearchParams(location.search).get('type') !== pageSurveyType) {
    const canonicalUrl = new URL(location.href);
    canonicalUrl.searchParams.set('type', pageSurveyType);
    history.replaceState(null, '', canonicalUrl.pathname + canonicalUrl.search + canonicalUrl.hash);
  }

  const initialHash = location.hash.replace('#', '');
  const requestedView = initialHash.indexOf('manage-') === 0 ? 'manage' : initialHash;

  // On page load, open the view named in the address (#surveys) or the dashboard
  showView(requestedView || <?= json_encode($initialView) ?>);

  const manageTabs = document.querySelectorAll('[data-manage-tab]');
  const manageLearnersMessage = document.getElementById('manageLearnersMessage');
  const createLearnerForm = document.getElementById('createLearnerForm');
  const createLearnerMessage = document.getElementById('createLearnerMessage');
  const newLearnerInstitute = document.getElementById('newLearnerInstitute');
  const learnerSearch = document.getElementById('learnerSearch');
  const learnerRows = document.getElementById('learnerRows');
  const createInstituteForm = document.getElementById('createInstituteForm');
  const createInstituteAdminForms = document.querySelectorAll('.create-institute-admin-form');
  const manageInstitutesMessage = document.getElementById('manageInstitutesMessage');
  const instituteRows = document.getElementById('instituteRows');
  const myInstituteRows = document.getElementById('myInstituteRows');
  const myInstituteMessage = document.getElementById('myInstituteMessage');
  const newInstituteAdminInstitute = document.getElementById('newInstituteAdminInstitute');
  const instituteAdminRows = document.getElementById('instituteAdminRows');
  const manageInstituteAdminsMessage = document.getElementById('manageInstituteAdminsMessage');
  const instituteTabs = document.querySelectorAll('[data-institute-tab]');
  const manageCsrfToken = <?= json_encode($csrfToken) ?>;
  const manageApiUrl = 'Api/manage_handler.php';
  const isInstituteAdmin = <?= json_encode($isInstituteAdmin) ?>;
  let allLearners = [];

  function setManageTab(tabName, updateHash) {
    const activeTab = tabName === 'institutes' ? 'institutes' : 'learners';
    manageTabs.forEach(function (tab) {
      const selected = tab.dataset.manageTab === activeTab;
      tab.classList.toggle('active', selected);
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    document.getElementById('manage-learners').hidden = activeTab !== 'learners';
    const institutesPanel = document.getElementById('manage-institutes');
    if (institutesPanel) institutesPanel.hidden = activeTab !== 'institutes';
    if (activeTab === 'institutes' && document.getElementById('institute-list-panel')) {
      setInstituteTab('institutes');
    }
    if (updateHash) history.replaceState(null, '', '#manage-' + activeTab);
  }

  function setInstituteTab(tabName) {
    const activeTab = tabName === 'admins' ? 'admins' : 'institutes';
    instituteTabs.forEach(function (tab) {
      const selected = tab.dataset.instituteTab === activeTab;
      tab.classList.toggle('active', selected);
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    document.getElementById('institute-list-panel').hidden = activeTab !== 'institutes';
    document.getElementById('institute-admins-panel').hidden = activeTab !== 'admins';
  }

  function renderLearners(learners) {
    learnerRows.replaceChildren();
    const query = learnerSearch.value.trim().toLocaleLowerCase();
    const filteredLearners = learners.filter(function (learner) {
      const active = Number(learner.is_active) === 1;
      const searchableText = [
        learner.full_name,
        learner.email,
        learner.institute_name || '',
        learner.role_name || 'learner',
        active ? 'active' : 'deactivated'
      ].join(' ').toLocaleLowerCase();
      return searchableText.includes(query);
    });

    filteredLearners.forEach(function (learner) {
      const active = Number(learner.is_active) === 1;
      const row = document.createElement('tr');
      const name = document.createElement('td');
      name.textContent = learner.full_name;
      row.appendChild(name);
      const email = document.createElement('td');
      email.textContent = learner.email;
      row.appendChild(email);
      const institute = document.createElement('td');
      institute.textContent = learner.institute_name || '';
      row.appendChild(institute);
      if (!isInstituteAdmin) {
        const role = document.createElement('td');
        role.textContent = learner.role_name === 'institute_admin' ? 'Institute Admin' : 'Learner';
        row.appendChild(role);
      }
      const joined = document.createElement('td');
      joined.textContent = new Date(learner.created_at).toLocaleDateString();
      row.appendChild(joined);
      const statusCell = document.createElement('td');
      const status = document.createElement('span');
      status.className = 'learner-status ' + (active ? 'active' : 'inactive');
      status.textContent = active ? 'Active' : 'Deactivated';
      statusCell.appendChild(status);
      row.appendChild(statusCell);
      if (!isInstituteAdmin) {
        const actionCell = document.createElement('td');
        if (learner.role_name === 'learner') {
          const action = document.createElement('button');
          action.type = 'button';
          action.className = 'learner-action' + (active ? ' deactivate' : '');
          action.textContent = active ? 'Deactivate' : 'Activate';
          action.setAttribute('aria-label', (active ? 'Deactivate ' : 'Activate ') + learner.full_name);
          action.addEventListener('click', function () {
            updateLearnerStatus(learner, !active, action);
          });
          actionCell.appendChild(action);
        } else {
          actionCell.textContent = '—';
        }
        row.appendChild(actionCell);
      }
      if (isInstituteAdmin) {
        const roleActionCell = document.createElement('td');
        const roleAction = document.createElement('button');
        roleAction.type = 'button';
        roleAction.className = 'learner-action';
        roleAction.textContent = 'Promote to Admin';
        roleAction.addEventListener('click', function () {
          updateLearnerRole(learner, 'institute_admin', roleAction);
        });
        roleActionCell.appendChild(roleAction);
        row.appendChild(roleActionCell);
      }
      learnerRows.appendChild(row);
    });

    if (filteredLearners.length) {
      manageLearnersMessage.textContent = 'Showing ' + filteredLearners.length + ' of ' + learners.length + ' learners.';
    } else if (learners.length && query) {
      manageLearnersMessage.textContent = 'No learners match your search.';
    } else {
      manageLearnersMessage.textContent = 'No learners are registered yet.';
    }
    manageLearnersMessage.classList.remove('error');
  }

  async function loadLearners() {
    try {
      const response = await fetch(manageApiUrl, { credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not load learners.');
      }
      allLearners = result.learners;
      renderLearners(allLearners);
      if (instituteRows && result.institutes) renderInstitutes(result.institutes);
      if (instituteAdminRows && result.institute_admins) renderInstituteAdmins(result.institute_admins);
      if (myInstituteRows && result.institute) renderMyInstitute(result.institute);
    } catch (error) {
      manageLearnersMessage.textContent = error.message || 'Could not load learners.';
      manageLearnersMessage.classList.add('error');
    }
  }

  function renderInstitutes(institutes) {
    instituteRows.replaceChildren();
    if (newLearnerInstitute) {
      newLearnerInstitute.replaceChildren(new Option('Select an institute', ''));
      const activeInstitutes = institutes.filter(function (institute) {
        return Number(institute.is_active) === 1;
      });
      activeInstitutes.forEach(function (institute) {
        newLearnerInstitute.add(new Option(institute.name, institute.id));
      });
      const createLearnerButton = document.getElementById('createLearnerButton');
      if (activeInstitutes.length === 0) {
        newLearnerInstitute.options[0].textContent = 'No active institutes available';
        newLearnerInstitute.disabled = true;
      } else {
        newLearnerInstitute.disabled = false;
      }
      createLearnerButton.disabled = activeInstitutes.length === 0;
    }
    if (newInstituteAdminInstitute) {
      newInstituteAdminInstitute.replaceChildren(new Option('Select an institute', ''));
      institutes.filter(function (institute) {
        return Number(institute.is_active) === 1;
      }).forEach(function (institute) {
        newInstituteAdminInstitute.add(new Option(institute.name, institute.id));
      });
    }
    institutes.forEach(function (institute) {
      const row = document.createElement('tr');
      const name = document.createElement('td');
      name.textContent = institute.name;
      const created = document.createElement('td');
      created.textContent = new Date(institute.created_at).toLocaleDateString();
      const active = Number(institute.is_active) === 1;
      const statusCell = document.createElement('td');
      const status = document.createElement('span');
      status.className = 'learner-status ' + (active ? 'active' : 'inactive');
      status.textContent = active ? 'Active' : 'Deactivated';
      statusCell.appendChild(status);
      const actionCell = document.createElement('td');
      const action = document.createElement('button');
      action.type = 'button';
      action.className = 'learner-action' + (active ? ' deactivate' : '');
      action.textContent = active ? 'Deactivate' : 'Activate';
      action.addEventListener('click', function () {
        updateInstituteStatus(institute, !active, action);
      });
      actionCell.appendChild(action);
      row.append(name, created, statusCell, actionCell);
      instituteRows.appendChild(row);
    });
  }

  function renderMyInstitute(institute) {
    myInstituteRows.replaceChildren();
    const row = document.createElement('tr');
    const name = document.createElement('td');
    name.textContent = institute.name;
    const created = document.createElement('td');
    created.textContent = new Date(institute.created_at).toLocaleDateString();
    row.append(name, created);
    myInstituteRows.appendChild(row);
    myInstituteMessage.textContent = 'Institute information is read-only.';
  }

  function renderInstituteAdmins(admins) {
    instituteAdminRows.replaceChildren();
    admins.forEach(function (admin) {
      const row = document.createElement('tr');
      const name = document.createElement('td');
      name.textContent = admin.full_name;
      const email = document.createElement('td');
      email.textContent = admin.email;
      const joined = document.createElement('td');
      joined.textContent = new Date(admin.created_at).toLocaleDateString();
      const statusCell = document.createElement('td');
      const status = document.createElement('span');
      const active = Number(admin.is_active) === 1;
      status.className = 'learner-status ' + (active ? 'active' : 'inactive');
      status.textContent = active ? 'Active' : 'Deactivated';
      statusCell.appendChild(status);
      row.append(name, email, joined, statusCell);
      if (isInstituteAdmin) {
        const actionCell = document.createElement('td');
        const demote = document.createElement('button');
        demote.type = 'button';
        demote.className = 'learner-action deactivate';
        demote.textContent = 'Demote to Learner';
        demote.disabled = isInstituteAdmin
          && Number(admin.id) === Number(<?= json_encode((int)($_SESSION['user_id'] ?? 0)) ?>);
        demote.addEventListener('click', function () {
          updateLearnerRole(admin, 'learner', demote);
        });
        actionCell.appendChild(demote);
        row.appendChild(actionCell);
      }
      instituteAdminRows.appendChild(row);
    });
    manageInstituteAdminsMessage.textContent = admins.length
      ? 'Showing ' + admins.length + ' institute administrator' + (admins.length === 1 ? '.' : 's.')
      : 'No institute admins are registered yet.';
  }

  if (createLearnerForm) {
    createLearnerForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = document.getElementById('createLearnerButton');
      button.disabled = true;
      createLearnerMessage.textContent = '';
      createLearnerMessage.classList.remove('error');
      try {
        const formData = new FormData(createLearnerForm);
        const requestData = {
          action: 'create_learner',
          full_name: formData.get('full_name'),
          email: formData.get('email'),
          password: formData.get('password')
        };
        if (formData.has('institute_id')) {
          requestData.institute_id = formData.get('institute_id');
        }
        const response = await fetch(manageApiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': manageCsrfToken
          },
          body: JSON.stringify(requestData)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Could not create the learner.');
        }
        createLearnerForm.reset();
        createLearnerMessage.textContent = result.message;
        await loadLearners();
      } catch (error) {
        createLearnerMessage.textContent = error.message || 'Could not create the learner.';
        createLearnerMessage.classList.add('error');
      } finally {
        button.disabled = false;
      }
    });
  }

  if (createInstituteForm) {
    createInstituteForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = document.getElementById('createInstituteButton');
      button.disabled = true;
      manageInstitutesMessage.textContent = '';
      manageInstitutesMessage.classList.remove('error');
      try {
        const formData = new FormData(createInstituteForm);
        const response = await fetch(manageApiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': manageCsrfToken
          },
          body: JSON.stringify({
            action: 'create_institute',
            name: formData.get('name'),
            admin_name: formData.get('admin_name'),
            admin_email: formData.get('admin_email'),
            admin_password: formData.get('admin_password')
          })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Could not create the institute.');
        }
        createInstituteForm.reset();
        manageInstitutesMessage.textContent = result.message;
        await loadLearners();
      } catch (error) {
        manageInstitutesMessage.textContent = error.message || 'Could not create the institute.';
        manageInstitutesMessage.classList.add('error');
      } finally {
        button.disabled = false;
      }
    });
  }

  createInstituteAdminForms.forEach(function (form) {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const message = form.parentElement.querySelector('.create-institute-admin-message');
      button.disabled = true;
      message.textContent = '';
      message.classList.remove('error');
      try {
        const formData = new FormData(form);
        const requestData = {
          action: 'create_institute_admin',
          admin_name: formData.get('admin_name'),
          admin_email: formData.get('admin_email'),
          admin_password: formData.get('admin_password')
        };
        if (formData.has('institute_id')) {
          requestData.institute_id = formData.get('institute_id');
        }
        const response = await fetch(manageApiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': manageCsrfToken
          },
          body: JSON.stringify(requestData)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Could not create the institute admin.');
        }
        form.reset();
        message.textContent = result.message;
        await loadLearners();
      } catch (error) {
        message.textContent = error.message || 'Could not create the institute admin.';
        message.classList.add('error');
      } finally {
        button.disabled = false;
      }
    });
  });

  async function updateLearnerStatus(learner, isActive, action) {
    action.disabled = true;
    manageLearnersMessage.textContent = '';
    manageLearnersMessage.classList.remove('error');
    try {
      const response = await fetch(manageApiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': manageCsrfToken
        },
        body: JSON.stringify({ learner_id: learner.id, is_active: isActive })
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not update learner status.');
      }
      manageLearnersMessage.textContent = result.message;
      await loadLearners();
    } catch (error) {
      manageLearnersMessage.textContent = error.message || 'Could not update learner status.';
      manageLearnersMessage.classList.add('error');
      action.disabled = false;
    }
  }

  async function updateInstituteStatus(institute, isActive, action) {
    action.disabled = true;
    manageInstitutesMessage.textContent = '';
    manageInstitutesMessage.classList.remove('error');
    try {
      const response = await fetch(manageApiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': manageCsrfToken
        },
        body: JSON.stringify({
          action: 'toggle_institute',
          institute_id: institute.id,
          is_active: isActive
        })
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not update institute status.');
      }
      manageInstitutesMessage.textContent = result.message;
      await loadLearners();
    } catch (error) {
      manageInstitutesMessage.textContent = error.message || 'Could not update institute status.';
      manageInstitutesMessage.classList.add('error');
      action.disabled = false;
    }
  }

  async function updateLearnerRole(learner, roleName, action) {
    action.disabled = true;
    const message = roleName === 'learner' ? manageInstituteAdminsMessage : manageLearnersMessage;
    message.textContent = '';
    message.classList.remove('error');
    try {
      const response = await fetch(manageApiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': manageCsrfToken
        },
        body: JSON.stringify({
          action: 'change_role',
          learner_id: learner.id,
          role_name: roleName
        })
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not change the account role.');
      }
      message.textContent = result.message;
      await loadLearners();
    } catch (error) {
      message.textContent = error.message || 'Could not change the account role.';
      message.classList.add('error');
      action.disabled = false;
    }
  }

  manageTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setManageTab(tab.dataset.manageTab, true);
    });
  });
  instituteTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setInstituteTab(tab.dataset.instituteTab);
    });
  });
  if (learnerSearch) {
    learnerSearch.addEventListener('input', function () {
      renderLearners(allLearners);
    });
  }
  if (manageTabs.length) {
    const initialManageTab = initialHash === 'manage-institutes' && document.getElementById('manage-institutes') ? 'institutes' : 'learners';
    setManageTab(initialManageTab, initialHash.indexOf('manage-') === 0);
    if (<?= json_encode($initialView === 'manage') ?>) loadLearners();
  }

  // ---------- Load the user's profile from the API ----------
  async function loadProfile() {
    try {
      const response = await fetch(API_URL, { credentials: 'same-origin' });
      const result = await response.json();

      // Session ended -> go back to the login page
      if (response.status === 401) {
        window.location.href = result.code === 'account_deactivated'
          ? 'login.php?reason=access_revoked'
          : 'login.php';
        return;
      }

      if (!result.success) {
        document.getElementById('profileError').textContent = result.message;
        return;
      }

      // textContent prints the values safely as plain text
      document.getElementById('pName').textContent  = result.learner.full_name;
      document.getElementById('pEmail').textContent = result.learner.email;
      document.getElementById('pSince').textContent = result.learner.member_since;
      document.getElementById('pRole').textContent  = result.learner.role;

    } catch (err) {
      document.getElementById('profileError').textContent = 'Could not load your profile.';
    }
  }

  loadProfile();

  const surveyCards = document.getElementById('surveyCards');
  const surveyListMessage = document.getElementById('surveyListMessage');
  if (surveyCards && surveyListMessage && <?= json_encode($initialView === 'survey360') ?>) {
    const surveyTypeFilter = <?= json_encode($surveyType ?? '360') ?>;
    fetch(SURVEY_API_URL + '?type=' + encodeURIComponent(surveyTypeFilter), { credentials: 'same-origin' })
      .then(function (response) {
        return response.json().then(function (result) {
          if (!response.ok || !result.success) throw new Error(result.message || 'Could not load surveys.');
          return result.surveys;
        });
      })
      .then(function (surveys) {
        surveyCards.replaceChildren();
        if (!surveys.length) {
          surveyListMessage.textContent = 'No surveys created yet.';
          return;
        }
        surveyListMessage.textContent = '';
        surveys.forEach(function (survey) {
          const card = document.createElement('article');
          card.className = 'survey-card';

          const header = document.createElement('div');
          header.className = 'survey-card-header';
          const title = document.createElement('h3');
          title.textContent = survey.title;
          const status = document.createElement('span');
          status.className = 'survey-status ' + survey.status;
          status.textContent = survey.status;
          header.append(title, status);

          const description = document.createElement('p');
          description.className = 'survey-description';
          description.textContent = survey.description || 'No description provided.';

          const created = document.createElement('p');
          created.className = 'survey-created';
          created.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>';
          created.appendChild(document.createTextNode('Created ' + survey.created_at));

          const feedback = document.createElement('p');
          feedback.className = 'survey-card-feedback';
          feedback.setAttribute('aria-live', 'polite');
          feedback.hidden = true;

          function makeSurveyAction(label, icon, className) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'survey-card-button' + (className ? ' ' + className : '');
            button.innerHTML = icon;
            button.appendChild(document.createTextNode(label));
            button.addEventListener('click', function () {
              feedback.textContent = label + ' is not available yet.';
              feedback.hidden = false;
            });
            return button;
          }

          const actions = document.createElement('div');
          actions.className = 'survey-card-buttons';
          actions.append(
            makeSurveyAction('Add assessee', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15 12a4 4 0 1 0-3.98-4A4 4 0 0 0 15 12Zm-8 0a3 3 0 1 0-2.98-3A3 3 0 0 0 7 12Zm8 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4ZM7 14c-2.33 0-7 1.17-7 3.5V20h4v-2c0-1.42.8-2.67 2.3-3.65A12 12 0 0 1 7 14Z"/><path d="M20 5V2h-2v3h-3v2h3v3h2V7h3V5z"/></svg>', 'assessees'),
            makeSurveyAction('Assign Raters', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 11a3 3 0 1 0-2.9-3A3 3 0 0 0 16 11ZM8 11a3 3 0 1 0-2.9-3A3 3 0 0 0 8 11Zm8 2c-2.2 0-6.5 1.1-6.5 3.3V19h13v-2.7C22.5 14.1 18.2 13 16 13ZM8 13c-2.2 0-6.5 1.1-6.5 3.3V19h4v-2c0-1.2.6-2.2 1.8-3A8 8 0 0 1 8 13Z"/><path d="m18.7 2 .7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7zM4 2l.8 2.2L7 5l-2.2.8L4 8l-.8-2.2L1 5l2.2-.8z"/></svg>', 'raters'),
            makeSurveyAction('Send Invitations', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 5h20v14H2zm2 2v1l8 5 8-5V7l-8 5z"/></svg>', 'invitations')
          );

          const divider = document.createElement('hr');
          divider.className = 'survey-card-divider';
          const managementActions = document.createElement('div');
          managementActions.className = 'survey-card-buttons';
          managementActions.append(
            makeSurveyAction('Progress', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>'),
            makeSurveyAction('Reports', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5M4 19h17M8 15v-3m4 3V8m4 7V5m4 10v-6"/></svg>'),
            makeSurveyAction('Edit', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>'),
            makeSurveyAction('Delete', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>', 'danger')
          );

          card.append(header, description, created, actions, divider, managementActions, feedback);
          surveyCards.appendChild(card);
        });
      })
      .catch(function (error) {
        surveyListMessage.textContent = error.message || 'Could not load surveys.';
        surveyListMessage.classList.add('error');
      });
  }
</script>

</body>
</html>