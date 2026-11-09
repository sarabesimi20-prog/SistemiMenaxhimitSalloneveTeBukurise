
document.addEventListener('DOMContentLoaded', () => {
    const filters = document.querySelectorAll('.gallery-filter');
    const cards = document.querySelectorAll('.gallery-card');

    filters.forEach(filter => {
        filter.addEventListener('click', () => {
            filters.forEach(button => button.classList.remove('active'));
            filter.classList.add('active');

            const category = filter.dataset.filter;

            cards.forEach(card => {
                const matches = category === 'all' || card.dataset.category === category;
                card.classList.toggle('is-hidden', !matches);
            });
        });
    });

    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const imageButtons = document.querySelectorAll('.gallery-image-button');
    const lightboxClose = document.querySelector('.gallery-lightbox-close');

    function closeLightbox() {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImage.src = '';
    }

    imageButtons.forEach(button => {
        button.addEventListener('click', () => {
            lightboxImage.src = button.dataset.image;
            lightboxImage.alt = button.dataset.title;
            lightboxTitle.textContent = button.dataset.title;

            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden', 'false');
        });
    });

    lightboxClose.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', event => {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    const videoModal = document.getElementById('galleryVideoModal');
    const video = document.getElementById('galleryVideo');
    const openFilm = document.getElementById('openFilm');
    const videoClose = document.querySelector('.gallery-video-close');

    function closeVideo() {
        video.pause();
        video.currentTime = 0;
        videoModal.classList.remove('open');
        videoModal.setAttribute('aria-hidden', 'true');
    }

    openFilm.addEventListener('click', () => {
        videoModal.classList.add('open');
        videoModal.setAttribute('aria-hidden', 'false');

        video.play().catch(() => {
            videoModal.classList.add('open');
        });
    });

    videoClose.addEventListener('click', closeVideo);

    videoModal.addEventListener('click', event => {
        if (event.target === videoModal) {
            closeVideo();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeLightbox();
            closeVideo();
        }
    });
});
