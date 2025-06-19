# Greeting Cards Slider Component

A responsive, accessible horizontal slider component for displaying greeting cards with navigation arrows and progress bar.

## Features

- **Responsive Design**: Adapts to different screen sizes with appropriate card counts
- **Full Bleed Layout**: Extends to the right edge of the page while maintaining left alignment
- **Navigation Controls**: Previous/next arrow buttons with hover states
- **Progress Bar**: Visual indicator of current position in the slider
- **Keyboard Support**: Arrow keys, Home, End navigation
- **Touch/Swipe Support**: Mobile-friendly touch gestures
- **Accessibility**: ARIA labels, keyboard navigation, focus management
- **Card Selection**: Click/tap to select cards with visual feedback
- **Dynamic Content**: Add/remove cards programmatically

## Usage

### Basic HTML Structure

```html
<section class="greeting-cards-section" role="region" aria-label="Greeting Cards">
    <div class="greeting-cards-container">
        <div class="greeting-cards-header">
            <h2 class="greeting-cards-title">Birthday</h2>
            <a href="#" class="greeting-cards-see-all">See all</a>
        </div>
        
        <p class="greeting-cards-description">
            Because it wouldn't be a birthday without a card. Pick your fave design, and add your own celebratory note.
        </p>
        
        <div class="greeting-cards-slider-wrapper">
            <!-- Navigation arrows -->
            <button class="slider-nav slider-nav-prev" aria-label="Previous cards" type="button">
                <!-- SVG icon -->
            </button>
            <button class="slider-nav slider-nav-next" aria-label="Next cards" type="button">
                <!-- SVG icon -->
            </button>
            
            <!-- Cards container -->
            <div class="greeting-cards-slider" role="list">
                <div class="greeting-card" data-card-id="1" role="listitem">
                    <img src="card-image.jpg" alt="Card Title" class="greeting-card-image">
                    <div class="greeting-card-content">
                        <h4 class="greeting-card-title">Card Title</h4>
                        <p class="greeting-card-price">£1.50</p>
                    </div>
                </div>
                <!-- More cards... -->
            </div>
        </div>
        
        <!-- Progress bar -->
        <div class="slider-progress-container">
            <div class="slider-progress-bar" role="progressbar" aria-label="Slider progress">
                <div class="slider-progress-fill"></div>
            </div>
        </div>
    </div>
</section>
```

### CSS and JavaScript

Include the CSS and JavaScript files:

```html
<link rel="stylesheet" href="assets/greeting-cards-slider.css">
<script src="assets/greeting-cards-slider.js"></script>
```

### Manual Initialization

```javascript
// Auto-initialization happens on DOMContentLoaded
// For manual initialization:
const sliderElement = document.querySelector('.greeting-cards-section');
const slider = new GreetingCardsSlider(sliderElement);
```

## API Methods

### Navigation
- `prev()` - Move to previous slide
- `next()` - Move to next slide
- `goToSlide(index)` - Go to specific slide

### Content Management
- `addCard(cardData)` - Add a new card
- `removeCard(index)` - Remove card at index

### Card Data Format
```javascript
const cardData = {
    id: 'unique-id',
    title: 'Card Title',
    price: '£1.50', // or 'FREE'
    image: 'path/to/image.jpg'
};
```

## Events

### Card Selection
```javascript
sliderElement.addEventListener('cardSelected', function(e) {
    const cardData = e.detail.cardData;
    const cardElement = e.detail.card;
    const cardIndex = e.detail.index;
    
    console.log('Selected card:', cardData);
});
```

## Responsive Breakpoints

- **Desktop (>768px)**: 200px cards, 20px gap
- **Tablet (≤768px)**: 160px cards, 16px gap  
- **Mobile (≤480px)**: 140px cards, 16px gap

## Accessibility Features

- ARIA labels and roles
- Keyboard navigation (Arrow keys, Home, End)
- Focus management
- Screen reader support
- High contrast support

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills for CSS Grid)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Customization

### CSS Custom Properties
```css
.greeting-cards-section {
    --card-width: 200px;
    --card-gap: 20px;
    --primary-color: #007cba;
    --border-radius: 12px;
}
```

### Styling Cards
```css
.greeting-card.selected {
    border: 2px solid var(--primary-color);
    transform: scale(1.02);
}
```

## Performance Considerations

- Uses CSS transforms for smooth animations
- Debounced resize events
- Efficient DOM manipulation
- Lazy loading compatible (add `loading="lazy"` to images)

## License

MIT License - feel free to use in your projects!