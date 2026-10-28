<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

function createSurveyReply($status, $success, $message, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if (empty($_SESSION['user_id']) && empty($_SESSION['learner_id'])) {
    createSurveyReply(401, false, 'Your session has expired. Please log in again.');
}

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    createSurveyReply(405, false, 'Method not allowed.');
}

require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = getConnection();
    $creatorId = (int)($_SESSION['user_id'] ?? $_SESSION['learner_id']);

    if ($method === 'GET') {
        $surveyType = $_GET['type'] ?? null;
        if ($surveyType !== null && !in_array($surveyType, ['180', '360'], true)) {
            createSurveyReply(422, false, 'Survey type must be 180 or 360.');
        }

        $typeFilter = $surveyType === null ? '' : ' AND surveys.survey_type = ?';
        $stmt = $pdo->prepare(
            "SELECT surveys.id, surveys.title, surveys.survey_type, surveys.status,
                    surveys.description,
                    DATE_FORMAT(surveys.created_at, '%m/%d/%Y') AS created_at,
                    COUNT(DISTINCT pages.id) AS page_count,
                    COUNT(questions.id) AS question_count
             FROM surveys
             LEFT JOIN survey_pages AS pages ON pages.survey_id = surveys.id
             LEFT JOIN survey_questions AS questions ON questions.page_id = pages.id
             WHERE surveys.creator_id = ?$typeFilter
             GROUP BY surveys.id
             ORDER BY surveys.created_at DESC, surveys.id DESC"
        );
        $parameters = [$creatorId];
        if ($surveyType !== null) {
            $parameters[] = $surveyType;
        }
        $stmt->execute($parameters);
        createSurveyReply(200, true, 'Surveys loaded.', ['surveys' => $stmt->fetchAll()]);
    }

    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
        createSurveyReply(403, false, 'Security token expired. Refresh the page and try again.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        createSurveyReply(422, false, 'A valid survey is required.');
    }

    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $surveyType = (string)($data['survey_type'] ?? '');
    $visibility = (string)($data['question_visibility'] ?? '');
    $status = (string)($data['status'] ?? '');
    $pages = $data['pages'] ?? null;

    if ($title === '' || mb_strlen($title) > 150) {
        createSurveyReply(422, false, 'Enter a survey title of 150 characters or fewer.');
    }
    if (mb_strlen($description) > 2000) {
        createSurveyReply(422, false, 'Survey description must be 2,000 characters or fewer.');
    }
    if (!in_array($surveyType, ['180', '360'], true)
        || !in_array($visibility, ['multiple', 'single'], true)
        || !in_array($status, ['draft', 'published'], true)
        || !is_array($pages)
        || count($pages) < 1
        || count($pages) > 30
    ) {
        createSurveyReply(422, false, 'Survey settings or pages are invalid.');
    }

    $questionCount = 0;
    foreach ($pages as $pageIndex => $page) {
        if (!is_array($page)) {
            createSurveyReply(422, false, 'Each survey page must be valid.');
        }
        $pageName = trim((string)($page['name'] ?? ''));
        $pageDescription = trim((string)($page['description'] ?? ''));
        $questions = $page['questions'] ?? null;
        if ($pageName === '' || mb_strlen($pageName) > 100 || mb_strlen($pageDescription) > 1000 || !is_array($questions)) {
            createSurveyReply(422, false, 'Each page needs a name and valid questions.');
        }
        foreach ($questions as $question) {
            if (!is_array($question)) {
                createSurveyReply(422, false, 'Each question must be valid.');
            }
            $questionText = trim((string)($question['text'] ?? ''));
            $questionType = (string)($question['type'] ?? '');
            $category = trim((string)($question['category'] ?? ''));
            $score = $question['score'] ?? null;
            $choices = $question['choices'] ?? [];
            $scaleMin = $question['scale_min'] ?? null;
            $scaleMax = $question['scale_max'] ?? null;
            $ratingLabels = $question['rating_labels'] ?? [];
            if ($questionText === '' || mb_strlen($questionText) > 1000
                || !in_array($questionType, ['rating', 'text', 'multiple_choice'], true)
                || mb_strlen($category) > 100
                || ($score !== null && (!is_numeric($score) || (float)$score < 0 || (float)$score > 100000))
                || !is_array($choices)
                || count($choices) > 20
                || !is_array($ratingLabels)
                || count($ratingLabels) > 11
            ) {
                createSurveyReply(422, false, 'Review the question text, type, category, score, and choices.');
            }
            if ($questionType === 'rating'
                && (!in_array($scaleMin, [0, 1], true)
                    || !in_array($scaleMax, [5, 7, 10], true)
                    || $scaleMax <= $scaleMin
                    || ($ratingLabels !== [] && count($ratingLabels) !== $scaleMax - $scaleMin + 1))
            ) {
                createSurveyReply(422, false, 'Rating scale labels must match a supported scale range.');
            }
            if ($questionType === 'multiple_choice' && count($choices) < 2) {
                createSurveyReply(422, false, 'Multiple-choice questions need at least two answer choices.');
            }
            foreach ($choices as $choice) {
                if (!is_string($choice) || trim($choice) === '' || mb_strlen($choice) > 200) {
                    createSurveyReply(422, false, 'Each answer choice must contain 1 to 200 characters.');
                }
            }
            foreach ($ratingLabels as $label) {
                if (!is_string($label) || trim($label) === '' || mb_strlen($label) > 50) {
                    createSurveyReply(422, false, 'Each rating label must contain 1 to 50 characters.');
                }
            }
            $questionCount++;
        }
    }

    if ($questionCount > 300) {
        createSurveyReply(422, false, 'A survey can have at most 300 questions.');
    }
    if ($status === 'published' && $questionCount === 0) {
        createSurveyReply(422, false, 'Add at least one question before publishing the survey.');
    }

    $pdo->beginTransaction();
    $surveyStmt = $pdo->prepare(
        'INSERT INTO surveys (creator_id, title, description, survey_type, question_visibility, allow_comments, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $surveyStmt->execute([
        $creatorId,
        $title,
        $description,
        $surveyType,
        $visibility,
        !empty($data['allow_comments']) ? 1 : 0,
        $status
    ]);
    $surveyId = (int)$pdo->lastInsertId();
    $pageStmt = $pdo->prepare('INSERT INTO survey_pages (survey_id, page_order, name, description) VALUES (?, ?, ?, ?)');
    $questionStmt = $pdo->prepare(
        'INSERT INTO survey_questions (page_id, question_order, question_text, question_type, category, score, choices, scale_min, scale_max, rating_labels)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($pages as $pageIndex => $page) {
        $pageStmt->execute([$surveyId, $pageIndex + 1, trim($page['name']), trim($page['description'] ?? '')]);
        $pageId = (int)$pdo->lastInsertId();
        foreach ($page['questions'] as $questionIndex => $question) {
            $questionStmt->execute([
                $pageId,
                $questionIndex + 1,
                trim($question['text']),
                $question['type'],
                trim($question['category'] ?? ''),
                isset($question['score']) ? $question['score'] : null,
                json_encode(array_values($question['choices'] ?? []), JSON_THROW_ON_ERROR),
                $question['scale_min'] ?? null,
                $question['scale_max'] ?? null,
                json_encode(array_values($question['rating_labels'] ?? []), JSON_THROW_ON_ERROR)
            ]);
        }
    }
    $pdo->commit();

    createSurveyReply(201, true, 'Survey created successfully.', ['survey_id' => $surveyId]);
} catch (PDOException | JsonException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[create_180_360_survey_hamdler] ' . $e->getMessage());
    createSurveyReply(500, false, 'Could not save the survey. Verify that database migrations have been run.');
}
