// Following a >>12 link a second time has to flash the post again, and :target
// only fires when the target changes — so the flash is driven by a class here,
// and the CSS falls back to :target when this never runs.
document.documentElement.classList.add('js');

const flash = (hash) => {
    if (! hash || hash.length < 2) {
        return;
    }

    let post = null;

    try {
        post = document.querySelector(hash);
    } catch {
        return; // Not something that can name an element.
    }

    if (! post?.classList.contains('post')) {
        return;
    }

    post.classList.remove('flash');
    // Reading the layout restarts the animation rather than letting the class
    // re-add pass unnoticed.
    void post.offsetWidth;
    post.classList.add('flash');
};

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href*="#"]');

    if (link) {
        flash(new URL(link.href, location.href).hash);
    }
});

window.addEventListener('hashchange', () => flash(location.hash));
window.addEventListener('DOMContentLoaded', () => flash(location.hash));
