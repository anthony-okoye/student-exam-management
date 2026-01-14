<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Online Examination System</title>
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
                        <a class="nav-link" href="/admin/questions">Questions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/exams">Exams</a>
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
        <h1 class="mb-4">Manage Exams</h1>
        
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
        
        <?php if (isset($assignExamId) && isset($assignExam)): ?>
            <!-- Question Assignment Interface -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assign Questions to: <?php echo htmlspecialchars($assignExam['title']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin/exams/assign-questions">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                        <input type="hidden" name="exam_id" value="<?php echo $assignExamId; ?>">
                        
                        <p class="text-muted">
                            Select questions to include in this exam. Duration will be calculated automatically (2 minutes per question).
                        </p>
                        
                        <?php if (empty($allQuestions)): ?>
                            <div class="alert alert-warning">
                                No questions available. Please <a href="/admin/questions">create questions</a> first.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Content</th>
                                            <th>Marks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allQuestions as $question): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="question_ids[]" 
                                                           value="<?php echo $question['id']; ?>" 
                                                           class="form-check-input question-checkbox"
                                                           <?php echo in_array($question['id'], $assignedQuestionIds) ? 'checked' : ''; ?>>
                                                </td>
                                                <td><?php echo htmlspecialchars($question['id']); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php 
                                                            $typeLabels = [
                                                                'multiple_choice' => 'Multiple Choice',
                                                                'true_false' => 'True/False',
                                                                'fill_blank' => 'Fill in Blank'
                                                            ];
                                                            echo $typeLabels[$question['type']] ?? $question['type'];
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($question['content'], 0, 80)) . (strlen($question['content']) > 80 ? '...' : ''); ?></td>
                                                <td><?php echo htmlspecialchars($question['marks']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Save Question Assignment</button>
                                <a href="/admin/exams" class="btn btn-secondary">Cancel</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php elseif (isset($assignStudentsExamId) && isset($assignStudentsExam)): ?>
            <!-- Student Assignment Interface -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assign Students to: <?php echo htmlspecialchars($assignStudentsExam['title']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin/exams/assign-students">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                        <input type="hidden" name="exam_id" value="<?php echo $assignStudentsExamId; ?>">
                        
                        <p class="text-muted">
                            Select students who can take this exam. Only assigned students will see this exam in their dashboard.
                        </p>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="retake_allowed" value="1" id="retakeAllowed">
                                <label class="form-check-label" for="retakeAllowed">
                                    Allow students to retake this exam after completion
                                </label>
                            </div>
                        </div>
                        
                        <?php if (empty($allStudents)): ?>
                            <div class="alert alert-warning">
                                No students available. Please <a href="/admin/students">create students</a> first.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAllStudents" class="form-check-input">
                                            </th>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allStudents as $student): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="student_ids[]" 
                                                           value="<?php echo $student['id']; ?>" 
                                                           class="form-check-input student-checkbox"
                                                           <?php echo in_array($student['id'], $assignedStudentIds) ? 'checked' : ''; ?>>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($student['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Save Student Assignment</button>
                                <a href="/admin/exams" class="btn btn-secondary">Cancel</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Create/Edit Exam Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $editExam ? 'Edit Exam' : 'Create New Exam'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $editExam ? '/admin/exams/update' : '/admin/exams/create'; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                        <?php if ($editExam): ?>
                            <input type="hidden" name="exam_id" value="<?php echo $editExam['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $editExam ? htmlspecialchars($editExam['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editExam ? htmlspecialchars($editExam['description']) : ''; ?></textarea>
                        </div>
                        
                        <?php if ($editExam): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" 
                                               value="<?php echo $editExam['duration']; ?>" min="0" readonly>
                                        <small class="form-text text-muted">Auto-calculated: 2 min × question count</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="total_marks" class="form-label">Total Marks</label>
                                        <input type="number" class="form-control" id="total_marks" name="total_marks" 
                                               value="<?php echo $editExam['total_marks']; ?>" min="0" readonly>
                                        <small class="form-text text-muted">Auto-calculated from questions</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="draft" <?php echo ($editExam['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="in_progress" <?php echo ($editExam['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="closed" <?php echo ($editExam['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="draft" selected>Draft</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="alert alert-info">
                                <strong>Note:</strong> Duration and total marks will be calculated automatically when you assign questions to this exam.
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editExam ? 'Update Exam' : 'Create Exam'; ?>
                        </button>
                        <?php if ($editExam): ?>
                            <a href="/admin/exams" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Exams List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Exams</h5>
            </div>
            <div class="card-body">
                <?php if (empty($exams)): ?>
                    <p class="text-muted">No exams created yet. Create your first exam above.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Questions</th>
                                    <th>Duration</th>
                                    <th>Total Marks</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['id']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($exam['description'], 0, 50)) . (strlen($exam['description']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $exam['question_count']; ?> questions</span>
                                        </td>
                                        <td><?php echo htmlspecialchars($exam['duration']); ?> min</td>
                                        <td><?php echo htmlspecialchars($exam['total_marks']); ?></td>
                                        <td>
                                            <?php
                                                $statusColors = [
                                                    'draft' => 'secondary',
                                                    'in_progress' => 'primary',
                                                    'closed' => 'danger'
                                                ];
                                                $statusColor = $statusColors[$exam['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $exam['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($exam['creator_name'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/admin/exams?assign=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-info" title="Assign Questions">
                                                    Questions
                                                </a>
                                                <a href="/admin/exams?assign_students=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-success" title="Assign Students">
                                                    Students
                                                </a>
                                                <a href="/admin/exams?edit=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-warning" title="Edit Exam">
                                                    Edit
                                                </a>
                                                <form method="POST" action="/admin/exams/delete" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this exam?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                                                    <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Exam">Delete</button>
                                                </form>
                                            </div>
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
    <script>
        // Select all checkbox functionality for questions
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.question-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all checkbox when individual checkboxes change
        document.querySelectorAll('.question-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.question-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
                const selectAllCheckbox = document.getElementById('selectAll');
                
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                }
            });
        });
        
        // Select all checkbox functionality for students
        document.getElementById('selectAllStudents')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all checkbox when individual student checkboxes change
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.student-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
                const selectAllCheckbox = document.getElementById('selectAllStudents');
                
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                }
            });
        });
    </script>
</body>
</html>
