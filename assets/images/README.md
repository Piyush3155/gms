# Images Directory

This directory contains images used throughout the Gym Management System.

## Background Images

### Login Page Background
- **File**: `gym-bg.svg` (default)
- **Usage**: Used as background for the modern login page
- **Customization**: Replace with your own gym/fitness themed image

### How to Add Custom Background Image

1. Add your image file to this directory (recommended formats: JPG, PNG, SVG)
2. Update the CSS in `login.php`:
   ```css
   .login-image {
       background: url('assets/images/your-image.jpg') center/cover no-repeat;
       /* ... rest of styles ... */
   }
   ```

### Recommended Image Specifications
- **Size**: At least 800x600 pixels
- **Format**: JPG, PNG, or SVG
- **Theme**: Gym equipment, fitness, workout scenes
- **Style**: High contrast for text overlay readability

## Other Images

- Member photos: Store in `assets/images/members/`
- Gym logo: `assets/images/logo.png`
- Trainer photos: `assets/images/trainers/`

## File Naming Convention

- Use lowercase with hyphens: `gym-background.jpg`
- Include descriptive names: `treadmill-equipment.png`