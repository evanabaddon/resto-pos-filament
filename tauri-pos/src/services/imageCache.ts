import { invoke } from '@tauri-apps/api/core';

export const imageCacheService = {
    /**
     * Download image from URL and save to local cache
     */
    downloadImage: async (url: string, filename: string): Promise<string> => {
        try {
            return await invoke('download_image', { url, filename });
        } catch (error) {
            console.error(`Failed to download image ${filename}:`, error);
            throw error;
        }
    },

    /**
     * Get local file path for cached image
     */
    getLocalImagePath: async (filename: string): Promise<string | null> => {
        try {
            return await invoke('get_image_path', { filename });
        } catch (error) {
            // Image not cached, return null
            return null;
        }
    },

    /**
     * Clear all cached images
     */
    clearCache: async (): Promise<string> => {
        try {
            return await invoke('clear_image_cache');
        } catch (error) {
            console.error('Failed to clear image cache:', error);
            throw error;
        }
    },

    /**
     * Get total size of image cache in bytes
     */
    getCacheSize: async (): Promise<number> => {
        try {
            return await invoke('get_cache_size');
        } catch (error) {
            console.error('Failed to get cache size:', error);
            return 0;
        }
    },

    /**
     * Download multiple images with concurrency control
     */
    downloadImages: async (images: { url: string, filename: string }[], maxConcurrent = 5): Promise<void> => {
        const queue = [...images];
        const inProgress: Promise<void>[] = [];

        while (queue.length > 0 || inProgress.length > 0) {
            // Fill up to maxConcurrent
            while (inProgress.length < maxConcurrent && queue.length > 0) {
                const image = queue.shift()!;
                const promise = imageCacheService.downloadImage(image.url, image.filename)
                    .then(() => {
                        console.log(`✅ Downloaded: ${image.filename}`);
                    })
                    .catch((err) => {
                        console.warn(`⚠️ Failed to download ${image.filename}:`, err);
                    })
                    .finally(() => {
                        // Remove from inProgress when done
                        const index = inProgress.indexOf(promise);
                        if (index > -1) inProgress.splice(index, 1);
                    });

                inProgress.push(promise);
            }

            // Wait for at least one to finish
            if (inProgress.length > 0) {
                await Promise.race(inProgress);
            }
        }

        console.log('🎉 All images downloaded');
    }
};
