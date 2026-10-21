/**
 * Smart Hostel Complaint Management System
 * Core Client-side Interactions & Smart Features
 * Yana Sareen - Software Engineering Laboratory Project
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide Icons if available
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Initialize Duplicate Complaint Detector (FR-18)
    initDuplicateDetector();

    // Initialize Star Rating Interaction (FR-20)
    initStarRating();

    // Auto-dismiss alerts after 5 seconds
    initAlertDismissal();
});

/**
 * Smart Duplicate Complaint Identification (FR-18)
 * Checks the database in real-time as student selects category and room/location
 */
function initDuplicateDetector() {
    const categorySelect = document.getElementById('complaint_category');
    const roomSelect = document.getElementById('complaint_room');
    const locationInput = document.getElementById('complaint_location');
    const alertBox = document.getElementById('duplicate_alert_box');
    const alertContent = document.getElementById('duplicate_alert_content');

    if (!categorySelect || !alertBox) return;

    let debounceTimer;

    function checkForDuplicates() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const category = categorySelect.value;
            const roomId = roomSelect ? roomSelect.value : '';
            const location = locationInput ? locationInput.value : '';

            if (!category || (!roomId && !location)) {
                alertBox.classList.add('hidden');
                return;
            }

            const url = `api.php?action=check_duplicate&category=${encodeURIComponent(category)}&room_id=${encodeURIComponent(roomId)}&location=${encodeURIComponent(location)}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data && data.duplicate_found) {
                        alertContent.innerHTML = `
                            <div class="flex items-start gap-3">
                                <span class="text-xl">⚠️</span>
                                <div>
                                    <h4 class="font-bold text-amber-800 text-sm">Potential Duplicate Issue Detected!</h4>
                                    <p class="text-xs text-amber-700 mt-1">
                                        An active ticket for <strong>${data.ticket.category}</strong> in this area is already 
                                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-amber-200 text-amber-900">${data.ticket.status}</span>
                                        (<strong>#${data.ticket.ticket_id}</strong>: "${data.ticket.title}").
                                    </p>
                                    <p class="text-xs text-amber-600 mt-1">
                                        Submitting this form will automatically link your ticket as an additional report, giving it higher urgency for maintenance staff.
                                    </p>
                                    <input type="hidden" name="parent_duplicate_id" value="${data.ticket.id}">
                                </div>
                            </div>
                        `;
                        alertBox.classList.remove('hidden');
                    } else {
                        alertBox.classList.add('hidden');
                    }
                })
                .catch(err => {
                    console.warn('Duplicate check error:', err);
                });
        }, 300);
    }

    categorySelect.addEventListener('change', checkForDuplicates);
    if (roomSelect) roomSelect.addEventListener('change', checkForDuplicates);
    if (locationInput) locationInput.addEventListener('input', checkForDuplicates);
}

/**
 * Star Rating Interaction for Feedback (FR-20)
 */
function initStarRating() {
    const starContainers = document.querySelectorAll('.star-rating');
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('input');
        stars.forEach(star => {
            star.addEventListener('change', (e) => {
                const label = container.closest('form').querySelector('.rating-text');
                if (label) {
                    const val = e.target.value;
                    const texts = {
                        '1': '1 Star - Very Poor / Unresolved',
                        '2': '2 Stars - Poor / Delayed',
                        '3': '3 Stars - Average',
                        '4': '4 Stars - Good / Resolved Cleanly',
                        '5': '5 Stars - Excellent / Prompt Resolution'
                    };
                    label.textContent = texts[val] || '';
                }
            });
        });
    });
}

/**
 * Modal Control Helpers
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside modal box
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.add('hidden');
        document.body.style.overflow = '';
    }
});

/**
 * Auto-dismiss flash alerts
 */
function initAlertDismissal() {
    const alerts = document.querySelectorAll('.auto-dismiss-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
}

/**
 * In-app Notification Mark as Read (FR-17)
 */
function markNotificationRead(notifId, targetUrl = null) {
    fetch(`api.php?action=mark_notification_read&id=${notifId}`)
        .then(() => {
            if (targetUrl) {
                window.location.href = targetUrl;
            } else {
                location.reload();
            }
        });
}

/**
 * Print View Trigger
 */
function triggerPrint() {
    window.print();
}
