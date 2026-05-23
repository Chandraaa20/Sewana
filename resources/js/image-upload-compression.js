const IMAGE_MIME_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);
const DEFAULT_OPTIONS = {
    maxDimension: 1800,
    quality: 0.86,
};
const DOCUMENT_OPTIONS = {
    maxDimension: 2000,
    quality: 0.9,
};

function supportsRequiredApis() {
    const canvas = document.createElement('canvas');

    return typeof DataTransfer !== 'undefined'
        && typeof File !== 'undefined'
        && typeof canvas.toBlob === 'function';
}

function optionsForInput(input) {
    const name = input.name.toLowerCase();

    if (name.includes('identity') || name.includes('bukti')) {
        return DOCUMENT_OPTIONS;
    }

    return DEFAULT_OPTIONS;
}

function webpName(originalName) {
    const baseName = originalName.replace(/\.[^.]+$/, '');

    return `${baseName || 'image'}.webp`;
}

function targetSize(width, height, maxDimension) {
    const largestSide = Math.max(width, height);

    if (largestSide <= maxDimension) {
        return { width, height };
    }

    const scale = maxDimension / largestSide;

    return {
        width: Math.round(width * scale),
        height: Math.round(height * scale),
    };
}

function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => {
        canvas.toBlob(resolve, type, quality);
    });
}

function loadImageElement(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Image failed to load.'));
        };

        image.src = url;
    });
}

async function decodeImage(file) {
    if ('createImageBitmap' in window) {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            // Fall back to HTMLImageElement decoding for browsers without this option.
        }
    }

    return loadImageElement(file);
}

async function compressImage(file, options) {
    if (!IMAGE_MIME_TYPES.has(file.type)) {
        return file;
    }

    const image = await decodeImage(file);
    const sourceWidth = image.width || image.naturalWidth;
    const sourceHeight = image.height || image.naturalHeight;
    const { width, height } = targetSize(sourceWidth, sourceHeight, options.maxDimension);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    canvas.width = width;
    canvas.height = height;
    context.drawImage(image, 0, 0, width, height);

    if ('close' in image) {
        image.close();
    }

    const blob = await canvasToBlob(canvas, 'image/webp', options.quality);

    if (!blob) {
        return file;
    }

    if (file.type === 'image/webp' && blob.size >= file.size && width === sourceWidth && height === sourceHeight) {
        return file;
    }

    return new File([blob], webpName(file.name), {
        type: 'image/webp',
        lastModified: Date.now(),
    });
}

async function compressInputFiles(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }

    const transfer = new DataTransfer();
    const options = optionsForInput(input);

    for (const file of input.files) {
        try {
            transfer.items.add(await compressImage(file, options));
        } catch {
            transfer.items.add(file);
        }
    }

    input.files = transfer.files;
}

async function compressFormImages(form) {
    const inputs = form.querySelectorAll('input[type="file"]');
    const imageInputs = [...inputs].filter((input) => [...(input.files || [])].some((file) => IMAGE_MIME_TYPES.has(file.type)));

    await Promise.all(imageInputs.map(compressInputFiles));
}

function resetCompressionState(event) {
    const input = event.target;

    if (input instanceof HTMLInputElement && input.type === 'file' && input.form) {
        delete input.form.dataset.imagesCompressed;
    }
}

async function handleSubmit(event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.imagesCompressed === 'true') {
        return;
    }

    const hasImageFiles = [...form.querySelectorAll('input[type="file"]')]
        .some((input) => [...(input.files || [])].some((file) => IMAGE_MIME_TYPES.has(file.type)));

    if (!hasImageFiles) {
        return;
    }

    event.preventDefault();

    form.setAttribute('aria-busy', 'true');
    await compressFormImages(form);
    form.dataset.imagesCompressed = 'true';
    form.removeAttribute('aria-busy');

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(event.submitter || undefined);
        return;
    }

    form.submit();
}

if (supportsRequiredApis()) {
    document.addEventListener('change', resetCompressionState, true);
    document.addEventListener('submit', handleSubmit, true);
}
