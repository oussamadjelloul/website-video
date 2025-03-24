/**
 * CDN Test Project - Main JavaScript
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize components
    initVideoPlayers();
    initFormValidation();
    initAlerts();
    initImagePreviews();
    initTooltips();
});

/**
 * Video Player Initialization
 */
function initVideoPlayers() {
    // Find all video players on the page
    const videoContainers = document.querySelectorAll('.video-player-container');

    videoContainers.forEach(container => {
        const video = container.querySelector('video');
        const playBtn = container.querySelector('.play-btn');
        const volumeBtn = container.querySelector('.volume-btn');
        const progressBar = container.querySelector('.progress-bar');
        const currentTime = container.querySelector('.current-time');
        const duration = container.querySelector('.duration');

        if (!video) return;

        // Set up play button functionality
        if (playBtn) {
            playBtn.addEventListener('click', () => {
                if (video.paused) {
                    video.play();
                    playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                } else {
                    video.pause();
                    playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                }
            });
        }

        // Set up volume button functionality
        if (volumeBtn) {
            volumeBtn.addEventListener('click', () => {
                if (video.muted) {
                    video.muted = false;
                    volumeBtn.innerHTML = '<i class="bi bi-volume-up"></i>';
                } else {
                    video.muted = true;
                    volumeBtn.innerHTML = '<i class="bi bi-volume-mute"></i>';
                }
            });
        }

        // Update progress bar as video plays
        if (progressBar) {
            video.addEventListener('timeupdate', () => {
                const percent = (video.currentTime / video.duration) * 100;
                progressBar.style.width = percent + '%';

                // Update current time display
                if (currentTime) {
                    currentTime.textContent = formatTime(video.currentTime);
                }
            });

            // Allow seeking by clicking on the progress container
            const progressContainer = progressBar.parentElement;
            progressContainer.addEventListener('click', (e) => {
                const pos = (e.pageX - progressContainer.getBoundingClientRect().left) / progressContainer.offsetWidth;
                video.currentTime = pos * video.duration;
            });
        }

        // Set video duration once metadata is loaded
        video.addEventListener('loadedmetadata', () => {
            if (duration) {
                duration.textContent = formatTime(video.duration);
            }
        });

        // Check if we should use CDN URL instead of local URL
        if (video.dataset.cdnUrl) {
            video.src = video.dataset.cdnUrl;
        }
    });
}

/**
 * Format time from seconds to MM:SS format
 */
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    seconds = Math.floor(seconds % 60);
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

/**
 * Form Validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });

    // Video upload form specific validation
    const videoUploadForm = document.getElementById('video-upload-form');
    if (videoUploadForm) {
        const videoInput = document.getElementById('video-file');
        const maxSize = videoInput?.dataset?.maxSize || 10485760; // 10MB default

        videoInput?.addEventListener('change', () => {
            const file = videoInput.files[0];

            if (file) {
                // Check file size
                if (file.size > maxSize) {
                    videoInput.setCustomValidity(`File size exceeds ${formatFileSize(maxSize)}`);
                } else {
                    videoInput.setCustomValidity('');
                }

                // Check file type
                const allowedTypes = (videoInput.dataset.allowedTypes || 'video/mp4,video/webm').split(',');
                if (!allowedTypes.includes(file.type)) {
                    videoInput.setCustomValidity('Invalid file type');
                }
            }
        });
    }

    // Post form image validation
    const postForm = document.getElementById('post-form');
    if (postForm) {
        const imageInput = document.getElementById('image-file');
        const maxSize = imageInput?.dataset?.maxSize || 2097152; // 2MB default

        imageInput?.addEventListener('change', () => {
            const file = imageInput.files[0];

            if (file) {
                // Check file size
                if (file.size > maxSize) {
                    imageInput.setCustomValidity(`File size exceeds ${formatFileSize(maxSize)}`);
                } else {
                    imageInput.setCustomValidity('');
                }

                // Check file type
                const allowedTypes = (imageInput.dataset.allowedTypes || 'image/jpeg,image/png,image/gif').split(',');
                if (!allowedTypes.includes(file.type)) {
                    imageInput.setCustomValidity('Invalid file type');
                }
            }
        });
    }
}

/**
 * Initialize Bootstrap alerts with auto-close functionality
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(alert => {
        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
}

/**
 * Image preview functionality for upload forms
 */
function initImagePreviews() {
    // For post images
    const imageInput = document.getElementById('image-file');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.classList.add('d-none');
            }
        });
    }

    // For video thumbnails
    const thumbnailInput = document.getElementById('thumbnail-file');
    const thumbnailPreview = document.getElementById('thumbnail-preview');

    if (thumbnailInput && thumbnailPreview) {
        thumbnailInput.addEventListener('change', () => {
            const file = thumbnailInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    thumbnailPreview.src = e.target.result;
                    thumbnailPreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                thumbnailPreview.classList.add('d-none');
            }
        });
    }
}

/**
 * Format file size to human-readable format
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showToast('Copied to clipboard!');
            })
            .catch(() => {
                showToast('Failed to copy to clipboard', 'danger');
            });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast('Copied to clipboard!');
            } else {
                showToast('Failed to copy to clipboard', 'danger');
            }
        } catch (err) {
            showToast('Failed to copy to clipboard', 'danger');
        }

        document.body.removeChild(textArea);
    }
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    // Initialize and show the toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
