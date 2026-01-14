<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                        <a class="nav-link" href="/admin/students">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/analytics">Analytics</a>
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
    <div class="container-fluid mt-4">
        <h1 class="mb-4">Analytics Dashboard</h1>

        <!-- Overall Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Exams</h5>
                        <h2><?php echo $overallStats['total_exams'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Students</h5>
                        <h2><?php echo $overallStats['total_students'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Questions</h5>
                        <h2><?php echo $overallStats['total_questions'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Completed Sessions</h5>
                        <h2><?php echo $overallStats['total_sessions'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Scores Per Exam -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Average Scores Per Exam</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($avgScores)): ?>
                            <canvas id="avgScoresChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted">No exam data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pass/Fail Rates -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Pass/Fail Rates (60% Threshold)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($passFailRates)): ?>
                            <canvas id="passFailChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted">No exam completion data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question-Level Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-question-circle-fill me-2"></i>Question-Level Statistics (% Correct)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($questionStats)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Question</th>
                                            <th>Type</th>
                                            <th>Total Answers</th>
                                            <th>Correct Answers</th>
                                            <th>% Correct</th>
                                            <th>Difficulty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questionStats as $stat): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(substr($stat['content'], 0, 100)) . (strlen($stat['content']) > 100 ? '...' : ''); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($stat['type']); ?></span></td>
                                                <td><?php echo $stat['total_answers']; ?></td>
                                                <td><?php echo $stat['correct_answers']; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 25px;">
                                                        <div class="progress-bar <?php 
                                                            echo $stat['percentage_correct'] >= 70 ? 'bg-success' : 
                                                                ($stat['percentage_correct'] >= 40 ? 'bg-warning' : 'bg-danger'); 
                                                        ?>" role="progressbar" 
                                                             style="width: <?php echo $stat['percentage_correct']; ?>%"
                                                             aria-valuenow="<?php echo $stat['percentage_correct']; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo $stat['percentage_correct']; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $difficulty = $stat['percentage_correct'] >= 70 ? 'Easy' : 
                                                                     ($stat['percentage_correct'] >= 40 ? 'Medium' : 'Hard');
                                                        $badgeClass = $stat['percentage_correct'] >= 70 ? 'bg-success' : 
                                                                     ($stat['percentage_correct'] >= 40 ? 'bg-warning' : 'bg-danger');
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $difficulty; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No question statistics available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Performance Trends -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Student Performance Trends Over Time</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($studentTrends)): ?>
                            <?php foreach ($studentTrends as $studentId => $studentData): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary"><?php echo htmlspecialchars($studentData['username']); ?></h6>
                                    <canvas id="studentTrendChart<?php echo $studentId; ?>" style="max-height: 200px;"></canvas>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No student performance data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Exam Statistics Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Detailed Exam Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($avgScores)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Exam Title</th>
                                            <th>Total Sessions</th>
                                            <th>Average Score</th>
                                            <th>Average Percentage</th>
                                            <th>Total Marks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($avgScores as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td><?php echo $exam['total_sessions']; ?></td>
                                                <td><?php echo number_format($exam['avg_score'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $exam['avg_percentage'] >= 60 ? 'bg-success' : 'bg-danger'; 
                                                    ?>">
                                                        <?php echo number_format($exam['avg_percentage'], 2); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo $exam['total_marks']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No exam statistics available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Average Scores Per Exam Chart
        <?php if (!empty($avgScores)): ?>
        const avgScoresCtx = document.getElementById('avgScoresChart');
        new Chart(avgScoresCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($avgScores, 'title')); ?>,
                datasets: [{
                    label: 'Average Percentage',
                    data: <?php echo json_encode(array_column($avgScores, 'avg_percentage')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Average: ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Pass/Fail Rates Chart
        <?php if (!empty($passFailRates)): ?>
        const passFailCtx = document.getElementById('passFailChart');
        new Chart(passFailCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($passFailRates, 'title')); ?>,
                datasets: [
                    {
                        label: 'Passed',
                        data: <?php echo json_encode(array_column($passFailRates, 'passed')); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Failed',
                        data: <?php echo json_encode(array_column($passFailRates, 'failed')); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const datasetIndex = context.datasetIndex;
                                const index = context.dataIndex;
                                const passed = <?php echo json_encode(array_column($passFailRates, 'passed')); ?>[index];
                                const failed = <?php echo json_encode(array_column($passFailRates, 'failed')); ?>[index];
                                const total = passed + failed;
                                const percentage = total > 0 ? ((datasetIndex === 0 ? passed : failed) / total * 100).toFixed(1) : 0;
                                return percentage + '% of total';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Student Performance Trends Charts
        <?php if (!empty($studentTrends)): ?>
        <?php foreach ($studentTrends as $studentId => $studentData): ?>
        const studentTrendCtx<?php echo $studentId; ?> = document.getElementById('studentTrendChart<?php echo $studentId; ?>');
        new Chart(studentTrendCtx<?php echo $studentId; ?>, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($studentData['exams'], 'exam_title')); ?>,
                datasets: [{
                    label: 'Percentage Score',
                    data: <?php echo json_encode(array_column($studentData['exams'], 'percentage')); ?>,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Score: ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endforeach; ?>
        <?php endif; ?>
    </script>
</body>
</html>
