<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo SecurityService::generateCSRFToken(); ?>">
    <title><?php echo htmlspecialchars($exam['title']); ?> - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .timer-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .timer {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .timer.warning {
            color: #ffc107;
        }
        .timer.danger {
            color: #dc3545;
        }
        .question-card {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
        }
        .question-number {
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .question-content {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .option-label {
            display: block;
            padding: 10px 15px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-label:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }
        .option-label input {
            margin-right: 10px;
        }
        .submit-section {
            position: sticky;
            bottom: 0;
            background: #fff;
            padding: 20px;
            border-top: 2px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .auto-save-indicator {
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }
        .auto-save-indicator.saving {
            background: #ffc107;
        }
        .auto-save-indicator.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Timer Display -->
    <div class="timer-container">
        <div class="text-center">
            <small class="d-block text-muted">Time Remaining</small>
            <div id="timer" class="timer" data-session-id="<?php echo $sessionId; ?>" data-remaining="<?php echo $remainingTime; ?>">
                --:--
            </div>
        </div>
    </div>

    <!-- Auto-save Indicator -->
    <div id="autoSaveIndicator" class="auto-save-indicator">
        Saved
    </div>

    <div class="container mt-5 mb-5" style="padding-top: 20px;">
        <div class="row">
            <div class="col-md-12">
                <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></p>
                <hr>
                
                <form id="examForm" method="POST" action="/student/exam/submit">
                    <input type="hidden" name="session_id" value="<?php echo $sessionId; ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card" data-question-id="<?php echo $question['id']; ?>">
                            <div class="question-number">
                                Question <?php echo ($index + 1); ?> 
                                <span class="badge bg-secondary"><?php echo $question['marks']; ?> mark<?php echo $question['marks'] > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="question-content">
                                <?php echo nl2br(htmlspecialchars($question['content'])); ?>
                            </div>
                            
                            <?php
                            // Get options for this question
                            $pdo = getDBConnection();
                            $optionsStmt = $pdo->prepare(
                                "SELECT * FROM question_options 
                                 WHERE question_id = :question_id 
                                 ORDER BY option_order ASC"
                            );
                            $optionsStmt->execute(['question_id' => $question['id']]);
                            $options = $optionsStmt->fetchAll();
                            
                            // Get existing answer if any
                            $existingAnswer = $answersMap[$question['id']] ?? null;
                            $selectedOptions = [];
                            if ($existingAnswer && $existingAnswer['selected_options']) {
                                $selectedOptions = json_decode($existingAnswer['selected_options'], true) ?? [];
                            }
                            $answerText = $existingAnswer['answer_text'] ?? '';
                            ?>
                            
                            <div class="answer-section">
                                <?php if ($question['type'] === 'multiple_choice' || $question['type'] === 'true_false'): ?>
                                    <!-- Radio buttons for single choice -->
                                    <?php foreach ($options as $option): ?>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="question_<?php echo $question['id']; ?>" 
                                                   value="<?php echo $option['id']; ?>"
                                                   class="answer-input"
                                                   data-question-id="<?php echo $question['id']; ?>"
                                                   data-question-type="<?php echo $question['type']; ?>"
                                                   <?php echo in_array($option['id'], $selectedOptions) ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($question['type'] === 'select_all'): ?>
                                    <!-- Checkboxes for multiple choice -->
                                    <?php foreach ($options as $option): ?>
                                        <label class="option-label">
                                            <input type="checkbox" 
                                                   name="question_<?php echo $question['id']; ?>[]" 
                                                   value="<?php echo $option['id']; ?>"
                                                   class="answer-input"
                                                   data-question-id="<?php echo $question['id']; ?>"
                                                   data-question-type="<?php echo $question['type']; ?>"
                                                   <?php echo in_array($option['id'], $selectedOptions) ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($question['type'] === 'fill_blank' || $question['type'] === 'short_answer'): ?>
                                    <!-- Text input for fill in blank or short answer -->
                                    <?php if ($question['type'] === 'fill_blank'): ?>
                                        <input type="text" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               class="form-control answer-input"
                                               data-question-id="<?php echo $question['id']; ?>"
                                               data-question-type="<?php echo $question['type']; ?>"
                                               value="<?php echo htmlspecialchars($answerText); ?>"
                                               placeholder="Enter your answer">
                                    <?php else: ?>
                                        <textarea name="question_<?php echo $question['id']; ?>" 
                                                  class="form-control answer-input"
                                                  data-question-id="<?php echo $question['id']; ?>"
                                                  data-question-type="<?php echo $question['type']; ?>"
                                                  rows="4"
                                                  placeholder="Enter your answer"><?php echo htmlspecialchars($answerText); ?></textarea>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="submit-section">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 text-muted">
                                    <small>Your answers are being saved automatically</small>
                                </p>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    Submit Exam
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/timer.js"></script>
    <script src="/js/exam.js"></script>
</body>
</html>
