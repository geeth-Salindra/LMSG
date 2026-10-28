<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) && empty($_SESSION['learner_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$surveyType = ($_GET['type'] ?? '360') === '180' ? '180' : '360';
$csrfToken = $_SESSION['csrf_token'];
$learnerName = $_SESSION['user_name'] ?? $_SESSION['learner_name'] ?? 'User';
$initial = mb_strtoupper(mb_substr($learnerName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create <?= htmlspecialchars($surveyType) ?>° Survey · LMSG</title>
  <style>
    :root{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;color:#18221b;background:#f2f5f2;font-synthesis:none}
    *{box-sizing:border-box}
    body{margin:0}
    button,input,select,textarea{font:inherit}
    button{cursor:pointer}
    .app{display:flex;min-height:100vh}
    :root{--accent:#2f8152;--sidebar-bg:#111814;--sidebar-border:#29382f;--sidebar-text:#f1f5f2;--sidebar-muted:#b2c0b6;--sidebar-button:#1b241e;--sidebar-button-hover:#25342a;--header-height:67px;--sidebar-w:250px}
    button{font-family:inherit;cursor:pointer}
    .sidebar{display:flex;flex-direction:column;width:var(--sidebar-w);background:var(--sidebar-bg);border-right:1px solid var(--sidebar-border);color:var(--sidebar-text);padding:0 14px 20px;position:sticky;top:0;height:100vh;flex-shrink:0}
    .brand{display:flex;align-items:center;gap:12px;height:var(--header-height);flex-shrink:0;margin:0 -14px 30px;padding:4px 24px;border-bottom:1px solid var(--sidebar-border)}
    .brand-mark{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#237747,#43a96c);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px}
    .brand-name{font-weight:700;font-size:16px}.brand-sub{font-size:12px;color:var(--sidebar-muted)}
    .nav-btn{width:100%;display:flex;align-items:center;gap:12px;padding:12px 14px;margin-bottom:6px;border:0;border-radius:12px;background:var(--sidebar-button);color:var(--sidebar-text);font-size:14px;font-weight:600;text-align:left;text-decoration:none}
    .nav-btn svg{width:20px;height:20px;flex-shrink:0}
    .nav-btn:hover,.nav-btn:focus-visible{background:var(--sidebar-button-hover);color:#fff}
    .nav-btn.active{background:var(--accent);color:#fff}
    .manage-nav{margin-top:auto}
    .profile-nav{margin-top:auto}
    .sidebar-logout{display:flex;align-items:center;gap:12px;width:100%;padding:12px 14px;margin-top:6px;border:0;border-radius:12px;background:#dc2626;color:#fff;font-size:14px;font-weight:600;text-decoration:none}
    .sidebar-logout:hover,.sidebar-logout:focus-visible{background:#b91c1c}
    .sidebar-logout svg{width:20px;height:20px;flex-shrink:0}
    .main{flex:1;min-width:0;display:flex;flex-direction:column}
    .topbar{height:67px;display:flex;align-items:center;justify-content:space-between;padding:12px 32px;background:#fff;border-bottom:1px solid #e2e9e3}
    .topbar-left{display:flex;align-items:center;gap:14px}
    .menu-toggle{display:none;border:1px solid #e2e9e3;background:#fff;border-radius:10px;width:38px;height:38px;font-size:18px}
    .topbar h1{margin:0;font-size:18px}
    .user{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600}
    .avatar{display:grid;place-items:center;width:38px;height:38px;border-radius:50%;background:#2f8152;color:#fff}
    .logout{color:#18221b;text-decoration:none}
    .content{width:100%;max-width:none;margin:0;padding:28px 32px 48px}
    .heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:24px}
    .heading h2{margin:0 0 5px;font-size:24px}
    .heading p{margin:0;color:#637067;font-size:14px}
    .button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:9px 16px;border:0;border-radius:10px;background:linear-gradient(135deg,#174a30 0%,#2f8152 100%);color:#fff;font-size:14px;font-weight:600;text-decoration:none;transition:background .15s,box-shadow .15s,transform .15s}
    .button:hover{background:linear-gradient(135deg,#123b26 0%,#24643f 100%);box-shadow:0 3px 10px #174a3030}
    .button:active{transform:translateY(1px)}
    .button:focus-visible,.step-tab:focus-visible,.page-chip:focus-visible,.page-delete:focus-visible,.question-delete:focus-visible{outline:2px solid #2f8152;outline-offset:2px}
    .button:disabled{opacity:.6;cursor:not-allowed;box-shadow:none}
    .button.secondary{border:1px solid #e2e9e3;background:#fff;color:#18221b}
    .button.secondary:hover{background:#eaf2ec}
    .steps{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:20px;background:#e9ecea}
    .step-tab{min-height:48px;border:0;border-bottom:2px solid transparent;background:transparent;color:#637067;font-size:13px}
    .step-tab.active{border-bottom-color:#2f8152;background:#fff;color:#18221b;font-weight:700}
    .card{width:100%;max-width:none;margin:0;padding:24px 28px;background:#fff;border:1px solid #e2e9e3;border-radius:8px;box-shadow:0 4px 18px #0f23160d}
    .step-panel[hidden]{display:none}
    .card h3{margin:0 0 18px;font-size:16px}
    .field{margin-bottom:16px}
    .field label,.field-label{display:block;margin-bottom:6px;font-size:13px;font-weight:600}
    .required{color:#c62828}
    input[type=text],input[type=number],select,textarea{width:100%;padding:10px 12px;border:1px solid #dfe5e1;border-radius:5px;background:#fff;color:#18221b;font-size:13px}
    textarea{min-height:90px;resize:vertical}
    input:focus,select:focus,textarea:focus{outline:2px solid #b5ddc2;outline-offset:1px;border-color:#2f8152}
    .radio-row{display:flex;align-items:center;gap:18px;margin:9px 0;color:#39443c;font-size:13px}
    .radio-row label{display:flex;align-items:center;gap:7px}
    .toolbar{display:flex;justify-content:flex-end;gap:9px;margin-top:20px}
    .page-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
    .page-top>div{display:flex;align-items:center;gap:8px}
    .page-list{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
    .page-chip{min-width:130px;padding:12px;border:1px solid #dfe5e1;border-radius:7px;background:#fafbfa;text-align:left}
    .page-chip.active{border-color:#2f8152;background:#eaf2ec}
    .page-chip strong,.page-chip small{display:block}
    .page-chip small{margin-top:4px;color:#637067}
    .page-settings{padding:16px 0;border-top:1px solid #e2e9e3}
    .question-header{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:18px 0 10px}
    .question-card{margin:12px 0;padding:16px;border:1px solid #8fc19e;border-radius:7px}
    .question-card-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;font-size:13px;font-weight:700}
    .question-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .question-fields .field{margin-bottom:10px}
    .rating-config{margin-top:8px;padding:15px;border:1px solid #e2e9e3;border-radius:7px;background:#f8faf8}
    .rating-config h4{margin:0 0 14px;font-size:13px}
    .rating-labels{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}
    .question-delete{border:0;background:transparent;color:#b42318;font-size:13px}
    .page-delete{border:0;background:transparent;color:#b42318;font-size:13px}
    .empty-state{padding:30px;color:#637067;background:#f8faf8;text-align:center;font-size:13px}
    .preview-question{padding:14px 0;border-bottom:1px solid #e2e9e3}
    .preview-question:last-child{border-bottom:0}
    .message{margin:16px 0 0;color:#b42318;font-size:14px}
    .message.success{color:#237747}
    .checkbox-row{display:flex;align-items:center;gap:8px;margin-bottom:18px;font-size:13px}
    @media(max-width:800px){:root{--header-height:63px}.sidebar{position:fixed;left:calc(var(--sidebar-w)*-1);z-index:30;transition:left .2s}.sidebar.open{left:0;box-shadow:0 12px 32px #140f5033}.menu-toggle{display:block}.topbar{padding:12px 16px}.content{padding:22px 16px}.user-name{display:none}}
    @media(max-width:600px){.topbar{height:60px}.content{padding:18px 12px 32px}.heading{align-items:flex-start;flex-direction:column}.card{padding:18px 14px}.question-fields{grid-template-columns:1fr}.steps .step-tab{font-size:11px}}
  </style>
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand"><div class="brand-mark">LM</div><div><div class="brand-name">LMSG</div><div class="brand-sub">Learning Portal</div></div></div>
    <a class="nav-btn" href="dashboard.php#dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a class="nav-btn active" href="survey_180_360_insider.php?type=<?= urlencode($surveyType) ?>#survey360" aria-current="page">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      360/180 Surveys
    </a>
    <?php if (($_SESSION['user_role'] ?? '') === 'super_admin'): ?>
    <a class="nav-btn nav-link manage-nav" href="manage.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 2.94-.08-.03a1.7 1.7 0 0 0-1.83.23l-.07.05h-3.4l-.03-.08a1.7 1.7 0 0 0-1.48-1.16l-.09-.01-2.94-1.7.03-.08a1.7 1.7 0 0 0-.23-1.83l-.05-.07v-3.4l.08-.03a1.7 1.7 0 0 0 1.16-1.48l.01-.09 1.7-2.94.08.03a1.7 1.7 0 0 0 1.83-.23l.07-.05h3.4l.03.08a1.7 1.7 0 0 0 1.48 1.16l.09.01 2.94 1.7-.03.08a1.7 1.7 0 0 0 .23 1.83l.05.07v3.4l-.08.03A1.7 1.7 0 0 0 19.4 15z"/></svg>
      Manage
    </a>
    <?php endif; ?>
    <a class="nav-btn<?= ($_SESSION['user_role'] ?? '') === 'super_admin' ? '' : ' profile-nav' ?>" href="dashboard.php#profile">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
      My Profile
    </a>
    <a class="sidebar-logout" href="Api/logout.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
      Logout
    </a>
  </aside>
  <div class="main">
    <header class="topbar"><div class="topbar-left"><button class="menu-toggle" id="menuToggle" aria-label="Open menu">☰</button><h1>Create <?= htmlspecialchars($surveyType) ?>° Survey</h1></div><div class="user"><span class="avatar"><?= htmlspecialchars($initial) ?></span><span class="user-name"><?= htmlspecialchars($learnerName) ?></span></div></header>
    <main class="content">
      <div class="heading">
        <div><h2>Create <?= htmlspecialchars($surveyType) ?>° Survey</h2><p>Design your survey by adding pages and questions.</p></div>
        <a class="button secondary" href="survey_180_360_insider.php?type=<?= urlencode($surveyType) ?>#survey360">← Back to Surveys</a>
      </div>
      <nav class="steps" aria-label="Survey creation steps">
        <button class="step-tab active" type="button" data-step="0">Survey Info</button>
        <button class="step-tab" type="button" data-step="1">Pages &amp; Questions</button>
        <button class="step-tab" type="button" data-step="2">Preview</button>
      </nav>
      <form id="surveyForm">
        <input type="hidden" name="survey_type" value="<?= htmlspecialchars($surveyType) ?>">
        <section class="card step-panel" data-panel="0">
          <h3>Basic Information</h3>
          <div class="field"><label for="surveyTitle">Survey Title <span class="required">*</span></label><input id="surveyTitle" name="title" type="text" maxlength="150" placeholder="e.g., Annual Leadership Assessment" required></div>
          <div class="field"><label for="surveyDescription">Survey Description</label><textarea id="surveyDescription" name="description" maxlength="2000" placeholder="Describe the purpose of this survey..."></textarea></div>
          <div class="field"><span class="field-label">Question Visibility</span>
            <div class="radio-row"><label><input type="radio" name="question_visibility" value="multiple" checked> Multiple questions per page</label></div>
            <div class="radio-row"><label><input type="radio" name="question_visibility" value="single"> One question at a time</label></div>
          </div>
          <div class="toolbar"><button class="button" type="button" data-next>Continue to Builder</button></div>
        </section>
        <section class="card step-panel" data-panel="1" hidden>
          <label class="checkbox-row"><input id="allowComments" type="checkbox"> Allow overall page comments</label>
          <div class="page-top"><h3 id="pageCount">Survey Pages (1)</h3><div><button class="page-delete" type="button" id="deletePage">Delete Page</button><button class="button" type="button" id="addPage">＋ Add Page</button></div></div>
          <div class="page-list" id="pageList"></div>
          <div class="page-settings">
            <h3 id="pageSettingsTitle">Page 1 Settings</h3>
            <div class="field"><label for="pageName">Page Name <span class="required">*</span></label><input id="pageName" type="text" maxlength="100" required></div>
            <div class="field"><label for="pageDescription">Page Description</label><textarea id="pageDescription" maxlength="1000" placeholder="Describe this section of the survey..."></textarea></div>
          </div>
          <div class="question-header"><strong id="questionCount">Questions (0)</strong><button class="button" type="button" id="addQuestion">＋ Add Question</button></div>
          <div id="questionList"></div>
          <div class="toolbar"><button class="button secondary" type="button" data-back>Back</button><button class="button" type="button" data-next>Continue to Preview</button></div>
        </section>
        <section class="card step-panel" data-panel="2" hidden>
          <h3 id="previewTitle"></h3>
          <p id="previewDescription"></p>
          <div id="previewPages"></div>
          <div class="field" style="margin-top:18px"><label for="visibility">Survey status</label><select id="visibility"><option value="draft">Save as draft</option><option value="published">Publish survey</option></select></div>
          <p class="message" id="formMessage" role="status"></p>
          <div class="toolbar"><button class="button secondary" type="button" data-back>Back to Builder</button><button class="button" type="submit" id="createSurvey">✓ Create Survey</button></div>
        </section>
      </form>
    </main>
  </div>
</div>
<script>
  const csrfToken = <?= json_encode($csrfToken) ?>;
  const surveyType = <?= json_encode($surveyType) ?>;
  const surveyForm = document.getElementById('surveyForm');
  const panels = Array.from(document.querySelectorAll('.step-panel'));
  const stepTabs = Array.from(document.querySelectorAll('.step-tab'));
  const pages = [{ name: 'Page 1', description: '', questions: [] }];
  let activePage = 0;
  let activeStep = 0;

  document.getElementById('menuToggle').addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('open');
  });
  document.querySelectorAll('.sidebar .nav-btn, .sidebar .sidebar-logout').forEach(link => {
    link.addEventListener('click', () => document.querySelector('.sidebar').classList.remove('open'));
  });

  function showStep(step) {
    activeStep = step;
    panels.forEach((panel, index) => { panel.hidden = index !== step; });
    stepTabs.forEach((tab, index) => {
      tab.classList.toggle('active', index === step);
      tab.setAttribute('aria-current', index === step ? 'step' : 'false');
    });
    if (step === 2) renderPreview();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderPages() {
    document.getElementById('pageCount').textContent = `Survey Pages (${pages.length})`;
    document.getElementById('pageList').replaceChildren(...pages.map((page, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `page-chip${index === activePage ? ' active' : ''}`;
      button.innerHTML = `<strong></strong><small></small>`;
      button.querySelector('strong').textContent = page.name || `Page ${index + 1}`;
      button.querySelector('small').textContent = `${page.questions.length} question${page.questions.length === 1 ? '' : 's'}`;
      button.addEventListener('click', () => selectPage(index));
      return button;
    }));
    document.getElementById('pageSettingsTitle').textContent = `Page ${activePage + 1} Settings`;
    document.getElementById('pageName').value = pages[activePage].name;
    document.getElementById('pageDescription').value = pages[activePage].description;
    renderQuestions();
  }

  function selectPage(index) {
    saveCurrentPage();
    activePage = index;
    renderPages();
  }

  function saveCurrentPage() {
    if (!pages[activePage]) return;
    pages[activePage].name = document.getElementById('pageName').value.trim();
    pages[activePage].description = document.getElementById('pageDescription').value.trim();
  }

  function renderQuestions() {
    const list = document.getElementById('questionList');
    const questions = pages[activePage].questions;
    document.getElementById('questionCount').textContent = `Questions (${questions.length})`;
    if (!questions.length) {
      list.innerHTML = '<div class="empty-state">No questions yet. Click “Add Question” to get started.</div>';
      return;
    }
    list.replaceChildren(...questions.map((question, index) => {
      const card = document.createElement('article');
      card.className = 'question-card';
      card.innerHTML = `<div class="question-card-top"><span>Q${index + 1}</span><button class="question-delete" type="button">Delete</button></div>
        <div class="field"><label>Question Text <span class="required">*</span></label><textarea data-field="text" maxlength="1000" placeholder="Enter your question..."></textarea></div>
        <div class="question-fields">
          <div class="field"><label>Question Type</label><select data-field="type"><option value="rating">Rating Scale</option><option value="text">Text Response</option><option value="multiple_choice">Multiple Choice</option></select></div>
          <div class="field"><label>Category</label><input data-field="category" type="text" maxlength="100" placeholder="General"></div>
          <div class="field"><label>Score (Optional)</label><input data-field="score" type="number" min="0" max="100000" step="1" placeholder="0"></div>
        </div>
        <div class="rating-config" data-rating-config>
          <h4>Rating Scale Configuration</h4>
          <div class="question-fields">
            <div class="field"><label>Scale Range</label><select data-field="scale"><option value="1-5">1 to 5</option><option value="1-7">1 to 7</option><option value="0-10">0 to 10</option></select></div>
            <div class="field"><label>Label Type</label><select data-field="labelType"><option value="text">Text Labels</option><option value="number">Numbers</option></select></div>
          </div>
          <div class="field" data-labels-field><label>Customize Labels</label><input data-field="labels" type="text" maxlength="500" placeholder="Poor, Fair, Good, Very Good, Excellent"></div>
        </div>
        <div class="field" data-choices-field hidden><label>Answer choices (comma-separated)</label><input data-field="choices" type="text" maxlength="1000" placeholder="Option 1, Option 2, Option 3"></div>`;
      const text = card.querySelector('[data-field="text"]');
      const type = card.querySelector('[data-field="type"]');
      const category = card.querySelector('[data-field="category"]');
      const score = card.querySelector('[data-field="score"]');
      const choices = card.querySelector('[data-field="choices"]');
      const choicesField = card.querySelector('[data-choices-field]');
      const ratingConfig = card.querySelector('[data-rating-config]');
      const scale = card.querySelector('[data-field="scale"]');
      const labelType = card.querySelector('[data-field="labelType"]');
      const labels = card.querySelector('[data-field="labels"]');
      const labelsField = card.querySelector('[data-labels-field]');
      text.value = question.text;
      type.value = question.type;
      category.value = question.category;
      score.value = question.score;
      choices.value = question.choices;
      choicesField.hidden = type.value !== 'multiple_choice';
      ratingConfig.hidden = type.value !== 'rating';
      scale.value = `${question.scaleMin}-${question.scaleMax}`;
      labelType.value = question.labelType;
      labels.value = question.labels;
      labelsField.hidden = labelType.value !== 'text';
      text.addEventListener('input', () => { question.text = text.value; });
      type.addEventListener('change', () => {
        question.type = type.value;
        choicesField.hidden = type.value !== 'multiple_choice';
        ratingConfig.hidden = type.value !== 'rating';
      });
      category.addEventListener('input', () => { question.category = category.value; });
      score.addEventListener('input', () => { question.score = score.value; });
      choices.addEventListener('input', () => { question.choices = choices.value; });
      scale.addEventListener('change', () => {
        [question.scaleMin, question.scaleMax] = scale.value.split('-').map(Number);
        const labelCount = question.scaleMax - question.scaleMin + 1;
        if (question.labels.split(',').filter(label => label.trim()).length !== labelCount) {
          question.labels = Array.from({ length: labelCount }, (_, labelIndex) => String(question.scaleMin + labelIndex)).join(', ');
        }
        labels.value = question.labels;
        renderPages();
      });
      labelType.addEventListener('change', () => {
        question.labelType = labelType.value;
        labelsField.hidden = labelType.value !== 'text';
      });
      labels.addEventListener('input', () => { question.labels = labels.value; });
      card.querySelector('.question-delete').addEventListener('click', () => {
        questions.splice(index, 1);
        renderPages();
      });
      return card;
    }));
  }

  function renderPreview() {
    saveCurrentPage();
    document.getElementById('previewTitle').textContent = document.getElementById('surveyTitle').value.trim() || `New ${surveyType}° Survey`;
    document.getElementById('previewDescription').textContent = document.getElementById('surveyDescription').value.trim();
    const preview = document.getElementById('previewPages');
    preview.replaceChildren(...pages.map((page, pageIndex) => {
      const section = document.createElement('section');
      const title = document.createElement('h4');
      title.textContent = page.name || `Page ${pageIndex + 1}`;
      section.appendChild(title);
      if (page.description) {
        const description = document.createElement('p');
        description.textContent = page.description;
        section.appendChild(description);
      }
      page.questions.forEach((question, questionIndex) => {
        const item = document.createElement('div');
        item.className = 'preview-question';
        const prompt = document.createElement('strong');
        prompt.textContent = `${questionIndex + 1}. ${question.text || 'Untitled question'}`;
        item.appendChild(prompt);
        const kind = document.createElement('div');
        kind.style.cssText = 'margin-top:8px;color:#637067;font-size:13px';
        kind.textContent = question.type === 'rating'
          ? `Rating: ${question.scaleMin} to ${question.scaleMax}${question.labelType === 'text' && question.labels ? ` · ${question.labels}` : ''}`
          : question.type === 'text' ? 'Text response' : (question.choices || 'Multiple choice');
        item.appendChild(kind);
        section.appendChild(item);
      });
      preview.appendChild(section);
      return section;
    }));
  }

  document.getElementById('pageName').addEventListener('input', () => {
    pages[activePage].name = document.getElementById('pageName').value;
    document.querySelectorAll('.page-chip')[activePage]?.querySelector('strong').replaceChildren(document.createTextNode(pages[activePage].name || `Page ${activePage + 1}`));
  });
  document.getElementById('pageDescription').addEventListener('input', () => { pages[activePage].description = document.getElementById('pageDescription').value; });
  document.getElementById('addPage').addEventListener('click', () => {
    if (pages.length >= 30) {
      document.getElementById('formMessage').textContent = 'A survey can have at most 30 pages.';
      return;
    }
    saveCurrentPage();
    pages.push({ name: `Page ${pages.length + 1}`, description: '', questions: [] });
    activePage = pages.length - 1;
    renderPages();
  });
  document.getElementById('deletePage').addEventListener('click', () => {
    if (pages.length === 1) {
      document.getElementById('formMessage').textContent = 'A survey needs at least one page.';
      return;
    }
    pages.splice(activePage, 1);
    activePage = Math.max(0, activePage - 1);
    renderPages();
  });
  document.getElementById('addQuestion').addEventListener('click', () => {
    pages[activePage].questions.push({ text: '', type: 'rating', category: 'General', score: '', choices: '', scaleMin: 1, scaleMax: 5, labelType: 'text', labels: 'Poor, Fair, Good, Very Good, Excellent' });
    renderPages();
  });
  stepTabs.forEach(tab => tab.addEventListener('click', () => {
    if (Number(tab.dataset.step) <= activeStep + 1) showStep(Number(tab.dataset.step));
  }));
  document.querySelectorAll('[data-next]').forEach(button => button.addEventListener('click', () => {
    if (activeStep === 0 && !document.getElementById('surveyTitle').reportValidity()) return;
    if (activeStep === 1) saveCurrentPage();
    showStep(Math.min(activeStep + 1, 2));
  }));
  document.querySelectorAll('[data-back]').forEach(button => button.addEventListener('click', () => showStep(Math.max(activeStep - 1, 0))));

  surveyForm.addEventListener('submit', async event => {
    event.preventDefault();
    saveCurrentPage();
    const message = document.getElementById('formMessage');
    const submit = document.getElementById('createSurvey');
    const payload = {
      survey_type: surveyType,
      title: document.getElementById('surveyTitle').value.trim(),
      description: document.getElementById('surveyDescription').value.trim(),
      question_visibility: surveyForm.querySelector('[name="question_visibility"]:checked').value,
      allow_comments: document.getElementById('allowComments').checked,
      status: document.getElementById('visibility').value,
      pages: pages.map(page => ({
        name: page.name.trim(),
        description: page.description.trim(),
        questions: page.questions.map(question => ({
          text: question.text.trim(),
          type: question.type,
          category: question.category.trim(),
          score: question.score === '' ? null : Number(question.score),
          choices: question.choices.split(',').map(choice => choice.trim()).filter(Boolean),
          scale_min: question.type === 'rating' ? question.scaleMin : null,
          scale_max: question.type === 'rating' ? question.scaleMax : null,
          rating_labels: question.type === 'rating' && question.labelType === 'text' ? question.labels.split(',').map(label => label.trim()).filter(Boolean) : []
        }))
      }))
    };
    submit.disabled = true;
    message.className = 'message';
    message.textContent = 'Saving survey...';
    try {
      const response = await fetch('Api/create_180_360_survey_hamdler.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(payload)
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Could not save this survey.');
      message.classList.add('success');
      message.textContent = 'Survey created. Returning to your surveys...';
      window.location.href = `survey_180_360_insider.php?type=${surveyType}&created=1#survey360`;
    } catch (error) {
      message.textContent = error.message || 'Could not save this survey.';
      submit.disabled = false;
    }
  });

  renderPages();
</script>
</body>
</html>
