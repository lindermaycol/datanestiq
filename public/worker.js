import { pipeline, env } from 'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js';

// Skip local check, fetch from CDN/IndexedDB cache
env.allowLocalModels = false;
env.useBrowserCache = true; // Use IndexedDB

class PipelineSingleton {
    static task = 'feature-extraction';
    static model = 'Xenova/paraphrase-multilingual-MiniLM-L12-v2';
    static instance = null;

    static async getInstance(progress_callback = null) {
        if (this.instance === null) {
            this.instance = pipeline(this.task, this.model, { 
                progress_callback,
                quantized: true,
                dtype: 'q8'
            });
        }
        return this.instance;
    }
}

self.addEventListener('message', async (event) => {
    const data = event.data;

    if (data.type === 'warmup') {
        try {
            await PipelineSingleton.getInstance(x => {
                self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
            });
            self.postMessage({ status: 'ready' });
        } catch (e) {
            self.postMessage({ status: 'error', error: e.message });
        }
        return;
    }

    const { query, corpusTexts, id } = data;

    let extractor = await PipelineSingleton.getInstance(x => {
        self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
    });

    try {
        let queryOutput = await extractor(query, { pooling: 'mean', normalize: true });
        let corpusOutput = await extractor(corpusTexts, { pooling: 'mean', normalize: true });

        const similarities = [];
        for (let i = 0; i < corpusTexts.length; ++i) {
            let sum = 0;
            for (let j = 0; j < queryOutput.data.length; ++j) {
                sum += queryOutput.data[j] * corpusOutput.data[i * queryOutput.data.length + j];
            }
            similarities.push({ index: i, score: sum });
        }

        similarities.sort((a, b) => b.score - a.score);
        self.postMessage({ status: 'complete', id, results: similarities });
    } catch (e) {
        self.postMessage({ status: 'error', error: e.message });
    }
});
