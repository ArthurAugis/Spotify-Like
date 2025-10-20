/**
 * Spotify-Like Utility Functions
 * Common functions used across the application
 */

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;
    
    // Generate unique ID for the toast
    const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    
    // Create toast element
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    
    // Determine toast colors based on type
    let bgClass = 'bg-info';
    let icon = 'fas fa-info-circle';
    
    switch (type) {
        case 'success':
            bgClass = 'bg-success';
            icon = 'fas fa-check-circle';
            break;
        case 'error':
            bgClass = 'bg-danger';
            icon = 'fas fa-exclamation-circle';
            break;
        case 'warning':
            bgClass = 'bg-warning';
            icon = 'fas fa-exclamation-triangle';
            break;
    }
    
    // Set toast HTML
    toastElement.innerHTML = `
    `;
    
    // Add to container
    toastContainer.appendChild(toastElement);
    
    // Initialize and show toast
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Fetch playlist data and play all tracks
 */
function playAllFromCard(playlistId, playlistName) {
    // Determine the correct route based on current page
    const isLibraryPage = window.location.pathname.includes('/library');
    const routePath = isLibraryPage ? 
        window.libraryViewPlaylistRoute || '/library/playlist/__ID__/view' : 
        window.homeViewPlaylistRoute || '/playlist/__ID__/view';
    
    const url = routePath.replace('__ID__', playlistId);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const playlist = data.playlist;
                
                if (playlist.tracks.length > 0) {
                    window.audioPlayer.playAllFromPlaylist(playlistId, playlistName, playlist.tracks);
                } else {
                    showToast('This playlist is empty', 'error');
                }
            } else {
                showToast('Error loading playlist: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred while loading the playlist', 'error');
            console.error('Error:', error);
        });
}

/**
 * Format duration in MM:SS format
 */
function formatDuration(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

/**
 * Escape string for use in JavaScript
 */
function escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, '&apos;').replace(/"/g, '&quot;');
}

/**
 * Safe play track function with proper escaping
 */
function safePlayTrack(audioFile, title, artist, coverImage = null, trackId = null) {
    // Ensure proper escaping of parameters
    const safeTitle = typeof title === 'string' ? title : '';
    const safeArtist = typeof artist === 'string' ? artist : '';
    const safeCover = typeof coverImage === 'string' ? coverImage : null;

    // If a trackId is provided, prefer calling playTrack with trackId info (we'll let playTrack decide how to call the API)
    if (window.audioPlayer && typeof window.audioPlayer.playTrack === 'function') {
        window.audioPlayer.playTrack(audioFile, safeTitle, safeArtist, safeCover, trackId);
    } else {
        console.warn('Audio player not available');
    }
}

/**
 * Initialize common page functionality
 */
function initializePage() {
    // Check if audio player is available
    if (typeof window.audioPlayer === 'undefined') {
        console.warn('Audio player not initialized yet, retrying...');
        setTimeout(initializePage, 100);
        return;
    }
    
    console.log('Page initialized with audio player');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializePage);

// Make functions globally available
window.showToast = showToast;
window.playAllFromCard = playAllFromCard;
window.formatDuration = formatDuration;
window.escapeJs = escapeJs;
window.safePlayTrack = safePlayTrack;