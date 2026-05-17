export async function resizeImage(file: File, maxSize = 800): Promise<File> {
    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);

        img.onload = () => {
            URL.revokeObjectURL(url);

            let { width, height } = img;

            if (width <= maxSize && height <= maxSize) {
                resolve(file);
                return;
            }

            if (width > height) {
                height = Math.round((height * maxSize) / width);
                width = maxSize;
            } else {
                width = Math.round((width * maxSize) / height);
                height = maxSize;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                resolve(file);
                return;
            }

            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    if (blob) {
                        const name = file.name.replace(/\.[^.]+$/, '.jpg');
                        resolve(new File([blob], name, { type: 'image/jpeg' }));
                        return;
                    }
                    // toBlob returned null (low-memory Android) — fall back to toDataURL
                    try {
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                        const base64 = dataUrl.split(',')[1];
                        const bytes = atob(base64);
                        const ab = new ArrayBuffer(bytes.length);
                        const ia = new Uint8Array(ab);
                        for (let i = 0; i < bytes.length; i++) ia[i] = bytes.charCodeAt(i);
                        const fallbackBlob = new Blob([ab], { type: 'image/jpeg' });
                        const name = file.name.replace(/\.[^.]+$/, '.jpg');
                        resolve(new File([fallbackBlob], name, { type: 'image/jpeg' }));
                    } catch {
                        resolve(file);
                    }
                },
                'image/jpeg',
                0.85
            );
        };

        img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
        img.src = url;
    });
}
