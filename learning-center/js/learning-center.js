document.addEventListener('DOMContentLoaded', () => {
    // Enhanced Categories horizontal scroll
    const categoriesWrapper = document.querySelector('.categories-wrapper');
    if (categoriesWrapper) {
        // Wrap categories-wrapper with container div if not already wrapped
        if (!categoriesWrapper.parentElement.classList.contains('categories-container')) {
            const container = document.createElement('div');
            container.className = 'categories-container';
            categoriesWrapper.parentNode.insertBefore(container, categoriesWrapper);
            container.appendChild(categoriesWrapper);
        }

        // Add scroll buttons
        const prevButton = document.createElement('button');
        const nextButton = document.createElement('button');
        prevButton.className = 'scroll-button prev';
        nextButton.className = 'scroll-button next';
        prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
        nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
        categoriesWrapper.parentElement.appendChild(prevButton);
        categoriesWrapper.parentElement.appendChild(nextButton);

        // Scroll functionality variables
        let isDown = false;
        let startX;
        let scrollLeft;
        let velocity = 0;
        const friction = 0.95;
        let animationFrameId;

        // Update button states
        const updateScrollButtons = () => {
            const isAtStart = categoriesWrapper.scrollLeft <= 0;
            const isAtEnd = categoriesWrapper.scrollLeft >= categoriesWrapper.scrollWidth - categoriesWrapper.clientWidth;
            
            prevButton.disabled = isAtStart;
            nextButton.disabled = isAtEnd;
        };

        // Smooth scroll function
        const smoothScroll = (target) => {
            const start = categoriesWrapper.scrollLeft;
            const startTime = performance.now();
            const duration = 300;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function
                const easeProgress = 1 - Math.pow(1 - progress, 4);
                
                categoriesWrapper.scrollLeft = start + (target - start) * easeProgress;

                if (progress < 1) {
                    animationFrameId = requestAnimationFrame(animate);
                }
            };

            cancelAnimationFrame(animationFrameId);
            animationFrameId = requestAnimationFrame(animate);
        };

        // Button click handlers
        prevButton.addEventListener('click', () => {
            const scrollAmount = categoriesWrapper.clientWidth * 0.8;
            smoothScroll(categoriesWrapper.scrollLeft - scrollAmount);
        });

        nextButton.addEventListener('click', () => {
            const scrollAmount = categoriesWrapper.clientWidth * 0.8;
            smoothScroll(categoriesWrapper.scrollLeft + scrollAmount);
        });

        // Mouse wheel horizontal scroll
        categoriesWrapper.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault();
                categoriesWrapper.scrollLeft += e.deltaY;
                updateScrollButtons();
            }
        });

        // Mouse drag scroll
        const startDragging = (e) => {
            isDown = true;
            categoriesWrapper.style.cursor = 'grabbing';
            startX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
            scrollLeft = categoriesWrapper.scrollLeft;
            cancelAnimationFrame(animationFrameId);
        };

        const stopDragging = () => {
            isDown = false;
            categoriesWrapper.style.cursor = 'grab';
        };

        const drag = (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
            const walk = (x - startX) * 2;
            categoriesWrapper.scrollLeft = scrollLeft - walk;
            updateScrollButtons();
        };

        // Mouse events
        categoriesWrapper.addEventListener('mousedown', startDragging);
        categoriesWrapper.addEventListener('mousemove', drag);
        categoriesWrapper.addEventListener('mouseup', stopDragging);
        categoriesWrapper.addEventListener('mouseleave', stopDragging);

        // Touch events
        categoriesWrapper.addEventListener('touchstart', startDragging);
        categoriesWrapper.addEventListener('touchmove', drag);
        categoriesWrapper.addEventListener('touchend', stopDragging);

        // Scroll event listener for button updates
        categoriesWrapper.addEventListener('scroll', updateScrollButtons);

        // Initialize button states
        updateScrollButtons();
    }

    // Smooth scroll for article page
    const articleLinks = document.querySelectorAll('a[href^="#"]');
    articleLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});