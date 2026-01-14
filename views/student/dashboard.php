<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="/student/dashboard">Online Exam System - Student</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/student/dashboard">Dashboard</a>
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

    <!-- Main Content -->
    <div class="container mt-5">
        <h1 class="mb-4">My Exams</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($exams)): ?>
            <div class="alert alert-info">
                No exams are currently assigned to you. Please check back later or contact your administrator.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($exams as $exam): ?>
                    <?php 
                        $examId = $exam['id'];
                        $status = $examStatuses[$examId] ?? null;
                        $sessionId = $status['session_id'] ?? null;
                        $statusText = 'Not Started';
                        $statusClass = 'bg-secondary';
                        $canTake = true;
                        $score = null;
                        $retakeAllowed = $exam['retake_allowed'] ?? false;
                        
                        if ($status) {
                            if ($status['status'] === 'in_progress') {
                                $statusText = 'In Progress';
                                $statusClass = 'bg-warning text-dark';
                            } elseif ($status['status'] === 'completed' || $status['status'] === 'auto_submitted') {
                                $statusText = 'Completed';
                                $statusClass = 'bg-success';
                                $canTake = $retakeAllowed; // Can only retake if allowed
                                $score = $status['percentage'];
                            }
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                <?php if ($score !== null): ?>
                                    <span class="badge bg-info">Score: <?php echo number_format($score, 1); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars($exam['description'] ?? 'No description available'); ?>
                                </p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-clock me-2"></i><strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes</li>
                                    <li><i class="bi bi-trophy me-2"></i><strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></li>
                                    <li><i class="bi bi-question-circle me-2"></i><strong>Questions:</strong> <?php echo $exam['question_count']; ?></li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <?php if ($canTake): ?>
                                    <a href="/student/exam/instructions?id=<?php echo $examId; ?>" class="btn btn-primary w-100">
                                        <?php echo ($status && $status['status'] === 'in_progress') ? 'Continue Exam' : 'View Details'; ?>
                                    </a>
                                <?php else: ?>
                                    <?php if ($sessionId): ?>
                                        <a href="/student/exam/results?session_id=<?php echo $sessionId; ?>" class="btn btn-outline-success w-100">
                                            View Results
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-outline-secondary w-100 disabled">No Results Available</span>
                                    <?php endif; ?>
                                    <?php if (!$retakeAllowed): ?>
                                        <small class="text-muted d-block mt-2 text-center">Retakes not allowed</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
