<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Online Examination System</title>
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
                        <a class="nav-link" href="/admin/exams">Exams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/students">Students</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Students</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
                <i class="bi bi-plus-circle me-2"></i>Create New Student
            </button>
        </div>

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

        <!-- Students Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">All Students (<?php echo count($students); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($students)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted mt-3 mb-0">No students found. Create your first student to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3" style="width: 60px;">ID</th>
                                    <th style="width: 200px;">Username</th>
                                    <th style="width: 250px;">Email</th>
                                    <th class="text-center" style="width: 100px;">Exams</th>
                                    <th class="text-center" style="width: 120px;">Avg Score</th>
                                    <th style="width: 120px;">Joined</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $examCount = count($student['exam_history']);
                                    $completedExams = array_filter($student['exam_history'], function($exam) {
                                        return $exam['status'] === 'completed' || $exam['status'] === 'auto_submitted';
                                    });
                                    $avgScore = 0;
                                    if (count($completedExams) > 0) {
                                        $totalScore = array_sum(array_column($completedExams, 'percentage'));
                                        $avgScore = $totalScore / count($completedExams);
                                    }
                                    ?>
                                    <tr>
                                        <td class="px-3 fw-medium text-muted">#<?php echo htmlspecialchars($student['id']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?php echo strtoupper(substr($student['username'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($student['username']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?php echo htmlspecialchars($student['email']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info"><?php echo $examCount; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (count($completedExams) > 0): ?>
                                                <span class="badge <?php echo $avgScore >= 60 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?>">
                                                    <?php echo number_format($avgScore, 1); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">No data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewHistoryModal<?php echo $student['id']; ?>"
                                                        title="View History">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="/admin/students?edit=<?php echo $student['id']; ?>" 
                                                   class="btn btn-outline-warning"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['username'], ENT_QUOTES); ?>')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

        <!-- Exam History Modals (Outside the table) -->
        <?php if (!empty($students)): ?>
            <?php foreach ($students as $student): ?>
                <div class="modal fade" id="viewHistoryModal<?php echo $student['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Exam History - <?php echo htmlspecialchars($student['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if (empty($student['exam_history'])): ?>
                                                        <p class="text-muted">No exam history available.</p>
                                                    <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Exam</th>
                                                                        <th>Date</th>
                                                                        <th>Status</th>
                                                                        <th>Score</th>
                                                                        <th>Percentage</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($student['exam_history'] as $exam): ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                                                            <td><?php echo date('M d, Y H:i', strtotime($exam['start_time'])); ?></td>
                                                                            <td>
                                                                                <?php
                                                                                $statusClass = 'secondary';
                                                                                $statusText = ucfirst(str_replace('_', ' ', $exam['status']));
                                                                                if ($exam['status'] === 'completed' || $exam['status'] === 'auto_submitted') {
                                                                                    $statusClass = 'success';
                                                                                } elseif ($exam['status'] === 'in_progress') {
                                                                                    $statusClass = 'warning';
                                                                                }
                                                                                ?>
                                                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                                                    <?php echo $statusText; ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($exam['score'] !== null): ?>
                                                                                    <?php echo number_format($exam['score'], 1); ?> / <?php echo $exam['total_marks']; ?>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($exam['percentage'] !== null): ?>
                                                                                    <span class="badge <?php echo $exam['percentage'] >= 60 ? 'bg-success' : 'bg-danger'; ?>">
                                                                                        <?php echo number_format($exam['percentage'], 1); ?>%
                                                                                    </span>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Exam History Modals (Outside the table) -->
        <?php if (!empty($students)): ?>
            <?php foreach ($students as $student): ?>
                <div class="modal fade" id="viewHistoryModal<?php echo $student['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Exam History - <?php echo htmlspecialchars($student['username']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (empty($student['exam_history'])): ?>
                                    <p class="text-muted">No exam history available.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Exam</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Score</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($student['exam_history'] as $exam): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($exam['start_time'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = 'secondary';
                                                            $statusText = ucfirst(str_replace('_', ' ', $exam['status']));
                                                            if ($exam['status'] === 'completed' || $exam['status'] === 'auto_submitted') {
                                                                $statusClass = 'success';
                                                            } elseif ($exam['status'] === 'in_progress') {
                                                                $statusClass = 'warning';
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                                <?php echo $statusText; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($exam['score'] !== null): ?>
                                                                <?php echo number_format($exam['score'], 1); ?> / <?php echo $exam['total_marks']; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($exam['percentage'] !== null): ?>
                                                                <span class="badge <?php echo $exam['percentage'] >= 60 ? 'bg-success' : 'bg-danger'; ?>">
                                                                    <?php echo number_format($exam['percentage'], 1); ?>%
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Create Student Modal -->
    <div class="modal fade" id="createStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/admin/students/create">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <?php if ($editStudent): ?>
    <div class="modal fade show" id="editStudentModal" tabindex="-1" style="display: block;" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/admin/students/update">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Student</h5>
                        <a href="/admin/students" class="btn-close"></a>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
                        <input type="hidden" name="student_id" value="<?php echo $editStudent['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_username" name="username" 
                                   value="<?php echo htmlspecialchars($editStudent['username']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" 
                                   value="<?php echo htmlspecialchars($editStudent['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="/admin/students" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <!-- Delete Confirmation Form (Hidden) -->
    <form id="deleteForm" method="POST" action="/admin/students/delete" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken(); ?>">
        <input type="hidden" name="student_id" id="deleteStudentId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(studentId, username) {
            if (confirm('Are you sure you want to delete student "' + username + '"? This will also delete all their exam history.')) {
                document.getElementById('deleteStudentId').value = studentId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
