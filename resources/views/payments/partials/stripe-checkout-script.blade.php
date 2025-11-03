<script>
    (function () {
        if (window.__stripeCheckoutInitialized) {
            return;
        }

        window.__stripeCheckoutInitialized = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        const bindStripeButtons = () => {
            const stripeButtons = document.querySelectorAll('[data-stripe-button]');

            stripeButtons.forEach((button) => {
                if (button.dataset.stripeBound === 'true') {
                    return;
                }

                button.dataset.stripeBound = 'true';

                button.addEventListener('click', async (event) => {
                    if (event.defaultPrevented) {
                        return;
                    }

                    const form = button.closest('form');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();

                    const errorContainer = form.querySelector('[data-stripe-error]');
                    const originalLabel = button.dataset.originalText || button.textContent.trim();
                    const loadingLabel = button.dataset.loadingText || 'Processing...';

                    button.dataset.originalText = originalLabel;
                    button.disabled = true;
                    button.textContent = loadingLabel;

                    if (errorContainer) {
                        errorContainer.textContent = '';
                    }

                    try {
                        const targetUrl = button.dataset.stripeUrl || button.getAttribute('formaction') || form.action;

                        if (!targetUrl) {
                            throw new Error('Stripe checkout URL is missing.');
                        }

                        const response = await fetch(targetUrl, {
                            method: form.getAttribute('method') || 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                            },
                            credentials: 'same-origin',
                            body: new FormData(form),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (response.ok && data.redirect_url) {
                            window.location.href = data.redirect_url;
                            return;
                        }

                        const message =
                            (data.errors && data.errors.checkout && data.errors.checkout[0]) ||
                            data.message ||
                            'Unable to start Stripe checkout. Please try again later.';

                        if (errorContainer) {
                            errorContainer.textContent = message;
                        } else {
                            console.error('Stripe checkout error:', message);
                        }
                    } catch (error) {
                        if (errorContainer) {
                            errorContainer.textContent = 'Network error while contacting Stripe. Please try again.';
                        }

                        console.error('Stripe checkout network error:', error);
                    } finally {
                        button.disabled = false;
                        button.textContent = button.dataset.originalText || originalLabel;
                    }
                });
            });
        };

        document.addEventListener('DOMContentLoaded', bindStripeButtons);

        if (document.readyState !== 'loading') {
            bindStripeButtons();
        }
    })();
</script>
