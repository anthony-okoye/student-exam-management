<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - Instructions</title>
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

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Exam Instructions</h4>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title mb-4"><?php echo htmlspecialchars($exam['title']); ?></h2>
                        
                        <?php if (!empty($exam['description'])): ?>
                            <div class="alert alert-light">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($exam['description'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card bg-light border-0 shadow-sm">
                                    <div class="card-body text-center py-4">
                                        <div class="display-4 text-primary fw-bold"><?php echo $exam['duration']; ?></div>
                                        <p class="card-text text-muted mb-0 mt-2">Minutes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card bg-light border-0 shadow-sm">
                                    <div class="card-body text-center py-4">
                                        <div class="display-4 text-success fw-bold"><?php echo $exam['total_marks']; ?></div>
                                        <p class="card-text text-muted mb-0 mt-2">Total Marks</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light border-0 shadow-sm">
                                    <div class="card-body text-center py-4">
                                        <div class="display-4 text-info fw-bold"><?php echo $exam['question_count']; ?></div>
                                        <p class="card-text text-muted mb-0 mt-2">Questions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Important Instructions:</h5>
                            <ul class="mb-0">
                                <li>Once you start the exam, the timer will begin counting down.</li>
                                <li>The exam will be automatically submitted when the timer reaches zero.</li>
                                <li>You can submit the exam manually at any time before the timer expires.</li>
                                <li>Make sure you have a stable internet connection.</li>
                                <li>Do not refresh the page during the exam unless necessary.</li>
                                <li>Your answers are saved automatically as you progress.</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-shield-exclamation me-2"></i>Anti-Cheating Policy:</h5>
                            <ul class="mb-0">
                                <li><strong>Do not switch tabs or windows</strong> during the exam.</li>
                                <li>Tab switches are monitored and logged automatically.</li>
                                <li>Sessions with 3 or more tab switches will be flagged for review.</li>
                                <li>Flagged sessions may result in penalties or exam invalidation.</li>
                                <li>Stay focused on the exam tab for the entire duration.</li>
                            </ul>
                        </div>
                        
                        <?php 
                            $canStart = true;
                            $buttonText = 'Start Exam';
                            $buttonClass = 'btn-success';
                            
                            if ($examStatus) {
                                if ($examStatus['status'] === 'in_progress') {
                                    $buttonText = 'Continue Exam';
                                    $buttonClass = 'btn-warning';
                                } elseif ($examStatus['status'] === 'completed' || $examStatus['status'] === 'auto_submitted') {
                                    $canStart = false;
                                }
                            }
                        ?>
                        
                        <?php if ($canStart): ?>
                            <div class="d-grid gap-2">
                                <a href="/student/exam/start?id=<?php echo $exam['id']; ?>" class="btn <?php echo $buttonClass; ?> btn-lg">
                                    <?php echo $buttonText; ?>
                                </a>
                                <a href="/student/dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5>Exam Completed!</h5>
                                <p class="mb-0">You have already completed this exam.</p>
                                <?php if ($examStatus['percentage'] !== null): ?>
                                    <p class="mb-0"><strong>Your Score:</strong> <?php echo number_format($examStatus['percentage'], 1); ?>%</p>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="/student/exam/results?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-lg">View Results</a>
                                <a href="/student/dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
