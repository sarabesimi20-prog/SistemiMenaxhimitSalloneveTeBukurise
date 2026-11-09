
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.av-discover').forEach(link => {
        link.addEventListener('click', event => {
            const target = document.querySelector('#av-story');

            if (target) {
                event.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});
