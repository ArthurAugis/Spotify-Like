SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

ALTER DATABASE spotify_db CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET GLOBAL max_allowed_packet = 1073741824;
SET GLOBAL innodb_log_file_size = 512M;
SET GLOBAL innodb_buffer_pool_size = 256M;

SELECT 'Spotify-Like database initialized successfully!' as message;