/**
 * Unified Audio Player System
 * Global audio player for streaming platform
 */

// Use IIFE to prevent multiple executions
(function() {
    'use strict';
    
    // Check if AudioPlayer is already defined to prevent redeclaration
    if (typeof window.AudioPlayer !== 'undefined') {
        return; // Already loaded, exit early
    }
    
window.AudioPlayer = class {
    constructor() {
        this.currentAudio = null;
        this.currentTrackInfo = null;
        this.currentPlaylistQueue = [];
        this.currentTrackIndex = 0;
        this.loopMode = 0;
        this.isPlaying = false;
        this.isDragging = false;
        this.volume = 100;
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    init() {
        this.setupEventListeners();
        this.updateLoopButtons();
        this.updateNavigationButtons();
        console.log('AudioPlayer initialized');
    }
    
    playTrack(audioFile, title, artist, coverImage = null, trackId = null) {
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
        }

        // Use secure media route instead of direct file access
        this.currentAudio = new Audio(`/media/track/${audioFile}`);
        this.currentTrackInfo = { audioFile, title, artist, coverImage };
        this.currentAudio.volume = this.volume / 100;

        // Sync volume sliders to ensure UI consistency
        this.syncVolumeSliders(this.volume);

        // Show music player
        const musicPlayer = document.getElementById('musicPlayer');
        if (musicPlayer) {
            musicPlayer.style.display = 'block';
        }

        // Update track info in both players
        this.updateTrackInfo(title, artist, coverImage);

        // Set up audio event listeners
        this.setupAudioEventListeners();

        // Incrémentation du playCount côté serveur (API)
        const doTrackListen = (id) => {
            if (!id) return;
            fetch(`/api/track/${id}/listen`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    if (window.audioPlayer && typeof window.audioPlayer.updatePlayCountInUI === 'function') {
                        window.audioPlayer.updatePlayCountInUI('track', id, data.playCount);
                    }
                } else {
                    console.warn('Track listen API responded without success', data);
                }
            })
            .catch(err => console.error('Error calling track listen API', err));
        };

        if (trackId) {
            doTrackListen(trackId.toString());
        } else if (audioFile && typeof audioFile === 'string') {
            // Fallback: try to parse ID from filename (legacy)
            const match = audioFile.match(/^(\d+)-/);
            if (match) doTrackListen(match[1]);
        }

        // Play the track
        this.currentAudio.play().then(() => {
            this.isPlaying = true;
            this.updatePlayPauseButtons(false); // false = playing
            console.log(`Now playing: ${title} by ${artist}`);
        }).catch(error => {
            console.error('Error playing track:', error);
            this.showToast(`Could not play: ${title}`, 'error');
        });
    }
    
    /**
     * Set up audio event listeners
     */
    setupAudioEventListeners() {
        if (!this.currentAudio) return;
        
        // Remove existing listeners to prevent duplicates
        this.currentAudio.removeEventListener('timeupdate', this.handleTimeUpdate);
        this.currentAudio.removeEventListener('loadedmetadata', this.handleLoadedMetadata);
        this.currentAudio.removeEventListener('ended', this.handleTrackEnded);
        this.currentAudio.removeEventListener('play', this.handlePlay);
        this.currentAudio.removeEventListener('pause', this.handlePause);
        this.currentAudio.removeEventListener('volumechange', this.handleVolumeChange);
        
        // Bind methods to preserve 'this' context
        this.handleTimeUpdate = this.handleTimeUpdate.bind(this);
        this.handleLoadedMetadata = this.handleLoadedMetadata.bind(this);
        this.handleTrackEnded = this.handleTrackEnded.bind(this);
        this.handlePlay = this.handlePlay.bind(this);
        this.handlePause = this.handlePause.bind(this);
        this.handleVolumeChange = this.handleVolumeChange.bind(this);
        
        // Add event listeners
        this.currentAudio.addEventListener('timeupdate', this.handleTimeUpdate);
        this.currentAudio.addEventListener('loadedmetadata', this.handleLoadedMetadata);
        this.currentAudio.addEventListener('ended', this.handleTrackEnded);
        this.currentAudio.addEventListener('play', this.handlePlay);
        this.currentAudio.addEventListener('pause', this.handlePause);
        this.currentAudio.addEventListener('volumechange', this.handleVolumeChange);
    }
    
    // Event handlers
    handleTimeUpdate() {
        if (!this.isDragging) {
            this.updateProgressBars();
        }
    }
    
    handleLoadedMetadata() {
        this.updateProgressBars();
        this.updateTimeDisplays();
    }
    
    handleTrackEnded() {
        if (this.loopMode === 2) {
            // Loop current track
            this.currentAudio.currentTime = 0;
            this.currentAudio.play();
        } else {
            // Go to next track or stop
            this.playNextTrack();
        }
    }
    
    handlePlay() {
        this.isPlaying = true;
        this.updatePlayPauseButtons(false);
    }
    
    handlePause() {
        this.isPlaying = false;
        this.updatePlayPauseButtons(true);
    }
    
    handleVolumeChange() {
        const volume = Math.round(this.currentAudio.volume * 100);
        this.volume = volume;
        this.syncVolumeSliders(volume);
    }
    
    /**
     * Update track info in both players
     */
    updateTrackInfo(title, artist, coverImage) {
        // Use secure media route instead of direct file access
        const coverPath = coverImage ? `/media/cover/${coverImage}` : this.getDefaultCover();
        
        // Mini player
        this.updateElement('miniTitle', title);
        this.updateElement('miniArtist', artist);
        this.updateCoverImage('#miniCover img', coverPath, `${title} cover`);
        
        // Fullscreen player
        this.updateElement('fullscreenTitle', title);
        this.updateElement('fullscreenArtist', artist);
        this.updateCoverImage('#fullscreenCover', coverPath, `${title} cover`);
        
        // Update page title
        document.title = `🎵 ${title} - ${artist} | Spotify-Like`;
    }
    
    /**
     * Helper method to update element text content safely
     */
    updateElement(id, content) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = content;
        }
    }

    /**
     * Update playCount badge in the UI for track or playlist
     */
    updatePlayCountInUI(type, id, newCount) {
        try {
            const elId = type === 'track' ? `track-playcount-${id}` : `playlist-playcount-${id}`;
            const el = document.getElementById(elId);
            if (el) {
                const countSpan = el.querySelector('.count');
                if (countSpan) {
                    countSpan.textContent = newCount;
                } else {
                    // fallback: replace text content
                    el.textContent = newCount;
                }
            }
        } catch (e) {
            console.error('Failed to update play count in UI', e);
        }
    }
    
    /**
     * Helper method to update cover image safely
     */
    updateCoverImage(selector, src, alt) {
        const img = document.querySelector(selector);
        if (img) {
            img.src = src;
            img.alt = alt;
        }
    }
    
    /**
     * Get default cover image
     */
    getDefaultCover(size = 300) {
        return `data:image/svg+xml;base64,${btoa(`
            <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="${size}" height="${size}" fill="#374151"/>
                <path d="M${size/2} ${size*0.7}C${size*0.31} ${size*0.7} ${size*0.7} ${size*0.62} ${size*0.7} ${size/2}S${size*0.62} ${size*0.3} ${size/2} ${size*0.3}S${size*0.3} ${size*0.38} ${size*0.3} ${size/2}S${size*0.38} ${size*0.7} ${size/2} ${size*0.7}Z" fill="#6B7280"/>
                <path d="M${size/2} ${size*0.6}C${size*0.56} ${size*0.6} ${size*0.6} ${size*0.56} ${size*0.6} ${size/2}S${size*0.56} ${size*0.4} ${size/2} ${size*0.4}S${size*0.4} ${size*0.44} ${size*0.4} ${size/2}S${size*0.44} ${size*0.6} ${size/2} ${size*0.6}Z" fill="#9CA3AF"/>
            </svg>
        `)}`;
    }
    
    /**
     * Update progress bars
     */
    updateProgressBars() {
        if (!this.currentAudio || !this.currentAudio.duration || isNaN(this.currentAudio.duration)) return;
        
        const progress = (this.currentAudio.currentTime / this.currentAudio.duration) * 100;
        
        this.updateProgressBar('miniProgressBar', progress);
        this.updateProgressBar('fullscreenProgressBar', progress);
        
        this.updateTimeDisplays();
    }
    
    /**
     * Helper method to update progress bar safely
     */
    updateProgressBar(id, progress) {
        const progressBar = document.getElementById(id);
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    }
    
    /**
     * Update time displays
     */
    updateTimeDisplays() {
        if (!this.currentAudio) return;
        
        const currentTime = this.formatTime(this.currentAudio.currentTime || 0);
        const totalTime = this.formatTime(this.currentAudio.duration || 0);
        
        // Update all time displays
        this.updateElement('miniCurrentTime', currentTime);
        this.updateElement('miniTotalTime', totalTime);
        this.updateElement('fullscreenCurrentTime', currentTime);
        this.updateElement('fullscreenTotalTime', totalTime);
    }
    
    /**
     * Format time in MM:SS format
     */
    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    /**
     * Update play/pause buttons
     */
    updatePlayPauseButtons(isPaused) {
        const miniBtn = document.getElementById('playPauseBtn');
        const fullscreenBtn = document.getElementById('fullscreenPlayPauseBtn');
        
        const playIcon = '<i class="fas fa-play"></i>';
        const pauseIcon = '<i class="fas fa-pause"></i>';
        const fullscreenPlayIcon = '<i class="fas fa-play fa-2x"></i>';
        const fullscreenPauseIcon = '<i class="fas fa-pause fa-2x"></i>';
        
        if (miniBtn) {
            miniBtn.innerHTML = isPaused ? playIcon : pauseIcon;
        }
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = isPaused ? fullscreenPlayIcon : fullscreenPauseIcon;
        }
    }
    
    /**
     * Toggle play/pause
     */
    togglePlayPause() {
        if (!this.currentAudio) return;
        
        if (this.isPlaying) {
            this.currentAudio.pause();
        } else {
            this.currentAudio.play();
        }
    }
    
    /**
     * Play next track
     */
    playNextTrack() {
        if (this.currentPlaylistQueue.length === 0) {
            this.stopPlayback();
            return;
        }
        
        this.currentTrackIndex++;
        
        if (this.currentTrackIndex >= this.currentPlaylistQueue.length) {
            if (this.loopMode === 1) {
                // Loop playlist
                this.currentTrackIndex = 0;
            } else {
                // End of playlist
                this.stopPlayback();
                return;
            }
        }
        
        const nextTrack = this.currentPlaylistQueue[this.currentTrackIndex];
        this.playTrack(nextTrack.audioFile, nextTrack.title, nextTrack.artist, nextTrack.coverImage);
        this.updateNavigationButtons();
        this.updatePlaylistQueue();
    }
    
    /**
     * Play previous track
     */
    playPreviousTrack() {
        if (this.currentPlaylistQueue.length === 0) return;
        
        this.currentTrackIndex--;
        
        if (this.currentTrackIndex < 0) {
            this.currentTrackIndex = this.currentPlaylistQueue.length - 1;
        }
        
        const prevTrack = this.currentPlaylistQueue[this.currentTrackIndex];
        this.playTrack(prevTrack.audioFile, prevTrack.title, prevTrack.artist, prevTrack.coverImage);
        this.updateNavigationButtons();
        this.updatePlaylistQueue();
    }
    
    /**
     * Toggle loop mode
     */
    toggleLoopMode() {
        this.loopMode = (this.loopMode + 1) % 3;
        this.updateLoopButtons();
    }
    
    /**
     * Update loop buttons
     */
    updateLoopButtons() {
        const miniBtn = document.getElementById('loopBtn');
        const fullscreenBtn = document.getElementById('fullscreenLoopBtn');
        
        let title, icon, className;
        
        switch (this.loopMode) {
            case 0:
                title = 'Loop Off';
                icon = 'fas fa-redo';
                className = 'btn-outline-light';
                break;
            case 1:
                title = 'Loop Playlist';
                icon = 'fas fa-redo';
                className = 'btn-warning';
                break;
            case 2:
                title = 'Loop Track';
                icon = 'fas fa-redo';
                className = 'btn-success';
                break;
        }
        
        if (miniBtn) {
            miniBtn.title = title;
            miniBtn.className = miniBtn.className.replace(/btn-(outline-light|warning|success)/, className);
            miniBtn.innerHTML = `<i class="${icon}"></i>`;
        }
        
        if (fullscreenBtn) {
            fullscreenBtn.title = title;
            fullscreenBtn.className = fullscreenBtn.className.replace(/btn-(outline-light|warning|success)/, className);
            fullscreenBtn.innerHTML = `<i class="${icon}"></i>`;
        }
    }
    
    /**
     * Update navigation buttons state
     */
    updateNavigationButtons() {
        const hasPrevious = this.currentPlaylistQueue.length > 0 && this.currentTrackIndex > 0;
        const hasNext = this.currentPlaylistQueue.length > 0 && this.currentTrackIndex < this.currentPlaylistQueue.length - 1;
        
        // Previous buttons
        this.updateButtonState('prevBtn', !hasPrevious);
        this.updateButtonState('fullscreenPrevBtn', !hasPrevious);
        
        // Next buttons
        this.updateButtonState('nextBtn', !hasNext);
        this.updateButtonState('fullscreenNextBtn', !hasNext);
    }
    
    /**
     * Helper method to update button disabled state
     */
    updateButtonState(id, disabled) {
        const button = document.getElementById(id);
        if (button) {
            button.disabled = disabled;
        }
    }
    
    /**
     * Update playlist queue display
     */
    updatePlaylistQueue() {
        const queueContainer = document.getElementById('playlistQueue');
        const queueList = document.getElementById('queueList');
        
        if (!queueContainer || !queueList) return;
        
        if (this.currentPlaylistQueue.length === 0) {
            queueContainer.style.display = 'none';
            return;
        }
        
        queueContainer.style.display = 'block';
        queueList.innerHTML = '';
        
        this.currentPlaylistQueue.forEach((track, index) => {
            const isActive = index === this.currentTrackIndex;
            const item = document.createElement('div');
            item.className = `list-group-item list-group-item-action bg-transparent border-secondary ${isActive ? 'active' : ''}`;
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="me-3" style="width: 30px;">
                        ${isActive ? '<i class="fas fa-play text-success"></i>' : (index + 1)}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${track.title}</div>
                        <small class="text-muted">${track.artist}</small>
                    </div>
                </div>
            `;
            
            if (!isActive) {
                item.style.cursor = 'pointer';
                item.onclick = () => {
                    this.currentTrackIndex = index;
                    this.playTrack(track.audioFile, track.title, track.artist, track.coverImage);
                    this.updateNavigationButtons();
                    this.updatePlaylistQueue();
                };
            }
            
            queueList.appendChild(item);
        });
    }
    
    /**
     * Stop playback
     */
    stopPlayback() {
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
        }
        
        this.isPlaying = false;
        const musicPlayer = document.getElementById('musicPlayer');
        if (musicPlayer) {
            musicPlayer.style.display = 'none';
        }
        document.title = 'Spotify-Like';
        this.currentTrackInfo = null;
        this.currentPlaylistQueue = [];
        this.currentTrackIndex = 0;
    }
    
    /**
     * Seek to position in track
     */
    seekToPosition(percentage) {
        if (!this.currentAudio || !this.currentAudio.duration) return;
        
        const newTime = (percentage / 100) * this.currentAudio.duration;
        this.currentAudio.currentTime = newTime;
        this.updateProgressBars();
    }
    
    /**
     * Set volume
     */
    setVolume(volume) {
        if (!this.currentAudio) return;
        
        this.currentAudio.volume = volume / 100;
        this.volume = volume;
    }
    
    /**
     * Sync volume sliders
     */
    syncVolumeSliders(volume) {
        const volumeSlider = document.getElementById('volumeSlider');
        const fullscreenVolumeSlider = document.getElementById('fullscreenVolumeSlider');
        
        if (volumeSlider) volumeSlider.value = volume;
        if (fullscreenVolumeSlider) fullscreenVolumeSlider.value = volume;
    }
    
    /**
     * Play all tracks from a playlist
     */
    playAllFromPlaylist(playlistId, playlistName, tracks) {
        if (tracks && tracks.length > 0) {
            // Incrémenter le playCount côté serveur (API)
            if (playlistId) {
                fetch(`/api/playlist/${playlistId}/listen`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        if (window.audioPlayer && typeof window.audioPlayer.updatePlayCountInUI === 'function') {
                            window.audioPlayer.updatePlayCountInUI('playlist', playlistId, data.playCount);
                        }
                    } else {
                        console.warn('Playlist listen API responded without success', data);
                    }
                })
                .catch(err => console.error('Error calling playlist listen API', err));
            }

            // Set up playlist queue
            this.currentPlaylistQueue = tracks;
            this.currentTrackIndex = 0;
            
            // Start playing first track
            const firstTrack = tracks[0];
            this.playTrack(firstTrack.audioFile, firstTrack.title, firstTrack.artist, firstTrack.coverImage);
            
            // Update navigation and queue
            this.updateNavigationButtons();
            this.updatePlaylistQueue();
        } else {
            this.showToast('This playlist is empty', 'error');
        }
    }
    
    /**
     * Setup event listeners for player controls
     */
    setupEventListeners() {
        // Play/Pause buttons
        this.addEventListenerSafe('playPauseBtn', 'click', () => this.togglePlayPause());
        this.addEventListenerSafe('fullscreenPlayPauseBtn', 'click', () => this.togglePlayPause());
        
        // Navigation buttons
        this.addEventListenerSafe('prevBtn', 'click', () => this.playPreviousTrack());
        this.addEventListenerSafe('fullscreenPrevBtn', 'click', () => this.playPreviousTrack());
        this.addEventListenerSafe('nextBtn', 'click', () => this.playNextTrack());
        this.addEventListenerSafe('fullscreenNextBtn', 'click', () => this.playNextTrack());
        
        // Loop buttons
        this.addEventListenerSafe('loopBtn', 'click', () => this.toggleLoopMode());
        this.addEventListenerSafe('fullscreenLoopBtn', 'click', () => this.toggleLoopMode());
        
        // Fullscreen button
        this.addEventListenerSafe('fullscreenBtn', 'click', () => {
            const fullscreenPlayer = document.getElementById('fullscreenPlayer');
            if (fullscreenPlayer && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(fullscreenPlayer).show();
            }
        });
        
        // Volume sliders
        this.addEventListenerSafe('volumeSlider', 'input', (e) => {
            this.setVolume(e.target.value);
            this.syncVolumeSliders(e.target.value);
        });
        
        this.addEventListenerSafe('fullscreenVolumeSlider', 'input', (e) => {
            this.setVolume(e.target.value);
            this.syncVolumeSliders(e.target.value);
        });
        
        // Progress bar clicking
        this.addEventListenerSafe('miniProgressContainer', 'click', (e) => {
            this.handleProgressClick(e, e.currentTarget);
        });
        
        this.addEventListenerSafe('fullscreenProgressContainer', 'click', (e) => {
            this.handleProgressClick(e, e.currentTarget);
        });
    }
    
    /**
     * Helper method to safely add event listeners
     */
    addEventListenerSafe(id, event, handler) {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener(event, handler);
        }
    }
    
    /**
     * Handle progress bar clicks
     */
    handleProgressClick(e, container) {
        const rect = container.getBoundingClientRect();
        const percentage = ((e.clientX - rect.left) / rect.width) * 100;
        this.seekToPosition(Math.max(0, Math.min(100, percentage)));
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            console.log(`Toast (${type}): ${message}`);
        }
    }
}

// Create global instance (only if it doesn't exist)
if (!window.audioPlayer || !(window.audioPlayer instanceof window.AudioPlayer)) {
    window.audioPlayer = new window.AudioPlayer();
}

// Global functions for backward compatibility
window.playTrack = (audioFile, title, artist, coverImage, trackId = null) => {
    window.audioPlayer.playTrack(audioFile, title, artist, coverImage, trackId);
};

window.playAllFromCard = (playlistId, playlistName) => {
    // This will be implemented by each page that needs it
    console.log('playAllFromCard called:', playlistId, playlistName);
};

})(); // End of IIFE