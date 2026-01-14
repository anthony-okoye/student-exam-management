<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">Online Exam System - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/questions">Questions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/exams">Exams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/students">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/analytics">Analytics</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="mb-4">Manage Questions</h1>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Create Question Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $editQuestion ? 'Edit Question' : 'Create New Question'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/questions/create" id="questionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Question Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select question type...</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="select_all">Select All That Apply</option>
                            <option value="true_false">True/False</option>
                            <option value="fill_blank">Fill in the Blank</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Question Content *</label>
                        <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="marks" class="form-label">Marks *</label>
                        <input type="number" class="form-control" id="marks" name="marks" value="1" min="1" required>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="multipleChoiceOptions" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Options (2-4 options) *</label>
                            <div id="optionsContainer">
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="radio" name="correct_option" value="0" class="form-check-input mt-0">
                                    </div>
                                    <input type="text" class="form-control" name="options[]" placeholder="Option 1">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="radio" name="correct_option" value="1" class="form-check-input mt-0">
                                    </div>
                                    <input type="text" class="form-control" name="options[]" placeholder="Option 2">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addOption">Add Option</button>
                            <button type="button" class="btn btn-sm btn-danger" id="removeOption">Remove Option</button>
                            <small class="form-text text-muted d-block mt-2">Select the radio button for the correct answer</small>
                        </div>
                    </div>
                    
                    <!-- Select All That Apply Options -->
                    <div id="selectAllOptions" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Options (2-4 options) *</label>
                            <div id="selectAllOptionsContainer">
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="correct_options[]" value="0" class="form-check-input mt-0">
                                    </div>
                                    <input type="text" class="form-control" name="select_all_options[]" placeholder="Option 1">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="correct_options[]" value="1" class="form-check-input mt-0">
                                    </div>
                                    <input type="text" class="form-control" name="select_all_options[]" placeholder="Option 2">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addSelectAllOption">Add Option</button>
                            <button type="button" class="btn btn-sm btn-danger" id="removeSelectAllOption">Remove Option</button>
                            <small class="form-text text-muted d-block mt-2">Check all correct answers (one or more)</small>
                        </div>
                    </div>
                    
                    <!-- True/False Options -->
                    <div id="trueFalseOptions" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Correct Answer *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_option" id="trueOption" value="0" checked>
                                <label class="form-check-label" for="trueOption">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_option" id="falseOption" value="1">
                                <label class="form-check-label" for="falseOption">False</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fill in the Blank Options -->
                    <div id="fillBlankOptions" style="display: none;">
                        <div class="mb-3">
                            <label for="correct_answer" class="form-label">Correct Answer *</label>
                            <input type="text" class="form-control" id="correct_answer" name="correct_answer" placeholder="Enter the correct answer">
                        </div>
                    </div>
                    
                    <!-- Short Answer Options -->
                    <div id="shortAnswerOptions" style="display: none;">
                        <div class="mb-3">
                            <label for="expected_answer" class="form-label">Expected Answer (for reference) *</label>
                            <textarea class="form-control" id="expected_answer" name="expected_answer" rows="3" placeholder="Enter the expected answer or key points"></textarea>
                            <small class="form-text text-muted">This will be used for manual grading or keyword matching</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editQuestion ? 'Update Question' : 'Create Question'; ?>
                    </button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </form>
            </div>
        </div>
        
        <!-- Questions List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Questions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <p class="text-muted">No questions created yet. Create your first question above.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Content</th>
                                    <th>Marks</th>
                                    <th>Options/Answer</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($question['id']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php 
                                                    $typeLabels = [
                                                        'multiple_choice' => 'Multiple Choice',
                                                        'select_all' => 'Select All',
                                                        'true_false' => 'True/False',
                                                        'fill_blank' => 'Fill in Blank',
                                                        'short_answer' => 'Short Answer'
                                                    ];
                                                    echo $typeLabels[$question['type']] ?? $question['type'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($question['content'], 0, 100)) . (strlen($question['content']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($question['marks']); ?></td>
                                        <td>
                                            <?php if (!empty($question['options'])): ?>
                                                <small>
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <div>
                                                            <?php if ($option['is_correct']): ?>
                                                                <strong class="text-success">✓</strong>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($question['creator_name'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <form method="POST" action="/admin/questions/delete" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/admin.js"></script>
</body>
</html>
