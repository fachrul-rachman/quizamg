function setPasswordToggleState(container, isVisible) {
    const input = container.querySelector('[data-password-input]');
    const label = container.querySelector('[data-password-label]');
    const showIcon = container.querySelector('[data-password-icon="show"]');
    const hideIcon = container.querySelector('[data-password-icon="hide"]');
    const button = container.querySelector('[data-password-toggle]');

    if (!input || !button) return;

    input.type = isVisible ? 'text' : 'password';
    button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');

    const showLabel = button.getAttribute('data-password-label-show') || 'Show password';
    const hideLabel = button.getAttribute('data-password-label-hide') || 'Hide password';
    const ariaLabel = isVisible ? hideLabel : showLabel;
    button.setAttribute('aria-label', ariaLabel);
    if (label) label.textContent = ariaLabel;

    if (showIcon) showIcon.classList.toggle('hidden', isVisible);
    if (hideIcon) hideIcon.classList.toggle('hidden', !isVisible);
}

function closestMatching(startNode, selector) {
    let node = startNode;
    while (node && node !== document) {
        if (node.nodeType === 1 && typeof node.matches === 'function' && node.matches(selector)) {
            return node;
        }
        node = node.parentNode ?? node.host ?? null;
    }
    return null;
}

document.addEventListener('click', (event) => {
    const button = closestMatching(event.target, '[data-password-toggle]');
    if (!button) return;

    event.preventDefault();

    const container = closestMatching(button, '[data-password-field]') ?? button.parentElement;
    if (!container) return;

    const input = container.querySelector('[data-password-input]');
    if (!input) return;

    const ariaPressed = button.getAttribute('aria-pressed');
    const currentlyVisible =
        ariaPressed === 'true'
        || (ariaPressed == null && input.type === 'text');

    setPasswordToggleState(container, !currentlyVisible);
});
