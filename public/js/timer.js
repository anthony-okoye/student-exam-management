/**
 * Exam Timer
 * 
 * Handles countdown timer for exam sessions with auto-submit functionality
 */

class ExamTimer {
    constructor(sessionId, remainingSeconds) {
        this.sessionId = sessionId;
        this.remainingSeconds = remainingSeconds;
        this.intervalId = null;
        this.syncIntervalId = null;
        this.timerElement = document.getElementById('timer');
        this.isRunning = false;
    }
    
    /**
     * Start the timer
     */
    start() {
        if (this.isRunning) {
            return;
        }
        
        this.isRunning = true;
        
        // Update display immediately
        this.updateDisplay();
        
        // Update every second
        this.intervalId = setInterval(() => {
            this.remainingSeconds--;
            this.updateDisplay();
            
            // Check if time expired
            if (this.remainingSeconds <= 0) {
                this.stop();
                this.autoSubmit();
            }
        }, 1000);
        
        // Sync with server every 30 seconds
        this.syncIntervalId = setInterval(() => {
            this.syncWithServer();
        }, 30000);
    }
    
    /**
     * Stop the timer
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        if (this.syncIntervalId) {
            clearInterval(this.syncIntervalId);
            this.syncIntervalId = null;
        }
        
        this.isRunning = false;
    }
    
    /**
     * Update timer display
     */
    updateDisplay() {
        const minutes = Math.floor(this.remainingSeconds / 60);
        const seconds = this.remainingSeconds % 60;
        
        // Format as MM:SS
        const display = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        this.timerElement.textContent = display;
        
        // Update color based on remaining time
        this.timerElement.classList.remove('warning', 'danger');
        
        if (this.remainingSeconds <= 60) {
            // Less than 1 minute - red
            this.timerElement.classList.add('danger');
        } else if (this.remainingSeconds <= 300) {
            // Less than 5 minutes - yellow
            this.timerElement.classList.add('warning');
        }
    }
    
    /**
     * Sync with server to get accurate remaining time
     */
    syncWithServer() {
        fetch('/student/exam/remaining-time?session_id=' + this.sessionId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check if time expired on server
                if (data.time_expired) {
                    this.stop();
                    alert(data.message);
                    window.location.href = data.redirect;
                    return;
                }
                
                // Update remaining time from server
                this.remainingSeconds = data.remaining_time;
                this.updateDisplay();
                
                // Check if time expired
                if (this.remainingSeconds <= 0) {
                    this.stop();
                    this.autoSubmit();
                }
            }
        })
        .catch(error => {
            console.error('Error syncing timer with server:', error);
        });
    }
    
    /**
     * Auto-submit exam when timer expires
     */
    autoSubmit() {
        // Show alert
        alert('Time is up! Your exam will be submitted automatically.');
        
        // Submit the exam
        const submitData = {
            session_id: this.sessionId
        };
        
        fetch('/student/exam/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(submitData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to results page
                window.location.href = data.redirect;
            } else {
                alert('Error submitting exam: ' + data.message);
                // Try form submission as fallback
                document.getElementById('examForm').submit();
            }
        })
        .catch(error => {
            console.error('Error auto-submitting exam:', error);
            // Try form submission as fallback
            document.getElementById('examForm').submit();
        });
    }
}

// Initialize timer when page loads
document.addEventListener('DOMContentLoaded', function() {
    const timerElement = document.getElementById('timer');
    
    if (timerElement) {
        const sessionId = parseInt(timerElement.dataset.sessionId);
        const remainingSeconds = parseInt(timerElement.dataset.remaining);
        
        if (sessionId && remainingSeconds >= 0) {
            const timer = new ExamTimer(sessionId, remainingSeconds);
            timer.start();
            
            // Store timer instance globally for access from exam.js
            window.examTimer = timer;
        }
    }
});
