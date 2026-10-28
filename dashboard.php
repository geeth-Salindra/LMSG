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

    <?php if ($isSuperAdmin): ?>
    <a class="nav-btn nav-link manage-nav<?= $initialView === 'manage' ? ' active' : '' ?>" href="manage.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 2.94-.08-.03a1.7 1.7 0 0 0-1.83.23l-.07.05h-3.4l-.03-.08a1.7 1.7 0 0 0-1.48-1.16l-.09-.01-2.94-1.7.03-.08a1.7 1.7 0 0 0-.23-1.83l-.05-.07v-3.4l.08-.03a1.7 1.7 0 0 0 1.16-1.48l.01-.09 1.7-2.94.08.03a1.7 1.7 0 0 0 1.83-.23l.07-.05h3.4l.03.08a1.7 1.7 0 0 0 1.48 1.16l.09.01 2.94 1.7-.03.08a1.7 1.7 0 0 0 .23 1.83l.05.07v3.4l-.08.03A1.7 1.7 0 0 0 19.4 15z"/>
      </svg>
      Manage
    </a>
    <?php endif; ?>

    <button class="nav-btn<?= $isSuperAdmin ? '' : ' profile-nav' ?>" data-view="profile">
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
        </div>
      </section>

      <?php if ($isSuperAdmin): ?>
      <section class="view<?= $initialView === 'manage' ? ' active' : '' ?>" id="view-manage">
        <div class="banner">
          <h2>Manage</h2>
          <p>Super Admin workspace</p>
        </div>
        <div class="manage-tabs" role="tablist" aria-label="Manage sections">
          <button type="button" class="manage-tab active" id="learnersTab" role="tab" aria-selected="true" aria-controls="manage-learners" data-manage-tab="learners">Manage Learners</button>
          <button type="button" class="manage-tab" id="institutesTab" role="tab" aria-selected="false" aria-controls="manage-institutes" data-manage-tab="institutes">Manage Institutes</button>
        </div>
        <section class="manage-panel" id="manage-learners" role="tabpanel" aria-labelledby="learnersTab">
          <label for="learnerSearch">Search learners</label>
          <input class="learner-search" type="search" id="learnerSearch" placeholder="Search by name, email, or status">
          <p class="manage-message" id="manageLearnersMessage" aria-live="polite">Loading learners...</p>
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
              <tbody id="learnerRows"></tbody>
            </table>
          </div>
        </section>
        <section class="manage-panel" id="manage-institutes" role="tabpanel" aria-labelledby="institutesTab" hidden>
          <div class="box">
            <h3>Manage Institutes</h3>
            <p>Institute management is not available yet.</p>
          </div>
        </section>
      </section>
      <?php endif; ?>

    </main>
  </div>
</div>

<script>
  const API_URL = 'Api/dashboard_handler.php';

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
    <?php if ($isSuperAdmin): ?>
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
  const learnerSearch = document.getElementById('learnerSearch');
  const learnerRows = document.getElementById('learnerRows');
  const manageCsrfToken = <?= json_encode($csrfToken) ?>;
  const manageApiUrl = 'Api/manage_handler.php';
  let allLearners = [];

  function setManageTab(tabName, updateHash) {
    const activeTab = tabName === 'institutes' ? 'institutes' : 'learners';
    manageTabs.forEach(function (tab) {
      const selected = tab.dataset.manageTab === activeTab;
      tab.classList.toggle('active', selected);
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    document.getElementById('manage-learners').hidden = activeTab !== 'learners';
    document.getElementById('manage-institutes').hidden = activeTab !== 'institutes';
    if (updateHash) history.replaceState(null, '', '#manage-' + activeTab);
  }

  function renderLearners(learners) {
    learnerRows.replaceChildren();
    const query = learnerSearch.value.trim().toLocaleLowerCase();
    const filteredLearners = learners.filter(function (learner) {
      const active = Number(learner.is_active) === 1;
      const searchableText = [
        learner.full_name,
        learner.email,
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
      const joined = document.createElement('td');
      joined.textContent = new Date(learner.created_at).toLocaleDateString();
      row.appendChild(joined);
      const statusCell = document.createElement('td');
      const status = document.createElement('span');
      status.className = 'learner-status ' + (active ? 'active' : 'inactive');
      status.textContent = active ? 'Active' : 'Deactivated';
      statusCell.appendChild(status);
      row.appendChild(statusCell);
      const actionCell = document.createElement('td');
      const action = document.createElement('button');
      action.type = 'button';
      action.className = 'learner-action' + (active ? ' deactivate' : '');
      action.textContent = active ? 'Deactivate' : 'Activate';
      action.setAttribute('aria-label', (active ? 'Deactivate ' : 'Activate ') + learner.full_name);
      action.addEventListener('click', function () {
        updateLearnerStatus(learner, !active, action);
      });
      actionCell.appendChild(action);
      row.appendChild(actionCell);
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
    } catch (error) {
      manageLearnersMessage.textContent = error.message || 'Could not load learners.';
      manageLearnersMessage.classList.add('error');
    }
  }

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

  manageTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setManageTab(tab.dataset.manageTab, true);
    });
  });
  if (learnerSearch) {
    learnerSearch.addEventListener('input', function () {
      renderLearners(allLearners);
    });
  }
  if (manageTabs.length) {
    const initialManageTab = initialHash === 'manage-institutes' ? 'institutes' : 'learners';
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
</script>

</body>
</html>