/**
 * Greeting Cards Slider Component - Complete with grid view and selection toggle
 * Responsive horizontal slider with navigation arrows, progress bar, and grid view
 */

class GreetingCardsSlider {
    constructor(container) {
        this.container = container;
        this.slider = container.querySelector('.greeting-cards-slider');
        this.cards = container.querySelectorAll('.greeting-card');
        this.prevBtn = container.querySelector('.slider-nav-prev');
        this.nextBtn = container.querySelector('.slider-nav-next');
        this.progressFill = container.querySelector('.slider-progress-fill');
        this.seeAllBtn = container.querySelector('.greeting-cards-see-all');
        this.controlsPrevBtn = container.querySelector('.slider-nav-controls .slider-nav-prev');
        this.controlsNextBtn = container.querySelector('.slider-nav-controls .slider-nav-next');
        
        this.currentIndex = 0;
        this.cardWidth = 256; // 240px + 16px gap
        this.visibleCards = this.calculateVisibleCards();
        this.maxIndex = Math.max(0, this.cards.length - this.visibleCards);
        this.isGridView = false;
        this.selectedCard = null;
        
        this.init();
    }
    
    init() {
        this.updateCardWidth();
        this.updateNavigation();
        this.updateProgress();
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
        this.controlsPrevBtn?.addEventListener('click', () => this.prev());
        this.controlsNextBtn?.addEventListener('click', () => this.next());
        
        // See All / See Less toggle
        this.seeAllBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleView();
        });
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Touch/swipe support
        this.bindTouchEvents();
        
        // Window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Card click events with toggle selection
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
        if (this.isGridView) return; // No touch events in grid view
        
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
        if (this.isGridView) return; // No keyboard navigation in grid view
        
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
            if (!this.isGridView) {
                this.updateCardWidth();
                this.updateSliderPosition();
                this.updateNavigation();
                this.updateProgress();
            }
        }, 150);
    }
    
    handleCardClick(card, index) {
        // Toggle selection
        if (this.selectedCard === card) {
            // Deselect current card
            card.classList.remove('selected');
            this.selectedCard = null;
        } else {
            // Remove previous selection
            if (this.selectedCard) {
                this.selectedCard.classList.remove('selected');
            }
            
            // Select new card
            card.classList.add('selected');
            this.selectedCard = card;
            
            // Ensure selected card is visible in slider view
            if (!this.isGridView) {
                if (index < this.currentIndex || index >= this.currentIndex + this.visibleCards) {
                    const targetIndex = Math.max(0, Math.min(index - Math.floor(this.visibleCards / 2), this.maxIndex));
                    this.goToSlide(targetIndex);
                }
            }
        }
        
        // Emit custom event for card selection/deselection
        const event = new CustomEvent('cardSelected', {
            detail: {
                card: card,
                index: index,
                selected: this.selectedCard === card,
                cardData: this.getCardData(card)
            }
        });
        this.container.dispatchEvent(event);
    }
    
    getCardData(card) {
        return {
            id: card.dataset.cardId,
            title: card.querySelector('.greeting-card-title')?.textContent,
            price: card.querySelector('.greeting-card-price')?.textContent,
            image: card.querySelector('.greeting-card-image')?.src
        };
    }
    
    toggleView() {
        this.isGridView = !this.isGridView;
        
        if (this.isGridView) {
            this.container.classList.add('grid-view');
            this.seeAllBtn.textContent = 'See less';
        } else {
            this.container.classList.remove('grid-view');
            this.seeAllBtn.textContent = 'See all';
            // Restore slider functionality
            this.updateSliderPosition();
            this.updateNavigation();
            this.updateProgress();
        }
    }
    
    prev() {
        if (this.isGridView) return;
        
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateSliderPosition();
            this.updateNavigation();
            this.updateProgress();
        }
    }
    
    next() {
        if (this.isGridView) return;
        
        if (this.currentIndex < this.maxIndex) {
            this.currentIndex++;
            this.updateSliderPosition();
            this.updateNavigation();
            this.updateProgress();
        }
    }
    
    goToSlide(index) {
        if (this.isGridView) return;
        
        this.currentIndex = Math.max(0, Math.min(index, this.maxIndex));
        this.updateSliderPosition();
        this.updateNavigation();
        this.updateProgress();
    }
    
    updateSliderPosition() {
        if (this.isGridView) return;
        
        const translateX = -this.currentIndex * this.cardWidth;
        this.slider.style.transform = `translateX(${translateX}px)`;
    }
    
    updateNavigation() {
        if (this.isGridView) return;
        
        // Update main navigation arrows
        if (this.prevBtn) {
            this.prevBtn.classList.toggle('disabled', this.currentIndex === 0);
            this.prevBtn.setAttribute('aria-disabled', this.currentIndex === 0);
        }
        
        if (this.nextBtn) {
            this.nextBtn.classList.toggle('disabled', this.currentIndex >= this.maxIndex);
            this.nextBtn.setAttribute('aria-disabled', this.currentIndex >= this.maxIndex);
        }
        
        // Update controls navigation arrows
        if (this.controlsPrevBtn) {
            this.controlsPrevBtn.classList.toggle('disabled', this.currentIndex === 0);
            this.controlsPrevBtn.setAttribute('aria-disabled', this.currentIndex === 0);
        }
        
        if (this.controlsNextBtn) {
            this.controlsNextBtn.classList.toggle('disabled', this.currentIndex >= this.maxIndex);
            this.controlsNextBtn.setAttribute('aria-disabled', this.currentIndex >= this.maxIndex);
        }
    }
    
    updateProgress() {
        if (this.isGridView) return;
        
        if (this.progressFill && this.maxIndex > 0) {
            const progress = (this.currentIndex / this.maxIndex) * 100;
            this.progressFill.style.width = `${progress}%`;
        } else if (this.progressFill) {
            this.progressFill.style.width = '100%';
        }
    }
    
    // Public methods for external control
    addCard(cardData) {
        const cardElement = this.createCardElement(cardData);
        this.slider.appendChild(cardElement);
        this.cards = this.container.querySelectorAll('.greeting-card');
        this.updateCardWidth();
        this.updateNavigation();
        this.updateProgress();
    }
    
    removeCard(index) {
        if (this.cards[index]) {
            this.cards[index].remove();
            this.cards = this.container.querySelectorAll('.greeting-card');
            this.updateCardWidth();
            this.updateNavigation();
            this.updateProgress();
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