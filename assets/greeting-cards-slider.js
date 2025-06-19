/**
 * Greeting Cards Slider Component - Updated to match design
 * Responsive horizontal slider with navigation arrows
 */

class GreetingCardsSlider {
    constructor(container) {
        this.container = container;
        this.slider = container.querySelector('.greeting-cards-slider');
        this.cards = container.querySelectorAll('.greeting-card');
        this.prevBtn = container.querySelector('.slider-nav-prev');
        this.nextBtn = container.querySelector('.slider-nav-next');
        
        this.currentIndex = 0;
        this.cardWidth = 256; // 240px + 16px gap
        this.visibleCards = this.calculateVisibleCards();
        this.maxIndex = Math.max(0, this.cards.length - this.visibleCards);
        
        this.init();
    }
    
    init() {
        this.updateCardWidth();
        this.updateNavigation();
        this.bindEvents();
        this.updateSliderPosition();
    }
    
    calculateVisibleCards() {
        const containerWidth = this.container.offsetWidth - 40; // Account for padding
        return Math.floor(containerWidth / this.cardWidth);
    }
    
    updateCardWidth() {
        const containerWidth = this.container.offsetWidth;
        
        if (containerWidth <= 480) {
            this.cardWidth = 192; // 180px + 12px gap
        } else if (containerWidth <= 768) {
            this.cardWidth = 212; // 200px + 12px gap
        } else {
            this.cardWidth = 256; // 240px + 16px gap
        }
        
        this.visibleCards = this.calculateVisibleCards();
        this.maxIndex = Math.max(0, this.cards.length - this.visibleCards);
        
        // Ensure current index is within bounds
        this.currentIndex = Math.min(this.currentIndex, this.maxIndex);
    }
    
    bindEvents() {
        // Navigation buttons
        this.prevBtn?.addEventListener('click', () => this.prev());
        this.nextBtn?.addEventListener('click', () => this.next());
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Touch/swipe support
        this.bindTouchEvents();
        
        // Window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Card click events
        this.cards.forEach((card, index) => {
            card.addEventListener('click', () => this.handleCardClick(card, index));
            
            // Make cards focusable
            card.setAttribute('tabindex', '0');
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.handleCardClick(card, index);
                }
            });
        });
    }
    
    bindTouchEvents() {
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        this.slider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });
        
        this.slider.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        }, { passive: true });
        
        this.slider.addEventListener('touchend', () => {
            if (!isDragging) return;
            
            const diff = startX - currentX;
            const threshold = 50;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
            
            isDragging = false;
        }, { passive: true });
    }
    
    handleKeydown(e) {
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                this.prev();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.next();
                break;
            case 'Home':
                e.preventDefault();
                this.goToSlide(0);
                break;
            case 'End':
                e.preventDefault();
                this.goToSlide(this.maxIndex);
                break;
        }
    }
    
    handleResize() {
        // Debounce resize events
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            this.updateCardWidth();
            this.updateSliderPosition();
            this.updateNavigation();
        }, 150);
    }
    
    handleCardClick(card, index) {
        // Emit custom event for card selection
        const event = new CustomEvent('cardSelected', {
            detail: {
                card: card,
                index: index,
                cardData: this.getCardData(card)
            }
        });
        this.container.dispatchEvent(event);
        
        // Add visual feedback
        this.selectCard(card);
    }
    
    getCardData(card) {
        return {
            id: card.dataset.cardId,
            title: card.querySelector('.greeting-card-title')?.textContent,
            price: card.querySelector('.greeting-card-price')?.textContent,
            image: card.querySelector('.greeting-card-image')?.src
        };
    }
    
    selectCard(selectedCard) {
        // Remove previous selection
        this.cards.forEach(card => card.classList.remove('selected'));
        
        // Add selection to clicked card
        selectedCard.classList.add('selected');
        
        // Ensure selected card is visible
        const cardIndex = Array.from(this.cards).indexOf(selectedCard);
        if (cardIndex < this.currentIndex || cardIndex >= this.currentIndex + this.visibleCards) {
            const targetIndex = Math.max(0, Math.min(cardIndex - Math.floor(this.visibleCards / 2), this.maxIndex));
            this.goToSlide(targetIndex);
        }
    }
    
    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateSliderPosition();
            this.updateNavigation();
        }
    }
    
    next() {
        if (this.currentIndex < this.maxIndex) {
            this.currentIndex++;
            this.updateSliderPosition();
            this.updateNavigation();
        }
    }
    
    goToSlide(index) {
        this.currentIndex = Math.max(0, Math.min(index, this.maxIndex));
        this.updateSliderPosition();
        this.updateNavigation();
    }
    
    updateSliderPosition() {
        const translateX = -this.currentIndex * this.cardWidth;
        this.slider.style.transform = `translateX(${translateX}px)`;
    }
    
    updateNavigation() {
        if (this.prevBtn) {
            this.prevBtn.classList.toggle('disabled', this.currentIndex === 0);
            this.prevBtn.setAttribute('aria-disabled', this.currentIndex === 0);
        }
        
        if (this.nextBtn) {
            this.nextBtn.classList.toggle('disabled', this.currentIndex >= this.maxIndex);
            this.nextBtn.setAttribute('aria-disabled', this.currentIndex >= this.maxIndex);
        }
    }
    
    // Public methods for external control
    addCard(cardData) {
        const cardElement = this.createCardElement(cardData);
        this.slider.appendChild(cardElement);
        this.cards = this.container.querySelectorAll('.greeting-card');
        this.updateCardWidth();
        this.updateNavigation();
    }
    
    removeCard(index) {
        if (this.cards[index]) {
            this.cards[index].remove();
            this.cards = this.container.querySelectorAll('.greeting-card');
            this.updateCardWidth();
            this.updateNavigation();
        }
    }
    
    createCardElement(cardData) {
        const card = document.createElement('div');
        card.className = 'greeting-card';
        card.setAttribute('data-card-id', cardData.id);
        card.setAttribute('tabindex', '0');
        
        card.innerHTML = `
            <img src="${cardData.image}" alt="${cardData.title}" class="greeting-card-image">
            <div class="greeting-card-content">
                <h4 class="greeting-card-title">${cardData.title}</h4>
                <p class="greeting-card-price ${cardData.price === 'FREE' ? 'free' : ''}">${cardData.price}</p>
            </div>
        `;
        
        return card;
    }
}

// Auto-initialize sliders when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const sliders = document.querySelectorAll('.greeting-cards-section');
    sliders.forEach(slider => {
        new GreetingCardsSlider(slider);
    });
});

// Export for manual initialization
window.GreetingCardsSlider = GreetingCardsSlider;