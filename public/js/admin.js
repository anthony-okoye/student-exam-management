/**
 * Admin Interface JavaScript
 * 
 * Handles dynamic form behavior for question management
 */

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

document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const multipleChoiceOptions = document.getElementById('multipleChoiceOptions');
    const selectAllOptions = document.getElementById('selectAllOptions');
    const trueFalseOptions = document.getElementById('trueFalseOptions');
    const fillBlankOptions = document.getElementById('fillBlankOptions');
    const shortAnswerOptions = document.getElementById('shortAnswerOptions');
    const addOptionBtn = document.getElementById('addOption');
    const removeOptionBtn = document.getElementById('removeOption');
    const optionsContainer = document.getElementById('optionsContainer');
    const addSelectAllOptionBtn = document.getElementById('addSelectAllOption');
    const removeSelectAllOptionBtn = document.getElementById('removeSelectAllOption');
    const selectAllOptionsContainer = document.getElementById('selectAllOptionsContainer');
    
    // Show/hide option fields based on question type
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            
            // Hide all option sections
            multipleChoiceOptions.style.display = 'none';
            selectAllOptions.style.display = 'none';
            trueFalseOptions.style.display = 'none';
            fillBlankOptions.style.display = 'none';
            shortAnswerOptions.style.display = 'none';
            
            // Show relevant section based on type
            if (selectedType === 'multiple_choice') {
                multipleChoiceOptions.style.display = 'block';
            } else if (selectedType === 'select_all') {
                selectAllOptions.style.display = 'block';
            } else if (selectedType === 'true_false') {
                trueFalseOptions.style.display = 'block';
            } else if (selectedType === 'fill_blank') {
                fillBlankOptions.style.display = 'block';
            } else if (selectedType === 'short_answer') {
                shortAnswerOptions.style.display = 'block';
            }
        });
    }
    
    // Add option for multiple choice
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function() {
            const currentOptions = optionsContainer.querySelectorAll('.input-group');
            const optionCount = currentOptions.length;
            
            // Limit to 4 options
            if (optionCount >= 4) {
                alert('Maximum 4 options allowed');
                return;
            }
            
            const newOption = document.createElement('div');
            newOption.className = 'input-group mb-2';
            newOption.innerHTML = `
                <div class="input-group-text">
                    <input type="radio" name="correct_option" value="${optionCount}" class="form-check-input mt-0">
                </div>
                <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCount + 1}">
            `;
            
            optionsContainer.appendChild(newOption);
        });
    }
    
    // Remove option for multiple choice
    if (removeOptionBtn) {
        removeOptionBtn.addEventListener('click', function() {
            const currentOptions = optionsContainer.querySelectorAll('.input-group');
            const optionCount = currentOptions.length;
            
            // Keep at least 2 options
            if (optionCount <= 2) {
                alert('Minimum 2 options required');
                return;
            }
            
            // Remove last option
            currentOptions[optionCount - 1].remove();
        });
    }
    
    // Add option for select all that apply
    if (addSelectAllOptionBtn) {
        addSelectAllOptionBtn.addEventListener('click', function() {
            const currentOptions = selectAllOptionsContainer.querySelectorAll('.input-group');
            const optionCount = currentOptions.length;
            
            // Limit to 4 options
            if (optionCount >= 4) {
                alert('Maximum 4 options allowed');
                return;
            }
            
            const newOption = document.createElement('div');
            newOption.className = 'input-group mb-2';
            newOption.innerHTML = `
                <div class="input-group-text">
                    <input type="checkbox" name="correct_options[]" value="${optionCount}" class="form-check-input mt-0">
                </div>
                <input type="text" class="form-control" name="select_all_options[]" placeholder="Option ${optionCount + 1}">
            `;
            
            selectAllOptionsContainer.appendChild(newOption);
        });
    }
    
    // Remove option for select all that apply
    if (removeSelectAllOptionBtn) {
        removeSelectAllOptionBtn.addEventListener('click', function() {
            const currentOptions = selectAllOptionsContainer.querySelectorAll('.input-group');
            const optionCount = currentOptions.length;
            
            // Keep at least 2 options
            if (optionCount <= 2) {
                alert('Minimum 2 options required');
                return;
            }
            
            // Remove last option
            currentOptions[optionCount - 1].remove();
        });
    }
    
    // Form validation
    const questionForm = document.getElementById('questionForm');
    if (questionForm) {
        questionForm.addEventListener('submit', function(e) {
            const type = typeSelect.value;
            
            if (type === 'multiple_choice') {
                // Check if at least one option is marked as correct
                const correctOption = document.querySelector('input[name="correct_option"]:checked');
                if (!correctOption) {
                    e.preventDefault();
                    alert('Please select the correct answer');
                    return false;
                }
                
                // Check if all options have text
                const optionInputs = document.querySelectorAll('#optionsContainer input[type="text"]');
                let hasEmptyOption = false;
                optionInputs.forEach(function(input) {
                    if (input.value.trim() === '') {
                        hasEmptyOption = true;
                    }
                });
                
                if (hasEmptyOption) {
                    e.preventDefault();
                    alert('Please fill in all option fields');
                    return false;
                }
            } else if (type === 'select_all') {
                // Check if at least one option is marked as correct
                const correctOptions = document.querySelectorAll('input[name="correct_options[]"]:checked');
                if (correctOptions.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one correct answer');
                    return false;
                }
                
                // Check if all options have text
                const optionInputs = document.querySelectorAll('#selectAllOptionsContainer input[type="text"]');
                let hasEmptyOption = false;
                optionInputs.forEach(function(input) {
                    if (input.value.trim() === '') {
                        hasEmptyOption = true;
                    }
                });
                
                if (hasEmptyOption) {
                    e.preventDefault();
                    alert('Please fill in all option fields');
                    return false;
                }
            } else if (type === 'fill_blank') {
                // Check if correct answer is provided
                const correctAnswer = document.getElementById('correct_answer').value;
                if (correctAnswer.trim() === '') {
                    e.preventDefault();
                    alert('Please provide the correct answer');
                    return false;
                }
            } else if (type === 'short_answer') {
                // Check if expected answer is provided
                const expectedAnswer = document.getElementById('expected_answer').value;
                if (expectedAnswer.trim() === '') {
                    e.preventDefault();
                    alert('Please provide the expected answer');
                    return false;
                }
            }
            
            // Show loading spinner on form submit
            showLoadingSpinner('Saving...');
        });
    }
    
    // Add loading spinner to delete forms
    const deleteForms = document.querySelectorAll('form[action*="/delete"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoadingSpinner('Deleting...');
        });
    });
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
