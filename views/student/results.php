<?php
/**
 * Student Exam Results View
 * 
 * Displays exam results with score, percentage, pass/fail status,
 * and detailed feedback for each question
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .result-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .result-summary.passed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .result-summary.failed {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        .question-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
        }
        .question-card.correct {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }
        .question-card.incorrect {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }
        .answer-option {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 4px;
        }
        .answer-option.correct-answer {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .answer-option.student-answer {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        .answer-option.student-correct {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .answer-option.student-incorrect {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .badge-correct {
            background: #28a745;
        }
        .badge-incorrect {
            background: #dc3545;
        }
        .score-display {
            font-size: 3rem;
            font-weight: bold;
        }
        .percentage-display {
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/student/dashboard">Online Exam System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/student/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Result Summary -->
        <div class="result-summary <?php echo $isPassed ? 'passed' : 'failed'; ?>">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3"><?php echo htmlspecialchars($session['exam_title']); ?></h1>
                    <h2 class="mb-2">
                        <?php if ($isPassed): ?>
                            <i class="bi bi-check-circle-fill"></i> Congratulations! You Passed!
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill"></i> You Did Not Pass
                        <?php endif; ?>
                    </h2>
                    <p class="mb-0">
                        Submitted: <?php echo date('F j, Y g:i A', strtotime($session['end_time'])); ?>
                        <?php if ($session['status'] === 'auto_submitted'): ?>
                            <br><span class="badge bg-warning text-dark mt-2">
                                <i class="bi bi-clock-history"></i> Auto-submitted (Time Expired)
                            </span>
                        <?php endif; ?>
                        <?php if (isset($session['tab_switch_count']) && $session['tab_switch_count'] > 0): ?>
                            <br><span class="badge <?php echo $session['tab_switch_count'] >= 3 ? 'bg-danger' : 'bg-warning text-dark'; ?> mt-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> Tab Switches: <?php echo $session['tab_switch_count']; ?>
                                <?php if ($session['tab_switch_count'] >= 3): ?>
                                    (Flagged for Review)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="score-display">
                        <?php echo number_format($session['score'], 1); ?> / <?php echo $session['exam_total_marks']; ?>
                    </div>
                    <div class="percentage-display">
                        <?php echo number_format($session['percentage'], 1); ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Detailed Results -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h3 class="mb-0">Detailed Results</h3>
            </div>
            <div class="card-body">
                <?php if (empty($answers)): ?>
                    <p class="text-muted">No answers recorded for this exam.</p>
                <?php else: ?>
                    <?php foreach ($answers as $index => $answer): ?>
                        <div class="question-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                            <!-- Question Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        Question <?php echo $index + 1; ?>
                                        <span class="badge <?php echo $answer['is_correct'] ? 'badge-correct' : 'badge-incorrect'; ?>">
                                            <?php echo $answer['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                        </span>
                                    </h5>
                                    <small class="text-muted">
                                        Type: <?php echo ucwords(str_replace('_', ' ', $answer['type'])); ?> | 
                                        Marks: <?php echo $answer['marks_awarded'] ?? 0; ?> / <?php echo $answer['marks']; ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Question Content -->
                            <div class="mb-3">
                                <p class="fw-bold mb-2"><?php echo nl2br(htmlspecialchars($answer['content'])); ?></p>
                            </div>

                            <!-- Answer Display -->
                            <?php if ($answer['type'] === 'multiple_choice' || $answer['type'] === 'true_false'): ?>
                                <!-- Multiple Choice / True False -->
                                <div class="mb-2">
                                    <strong>Your Answer:</strong>
                                </div>
                                <?php foreach ($answer['options'] as $option): ?>
                                    <?php 
                                    $isStudentAnswer = in_array($option['id'], $answer['selected_options_array']);
                                    $isCorrectOption = $option['is_correct'];
                                    $cssClass = '';
                                    
                                    if ($isStudentAnswer && $isCorrectOption) {
                                        $cssClass = 'student-correct';
                                    } elseif ($isStudentAnswer && !$isCorrectOption) {
                                        $cssClass = 'student-incorrect';
                                    } elseif (!$isStudentAnswer && $isCorrectOption) {
                                        $cssClass = 'correct-answer';
                                    }
                                    ?>
                                    <div class="answer-option <?php echo $cssClass; ?>">
                                        <?php if ($isStudentAnswer): ?>
                                            <strong>➤</strong>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                        <?php if ($isCorrectOption): ?>
                                            <span class="badge bg-success ms-2">Correct Answer</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($answer['type'] === 'select_all'): ?>
                                <!-- Select All That Apply -->
                                <div class="mb-2">
                                    <strong>Your Answer:</strong>
                                </div>
                                <?php foreach ($answer['options'] as $option): ?>
                                    <?php 
                                    $isStudentAnswer = in_array($option['id'], $answer['selected_options_array']);
                                    $isCorrectOption = $option['is_correct'];
                                    $cssClass = '';
                                    
                                    if ($isStudentAnswer && $isCorrectOption) {
                                        $cssClass = 'student-correct';
                                    } elseif ($isStudentAnswer && !$isCorrectOption) {
                                        $cssClass = 'student-incorrect';
                                    } elseif (!$isStudentAnswer && $isCorrectOption) {
                                        $cssClass = 'correct-answer';
                                    }
                                    ?>
                                    <div class="answer-option <?php echo $cssClass; ?>">
                                        <?php if ($isStudentAnswer): ?>
                                            <strong>☑</strong>
                                        <?php else: ?>
                                            <strong>☐</strong>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                        <?php if ($isCorrectOption): ?>
                                            <span class="badge bg-success ms-2">Correct Answer</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($answer['type'] === 'fill_blank'): ?>
                                <!-- Fill in the Blank -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="answer-option <?php echo $answer['is_correct'] ? 'student-correct' : 'student-incorrect'; ?>">
                                            <strong>Your Answer:</strong><br>
                                            <?php echo htmlspecialchars($answer['answer_text'] ?? '(No answer provided)'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="answer-option correct-answer">
                                            <strong>Correct Answer:</strong><br>
                                            <?php 
                                            $correctOption = array_filter($answer['options'], function($opt) {
                                                return $opt['is_correct'];
                                            });
                                            $correctOption = reset($correctOption);
                                            echo htmlspecialchars($correctOption['option_text'] ?? 'N/A');
                                            ?>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($answer['type'] === 'short_answer'): ?>
                                <!-- Short Answer -->
                                <div class="answer-option student-answer">
                                    <strong>Your Answer:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($answer['answer_text'] ?? '(No answer provided)')); ?>
                                </div>
                                <div class="alert alert-info mt-2 mb-0">
                                    <small><i class="bi bi-info-circle"></i> Short answer questions require manual grading.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mb-4">
            <a href="/student/dashboard" class="btn btn-primary btn-lg">
                <i class="bi bi-house-door"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
