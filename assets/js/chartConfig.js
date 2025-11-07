/**
 * --------------------------------------------------------------------------------
 * Chart.js Global Configuration
 * --------------------------------------------------------------------------------
 * This file sets up default styling and behavior for all Chart.js instances
 * in the application. It ensures a consistent, modern, and responsive look
 * for all charts.
 */

// Define a color palette for consistent chart colors
const GMS_COLORS = {
    primary: 'rgba(102, 126, 234, 1)',
    primary_light: 'rgba(102, 126, 234, 0.2)',
    secondary: 'rgba(240, 147, 251, 1)',
    secondary_light: 'rgba(240, 147, 251, 0.2)',
    success: 'rgba(67, 233, 123, 1)',
    success_light: 'rgba(67, 233, 123, 0.2)',
    danger: 'rgba(245, 87, 108, 1)',
    danger_light: 'rgba(245, 87, 108, 0.2)',
    warning: 'rgba(255, 193, 7, 1)',
    info: 'rgba(79, 172, 254, 1)',
    dark: 'rgba(52, 58, 64, 1)',
    grid: 'rgba(233, 236, 239, 0.5)',
    text: '#6c757d',
    text_dark: '#343a40'
};

// Set global Chart.js defaults
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 13;
Chart.defaults.color = GMS_COLORS.text;
Chart.defaults.maintainAspectRatio = false;

// Global plugin for a clean background and border
const globalChartPlugins = {
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20
            }
        },
        tooltip: {
            backgroundColor: GMS_COLORS.dark,
            titleFont: {
                size: 14,
                weight: '600'
            },
            bodyFont: {
                size: 13
            },
            padding: 12,
            cornerRadius: 6,
            boxPadding: 5
        }
    }
};

// Common options for Line charts
const lineChartOptions = {
    ...globalChartPlugins,
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: GMS_COLORS.text
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: GMS_COLORS.grid,
                borderDash: [2, 4]
            },
            ticks: {
                color: GMS_COLORS.text,
                callback: function(value) {
                    if (value >= 1000) {
                        return (value / 1000) + 'k';
                    }
                    return value;
                }
            }
        }
    }
};

// Common options for Bar charts
const barChartOptions = {
    ...globalChartPlugins,
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: GMS_COLORS.text
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: GMS_COLORS.grid,
                borderDash: [2, 4]
            },
            ticks: {
                color: GMS_COLORS.text
            }
        }
    }
};

// Common options for Doughnut/Pie charts
const doughnutChartOptions = {
    ...globalChartPlugins,
    cutout: '70%',
    plugins: {
        ...globalChartPlugins.plugins,
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 15
            }
        }
    }
};

// Helper to format month labels (e.g., "Jan '23")
function formatChartMonth(monthString) {
    const date = new Date(monthString + '-02'); // Use day 2 to avoid timezone issues
    return date.toLocaleDateString('en-US', {
        month: 'short',
        year: '2-digit'
    });
}