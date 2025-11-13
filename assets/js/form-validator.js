/**
 * Form Validation Library for Gym Management System
 * Provides comprehensive client-side validation for all forms
 */

class FormValidator {
    constructor(formId, rules, options = {}) {
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.error(`Form with ID "${formId}" not found`);
            return;
        }
        
        this.rules = rules;
        this.options = {
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorElement: 'div',
            errorElementClass: 'invalid-feedback',
            validateOnBlur: true,
            validateOnInput: true,
            submitCallback: null,
            ...options
        };
        
        this.errors = {};
        this.init();
    }
    
    init() {
        // Prevent default form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.validateAll()) {
                if (this.options.submitCallback) {
                    this.options.submitCallback(this.getFormData());
                } else {
                    this.form.submit();
                }
            }
        });
        
        // Add blur and input event listeners
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (this.options.validateOnBlur) {
                    field.addEventListener('blur', () => this.validateField(fieldName));
                }
                if (this.options.validateOnInput) {
                    field.addEventListener('input', () => {
                        if (this.errors[fieldName]) {
                            this.validateField(fieldName);
                        }
                    });
                }
            }
        });
    }
    
    validateField(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return true;
        
        const value = field.value.trim();
        const rules = this.rules[fieldName];
        
        // Clear previous errors
        delete this.errors[fieldName];
        this.clearFieldError(field);
        
        // Apply each rule
        for (const rule of rules) {
            const result = this.applyRule(value, rule, field);
            if (!result.valid) {
                this.errors[fieldName] = result.message;
                this.showFieldError(field, result.message);
                return false;
            }
        }
        
        this.showFieldSuccess(field);
        return true;
    }
    
    applyRule(value, rule, field) {
        // Required rule
        if (rule === 'required') {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return {
                    valid: field.checked,
                    message: 'This field is required'
                };
            }
            return {
                valid: value !== '',
                message: 'This field is required'
            };
        }
        
        // Email rule
        if (rule === 'email') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return {
                valid: value === '' || emailPattern.test(value),
                message: 'Please enter a valid email address'
            };
        }
        
        // Phone rule
        if (rule === 'phone') {
            const phonePattern = /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/;
            return {
                valid: value === '' || phonePattern.test(value),
                message: 'Please enter a valid phone number'
            };
        }
        
        // Numeric rule
        if (rule === 'numeric') {
            return {
                valid: value === '' || !isNaN(value),
                message: 'This field must be a number'
            };
        }
        
        // Integer rule
        if (rule === 'integer') {
            return {
                valid: value === '' || /^\d+$/.test(value),
                message: 'This field must be an integer'
            };
        }
        
        // Decimal rule
        if (rule === 'decimal') {
            return {
                valid: value === '' || /^\d+(\.\d+)?$/.test(value),
                message: 'This field must be a decimal number'
            };
        }
        
        // URL rule
        if (rule === 'url') {
            try {
                new URL(value);
                return { valid: true };
            } catch {
                return {
                    valid: value === '',
                    message: 'Please enter a valid URL'
                };
            }
        }
        
        // Date rule
        if (rule === 'date') {
            const date = new Date(value);
            return {
                valid: value === '' || !isNaN(date.getTime()),
                message: 'Please enter a valid date'
            };
        }
        
        // Min length rule
        if (typeof rule === 'object' && rule.minLength !== undefined) {
            return {
                valid: value.length >= rule.minLength,
                message: `This field must be at least ${rule.minLength} characters`
            };
        }
        
        // Max length rule
        if (typeof rule === 'object' && rule.maxLength !== undefined) {
            return {
                valid: value.length <= rule.maxLength,
                message: `This field must not exceed ${rule.maxLength} characters`
            };
        }
        
        // Min value rule
        if (typeof rule === 'object' && rule.min !== undefined) {
            const numValue = parseFloat(value);
            return {
                valid: value === '' || numValue >= rule.min,
                message: `This field must be at least ${rule.min}`
            };
        }
        
        // Max value rule
        if (typeof rule === 'object' && rule.max !== undefined) {
            const numValue = parseFloat(value);
            return {
                valid: value === '' || numValue <= rule.max,
                message: `This field must not exceed ${rule.max}`
            };
        }
        
        // Pattern rule (regex)
        if (typeof rule === 'object' && rule.pattern !== undefined) {
            const pattern = new RegExp(rule.pattern);
            return {
                valid: value === '' || pattern.test(value),
                message: rule.message || 'This field format is invalid'
            };
        }
        
        // Match rule (confirm password, etc.)
        if (typeof rule === 'object' && rule.matches !== undefined) {
            const matchField = this.form.querySelector(`[name="${rule.matches}"]`);
            return {
                valid: matchField && value === matchField.value,
                message: rule.message || `This field must match ${rule.matches}`
            };
        }
        
        // Custom function rule
        if (typeof rule === 'function') {
            const result = rule(value, field);
            if (typeof result === 'boolean') {
                return { valid: result, message: 'This field is invalid' };
            }
            return result;
        }
        
        return { valid: true };
    }
    
    validateAll() {
        this.errors = {};
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            if (!this.validateField(fieldName)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            // Focus on first error field
            const firstErrorField = Object.keys(this.errors)[0];
            const field = this.form.querySelector(`[name="${firstErrorField}"]`);
            if (field) {
                field.focus();
                // Scroll to field if not in viewport
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }
    
    showFieldError(field, message) {
        field.classList.remove(this.options.successClass);
        field.classList.add(this.options.errorClass);
        
        // Remove existing error message
        this.clearFieldError(field);
        
        // Add error message
        const errorElement = document.createElement(this.options.errorElement);
        errorElement.className = this.options.errorElementClass;
        errorElement.textContent = message;
        errorElement.setAttribute('data-error-for', field.name);
        
        field.parentNode.appendChild(errorElement);
    }
    
    showFieldSuccess(field) {
        field.classList.remove(this.options.errorClass);
        field.classList.add(this.options.successClass);
    }
    
    clearFieldError(field) {
        field.classList.remove(this.options.errorClass, this.options.successClass);
        const existingError = field.parentNode.querySelector(`[data-error-for="${field.name}"]`);
        if (existingError) {
            existingError.remove();
        }
    }
    
    clearAllErrors() {
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.clearFieldError(field);
            }
        });
        this.errors = {};
    }
    
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }
    
    setFieldValue(fieldName, value) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.value = value;
        }
    }
    
    getFieldValue(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        return field ? field.value : null;
    }
}

// Password strength validator
class PasswordStrengthValidator {
    static validate(password) {
        const strength = {
            score: 0,
            feedback: [],
            level: 'weak'
        };
        
        // Length check
        if (password.length >= 8) {
            strength.score += 20;
        } else {
            strength.feedback.push('Use at least 8 characters');
        }
        
        if (password.length >= 12) {
            strength.score += 10;
        }
        
        // Uppercase letters
        if (/[A-Z]/.test(password)) {
            strength.score += 20;
        } else {
            strength.feedback.push('Add uppercase letters');
        }
        
        // Lowercase letters
        if (/[a-z]/.test(password)) {
            strength.score += 20;
        } else {
            strength.feedback.push('Add lowercase letters');
        }
        
        // Numbers
        if (/[0-9]/.test(password)) {
            strength.score += 20;
        } else {
            strength.feedback.push('Add numbers');
        }
        
        // Special characters
        if (/[@$!%*?&#]/.test(password)) {
            strength.score += 20;
        } else {
            strength.feedback.push('Add special characters (@$!%*?&#)');
        }
        
        // Determine level
        if (strength.score < 40) {
            strength.level = 'weak';
        } else if (strength.score < 70) {
            strength.level = 'medium';
        } else {
            strength.level = 'strong';
        }
        
        return strength;
    }
    
    static attachToField(fieldId, feedbackElementId) {
        const field = document.getElementById(fieldId);
        const feedbackElement = document.getElementById(feedbackElementId);
        
        if (!field || !feedbackElement) return;
        
        field.addEventListener('input', function() {
            const strength = PasswordStrengthValidator.validate(this.value);
            
            let html = `<div class="password-strength-${strength.level}">`;
            html += `<div class="strength-bar"><div class="strength-fill" style="width: ${strength.score}%"></div></div>`;
            html += `<small class="strength-text">Password strength: <strong>${strength.level.toUpperCase()}</strong></small>`;
            
            if (strength.feedback.length > 0) {
                html += '<ul class="strength-feedback">';
                strength.feedback.forEach(item => {
                    html += `<li>${item}</li>`;
                });
                html += '</ul>';
            }
            
            html += '</div>';
            feedbackElement.innerHTML = html;
        });
    }
}

// File upload validator
class FileValidator {
    static validate(file, options = {}) {
        const defaults = {
            maxSize: 5 * 1024 * 1024, // 5MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
            allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf']
        };
        
        const settings = { ...defaults, ...options };
        const errors = [];
        
        // Check file size
        if (file.size > settings.maxSize) {
            errors.push(`File size must not exceed ${(settings.maxSize / 1024 / 1024).toFixed(2)}MB`);
        }
        
        // Check file type
        if (settings.allowedTypes.length > 0 && !settings.allowedTypes.includes(file.type)) {
            errors.push(`File type must be one of: ${settings.allowedExtensions.join(', ')}`);
        }
        
        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (settings.allowedExtensions.length > 0 && !settings.allowedExtensions.includes(extension)) {
            errors.push(`File extension must be one of: ${settings.allowedExtensions.join(', ')}`);
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    static attachToField(fieldId, options = {}) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const result = FileValidator.validate(file, options);
            
            // Clear previous validation
            const existingError = this.parentNode.querySelector('.file-validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            if (!result.valid) {
                // Show errors
                const errorDiv = document.createElement('div');
                errorDiv.className = 'file-validation-error alert alert-danger mt-2';
                errorDiv.innerHTML = '<ul class="mb-0">' + 
                    result.errors.map(err => `<li>${err}</li>`).join('') + 
                    '</ul>';
                this.parentNode.appendChild(errorDiv);
                this.value = ''; // Clear the file input
            }
        });
    }
}

// Export validators
window.FormValidator = FormValidator;
window.PasswordStrengthValidator = PasswordStrengthValidator;
window.FileValidator = FileValidator;
