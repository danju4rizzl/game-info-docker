# CSS Refactoring Documentation

## Overview

This document outlines the CSS refactoring performed on the PandaScore Tracker plugin to eliminate inline styles and implement a clean, maintainable CSS class-based approach.

## Changes Made

### 1. Eliminated Inline CSS

**Before:** All styling was done using inline `style=""` attributes scattered throughout the HTML generation code.

**After:** Created a centralized `get_internal_styles()` method that outputs all CSS as internal styles within `<style>` tags.

### 2. Created Reusable CSS Classes

#### Main Container Classes

- `.pandascore-tracker` - Main plugin container with font family
- `.pandascore-tracker.align-left` - Left text alignment
- `.pandascore-tracker.align-center` - Center text alignment
- `.pandascore-tracker.align-right` - Right text alignment

#### Section Header Classes

- `.pandascore-section-header` - Styling for "LIVE" and "UPCOMING" headers
- `.pandascore-live-indicator` - Yellow dot indicator for live matches

#### Match Container Classes

- `.pandascore-matches-container` - Container for multiple matches
- `.pandascore-match` - Individual match card styling
- `.pandascore-match-content` - Main content area within match
- `.pandascore-match-content.live-layout` - Specific layout for live matches

#### League Logo Classes

- `.pandascore-league-container` - Container for league logo/placeholder
- `.pandascore-league-logo` - Styling for actual league logos
- `.pandascore-league-placeholder` - Fallback styling when no logo available

#### Team and Score Classes

- `.pandascore-teams-container` - Container for team information
- `.pandascore-team` - Individual team row
- `.pandascore-team.with-score` - Team row that includes score display
- `.pandascore-team-info` - Container for team logo and name
- `.pandascore-team-logo` - Team logo image styling
- `.pandascore-team-logo-placeholder` - Fallback placeholder when no team logo available
- `.pandascore-team-name` - Team name text styling
- `.pandascore-score` - Score display badge

#### Time Display Classes

- `.pandascore-time-container` - Container for match time
- `.pandascore-time-badge` - Time display badge
- `.pandascore-time-day` - Day indicator (Today, Tomorrow, etc.)

#### Error and Status Classes

- `.pandascore-error` - Error message styling
- `.pandascore-no-matches` - No matches found message styling

#### Admin Interface Classes

- `.pandascore-api-key-input` - API key input field styling

### 3. Benefits of Refactoring

#### Maintainability

- **Single Source of Truth:** All styles are now in one location
- **Easy Updates:** Changing colors, fonts, or layouts requires editing only the CSS
- **Consistent Styling:** Eliminates duplicate style definitions

#### Performance

- **Reduced HTML Size:** Removed repetitive inline styles
- **Better Caching:** CSS can be cached by browsers
- **Cleaner DOM:** HTML is more semantic and readable

#### Developer Experience

- **Better Readability:** HTML generation code is much cleaner
- **Easier Debugging:** CSS can be inspected and modified easily
- **Reusable Components:** Classes can be reused across different contexts

### 4. Team Logo Fallback System

#### Fallback Implementation

When team logos are not available from the PandaScore API, the plugin now displays attractive fallback placeholders:

- **Automatic Detection:** Checks if `opponent.image_url` is available
- **Smart Fallback:** Uses first letter of team acronym or name
- **Consistent Styling:** Yellow background matching the plugin's color scheme
- **Accessibility:** Includes proper `title` attributes for screen readers

#### Fallback Logic

```php
// Priority order for fallback letter:
1. First letter of team acronym (if available and not "N/A")
2. First letter of team name (if available and not "N/A")
3. Question mark "?" as ultimate fallback
```

#### Visual Design

- **Size:** 24x24px to match team logo dimensions
- **Background:** `#FFC700` (plugin's primary yellow)
- **Text Color:** `#1D1C26` (dark contrast)
- **Border Radius:** 4px for modern appearance
- **Typography:** Bold, 10px font size

### 5. Implementation Details

#### CSS Organization

The CSS is organized into logical groups:

1. Main container and alignment classes
2. Section headers and indicators
3. Match containers and layouts
4. League logo components
5. Team and score components (including logo fallbacks)
6. Time display components
7. Error and status messages
8. Admin interface components

#### Color Scheme

- **Primary Yellow:** `#FFC700` - Used for highlights, scores, and indicators
- **Dark Background:** `#1D1C26` - Main match card background
- **White Text:** `#fff` - Primary text color
- **Error Red:** `#f8d7da` background, `#842029` text
- **Info Gray:** `#eee` background, `#333` text

#### Typography

- **Font Family:** Inter, with fallbacks to system fonts
- **Font Sizes:** 14px for team names, 10px for time indicators
- **Font Weights:** Bold for scores and time, normal for team names

### 6. Usage Examples

#### Basic Implementation

```php
// Old way (inline styles)
$html = '<div style="background:#1D1C26;color:#fff;padding:10px;">Content</div>';

// New way (CSS classes)
$html = '<div class="pandascore-match">Content</div>';
```

#### Dynamic Alignment

```php
// Old way
$align_style = "text-align:{$atts['align']};";
$html = '<div class="pandascore-tracker" style="'.$align_style.'">';

// New way
$align_class = 'align-' . esc_attr( $atts['align'] );
$html = '<div class="pandascore-tracker ' . $align_class . '">';
```

### 7. Future Enhancements

#### Potential Improvements

1. **External CSS File:** Move styles to separate CSS file for better caching
2. **CSS Variables:** Use CSS custom properties for easier theme customization
3. **Responsive Design:** Add media queries for mobile optimization
4. **Dark/Light Themes:** Implement theme switching capabilities
5. **Animation Classes:** Add CSS transitions for better user experience

#### Customization Options

Developers can now easily customize the plugin appearance by:

1. Overriding CSS classes in their theme
2. Adding custom CSS to modify specific components
3. Using CSS specificity to change colors or layouts
4. Implementing their own theme variations

## Conclusion

This refactoring significantly improves the maintainability, performance, and developer experience of the PandaScore Tracker plugin while maintaining all existing functionality. The new CSS class-based approach provides a solid foundation for future enhancements and customizations.
