/**
 * Exam Taking Interface
 * 
 * Handles auto-saving answers, manual exam submission, and page refresh state restoration
 */

// Flag to track if this is a page reload
let isPageReload = false;

// Detect page reload using performance API
if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
    isPageReload = true;
} else if (performance.getEntriesByType && performance.getEntriesByType('navigation').length > 0) {
    const navEntry = performance.getEntriesByType('navigation')[0];
    if (navEntry.type === 'reload') {
        isPageReload = true;
    }
}

// Anti-cheating: Tab switch detection
let tabSwitchCount = 0;
let hasWarned = false;

function logTabSwitch(sessionId) {
    tabSwitchCount++;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="csrf_token"]')?.value || '';
    
    // Log to server
    fetch('/student/exam/log-tab-switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Tab switch logged:', data.count);
            
            // Show warning after 3 switches
            if (data.count >= 3 && !hasWarned) {
                hasWarned = true;
                alert('Warning: Multiple tab switches detected. Your session has been flagged for review.');
            }
        }
    })
    .catch(error => {
        console.error('Error logging tab switch:', error);
    });
}

function initTabSwitchDetection(sessionId) {
    // Use Page Visibility API to detect tab switches
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // User switched away from the tab
            logTabSwitch(sessionId);
        }
    });
    
    // Also detect window blur (when user clicks outside browser)
    window.addEventListener('blur', function() {
        // Only log if the page is still visible (to avoid double-logging)
        if (!document.hidden) {
            logTabSwitch(sessionId);
        }
    });
}

// Loading spinner functions
function showLoadingSpinner(message = 'Loading...') {
    let spinner = document.getElementById('loadingSpinner');
    
    if (!spinner) {
        // Create spinner if it doesn't exist
        spinner = document.createElement('div');
        spinner.id = 'loadingSpinner';
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-text mt-3">${message}</div>
            </div>
        `;
        document.body.appendChild(spinner);
    }
    
    spinner.classList.add('active');
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('active');
    }
}

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Auto-save indicator
function showAutoSaveIndicator(status) {
    const indicator = document.getElementById('autoSaveIndicator');
    
    if (!indicator) return;
    
    indicator.classList.remove('saving', 'error', 'show');
    
    if (status === 'saving') {
        indicator.textContent = 'Saving...';
        indicator.classList.add('saving', 'show');
    } else if (status === 'saved') {
        indicator.textContent = '✓ Saved';
        indicator.classList.add('show');
    } else if (status === 'error') {
        indicator.textContent = '✗ Error saving';
        indicator.classList.add('error', 'show');
    }
    
    // Hide after 2 seconds
    setTimeout(() => {
        indicator.classList.remove('show');
    }, 2000);
}

// Save answer to server
function saveAnswer(sessionId, questionId, answer, questionType) {
    showAutoSaveIndicator('saving');
    
    const data = {
        session_id: sessionId,
        question_id: questionId,
        answer: answer
    };
    
    // Get CSRF token from meta tag or hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="csrf_token"]')?.value || '';
    
    fetch('/student/exam/save-answer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAutoSaveIndicator('saved');
        } else if (data.time_expired) {
            // Time expired - redirect to results
            showAutoSaveIndicator('error');
            alert(data.message);
            window.location.href = data.redirect;
        } else {
            showAutoSaveIndicator('error');
            console.error('Error saving answer:', data.message);
        }
    })
    .catch(error => {
        showAutoSaveIndicator('error');
        console.error('Error saving answer:', error);
    });
}

// Debounced save function (wait 1 second after user stops typing)
const debouncedSave = debounce(saveAnswer, 1000);

// Get session ID from form
function getSessionId() {
    const form = document.getElementById('examForm');
    return parseInt(form.querySelector('input[name="session_id"]').value);
}

// Handle answer input changes
function handleAnswerChange(event) {
    const input = event.target;
    const questionId = parseInt(input.dataset.questionId);
    const questionType = input.dataset.questionType;
    const sessionId = getSessionId();
    
    let answer = null;
    
    if (questionType === 'multiple_choice' || questionType === 'true_false') {
        // Radio button - single selection
        if (input.checked) {
            answer = [parseInt(input.value)];
        }
    } else if (questionType === 'select_all') {
        // Checkboxes - multiple selections
        const checkboxes = document.querySelectorAll(
            `input[data-question-id="${questionId}"][data-question-type="select_all"]:checked`
        );
        answer = Array.from(checkboxes).map(cb => parseInt(cb.value));
    } else if (questionType === 'fill_blank' || questionType === 'short_answer') {
        // Text input or textarea
        answer = input.value;
    }
    
    // Save answer (debounced for text inputs, immediate for radio/checkbox)
    if (questionType === 'fill_blank' || questionType === 'short_answer') {
        debouncedSave(sessionId, questionId, answer, questionType);
    } else {
        saveAnswer(sessionId, questionId, answer, questionType);
    }
}

// Handle form submission
function handleFormSubmit(event) {
    event.preventDefault();
    
    // Confirm submission
    if (!confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
        return;
    }
    
    // Show loading spinner
    showLoadingSpinner('Submitting your exam...');
    
    // Stop timer if running
    if (window.examTimer) {
        window.examTimer.stop();
    }
    
    // Disable submit button
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
    
    const form = event.target;
    const sessionId = getSessionId();
    
    const data = {
        session_id: sessionId
    };
    
    // Get CSRF token from meta tag or hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="csrf_token"]')?.value || '';
    
    // Submit via AJAX
    fetch('/student/exam/submit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to results page
            window.location.href = data.redirect;
        } else {
            hideLoadingSpinner();
            alert('Error submitting exam: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Exam';
        }
    })
    .catch(error => {
        console.error('Error submitting exam:', error);
        hideLoadingSpinner();
        // Fallback to regular form submission
        form.submit();
    });
}

// Prevent accidental page navigation
function preventUnload(event) {
    event.preventDefault();
    event.returnValue = '';
    return '';
}

// Restore exam state after page reload
function restoreExamState(sessionId) {
    showLoadingSpinner('Restoring your exam...');
    
    fetch('/student/exam/state?session_id=' + sessionId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        
        if (!data.success) {
            // Handle errors
            if (data.time_expired || data.redirect) {
                alert(data.message || 'Exam session has ended.');
                window.location.href = data.redirect;
            } else {
                alert('Error restoring exam state: ' + (data.message || 'Unknown error'));
            }
            return;
        }
        
        // Restore answers
        if (data.answers) {
            restoreAnswers(data.answers);
        }
        
        // Update timer with server-calculated remaining time
        if (window.examTimer && data.remaining_time !== undefined) {
            window.examTimer.remainingSeconds = data.remaining_time;
            window.examTimer.updateDisplay();
        }
        
        console.log('Exam state restored successfully');
    })
    .catch(error => {
        hideLoadingSpinner();
        console.error('Error restoring exam state:', error);
        alert('Error restoring exam state. Please refresh the page or contact support.');
    });
}

// Restore answers to form inputs
function restoreAnswers(answersMap) {
    // Iterate through all questions and restore their answers
    Object.keys(answersMap).forEach(questionId => {
        const answer = answersMap[questionId];
        
        // Find inputs for this question
        const inputs = document.querySelectorAll(`[data-question-id="${questionId}"]`);
        
        if (inputs.length === 0) return;
        
        const firstInput = inputs[0];
        const questionType = firstInput.dataset.questionType;
        
        if (questionType === 'multiple_choice' || questionType === 'true_false') {
            // Radio buttons - restore single selection
            if (answer.selected_options && answer.selected_options.length > 0) {
                const selectedOptionId = answer.selected_options[0];
                inputs.forEach(input => {
                    if (input.type === 'radio' && parseInt(input.value) === selectedOptionId) {
                        input.checked = true;
                    }
                });
            }
        } else if (questionType === 'select_all') {
            // Checkboxes - restore multiple selections
            if (answer.selected_options && answer.selected_options.length > 0) {
                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        const optionId = parseInt(input.value);
                        input.checked = answer.selected_options.includes(optionId);
                    }
                });
            }
        } else if (questionType === 'fill_blank' || questionType === 'short_answer') {
            // Text input or textarea - restore text
            if (answer.answer_text) {
                inputs.forEach(input => {
                    if (input.type === 'text' || input.tagName === 'TEXTAREA') {
                        input.value = answer.answer_text;
                    }
                });
            }
        }
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = getSessionId();
    
    // Initialize tab switch detection
    if (sessionId) {
        initTabSwitchDetection(sessionId);
    }
    
    // Check if this is a page reload and restore state
    if (isPageReload) {
        if (sessionId) {
            restoreExamState(sessionId);
        }
    }
    
    // Add event listeners to all answer inputs
    const answerInputs = document.querySelectorAll('.answer-input');
    
    answerInputs.forEach(input => {
        if (input.type === 'radio' || input.type === 'checkbox') {
            input.addEventListener('change', handleAnswerChange);
        } else {
            // Text inputs and textareas
            input.addEventListener('input', handleAnswerChange);
        }
    });
    
    // Add form submit handler
    const examForm = document.getElementById('examForm');
    if (examForm) {
        examForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Warn user before leaving page
    window.addEventListener('beforeunload', preventUnload);
    
    // Remove warning when exam is submitted
    if (examForm) {
        examForm.addEventListener('submit', function() {
            window.removeEventListener('beforeunload', preventUnload);
        });
    }
});
